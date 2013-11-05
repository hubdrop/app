<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HubDropController extends Controller
{
  /**
   * Homepage
   */
  public function homeAction()
  {
    // @TODO: Goto Project form
    return $this->render('HubDropBundle:HubDrop:home.html.twig');
  }

  /**
   * Project View Page
   */
  public function projectAction($project_name)
  {
    /*
     * The action's view can be rendered using render() method
     * or @Template annotation as demonstrated in DemoController.
     *
     */
    $params['project_name'] = $project_name;
    $params['project_drupal_url'] = "http://drupal.org/project/$project_name";
    $params['project_drupal_git'] = "http://git.drupal.org/project/$project_name";
    return $this->render('HubDropBundle:HubDrop:project.html.twig', $params);
  }
}
