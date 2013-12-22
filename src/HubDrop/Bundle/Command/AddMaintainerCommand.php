<?php
/**
 * @file AddMaintainerCommand.php
 * The mirror command simply
 */


namespace HubDrop\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddMaintainerCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:maintainer:add')
      ->setDescription('Add a maintainer to the github repo.')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Which drupal project are we talkin, here?'
      )
      ->addArgument(
        'username',
        InputArgument::REQUIRED,
        'What is your drupal username?'
      )
      ->addOption(
        'github_username',
        null,
        InputOption::VALUE_OPTIONAL,
        'What is your github username, if different than your drupal username?'
      )
      ->addOption(
         'password',
         null,
         InputOption::VALUE_OPTIONAL,
         'What is Your drupal.org password?'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get hubdrop service & project.
    $hubdrop = $this->getContainer()->get('hubdrop');
    $project = $hubdrop->getProject($input->getArgument('name'));

    // Check if the user is a maintainer.
    $username = $input->getArgument('username');
    $password = $input->getOption('password');

    // Check for repo.  If not, mirror it.
    if (!$project->cloned){
      $project->mirror();
    }

    $project->checkMaintainership($username, $password);

  }
}