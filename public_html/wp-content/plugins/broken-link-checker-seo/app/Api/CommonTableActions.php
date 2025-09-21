<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles all common table action handlers.
 *
 * @since 1.1.0
 */
abstract class CommonTableActions {
	/**
	 * Unlinks the given link.
	 *
	 * @since 1.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function unlink( $request ) {
		$body         = $request->get_json_params();
		$linkStatusId = ! empty( $body['linkStatusId'] ) ? intval( $body['linkStatusId'] ) : null;
		$linkId       = ! empty( $body['linkId'] ) ? intval( $body['linkId'] ) : null;
		if ( empty( $linkStatusId ) && empty( $linkId ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No link status ID or link ID given.'
			], 400 );
		}

		if ( ! empty( $linkStatusId ) ) {
			$links = Models\Link::getByLinkStatusId( $linkStatusId );
			foreach ( $links as $link ) {
				self::removeLink( $link->id );
			}

			return new \WP_REST_Response( [
				'success' => true
			], 200 );
		}

		$success = self::removeLink( $linkId );
		if ( empty( $success ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'Link could not be removed.'
			], 400 );
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Rechecks the given links.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from BrokenLinks to TableActions and add support for bulk-checking rows.
	 *
	 * @param  array       $linkStatusRows The Link Status rows.
	 * @return object|bool                 The response or false if the links could not be checked.
	 */
	protected static function recheckLinks( $linkStatusRows ) {
		$linkStatusIds = array_map( function( $linkStatusRow ) {
			return $linkStatusRow['id'];
		}, $linkStatusRows );

		$linkStatuses = Models\LinkStatus::getByIds( $linkStatusIds );
		if ( empty( $linkStatuses ) ) {
			return false;
		}

		$rows = [];
		foreach ( $linkStatuses as $linkStatus ) {
			$rows[ $linkStatus->id ] = $linkStatus->url;
		}

		$requestBody = array_merge(
			aioseoBrokenLinkChecker()->main->linkStatus->data->getBaseData(),
			[ 'rows' => $rows ]
		);

		$response     = aioseoBrokenLinkChecker()->main->linkStatus->doPostRequest( 'recheck-bulk', $requestBody );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );
		$responseBody = json_decode( wp_remote_retrieve_body( $response ) );
		if ( is_wp_error( $response ) && 200 !== $responseCode || empty( $responseBody->success ) || empty( $responseBody->rows ) ) {
			return false;
		}

		foreach ( $responseBody->rows as $row ) {
			// Parse the data into a useable format and then save the updated results.
			aioseoBrokenLinkChecker()->main->linkStatus->parseResultsHelper( $row );
		}

