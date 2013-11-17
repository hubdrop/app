<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle;

class HubDrop {

  // The drupal project we want to deal with.
  protected $project;

  protected $drupal_http_url;
  protected $drupal_git_url;

  protected $github_http_url;
  protected $github_git_url;

  /**
   * Initiate our service
   */
  public function __construct($project) {
    $this->project = $project;

    $this->drupal_http_url = "http://drupal.org/project/$project";
    $this->drupal_git_url = "hubdrop@git.drupal.org/project/$project";
    $this->drupal_git_url_public = "";

    $this->github_http_url = "http://github.com/drupalprojects/$project";
    $this->github_git_url = "git@github.com:drupalprojects/$project";

  }
}

