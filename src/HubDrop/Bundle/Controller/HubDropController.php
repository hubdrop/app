<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Guzzle\Http\Client;
use Github\Client as GithubClient;

class HubDropController extends Controller
{

  /**
   * Route: Homepage
   */
  public function homeAction()
  {
    $project_name = $this->get('request')->query->get('project_name');
    if ($project_name){
      return $this->redirect($this->generateUrl('_project', array(
        'project_name' => $project_name
      )));
    }

    return $this->render('HubDropBundle:HubDrop:home.html.twig', array(
      'site_base_url' => $this->getRequest()->getHost(),
    ));
  }

  /**
   * Route: Project View Page
   */
  public function projectAction($project_name)
  {

    // @TODO: Break out into its own route. Require a POST? Symfony Form API?
    // Mirror: GO!
    // We only want to try to mirror a project if not yet cloned and it exists.
    if ($this->get('request')->query->get('mirror') == 'go'){
      $this->get('hubdrop')->initMirror($project_name);
      return $this->redirect('/project/' . $project_name);
    }

    // Get Project object (with checks)
    $project = $this->get('hubdrop')->getProject($project_name, TRUE);

    $params = array();
    $params['project_name'] = $project_name;
    $params['project_exists'] = $project->drupal_project_exists;    // On drupal.org
    $params['urls'] = $project->urls;

    if ($params['project_exists']){
      $params['project_cloned'] = $project->checkUrl('local', 'file');    // Cloned Locally
      $params['project_mirrored'] = $project->github_project_exists;  // On GitHub
      $params['message'] = '';

      $params['urls'] = $project->urls;
      $params['project_drupal_git'] = $project->getUrl('drupal', 'web');
    }

    // Stopgap
//    $params['message'] = 'This branch is in development.  Mirroring is temporarily disabled.';
    $params['allow_mirroring'] = TRUE;

    return $this->render('HubDropBundle:HubDrop:project.html.twig', $params);
  }
}
