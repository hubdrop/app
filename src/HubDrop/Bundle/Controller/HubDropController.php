<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Guzzle\Http\Client;
use Github\Client as GithubClient;

class HubDropController extends Controller
{
  private $repo_path = '/var/hubdrop/repos';
  private $github_org = 'drupalprojects';
  private $jenkins_url = 'http://hubdrop:8080';


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
      return $this->mirrorProject($project_name);
    }

    // Get Project object.
    $project = $this->get('hubdrop')->getProject($project_name);

    $params = array();
    $params['project_name'] = $project_name;
    $params['project_exists'] = $project->checkUrl('drupal', 'web');    // On drupal.org
    $params['urls'] = $project->urls;

    if ($params['project_exists']){
      $params['project_cloned'] = $project->checkUrl('local', 'file');    // Cloned Locally
      $params['project_mirrored'] = $project->checkUrl('github', 'web');  // On GitHub
      $params['message'] = '';

      $params['urls'] = $project->urls;
      $params['project_drupal_git'] = $project->getUrl('drupal', 'web');
    }

    // Stopgap
    $params['message'] = 'This branch is in development.  Mirroring is temporarily disabled.';
    $params['allow_mirroring'] = FALSE;

    return $this->render('HubDropBundle:HubDrop:project.html.twig', $params);
  }

  /**
   * Mirror a Project.
   */
  private function mirrorProject($project_name)
  {
//    $stop_process = FALSE;
//
//    // Connect to GitHub and create a Repo for $project_name
//    // From https://github.com/KnpLabs/php-github-api/blob/master/doc/repos.md
//    $client = new GithubClient();
//    $client->authenticate($this->github_application_token, '', \Github\Client::AUTH_URL_TOKEN);
//
//    try {
//      $repo = $client->api('repo')->create($project_name, "Mirror of http://drupal.org/project/$project_name provided by hubdrop.", "http://hubdrop.io", true, $this->github_org);
//      $output = "GitHub Repo created at " . $repo['html_url'];
//    }
//    catch (\Github\Exception\ValidationFailedException $e) {
//      // For now we assume this is just a
//      // "Validation Failed: name already exists on this account
//      if ($e->getCode() == 422){
//        $output = '<p>Repo already exists on github: http://github.com/' . $this->github_org . '/' . $project_name . '</p>';
//      }
//      else {
//        $output = $e->getMessage();
//        $stop_process = TRUE;
//      }
//    }
//
//    if (!$stop_process) {
//      $output = shell_exec('jenkins-cli build hubdrop-jenkins-create-mirror -p NAME=' . $project_name);
//    }
//    //return new Response($output);
//    return $this->redirect('/project/' . $project_name);
  }
}
