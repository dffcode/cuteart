<?php
/**
 * Uninstall Broken Link Checker.
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load plugin file.
require_once 'aioseo-broken-link-checker.php';

// Disable Action Scheduler Queue Runner.
if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
	ActionScheduler_QueueRunner::instance()->unhook_dispatch_async_request();
}

// Drop our custom tables.
aioseoBrokenLinkChecker()->core->uninstall->dropData();