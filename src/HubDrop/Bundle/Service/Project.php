<?php

/**
 * class Project
 *
 * Represents a Drupal project that is being mirrored by HubDrop.
 *
 */
namespace HubDrop\Bundle\Service;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Github\Client as GithubClient;

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

  // @TODO: Move to HubDrop service and use service parameters to store default.
  // These are really a part of the larger HubDrop service, but I
  // haven't learned how to access that from a Project class yet!
  private $github_organization = 'drupalprojects';

  /**
   * This was retrieved using a github username and password with curl:
   * curl -i -u <github_username> -d '{"scopes": ["repo"]}' https://api.github.com/authorizations
   * @TODO: We should generate a new key in the chef cookbooks, as a
   * part of the vagrant up/deployment process.
   */
  private $github_application_token = '2f3a787bc2881ac86d0277b37c1b9a67c4c509bb';

  /**
   * Initiate the project
   */
  public function __construct($name) {
    // Set properties
    // @TODO: Un-hardcode these properties.
    $this->name = $name;
    $this->urls = array(
      'drupal' => array(
        'web' =>  "http://drupal.org/project/$name",
        'ssh' => "hubdrop@git.drupal.org:project/$name.git",
        'http' => "http://git.drupal.org/project/$name.git",
      ),
      'github' => array(
        'web' => "http://github.com/drupalprojects/$name",
        'ssh' => "git@github.com:drupalprojects/$name.git",
        'http' =>  "https://github.com/project/$name.git",
      ),
      'hubdrop' => array(
        'web' => "http://hubdrop.io/project/$name",
      ),
      'localhost' => array(
        'path' => "/var/hubdrop/repos/$name.git",
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
    }
    // If it is not cloned locally...
    else {
      // Check to see if it exists
      $this->exists = (bool) $this->checkUrl('drupal');

      // If it does, check to see if it is mirrored yet.
      $this->mirrored = $this->exists?
        (bool) $this->checkUrl('github'):
        FALSE;
    }
  }

  /**
   * Return a specific URL
   */
  public function getUrl($remote = 'drupal', $type = 'web') {
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
      $client = new Client();
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

    // @TODO: Handle errors and send flash message to user.
    $output = shell_exec('jenkins-cli build hubdrop-jenkins-create-mirror -p NAME=' . $this->name);
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
      throw new NoProjectException('Not a Drupal project.');
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
      throw new NotClonedException("Project hasn't been cloned yet. Mirror it first.");
    }

    $cmds = array();
    $cmds[] = "git fetch -p origin";
    $cmds[] = "git push --mirror";

    // @TODO: Throw an exception if something fails.
    chdir($this->getUrl('localhost', 'path'));
    foreach ($cmds as $cmd){
      print $cmd . "\n";
      exec($cmd);
    }
  }

  /**
   * Pulls & Pushes the Project Repo.
   */
  public function setSource($source){
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
    $client = new GithubClient();
    $client->authenticate($this->github_application_token, '', \GitHub\Client::AUTH_URL_TOKEN);

    // Create the Repo on GitHub (this can be run by www-data, or any user.)
    try {
      $url = $this->getUrl();
      $repo = $client->api('repo')->create($name, "Mirror of $url provided by hubdrop.", $this->getUrl('hubdrop'), true, $this->github_organization);
      $this->github_repo = $repo;
    }
    catch (ValidationFailedException $e) {
      // If it already exists, that's ok, but alert the user.
      // @TODO: Learn Symfony best practices for logging, error handling...
      return FALSE;
    }
  }

  /**
   * $project->cloneDrupal()
   * Runs git clone
   */
  public function cloneDrupal(){
    $drupal_remote = $this->getUrl('drupal', 'http');
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
    $cmds[] = 'git config --local --unset-all remote.origin.fetch';
    $cmds[] = 'git config --local remote.origin.fetch "+refs/tags/*:refs/tags/*"';
    $cmds[] = 'git config --local remote.origin.fetch "+refs/heads/*:refs/heads/*" --add';

    // Set push url to github
    // @TODO: There will be a switchRemote command.
    $cmds[] = "git remote set-url --push origin $github_remote";

    // @TODO: Throw an exception if something fails.
    foreach ($cmds as $cmd){
      exec($cmd);
    }

    $this->cloned = TRUE;
  }

  /**
   * Quickly checks if a user is a maintainer of a module.
   * @param $username
   * @param $password
   */
  public function checkMaintainership($username, $password){
    $name = $this->name;

    // Throw exception if we haven't cloned it yet.
    if ($this->cloned == FALSE){
      throw new NotClonedException("THIS project hasn't been cloned yet.");
    }

    // If clone doesn't exist, clone it
    if (!file_exists("/var/hubdrop/repos/$name")){
      exec("git clone /var/hubdrop/repos/$name.git /var/hubdrop/repos/$name");
    }

    chdir("/var/hubdrop/repos/$name");

    $cmds = array();
    $cmds[] = "git remote add drupal-$username $username@git.drupal.org:project/$name";
    $cmds[] = "touch HUBDROP_TEST_COMMIT";
    $cmds[] = "git add HUBDROP_TEST_COMMIT";
    $cmds[] = "git commit -m 'HUBDROP: Testing if $username has access to this repo.'";
    $cmds[] = "git rm HUBDROP_TEST_COMMIT";
    $cmds[] = "git commit -m 'HUBDROP: Removing access test file.'";

    foreach ($cmds as $cmd){
      exec($cmd);
    }

    // Test if this command works
    print shell_exec("git push drupal-$username");
  }
}


class NoProjectException extends \Exception { }
class NotClonedException extends \Exception { }
