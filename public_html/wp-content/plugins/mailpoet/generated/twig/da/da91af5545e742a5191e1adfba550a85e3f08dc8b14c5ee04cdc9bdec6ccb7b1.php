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

/* forms.html */
class __TwigTemplate_8a6f0398fffc82506ba46e70538b728a453c02bea83ac685560c22b44c40173a extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
            'after_javascript' => [$this, 'block_after_javascript'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "layout.html";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("layout.html", "forms.html", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        yield "  <div id=\"forms_container\"></div>

  <div>
    <p class=\"mailpoet_sending_methods_help help\">
      ";
        // line 8
        $context["allowedHtml"] = ["a" => ["href" => [], "target" => [], "id" => []], "strong" => []];
        // line 9
        yield "      ";
        yield $this->extensions['MailPoet\Twig\Filters']->wpKses(MailPoet\Util\Helpers::replaceLinkTags($this->extensions['MailPoet\Twig\I18n']->translate("<strong>Tip:</strong> check out [link]this list[/link] of form plugins that integrate with MailPoet."), "https://kb.mailpoet.com/article/198-forms-themes-plugins-that-work-with-mailpoet", ["target" => "_blank", "id" => "mailpoet_helper_link"]),         // line 11
($context["allowedHtml"] ?? null));
        // line 12
        yield "
    </p>
  </div>

  <script type=\"text/javascript\">
    var mailpoet_listing_per_page = ";
        // line 17
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["items_per_page"] ?? null), "html", null, true);
        yield ";
    var mailpoet_segments = ";
        // line 18
        yield json_encode(($context["segments"] ?? null));
        yield ";
    var mailpoet_form_template_selection_url =
      \"";
        // line 20
        yield admin_url("admin.php?page=mailpoet-form-editor-template-selection");
        yield "\";
    var mailpoet_form_edit_url =
      \"";
        // line 22
        yield admin_url("admin.php?page=mailpoet-form-editor&id=");
        yield "\";

    var mailpoet_display_nps_poll = ";
        // line 24
        yield json_encode(($context["display_nps_survey"] ?? null));
        yield ";

    ";
        // line 26
        if (($context["display_nps_survey"] ?? null)) {
            // line 27
            yield "      var mailpoet_display_nps_form = true;
      var mailpoet_current_wp_user = ";
            // line 28
            yield json_encode(($context["current_wp_user"] ?? null));
            yield ";
      var mailpoet_current_wp_user_firstname = '";
            // line 29
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["current_wp_user_firstname"] ?? null), "html", null, true);
            yield "';
      var mailpoet_review_request_illustration_url = '";
            // line 30
            yield $this->extensions['MailPoet\Twig\Assets']->generateCdnUrl("review-request/review-request-illustration.20190815-1427.svg");
            yield "';
    ";
        }
        // line 32
        yield "  </script>
";
        return; yield '';
    }

    // line 35
    public function block_after_javascript($context, array $blocks = [])
    {
        $macros = $this->macros;
        yield "<script type=\"text/javascript\">
  jQuery('#mailpoet_helper_link').on('click', function() {
    MailPoet.trackEvent('Forms page > link to doc page');
  });
</script>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "forms.html";
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
        return array (  119 => 35,  113 => 32,  108 => 30,  104 => 29,  100 => 28,  97 => 27,  95 => 26,  90 => 24,  85 => 22,  80 => 20,  75 => 18,  71 => 17,  64 => 12,  62 => 11,  60 => 9,  58 => 8,  52 => 4,  48 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "forms.html", "/home/circleci/mailpoet/mailpoet/views/forms.html");
    }
}
