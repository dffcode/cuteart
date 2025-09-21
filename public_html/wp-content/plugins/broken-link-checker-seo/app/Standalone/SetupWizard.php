<?php
namespace AIOSEO\BrokenLinkChecker\Standalone;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that holds our setup wizard.
 *
 * @since 1.0.0
 */
class SetupWizard {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! is_admin() || wp_doing_cron() || wp_doing_ajax() ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'loadSetupWizard' ] );
		add_action( 'admin_init', [ $this, 'redirect' ], 9999 );
		add_action( 'admin_menu', [ $this, 'addDashboardPage' ] );
		add_action( 'admin_head', [ $this, 'hideDashboardPageFromMenu' ] );
	}

	/**
	 * Redirects the user to the setup wizard.
	 * This method checks if a new install or update has just occurred. If so, then we redirect the user to the appropriate page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function redirect() {
		if ( ! aioseoBrokenLinkChecker()->core->cache->get( 'activation_redirect' ) ) {
			return;
		}

		// If we are redirecting, clear the transient so it just happens once.
		aioseoBrokenLinkChecker()->core->cache->delete( 'activation_redirect' );

		if ( isset( $_GET['activate-multi'] ) || is_network_admin() ) { // phpcs:ignore HM.Security.NonceVerification.Recommended
			return;
		}

		wp_safe_redirect( admin_url( 'index.php?page=broken-link-checker-setup-wizard' ) );
		exit;
	}

	/**
	 * Adds a dashboard page for our setup wizard.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addDashboardPage() {
		add_dashboard_page(
			__( 'Setup Wizard', 'aioseo-broken-link-checker' ),
			__( 'Setup Wizard', 'aioseo-broken-link-checker' ),
			'aioseo_blc_setup_wizard_page',
			'broken-link-checker-setup-wizard',
			''
		);
	}

	/**
	 * Hide the dashboard page from the menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hideDashboardPageFromMenu() {
		remove_submenu_page( 'index.php', 'broken-link-checker-setup-wizard' );
	}

	/**
	 * Checks to see if we should load the setup wizard.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function loadSetupWizard() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// phpcs:disable HM.Security.ValidatedSanitizedInput.InputNotSanitized, HM.Security.NonceVerification.Recommended
		if (
			! isset( $_GET['page'] ) ||
			'broken-link-checker-setup-wizard' !== wp_unslash( $_GET['page'] ) ||
			! current_user_can( 'aioseo_blc_setup_wizard_page' )
		) {
			return;
		}
		// phpcs:enable

		set_current_screen();

		// Remove an action in the Gutenberg plugin (not core Gutenberg) which throws an error.
		remove_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );

		// If we are redirecting, clear the transient so it just happens once.
		aioseoBrokenLinkChecker()->core->cache->delete( 'activation_redirect' );

		$this->loadSetupWizardAssets();
	}

	/**
	 * Load the assets for the setup wizard.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function loadSetupWizardAssets() {
		$this->enqueueScripts();
		$this->setupWizardHeader();
		$this->setupWizardContent();
		$this->setupWizardFooter();
		exit;
	}

	/**
	 * Enqueues scripts for the setup wizard.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueueScripts() {
		// We don't want other plugins adding notices to our screens. Let's clear them out here.
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );

		$scriptHandle = 'src/vue/standalone/setup-wizard/main.js';
		aioseoBrokenLinkChecker()->core->assets->load( $scriptHandle, [], aioseoBrokenLinkChecker()->helpers->getVueData( 'setup-wizard' ) );

		wp_enqueue_style( 'common' );
	}

	/**
	 * Outputs the simplified header used for the Setup Wizard.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setupWizardHeader() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title>
			<?php
				// Translators: 1 - The plugin name ("Broken Link Checker").
				echo sprintf( esc_html__( '%1$s &rsaquo; Setup Wizard', 'aioseo-broken-link-checker' ), esc_html( AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_NAME ) );
			?>
			</title>
		</head>
		<body class="aioseo-blc-setup-wizard">
		<?php
	}

	/**
	 * Outputs the div to mount on.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setupWizardContent() {
		echo '<div id="aioseo-blc-app">';
		// TODO: Add JavaScript error page here.
		echo '</div>';
	}

	/**
	 * Outputs the simplified footer used for the Setup Wizard.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function setupWizardFooter() {
		?>
		<?php
		wp_print_scripts( 'aioseo-vendors' );
		wp_print_scripts( 'aioseo-common' );
		wp_print_scripts( 'aioseo-setup-wizard-script' );
		do_action( 'admin_footer', '' );
		do_action( 'admin_print_footer_scripts' );
		?>
		</body>
		</html>
		<?php
	}
}