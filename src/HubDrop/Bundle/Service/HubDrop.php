<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;

use HubDrop\Bundle\Service\Project;

use Guzzle\Http\Client;
use Github\Client as GithubClient;

class HubDrop {

  public $hubdrop_url = 'http://hubdrop.io';
  public $github_organization = 'drupalprojects';

  public $github_client;  // GithubClient object
  public $github_repo;    // GitHub API Repo object

  /**
   * This was retrieved using a github username and password with curl:
   * curl -i -u <github_username> -d '{"scopes": ["repo"]}' https://api.github.com/authorizations
   */
  private $github_application_token = '2f3a787bc2881ac86d0277b37c1b9a67c4c509bb';

  /**
   * Get a Project Object
   */
  public function getProject($name, $check = FALSE){
     return new Project($name, $check);
  }

  /**
   * Get a GitHub Token
   */
  public function getGitHubToken(){
    return $this->github_application_token;
  }

  /**
   * Create a GitHub Repo.
   */
  public function createGitHubRepo($name){

    $client = new GithubClient();
    $client->authenticate($this->github_application_token, '', \Github\Client::AUTH_URL_TOKEN);

    $project = new Project($name);

    try {

      // Create the Repo on GitHub (this can be run by www-data, or any user.)
      $url = $project->getUrl('drupal');
      $repo = $client->api('repo')->create($name, "Mirror of $url provided by hubdrop.", $this->hubdrop_url, true, $this->github_organization);

      // Fire the Jenkins build for cloning the mirror locally.
      // (Since we are cloning files we need to run this as the hubdrop user.
      $repo['hubdrop_jenkins_output'] = shell_exec('jenkins-cli build hubdrop-jenkins-create-mirror -p NAME=' . $name);
      return $repo;
    }
    catch (ValidationFailedException $e) {
      return FALSE;
    }
  }
}

