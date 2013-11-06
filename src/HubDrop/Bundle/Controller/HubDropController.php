<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Guzzle\Http\Client;

class HubDropController extends Controller
{
  private $repo_path = '/var/hubdrop/repos';
  private $github_org = 'hubdrop-projects';

  /**
   * Homepage
   */
  public function homeAction()
  {
    // @TODO: Goto Project form
    return $this->render('HubDropBundle:HubDrop:home.html.twig', array(
      'site_base_url' => $this->getRequest()->getHost(),
    ));
  }

  /**
   * Mirror a Project.
   */
  private function mirrorProject($project_name)
  {
    // @TODO:
    //  1. Initiate GitHub API and create a repo.
    //  2. exec hubdrop-create-mirror.
    //  3. Replace exec with a jenkins job.
    return new Response("mirroring $project_name...");
  }

  /**
   * Project View Page
   */
  public function projectAction($project_name)
  {

    $params = array();
    $params['project_ok'] = FALSE;
    $params['project_cloned'] = FALSE;

    $go_mirror = $this->get('request')->query->get('mirror');

    // If local repo exists...
    if (file_exists($this->repo_path . '/' . $project_name . '.git')){
      $params['project_cloned'] = TRUE;
      $params['github_web_url'] = "http://github.com/" . $this->github_org . '/' . $project_name;
      $params['project_ok'] = TRUE;
    }
    // Else If local repo doesn't exist...
    else {
      // Look for drupal.org/project/{project_name}
      $client = new Client('http://drupal.org');
      try {
        $response = $client->get('/project/' . $project_name)->send();
        $params['project_ok'] = TRUE;

        // Mirror: GO!
        // We only want to try to mirror a project if not yet cloned and it
        // exists.
        if ($go_mirror == 'go'){
          return $this->mirrorProject($project_name);
        }

      } catch (\Guzzle\Http\Exception\BadResponseException $e) {
        $params['project_ok'] = FALSE;
      }
    }

    // Build template params
    $params['project_name'] = $project_name;
    $params['project_drupal_url'] = "http://drupal.org/project/$project_name";

    if ($params['project_ok']){
      $params['project_drupal_git'] = "http://git.drupal.org/project/$project_name.git";
    }
    return $this->render('HubDropBundle:HubDrop:project.html.twig', $params);
  }
}
