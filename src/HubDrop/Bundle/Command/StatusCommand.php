<?php
namespace HubDrop\Bundle\Command;

//use HubDrop\Bundle\Service\HubDrop;
use Guzzle\Service\Command\LocationVisitor\Request;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:status')
      ->setDescription('Check the status of a certain project.')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Which drupal project are you talking about?'
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

    $out = array();
    $project_name = $input->getArgument('name');
    if ($project_name) {
      $out[] = 'Checking ' . $project_name;
    } else {
      $out[] = 'Which project?';
    }

    // Get hubdrop service.
    $hubdrop = $this->getContainer()->get('hubdrop');

    // Load a project
    $project = $hubdrop->getProject($project_name);

    // Check status.
    $project->checkStatus();
    $messages = $hubdrop->session->getFlashBag()->get('info');

    // @TODO: Setup proper exit code stuff.
    $output->writeln($messages);
  }
}
