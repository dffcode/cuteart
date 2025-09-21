<?php
/**
 * WPForms Save class.
 *
 * @since 2.9.0
 *
 * @package OMAPI
 * @author  Eduardo Nakatsuka
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPForms Save class.
 *
 * @since 2.9.0
 */
class OMAPI_WPForms_Save {

	/**
	 * Holds save error.
	 *
	 * @since 2.9.0
	 *
	 * @var mixed
	 */
	public $error = null;

	/**
	 * Holds the base class object.
	 *
	 * @since 2.9.0
	 *
	 * @var OMAPI
	 */
	public $base;

	/**
	 * Primary class constructor.
	 *
	 * @since 2.9.0
	 */
	public function __construct() {
		$this->base = OMAPI::get_instance();
	}

	/**
	 * Helper method to handle connecting WPForms to OptinMonster.
	 *
	 * @since 2.9.0
	 *
	 * @return void
	 */
	public function connect() {
		$this->update_connection();
	}

	/**
	 * Helper method to handle disconnecting WPForms to OptinMonster.
	 *
	 * @since 2.9.0
	 *
	 * @return void
	 */
	public function disconnect() {
		$this->update_connection( false );
	}

	/**
	 * Handles connecting or disconnecting WPForms to OptinMonster.
	 *
	 * @since 2.9.0
	 *
	 * @param bool $connect True to connect, false to disconnect.
	 *
	 * @return void
	 */
	public function update_connection( $connect = true ) {
		$creds = $this->base->get_api_credentials();

		if ( empty( $creds['apikey'] ) && empty( $creds['user'] ) && empty( $creds['key'] ) ) {
			return;
		}

		// Make a connection request.
		$action = $connect ? 'connect' : 'disconnect';
		$api    = new OMAPI_Api( 'wpforms/' . $action, $creds, 'POST', 'v2' );

		$response = $api->request( OMAPI_Api::get_url_args() );

		if ( is_wp_error( $response ) ) {
			$message = $connect
				/* translators: %s - Error message found while connecting to OptinMonster. */
				? esc_html__( 'WPForms could not be connected to OptinMonster. The OptinMonster API returned with the following response: %s', 'optin-monster-api' )
				/* translators: %s - Error message found while disconnecting to OptinMonster. */
				: esc_html__( 'WPForms could not be disconnected from OptinMonster. The OptinMonster API returned with the following response: %s', 'optin-monster-api' );

			$this->error = sprintf( $message, $response->get_error_message() );
		}
	}
}
