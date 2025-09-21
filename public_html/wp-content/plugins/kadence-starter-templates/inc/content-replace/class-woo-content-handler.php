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
class Woo_Content_Handler {

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
	public static function replace_woo_content( $content, $product_ids ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$products = ! empty( $product_ids ) ? $product_ids : '';
		$four_products = $products ? implode( ',', array_slice( $product_ids, 0, 4 ) ) : '';
		$three_products = $products ? implode( ',', array_slice( $product_ids, 0, 3 ) ) : '';

		// Replace three product block.
		$product_content = self::get_string_between(
			$content,
			'<!-- wp:woocommerce/handpicked-products',
			'"products":[8121,5160,5159]',
			0
		);

		if ( $product_content ) {
			$content = str_replace(
				'<!-- wp:woocommerce/handpicked-products' . $product_content . '"products":[8121,5160,5159]',
				'<!-- wp:woocommerce/handpicked-products' . $product_content . '"products":[' . $three_products . ']',
				$content
			);
		}

		// Replace four product block.
		$four_product_content = self::get_string_between(
			$content,
			'<!-- wp:woocommerce/handpicked-products',
			'"products":[8121,5159,5160,7737]',
			0
		);

		if ( $four_product_content ) {
			$content = str_replace(
				'<!-- wp:woocommerce/handpicked-products' . $four_product_content . '"products":[8121,5159,5160,7737]',
				'<!-- wp:woocommerce/handpicked-products' . $four_product_content . '"products":[' . $four_products . ']',
				$content
			);
		}

		return $content;
	}
} 