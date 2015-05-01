<?php

/**
 * class Project
 *
 * Represents a Drupal project that is being mirrored by HubDrop.
 *
 */
namespace HubDrop\Bundle\Service;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\BadResponseException;
use Symfony\Component\HttpFoundation\Session\Session;


class Project {

  // The drupal project we want to deal with.
  public $name;

  // All of the pertinent urls for this project, including local ones.
  public $urls;

  // Whether or not the Drupal project exists
  public $exists = FALSE;

  // Whether or not the Drupal project has a mirror on GitHub
  public $mirrored = FALSE;

  // Whether or not the Drupal project is cloned locally.
  public $cloned = FALSE;

  // The default git branch this project is on.
  public $default_branch = '';

  // The current source of this project. (drupal or github)
  public $source = '';

  // GitHub committers
  public $committers = array();

  // GitHub admins
  public $admins = array();

  // GitHub Organization
  public $github_organization = '';

  /**
   * Initiate the project
   */
  public function __construct($name, HubDrop $hubdrop) {
    // Make our hubdrop service available to projects.
    $this->hubdrop = $hubdrop;

    // Set properties
    $this->name = $name = strtolower(trim($name));
    $this->urls = array(
      'drupal' => array(
        'web' =>  "http://drupal.org/project/{$this->name}",
        'ssh' => "{$hubdrop->drupal_username}@git.drupal.org:project/{$this->name}.git",
        'http' => "http://git.drupal.org/project/{$this->name}.git",
      ),
      'github' => array(
        'web' => "http://github.com/{$hubdrop->github_organization}/{$this->name}",
        'ssh' => "git@github.com:{$hubdrop->github_organization}/{$this->name}.git",
        'http' =>  "https://github.com/{$hubdrop->github_organization}/{$this->name}.git",
      ),
      'hubdrop' => array(
        'web' => "{$hubdrop->url}/project/{$this->name}",
      ),
      'localhost' => array(
        'path' => "/var/hubdrop/repos/{$this->name}.git",
      ),
    );

    // Check if the project has been cloned (and therefor, exists)
    $this->cloned = (bool) $this->checkUrl('localhost');

    // If it is cloned locally...
    if ($this->cloned){
      // We can assume the Drupal project exists.
      $this->exists = TRUE;

      // We will assume the clone is mirrored (until we can't).
      $this->mirrored = TRUE;

      // Get the default branch
      $this->default_branch = $this->getCurrentBranch();

      // Get the current source
      $this->source = $this->checkSource();

      // Get maintainers
      $config = $this->exec('git config -l');
      $config = explode("\n", $config);
      foreach ($config as $line){
        if (strpos($line, "hubdrop.committers") === 0){
          list($name, $value) = explode("=", $line);
          $names = explode('.', $name);
          $username = array_pop($names);
          $this->committers[$value] = array(
            'username' => $username,
            'uid' => $value,
          );
        }
        if (strpos($line, "hubdrop.admins") === 0){
          list($name, $value) = explode("=", $line);
          $names = explode('.', $name);
          $username = array_pop($names);
          $this->admins[$value] = array(
            'username' => $username,
            'uid' => $value,
          );
        }
      }

      // Get GitHub Organization
//      $remotes = explode("\n", $this->exec('git remote -v'));
//
//      foreach ($remotes as $line){
//        list($remote, $url, $type) = preg_split('/\s+/', $line);
////        origin	git@github.com:pcgroup/engageny2.git (push)
//        if ($remote == 'origin' && $type == '(push)'){
//          // @TODO: REGEX!
//          $url = str_replace('git@github.com:', 'http://github.com/', $url);
//          $url = parse_url($url);
//          $path = explode('/', $url['path']);
//          $this->github_organization = $path[0];
//          break;
//        }
//      }
      $this->github_organization = $this->hubdrop->github_organization;
    }
    // If it is not cloned locally...
    else {
      // Check to see if it exists
      $this->exists = (bool) $this->checkUrl('drupal');

      // If it does, check to see if it is mirrored yet.
      $this->mirrored = $this->exists?
        (bool) $this->checkUrl('github'):
        FALSE;

      // Set GitHub Org to match HubDrop
      $this->github_organization = $this->hubdrop->github_organization;
    }
  }

