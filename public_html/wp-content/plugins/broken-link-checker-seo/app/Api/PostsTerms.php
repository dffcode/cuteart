<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles post/term lookups.
 *
 * @since 1.0.0
 */
class PostsTerms {
	/**
	 * Returns posts by ID/name.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function searchForObjects( $request ) {
		$body       = $request->get_json_params();
		$searchTerm = ! empty( $body['query'] ) ? sanitize_text_field( $body['query'] ) : null;
		$type       = ! empty( $body['type'] ) ? sanitize_text_field( $body['type'] ) : null;
		if ( empty( $searchTerm ) || empty( $type ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No search term or object type was provided.'
			], 400 );
		}

		$escapedSearchTerm = esc_sql( aioseoBrokenLinkChecker()->core->db->db->esc_like( $searchTerm ) );

		$objects = [];
		if ( 'posts' === $body['type'] ) {
			$postTypes = aioseoBrokenLinkChecker()->helpers->getPublicPostTypes( true );
			$objects   = aioseoBrokenLinkChecker()->core->db
				->start( 'posts' )
				->select( 'ID, post_type, post_title, post_name' )
				->whereRaw( "( post_title LIKE '%{$escapedSearchTerm}%' OR post_name LIKE '%{$escapedSearchTerm}%' OR ID = '{$escapedSearchTerm}' )" )
				->whereIn( 'post_type', $postTypes )
				->whereIn( 'post_status', [ 'publish', 'draft', 'future', 'pending' ] )
				->orderBy( 'post_title' )
				->limit( 10 )
				->run()
				->result();
		}

		if ( empty( $objects ) ) {
			return new \WP_REST_Response( [
				'success' => true,
				'objects' => []
			], 200 );
		}

		$parsedObjects = [];
		foreach ( $objects as $object ) {
			if ( 'posts' === $type ) {
				$parsedObjects[] = [
					'value' => (int) $object->ID,
					'slug'  => $object->post_name,
					'label' => $object->post_title,
					'type'  => $object->post_type,
					'link'  => get_permalink( $object->ID )
				];
			}
		}

		return new \WP_REST_Response( [
			'success' => true,
			'objects' => $parsedObjects
		], 200 );
	}
}