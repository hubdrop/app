<?php

namespace HubDrop\Bundle\Command;

use Guzzle\Http\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:configure')
      ->setDescription('Configure hubdrop authorizations.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Check if authorization already exists
    $dialog = $this->getHelperSet()->get('dialog');
    $hubdrop_path_to_github_auth = '/var/hubdrop/.github-authorization';
    $hubdrop_path_to_drupal_pass = '/var/hubdrop/.drupal-password';

    // Ask to create a new password file.
    if (file_exists($hubdrop_path_to_github_auth)){
      if ($dialog->askConfirmation(
        $output,
        "Drupal user password file already exists at $hubdrop_path_to_drupal_pass.  Generate a new one? ",
        false
      )){
        $need_new_pass = TRUE;
      }
      else {
        $need_new_pass = FALSE;
        $password = file_get_contents($hubdrop_path_to_drupal_pass);
      }
    }
    else {
      $need_new_pass = TRUE;
    }

    // If need new password file...
    if ($need_new_pass){
      $password = $dialog->askHiddenResponse($output, "Drupal.org password? ");

      // Ask to write to file
      if ($dialog->askConfirmation(
        $output,
        "Write to <comment>$hubdrop_path_to_drupal_pass</comment>?</question> ",
        false
      )){
        if (file_put_contents($hubdrop_path_to_drupal_pass, $password)){
          $output->writeln("Wrote to $hubdrop_path_to_drupal_pass.");
        }
        else {
          $output->writeln("Could not write to $hubdrop_path_to_drupal_pass.");
        }
      }
    }

    // Ask to create a new authorization.
    if (file_exists($hubdrop_path_to_github_auth)){
      if ($dialog->askConfirmation(
        $output,
        "You already have an authorization.  Generate a new one? ",
        false
      )){
        $need_new_code = TRUE;
      }
      else {
        $need_new_code = FALSE;
        $authorization = file_get_contents($hubdrop_path_to_github_auth);
      }
    }
    else {
      $need_new_code = TRUE;
    }

    // If need new code...
    if ($need_new_code){
      $hubdrop_github_org = $this->getContainer()->getParameter('hubdrop.github_organization');
      $hubdrop_github_username = $this->getContainer()->getParameter('hubdrop.github_username');

      // Note to user
      $output->writeln("Enter your credentials to generate a GitHub Authorization token.");
      $output->writeln("The user must have the ability to create repos in the <comment>$hubdrop_github_org</comment> organization.");

      // Get password
      $username = $dialog->ask($output, "GitHub Username (default: $hubdrop_github_username)? ", $hubdrop_github_username);
      $password = $dialog->askHiddenResponse($output, "GitHub Password? (Won't be stored.)");
      $client_id = $dialog->ask($output, "GitHub Application Client ID");
      $client_secret = $dialog->ask($output, "GitHub Application Client Secret");

      // Generates the key
      $authorization = $this->generateGitHubToken($username, $password, $client_id, $client_secret);

      if ($authorization) {
        // Output to user.
        $output->writeln("Token created: $authorization");

        // Ask to write to file
        if ($dialog->askConfirmation(
          $output,
          "Write to <comment>$hubdrop_path_to_github_auth</comment>?</question> ",
          false
        )){
          if (file_put_contents($hubdrop_path_to_github_auth, $authorization)){
            $output->writeln("Wrote to $hubdrop_path_to_github_auth.");
          }
          else {
            $output->writeln("Could not write to $hubdrop_path_to_github_auth.");
          }
        }
      }
      else {
        $output->writeln("<error>Authorization creation failed!</error>");
        return;
      }
    }
    // Doesn't need new code: we still need username.
    else {
      $username = $this->getContainer()->getParameter('hubdrop.github_username');

    }

    // Ask to push up ssh key.
    if (file_exists('/var/hubdrop/.ssh/id_rsa.pub')){
      if ($dialog->askConfirmation(
        $output,
        "Upload SSH key to <comment>$username's</comment> account? ",
        false
      )){

        // Post SSH key.
        $url = $this->getContainer()->getParameter('hubdrop.url');
        $title = "$username@$url";
        $key = file_get_contents('/var/hubdrop/.ssh/id_rsa.pub');

        $params = array(
          'title' => $title,
          'key' => $key,
        );

        $client = $this->getContainer()->get('hubdrop')->getGithubClient($authorization);

        $api = $client->api('current_user');
        $keys = $api->keys();
        $response = $keys->create($params);

        if (empty($response['id'])){
          throw new Exception('Something failed when uploading your public key.');
        }
        else {
          $output->writeln("<info>SSH Key added to $username's github account.</info>");
        }
      }
      else {
        $output->writeln("<warning>SSH Key not found at '/var/hubdrop/.ssh/id_rsa.pub'</warning> You must have an SSH key to mirror repositories.");
      }
    }
  }

  /**
   * Generates a GitHub Token
   */
  protected function generateGitHubToken($username, $password, $client_id, $client_secret){
    $client = new Client('https://api.github.com');


    $params = new \stdClass();
    $params->client_secret = $client_secret;
    $params->scopes = array('repo', 'user');
    $params->note = 'HubDrop Authorization';
    $params->note_url =  $this->getContainer()->getParameter('hubdrop.url');

    $request = $client->put('/authorizations/clients/' . $client_id)
      ->setAuth($username, $password);

    $request->setBody(json_encode($params), 'application/json');

    // @TODO: Throw our own exception.
    try {
      $response = $request->send();
      $data = json_decode($response->getBody());
      return $data->token;
    }
    catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {

      print (string) $e->getResponse();
      return FALSE;
    }
  }
}