  /**
   * Gets the current "source" of the mirror.
   *
   * @return string
   *   "drupal" or "github"
   */
  public function checkSource() {
    $source = $this->exec('git config --get remote.origin.url');

    if (trim($source) == trim($this->getUrl('drupal', 'http'))){
      return 'drupal';
    }
    elseif (trim($source) == trim($this->getUrl('github', 'http'))) {
      return 'github';
    }
    else {
      return 'unknown';
    }
  }

  /**
   * Return a specific URL
   */
  public function getUrl($remote = 'drupal', $type = 'web') {
    if ($remote == 'localhost'){
      $type = 'path';
    }
    if (isset($this->urls[$remote]) && isset($this->urls[$remote][$type])){
      return $this->urls[$remote][$type];
    }
    else {
      // @TODO: Exceptions, anyone?
    }
  }

  /**
   * Check a URL's existence.
   *
   * @param string $remote
   *   Can be 'drupal', 'github', 'local'
   *
   * @param string $type
   *   Can be 'web' for the project's website, 'ssh' for the ssh clone url, or
   *   'http' for the http clone url.  'file' for local filepath.
   *
   * @return bool|\Guzzle\Http\Message\Response
   */
  public function checkUrl($remote = 'drupal', $type = 'web'){
    if ($remote == 'localhost'){
      return file_exists($this->getUrl($remote, 'path'));
    }
    else {
      $client = new GuzzleClient();
      $url = $this->getUrl($remote, $type);

      // Check the HTTP response with Guzzle.
      try {
        $client->get($url)->send()->getReasonPhrase();
        return TRUE;
      } catch (BadResponseException $e) {
        return FALSE;
      }
    }
  }

  /**
   * Queues the creation of a HubDrop Mirror.  This command should return as
   * quickly as possible. It simply tells Jenkins to run a job.
   *
   * This only works in a HubDrop Vagrant / Chef provisioned server
   */
  public function initMirror(){
    // if in a dev environment, send a flash message with the command to run.
    $session = new Session();

    // set flash messages
    $session->getFlashBag()->add('notice', "A mirror of " . $this->name . " is being created! Should be ready in a few moments.");

    // @TODO: Handle errors.
    $command = "jenkins-cli build create-mirror -p NAME={$this->name}";
    $output = shell_exec($command);
  }

  /**
   * Queues the update of HubDrop Mirror.  This command should return as
   * quickly as possible. It simply tells Jenkins to run a job.
   *
   * This only works in a HubDrop Vagrant / Chef provisioned server
   */
  public function initUpdate(){
    shell_exec('jenkins-cli build update-mirror -p NAME=' . $this->name);
  }

  /**
   * $project->mirror()
   * The actual creation process for a hubdrop mirror.
   *
   * This function can take a long time, so it is typically run from the
   * command line (manually or via a jenkins job).
   *
   * 1. Creates a Github repo of the same name (if needed).
   * 2. Clones the drupal.org project locally (if needed).
   * 3. Updates (pulls & pushes) the project.
   *
   * @see MirrorCommand.php
   *
   */
  public function mirror(){
    // If there is no drupal project, we can't mirror.
    if ($this->exists == FALSE){
      throw new Exception('Not a Drupal project.');
    }

    // Create the GitHub Repo (if it doesn't exist)
    // No exception because we want it to keep going.
    if ($this->mirrored == FALSE){
      $this->createRemote();
    }

    // Clone the Drupal Repo (if it doesn't exist)
    if ($this->cloned == FALSE){
      $this->cloneDrupal();
    }

    // Pull & Push
    $this->update();
  }

