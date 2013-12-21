<?php
/**
 * @file SourceCommand.php
 */

namespace HubDrop\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SourceCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:source')
      ->setDescription('Set the source of a hubdrop mirror. Must be github or drupal.')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Which drupal project would you like to set the source for?'
      )
      ->addArgument(
        'source',
        InputArgument::REQUIRED,
        'Which remote would you like to be the primary source?'
      )
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

    // Get command argument.
    $source = $input->getArgument('source');

    // Get Project object
    $project = $hubdrop->getProject($project_name);

    // Mirror that sucker.
    $project->setSource($source);

    // Output a message.
    $out[] = "<info>[SUCCESS]</info> Source set.";
    $output->writeln($out);
  }
}
