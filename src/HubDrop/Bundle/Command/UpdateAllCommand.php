<?php
/**
 * @file UpdateAllCommand.php
 */


namespace HubDrop\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAllCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:update:all')
      ->setDescription('Update all mirrors of HubDrop.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get hubdrop service.
    $hubdrop = $this->getContainer()->get('hubdrop');

    // Loop through all repo folders.
    if ($handle = opendir($hubdrop->repo_path)) {
      $blacklist = array('.', '..');
      while (false !== ($file = readdir($handle))) {
        if (!in_array($file, $blacklist)) {
          $project_name = str_replace(".git", "", $file);

          $out = array();
          $out[] = "";
          $out[] = "<info>HUBDROP</info> Updating mirror of $project_name";
          $output->writeln($out);

          $project = $hubdrop->getProject($project_name);
          if ($project->source == 'drupal') {
            $project->update();
          } 
          else {
            $output->write('skipping project, source is github.');
          }
        }
      }
      closedir($handle);
    }
  }
}
