<?php
namespace AIOSEO\BrokenLinkChecker\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the extraction of the context paragraph from the post content.
 *
 * @since 1.0.0
 */
class Paragraph {
	/**
	 * Returns the context paragraph for the given phrase.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $postId      The post ID.
	 * @param  string $postContent The post content.
	 * @param  string $phrase      The phrase.
	 * @return string              The context paragraph.
	 */
	public function get( $postId, $postContent, $phrase ) {
		static $cachedPhrases = [];
		if ( ! isset( $cachedPhrases[ $postId ] ) ) {
			$postContent              = wp_strip_all_tags( $postContent );
			$cachedPhrases[ $postId ] = array_values( preg_split( '#([\.?!][\r\n\s]+|\r|\n|\s{2,})#u', (string) $postContent, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) );
		}
		$phrases = $cachedPhrases[ $postId ];

		// Locate phrase in list of phrases and use preceding/consecutive phrase for context.
		$paragraph = $phrase;
		for ( $i = 0; $i < count( $phrases ); $i++ ) {
			$escapedPhrase = aioseoBrokenLinkChecker()->helpers->escapeRegex( $phrase );
			if (
				! preg_match( "/{$escapedPhrase}/i", $phrases[ $i ] ) &&
				// Do another check and include the delimiter.
				( ! isset( $phrases[ $i + 1 ] ) || 1 < str_word_count( $phrases[ $i + 1 ] ) || ! preg_match( "/{$escapedPhrase}/i", $phrases[ $i ] . $phrases[ $i + 1 ] ) )
			) {
				continue;
			}

			// Now we'll use preceding/consecutive phrases, relative to the phrase, to get the context.
			// The odd indexes are the delimiters (punctuation).
			// We need to validate each phrase part to prevent us from including line breaks.
			// When constructing the paragraph, we cannot use the phrase we passed in because it might have punctuation at the end.

			// If phrase is the first phrase of the content, add two consecutive phrases.
			if ( 0 === $i ) {
				if (
					isset( $phrases[1] ) && $this->isValidPhrase( $phrases[1] ) &&
					isset( $phrases[2] ) && $this->isValidPhrase( $phrases[2] )
				) {
					$paragraph = $phrases[ $i ] . $phrases[1] . $phrases[2];
					if ( isset( $phrases[3] ) ) {
						$paragraph .= $phrases[3];
					}

					if (
						isset( $phrases[4] ) && $this->isValidPhrase( $phrases[4] ) &&
						isset( $phrases[5] ) && $this->isValidPhrase( $phrases[5] )
					) {
						$paragraph .= $phrases[4] . $phrases[5];
					} elseif ( isset( $phrases[4] ) ) {
						// If we find a line break, we still want to add the delimiter.
						$paragraph .= $phrases[4];
					}
				} elseif ( isset( $phrases[1] ) ) {
					// If we find a line break, we still want to add the delimiter.
					$paragraph = $phrases[ $i ] . $phrases[1];
				}
				break;
			}

			// If phrase is the last phrase of the content, add two preceding phrases.
			if ( ( count( $phrases ) - 1 ) === $i ) {
				if (
					isset( $phrases[ $i - 1 ] ) && $this->isValidPhrase( $phrases[ $i - 1 ] ) &&
					isset( $phrases[ $i - 2 ] ) && $this->isValidPhrase( $phrases[ $i - 2 ] )
				) {
					$paragraph = $phrases[ $i - 2 ] . $phrases[ $i - 1 ] . $phrases[ $i ];

					if (
						isset( $phrases[ $i - 3 ] ) && $this->isValidPhrase( $phrases[ $i - 3 ] ) &&
						isset( $phrases[ $i - 4 ] ) && $this->isValidPhrase( $phrases[ $i - 4 ] )
					) {
						$paragraph = $phrases[ $i - 4 ] . $phrases[ $i - 3 ] . $paragraph;
					}
				}
				break;
			}

			$addedPrecedingSentence = false;
			if (
				isset( $phrases[ $i - 1 ] ) && $this->isValidPhrase( $phrases[ $i - 1 ] ) &&
				isset( $phrases[ $i - 2 ] ) && $this->isValidPhrase( $phrases[ $i - 2 ] )
				) {
				$addedPrecedingSentence = true;
				$paragraph = $phrases[ $i - 2 ] . $phrases[ $i - 1 ] . $phrases[ $i ];
			}

			if (
				isset( $phrases[ $i + 1 ] ) && $this->isValidPhrase( $phrases[ $i + 1 ] ) &&
				isset( $phrases[ $i + 2 ] ) && $this->isValidPhrase( $phrases[ $i + 2 ] )
			) {
				$paragraph = $addedPrecedingSentence ? $paragraph : $phrases[ $i ];
				$paragraph = $paragraph . $phrases[ $i + 1 ] . $phrases[ $i + 2 ];
				if ( isset( $phrases[ $i + 3 ] ) ) {
					$paragraph .= $phrases[ $i + 3 ];
				}
			} elseif ( isset( $phrases[ $i + 1 ] ) ) {
				// If we find a line break, we still want to add the delimiter.
				if ( ! $addedPrecedingSentence ) {
					$paragraph = $phrases[ $i ];
				}
				$paragraph .= $phrases[ $i + 1 ];
			}
			break;
		}

		return trim( $paragraph );
	}

