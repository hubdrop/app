<?php

/* HubDropBundle::layout.html.twig */
class __TwigTemplate_7455da08bbca1b9cc3f0bed6926ade153733da60a53b19031ab4bcc58914cd66 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = $this->env->loadTemplate("TwigBundle::layout.html.twig");

        $this->blocks = array(
            'head' => array($this, 'block_head'),
            'title' => array($this, 'block_title'),
            'body' => array($this, 'block_body'),
            'content_header' => array($this, 'block_content_header'),
            'content_header_more' => array($this, 'block_content_header_more'),
            'content' => array($this, 'block_content'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "TwigBundle::layout.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_head($context, array $blocks = array())
    {
        // line 4
        echo "    <link rel=\"icon\" sizes=\"16x16\" href=\"";
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("favicon.ico"), "html", null, true);
        echo "\" />
";
    }

    // line 7
    public function block_title($context, array $blocks = array())
    {
        echo "HubDrop.io";
    }

    // line 9
    public function block_body($context, array $blocks = array())
    {
        // line 10
        echo "    ";
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute($this->getAttribute($this->getAttribute((isset($context["app"]) ? $context["app"] : $this->getContext($context, "app")), "session"), "flashbag"), "get", array(0 => "notice"), "method"));
        foreach ($context['_seq'] as $context["_key"] => $context["flashMessage"]) {
            // line 11
            echo "        <div class=\"flash-message\">
            <em>Notice</em>: ";
            // line 12
            echo twig_escape_filter($this->env, (isset($context["flashMessage"]) ? $context["flashMessage"] : $this->getContext($context, "flashMessage")), "html", null, true);
            echo "
        </div>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['flashMessage'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 15
        echo "
    ";
        // line 16
        $this->displayBlock('content_header', $context, $blocks);
        // line 25
        echo "
    <div class=\"block\">
        ";
        // line 27
        $this->displayBlock('content', $context, $blocks);
        // line 28
        echo "    </div>

    ";
        // line 30
        if (array_key_exists("code", $context)) {
            // line 31
            echo "        <h2>Code behind this page</h2>
        <div class=\"block\">
            <div class=\"symfony-content\">";
            // line 33
            echo (isset($context["code"]) ? $context["code"] : $this->getContext($context, "code"));
            echo "</div>
        </div>
    ";
        }
    }

    // line 16
    public function block_content_header($context, array $blocks = array())
    {
        // line 17
        echo "        <ul id=\"menu\">
            ";
        // line 18
        $this->displayBlock('content_header_more', $context, $blocks);
        // line 21
        echo "        </ul>

        <div style=\"clear: both\"></div>
    ";
    }

    // line 18
    public function block_content_header_more($context, array $blocks = array())
    {
        // line 19
        echo "                <li><a href=\"";
        echo $this->env->getExtension('routing')->getPath("_demo");
        echo "\">Demo Home</a></li>
            ";
    }

    // line 27
    public function block_content($context, array $blocks = array())
    {
    }

    public function getTemplateName()
    {
        return "HubDropBundle::layout.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  123 => 27,  116 => 19,  113 => 18,  106 => 21,  104 => 18,  101 => 17,  98 => 16,  90 => 33,  86 => 31,  84 => 30,  80 => 28,  78 => 27,  74 => 25,  72 => 16,  69 => 15,  60 => 12,  57 => 11,  52 => 10,  49 => 9,  43 => 7,  36 => 4,  33 => 3,);
    }
}
