<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Guzzle\Http\Client;
use Github\Client as GithubClient;

class HubDropController extends Controller
{
  private $repo_path = '/var/hubdrop/repos';
  private $github_org = 'hubdrop-projects';
  private $jenkins_url = 'http://hubdrop:8080';

  /**
   * This was retrieved using a github username and password with curl:
   * curl -i -u <github_username> -d '{"scopes": ["repo"]}' https://api.github.com/authorizations
   */
  private $github_application_token = 'af25172c6b5dd7e2ae29d1eb98636314588f0c28';

  /**
   * Homepage
   */
  public function homeAction()
  {
    //
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
   * Project View Page
   */
  public function projectAction($project_name)
  {

    $params = array();
    $params['project_ok'] = FALSE;
    $params['project_cloned'] = FALSE;
    $params['message'] = '';

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

  /**
   * Mirror a Project.
   */
  private function mirrorProject($project_name)
  {
    $stop_process = FALSE;

    // Connect to GitHub and create a Repo for $project_name
    // From https://github.com/KnpLabs/php-github-api/blob/master/doc/repos.md
    $client = new GithubClient();
    $client->authenticate($this->github_application_token, '', \Github\Client::AUTH_URL_TOKEN);

    try {
      $repo = $client->api('repo')->create($project_name, 'Mirror of drupal.org provided by hubdrop.io', 'http://drupal.org/project/' . $project_name, true, $this->github_org);
      $output = "GitHub Repo created at " . $repo['html_url'];
    }
    catch (\Github\Exception\ValidationFailedException $e) {
      // For now we assume this is just a
      // "Validation Failed: name already exists on this account
      if ($e->getCode() == 422){
        $output = '<p>Repo already exists on github: http://github.com/' . $this->github_org . '/' . $project_name . '</p>';
      }
      else {
        $output = $e->getMessage();
        $stop_process = TRUE;
      }
    }

    if (!$stop_process) {
      $output = shell_exec('jenkins-cli build hubdrop-jenkins-create-mirror -p NAME=' . $project_name);
    }

    //return new Response($output);
    return $this->redirect('/project/' . $project_name);
  }
}
