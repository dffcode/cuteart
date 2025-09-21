<?php
namespace AIOSEO\BrokenLinkChecker\Links;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles the extraction, parsing and storage of links for the links scan.
 *
 * @since 1.0.0
 */
class Data {
	/**
	 * The ignored extensions.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $ignoredExtensions = [];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setIgnoredExtensions();
	}

	/**
	 * Indexes the links in the given post.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $postId The post ID.
	 * @return void
	 */
	public function indexLinks( $postId ) {
		$post = get_post( $postId );
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		// Delete all links first. We have to do this in order to remove old links that no longer exist.
		Models\Link::deleteLinks( $postId );

		$links = $this->extractLinks( $postId, $post->post_content );
		if ( empty( $links ) ) {
			return;
		}

		$this->storeLinks( $links );
	}

	/**
	 * Stores the given links to the DB.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $links The links.
	 * @return void
	 */
	private function storeLinks( $links ) {
		$urls         = [];
		$insertValues = [];
		$currentDate  = gmdate( 'Y-m-d H:i:s' );
		foreach ( $links as $linkData ) {
			$data = Models\Link::sanitizeLink( $linkData );
			if ( empty( $data ) ) {
				continue;
			}

			if ( ! Models\Link::validateLink( $data ) ) {
				continue;
			}

			$urls[ $data['url_hash'] ] = $data['url'];

			$blcLinkStatusId = '%d';
			if ( empty( $data['blc_link_status_id'] ) ) {
				$blcLinkStatusId           = '%s';
				$data['blc_link_status_id'] = 'null';
			}

			$insertValues[] = vsprintf(
				"(%d, $blcLinkStatusId, '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '$currentDate', '$currentDate')",
				$data
			);
		}

		$implodedInsertValues = implode( ',', $insertValues );

		$tableName = aioseoBrokenLinkChecker()->core->db->prefix . 'aioseo_blc_links';
		aioseoBrokenLinkChecker()->core->db->execute(
			"INSERT INTO $tableName
			(`post_id`, `blc_link_status_id`, `url`, `url_hash`, `hostname`, `hostname_url`, `external`, `anchor`, `phrase`, `phrase_html`, `paragraph`, `paragraph_html`, `created`, `updated`)
			VALUES $implodedInsertValues"
		);

		$existing = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status' )
			->select( 'url_hash' )
			->whereIn( 'url_hash', array_keys( $urls ) )
			->run()
			->result();

		foreach ( $existing as $row ) {
			unset( $urls[ $row->url_hash ] );
		}

		if ( empty( $urls ) ) {
			return;
		}

		foreach ( $urls as $hash => $url ) {
			$statusId = aioseoBrokenLinkChecker()->core->db->insert( 'aioseo_blc_link_status' )
				->set( [
					'url'      => $url,
					'url_hash' => $hash,
					'created'  => aioseoBrokenLinkChecker()->helpers->timeToMysql( time() ),
					'updated'  => aioseoBrokenLinkChecker()->helpers->timeToMysql( time() )
				] )
				->run()
				->insertId();

			aioseoBrokenLinkChecker()->core->db->update( 'aioseo_blc_links' )
				->where( 'url', $url )
				->set( [
					'blc_link_status_id' => $statusId
				] )
				->run();
		}
	}

