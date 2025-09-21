<?php
/**
 * Address content replacement functionality.
 *
 * @package StarterSite
 */

namespace KadenceWP\KadenceStarterTemplates\ContentReplace;

/**
 * Class Address_Replacer
 * Handles replacing placeholder addresses with actual addresses in content.
 */
class Address_Replacer {

	/**
	 * Replace placeholder addresses with the provided address.
	 *
	 * @param string $content The content to process.
	 * @param string $address The address to replace placeholders with.
	 * @return string The processed content.
	 */
	public static function replace_address_content( $content, $address ) {
		if ( empty( $content ) || empty( $address ) ) {
			return $content;
		}

		$replacements = array(
			"1234 N Street \nCity, State, Country" => $address,
			"1234 N Street \nCity, State\nCountry" => $address,
			'1234 N Street<br>City, State, Country' => $address,
			'1234 N Street <br>City, State, Country' => $address,
			'1234 N Street <br>City, State<br>Country' => $address,
			'1234 N Street <br>City, State <br>Country' => $address,
			'1234 N Street City, State, Country' => $address,
			'1234 N Street <br/>City, State, Country' => $address,
			'1234 N Street <br/>City, State, Country' => $address,
			'Los angeles' => $address,
			'Las angeles' => $address,
			'Los Angeles' => $address,
			'Las Angeles' => $address,
		);

		foreach ( $replacements as $search => $replace ) {
			$content = str_replace( $search, $replace, $content );
		}

		return $content;
	}
} 