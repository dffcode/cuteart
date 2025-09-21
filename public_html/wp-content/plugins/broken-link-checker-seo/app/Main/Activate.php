<?php
namespace AIOSEO\BrokenLinkChecker\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin (de)activation.
 *
 * @since 1.0.0
 */
class Activate {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		register_activation_hook( AIOSEO_BROKEN_LINK_CHECKER_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( AIOSEO_BROKEN_LINK_CHECKER_FILE, [ $this, 'deactivate' ] );
	}

	/**
	 * Runs on activation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function activate() {
		aioseoBrokenLinkChecker()->access->addCapabilities();

		// Set the activation timestamps.
		$time = time();
		aioseoBrokenLinkChecker()->internalOptions->internal->activated = $time;

		if ( ! aioseoBrokenLinkChecker()->internalOptions->internal->firstActivated ) {
			$this->showSetupWizard();

			aioseoBrokenLinkChecker()->internalOptions->internal->firstActivated = $time;
		}

		aioseoBrokenLinkChecker()->core->cache->clear();
	}

	/**
	 * Show the setup wizard if this is the first time the user activates the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function showSetupWizard() {
		if ( aioseoBrokenLinkChecker()->internalOptions->internal->firstActivated ) {
			return;
		}

		if ( is_network_admin() ) {
			return;
		}

		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore HM.Security.NonceVerification.Recommended
			return;
		}

		// Sets 30 second transient for welcome screen redirect on activation.
		aioseoBrokenLinkChecker()->core->cache->update( 'activation_redirect', true, 30 );
	}

	/**
	 * Runs on deactivation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function deactivate() {
		aioseoBrokenLinkChecker()->access->removeCapabilities();
	}
}