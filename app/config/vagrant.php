<?php
// If there is an attributes.json file one level above the root of this repo,
// use that to override Symfony parameters. These typically come from the Apache Server variables,
// but this lets the console read vagrant attributes.json file when developing symfony locally.
$attributes_path = __DIR__ . '/../../../attributes.json';
$is_console_server = file_exists($attributes_path);

if ($is_console_server){

  // Load JSON attributes
  $attributes = json_decode(file_get_contents($is_console_server?  $attributes_path: '/vagrant/attributes.json'));

  if (!is_object($attributes)){
    return;
  }

  $container->setParameter('hubdrop.github_username', $attributes->hubdrop->github->organization);
  $container->setParameter('hubdrop.github_organization', $attributes->hubdrop->github->organization);
  $container->setParameter('hubdrop.github_authorization_key', $attributes->hubdrop->github->authorization_key);

  $container->setParameter('hubdrop.drupal_username', $attributes->hubdrop->drupal->username);

  $container->setParameter('hubdrop.url', $attributes->hubdrop->url);
}
