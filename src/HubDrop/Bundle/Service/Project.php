<?php

/**
 * Our Service
 *
 * Put as much logic in here as possible
 */
namespace HubDrop\Bundle\Service;

class Project {

  // The drupal project we want to deal with.
  public $name;

public $drupal_http_url;
public $drupal_git_url;

public $github_http_url;
public $github_git_url;

public $local_clone_path;

  /**
   * Initiate the project
   */
  public function __construct($name) {
    $this->name = $name;

    $this->drupal_http_url = "http://drupal.org/project/$name";
    $this->drupal_git_ssh_url = "hubdrop@git.drupal.org/project/$name";
    $this->drupal_git_http_url = "http://git.drupal.org/project/$name.git";

    $this->github_http_url = "http://github.com/drupalprojects/$name";
    $this->github_git_ssh_url = "git@github.com:drupalprojects/$name";
    $this->github_git_http_url = "http://github.com/drupalprojects/$name";

    $this->local_clone_path = "/var/hubdrop/repos/$name.git";

  }
}

