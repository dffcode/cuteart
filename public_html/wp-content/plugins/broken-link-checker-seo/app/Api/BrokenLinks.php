<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles all general Broken Links report related routes.
 *
 * @since 1.1.0
 */
class BrokenLinks {
	/**
	 * Returns the scan percent completed.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The request
	 * @return \WP_REST_Response          The response.
	 */
	public static function getScanPercent( $request ) {
		$body   = $request->get_json_params();
		$scan = ! empty( $body['scan'] ) ? sanitize_text_field( $body['scan'] ) : '';
		if ( empty( $scan ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No scan name given.'
			], 400 );
		}

		$percentage = 0;
		switch ( $scan ) {
			case 'links':
				$percentage = aioseoBrokenLinkChecker()->main->links->data->getScanPercentage();
				break;
			case 'linkStatuses':
				$percentage = aioseoBrokenLinkChecker()->main->linkStatus->data->getScanPercentage();
				break;
			default:
				break;
		}

		return new \WP_REST_Response( [
			'success' => true,
			'percent' => $percentage
		], 200 );
	}
}