	/**
	 * Returns the links that are in the post content.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $postId      The post ID.
	 * @param  string $postContent The post content.
	 * @return array               The links.
	 */
	private function extractLinks( $postId, $postContent ) {
		$postContent = aioseoBrokenLinkChecker()->helpers->decodeHtmlEntities( $postContent );

		// Strip data URIs to prevent catastrophic backtracking.
		$postContent = preg_replace( '/data:[^;]+;base64,[^"]+/', '', (string) $postContent );

		/**
		 * Regex pattern divided into groups:
		 * 0  - Full phrase with link tag.
		 * 2  - Start of the phrase, before the anchor.
		 * 4  - The URL.
		 * 6  - The anchor.
		 * 9  - The end of the phrase, after the anchor.
		 * 10 - The ending punctuation mark.
		 */
		preg_match_all(
			'/(([^\r\n.?!]*)<t?a[^>]*?href=(\"|\')(?!tel:|mailto:)([^\"\']*?)(\"|\')[^>]*?>([\s\w\W]*?)<\/t?a>|<!-- wp:core-embed\/wordpress {"url":"([^"]*?)"[^}]*?"} -->|(?:>|&nbsp;|\s)((?:(?:http|ftp|https)\:\/\/)(?:[\w_-]+(?:(?:\.[\w_-]+)+))(?:[\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-]))(?:<|&nbsp;|\s))([^<>.?!\r\n]*)([.?!]?)/i', // phpcs:disable Generic.Files.LineLength.MaxExceeded
			(string) $postContent,
			$matches
		);

		if ( empty( $matches[0] ) ) {
			return [];
		}

		$links = [];
		foreach ( $matches[0] as $k => $v ) {
			if ( empty( $matches[4][ $k ] ) || empty( $matches[6][ $k ] ) ) {
				continue;
			}

			$parsedUrl = $this->parseUrl( $matches[4][ $k ] );
			if ( empty( $parsedUrl['host'] ) ) {
				continue;
			}

			if (
				! empty( $parsedUrl['path'] ) &&
				preg_match( '/\.(.*?)$/i', $parsedUrl['path'], $extension ) &&
				! empty( $extension[1] ) &&
				in_array( $extension[1], $this->ignoredExtensions, true )
			) {
				continue;
			}

			// NOTE: We need to check this here before we strip off the "www" part.
			// Otherwise we will not be able to detect internal links on sites running on "www".
			$isInternal = $parsedUrl['host'] === $this->getHostname();

			$hostname = aioseoBrokenLinkChecker()->helpers->pregReplace( '/www\./i', '', $parsedUrl['host'] );
			$anchor   = wp_strip_all_tags( $matches[6][ $k ] );
			// Remove trailing URL tags. The regex isn't sufficient for this.
			$phrase = wp_strip_all_tags( $matches[0][ $k ] );
			$phrase = trim( preg_replace( '/(.*)(<t?a[^<>].*$)/', '', (string) $phrase ) );

			// Don't continue if the anchor or phrase are empty, e.g. blank link tag.
			if ( ! $anchor || ! $phrase ) {
				continue;
			}

			$phraseHtml = aioseoBrokenLinkChecker()->helpers->stripIncompleteHtmlTags( $matches[0][ $k ] );
			$phraseHtml = aioseoBrokenLinkChecker()->helpers->stripScriptTags( $phraseHtml );
			$phraseHtml = aioseoBrokenLinkChecker()->helpers->trimParagraphTags( $phraseHtml );

			if ( empty( $phraseHtml ) ) {
				continue;
			}

			$paragraph     = aioseoBrokenLinkChecker()->main->paragraph->get( $postId, $postContent, $phrase );
			$paragraphHtml = aioseoBrokenLinkChecker()->main->paragraph->getHtml( $anchor, $paragraph, $postContent );

			// Reformat the URL to get rid of params and fragments.
			$url = $this->geturlWithoutParamsAndFragment( $parsedUrl );

			// We need to sanitize the URL here so the hash is calculated based on the escaped version.
			$url = trim( sanitize_url( $url ) );

			$linkData = [
				'post_id'            => (int) $postId,
				'blc_link_status_id' => $this->getLinkStatusId( $url ),
				'url'                => $url,
				'url_hash'           => sha1( $url ),
				'hostname'           => $hostname,
				'hostname_url'       => sha1( $hostname ),
				'external'           => ! $isInternal,
				'anchor'             => $anchor,
				'phrase'             => $phrase,
				'phrase_html'        => $phraseHtml,
				'paragraph'          => $paragraph,
				'paragraph_html'     => $paragraphHtml
			];

			$links[] = $linkData;
		}

		return $links;
	}

	/**
	 * Return the link status ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $url The URL to look up.
	 * @return int|null      The link status ID.
	 */
	private function getLinkStatusId( $url ) {
		static $linkStatusId = [];

		$hash = sha1( $url );
		if ( isset( $linkStatusId[ $hash ] ) ) {
			return $linkStatusId[ $hash ];
		}

		$possibleLinkStatusId = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status' )
			->where( 'url_hash', $hash )
			->run()
			->result();

		$linkStatusId[ $hash ] = ! empty( $possibleLinkStatusId ) ? $possibleLinkStatusId[0]->id : null;

		return $linkStatusId[ $hash ];
	}

	/**
	 * Returns the site's hostname.
	 *
	 * @since 1.0.0
	 *
	 * @return string The hostname.
	 */
	private function getHostname() {
		static $siteUrl = null;
		if ( null === $siteUrl ) {
			$siteUrl = wp_parse_url( get_site_url(), PHP_URL_HOST );
		}

		return $siteUrl;
	}

