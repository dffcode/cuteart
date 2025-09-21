<?php
namespace AIOSEO\BrokenLinkChecker\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all general admin code.
 *
 * @since 1.0.0
 */
class Admin {
	/**
	 * The main page slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $pageSlug = 'broken-link-checker';

	/**
	 * The current page.
	 * This gets set as soon as we've identified that we're on a Broken Link Checker page.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $currentPage = '';

	/**
	 * A list of asset slugs to use.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $assetSlugs = [
		'pages' => 'src/vue/pages/{page}/main.js'
	];

	/**
	 * The plugin basename.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	public $plugin = '';

	/**
	 * The list of pages.
	 *
	 * @since 1.2.0
	 *
	 * @var array
	 */
	private $pages = [];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'registerMenu' ] );
		add_action( 'admin_menu', [ $this, 'hideScheduledActionsMenu' ], 999 );
		add_filter( 'language_attributes', [ $this, 'addDirAttribute' ], 3000 );

		add_filter( 'plugin_row_meta', [ $this, 'registerRowMeta' ], 10, 2 );
		add_filter( 'plugin_action_links_' . AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_BASENAME, [ $this, 'registerActionLinks' ], 10, 2 );

		add_action( 'admin_footer', [ $this, 'addAioseoModalPortal' ] );
	}

	/**
	 * Checks whether the current page is a Broken Link Checker page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the current page is a Broken Link Checker page.
	 */
	public function isBrokenLinkCheckerPage() {
		return ! empty( $this->currentPage );
	}

	/**
	 * Add the dir attribute to the HTML tag.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $output The HTML language attribute.
	 * @return string         The modified HTML language attribute.
	 */
	public function addDirAttribute( $output ) {
		if ( is_rtl() || preg_match( '/dir=[\'"](ltr|rtl|auto)[\'"]/i', (string) $output ) ) {
			return $output;
		}

		return 'dir="ltr" ' . $output;
	}

	/**
	 * Registers the menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerMenu() {
		$hook = add_menu_page(
			__( 'Broken Links', 'aioseo-broken-link-checker' ),
			__( 'Broken Links', 'aioseo-broken-link-checker' ),
			'aioseo_blc_broken_links_page',
			$this->pageSlug,
			[ $this, 'renderMenuPage' ],
			'data:image/svg+xml;base64,' . base64_encode( aioseoBrokenLinkChecker()->helpers->icon() )
		);

		add_action( "load-{$hook}", [ $this, 'checkCurrentPage' ] );

		$this->registerMenuPages();
	}

	/**
	 * Renders the element that we mount our Vue UI on.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function renderMenuPage() {
		echo '<div id="aioseo-blc-app"></div>';
	}

	/**
	 * Registers our menu pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerMenuPages() {
		$hook = add_submenu_page(
			$this->pageSlug,
			__( 'SEO Settings', 'aioseo-broken-link-checker' ),
			__( 'SEO Settings', 'aioseo-broken-link-checker' ),
			'aioseo_blc_about_us_page',
			$this->pageSlug . '-seo-settings',
			[ $this, 'renderMenuPage' ]
		);

		$this->pages[] = $this->pageSlug . '-seo-settings';

		add_action( "load-{$hook}", [ $this, 'redirectSeoSettings' ] );

		$hook = add_submenu_page(
			$this->pageSlug,
			__( 'About Us', 'aioseo-broken-link-checker' ),
			__( 'About Us', 'aioseo-broken-link-checker' ),
			'aioseo_blc_about_us_page',
			$this->pageSlug . '-about',
			[ $this, 'renderMenuPage' ]
		);

		$this->pages[] = $this->pageSlug . '-about';

		add_action( "load-{$hook}", [ $this, 'checkCurrentPage' ] );
	}

	/**
	 * Checks if the current page is a Broken Link Checker page and if so, starts enqueing the relevant assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function checkCurrentPage() {
		global $admin_page_hooks; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		$currentScreen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		if ( empty( $currentScreen->id ) || empty( $admin_page_hooks ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
			return;
		}

		$pages = [
			'about',
			'links',
			'seo-settings'
		];

		foreach ( $pages as $page ) {
			$addScripts = false;

			if ( 'toplevel_page_broken-link-checker' === $currentScreen->id ) {
				$page       = 'links';
				$addScripts = true;
			}

			if ( strpos( $currentScreen->id, 'broken-link-checker-' . $page ) !== false ) {
				$addScripts = true;
			}

			if ( ! $addScripts ) {
				continue;
			}

			// We don't want other plugins adding notices to our screens. Let's clear them out here.
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );

			$this->currentPage = $page;
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueMenuAssets' ], 11 );
			add_filter( 'admin_footer_text', [ $this, 'addFooterText' ] );

			break;
		}
	}

	/**
	 * Enqueues our menu assets, based on the current page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueueMenuAssets() {
		if ( ! $this->currentPage ) {
			return;
		}

		$scriptHandle = str_replace( '{page}', $this->currentPage, $this->assetSlugs['pages'] );
		aioseoBrokenLinkChecker()->core->assets->load( $scriptHandle, [], aioseoBrokenLinkChecker()->helpers->getVueData( $this->currentPage ) );
	}

	/**
	 * Redirects the SEO Settings menu item to the General Settings in AIOSEO if it is installed and active.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function redirectSeoSettings() {
		if ( function_exists( 'aioseo' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=aioseo-settings' ) );
			exit;
		}

		// If AIOSEO isn't active, proceed with loading the menu assets.
		$this->checkCurrentPage();
	}

	/**
	 * Hides the Scheduled Actions menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hideScheduledActionsMenu() {
		// Don't hide it for developers when the main plugin isn't active.
		if ( defined( 'AIOSEO_BROKEN_LINK_CHECKER_DEV' ) && ! function_exists( 'aioseo' ) ) {
			return;
		}

		global $submenu;
		if ( ! isset( $submenu['tools.php'] ) ) {
			return;
		}

		foreach ( $submenu['tools.php'] as $index => $props ) {
			if ( ! empty( $props[2] ) && 'action-scheduler' === $props[2] ) {
				unset( $submenu['tools.php'][ $index ] );

				return;
			}
		}
	}

	/**
	 * Registers our row meta for the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $actions    List of existing actions.
	 * @param  string $pluginFile The plugin file.
	 * @return array              List of action links.
	 */
	public function registerRowMeta( $actions, $pluginFile ) {
		$reviewLabel = str_repeat( '<span class="dashicons dashicons-star-filled" style="font-size: 18px; width:16px; height: 16px; color: #ffb900;"></span>', 5 );

		$actionLinks = [
			'suggest-feature' => [
				// Translators: This is an action link users can click to open a feature request.
				'label' => __( 'Suggest a Feature', 'aioseo-broken-link-checker' ),
				'url'   => aioseoBrokenLinkChecker()->helpers->utmUrl( AIOSEO_BROKEN_LINK_CHECKER_MARKETING_URL . 'blc-feature-suggestion/', 'plugin-row-meta', 'feature' ),
			],
			'review'          => [
				'label' => $reviewLabel,
				'url'   => aioseoBrokenLinkChecker()->helpers->utmUrl( AIOSEO_BROKEN_LINK_CHECKER_MARKETING_URL . 'review-blc', 'plugin-row-meta', 'review' ),
				'title' => sprintf(
					// Translators: 1 - The plugin name ("Broken Link Checker").
					__( 'Rate %1$s', 'aioseo-broken-link-checker' ),
					'Broken Link Checker'
				)
			]
		];

		return $this->parseActionLinks( $actions, $pluginFile, $actionLinks );
	}

	/**
	 * Registers our action links for the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $actions    List of existing actions.
	 * @param  string $pluginFile The plugin file.
	 * @return array              List of action links.
	 */
	public function registerActionLinks( $actions, $pluginFile ) {
		$actionLinks = [
			'support' => [
				// Translators: This is an action link users can click to open our support.
				'label' => __( 'Support', 'aioseo-broken-link-checker' ),
				'url'   => aioseoBrokenLinkChecker()->helpers->utmUrl( AIOSEO_BROKEN_LINK_CHECKER_MARKETING_URL . 'plugin/blc-support', 'plugin-action-links', 'Documentation' ),
			],
			'docs'    => [
				// Translators: This is an action link users can click to open our documentation page.
				'label' => __( 'Documentation', 'aioseo-broken-link-checker' ),
				'url'   => aioseoBrokenLinkChecker()->helpers->utmUrl( AIOSEO_BROKEN_LINK_CHECKER_MARKETING_URL . 'doc-categories/broken-link-checker/', 'plugin-action-links', 'Documentation' ),
			]
		];

		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		return $this->parseActionLinks( $actions, $pluginFile, $actionLinks, 'before' );
	}

	/**
	 * Parses the action links.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $actions     The actions.
	 * @param  string $pluginFile  The plugin file.
	 * @param  array  $actionLinks The action links.
	 * @param  string $position    The position.
	 * @return array               The parsed actions.
	 */
	private function parseActionLinks( $actions, $pluginFile, $actionLinks = [], $position = 'after' ) {
		if ( empty( $this->plugin ) ) {
			$this->plugin = AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_BASENAME;
		}

		if ( $this->plugin === $pluginFile && ! empty( $actionLinks ) ) {
			foreach ( $actionLinks as $key => $value ) {

				$link = [
					$key => sprintf(
						'<a href="%1$s" %2$s target="_blank">%3$s</a>',
						esc_url( $value['url'] ),
						isset( $value['title'] ) ? 'title="' . esc_attr( $value['title'] ) . '"' : '',
						$value['label']
					)
				];

				$actions = 'after' === $position ? array_merge( $actions, $link ) : array_merge( $link, $actions );
			}
		}

		return $actions;
	}

	/**
	 * Add the div for the modal portal.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function addAioseoModalPortal() {
		if ( ! function_exists( 'aioseo' ) ) {
			echo '<div id="aioseo-modal-portal"></div>';
		}
	}

	/**
	 * Checks whether the current page is a Broken Link Checker menu page.
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether the current page is a Broken Link Checker menu page.
	 */
	public function isBlcScreen() {
		$currentScreen = aioseoBrokenLinkChecker()->helpers->getCurrentScreen();
		if ( empty( $currentScreen->id ) ) {
			return false;
		}

		$adminPages = array_keys( $this->pages );
		$adminPages = array_map( function( $slug ) {
			if ( 'aioseo' === $slug ) {
				return 'toplevel_page_broken-link-checker';
			}

			return 'broken-link-checker_page_' . $slug;
		}, $adminPages );

		return in_array( $currentScreen->id, $adminPages, true );
	}

	/**
	 * Add footer text to the WordPress admin screens.
	 *
	 * @since 1.2.0
	 *
	 * @return string The footer text.
	 */
	public function addFooterText() {
		$linkText = esc_html__( 'Give us a 5-star rating!', 'aioseo-broken-link-checker' );
		$href     = 'https://wordpress.org/support/plugin/broken-link-checker-seo/reviews/?filter=5#new-post';

		$link1 = sprintf(
			'<a href="%1$s" target="_blank" title="%2$s">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
			$href,
			$linkText
		);

		$link2 = sprintf(
			'<a href="%1$s" target="_blank" title="%2$s">WordPress.org</a>',
			$href,
			$linkText
		);

		printf(
			// Translators: 1 - The plugin name ("Broken Link Checker"), - 2 - This placeholder will be replaced with star icons, - 3 - "WordPress.org" - 4 - The plugin name ("Broken Link Checker").
			esc_html__( 'Please rate %1$s %2$s on %3$s to help us spread the word. Thank you!', 'aioseo-broken-link-checker' ),
			sprintf( '<strong>%1$s</strong>', esc_html( AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_NAME ) ),
			wp_kses_post( $link1 ),
			wp_kses_post( $link2 )
		);

		// Stop WP Core from outputting its version number and instead add both theirs & ours.
		global $wp_version; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		printf(
			wp_kses_post( '<p class="alignright">%1$s</p>' ),
			sprintf(
				// Translators: 1 - WP Core version number, 2 - BLC version number.
				esc_html__( 'WordPress %1$s | BLC %2$s', 'aioseo-broken-link-checker' ),
				esc_html( $wp_version ), // phpcs:ignore Squiz.NamingConventions.ValidVariableName
				esc_html( AIOSEO_BROKEN_LINK_CHECKER_VERSION )
			)
		);

		remove_filter( 'update_footer', 'core_update_footer' );

		return '';
	}
}