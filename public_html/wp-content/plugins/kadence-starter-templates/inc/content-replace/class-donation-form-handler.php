<?php
/**
 * WooCommerce Content Handler
 *
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates\ContentReplace;

/**
 * Class for handling WooCommerce content replacements
 */
class Donation_Form_Handler {

	/**
	 * Get string between two strings
	 *
	 * @param string $str The string to search in.
	 * @param string $start The starting string.
	 * @param string $end The ending string.
	 * @param int    $from Position to start searching from.
	 * @return string
	 */
	private static function get_string_between( $str, $start, $end, $from = 0 ) {
		// Check if product block exists.
		if ( strpos( $str, $start ) === false ) {
			return '';
		}

		// Get the start of the container.
		$startpos = strpos( $str, $start, $from );
		if ( $startpos === false ) {
			return '';
		}

		$pos = $startpos + strlen( $start );
		$endpos = strpos( $str, $end, $pos );
		
		if ( $endpos === false ) {
			return '';
		}

		$sub = substr( $str, $pos, $endpos - $pos );
		
		if ( empty( $sub ) ) {
			return '';
		}

		return $sub;
	}

	/**
	 * Replace WooCommerce content with new product IDs
	 *
	 * @param string $content The content to process.
	 * @param array  $product_ids Array of product IDs.
	 * @return string
	 */
	public static function replace_donation_content( $content, $give_form_id ) {
		if ( empty( $content ) ) {
			return $content;
		}
		if ( empty( $give_form_id ) ) {
			return $content;
		}

		// <!-- wp:give/donation-form {"id":19270,"blockId":"edd01b96-be54-4524-83db-3493dcc6af78"} /-->
		$content = str_replace(
			'<!-- wp:give/donation-form {"id":19270,',
			'<!-- wp:give/donation-form {"id":' . $give_form_id . ',',
			$content
		);
		
		return $content;
	}
} 