<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'aioseo_blc_is_plugin_disabled' ) ) {
	/**
	 * Disable the plugin if triggered externally.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the plugin should be disabled.
	 */
	function aioseo_blc_is_plugin_disabled() {
		if ( ! defined( 'AIOSEO_DEV_VERSION' ) && ! isset( $_REQUEST['aioseo-dev'] ) ) { // phpcs:ignore HM.Security.NonceVerification.Recommended
			return false;
		}

		if ( ! isset( $_REQUEST['aioseo-disable-broken-link-checker'] ) ) { // phpcs:ignore HM.Security.NonceVerification.Recommended
			return false;
		}

		return true;
	}
}