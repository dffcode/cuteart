<?php
namespace AIOSEO\BrokenLinkChecker\LinkStatus;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles fetching of data required for Link Status scan requests.
 *
 * @since 1.0.0
 */
class Data {
	/**
	 * Returns the base data we need to include in our requests to the server.
	 *
	 * @since 1.0.0
	 *
	 * @return array The base data.
	 */
	public function getBaseData() {
		return [
			'domain'          => aioseoBrokenLinkChecker()->helpers->getSiteDomain(),
			'internalOptions' => aioseoBrokenLinkChecker()->internalOptions->all(),
			'indexedLinks'    => aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status' )->count(),
			'isSsl'           => is_ssl(),
			'options'         => aioseoBrokenLinkChecker()->options->all(),
			'version'         => AIOSEO_BROKEN_LINK_CHECKER_VERSION
		];
	}

	/**
	 * Returns links that still need to be checked.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool      $countOnly          Whether to return the count instead of all the rows.
	 * @param  bool      $ignoreStaleResults Whether to ignore stale results.
	 * @return array|int                     The links to check the status for.
	 */
	public function getLinksToCheck( $countOnly = false, $ignoreStaleResults = false ) {
		static $linksToScan = null;
		if ( null !== $linksToScan ) {
			return $linksToScan;
		}

		$linksPerScan         = 200;
		$includedPostTypes    = aioseoBrokenLinkChecker()->helpers->getIncludedPostTypes();
		$includedPostStatuses = aioseoBrokenLinkChecker()->helpers->getIncludedPostStatuses();
		$excludedPostIds      = aioseoBrokenLinkChecker()->helpers->getExcludedPostIds();
		$time                 = aioseoBrokenLinkChecker()->helpers->timeToMysql( strtotime( '-7 days' ) );

		$query = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status as als' )
			->join( 'aioseo_blc_links al', 'al.blc_link_status_id = als.id' )
			->join( 'posts as p', 'p.ID = al.post_id' )
			->where( 'als.dismissed', 0 )
			->groupBy( 'als.id' );

		if ( $ignoreStaleResults ) {
			$query->where( 'als.last_scan_date', null );
		} else {
			$query->whereRaw( "(
				als.last_scan_date IS NULL
				OR als.last_scan_date < '$time'
			)" );
		}

		$excludedDomains = aioseoBrokenLinkChecker()->helpers->getExcludedDomains();
		if ( ! empty( $excludedDomains ) ) {
			$query->whereNotIn( 'al.hostname', $excludedDomains );
		}

		if ( aioseoBrokenLinkChecker()->license->isFree() ) {
			$query->where( 'al.external', 0 );
		}

		if ( ! empty( $includedPostStatuses ) ) {
			$includedPostStatuses = aioseoBrokenLinkChecker()->helpers->implodeWhereIn( $includedPostStatuses, true );
			$query->whereRaw( "p.post_status IN ( $includedPostStatuses )" );
		}

		if ( ! empty( $includedPostTypes ) ) {
			$includedPostTypes = aioseoBrokenLinkChecker()->helpers->implodeWhereIn( $includedPostTypes, true );
			$query->whereRaw( "p.post_type IN ( $includedPostTypes )" );
		}

		if ( ! empty( $excludedPostIds ) ) {
			$excludedPostIds = aioseoBrokenLinkChecker()->helpers->implodeWhereIn( $excludedPostIds, true );
			$query->whereRaw( "p.ID NOT IN ( $excludedPostIds )" );
		}

		if ( $countOnly ) {
			return $query->count();
		}

		$linksToScan = $query->select( 'als.id, als.url' )
			->limit( $linksPerScan )
			->run()
			->result();

		return $linksToScan;
	}

	/**
	 * Returns the total number of indexed links.
	 *
	 * @since 1.1.0
	 *
	 * @return int The total number of indexed links.
	 */
	private function getTotalLinks() {
		$query = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_link_status as als' )
			->select( 'als.id' )
			->join( 'aioseo_blc_links al', 'al.blc_link_status_id = als.id' )
			->where( 'als.dismissed', 0 )
			->groupBy( 'als.id' );

		$excludedDomains = aioseoBrokenLinkChecker()->helpers->getExcludedDomains();
		if ( ! empty( $excludedDomains ) ) {
			$query->whereNotIn( 'al.hostname', $excludedDomains );
		}

		if ( aioseoBrokenLinkChecker()->license->isFree() ) {
			$query->where( 'al.external', 0 );
		}

		return $query->count();
	}

	/**
	 * Returns the scan percentage.
	 *
	 * @since 1.1.0
	 *
	 * @return int The scan percentage.
	 */
	public function getScanPercentage() {
		$linksToCheck = $this->getLinksToCheck( true, true );
		$totalLinks   = $this->getTotalLinks();
		if (
			( 0 === $linksToCheck || 0 === $totalLinks ) ||
			// If there's just a few posts to scan, then we don't want to show the scan percentage bubble.
			5 >= (int) $linksToCheck
		) {
			return 100;
		}

		return ceil( 100 - ( ( $linksToCheck / $totalLinks ) * 100 ) );
	}
}