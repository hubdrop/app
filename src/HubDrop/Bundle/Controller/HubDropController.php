<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

  /**
   * Route: Project View Page
   */
  public function projectMigrateAction($project_name)
  {
    // Get Project object
    $project_name = strtolower($project_name);
    $vars['project'] = $project = $this->get('hubdrop')->getProject($project_name);

    if ($this->get('request')->query->get('action') == 'migrate') {
      $project->setSource('github');

      try {
        $project->updateMaintainers();
      }
      catch (\Exception $e) {
        $project->hubdrop->session->getFlashBag()->add('notice', $e->getMessage());
        return $this->redirect('/project/' . $project_name . '/migrate');
      }
      catch (\Github\Exception\RuntimeException $e) {
        $project->hubdrop->session->getFlashBag()->add('notice', 'Unable to create teams on GitHub.  Make sure github authorization is configured.');
        return $this->redirect('/project/' . $project_name . '/migrate');
      }

      $project->hubdrop->session->getFlashBag()->add('notice', 'Project committers team created! You should now be able to commit and push to http://github.com/drupalproject/' . $project->name);
      return $this->redirect('/project/' . $project_name);
    }

    // If project does not exist or project is not mirrored...
    if (!$project->exists && !$project->mirrored){
      $project->hubdrop->session->getFlashBag()->add('notice', "You can't migrate a project that isn't mirrored. Try mirroring it first.");
      return $this->redirect('/project/' . $project->name);
    }

    //
    return $this->render('HubDropBundle:HubDrop:projectMigrate.html.twig', $vars);

    // When the user hits the button, HubDrop must check to see if it can access the
    // maintainers page of the module, and check if it is listed as a committer.
    // If not, explain the process and let the user try again.

    // Then, it should check to see if the other committers have added their github
    // profile link to there drupal profile.  If not, just redirect back to project page
    // and let the user know to do so.

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
    $request_object = json_decode($request->getContent());

    $response = new Response();
    $response->headers->set('Content-Type', 'text/html');

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
