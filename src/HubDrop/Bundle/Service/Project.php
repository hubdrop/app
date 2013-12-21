<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Symfony\Component\Debug\ExceptionHandler;
use Github\Client as GithubClient;

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

  // @TODO: Remove.
  public $clone_exists = FALSE;
  public $drupal_project_exists = FALSE;
  public $github_project_exists = FALSE;

  // @TODO: Move to HubDrop service and use config.yml to store default.
  // These are really a part of the larger HubDrop service, but I
  // haven't learned how to access that from a Project class yet!
  public $github_organization = 'drupalprojects';
  private $local_path = '/var/hubdrop/repos';
  private $jenkins_url = 'http://hubdrop:8080';

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
      return file_exists($this->getUrl($remote, $type));
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
   * Project->mirror()
   * The creation process for a hubdrop mirror.
   *
   * This function can take a long time, so it is typically run by a
   * jenkins job, or from the command line.
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
    if (!$this->checkUrl("localhost", "path")){
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
  private function cloneDrupal(){
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
  }
}


class NoProjectException extends \Exception { }
class NotClonedException extends \Exception { }
