<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \AIOSEO\Plugin\Addon\Redirects\Utils as RedirectUtils;

/**
 * Handles all redirect related routes.
 *
 * @since 1.1.0
 */
class Redirects {
	/**
	 * Returns the hash for a redirect added through the redirect monitor.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The request
	 * @return \WP_REST_Response          The response.
	 */
	public static function getRedirectUrl( $request ) {
		if ( ! function_exists( 'aioseoRedirects' ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		$body          = $request->get_json_params();
		$linkStatusUrl = ! empty( $body['linkStatusUrl'] ) ? sanitize_text_field( $body['linkStatusUrl'] ) : '';
		if ( empty( $linkStatusUrl ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		$urls = [
			[
				'url' => RedirectUtils\WpUri::excludeHomeUrl( $linkStatusUrl )
			]
		];

		$hash = md5( wp_json_encode( $urls ) );
		aioseoRedirects()->cache->update( 'manual-urls-' . $hash, $urls, HOUR_IN_SECONDS );

		$redirectUrl = add_query_arg( 'aioseo-manual-urls', $hash, admin_url( 'admin.php?page=aioseo-redirects' ) );

		return new \WP_REST_Response( [
			'success'     => true,
			'redirectUrl' => $redirectUrl
		], 200 );
	}
}