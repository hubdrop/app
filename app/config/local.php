<?php

// Check for console
$input = new \Symfony\Component\Console\Input\ArgvInput();
$is_auth_command = $input->getFirstArgument() == 'hubdrop:github_auth';

// @TODO: Breaks all commands! Should only run on hubdrop commands.

// Check for server variables
$server_vars_exist = isset($_SERVER['SYMFONY__HUBDROP__GITHUB_AUTHORIZATION_KEY']);

// If the expected SERVER variables are not set AND we are not asking for
// github authorization, look for attributes.json
if (!$server_vars_exist){

  // Look for an attributes.json file. First hit wins.
  $path_suggestions = array();
  $path_suggestions[] = '/vagrant/attributes.json';
  $path_suggestions[] = '/var/hubdrop/app/attributes.json';
  // @TODO: add the users home directory.

  foreach ($path_suggestions as $path){
    if (file_exists($path)){
      $attributes = json_decode(file_get_contents($path));
      break;
    }
  }

  // Load JSON attributes
  // Bail out if it doesn't parse.
  if (!is_object($attributes)){
    throw new \Exception('Cannot find hubdrop $_SERVER variables or attributes.json file.');
  }

  // If missing authorization key...
  if (!$is_auth_command && empty($attributes->hubdrop->github->authorization_key)){
    throw new \Exception('Missing github authorization key.  Run `hubdrop:github_auth yourgithubusername` to get a key, then add to your attributes.json file.');
  }

  // Set our required parameters.
  $container->setParameter('hubdrop.github_username', $attributes->hubdrop->github->username);
  $container->setParameter('hubdrop.github_organization', $attributes->hubdrop->github->organization);
  $container->setParameter('hubdrop.github_authorization_key', $attributes->hubdrop->github->authorization_key);

  $container->setParameter('hubdrop.drupal_username', $attributes->hubdrop->drupal->username);

  $container->setParameter('hubdrop.url', $attributes->hubdrop->url);
}
