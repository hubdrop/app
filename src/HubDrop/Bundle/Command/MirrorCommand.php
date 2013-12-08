<?php
namespace HubDrop\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MirrorCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:mirror')
      ->setDescription('Create a new mirror of a Drupal.org project.')
      ->addArgument(
          'name',
          InputArgument::REQUIRED,
          'Which drupal project would you like to mirror?'
      )
      //->addOption(
      //   'yell',
      //   null,
      //   InputOption::VALUE_NONE,
      //   'If set, the task will yell in uppercase letters'
      //)
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get hubdrop service.
    $hubdrop = $this->getContainer()->get('hubdrop');

    // Prepare output lines.
    $out = array();

    // Get command argument.
    $project_name = $input->getArgument('name');

    // Get Project object (with check.
    $project = $hubdrop->getProject($project_name, TRUE);

    // Check if project already exists
    if ($project->github_project_exists){
      $output->writeln("<comment>[ERROR]</comment> GitHub project already exists: " . $project->getUrl('github'));
      return;
    }
    // check if there is no drupal project
    elseif (!$project->drupal_project_exists) {
      $output->writeln("<info>[ERROR]</info> Drupal.org project does not exist: " . $project->getUrl('drupal'));
      return;
    }
    // If there is a drupal project, we are ready to go.
    else {
      $out[] = '<info>[OK]</info> Preparing to mirror ' . $project->getUrl('drupal', 'http');
      $output->writeln($out); $out = array();

      // Create the GitHub Repo
      $repo = $hubdrop->createGitHubRepo($project_name);

      if ($repo['html_url']){
        $out[] = '<info>[OK]</info> GitHub Repo created! ' . $project->getUrl('github', 'web');
      }
      else {
        $out[] = '<warning>[ERROR]</warning>[> Something went wrong. GitHub Repo not created.';
      }
      $output->writeln($out);
    }
  }
}
