<?php

namespace HubDrop\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
  /**
   * Route: About
   */
  public function aboutAction()
  {
    //
    return $this->render('HubDropBundle:HubDrop:page.html.twig', array(
      'content' => 'welcome'));
  }
}
