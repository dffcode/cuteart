<?php

if (!defined('ABSPATH')) exit;


use MailPoetVendor\Twig\Environment;
use MailPoetVendor\Twig\Error\LoaderError;
use MailPoetVendor\Twig\Error\RuntimeError;
use MailPoetVendor\Twig\Extension\CoreExtension;
use MailPoetVendor\Twig\Extension\SandboxExtension;
use MailPoetVendor\Twig\Markup;
use MailPoetVendor\Twig\Sandbox\SecurityError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedTagError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFilterError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFunctionError;
use MailPoetVendor\Twig\Source;
use MailPoetVendor\Twig\Template;

/* upgrade/upgrade_button.html */
class __TwigTemplate_b67c32b7be3df4a420334077f7e14c432f62c4aab97a732513f3dcffc5dfc251 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        if (($context["has_valid_api_key"] ?? null)) {
            // line 2
            yield "  <a
    target=\"_blank\"
    href=\"";
            // line 4
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape((("https://account.mailpoet.com/orders/upgrade/" . ($context["plugin_partial_key"] ?? null)) . "?utm_source=plugin&utm_medium=premium&utm_campaign=upgrade&ref=plugin-upgrade-page"), "html", null, true);
            yield "\"
    class=\"components-button ";
            // line 5
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["button_class"] ?? null), "html", null, true);
            yield " mailpoet-premium-shop-link\"
  >
    ";
            // line 7
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["label"] ?? null), "html", null, true);
            yield "
  </a>
";
        } else {
            // line 10
            yield "  <a
    target=\"_blank\"
    href=\"";
            // line 12
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape((((((("https://account.mailpoet.com/?s=" . ($context["subscriber_count"] ?? null)) . "&email=") . ($context["current_wp_user_email"] ?? null)) . "&g=") . ($context["group"] ?? null)) . "&billing=monthly&utm_source=plugin&utm_medium=premium&utm_campaign=purchase&ref=plugin-upgrade-page"), "html", null, true);
            yield "\"
    class=\"components-button ";
            // line 13
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["button_class"] ?? null), "html", null, true);
            yield " mailpoet-premium-shop-link\"
  >
  ";
            // line 15
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["label"] ?? null), "html", null, true);
            yield "
  </a>
";
        }
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "upgrade/upgrade_button.html";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  72 => 15,  67 => 13,  63 => 12,  59 => 10,  53 => 7,  48 => 5,  44 => 4,  40 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "upgrade/upgrade_button.html", "/home/circleci/mailpoet/mailpoet/views/upgrade/upgrade_button.html");
    }
}
