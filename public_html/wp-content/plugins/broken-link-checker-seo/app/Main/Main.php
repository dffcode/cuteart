<?php
namespace AIOSEO\BrokenLinkChecker\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Highlighter;
use AIOSEO\BrokenLinkChecker\Links;
use AIOSEO\BrokenLinkChecker\LinkStatus;
use AIOSEO\BrokenLinkChecker\Models;

/**
 * Main class where core features are handled/registered.
 *
 * @since 1.0.0
 */
class Main {
	/**
	 * Paragraph class.
	 *
	 * @since 1.0.0
	 *
	 * @var Paragraph
	 */
	public $paragraph = null;

	/**
	 * Links class.
	 *
	 * @since 1.0.0
	 *
	 * @var Links\Links
	 */
	public $links = null;

	/**
	 * LinkStatus class.
	 *
	 * @since 1.0.0
	 *
	 * @var LinkStatus\LinkStatus
	 */
	public $linkStatus = null;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if (
			! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_links' ) ||
			! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_link_status' ) ||
			! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_cache' )
		) {
			aioseoBrokenLinkChecker()->updates->addInitialTables();

			// Don't return here; otherwise the Setup Wizard won't show on the first activation, but on the second.
		}

		new Activate();

		$this->paragraph  = new Paragraph();
		$this->links      = new Links\Links();
		$this->linkStatus = new LinkStatus\LinkStatus();

		add_filter( 'the_content', [ $this, 'filterLinks' ], 999 ); // High prio to make sure other plugins get a chance to render their content, parse their blocks, etc..
	}

	/**
	 * Filters links in the post content.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $postContent The post content.
	 * @return string              The post content.
	 */
	public function filterLinks( $postContent ) {
		if ( aioseoBrokenLinkChecker()->options->general->linkTweaks->nofollowBroken ) {
			$postContent = $this->nofollowBrokenLinks( $postContent );
		}

		return $postContent;
	}

	/**
	 * Adds rel="nofollow" to links in the post content that we know are broken.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $postContent The post content.
	 * @return string              The post content.
	 */
	private function nofollowBrokenLinks( $postContent ) {
		// First, capture all link tags.
		preg_match_all( '/<a.*href="(.*?").*>(.*?)<\/a>/i', (string) $postContent, $linkTags );

		if ( empty( $linkTags[0] ) ) {
			return $postContent;
		}

		foreach ( $linkTags[0] as $linkTag ) {
			preg_match( '/href="(.*?)"/i', (string) $linkTag, $url );
			if ( empty( $url[1] ) ) {
				continue;
			}

			// Now check if we've indexed the link. If so, check if it's broken and act accordingly.
			$linkStatus = Models\LinkStatus::getByUrl( $url[1] );
			if ( ! $linkStatus->exists() || ! $linkStatus->broken ) {
				continue;
			}

			preg_match( '/rel="(.*?)"/i', (string) $linkTag, $relAttributes );
			if ( ! empty( $relAttributes[0] ) ) {
				$relAttributes = explode( ' ', $relAttributes[1] );
				if ( ! in_array( 'nofollow', $relAttributes, true ) ) {
					$relAttributes[] = 'nofollow';
				}
				$relAttributes = implode( ' ', $relAttributes );
			} else {
				$relAttributes = 'nofollow';
			}

			$newLinkTag = $this->insertAttribute( $linkTag, 'rel', $relAttributes );

			$oldLinkTag = aioseoBrokenLinkChecker()->helpers->escapeRegex( $linkTag );
			$newLinkTag = aioseoBrokenLinkChecker()->helpers->escapeRegexReplacement( $newLinkTag );

			$postContent = preg_replace( "/{$oldLinkTag}/i", $newLinkTag, (string) $postContent );
		}

		return $postContent;
	}

	/**
	 * Inserts a given value for a given image attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $linkTag       The HTML tag.
	 * @param  string $attributeName The attribute name.
	 * @param  string $value         The attribute value.
	 * @return array                 The modified attributes.
	 */
	private function insertAttribute( $linkTag, $attributeName, $value ) {
		if ( empty( $value ) ) {
			return $linkTag;
		}

		$value   = esc_attr( $value );
		$linkTag = preg_replace( $this->attributeRegex( $attributeName, true ), '${1}' . $value . '${2}', (string) $linkTag, 1, $count );

		// Attribute does not exist. Let's append it at the beginning of the tag.
		if ( ! $count ) {
			$linkTag = preg_replace( '/<a /', '<a ' . $this->attributeToHtml( $attributeName, $value ) . ' ', (string) $linkTag );
		}

		return $linkTag;
	}

	/**
	 * Returns a regex string to match an attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $attributeName      The attribute name.
	 * @param  bool   $groupReplaceValue  Regex groupings without the value.
	 * @return string                     The regex string.
	 */
	private function attributeRegex( $attributeName, $groupReplaceValue = false ) {
		$regex = $groupReplaceValue ? "/(\s%s=['\"]).*?(['\"])/" : "/\s%s=['\"](.*?)['\"]/";

		return sprintf( $regex, trim( $attributeName ) );
	}

	/**
	 * Returns an attribute as HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $attributeName The attribute name.
	 * @param  string $value         The attribute value.
	 * @return string                The HTML formatted attribute.
	 */
	private function attributeToHtml( $attributeName, $value ) {
		return sprintf( '%s="%s"', $attributeName, esc_attr( $value ) );
	}
}