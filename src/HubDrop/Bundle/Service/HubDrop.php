<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;

use HubDrop\Bundle\Service\Project;
use Github\Client as GithubClient;


class HubDrop {

 /**
  * @TODO: Use a SERVER variable (or something) for these.
  *
  * These should originate as Chef attributes, and percolate through as
  * Symfony app config.
  */
  public $hubdrop_url = 'http://hubdrop.io';
  public $github_organization = 'drupalprojects';
  public $repo_path = '/var/hubdrop/repos';

  /**
   * This was retrieved using a github username and password with curl:
   * curl -i -u <github_username> -d '{"scopes": ["repo"]}' https://api.github.com/authorizations
   * @TODO: We should generate a new key in the chef cookbooks, as a
   * part of the vagrant up/deployment process.
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
  private function getGitHubToken(){
    return $this->github_application_token;
  }

  /**
   * Lookup all mirrors on github
   */
  public function getAllMirrors(){

    // Lookup all repositories for this github organization.
    $client = new GithubClient();
    $client->authenticate($this->getGitHubToken(), '', \GitHub\Client::AUTH_URL_TOKEN);

    try {
//      $repos = $client->api('repos');
      $repos = $client->api('organization')->repositories($this->github_organization);
      return $repos;
    }
    catch (ValidationFailedException $e) {
      return NULL;
    }
  }
}

