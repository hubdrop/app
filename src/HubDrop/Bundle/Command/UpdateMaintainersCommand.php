<?php
/**
 * @file AddMaintainerCommand.php
 * The mirror command simply
 */


namespace HubDrop\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMaintainersCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:update_maintainers')
      ->setDescription('Update GitHub maintainers based on Drupal.org info.')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Which drupal project are we talkin, here?'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get hubdrop service & project.
    $hubdrop = $this->getContainer()->get('hubdrop');
    $project = $hubdrop->getProject($input->getArgument('name'));

    // Update maintainers
    $project->updateMaintainers();
  }
}