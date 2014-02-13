<?php
/**
 * @file config.php
 *
 * This file loads the github authorization from a file on the server.
 */

// Check for the file
if (file_exists('~/.github-authorization')) {

  // Load key and set parameter.
  $key = file_get_contents('~/.github-authorization');
  $container->setParameter('app.github.authorization', $key);
}
// If file doesn't exist, and not running the github_auth command, throw exception.
else {
  $container->setParameter('app.github.authorization', '');
//
//  if (!$is_auth_command){
//    throw new Exception('Missing github authorization. Run `hubdrop github_auth username` to generate one and write it to ~/.github-authorization.');
//  }
}


