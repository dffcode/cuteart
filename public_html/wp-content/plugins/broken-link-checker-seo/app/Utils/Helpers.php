<?php
namespace AIOSEO\BrokenLinkChecker\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Traits\Helpers as TraitHelpers;

/**
 * Contains helper functions
 *
 * @since 1.0.0
 */
class Helpers {
	use TraitHelpers\Api;
	use TraitHelpers\Arrays;
	use TraitHelpers\Constants;
	use TraitHelpers\DateTime;
	use TraitHelpers\Strings;
	use TraitHelpers\ThirdParty;
	use TraitHelpers\Vue;
	use TraitHelpers\Wp;
	use TraitHelpers\WpContext;
	use TraitHelpers\WpMultisite;
	use TraitHelpers\WpUri;

	/**
	 * Checks if we are in a dev environment or not.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if we are, false if not.
	 */
	public function isDev() {
		return aioseoBrokenLinkChecker()->isDev || isset( $_REQUEST['aioseo-dev'] ); // phpcs:ignore HM.Security.NonceVerification.Recommended
	}

	/**
	 * Applies wp_kses_post on the given string, but also allows some other tags we support.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $string The string.
	 * @return string         The sanitized string.
	 */
	public function wpKsesPhrase( $string ) {
		$allowedHtmlTags = wp_kses_allowed_html( 'post' );

		$customTags = [
			'ta' => [
				'linkid' => [],
				'href'   => []
			]
		];

		$allowedHtmlTags = array_merge( $allowedHtmlTags, $customTags );

		return wp_kses( $string, $allowedHtmlTags );
	}

	/**
	 * Returns the scannable post types.
	 *
	 * @since 1.0.0
	 *
	 * @return array The scannable post types.
	 */
	public function getScannablePostTypes() {
		static $scannablePostTypes = null;
		if ( null !== $scannablePostTypes ) {
			return $scannablePostTypes;
		}

		// We exclude these post types to optimize performance.
		$nonSupportedPostTypes = [ 'attachment' ];
		$scannablePostTypes    = array_diff(
			$this->getPublicPostTypes( true ),
			$nonSupportedPostTypes
		);

		return $scannablePostTypes;
	}

	/**
	 * Returns the time that elapsed since the initial call to this function.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null The time that has elapsed.
	 */
	public function timeElapsed() {
		static $last = null;

		$now    = microtime( true );
		$return = null !== $last ? $now - $last : null;

		if ( null === $last ) {
			$last = $now;
		}

		return $return;
	}

