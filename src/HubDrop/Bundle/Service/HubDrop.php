<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

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
  public $drupal_password;
  public $url;
  public $repo_path;

  public $jenkins_url;
  public $jenkins_username;
  public $jenkins_password;

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
    $drupal_password,
    $url,
    $jenkins_url,
    $jenkins_username,
    $jenkins_password,
    $repo_path,
    Router $router, Session $session
  ) {
    $this->github_username = $github_username;
    $this->github_organization = $github_organization;
    $this->github_authorization_key = $github_authorization_key;

    $this->drupal_username = $drupal_username;
    $this->drupal_password = $drupal_password;
    $this->url = $url;
    $this->jenkins_url = $jenkins_url;
    $this->jenkins_username = $jenkins_username;
    $this->jenkins_password = $jenkins_password;

    $this->router = $router;
    $this->session = $session;

    // If running from command line, CWD is the project root.
    // If Repo Path is relative... we need to alter it to match the CWD.
    $fs = new Filesystem();

    if ($fs->isAbsolutePath($repo_path)) {
      $this->repo_path = $repo_path;
    }
    else {
      // If running from web, CWD is "web".
      if (file_exists('app.php')) {
        $this->repo_path = "../" . $repo_path;
      }
      else {
        $this->repo_path = realpath($repo_path);
      }
    }
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
  public function getGithubClient($key = NULL){
    if (is_null($key)){
      $key = $this->github_authorization_key;
    }
    // Create the Repo on GitHub (this can be run by www-data, or any user.)
    $client = new \Github\Client();
    $client->authenticate($key, '', \GitHub\Client::AUTH_URL_TOKEN);
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