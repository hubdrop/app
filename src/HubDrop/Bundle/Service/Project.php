<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;


class Project {

  // The drupal project we want to deal with.
  public $name;

  // All of the pertinant urls for this project, including local ones.
  public $urls;

  // Some easy access booleans
  public $drupal_project_exists = FALSE;
  public $github_project_exists = FALSE;

  /**
   * Initiate the project
   */
  public function __construct($name, $check = FALSE) {
    // Set properties
    $this->name = $name;
    $this->urls = array(
      'drupal' => array(
        'web' =>  "http://drupal.org/project/$name",
        'ssh' => "hubdrop@git.drupal.org/project/$name",
        'http' => "http://git.drupal.org/project/$name.git",
      ),
      'github' => array(
        'web' => "http://github.com/drupalprojects/$name",
        'ssh' => "git@github.com:drupalprojects/$name",
        'http' =>  "http://git.drupal.org/project/$name.git",
      ),
      'localhost' => array(
      // @TODO: Un-hardcode path?
        'file' => "/var/hubdrop/repos/$name.git",
      ),
    );

    // If $check, lookup if the projects exist.
    if ($check){
      $this->drupal_project_exists = (bool) $this->checkUrl();
      $this->github_project_exists = $this->drupal_project_exists?
        (bool) $this->checkUrl('github'):
        FALSE;
    }
  }

  /**
   * Return a specific URL
   */
  public function getUrl($remote, $type = 'web') {
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
    if ($remote == 'local' && $type == 'file'){
      return file_exists($this->getUrl($remote, $type));
    }
    else {
      $client = new Client();
      $url = $this->getUrl($remote, $type);

      // Check the HTTP response with Guzzle.
      try {
        $response = $client->get($url)->send()->getReasonPhrase();
        return TRUE;
      } catch (BadResponseException $e) {
        return FALSE;
      }
    }
  }
}

