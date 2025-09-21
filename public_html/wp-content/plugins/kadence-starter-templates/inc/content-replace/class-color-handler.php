<?php
/**
 * Color Replacement Handler
 *
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates\ContentReplace;

/**
 * Class for handling color replacements in content
 */
class Color_Handler {

	/**
	 * Get string between two strings
	 *
	 * @param string $str The string to search in.
	 * @param string $start The starting string.
	 * @param string $end The ending string.
	 * @param string $verify String to verify exists.
	 * @return string
	 */
	private static function get_string_between( $str, $start, $end, $verify ) {
		// Check if form is there?
		if ( strpos( $str, $verify ) === false ) {
			return '';
		}
		// Get the start position.
		$startpos = strpos( $str, $start );
		if ( false === $startpos ) {
			return '';
		}
		$pos = $startpos + strlen( $start );
		return substr( $str, $pos, strpos( $str, $end, $pos ) - $pos );
	}

	/**
	 * Get string between with verification and multiple occurrences
	 *
	 * @param string $str The string to search in.
	 * @param string $start The starting string.
	 * @param string $end The ending string.
	 * @param string $verify String to verify exists.
	 * @param int    $from Position to start searching from.
	 * @return string
	 */
	private static function get_string_between_when( $str, $start, $end, $verify, $from = 0 ) {
		// Check if form is there?
		if ( strpos( $str, $verify ) === false ) {
			return '';
		}
		// Get the start position.
		$startpos = strpos( $str, $start, $from );
		if ( false === $startpos ) {
			return '';
		}
		$pos = $startpos + strlen( $start );
		$endpos = strpos( $str, $end, $pos );
		$sub = substr( $str, $pos, $endpos - $pos );
		if ( empty( $sub ) ) {
			return '';
		}
		if ( strpos( $sub, $verify ) === false ) {
			return self::get_string_between_when( $str, $start, $end, $verify, $endpos + strlen( $end ) );
		}
		return $sub;
	}

