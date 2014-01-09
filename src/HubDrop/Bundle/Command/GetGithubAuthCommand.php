<?php

namespace HubDrop\Bundle\Command;

use Guzzle\Http\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Output\OutputInterface;

class GetGithubAuthCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:github_auth')
      ->setDescription('Generate a github authorization token.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get hubdrop service.
    $hubdrop = $this->getContainer()->get('hubdrop');
    $username = $hubdrop->github_username;

    // Get password
    $dialog = $this->getHelperSet()->get('dialog');
    $password = $dialog->askHiddenResponse($output, "What is the password for $username on GitHub? ");

    // Generates the token
    $token = $this->generateGitHubToken($username, $password);

    // Write to /etc/github_authorization_key
    exec("echo $token | sudo tee /etc/github_authorization_key");

    // Output to user.
    $output->writeln("Token written to /etc/github_authorization_key: $token");
  }

  /**
   * Generates a GitHub Token
   */
  protected function generateGitHubToken($username, $password){
    $client = new Client('https://api.github.com');
    $request = $client->post('/authorizations')
      ->setAuth($username, $password);

    $request->setBody('{"scopes": ["repo"]}', 'application/json');

    $response = $request->send();
    $data = json_decode($response->getBody());

    return $data->token;
  }
}
