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
    $content = <<<HTML
    <div class='row'>
<div class='col-sm-6'>
<p>
  <a href='http://hubdrop.io'>HubDrop.io</a> provides mirroring of <a href='http://hubdrop.io'>drupal.org</a> projects on <a href='http://github.com'>GitHub</a> as a free public service.
</p>
<p>
  Drupal.org's git hosting or issue tracking will not be able to move to GitHub anytime soon, so hubdrop will act as a bridge for developers who wish to leverage GitHub for their projects, yet stay connected with the drupal.org community.
</p>
</div>
<div class='col-sm-6'>
<p>
  HubDrop itself is <a href='http://github.com/hubdrop'>fully open source</a> and hosted on GitHub, so anyone can fork and improve the code. Hubdrop code includes <a href='http://opsworks.com'>Chef recipes</a> and a <a href='http://vagrantup.com'>Vagrantfile</a>, making deployment and development easy.
</p>
<p>
  Plans for the future include allowing projects to move to GitHub as the primary repo, with mirroring or releases being sent back to drupal.org.
</p>
</div>
</div>
<div class='row'>
<div class='col-sm-12 text-center text-muted'>
<p>
  HubDrop.io is brought to you by <a href='http://thinkdrop.net'>THINKDROP</a>.
</p>
</div>
HTML;
    return $this->render('HubDropBundle:HubDrop:page.html.twig', array(
      'content' => $content));
  }
}
