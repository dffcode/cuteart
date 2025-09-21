<?php
namespace AIOSEO\BrokenLinkChecker\Standalone;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles highlighting broken links.
 *
 * @since 1.2.0
 */
class Highlighter {
	/**
	 * Class constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initializes the class.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function init() {
		if (
			is_admin() ||
			! is_user_logged_in() ||
			! current_user_can( 'edit_posts' )
		) {
			return;
		}

		if ( ! aioseoBrokenLinkChecker()->options->general->highlightBrokenLinks ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScript' ] );
	}

	/**
	 * Enqueues the script.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function enqueueScript() {
		$scriptHandle = 'src/vue/standalone/highlighter/main.js';
		aioseoBrokenLinkChecker()->core->assets->load( $scriptHandle, [], aioseoBrokenLinkChecker()->helpers->getVueData( 'highlighter' ) );
	}
}