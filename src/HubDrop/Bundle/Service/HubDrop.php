<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;

class HubDrop {

  public function __construct($github_username, $github_organization, $drupal_username, $hubdrop_url, Router $router, Session $session)
  {
//    // Get application token form /etc/github_application_token
//    if (!file_exists('/etc/github_application_token')){
//      throw new \Exception('GitHub Application Token not found at /etc/github_application_token. Run hubdrop github-token <github_username>');
//    }

    if (file_exists('/etc/github_application_token')){
      $this->github_application_token = file_get_contents('/etc/github_application_token');
    }

    $this->github_username = $github_username;
    $this->github_organization = $github_organization;
    $this->drupal_username = $drupal_username;
    $this->hubdrop_url = $hubdrop_url;

    $this->router = $router;
    $this->session = $session;
  }

  // HubDrop Attributes.  @see \src\HubDrop\Bundle\Resources\config\services.yml
  private $github_organization;
  private $drupal_username;
  private $hubdrop_url;

  private $github_application_token;

  private $router;
  private $session;

//  public $repo_path = '/var/hubdrop/repos';

  /**
   * Get a Project Object
   */
  public function getProject($name){
     return new Project(
       $name,
       $this->github_organization,
       $this->drupal_username,
       $this->hubdrop_url,
       $this->github_application_token,
       $this->router,
       $this->session
     );
  }

  /**
   * Lookup all mirrors on github
   */
  public function getAllMirrors(){

    // Lookup all repositories for this github organization.
    $client = new \Github\Client();
    $client->authenticate($this->github_application_token, '', \GitHub\Client::AUTH_URL_TOKEN);

    try {
      $api = $client->api('organization');
      $paginator  = new \Github\ResultPager($client);
      $parameters = array($this->github_organization);
      $repos = $paginator->fetchAll($api, 'repositories', $parameters);
      return $repos;
    }
    catch (ValidationFailedException $e) {
      return NULL;
    }
  }
}

