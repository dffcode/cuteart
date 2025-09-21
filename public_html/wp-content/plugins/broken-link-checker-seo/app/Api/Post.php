<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles all post related routes.
 *
 * @since 1.1.0
 */
class Post {
	/**
	 * Deletes the given post.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The request
	 * @return \WP_REST_Response          The response.
	 */
	public static function deletePost( $request ) {
		$body   = $request->get_json_params();
		$postId = ! empty( $body['postId'] ) ? intval( $body['postId'] ) : null;
		if ( empty( $postId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No post ID given.'
			], 400 );
		}

		$success = wp_trash_post( $postId );
		if ( ! $success ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'Failed to delete post.'
			], 500 );
		}

		Models\Link::deleteLinks( $postId );

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}
}