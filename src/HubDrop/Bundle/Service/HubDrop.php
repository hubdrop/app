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

  /**
   * HubDrop Attributes.
   * @see \src\HubDrop\Bundle\Resources\config\services.yml
   *
   * These are passed all the way from Vagrant/Chef attributes.
   *
   */
  public $github_username;
  public $github_organization;
  protected $github_authorization_key;

  public $drupal_username;
  public $url;

  /**
   * Symfony router and session.
   */
  public $router;
  public $session;

  public function __construct(
    $github_username,
    $github_organization,
    $github_authorization_key,
    $drupal_username,
    $url,
    Router $router, Session $session
  ) {

    $this->github_username = $github_username;
    $this->github_organization = $github_organization;
    $this->github_authorization_key = $github_authorization_key;

    $this->drupal_username = $drupal_username;
    $this->url = $url;

    $this->router = $router;
    $this->session = $session;
  }

  /**
   * Get a Project Object
   */
  public function getProject($name){
    return new Project($name, $this);
  }

  /**
   * Get a authenticated GitHub Client Object
   */
  public function getGithubClient(){
    $client = new \Github\Client();
    $client->authenticate($this->github_authorization_key, '', \GitHub\Client::AUTH_URL_TOKEN);
    return $client;
  }

  /**
   * Lookup all mirrors on github
   */
  public function getAllMirrors(){

    // Lookup all repositories for this github organization.
    $client = new \Github\Client();
    $client->authenticate($this->github_authorization_key, '', \GitHub\Client::AUTH_URL_TOKEN);

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