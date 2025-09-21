<?php
namespace AIOSEO\BrokenLinkChecker\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Core\Database;

/**
 * The LinkStatus DB model class.
 *
 * @since 1.0.0
 */
class LinkStatus extends Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'aioseo_blc_link_status';

	/**
	 * Fields that should be numeric values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $integerFields = [ 'id', 'broken', 'dismissed', 'scan_count', 'redirect_count', 'http_status_code' ];

	/**
	 * Fields that are nullable.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $nullFields = [ 'last_scan_date', 'final_url' ];

	/**
	 * Fields that should be boolean values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $booleanFields = [
		'broken',
		'dismissed'
	];

	/**
	 * Fields that contain a JSON string.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $jsonFields = [ 'log' ];

	/**
	 * Returns the Link Status with the given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  int        $linkStatusId The Link Status ID.
	 * @return LinkStatus               The Link Status instance.
	 */
	public static function getById( $linkStatusId ) {
		return aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status' )
			->where( 'id', $linkStatusId )
			->run()
			->model( 'AIOSEO\\BrokenLinkChecker\\Models\\LinkStatus' );
	}

	/**
	 * Returns a list of Link Status rows with the given IDs.
	 *
	 * @since 1.1.0
	 *
	 * @param  array $linkStatusIds List of Link Status IDs.
	 * @return array                List of Link Status instances.
	 */
	public static function getByIds( $linkStatusIds ) {
		return aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status' )
			->whereIn( 'id', $linkStatusIds )
			->run()
			->models( 'AIOSEO\\BrokenLinkChecker\\Models\\LinkStatus' );
	}

	/**
	 * Returns the Link Status with the given URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string     $url The URL (unhashed!).
	 * @return LinkStatus      The Link Status instance.
	 */
	public static function getByUrl( $url ) {
		$hash = sha1( $url );

		$linkStatus = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status' )
			->where( 'url_hash', $hash )
			->run()
			->model( 'AIOSEO\\BrokenLinkChecker\\Models\\LinkStatus' );

		if ( ! $linkStatus->exists() ) {
			// Updates to the plugin can cause hash mismatches. Let's do another attempt using the URL.
			// We do a join to improve performance since the URL isn't indexed.
			$hostname = wp_parse_url( $url, PHP_URL_HOST );
			$result   = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status as abls' )
				->select( 'abls.id' )
				->join( 'aioseo_blc_links as abl', 'abls.id = abl.blc_link_status_id' )
				->where( 'abl.hostname', $hostname )
				->where( 'abls.url', $url )
				->groupBy( 'abls.id' )
				->limit( 1 )
				->run()
				->result();

			if ( ! empty( $result[0]->id ) ) {
				$linkStatus = self::getById( $result[0]->id );

				if ( $linkStatus->exists() ) {
					// Reset the URL hash to prevent future mismatches.
					$linkStatus->url_hash = $hash;
					$linkStatus->save();
				}
			}
		}

		return $linkStatus;
	}

	/**
	 * Returns all broken links for a given post ID.
	 *
	 * @since 1.2.0
	 *
	 * @param int    $postId The post ID.
	 * @return array         The list of broken links.
	 */
	public static function getBrokenByPostId( $postId ) {
		$query = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status as als' )
			->join( 'aioseo_blc_links as al', 'als.id = al.blc_link_status_id' )
			->where( 'al.post_id', $postId )
			->where( 'als.broken', true )
			->where( 'als.dismissed', false );

		return $query->run()
			->result();
	}

	/**
	 * Returns link status row results based on the given arguments.
	 * This is basically a wrapper/query builder that we use to fetch all the data we need for the Broken Links Report.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from Links model to Link Status model.
	 *
	 * @param  string $filter      The active filter.
	 * @param  int    $limit       The limit.
	 * @param  int    $offset      The offset.
	 * @param  string $whereClause The WHERE clause.
	 * @param  string $orderBy     The order by.
	 * @param  string $orderDir    The order direction.
	 * @return array               List of Link Status rows with related Link rows embedded.
	 */
	public static function rowQuery( $filter = 'all', $limit = 20, $offset = 0, $whereClause = '', $orderBy = '', $orderDir = 'DESC' ) {
		$query = self::baseQuery( $filter, $whereClause )
			->select( 'als.*, al.external' )
			->limit( $limit, $offset );

		if ( $orderBy && $orderDir ) {
			$query->orderBy( "$orderBy $orderDir" );
		} else {
			$query->orderBy( 'als.id DESC' );
		}

		$linkStatusRows = $query->run()
			->result();

		if ( empty( $linkStatusRows ) ) {
			return [];
		}

		$rowsWithData = [];
		foreach ( $linkStatusRows as $linkStatusRow ) {
			$linkStatusRow->totalLinks = Link::rowQueryCount( $linkStatusRow->id );
			if ( $linkStatusRow->totalLinks > 1 ) {
				$rowsWithData[] = $linkStatusRow;
				continue;
			}

			// If this link status has just one link, then we'll get it here.
			// Otherwise we'll get them when the links table loads.
			$linkRows            = Link::rowQuery( $linkStatusRow->id, 1 );
			$linkStatusRow->link = reset( $linkRows );
			$rowsWithData[]      = $linkStatusRow;
		}

		return $rowsWithData;
	}

	/**
	 * Returns link status row count based on the given arguments.
	 * This is basically a wrapper/query builder that we use to fetch all the counts we need for the Broken Links Report.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from Links model to Link Status model.
	 *
	 * @param  string $filter      The active filter.
	 * @param  string $whereClause The WHERE clause.
	 * @return int                 The row count.
	 */
	public static function rowCountQuery( $filter = 'all', $whereClause = '' ) {
		$query = self::baseQuery( $filter, $whereClause );

		return $query->count();
	}

	/**
	 * Returns the base query for the rowQuery() and rowCountQuery() methods.
	 *
	 * @since   1.0.0
	 * @version 1.1.0 Moved from Links model to Link Status model.
	 *
	 * @param  string   $filter      The active filter.
	 * @param  string   $whereClause The WHERE clause.
	 * @return Database              The query.
	 */
	private static function baseQuery( $filter = 'all', $whereClause = '' ) {
		$includedPostTypes    = aioseoBrokenLinkChecker()->helpers->getIncludedPostTypes();
		$includedPostStatuses = aioseoBrokenLinkChecker()->helpers->getIncludedPostStatuses();
		$excludedPostIds      = aioseoBrokenLinkChecker()->helpers->getExcludedPostIds();
		$excludedDomains      = aioseoBrokenLinkChecker()->helpers->getExcludedDomains();

		$query = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status as als' )
			->join( 'aioseo_blc_links as al', 'als.id = al.blc_link_status_id' )
			->join( 'posts as p', 'al.post_id = p.ID' )
			->groupBy( 'al.url' );

		if ( ! empty( $whereClause ) ) {
			$query->whereRaw( $whereClause );
		}

		if ( ! empty( $includedPostStatuses ) ) {
			$query->whereIn( 'p.post_status', $includedPostStatuses );
		}

		if ( ! empty( $includedPostTypes ) ) {
			$query->whereIn( 'p.post_type', $includedPostTypes );
		}

		if ( ! empty( $excludedPostIds ) ) {
			$query->whereNotIn( 'p.ID', $excludedPostIds );
		}

		if ( ! empty( $excludedDomains ) ) {
			$query->whereNotIn( 'al.hostname', $excludedDomains );
		}

		if ( ! empty( $filter ) ) {
			switch ( $filter ) {
				case 'broken':
					$query->where( 'als.broken', true );
					$query->where( 'als.dismissed', false );
					break;
				case 'redirects':
					$query->where( 'als.redirect_count >', 0 );
					$query->where( 'als.dismissed', false );
					break;
				case 'dismissed':
					$query->where( 'als.dismissed', true );
					break;
				case 'not-checked':
					$query->where( 'als.last_scan_date', null );
					break;
				case 'all':
				default:
					$query->where( 'als.dismissed', false );
					break;
			}
		}

		return $query;
	}
}