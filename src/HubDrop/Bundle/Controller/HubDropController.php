<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Guzzle\Http\Client;
use Github\Client as GithubClient;

use Symfony\Component\HttpFoundation\Session\Session;


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
        'project_name' => strtolower($project_name),
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
    $project_name = strtolower($project_name);

    // @TODO: Break out into its own route. Require a POST? Symfony Form API?
    // Mirror: GO!
    // We only want to try to mirror a project if not yet cloned and it exists.

    // Get Project object
    $project = $this->get('hubdrop')->getProject($project_name);

    // If ?mirror=go
    if ($project->mirrored == FALSE && $this->get('request')->query->get('mirror') == 'go'){
      $project->initMirror();
      return $this->redirect('/project/' . $project_name);
    }

    // Build twig vars
    $vars = array();
    $vars['project'] = $project;
    unset($vars['project']->hubdrop);

    // @TODO: Just use project in twig templates
    $vars['project_name'] = $project_name;
    $vars['project_exists'] = $project->exists;    // On drupal.org
    $vars['urls'] = $project->urls;

    if ($vars['project_exists']){
      $vars['project_cloned'] = $project->cloned;    // Cloned Locally
      $vars['project_mirrored'] = $project->mirrored;  // On GitHub
      $vars['message'] = '';

      $vars['urls'] = $project->urls;
      $vars['project_drupal_git'] = $project->getUrl('drupal');
    }

    // Stopgap
//    $params['message'] = 'This branch is in development.  Mirroring is temporarily disabled.';
    $vars['allow_mirroring'] = TRUE;

    return $this->render('HubDropBundle:HubDrop:project.html.twig', $vars);
  }
}
