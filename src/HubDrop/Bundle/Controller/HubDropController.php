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
  private $github_org = 'drupalrojects';
  private $jenkins_url = 'http://hubdrop:8080';

  /**
   * This was retrieved using a github username and password with curl:
   * curl -i -u <github_username> -d '{"scopes": ["repo"]}' https://api.github.com/authorizations
   */
  private $github_application_token = '2f3a787bc2881ac86d0277b37c1b9a67c4c509bb';

  /**
   * Route: Homepage
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
   * Route: Project View Page
   */
  public function projectAction($project_name)
  {

    // Default twig variables
    $params = array();
    $params['project_cloned'] = FALSE;    // Cloned Locally
    $params['project_mirrored'] = FALSE;  // On GitHub
    $params['project_exists'] = FALSE;    // On drupal.org
    $params['message'] = '';

    $params['github_web_url'] = 'http://github.com/' . $this->github_org . '/' . $project_name;
    $params['local_clone_path'] = $this->repo_path . '/' . $project_name . '.git';

    // @TODO: Figure out how to cache this, or rig it up so it is faster.

    // Check Steps:
    //  1. Check for local path.
    //  2. Check for Public github repo, if local path is missing. (This is
    //       really just for easy local development.  Allows the web app to work
    //       without having all the repos cloned.
    //  3. Check for drupal.org project.

    // If we have a local clone...
    if (file_exists($params['local_clone_path'])){
      $params['project_cloned'] = TRUE;
      $params['project_mirrored'] = TRUE;
      $params['project_exists'] = TRUE;
      // We will assume it exists on github if we have a local clone, to save
      // the extra request to github.
    }
    // If we don't have a local clone, ping github to see if it is already mirrored.
    else {
      $params['project_cloned'] = FALSE;

      // Lookup GitHub project.
      // @TODO: Should we only do this in dev environments?
      $client = new Client("http://github.com");
      try {
        $response = $client->get('/' . $this->github_org . '/' . $project_name)->send();
        $params['project_mirrored'] = TRUE;
        $params['project_exists'] = TRUE;

      } catch (\Guzzle\Http\Exception\BadResponseException $e) {
        $params['project_mirrored'] = FALSE;
      }
    }

    // If project is not yet mirrored, confirm it is really a drupal project.
    if ($params['project_mirrored'] == FALSE) {
      // Look for drupal.org/project/{project_name}
      $client = new Client('http://drupal.org');
      try {
        $response = $client->get('/project/' . $project_name)->send();
        $params['project_exists'] = TRUE;

        // @TODO: Break out into its own route. Require a POST? Symfony Form API?
        // Mirror: GO!
        // We only want to try to mirror a project if not yet cloned and it exists.
        if ($this->get('request')->query->get('mirror') == 'go'){
          return $this->mirrorProject($project_name);
        }

      } catch (\Guzzle\Http\Exception\BadResponseException $e) {
        $params['project_exists'] = FALSE;
      }
    }

    // Build template params
    $params['project_name'] = $project_name;
    $params['project_drupal_url'] = "http://drupal.org/project/$project_name";

    if ($params['project_exists']){
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
      $repo = $client->api('repo')->create($project_name, "Mirror of http://drupal.org/project/$project_name provided by hubdrop.", "http://hubdrop.io", true, $this->github_org);
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
    print $output;
    die;
    //return new Response($output);
    //return $this->redirect('/project/' . $project_name);
  }
}
