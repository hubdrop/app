<?php
/**
 * @file UpdateCommand.php
 */

namespace HubDrop\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:update')
      ->setDescription('Pull & Push a project repo.')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Which drupal project would you like to update?'
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
    $project->update();

    // Output a message.
    $out[] = "<info>[SUCCESS]</info> Project updated.";
    $output->writeln($out);
  }
}
