<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
      'project_count' => shell_exec('ls /var/hubdrop/repos | wc -l'),
    ));
  }

  /**
   * Route: Project View Page
   */
  public function projectAction($project_name, $webhook = '')
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

    if ($webhook){
      print $webhook; die;
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

  /**
   * Route: Project Webhook Callback
   */
  public function webhookAction()
  {
    $output = "Hello World";

    // Ensure that it is a POST

    // Ensure that it is github calling.
    $request = $this->get('request');
    $request_object = $request->getContent();

    print "REQUEST_CONTENT: ";
    print_r($request_object);

    $response = new Response();
    $response->headers->set('Content-Type', 'text/html');

    // Fake it till you make it.
    $request->headers->set('x-github-event', 'ping');

    // Check for the github event.
    $github_event = $request->headers->get('x-github-event');
    if (empty($github_event)){
      // If request not from github, set Forbidden
      $output = 'You are not Github. Blocked!';
      $response->setStatusCode(403);
    }

    // Ensure it is for one of our projects.
    if ($github_event == 'push' && $request_object->repository->organization == 'drupalprojects'){
      // Get Project object
      $project = $this->get('hubdrop')->getProject($request_object->repository->name);

      // If project source is github, update mirror.
      if ($project->source == 'github'){
        $output = 'We should update! Source is github.';
        $project->initUpdate();
      }
      else {
        $output = 'Source is drupal... we can\'t update...';
      }
    }

    // Depending on the action, do something
    // If project source is drupal, and action is a Pull Request, post an issue with a patch.

    $response->setContent($output);
    return $response;
  }
}