	/**
	 * Replace colors in content
	 *
	 * @param string $content The content to process.
	 * @param string $style The style to apply (dark or highlight).
	 * @param string $btn_color The button color (default '#ffffff').
	 * @return string
	 */
	public static function replace_colors( $content, $style, $btn_color = '#ffffff' ) {
		if ( empty( $content ) || empty( $style ) ) {
			return $content;
		}

		if ( 'dark' !== $style && 'highlight' !== $style ) {
			return $content;
		}

		// Swap Logos.
		$logo_replacements = array(
			'Logo-ploaceholder.png' => 'Logo-ploaceholder-white.png',
			'Logo-ploaceholder-1.png' => 'Logo-ploaceholder-1-white.png',
			'Logo-ploaceholder-2.png' => 'Logo-ploaceholder-2-white.png',
			'Logo-ploaceholder-3.png' => 'Logo-ploaceholder-3-white.png',
			'Logo-ploaceholder-4.png' => 'Logo-ploaceholder-4-white.png',
			'Logo-ploaceholder-5.png' => 'Logo-ploaceholder-5-white.png',
			'Logo-ploaceholder-6.png' => 'Logo-ploaceholder-6-white.png',
			'Logo-ploaceholder-7.png' => 'Logo-ploaceholder-7-white.png',
			'Logo-ploaceholder-8.png' => 'Logo-ploaceholder-8-white.png',
			'logo-placeholder.png' => 'logo-placeholder-white.png',
			'logo-placeholder-1.png' => 'logo-placeholder-1-white.png',
			'logo-placeholder-2.png' => 'logo-placeholder-2-white.png',
			'logo-placeholder-3.png' => 'logo-placeholder-3-white.png',
			'logo-placeholder-4.png' => 'logo-placeholder-4-white.png',
			'logo-placeholder-5.png' => 'logo-placeholder-5-white.png',
			'logo-placeholder-6.png' => 'logo-placeholder-6-white.png',
			'logo-placeholder-7.png' => 'logo-placeholder-7-white.png',
			'logo-placeholder-8.png' => 'logo-placeholder-8-white.png',
			'logo-placeholder-9.png' => 'logo-placeholder-9-white.png',
			'logo-placeholder-10.png' => 'logo-placeholder-10-white.png',
		);

		foreach ( $logo_replacements as $from => $to ) {
			$content = str_replace( $from, $to, $content );
		}

		$replacements = array();

		if ( 'dark' === $style ) {
			// Handle tabs.
			$tab_content = self::get_string_between( $content, 'wp:kadence/tabs', 'wp:kadence/tab', 'kb-pattern-active-tab-highlight' );
			if ( $tab_content ) {
				$tab_content_org = $tab_content;
				$tab_content = str_replace( '"titleColorActive":"palette9"', '"titleColorActive":"ph-kb-pal9"', $tab_content );
				$tab_content = str_replace( '"titleColorHover":"palette9"', '"titleColorHover":"ph-kb-pal9"', $tab_content );
				$content = str_replace( $tab_content_org, $tab_content, $content );
			}

			// Special testimonial issue.
			$white_text_content = self::get_string_between_when( $content, '<!-- wp:kadence/column', 'kt-inside-inner-col', 'kb-pattern-light-color', 0 );
			if ( $white_text_content ) {
				$white_text_content_org = $white_text_content;
				$white_text_content = str_replace( '"textColor":"palette9"', '"textColor":"ph-kb-pal9"', $white_text_content );
				$white_text_content = str_replace( '"linkColor":"palette9"', '"linkColor":"ph-kb-pal9"', $white_text_content );
				$white_text_content = str_replace( '"linkHoverColor":"palette9"', '"linkHoverColor":"ph-kb-pal9"', $white_text_content );
				$content = str_replace( $white_text_content_org, $white_text_content, $content );
			}

			// Handle Dividers.
			$row_divider_content = self::get_string_between( $content, 'wp:kadence/rowlayout', 'wp:kadence/rowlayout', 'kb-divider-static' );
			if ( $row_divider_content ) {
				$row_divider_content_org = $row_divider_content;
				$row_divider_content = str_replace(
					array(
						'"bottomSepColor":"palette9"',
						'"bottomSepColor":"palette8"',
						'"topSepColor":"palette9"',
						'"topSepColor":"palette8"',
					),
					array(
						'"bottomSepColor":"ph-kb-pal9"',
						'"bottomSepColor":"ph-kb-pal8"',
						'"topSepColor":"ph-kb-pal9"',
						'"topSepColor":"ph-kb-pal8"',
					),
					$row_divider_content
				);
				$content = str_replace( $row_divider_content_org, $row_divider_content, $content );
			}

			// Dark style color mappings
			$replacements = array(
				'has-theme-palette-3' => 'ph-kb-class9',
				'has-theme-palette-4' => 'ph-kb-class8',
				'has-theme-palette-5' => 'ph-kb-class7',
				'has-theme-palette-6' => 'ph-kb-class7',
				'has-theme-palette-7' => 'ph-kb-class3',
				'has-theme-palette-8' => 'ph-kb-class3',
				'has-theme-palette-9' => 'ph-kb-class4',
				'theme-palette3' => 'ph-class-kb-pal9',
				'theme-palette4' => 'ph-class-kb-pal8',
				'theme-palette5' => 'ph-class-kb-pal7',
				'theme-palette6' => 'ph-class-kb-pal7',
				'theme-palette7' => 'ph-class-kb-pal3',
				'theme-palette8' => 'ph-class-kb-pal3',
				'theme-palette9' => 'ph-class-kb-pal4',
				'palette3' => 'ph-kb-pal9',
				'palette4' => 'ph-kb-pal8',
				'palette5' => 'ph-kb-pal7',
				'palette6' => 'ph-kb-pal7',
				'palette7' => 'ph-kb-pal3',
				'palette8' => 'ph-kb-pal3',
				'palette9' => 'ph-kb-pal4',
			);

		} elseif ( 'highlight' === $style ) {
			// Handle Forms.
			$form_content = self::get_string_between( $content, '"submit":[{', ']}', 'wp:kadence/form' );
			if ( $form_content ) {
				$form_content_org = $form_content;
				$form_content = str_replace(
					array(
						'"color":""',
						'"background":""',
						'"colorHover":""',
						'"backgroundHover":""',
					),
					array(
						'"color":"ph-kb-pal9"',
						'"background":"ph-kb-pal3"',
						'"colorHover":"ph-kb-pal9"',
						'"backgroundHover":"ph-kb-pal4"',
					),
					$form_content
				);
				$content = str_replace( $form_content_org, $form_content, $content );
			}

			// Handle Dividers.
			$row_divider_content = self::get_string_between( $content, 'wp:kadence/rowlayout', 'wp:kadence/rowlayout', 'kb-divider-static' );
			if ( $row_divider_content ) {
				$row_divider_content_org = $row_divider_content;
				$row_divider_content = str_replace(
					array(
						'"bottomSepColor":"palette9"',
						'"bottomSepColor":"palette8"',
						'"topSepColor":"palette9"',
						'"topSepColor":"palette8"',
					),
					array(
						'"bottomSepColor":"ph-kb-pal9"',
						'"bottomSepColor":"ph-kb-pal8"',
						'"topSepColor":"ph-kb-pal9"',
						'"topSepColor":"ph-kb-pal8"',
					),
					$row_divider_content
				);
				$content = str_replace( $row_divider_content_org, $row_divider_content, $content );
			}

			// Handle Buttons.
			$content = str_replace(
				'"inheritStyles":"inherit"',
				'"color":"ph-kb-pal9","background":"ph-kb-pal3","colorHover":"ph-kb-pal9","backgroundHover":"ph-kb-pal4","inheritStyles":"inherit"',
				$content
			);

			// Handle Outline Buttons.
			$outline_button_replacement = ( '#ffffff' === $btn_color ) ?
				'"color":"ph-kb-pal9","colorHover":"ph-kb-pal9","borderStyle":[{"top":["ph-kb-pal9","",""],"right":["ph-kb-pal9","",""],"bottom":["ph-kb-pal9","",""],"left":["ph-kb-pal9","",""],"unit":"px"}],"borderHoverStyle":[{"top":["ph-kb-pal9","",""],"right":["ph-kb-pal9","",""],"bottom":["ph-kb-pal9","",""],"left":["ph-kb-pal9","",""],"unit":"px"}],"inheritStyles":"outline"' :
				'"color":"ph-kb-pal3","colorHover":"ph-kb-pal4","borderStyle":[{"top":["ph-kb-pal3","",""],"right":["ph-kb-pal3","",""],"bottom":["ph-kb-pal3","",""],"left":["ph-kb-pal3","",""],"unit":"px"}],"borderHoverStyle":[{"top":["ph-kb-pal4","",""],"right":["ph-kb-pal4","",""],"bottom":["ph-kb-pal4","",""],"left":["ph-kb-pal4","",""],"unit":"px"}],"inheritStyles":"outline"';

			$content = str_replace( '"inheritStyles":"outline"', $outline_button_replacement, $content );

			// Highlight style color mappings
			$replacements = array(
				'has-theme-palette-1' => 'ph-kb-class9',
				'has-theme-palette-2' => 'ph-kb-class8',
				'has-theme-palette-3' => 'ph-kb-class9',
				'has-theme-palette-4' => 'ph-kb-class9',
				'has-theme-palette-5' => 'ph-kb-class8',
				'has-theme-palette-6' => 'ph-kb-class7',
				'has-theme-palette-7' => 'ph-kb-class2',
				'has-theme-palette-8' => 'ph-kb-class2',
				'has-theme-palette-9' => 'ph-kb-class1',
				'theme-palette1' => 'ph-class-kb-pal9',
				'theme-palette2' => 'ph-class-kb-pal8',
				'theme-palette3' => 'ph-class-kb-pal9',
				'theme-palette4' => 'ph-class-kb-pal9',
				'theme-palette5' => 'ph-class-kb-pal8',
				'theme-palette6' => 'ph-class-kb-pal8',
				'theme-palette7' => 'ph-class-kb-pal2',
				'theme-palette8' => 'ph-class-kb-pal2',
				'theme-palette9' => 'ph-class-kb-pal1',
				'palette1' => 'ph-kb-pal9',
				'palette2' => 'ph-kb-pal8',
				'palette3' => 'ph-kb-pal9',
				'palette4' => 'ph-kb-pal9',
				'palette5' => 'ph-kb-pal8',
				'palette6' => 'ph-kb-pal7',
				'palette7' => 'ph-kb-pal2',
				'palette8' => 'ph-kb-pal2',
				'palette9' => 'ph-kb-pal1',
			);
		}

		// Final placeholder replacements
		$final_replacements = array(
			'ph-kb-class1' => 'has-theme-palette-1',
			'ph-kb-class2' => 'has-theme-palette-2',
			'ph-kb-class3' => 'has-theme-palette-3',
			'ph-kb-class4' => 'has-theme-palette-4',
			'ph-kb-class5' => 'has-theme-palette-5',
			'ph-kb-class6' => 'has-theme-palette-6',
			'ph-kb-class7' => 'has-theme-palette-7',
			'ph-kb-class8' => 'has-theme-palette-8',
			'ph-kb-class9' => 'has-theme-palette-9',
			'ph-class-kb-pal1' => 'theme-palette1',
			'ph-class-kb-pal2' => 'theme-palette2',
			'ph-class-kb-pal3' => 'theme-palette3',
			'ph-class-kb-pal4' => 'theme-palette4',
			'ph-class-kb-pal5' => 'theme-palette5',
			'ph-class-kb-pal6' => 'theme-palette6',
			'ph-class-kb-pal7' => 'theme-palette7',
			'ph-class-kb-pal8' => 'theme-palette8',
			'ph-class-kb-pal9' => 'theme-palette9',
			'ph-kb-pal1' => 'palette1',
			'ph-kb-pal2' => 'palette2',
			'ph-kb-pal3' => 'palette3',
			'ph-kb-pal4' => 'palette4',
			'ph-kb-pal5' => 'palette5',
			'ph-kb-pal6' => 'palette6',
			'ph-kb-pal7' => 'palette7',
			'ph-kb-pal8' => 'palette8',
			'ph-kb-pal9' => 'palette9',
		);

		$replacements = array_merge( $replacements, $final_replacements );

		foreach ( $replacements as $from => $to ) {
			$content = str_replace( $from, $to, $content );
		}

		return $content;
	}

