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

/* subscribers/importExport/export.html */
class __TwigTemplate_d397b2a974f636a1e1c9b326eb2ccfd5695d20d9e10469f6f7ff8efdcee0d044 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
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
        $this->parent = $this->loadTemplate("layout.html", "subscribers/importExport/export.html", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        yield "<div id=\"mailpoet_subscribers_export\" class=\"wrap\">
  <div class=\"mailpoet-top-bar\">
    <a href=\"?page=mailpoet-subscribers#/\" role=\"button\" class=\"mailpoet-top-bar-logo\" title=\"Back to section root\" tabindex=\"0\">
      <img src=\"";
        // line 7
        yield $this->extensions['MailPoet\Twig\Assets']->generateCdnUrl("logo-orange.svg");
        yield "\" alt=\"MailPoet logo\" />
    </a>
    <style>#wpbody-content { padding-top: 64px; } .wrap { margin-top: 20px; }</style>
  </div>

  <div class=\"mailpoet-page-header\">
    <div class=\"mailpoet-back-button\">
      <a href=\"?page=mailpoet-subscribers#/\" aria-label=\"Navigate to the lists page\" class=\"components-button is-small has-icon\">
        <svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"24\" height=\"24\" aria-hidden=\"true\" focusable=\"false\"><path d=\"M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z\"></path></svg>
      </a>
    </div>
    <h1 class=\"wp-heading-inline\">";
        // line 18
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Export subscribers");
        yield "</h1>
  </div>
  ";
        // line 20
        if (MailPoetVendor\Twig\Extension\CoreExtension::testEmpty(($context["segments"] ?? null))) {
            // line 21
            yield "  <div class=\"error\">
    <p>";
            // line 22
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Yikes! Couldn't find any subscribers");
            yield "</p>
  </div>
  ";
        }
        // line 25
        yield "  <div id=\"mailpoet-export\" class=\"mailpoet-tab-content\">
    <!-- Template data -->
  </div>
</div>
<script id=\"mailpoet_subscribers_export_template\" type=\"text/x-handlebars-template\">
  <div id=\"export_result_notice\" class=\"updated mailpoet_hidden\">
    <!-- Result message -->
  </div>
  <div class=\"mailpoet-settings-grid\">
    ";
        // line 34
        if ( !MailPoetVendor\Twig\Extension\CoreExtension::testEmpty(($context["segments"] ?? null))) {
            // line 35
            yield "      <div class=\"mailpoet-settings-label\">
        <label for=\"export_lists\">
          ";
            // line 37
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Pick one or multiple lists");
            yield "
        </label>
      </div>
      <div class=\"mailpoet-settings-inputs\">
        <div class=\"mailpoet-form-select mailpoet-form-input\">
          <select id=\"export_lists\" data-placeholder=\"";
            // line 42
            yield $this->extensions['MailPoet\Twig\I18n']->translateWithContext("Select", "Verb");
            yield "\" multiple=\"multiple\"></select>
        </div>
      </div>
    ";
        }
        // line 46
        yield "
    <div class=\"mailpoet-settings-label\">
      <label for=\"export_columns\">
        ";
        // line 49
        yield $this->extensions['MailPoet\Twig\I18n']->translate("List of fields to export");
        yield "
        <p class=\"description\">
          <a href=\"https://kb.mailpoet.com/article/245-what-is-the-subscriber-global-status\" target=\"_blank\">
            ";
        // line 52
        yield $this->extensions['MailPoet\Twig\I18n']->translateWithContext("Read about the Global status.", "Link to a documentation page in the knowledge base about what is the subscriber global status");
        yield "
          </a>
        </p>
      </label>
    </div>
    <div class=\"mailpoet-settings-inputs\">
      <div class=\"mailpoet-form-select mailpoet-form-input\">
        <select id=\"export_columns\" data-placeholder=\"";
        // line 59
        yield $this->extensions['MailPoet\Twig\I18n']->translateWithContext("Select", "Verb");
        yield "\" multiple=\"multiple\"></select>
      </div>
    </div>

    <div class=\"mailpoet-settings-label\">
      ";
        // line 64
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Format");
        yield "
    </div>
    <div class=\"mailpoet-settings-inputs\">
      <div class=\"mailpoet-settings-inputs-row\">
        <label class=\"mailpoet-form-radio\">
          <input type=\"radio\" name=\"option_format\" id=\"export-format-csv\" value=\"csv\" checked>
          <span class=\"mailpoet-form-radio-control\"></span>
        </label>
        <label for=\"export-format-csv\">";
        // line 72
        yield $this->extensions['MailPoet\Twig\I18n']->translate("CSV file");
        yield "</label>
      </div>
      <div class=\"mailpoet-settings-inputs-row";
        // line 74
        if ( !($context["zipExtensionLoaded"] ?? null)) {
            yield " mailpoet-disabled";
        }
        yield "\">
        <label class=\"mailpoet-form-radio\">
          <input type=\"radio\" name=\"option_format\" id=\"export-format-xlsx\" value=\"xlsx\"";
        // line 76
        if ( !($context["zipExtensionLoaded"] ?? null)) {
            yield " disabled";
        }
        yield ">
          <span class=\"mailpoet-form-radio-control\"></span>
        </label>
        <label for=\"export-format-xlsx\">";
        // line 79
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Excel file");
        yield "</label>
      </div>
      ";
        // line 81
        if ( !($context["zipExtensionLoaded"] ?? null)) {
            // line 82
            yield "        <div class=\"inline notice notice-warning\">
          <p>";
            // line 83
            yield $this->extensions['MailPoet\Twig\I18n']->translate(MailPoet\Util\Helpers::replaceLinkTags("ZIP extension is required to create Excel files. Please refer to the [link]official PHP ZIP installation guide[/link] or contact your hosting provider’s technical support for instructions on how to install and load the ZIP extension.", "http://php.net/manual/en/zip.installation.php"));
            yield "</p>
        </div>
      ";
        }
        // line 86
        yield "    </div>

    <div class=\"mailpoet-settings-save\">
        <a href=\"javascript:;\" class=\"mailpoet-button mailpoet-disabled button-primary\" id=\"mailpoet-export-button\">
          ";
        // line 90
        yield $this->extensions['MailPoet\Twig\I18n']->translate("Export");
        yield "
        </a>
    </div>
  </div>
</script>

<script type=\"text/javascript\">
  var
    segments = JSON.parse(\"";
        // line 98
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["segments"] ?? null), "js"), "html", null, true);
        yield "\"),
    subscriberFieldsSelect2 = JSON.parse(\"";
        // line 99
        yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["subscriberFieldsSelect2"] ?? null), "js"), "html", null, true);
        yield "\"),
    exportData = {
     segments: segments.length || null
    };
</script>

";
        // line 105
        yield $this->extensions['MailPoet\Twig\I18n']->localize(["serverError" => $this->extensions['MailPoet\Twig\I18n']->translate("Server error:"), "exportMessage" => $this->extensions['MailPoet\Twig\I18n']->translate("%1\$s subscribers were exported. Get the exported file [link]here[/link].")]);
        // line 108
        yield "
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "subscribers/importExport/export.html";
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
        return array (  226 => 108,  224 => 105,  215 => 99,  211 => 98,  200 => 90,  194 => 86,  188 => 83,  185 => 82,  183 => 81,  178 => 79,  170 => 76,  163 => 74,  158 => 72,  147 => 64,  139 => 59,  129 => 52,  123 => 49,  118 => 46,  111 => 42,  103 => 37,  99 => 35,  97 => 34,  86 => 25,  80 => 22,  77 => 21,  75 => 20,  70 => 18,  56 => 7,  51 => 4,  47 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "subscribers/importExport/export.html", "/home/circleci/mailpoet/mailpoet/views/subscribers/importExport/export.html");
    }
}
