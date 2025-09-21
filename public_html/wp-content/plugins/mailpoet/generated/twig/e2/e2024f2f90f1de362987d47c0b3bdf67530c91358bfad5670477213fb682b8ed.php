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

/* upgrade_tiers.html */
class __TwigTemplate_c260ca698e3584b2cbf2795424a7e23a50fca4519ec42363a4acb3eb996211e7 extends Template
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
        $this->parent = $this->loadTemplate("layout.html", "upgrade_tiers.html", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        yield "<style>
  body { background: white; }
  #wpbody-content { padding-top: 64px; }
</style>
<div class=\"mailpoet-top-bar\">
  <a role=\"button\" class=\"mailpoet-top-bar-logo\" title=\"";
        // line 9
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscAttr("Back to section root", "mailpoet");
        yield "\" tabindex=\"0\" href=\"admin.php?page=mailpoet-homepage\">
    <img src=\"";
        // line 10
        yield $this->extensions['MailPoet\Twig\Assets']->generateCdnUrl("logo-orange.svg");
        yield "\" alt=\"MailPoet logo\" />
  </a>
</div>
<div class=\"mailpoet-tiers-page\">
  <br><br>
  <div class=\"mp-toggle pricing-period-toggle mailpoet-tiers-page-billing-toggle\">
    <input id=\"pricing_duration\" type=\"checkbox\">
    <label for=\"pricing_duration\" data-automation-id=\"pricing_duration\">
      <div class=\"mp-toggle__switch\" data-unchecked=\"";
        // line 18
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscAttr("Pay monthly");
        yield "\" data-checked=\"";
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscAttr("Pay yearly");
        yield "\"></div>
    </label>
    <div class=\"mailpoet-tiers-page-pricing-period-description\">";
        // line 20
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Save up to 15% on yearly plans");
        yield "</div>
    <div class=\"mailpoet-tiers-page-pricing-period-description-selected\">";
        // line 21
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("You are saving up to 15%!");
        yield "</div>
  </div>
  <figure class=\"mailpoet-tiers-page-content\">
    <div class=\"mailpoet-tiers-page-content__pricing\">
      <div class=\"mailpoet-tiers-page-content__tier\">
        <div class=\"mailpoet-tiers-page-tier-header\">
          <div class=\"mailpoet-tiers-page-tier-header__title\">
            <div class=\"mailpoet-tiers-page-tier-header__icon starter\"></div>
            <h2 class=\"mailpoet-tiers-page-tier-header-label\">
              ";
        // line 30
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Starter");
        yield "
            </h2>
          </div>
          <p>";
        // line 33
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Email marketing essentials for bloggers, small businesses, or those just starting out.");
        yield "</p>
        </div>
        <div class=\"mailpoet-tiers-page-tier-price\">
          <p>&nbsp;</p>
          <h3>
            <span class=\"pricing-value\">";
        // line 38
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Free");
        yield "</span>
          </h3>
          <p>";
        // line 40
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Up to 500 subscribers");
        yield "</p>
        </div>
        ";
        // line 42
        yield MailPoetVendor\Twig\Extension\CoreExtension::include($this->env, $context, "upgrade/upgrade_button.html", ["label" => $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Get Starter"), "button_class" => "is-secondary", "group" => "starter"]);
        yield "
        <ul class=\"free\">
          <li>";
        // line 44
        yield $this->extensions['MailPoet\Twig\Filters']->wpKses($this->extensions['MailPoet\Twig\I18n']->translate("Send up to <b>5,000</b> emails per month"), ["b" => []]);
        yield "</li>
          <li>";
        // line 45
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Subscription forms");
        yield "</li>
          <li>";
        // line 46
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Email and ecommerce marketing");
        yield "</li>
          <li>";
        // line 47
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Basic marketing automation");
        yield "</li>
          <li>";
        // line 48
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Summarized email engagement statistics");
        yield "</li>
          <li>";
        // line 49
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Send with MailPoet Sending Service");
        yield "</li>
        </ul>
      </div>
      <div class=\"mailpoet-tiers-page-content__tier popular\">
        <div class=\"mailpoet-tiers-page-label\">
          <div class=\"mailpoet-tiers-page-label__content\">
            ";
        // line 55
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Most popular");
        yield "
          </div>
        </div>
        <div class=\"mailpoet-tiers-page-tier-header\">
          <div class=\"mailpoet-tiers-page-tier-header__title\">
            <div class=\"mailpoet-tiers-page-tier-header__icon business\"></div>
            <h2 class=\"mailpoet-tiers-page-tier-header-label\">
              ";
        // line 62
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Business");
        yield "
            </h2>
          </div>
          <p>";
        // line 65
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Advanced email marketing functionality designed to help businesses of all sizes grow.");
        yield "</p>
        </div>
        <div class=\"mailpoet-tiers-page-tier-price\">
          <p>";
        // line 68
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("From");
        yield "</p>
          <h3 id=\"mailpoet-business-yearly-price\">
            <span class=\"pricing-discount\">
              <sup class=\"pricing-discount-currency\">\$</sup>
              <span class=\"pricing-discount-value\">10</span>
              &nbsp;
            </span>
            <sup class=\"pricing-currency\">\$</sup>
            <span class=\"pricing-value\">9</span>
            ";
        // line 77
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML(" / month");
        yield "
          </h3>
          <h3 id=\"mailpoet-business-monthly-price\">
            <sup class=\"pricing-currency\">\$</sup>
            <span class=\"pricing-value\">10</span>
            ";
        // line 82
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML(" / month");
        yield "
          </h3>
          <p>";
        // line 84
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("for 500 subscribers");
        yield "</p>
        </div>
        ";
        // line 86
        yield MailPoetVendor\Twig\Extension\CoreExtension::include($this->env, $context, "upgrade/upgrade_button.html", ["label" => $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Get Business"), "button_class" => "is-primary", "group" => "business"]);
        yield "
        <div>
          <p>";
        // line 88
        yield $this->extensions['MailPoet\Twig\Filters']->wpKses($this->extensions['MailPoet\Twig\I18n']->translate("<b>All Starter plan features</b>, plus:"), ["b" => []]);
        yield "</p>
          <ul class=\"business\">
            <li>";
        // line 90
        yield $this->extensions['MailPoet\Twig\Filters']->wpKses($this->extensions['MailPoet\Twig\I18n']->translate("Send <b>unlimited</b> emails per month"), ["b" => []]);
        yield "</li>
            <li>";
        // line 91
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Remove MailPoet branding");
        yield "</li>
            <li>";
        // line 92
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Priority support");
        yield "</li>
            <li>";
        // line 93
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Subscriber segmentation");
        yield "</li>
            <li>";
        // line 94
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Targeted marketing automation");
        yield "</li>
            <li>";
        // line 95
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Detailed email engagement and ecommerce statistics");
        yield "</li>
          </ul>
        </div>
      </div>
      <div class=\"mailpoet-tiers-page-content__tier\">
        <div class=\"mailpoet-tiers-page-tier-header\">
          <div class=\"mailpoet-tiers-page-tier-header__title\">
            <div class=\"mailpoet-tiers-page-tier-header__icon agency\"></div>
            <h2 class=\"mailpoet-tiers-page-tier-header-label\">
              ";
        // line 104
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Agency");
        yield "
            </h2>
          </div>
          <p>";
        // line 107
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Add MailPoet’s full functionality to multiple websites – perfect for WordPress agencies or freelancers.");
        yield "</p>
        </div>
        <div class=\"mailpoet-tiers-page-tier-price\">
          <p>";
        // line 110
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("From");
        yield "</p>
          <h3 id=\"mailpoet-agency-yearly-price\">
            <span class=\"pricing-discount\">
              <sup class=\"pricing-discount-currency\">\$</sup>
              <span class=\"pricing-discount-value\">30</span>
              &nbsp;
            </span>
            <sup class=\"pricing-currency\">\$</sup>
            <span class=\"pricing-value\">26</span>
            ";
        // line 119
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML(" / month");
        yield "
          </h3>
          <h3 id=\"mailpoet-agency-monthly-price\">
            <sup class=\"pricing-currency\">\$</sup>
            <span class=\"pricing-value\">30</span>
            ";
        // line 124
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML(" / month");
        yield "
          </h3>
          <p>";
        // line 126
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("for 500 subscribers");
        yield "</p>
        </div>
        ";
        // line 128
        yield MailPoetVendor\Twig\Extension\CoreExtension::include($this->env, $context, "upgrade/upgrade_button.html", ["label" => $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Get Agency"), "button_class" => "is-secondary", "group" => "agency"]);
        yield "
        <div>
          <p>";
        // line 130
        yield $this->extensions['MailPoet\Twig\Filters']->wpKses($this->extensions['MailPoet\Twig\I18n']->translate("<b>All Business features</b>, plus:"), ["b" => []]);
        yield "</p>
          <ul>
            <li>";
        // line 132
        yield $this->extensions['MailPoet\Twig\Filters']->wpKses($this->extensions['MailPoet\Twig\I18n']->translate("Use MailPoet on <b>50 websites</b>"), ["b" => []]);
        yield "</li>
            <li>";
        // line 133
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Number of subscribers is per MailPoet instance");
        yield "</li>
          </ul>
        </div>
      </div>
    </div>
    <figcaption>
      <p>
        ";
        // line 140
        $context["allowedHtml"] = ["a" => ["href" => [], "target" => []]];
        // line 141
        yield "        ";
        yield $this->extensions['MailPoet\Twig\Filters']->wpKses(MailPoet\Util\Helpers::replaceLinkTags($this->extensions['MailPoet\Twig\I18n']->translate("Compare full plan features and view more pricing options on [link]MailPoet.com[/link]"), "https://www.mailpoet.com/", ["target" => "_blank"]),         // line 144
($context["allowedHtml"] ?? null));
        // line 145
        yield "
      </p>
    </figcaption>
  </figure>
  <div class=\"mailpoet-tiers-page-hero-section footer\">
    <div class=\"mailpoet-tiers-page-hero-section-questions\">
      <div class=\"mailpoet-tiers-page-hero-section-questions-content\">
        <h1 class=\"mailpoet-tiers-page-hero-section__title\">
          ";
        // line 153
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Have questions?");
        yield "
        </h1>
        <p>
          ";
        // line 156
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Visit our website to learn more about our plans.");
        yield "
          ";
        // line 157
        $context["allowedHtml"] = ["a" => ["href" => [], "target" => []]];
        // line 158
        yield "          ";
        yield $this->extensions['MailPoet\Twig\Filters']->wpKses(MailPoet\Util\Helpers::replaceLinkTags($this->extensions['MailPoet\Twig\I18n']->translate("And if you’re not sure which plan is the right one for you, our [link]Customer Support team[/link] are on hand to help you decide."), "https://www.mailpoet.com/support/sales-pre-sales-questions/?ref=plugin-upgrade-page", ["target" => "_blank"]),         // line 161
($context["allowedHtml"] ?? null));
        // line 162
        yield "
        </p>
      </div>
      <div class=\"mailpoet-tiers-page-hero-section-questions__actions\">
        <a target=\"_blank\" href=\"https://account.mailpoet.com/\" class=\"components-button is-secondary\">
          ";
        // line 167
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Compare plans");
        yield "
        </a>
        <a target=\"_blank\" href=\"https://kb.mailpoet.com/article/223-licenses-and-sales\" class=\"components-button is-link\">
          ";
        // line 170
        yield $this->extensions['MailPoet\Twig\I18n']->translateEscHTML("Read FAQ");
        yield "
        </a>
      </div>
    </div>
    <img src=\"";
        // line 174
        yield $this->extensions['MailPoet\Twig\Assets']->generateCdnUrl("premium/6-hero.png");
        yield "\" alt=\"\" />
  </div>
</div>
";
        return; yield '';
    }

    // line 179
    public function block_after_javascript($context, array $blocks = [])
    {
        $macros = $this->macros;
        yield "<script type=\"text/javascript\">
  MailPoet.trackEvent('Premium page viewed');

  jQuery(function(\$) {
    \$('#pricing_duration').on('change', function() {
      const billingValue = \$(this).prop('checked') ? 'annual' : 'monthly';

      \$('.mailpoet-tiers-page a.mailpoet-premium-shop-link').each(function() {
        let href = \$(this).attr('href');
        if (href) {
          let newHref = href.replace(/([?&]billing=)[^&]*/, '\$1' + billingValue);
          \$(this).attr('href', newHref);
        }
      });
    });

    \$('.mailpoet-tiers-page a.mailpoet-premium-shop-link').on('click', function() {
      MailPoet.trackEvent('Upgrade page CTA clicked');
    });
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
        return "upgrade_tiers.html";
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
        return array (  376 => 179,  367 => 174,  360 => 170,  354 => 167,  347 => 162,  345 => 161,  343 => 158,  341 => 157,  337 => 156,  331 => 153,  321 => 145,  319 => 144,  317 => 141,  315 => 140,  305 => 133,  301 => 132,  296 => 130,  291 => 128,  286 => 126,  281 => 124,  273 => 119,  261 => 110,  255 => 107,  249 => 104,  237 => 95,  233 => 94,  229 => 93,  225 => 92,  221 => 91,  217 => 90,  212 => 88,  207 => 86,  202 => 84,  197 => 82,  189 => 77,  177 => 68,  171 => 65,  165 => 62,  155 => 55,  146 => 49,  142 => 48,  138 => 47,  134 => 46,  130 => 45,  126 => 44,  121 => 42,  116 => 40,  111 => 38,  103 => 33,  97 => 30,  85 => 21,  81 => 20,  74 => 18,  63 => 10,  59 => 9,  52 => 4,  48 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "upgrade_tiers.html", "/home/circleci/mailpoet/mailpoet/views/upgrade_tiers.html");
    }
}
