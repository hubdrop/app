<?php
/**
 * @file config.php
 *
 * This file loads the github authorization from a file on the server.
 */


// Check for the file
$hubdrop_path_to_github_auth = $container->getParameter('hubdrop.paths.github_authorization');
if (file_exists($hubdrop_path_to_github_auth)) {

  // Load key and set parameter.
  $key = file_get_contents($hubdrop_path_to_github_auth);
  $container->setParameter('app.github.authorization', $key);
}
// If file doesn't exist set an empty parameter.
else {
  $container->setParameter('app.github.authorization', '');
}