  /**
   * Pulls & Pushes the Project Repo.
   */
  public function update(){

    // Check if local clone exists
    if ($this->checkUrl("localhost") == FALSE){
      throw new \Exception("Project hasn't been cloned yet. Mirror it first.");
    }

    // Update all remotes
    $this->pushAndPull();

    // Get and set default branch
    $this->default_branch = $this->getCurrentBranch();
    $this->setDefaultBranch($this->default_branch);

  }

  /**
   * Pulls & Pushes the Project Repo.
   */
  private function pushAndPull(){
    // @TODO: ensure the remotes exist?
    $cmds = array();
    $cmds[] = "git fetch -p origin";
    $cmds[] = "git push origin";

    // @TODO: Throw an exception if something fails.
    chdir($this->getUrl('localhost', 'path'));
    foreach ($cmds as $cmd){
      exec($cmd);
    }
  }

  /**
   * Sets the remote source to drupal or github.
   */
  public function setSource($source, $update_maintainers = TRUE){

    // If the source USED to be drupal but is now github, create all teams
    if ($source == 'github' && $this->source == 'drupal' && $update_maintainers){
      $this->updateMaintainers();
      $this->updateWebhook();
    }

    // If the source USED to be github but is now Drupal, delete all teams
    if ($source == 'drupal' && $this->source == 'github'){
      $client = $this->hubdrop->getGitHubClient();
      $name = $this->name;
      $teams = $client->api('teams')->all($this->hubdrop->github_organization);
      foreach ($teams as $team){
        if ($team['name'] == "$name committers" || $team['name'] == "$name administrators"){
          $client->api('teams')->remove($team['id']);
        }
      }
    }

    // Change the remote URLs
    $cmds = array();
    chdir($this->getUrl('localhost', 'path'));
    if ($source == 'github'){
      $cmds[] = "git remote set-url --push origin " . $this->getUrl('drupal', 'ssh');
      $cmds[] = "git remote set-url origin " . $this->getUrl('github', 'ssh');
    }
    elseif ($source == 'drupal') {
      $cmds[] = "git remote set-url --push origin " . $this->getUrl('github', 'ssh');
      $cmds[] = "git remote set-url origin " . $this->getUrl('drupal', 'http');
    }
    foreach ($cmds as $cmd){
      exec($cmd);
    }
  }

  /**
   * Create the GitHub repository.
   */
  private function createRemote(){
    $name = $this->name;
    $client = $this->hubdrop->getGitHubClient();

    // Create the Repo on GitHub (this can be run by www-data, or any user.)
    try {
      $url = $this->getUrl();
      $repo = $client->api('repo')->create($name, "Mirror of $url provided by hubdrop.", $this->getUrl('hubdrop'), true, $this->hubdrop->github_organization);
    }
    catch (\Github\Exception\RuntimeException $e) {
      // If it already exists, that's ok, but alert the user.
      return FALSE;
    }
  }

  /**
   * Gets the current branch of the local repo.
   */
  public function getCurrentBranch(){
    // Look for default branch
    $default_branch = 'master';
    chdir($this->getUrl('localhost', 'path'));
    $cmd = "git branch -a";
    $branches = array();
    exec($cmd, $branches);
    foreach ($branches as $branch){
      // Sometimes there is no default set, like for help.git
      $default_branch = $branch;
      if (strpos($branch, '*') === 0){
        $default_branch = str_replace('* ', '', $branch);
        break;
      }
    }
    return $default_branch;
  }

  /**
   * Sets the default branch in the GitHub repo
   */
  public function setDefaultBranch($branch = NULL){

    // Use the project's branch, if no parameter is set.
    if (!$branch){
      $branch = $this->default_branch;
    }

    // Set the default branch of a repo.
    try {
      $client = $this->hubdrop->getGithubClient();
      $repo = $client->api('repo')->update($this->github_organization, $this->name, array('name' => $this->name, 'default_branch' => $branch));
    }
    catch (\Github\Exception\ValidationFailedException $e) {
      return FALSE;
    }
    catch (\Github\Exception\RuntimeException $e){
      // @TODO: Log something
      return FALSE;
    }
  }

