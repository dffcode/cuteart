<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles links table related routes.
 *
 * @since 1.1.0
 */
class LinksTable extends CommonTableActions {
	/**
	 * Returns the data for the links table.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function fetchData( $request ) {
		$body         = $request->get_json_params();
		$linkStatusId = ! empty( $body['linkStatusId'] ) ? intval( $body['linkStatusId'] ) : null;
		$limit        = ! empty( $body['limit'] ) ? intval( $body['limit'] ) : 20;
		$offset       = ! empty( $body['offset'] ) ? intval( $body['offset'] ) : 0;
		$searchTerm   = ! empty( $body['searchTerm'] ) ? sanitize_text_field( $body['searchTerm'] ) : null;
		$whereClause  = Models\Link::getLinkWhereClause( $searchTerm );

		if ( empty( $linkStatusId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status ID was provided.'
			], 400 );
		}

		$totalRows = Models\Link::rowQueryCount( $linkStatusId, $whereClause );
		$page      = 0 === $offset ? 1 : ( $offset / $limit ) + 1;

		return new \WP_REST_Response( [
			'success' => true,
			'links'   => [
				'rows'   => Models\Link::rowQuery( $linkStatusId, $limit, $offset, $whereClause ),
				'totals' => [
					'page'  => $page,
					'pages' => ceil( $totalRows / $limit ),
					'total' => $totalRows
				]
			]
		], 200 );
	}

	/**
	 * Executes the given bulk action on the given rows.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function bulk( $request ) {
		$body   = $request->get_json_params();
		$action = ! empty( $body['action'] ) ? sanitize_text_field( $body['action'] ) : null;
		$rows   = ! empty( $body['rows'] ) ? $body['rows'] : null;
		if ( empty( $action ) || empty( $rows ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No action or rows given.'
			], 400 );
		}

		switch ( $action ) {
			case 'unlink':
				foreach ( $rows as $row ) {
					self::removeLink( $row['id'] );
				}
				break;
			default:
				break;
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}
}