	/**
	 * Checks whether the current post can be scanned.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_Post $post The post object.
	 * @return bool           Whether the post is scannable.
	 */
	public function isScannablePost( $post ) {
		if ( ! is_object( $post ) ) {
			return false;
		}

		$postTypes = array_diff( $this->getPublicPostTypes( true ), [ 'attachment' ] );
		if ( ! in_array( $post->post_type, $postTypes, true ) ) {
			return false;
		}

		if ( ! aioseoBrokenLinkChecker()->helpers->isValidPost( $post, $this->getPublicPostStatuses( true ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the post title or a placeholder if there isn't one.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $postId The post ID.
	 * @return string         The post title.
	 */
	public function getPostTitle( $postId ) {
		static $titles = [];
		if ( isset( $titles[ $postId ] ) ) {
			return $titles[ $postId ];
		}

		$post  = get_post( $postId );
		$title = $post->post_title;
		$title = $title ? $title : __( '(no title)' ); // phpcs:ignore AIOSEO.Wp.I18n.MissingArgDomain

		$titles[ $postId ] = $this->decodeHtmlEntities( $title );

		return $titles[ $postId ];
	}


	/**
	 * Checks if the given post is excluded from Broken Link Checker.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $postId The post ID.
	 * @return bool         Whether the post is excluded.
	 */
	public function isExcludedPost( $postId ) {
		$excludedPostIds      = $this->getExcludedPostIds();
		$includedPostTypes    = $this->getIncludedPostTypes();
		// We include auto-drafts here because all new posts are otherwise excluded before they are saved.
		$includedPostStatuses = array_merge( $this->getIncludedPostStatuses(), [ 'auto-draft' ] );
		$post                 = get_post( $postId );

		return in_array( (int) $postId, $excludedPostIds, true ) ||
			! in_array( $post->post_type, $includedPostTypes, true ) ||
			! in_array( $post->post_status, $includedPostStatuses, true );
	}

	/**
	 * Returns the IDs of posts that are excluded from Broken Link Checker.
	 *
	 * @since 1.0.0
	 *
	 * @return array The post IDs.
	 */
	public function getExcludedPostIds() {
		static $excludedPostIds = null;
		if ( null === $excludedPostIds ) {
			if ( ! aioseoBrokenLinkChecker()->options->advanced->enable ) {
				$excludedPostIds = [];

				return $excludedPostIds;
			}

			$excludedPostIds = [];
			$excludedPosts   = aioseoBrokenLinkChecker()->options->advanced->excludePosts;
			foreach ( $excludedPosts as $excludedPost ) {
				$excludedPost = json_decode( $excludedPost );
				if ( ! empty( $excludedPost->value ) ) {
					$excludedPostIds[] = $excludedPost->value;
				}
			}
		}

		return $excludedPostIds;
	}

	/**
	 * Returns the post types that Broken Link Checker is enabled for.
	 *
	 * @since 1.0.0
	 *
	 * @return array The included post types.
	 */
	public function getIncludedPostTypes() {
		static $includedPostTypes = null;
		if ( null !== $includedPostTypes ) {
			return $includedPostTypes;
		}

		$includedPostTypes = [];
		$postTypes         = aioseoBrokenLinkChecker()->options->advanced->postTypes->all();
		if ( ! aioseoBrokenLinkChecker()->options->advanced->enable || ! empty( $postTypes['all'] ) ) {
			$includedPostTypes = $this->getScannablePostTypes();
		} else {
			// Determine the intersection to make sure that we only consider post types that are currently registered.
			$includedPostTypes = array_intersect(
				$postTypes['included'],
				$this->getScannablePostTypes()
			);
		}

		foreach ( $includedPostTypes as $k => $postType ) {
			if ( ! $this->canEditPostType( $postType ) ) {
				unset( $includedPostTypes[ $k ] );
			}
		}

		return $includedPostTypes;
	}

	/**
	 * Returns the post statuses that Broken Link Checker is enabled for.
	 *
	 * @since 1.0.0
	 *
	 * @return array The included post statuses.
	 */
	public function getIncludedPostStatuses() {
		static $includedPostStatuses = null;
		if ( null !== $includedPostStatuses ) {
			return $includedPostStatuses;
		}

		$includedPostStatuses = [];
		$postStatuses         = aioseoBrokenLinkChecker()->options->advanced->postStatuses->all();
		if ( ! aioseoBrokenLinkChecker()->options->advanced->enable || ! empty( $postStatuses['all'] ) ) {
			$includedPostStatuses = $this->getPublicPostStatuses( true );
		} else {
			// Determine the intersection to make sure that we only consider post statuses that are currently registered.
			$includedPostStatuses = array_intersect(
				$postStatuses['included'],
				$this->getPublicPostStatuses( true )
			);
		}

		return $includedPostStatuses;
	}

	/**
	 * Generates a UTM URL from the URL and medium/content that are passed in.
	 *
	 * @since 1.0.0
	 *
	 * @param  string      $url     The URL to parse.
	 * @param  string      $medium  The UTM medium parameter.
	 * @param  string|null $content The UTM content parameter or null.
	 * @param  boolean     $esc     Whether or not to escape the URL.
	 * @return string               The new URL.
	 */
	public function utmUrl( $url, $medium, $content = null, $esc = true ) {
		// First, remove any existing utm parameters on the URL.
		$url = remove_query_arg( [
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_content'
		], $url );

		// Generate the new arguments.
		$args = [
			'utm_source'   => 'WordPress',
			'utm_campaign' => 'plugin',
			'utm_medium'   => $medium
		];

		// Content is not used by default.
		if ( $content ) {
			$args['utm_content'] = $content;
		}

		// Return the new URL.
		$url = add_query_arg( $args, $url );

		return $esc ? esc_url( $url ) : $url;
	}

	/**
	 * Returns the excluded domains.
	 *
	 * @since 1.1.1
	 *
	 * @return array The excluded domains.
	 */
	public function getExcludedDomains() {
		if ( ! aioseoBrokenLinkChecker()->options->advanced->enable ) {
			return [];
		}

		$excludedDomains = aioseoBrokenLinkChecker()->options->advanced->excludeDomains;
		if ( ! is_string( $excludedDomains ) ) {
			return [];
		}

		$pattern = '/([\.?!][\r\n\s]+|\r|\n|\s{2,})/u';

		return array_map( 'trim', preg_split( $pattern, (string) $excludedDomains, -1, PREG_SPLIT_NO_EMPTY ) );
	}

	/**
	 * Checks if the given string is serialized, and if so, unserializes it.
	 * If the serialized string contains an object, we abort to prevent PHP object injection.
	 *
	 * @since 1.2.0
	 *
	 * @param  string       $string The string.
	 * @return string|array         The string or unserialized data.
	 */
	public function maybeUnserialize( $string ) {
		if ( ! is_string( $string ) ) {
			return $string;
		}

		$string = trim( $string );
		if ( is_serialized( $string ) && ! $this->stringContains( $string, 'O:' ) ) {
			// We want to add extra hardening for PHP versions greater than 5.6.
			return version_compare( PHP_VERSION, '7.0', '<' )
				? @unserialize( $string )
				: @unserialize( $string, [ 'allowed_classes' => false ] ); // phpcs:disable PHPCompatibility.FunctionUse.NewFunctionParameters.unserialize_optionsFound
		}

		return $string;
	}

	/**
	 * Returns user roles in the current WP install.
	 *
	 * @since 1.2.4
	 *
	 * @return array An array of user roles.
	 */
	public function getUserRoles() {
		global $wp_roles; // phpcs:ignore Squiz.NamingConventions.ValidVariableName

		$wpRoles = $wp_roles; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		if ( ! is_object( $wpRoles ) ) {
			// Don't assign this to the global because otherwise WordPress won't override it.
			$wpRoles = new \WP_Roles();
		}

		$roleNames = $wpRoles->get_names();
		asort( $roleNames );

		return $roleNames;
	}

	/**
	 * Check if the current request is uninstalling (deleting) Broken Link Checker.
	 *
	 * @since {Pnext}
	 *
	 * @return bool Whether Broken Link Checker is being uninstalled/deleted or not.
	 */
	public function isUninstalling() {
		if (
			defined( 'AIOSEO_BROKEN_LINK_CHECKER_FILE' ) &&
			defined( 'WP_UNINSTALL_PLUGIN' )
		) {
			// Make sure `plugin_basename()` exists.
			include_once ABSPATH . 'wp-admin/includes/plugin.php';

			return WP_UNINSTALL_PLUGIN === plugin_basename( AIOSEO_BROKEN_LINK_CHECKER_FILE );
		}

		return false;
	}
}