<?php
/**
 * @file MirrorAllCommand.php
 * This command will look at any mirrors present on the github organization,
 * and clone them locally.  This is useful for dev environments.
 */


namespace HubDrop\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MirrorAllCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:mirror:all')
      ->setDescription('Create all mirrors based on the GitHub organization\'s repos.')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get hubdrop service.
    $hubdrop = $this->getContainer()->get('hubdrop');

    // Loop through all github repos.
    $output->writeln('<info>HUBDROP</info> Looking up all mirrors... this takes a moment.');
    $repos = $hubdrop->getAllMirrors();
    $missing_projects = array();

    $output->writeln(strtr('<info>HUBDROP</info> Found %total mirrors', array('%total' => count($repos))));

    foreach ($repos as $mirror){
      // Get the project
      $project = $hubdrop->getProject($mirror['name']);

      // If it is not yet cloned, clone it.
      if ($project->exists && !$project->cloned){
        $output->writeln("");
        $output->writeln("<info>HUBDROP</info> Cloning $project->name");
        $project->cloneDrupal();
      }
      elseif (!$project->exists) {
        $missing_projects[] = $project->name;
      }
    }

    if (count($missing_projects)){
      $output->writeln(strtr('<info>HUBDROP</info> Warning: %total github repos are not actually drupal projects: %list', array('%total' => count($missing_projects), '%list' => implode(', ', $missing_projects))));
    }
  }
}