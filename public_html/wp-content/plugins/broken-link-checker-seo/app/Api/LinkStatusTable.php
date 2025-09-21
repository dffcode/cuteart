<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles link related routes.
 *
 * @since 1.1.0
 */
class LinkStatusTable extends CommonTableActions {
	/**
	 * Returns the data for the Broken Links Report.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function fetchData( $request ) {
		$body       = $request->get_json_params();
		$limit      = ! empty( $body['limit'] ) ? intval( $body['limit'] ) : 20;
		$offset     = ! empty( $body['offset'] ) ? intval( $body['offset'] ) : 0;
		$searchTerm = ! empty( $body['searchTerm'] ) ? sanitize_text_field( $body['searchTerm'] ) : null;
		$filter     = ! empty( $body['filter'] ) ? sanitize_text_field( $body['filter'] ) : 'all';
		$orderBy    = ! empty( $body['orderBy'] ) ? sanitize_text_field( $body['orderBy'] ) : 'id';
		$orderDir   = ! empty( $body['orderDir'] ) && ! empty( $body['orderBy'] ) ? strtoupper( sanitize_text_field( $body['orderDir'] ) ) : 'DESC';

		return new \WP_REST_Response( [
			'success'      => true,
			'linkStatuses' => aioseoBrokenLinkChecker()->helpers->getLinkStatusesData( $limit, $offset, $searchTerm, $filter, $orderBy, $orderDir )
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
			case 'recheck':
				$responseBody = self::recheckLinks( $rows );

				if ( null !== $responseBody ) {
					aioseoBrokenLinkChecker()->internalOptions->internal->license->quotaRemaining = $responseBody->quotaRemaining;

					// If the quota changed, reactivate the license to pull in the latest date from the marketing site.
					if ( aioseoBrokenLinkChecker()->internalOptions->internal->license->quota !== $responseBody->quota ) {
						aioseoBrokenLinkChecker()->internalOptions->internal->license->quota = $responseBody->quota;
						aioseoBrokenLinkChecker()->license->activate();
					}
				}
				break;
			case 'dismiss':
				foreach ( $rows as $row ) {
					self::setLinkStatusDismissed( $row['id'] );
				}
				break;
			case 'undismiss':
				foreach ( $rows as $row ) {
					self::setLinkStatusDismissed( $row['id'], false );
				}
				break;
			case 'unlink':
				foreach ( $rows as $row ) {
					$links = Models\Link::getByLinkStatusId( $row['id'] );
					foreach ( $links as $link ) {
						$link = (array) $link;
						self::removeLink( $link['id'] );
					}
				}
				break;
			default:
				break;
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Rechecks the given link.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function recheck( $request ) {
		$body         = $request->get_json_params();
		$linkStatusId = ! empty( $body['linkStatusId'] ) ? intval( $body['linkStatusId'] ) : null;
		if ( empty( $linkStatusId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status ID given.'
			], 400 );
		}

		// Construct a list with a single row so that we can pass it into the bulk recheck handler.
		$linkStatusRows = [
			[
				'id' => $linkStatusId,
			]
		];

		$response = self::recheckLinks( $linkStatusRows );
		if ( ! $response ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'Link could not be checked.'
			], 400 );
		}

		aioseoBrokenLinkChecker()->internalOptions->internal->license->quotaRemaining = $response->quotaRemaining;
		if ( aioseoBrokenLinkChecker()->internalOptions->internal->license->quota !== $response->quota ) {
			// If the quota changed, reactivate the license to pull in the latest date from the marketing site.
			aioseoBrokenLinkChecker()->internalOptions->internal->license->quota = $response->quota;
			aioseoBrokenLinkChecker()->license->activate();
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Dismisses the given link.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function dismiss( $request ) {
		$body         = $request->get_json_params();
		$linkStatusId = ! empty( $body['linkStatusId'] ) ? intval( $body['linkStatusId'] ) : null;
		if ( empty( $linkStatusId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status ID given.'
			], 400 );
		}

		$success = self::setLinkStatusDismissed( $linkStatusId );
		if ( ! $success ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status found.'
			], 400 );
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Undismisses the given link.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function undismiss( $request ) {
		$body         = $request->get_json_params();
		$linkStatusId = ! empty( $body['linkStatusId'] ) ? intval( $body['linkStatusId'] ) : null;
		if ( empty( $linkStatusId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status ID given.'
			], 400 );
		}

		$success = self::setLinkStatusDismissed( $linkStatusId, false );
		if ( ! $success ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status found.'
			], 400 );
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Sets the dismissed value for a given Link Status object.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from BrokenLinks to TableActions.
	 *
	 * @param  int  $linkStatusId The Link Status ID.
	 * @param  bool $value        The new value.
	 * @return bool               Whether the record was updated.
	 */
	protected static function setLinkStatusDismissed( $linkStatusId, $value = true ) {
		$linkStatus = Models\LinkStatus::getById( $linkStatusId );
		if ( ! $linkStatus->exists() ) {
			return false;
		}

		$linkStatus->dismissed = $value;
		$linkStatus->save();

		return true;
	}
}