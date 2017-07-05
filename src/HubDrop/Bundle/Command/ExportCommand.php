<?php
namespace HubDrop\Bundle\Command;

//use HubDrop\Bundle\Service\HubDrop;
use Guzzle\Service\Command\LocationVisitor\Request;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;

class ExportCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('hubdrop:export')
      ->setDescription('Save project sources to a file.')
      ->addOption(
        'file',
        'f',
        InputOption::VALUE_OPTIONAL,
        'The name of the file to save. Defaults to ~/sources.yml',
        $_SERVER['HOME'] . '/sources.yml'
      )
    ;
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
          $project = $hubdrop->getProject($project_name);

          // Save to YML output array.
          $sources_data[$project_name] = $project->source;
        }
      }
      closedir($handle);
    }

    $file_path = realpath($input->getOption('file'));
    $dumper = new Dumper();
    if (file_put_contents($file_path, $dumper->dump($sources_data, 4))) {
      $output->writeln([
        'File written to ' . $file_path
      ]);
    }

  }
}