	/**
	 * Returns the parsed URL.
	 *
	 * @since 1.0.0
	 * @since 1.1.1 Renamed method.
	 *
	 * @param  string $url The URL.
	 * @return array       The parsed URL.
	 */
	private function parseUrl( $url ) {
		$parsedUrl = wp_parse_url( $url );
		if ( empty( $parsedUrl ) ) {
			return [];
		}

		// If the URL is relative, add the hostname of the site.
		if ( empty( $parsedUrl['host'] ) ) {
			$parsedUrl['host']   = $this->getHostname();
			$parsedUrl['scheme'] = wp_parse_url( get_site_url(), PHP_URL_SCHEME );
		}

		return $parsedUrl;
	}

	/**
	 * Returns the URL without params and fragments.
	 *
	 * @since 1.1.1
	 *
	 * @param  array  $parsedUrl The parsed URL.
	 * @return string            The URL without params and fragments.
	 */
	private function geturlWithoutParamsAndFragment( $parsedUrl ) {
		$url = '';
		if ( ! empty( $parsedUrl['scheme'] ) ) {
			$url .= $parsedUrl['scheme'] . '://';
		}

		$url .= $parsedUrl['host'];

		if ( ! empty( $parsedUrl['path'] ) ) {
			$url .= $parsedUrl['path'];
		}

		return $url;
	}

	/**
	 * Returns the posts to scan.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool      $countOnly Whether to return only the count.
	 * @return array|int            The posts to scan or a count.
	 */
	public function getPostsToScan( $countOnly = false ) {
		$postsPerScan        = apply_filters( 'aioseo_blc_links_posts_per_scan', 50 );
		$postTypes           = aioseoBrokenLinkChecker()->helpers->getScannablePostTypes();
		$postStatuses        = aioseoBrokenLinkChecker()->helpers->getPublicPostStatuses( true );
		$minimumLinkScanDate = aioseoBrokenLinkChecker()->internalOptions->internal->minimumLinkScanDate ?: date( 'Y-m-d H:i:s' );

		$query = aioseoBrokenLinkChecker()->core->db->start( 'posts as p' )
			->leftJoin( 'aioseo_blc_posts as abp', 'p.ID = abp.post_id' )
			->whereIn( 'p.post_status', $postStatuses )
			->whereIn( 'p.post_type', $postTypes )
			->whereRaw( "(
				abp.post_id IS NULL OR
				abp.link_scan_date < p.post_modified_gmt OR
				abp.link_scan_date IS NULL OR
				abp.link_scan_date < '$minimumLinkScanDate'
			)" );

		if ( $countOnly ) {
			return $query->count();
		}

		$postsToScan = $query
			->select( 'DISTINCT p.ID, p.post_content, p.post_type, p.post_status' )
			->limit( $postsPerScan )
			->run()
			->result();

		return $postsToScan;
	}

	/**
	 * Returns the total number of scannable posts.
	 *
	 * @since 1.0.0
	 *
	 * @return int The total number of scannable posts.
	 */
	private function getTotalScannablePosts() {
		$postTypes    = aioseoBrokenLinkChecker()->helpers->getScannablePostTypes();
		$postStatuses = aioseoBrokenLinkChecker()->helpers->getPublicPostStatuses( true );

		$query = aioseoBrokenLinkChecker()->core->db->start( 'posts as p' )
			->whereIn( 'p.post_status', $postStatuses )
			->whereIn( 'p.post_type', $postTypes );

		return $query->count();
	}

	/**
	 * Returns the scan percentage.
	 *
	 * @since 1.0.0
	 *
	 * @return int The scan percentage.
	 */
	public function getScanPercentage() {
		$postsToScan         = $this->getPostsToScan( true );
		$totalScannablePosts = $this->getTotalScannablePosts();
		if ( 0 === $postsToScan || 0 === $totalScannablePosts ) {
			return 100;
		}

		return ceil( 100 - ( ( $postsToScan / $totalScannablePosts ) * 100 ) );
	}

	/**
	 * Sets the ignored extensions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function setIgnoredExtensions() {
		$this->ignoredExtensions = apply_filters( 'aioseo_blc_ignored_extensions', [
			// Executable files
			'apk',
			'bat',
			'bin',
			'cgi',
			'com',
			'exe',
			'gadget',
			'jar',
			'py',
			'wsf',
		] );
	}
}