	/**
	 * Replace contrast colors
	 *
	 * @param string $content The content to process.
	 * @param string $style The style to apply (dark or highlight).
	 * @return string
	 */
	public static function replace_contrast_colors( $content, $style ) {
		if ( empty( $content ) ) {
			return $content;
		}
		if ( empty( $style ) ) {
			return $content;
		}
		if ( 'dark' !== $style && 'highlight' !== $style ) {
			return $content;
		}

		// Logo replacements
		$logo_replacements = array(
			'logo-placeholder.png' => 'logo-placeholder-white.png',
			'logo-placeholder-1.png' => 'logo-placeholder-1-white.png',
			'logo-placeholder-2.png' => 'logo-placeholder-2-white.png',
			'logo-placeholder-3.png' => 'logo-placeholder-3-white.png',
			'logo-placeholder-4.png' => 'logo-placeholder-4-white.png',
			'logo-placeholder-5.png' => 'logo-placeholder-5-white.png',
			'logo-placeholder-6.png' => 'logo-placeholder-6-white.png',
			'logo-placeholder-7.png' => 'logo-placeholder-7-white.png',
			'logo-placeholder-8.png' => 'logo-placeholder-8-white.png',
			'logo-placeholder-9.png' => 'logo-placeholder-9-white.png',
			'logo-placeholder-10.png' => 'logo-placeholder-10-white.png'
		);

		foreach ( $logo_replacements as $from => $to ) {
			$content = str_replace( $from, $to, $content );
		}

		// Handle tabs
		$tab_content = self::get_string_between( $content, 'wp:kadence/tabs', 'wp:kadence/tab', 'kb-pattern-active-tab-highlight' );
		if ( $tab_content ) {
			$tab_content_org = $tab_content;
			$tab_content = str_replace( '"titleColorActive":"palette9"', '"titleColorActive":"ph-kb-pal9"', $tab_content );
			$tab_content = str_replace( '"titleColorHover":"palette9"', '"titleColorHover":"ph-kb-pal9"', $tab_content );
			$content = str_replace( $tab_content_org, $tab_content, $content );
		}

		// Special testimonial issue
		$white_text_content = self::get_string_between_when( $content, '<!-- wp:kadence/column', 'kt-inside-inner-col', 'kb-pattern-light-color', 0 );
		if ( $white_text_content ) {
			$white_text_content_org = $white_text_content;
			$white_text_content = str_replace( '"textColor":"palette9"', '"textColor":"ph-kb-pal9"', $white_text_content );
			$white_text_content = str_replace( '"linkColor":"palette9"', '"linkColor":"ph-kb-pal9"', $white_text_content );
			$white_text_content = str_replace( '"linkHoverColor":"palette9"', '"linkHoverColor":"ph-kb-pal9"', $white_text_content );
			$content = str_replace( $white_text_content_org, $white_text_content, $content );
		}

		// Color Map Switch replacements
		$replacements = array(
			'has-theme-palette-3' => 'ph-kb-class9',
			'has-theme-palette-4' => 'ph-kb-class8',
			'has-theme-palette-5' => 'ph-kb-class7',
			'has-theme-palette-6' => 'ph-kb-class7',
			'has-theme-palette-7' => 'ph-kb-class3',
			'has-theme-palette-8' => 'ph-kb-class3',
			'has-theme-palette-9' => 'ph-kb-class4',
			'theme-palette3' => 'ph-class-kb-pal9',
			'theme-palette4' => 'ph-class-kb-pal8',
			'theme-palette5' => 'ph-class-kb-pal7',
			'theme-palette6' => 'ph-class-kb-pal7',
			'theme-palette7' => 'ph-class-kb-pal3',
			'theme-palette8' => 'ph-class-kb-pal3',
			'theme-palette9' => 'ph-class-kb-pal4',
			'palette3' => 'ph-kb-pal9',
			'palette4' => 'ph-kb-pal8',
			'palette5' => 'ph-kb-pal7',
			'palette6' => 'ph-kb-pal7',
			'palette7' => 'ph-kb-pal3',
			'palette8' => 'ph-kb-pal3',
			'palette9' => 'ph-kb-pal4'
		);

		// Final placeholder replacements
		$final_replacements = array(
			'ph-kb-class1' => 'has-theme-palette-1',
			'ph-kb-class2' => 'has-theme-palette-2',
			'ph-kb-class3' => 'has-theme-palette-3',
			'ph-kb-class4' => 'has-theme-palette-4',
			'ph-kb-class5' => 'has-theme-palette-5',
			'ph-kb-class6' => 'has-theme-palette-6',
			'ph-kb-class7' => 'has-theme-palette-7',
			'ph-kb-class8' => 'has-theme-palette-8',
			'ph-kb-class9' => 'has-theme-palette-9',
			'ph-class-kb-pal1' => 'theme-palette1',
			'ph-class-kb-pal2' => 'theme-palette2',
			'ph-class-kb-pal3' => 'theme-palette3',
			'ph-class-kb-pal4' => 'theme-palette4',
			'ph-class-kb-pal5' => 'theme-palette5',
			'ph-class-kb-pal6' => 'theme-palette6',
			'ph-class-kb-pal7' => 'theme-palette7',
			'ph-class-kb-pal8' => 'theme-palette8',
			'ph-class-kb-pal9' => 'theme-palette9',
			'ph-kb-pal1' => 'palette1',
			'ph-kb-pal2' => 'palette2',
			'ph-kb-pal3' => 'palette3',
			'ph-kb-pal4' => 'palette4',
			'ph-kb-pal5' => 'palette5',
			'ph-kb-pal6' => 'palette6',
			'ph-kb-pal7' => 'palette7',
			'ph-kb-pal8' => 'palette8',
			'ph-kb-pal9' => 'palette9'
		);

		$replacements = array_merge( $replacements, $final_replacements );

		foreach ( $replacements as $from => $to ) {
			$content = str_replace( $from, $to, $content );
		}

		return $content;
	}
	/**
	 * Replace logo farm colors
	 *
	 * @param string $content The content to process.
	 * @param string $style The style to apply (dark or highlight).
	 * @return string
	 */
	public static function replace_logo_farm_colors( $content, $style ) {
		if ( empty( $content ) ) {
			return $content;
		}
		if ( empty( $style ) ) {
			return $content;
		}
		if ( 'dark' !== $style && 'highlight' !== $style ) {
			return $content;
		}	
		$logo_replacements = array(
			'logo-placeholder.png' => 'logo-placeholder-white.png',
			'logo-placeholder-1.png' => 'logo-placeholder-1-white.png',
			'logo-placeholder-2.png' => 'logo-placeholder-2-white.png',
			'logo-placeholder-3.png' => 'logo-placeholder-3-white.png',
			'logo-placeholder-4.png' => 'logo-placeholder-4-white.png',
			'logo-placeholder-5.png' => 'logo-placeholder-5-white.png',
			'logo-placeholder-6.png' => 'logo-placeholder-6-white.png',
			'logo-placeholder-7.png' => 'logo-placeholder-7-white.png',
			'logo-placeholder-8.png' => 'logo-placeholder-8-white.png',
			'logo-placeholder-9.png' => 'logo-placeholder-9-white.png',
			'logo-placeholder-10.png' => 'logo-placeholder-10-white.png'
		);
		foreach ( $logo_replacements as $from => $to ) {
			$content = str_replace( $from, $to, $content );
		}
		return $content;
	}
	
} 