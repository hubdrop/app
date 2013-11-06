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

    // From https://github.com/KnpLabs/php-github-api/blob/master/doc/repos.md
    $client = new GithubClient();
    $client->authenticate($this->github_application_token, '', \Github\Client::AUTH_URL_TOKEN);

    $repo = $client->api('repo')->create($project_name, 'Mirror of drupal.org provided by hubdrop.io', 'http://drupal.org/project/' . $project_name, true, $this->github_org);

    $message = "A repo on GitHub has been created.  Commits should appear shortly. " . $repo['html_url'];

    // @TODO: Figure out symfony messages to notify the user.
    return $this->projectAction($project_name, $message);
  }

  /**
   * Project View Page
   */
  public function projectAction($project_name, $message = '')
  {

    $params = array();
    $params['project_ok'] = FALSE;
    $params['project_cloned'] = FALSE;
    $params['message'] = $message;

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
