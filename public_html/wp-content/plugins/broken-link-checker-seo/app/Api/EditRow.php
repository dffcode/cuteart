<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles link status/link row edit updates.
 *
 * @since 1.1.0
 */
class EditRow extends CommonTableActions {
	/**
	 * Edits the given link status/link row.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The request
	 * @return \WP_REST_Response          The response.
	 */
	public static function update( $request ) {
		$body         = $request->get_json_params();
		$linkStatusId = ! empty( $body['linkStatusId'] ) ? intval( $body['linkStatusId'] ) : null;
		$linkId       = ! empty( $body['linkId'] ) ? intval( $body['linkId'] ) : null;
		if ( empty( $linkStatusId ) && empty( $linkId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status or link ID ID was provided.'
			], 400 );
		}

		$newUrl    = ! empty( $body['url'] ) ? sanitize_text_field( $body['url'] ) : '';
		$newAnchor = ! empty( $body['anchor'] ) ? sanitize_text_field( $body['anchor'] ) : '';
		if ( empty( $newUrl ) && empty( $newAnchor ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No new URL or anchor was provided.'
			], 400 );
		}

		// If a link status ID was provided, then we need to update the URL for each link related to this link status.
		if ( $linkStatusId ) {
			$links = Models\Link::getByLinkStatusId( $linkStatusId );
			foreach ( $links as $link ) {
				self::updateLink( $link->id, '', $newUrl );
			}
		}

		if ( $linkId ) {
			self::updateLink( $linkId, $newAnchor, $newUrl );
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}
}