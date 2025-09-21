<?php
/**
 * Content Removal Handler
 *
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates\ContentReplace;

/**
 * Content Remover Class
 */
class Content_Remover {

	/**
	 * Get string between two strings
	 *
	 * @param string $str The string to search in.
	 * @param string $start The start string.
	 * @param string $end The end string.
	 * @param int    $from The position to start searching from.
	 * @return string
	 */
	private static function get_string_between( $str, $start, $end, $from = 0 ) {
		// Check if form is there?
		if ( strpos( $str, 'kb-pattern-delete-block' ) === false ) {
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

		if ( strpos( $sub, 'kb-pattern-delete-block' ) === false ) {
			return self::get_string_between( $str, $start, $end, $endpos + strlen( $end ) );
		}

		return $sub;
	}

	/**
	 * Remove content from string
	 *
	 * @param string $content The content to remove from.
	 * @return string
	 */
	public static function remove_content( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$remove_content = self::get_string_between( $content, '<!-- wp:kadence/column', '<!-- /wp:kadence/column -->', 0 );
		
		if ( ! empty( $remove_content ) ) {
			$content = str_replace( 
				'<!-- wp:kadence/column' . $remove_content . '<!-- /wp:kadence/column -->', 
				'', 
				$content 
			);
		}

		return $content;
	}
} 