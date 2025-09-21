<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles link status detail page routes.
 *
 * @since 1.1.0
 */
class LinkStatusDetail {
	/**
	 * Returns the Link Status data for the Link Detail page.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function getLinkStatusData( $request ) {
		$body         = $request->get_json_params();
		$linkStatusId = ! empty( $body['linkStatusId'] ) ? intval( $body['linkStatusId'] ) : null;
		if ( empty( $linkStatusId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status ID was provided.'
			], 400 );
		}

		return new \WP_REST_Response( [
			'success'    => true,
			'linkStatus' => Models\LinkStatus::getById( $linkStatusId )
		], 200 );
	}
}