<?php
/**
 * @file config.php
 *
 * This file loads the github authorization from a file on the server.
 */

// Check for the file
if (file_exists('/etc/hubdrop-github-authorization')) {

  // Load key and set parameter.
  $key = file_get_contents('/etc/hubdrop-github-authorization');
  $container->setParameter('app.github.authorization', $key);
}
// If file doesn't exist, and not running the github_auth command, throw exception.
else {

  // Check for console command
  $input = new \Symfony\Component\Console\Input\ArgvInput();
  $is_auth_command = $input->getFirstArgument() == 'hubdrop:github_auth';

  if (!$is_auth_command){
    throw new Exception('Missing github authorization. Run `sudo hubdrop github_auth username` to generate one and write it to /etc/hubdrop-github-authorization.');
  }
}


