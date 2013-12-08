<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;

use HubDrop\Bundle\Service\Project;

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


    try {
      $repo = $client->api('repo')->create($name, "Mirror of $project->drupal_http_url provided by hubdrop.", $this->hubdrop_url, true, $this->github_organization);

      $this->github_repo = $repo;
      return $project;
    }
    catch (\Github\Exception\ValidationFailedException $e) {
      // For now we assume this is just a
      // "Validation Failed: name already exists on this account
      if ($e->getCode() == 422){
        //$output = '<p>Repo already exists on github: http://github.com/' . $this->github_org . '/' . $project_name . '</p>';

        return FALSE;
      }
      else {
        return FALSE;
      }
    }
  }
}

