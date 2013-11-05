<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client;

class HubDropController extends Controller
{
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
   * Project View Page
   */
  public function projectAction($project_name)
  {
    // Mirror it?
    $request = $this->get('request');
    if ($request->query->get('mirror')){
      $repos_path = "/var/hubdrop/repos";
      exec("hubdrop-create-mirror $project_name $repos_path");
    }

    // $_POST parameters
    $request->request->get('name');

    // Create a client and provide a base URL
    $client = new Client('http://drupal.org');

    // Look for drupal.org/project/{project_name}
    try {
      $response = $client->get('/project/' . $project_name)->send();
      $project_ok = TRUE;
    } catch (\Guzzle\Http\Exception\BadResponseException $e) {
      $project_ok = FALSE;
    }

    // Build template params
    $params = array();
    $params['project_name'] = $project_name;
    $params['project_ok'] = $project_ok;
    $params['project_drupal_url'] = "http://drupal.org/project/$project_name";

    if ($project_ok){
      $params['project_drupal_git'] = "http://git.drupal.org/project/$project_name";
    }
    return $this->render('HubDropBundle:HubDrop:project.html.twig', $params);
  }
}
