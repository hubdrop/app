<?php
namespace HubDrop\Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MirrorCommand extends Command
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
    $project_name = $input->getArgument('name');
    if ($project_name) {
      $text = 'Cloning ' . $project_name;
    } else {
      $text = 'No Project!';
    }
    //
    //if ($input->getOption('yell')) {
    //    $text = strtoupper($text);
    //}

    $output->writeln($text);
  }
}
