<?php
/**
 * @file MirrorCommand.php
 * The mirror command simply
 */


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

    // Get Project object
    $project = $hubdrop->getProject($project_name);

    // Mirror that sucker.
    $project->mirror();

    // Output a message.
    // @TODO: Improve logging.
    $out[] = "<info>[SUCCESS]</info> Mirror successfully created!";
    $output->writeln($out);
  }
}