	/**
	 * Returns the paragraph with its inner HTML contents and preceding/trailing tags.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $anchor       The anchor.
	 * @param  string $paragraph    The paragraph.
	 * @param  string $postContent  The post content.
	 * @param  bool   $isSuggestion Whether we're getting the HTML paragraph for a suggestion.
	 * @return string               The paragraph with its inner HTML contents.
	 */
	public function getHtml( $anchor, $paragraph, $postContent, $isSuggestion = false ) {
		$words = preg_split( '/\s|\p{P}/', (string) $paragraph, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! isset( $words[0] ) ) {
			return $paragraph;
		}

		$firstWord = aioseoBrokenLinkChecker()->helpers->escapeRegex( $words[0] );
		$lastWord  = aioseoBrokenLinkChecker()->helpers->escapeRegex( $words[ count( $words ) - 1 ] );

		// We must check if the first/last word isn't part of the anchor. Otherwise we'll mess up the pattern below by including the word twice.
		$firstWord = ! preg_match( "/^{$firstWord}/i", (string) $anchor ) ? $firstWord : '';
		$lastWord  = ! preg_match( "/{$lastWord}$/i", (string) $anchor ) ? $lastWord : '';
		$anchor    = aioseoBrokenLinkChecker()->helpers->escapeRegex( $anchor );
		$pattern   = $isSuggestion
			? "/{$firstWord}.*{$anchor}.*{$lastWord}/i"
			: "/{$firstWord}.*<a[^<>]*>.*{$anchor}.*<\/a>.*{$lastWord}/i";

		preg_match( $pattern, (string) $postContent, $match );
		if ( ! isset( $match[0] ) ) {
			return $paragraph;
		}

		$paragraphWithInnerHtml        = $match[0];
		$escapedParagraphWithInnerHtml = aioseoBrokenLinkChecker()->helpers->escapeRegex( $paragraphWithInnerHtml );

		$precedingTags = '';
		preg_match( "/(<[a-z]* .*>|<[a-z]*>)+$escapedParagraphWithInnerHtml/i", (string) $postContent, $match );
		if ( ! empty( $match[0] ) ) {
			$precedingTags = preg_replace( "/$escapedParagraphWithInnerHtml/", '', $match[0] );
		}

		$trailingTags = '';
		preg_match( "/{$escapedParagraphWithInnerHtml}[.?!]?(<\/[a-z]*>)?/i", $postContent, $match );
		if ( ! empty( $match[0] ) ) {
			$trailingTags = preg_replace( "/$escapedParagraphWithInnerHtml/", '', $match[0] );
		}

		$paragraphHtml = $precedingTags . $paragraphWithInnerHtml . $trailingTags;

		$paragraphHtml = aioseoBrokenLinkChecker()->helpers->stripScriptTags( $paragraphHtml );
		$paragraphHtml = aioseoBrokenLinkChecker()->helpers->trimParagraphTags( $paragraphHtml );

		return $paragraphHtml;
	}

	/**
	 * Checks whether the phrase is valid. It cannot contain line breaks.
	 * We do this so that we can prevent phrases being added to the context paragraph that aren't part of the phrase's text block.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the phrase is valid.
	 */
	private function isValidPhrase( $phrase ) {
		return preg_match( '/(\r\n|\r|\n)/', $phrase );
	}
}