<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;
use HubDrop\Bundle\Service\Project;

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
}