		return $responseBody;
	}

	/**
	 * Updates a given link with a new anchor and/or URL.
	 *
	 * @since 1.1.0
	 *
	 * @param  int    $linkId    The Link ID.
	 * @param  string $newAnchor The new anchor.
	 * @param  string $newUrl    The new URL.
	 * @return bool              Whether the Link was updated.
	 */
	protected static function updateLink( $linkId, $newAnchor = '', $newUrl = '' ) {
		$link = Models\Link::getById( $linkId );
		if ( ! $link->exists() ) {
			return false;
		}

		$post = get_post( $link->post_id );
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		if ( empty( $newAnchor ) && empty( $newUrl ) ) {
			return false;
		}

		// First, update the link in the phrase.
		$oldAnchor     = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->anchor );
		$oldUrl        = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->url );
		$escapedAnchor = aioseoBrokenLinkChecker()->helpers->escapeRegexReplacement( $newAnchor ?: $link->anchor );
		$escapedUrl    = aioseoBrokenLinkChecker()->helpers->escapeRegexReplacement( $newUrl ?: $link->url );

		$newPhraseHtml = preg_replace( "/(<a.*?href=\")($oldUrl)(\".*?>[\s\w]*?)(<[^>]+>)?($oldAnchor)(<\/[^>]+>)?([\s\w]*?<\/a>)/is", "$1$escapedUrl$3$4$escapedAnchor$6$7", $link->phrase_html );

		$success = self::updateLinkInContent( $post, $link, $newPhraseHtml );
		if ( ! $success ) {
			// It's possible that the update failed because the original/old URL is relative in the phrase HTML.
			// In that case, make the old URL relative to match it.
			// This is needed because we make URLs absolute before storing them in the DB.
			$relativeUrl = self::makeUrlRelative( $link->url );
			if ( $relativeUrl !== $link->url ) {
				$oldUrl        = aioseoBrokenLinkChecker()->helpers->escapeRegex( $relativeUrl );
				$newPhraseHtml = preg_replace( "/(<a.*?href=\")($oldUrl)(\".*?>[\s\w]*?)(<[^>]+>)?($oldAnchor)(<\/[^>]+>)?([\s\w]*?<\/a>)/is", "$1$escapedUrl$3$4$escapedAnchor$6$7", $link->phrase_html ); // phpcs:ignore Generic.Files.LineLength.MaxExceeded

				$success = self::updateLinkInContent( $post, $link, $newPhraseHtml );
			}
		}

		return $success;
	}

	/**
	 * Removes a given link.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from BrokenLinks to TableActions.
	 *
	 * @param  int  $linkId The Link ID.
	 * @return bool         Whether the Link was unlinked.
	 */
	protected static function removeLink( $linkId ) {
		$link = Models\Link::getById( $linkId );
		if ( ! $link->exists() ) {
			return false;
		}

		$post = get_post( $link->post_id );
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		// First, remove the link in the phrase.
		$escapedAnchor = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->anchor );
		$newPhraseHtml = preg_replace( "/<a.*?>([\s\w<>]*?{$escapedAnchor}[\s\w<>\/]*?)<\/a>/is", '$1', (string) aioseoBrokenLinkChecker()->helpers->escapeRegexReplacement( $link->phrase_html ) );

		if ( self::checkIsRelativeUrl( $link->url ) ) {
			$escapedUrl    = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->url );
			$newPhraseHtml = preg_replace( "/<a.*?href=\"{$escapedUrl}\".*?>[\s\w<>]*?{$escapedAnchor}[\s\w<>\/]*?<\/a>/is", $escapedAnchor, $newPhraseHtml );
		}

		return self::updateLinkInContent( $post, $link, $newPhraseHtml, true );
	}

	/**
	 * Adds, updates or removes a link in the content.
	 *
	 * @since 1.2.3
	 *
	 * @param  \WP_Post $post          The post object.
	 * @param  object   $link          The link object.
	 * @param  string   $newPhraseHtml The new phrase HTML.
	 * @param  bool     $isDeletion    Whether the link is being deleted.
	 * @return bool                    Whether the link was updated/deleted.
	 */
	private static function updateLinkInContent( $post, $link, $newPhraseHtml, $isDeletion = false ) {
		$postContent   = str_replace( '&nbsp;', ' ', (string) $post->post_content );
		$oldPhraseHtml = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->phrase_html );
		$pattern       = "/$oldPhraseHtml/i";

		$postContent = preg_replace( $pattern, $newPhraseHtml, (string) $postContent );

		// If the phrase is still there and we're deleting, attempt to remove it without the phrase if it occurs just once.
		if ( $isDeletion && preg_match( $pattern, $postContent ) ) {
			// Check if the post has just one occurence of this link.
			$escapedAnchor = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->anchor );
			$escapedUrl    = aioseoBrokenLinkChecker()->helpers->escapeRegex( $link->url );
			$pattern2      = "/<a.*?href=\"{$escapedUrl}\".*?>[\s\w<>]*?{$escapedAnchor}[\s\w<>\/]*?<\/a>/is";
			preg_match_all( $pattern2, $postContent, $matches );

			// If there's just one match, remove it without the phrase.
			if ( isset( $matches[0] ) && 1 === count( $matches[0] ) ) {
				$escapedAnchorReplacement = aioseoBrokenLinkChecker()->helpers->escapeRegexReplacement( $link->anchor );
				$postContent              = preg_replace( $pattern2, $escapedAnchorReplacement, $postContent );
			}
		}

		// Check again. If the phrase is still the same, bail.
		if ( preg_match( $pattern, $postContent ) ) {
			return false;
		}

		// Reset modified date when the post is updated if the option is enabled.
		$limitModifiedDate = aioseoBrokenLinkChecker()->options->general->linkTweaks->limitModifiedDate;
		if ( $limitModifiedDate ) {
			add_filter( 'wp_insert_post_data', function ( $data ) use ( $post ) {
				$data['post_modified']     = $post->post_modified;
				$data['post_modified_gmt'] = $post->post_modified_gmt;

				return $data;
			}, 99999, 1 );
		}

		// Now, update the post with the modified post content.
		$error = wp_update_post( [
			'ID'           => $link->post_id,
			'post_content' => $postContent
		], true );

		if ( 0 === $error || is_a( $error, 'WP_Error' ) ) {
			return false;
		}

		// Indicate that the post needs to be rescanned.
		aioseoBrokenLinkChecker()->main->links->postsToRescan[] = $link->post_id;

		// The "save_post" callback will trigger a rescan of the post, so we can delete the existing Link record.
		$link->delete();

		return true;
	}

	/**
	 * Checks if the given URL is relative.
	 *
	 * @since 1.2.3
	 *
	 * @param  string $url The URL to check.
	 * @return bool        Whether the URL is relative.
	 */
	private static function checkIsRelativeUrl( $url ) {
		$parsedUrl = wp_parse_url( $url );
		if ( ! $parsedUrl ) {
			return false;
		}

		return empty( $parsedUrl['scheme'] ) && empty( $parsedUrl['host'] );
	}

	/**
	 * Makes the given URL relative.
	 *
	 * @since 1.2.3
	 *
	 * @param  string $url The URL to make relative.
	 * @return string      The relative URL.
	 */
	private static function makeUrlRelative( $url ) {
		$parsedUrl = wp_parse_url( $url );
		if ( ! $parsedUrl ) {
			return $url;
		}

		return ! empty( $parsedUrl['path'] ) ? $parsedUrl['path'] : $url;
	}
}