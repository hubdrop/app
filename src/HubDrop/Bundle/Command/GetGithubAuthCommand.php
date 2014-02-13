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
//    // Get hubdrop service and github username.
//    $hubdrop = $this->getContainer()->get('hubdrop');
//    $username = $hubdrop->github_username;

    // Get password
    $dialog = $this->getHelperSet()->get('dialog');
    $username = $dialog->ask($output, "GitHub Username? ");
    $password = $dialog->askHiddenResponse($output, "GitHub Password? ");

    // @TODO: We should lookup existing tokens and display them.

    // Generates the key
    $key = $this->generateGitHubToken($username, $password);

    // Output to user.
    $output->writeln("Token created: $key");

    // Ask to write to file
    if ($dialog->askConfirmation(
      $output,
      '<question>Write to /etc/hubdrop-github-authorization?</question> ',
      false
    )){
      if (file_put_contents('/etc/hubdrop-github-authorization', $key)){
        $output->writeln("Wrote to /etc/hubdrop-github-authorization");
      }
      else {
        $output->writeln("Could not write to /etc/hubdrop-github-authorization. Try:");
        $output->writeln(" sudo hubdrop github_auth");
        $output->writeln("            -or- ");
        $output->writeln(" echo '$key' | sudo tee -a /etc/hubdrop-github-authorization");
      }
    }
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