  /**
   * $project->cloneDrupal()
   * Runs git clone
   */
  public function cloneDrupal(){
    $drupal_remote = $this->getUrl('drupal', 'http');
    $drupal_remote_ssh = $this->getUrl('drupal', 'ssh');
    $github_remote = $this->getUrl('github', 'ssh');
    $target_path = $this->getUrl('localhost', 'path');

    // Clone the repo
    $cmd = "git clone $drupal_remote $target_path --mirror";
    exec($cmd);

    // Set fetch configs to ignore github pull requests
    // See http://christoph.ruegg.name/blog/git-howto-mirror-a-github-repository-without-pull-refs.html
    chdir($target_path);

    $cmds = array();
    $cmds[] = "cd $target_path";

    // Removes the origin URL. we will push to the other remotes.
//    $cmds[] = 'git config --local --unset-all remote.origin.url';

    // Removes the "fetch all refs" config and adding back the refs we do want.
    // This prevents github pull requests from going through.
    // coming in.
    $cmds[] = 'git config --local --unset-all remote.origin.fetch';
    $cmds[] = 'git config --local remote.origin.fetch "+refs/tags/*:refs/tags/*"';
    $cmds[] = 'git config --local remote.origin.fetch "+refs/heads/*:refs/heads/*" --add';


    // Add remotes for github and drupal
    $git_remote = $this->getUrl('github', 'ssh');

    $cmds[] = "git remote add github $git_remote";
    $cmds[] = "git remote add drupal $drupal_remote_ssh";

    // Fix Refs
    $cmds[] = 'git config --local --unset-all remote.drupal.fetch';
    $cmds[] = 'git config --local remote.drupal.fetch "+refs/tags/*:refs/tags/*"';
    $cmds[] = 'git config --local remote.drupal.fetch "+refs/heads/*:refs/heads/*" --add';

    $cmds[] = 'git config --local --unset-all remote.github.fetch';
    $cmds[] = 'git config --local remote.github.fetch "+refs/tags/*:refs/tags/*"';
    $cmds[] = 'git config --local remote.github.fetch "+refs/heads/*:refs/heads/*" --add';

    // @TODO: Throw an exception if something fails.
    foreach ($cmds as $cmd){
      exec($cmd);
    }

    $this->cloned = TRUE;

    $this->setSource('drupal');
  }

  /**
   * Update GitHub.com project maintainers.
   */
  public function updateMaintainers(){
    // 0. Prepare GitHubClient
    $client = $this->hubdrop->getGithubClient();
    $name = $this->name;

    // 1. Lookup maintainers and admins from drupal.org
    $users = $this->getMaintainers();

    $members = array();
    $admins = array();

    $members_github = array();
    $admins_github = array();

    foreach ($users as $username => $user) {
      if (!empty($user['github_username']) && !empty($user['write'])){
        $members[$user['uid']] = $user['github_username'];
      }
      if (!empty($user['github_username']) && !empty($user['administer'])){
        $admins[$user['uid']] = $user['github_username'];
      }
    }

    // 2. Check for github teams. If doesn't exist, create it.
    // @TODO: move team creation to $this->createRepo()?
    $teams = $client->api('teams')->all($this->github_organization);
    foreach ($teams as $team){
      if ($team['name'] == "$name committers"){
        $team_id = $team['id'];
      }
      elseif ($team['name'] == "$name administrators"){
        $team_id_admin = $team['id'];
      }
    }

    // If team not found... create them.
    if (empty($team_id)){
      $vars = array(
        "name" => "$name committers",
        "permission" => "push",
        "repo_names" => array(
          "{$this->github_organization}/{$name}",
        ),
      );
      $team = $client->api('teams')->create($this->github_organization, $vars);
      $team_id = $team['id'];
    }

    // If admin team not found... create them.
    if (empty($team_id_admin)){

      // Create admin team
      $vars = array(
        "name" => "$name administrators",
        "permission" => "admin",
        "repo_names" => array(
          "{$this->github_organization}/{$name}",
        ),
      );
      $team = $client->api('teams')->create($this->github_organization, $vars);
      $team_id_admin = $team['id'];
    }

    // 3. Add all drupal maintainers to Push team, all admins to admin team
    foreach ($members as $uid => $member){
      if (!in_array($member, $members_github)){
        $client->api('teams')->addMember($team_id, $member);
        $this->exec("git config --add hubdrop.committers.$member $uid");
      }
    }
    foreach ($admins as $uid => $member){
      if (!in_array($member, $admins_github)){
        $client->api('teams')->addMember($team_id_admin, $member);
        $this->exec("git config --add hubdrop.admins.$member $uid");
      }
    }
  }

