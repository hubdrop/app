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
      ->setDescription('Update a mirror of HubDrop.')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Which drupal project are you talking about?'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $name = $input->getArgument('name');

    $out = array();
    $out[] = "";
    $out[] = "<info>HUBDROP</info> Updating mirror of $name";
    $output->writeln($out);

    $project = $this->getContainer()->get('hubdrop')
      ->getProject($input->getArgument('name'));
    $project->update();
  }
}