  /**
   * Logs into drupal.org with the password located at /etc/hubdrop_drupal_pass
   */
  public function getMaintainers(){

    // Throw exception if we haven't cloned it yet.
    if ($this->cloned == FALSE){
      throw new \Exception("This project hasn't been mirrored yet (on this server).  Run `hubdrop mirror {$this->name}` on the server to continue.");
    }

    // Get a Mink object
    $mink = new \Behat\Mink\Mink(array(
      'hubdrop' => new  \Behat\Mink\Session(new \Behat\Mink\Driver\GoutteDriver(new \Behat\Mink\Driver\Goutte\Client)),
    ));
    $mink->setDefaultSessionName('hubdrop');
    $mink->getSession()->visit($this->getUrl());

    // Visit the project page, then click "Log in / Register"
    $mink->getSession()->getPage()->findLink('Log in / Register')->click();

    // Fill out the login form and click "Log in"
    $page = $mink->getSession()->getPage();

    $username = 'hubdrop';
    if (file_exists('/var/hubdrop/.drupal-password')){
      $password = file_get_contents('/var/hubdrop/.drupal-password');
    } else {
      throw new \Exception("drupal.org user hubdrop password not found in /var/hubdrop/.drupal-password");
    }
    $el = $page->find('css', '#edit-name');
    $el->setValue($username);

    $el = $page->find('css', '#edit-pass');
    $el->setValue($password);

    $page->findButton('Log in')->click();

    // The project page, hopefully
    $link = $mink->getSession()->getPage()->findLink('Maintainers');
    if (!$link) {
      throw new \Exception('Unable to access project maintainers list. Add "hubdrop" to the project, allowing Write to VCS and Administer Maintainers.');
    }

    // Click "Maintainers"
    $link->click();
    $page = $mink->getSession()->getPage();

    // Find users
    $data = array();
    $users = $page->findAll('css', '#project-maintainers-form .username');
    $github_user_exists = FALSE;

    foreach ($users as $user){
      $uid = $user->getAttribute('data-uid');
      $username = $user->getText();

      // Lookup github username locally.
      if (file_exists('/var/hubdrop/users/' . $uid)){
        $github_username = file_get_contents('/var/hubdrop/users/' . $uid);
      }
      else {
        $github_username = $this->getGithubAccount($uid);
      }

      $data[$uid] = array(
        'uid' => $uid,
        'username' => $username,
        'github_username' => $github_username,
      );

      if ($github_username) {
        $github_user_exists = TRUE;
      }
    }

    // Fail if there is no github user.
    if ($github_user_exists == FALSE){
      throw new \Exception('Unable to detect GitHub accounts for the maintainers of this project.  Add the URL of your github user profile to your Drupal.org profile to get commit access to this project on GitHub.');
    }

    // Find permissions
    $permissions = $page->findAll('css', '#project-maintainers-form .form-checkbox');
    foreach ($permissions as $box){
      $id = $box->getAttribute('id');
      $id_parts = explode('-', $id);

      if (is_numeric($id_parts[2])){

        if (isset($data[$id_parts[2]][$id_parts[4]])){
          $data[$id_parts[2]][$id_parts[5]] = $box->isChecked();
        }
        else {
          $data[$id_parts[2]][$id_parts[4]] = $box->isChecked();
        }
      }
    }

    // @TODO: Throw exception if write to vcs is missing for hubdrop.
    return $data;
  }

  /**
   * Get a maintainer's github username and save it.
   */
  private function getGithubAccount($uid){

    // Get a Mink object
    $mink = new \Behat\Mink\Mink(array(
      'hubdrop_user' => new  \Behat\Mink\Session(new \Behat\Mink\Driver\GoutteDriver(new \Behat\Mink\Driver\Goutte\Client)),
    ));

    // Load the developer's page.
    $mink->setDefaultSessionName('hubdrop_user');

    // Visit the project page, then click "Log in / Register"
    $mink->getSession()->visit("http://drupal.org");
    $login_link = $mink->getSession()->getPage()->findLink('Log in / Register');

    // @TODO: figure out how to initiate one session so we only have to login once.
    // This is really slow

//    // If login link is present, login
//    // We are logging in to try to get the latest user profile pae.
//    // Drupal.org's caching delays changes to profiles.
//    if ($login_link){
//      $login_link->click();
//
//      // Fill out the login form and click "Log in"
//      $page = $mink->getSession()->getPage();
//
//      $username = 'hubdrop';
//      $password = file_get_contents('/etc/hubdrop_drupal_pass');
//
//      $el = $page->find('css', '#edit-name');
//      $el->setValue($username);
//
//      $el = $page->find('css', '#edit-pass');
//      $el->setValue($password);
//
//      $page->findButton('Log in')->click();
//    }

    // Look for a link to a github profile
    $mink->getSession()->visit("http://drupal.org/user/$uid");
    $github_profile_link = $mink->getSession()->getPage()->find('xpath', '//a[contains(@href, "github.com")]');

    // If there is a link, click it to make sure the user exists.
    if ($github_profile_link){

      // Click the link
      $github_profile_link->click();
      $github_profile_page = $mink->getSession()->getPage();

      // Check the response
      $response = $github_profile_page->getSession()->getDriver()->getClient()->getResponse()->getStatus();

      if ($response == 200){
        $username = $github_profile_page->find('css', '.vcard-username')->getText();

        // Write to local file
        if (!file_exists('/var/hubdrop/users')){
          mkdir('/var/hubdrop/users');
        }
        file_put_contents('/var/hubdrop/users/' . $uid, $username);
        return $username;
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Update GitHub.com project webhook..
   */
  public function updateWebhook(){

    // Get GitHub Client and hooks API.
    $client = $this->hubdrop->getGithubClient();
    $hooks = $client->api('repo')->hooks();

    $webhooks = $hooks->all($this->github_organization, $this->name);

    // Look for existing webhook
    foreach ($webhooks as $webhook){
      if ($webhook['config']['url'] == 'http://' . $this->hubdrop->url . '/webhook') {
        // @TODO: Send message about hook already existing.
        return $webhook['id'];
      }
    }

    // Create a webhook.
    $params = array();
    $params['name'] = 'web';
    $params['config'] = array();
    $params['config']['url'] = 'http://' . $this->hubdrop->url . '/webhook';
    $params['config']['content_type'] = 'json';

    $hook = $hooks->create($this->github_organization, $this->name, $params);
    return $hook['id'];
  }

  /**
   * Exec Helper
   */
  protected function exec($cmd){
    chdir($this->getUrl('localhost'));
    return shell_exec($cmd);
  }

  /**
   * Check Status
   */
  public function checkStatus(){
    // Check all http urls
    foreach ($this->urls as $remote => $urls){
      if (isset($urls['web'])){
        $web = $urls['web'];
        if ($this->checkUrl($remote)){
          $this->hubdrop->session->getFlashBag()->add('info', "$remote site exists at $web");
        }
        else {
          $this->hubdrop->session->getFlashBag()->add('info', "$remote site not found at $web");
        }
      }
    }
  }
}
