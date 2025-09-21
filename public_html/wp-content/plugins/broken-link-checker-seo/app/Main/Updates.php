<?php
namespace AIOSEO\BrokenLinkChecker\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles update migrations.
 *
 * @since 1.0.0
 */
class Updates {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		add_action( 'init', [ $this, 'runUpdates' ], 1002 );
		add_action( 'init', [ $this, 'updateLatestVersion' ], 3000 );
	}

	/**
	 * Runs our migrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function runUpdates() {
		$lastActiveVersion = aioseoBrokenLinkChecker()->internalOptions->internal->lastActiveVersion;
		if ( version_compare( $lastActiveVersion, '1.0.0', '<' ) ) {
			$this->addInitialTables();

			aioseoBrokenLinkChecker()->internalOptions->internal->minimumLinkScanDate = date( 'Y-m-d H:i:s', time() );
		}

		if ( version_compare( $lastActiveVersion, '1.2.0', '<' ) ) {
			$this->dropInvalidMediaLinks();
			$this->dropLinksWithInvalidHash();
		}
	}

	/**
	 * Updates the latest version after all migrations and updates have run.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function updateLatestVersion() {
		if ( aioseoBrokenLinkChecker()->internalOptions->internal->lastActiveVersion === aioseoBrokenLinkChecker()->version ) {
			return;
		}

		aioseoBrokenLinkChecker()->internalOptions->internal->lastActiveVersion = aioseoBrokenLinkChecker()->version;

		aioseoBrokenLinkChecker()->core->db->bustCache();
		aioseoBrokenLinkChecker()->internalOptions->database->installedTables = '';
	}

	/**
	 * Adds our custom tables.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addInitialTables() {
		$db             = aioseoBrokenLinkChecker()->core->db->db;
		$charsetCollate = '';

		if ( ! empty( $db->charset ) ) {
			$charsetCollate .= "DEFAULT CHARACTER SET {$db->charset}";
		}
		if ( ! empty( $db->collate ) ) {
			$charsetCollate .= " COLLATE {$db->collate}";
		}

		if ( ! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_link_status' ) ) {
			$tableName = $db->prefix . 'aioseo_blc_link_status';

			aioseoBrokenLinkChecker()->core->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`url` text NOT NULL,
					`url_hash` varchar(40) NOT NULL,
					`http_status_code` smallint(6) DEFAULT NULL,
					`broken` tinyint(1) unsigned DEFAULT 0 NOT NULL,
					`dismissed` tinyint(1) DEFAULT 0 NOT NULL,
					`request_duration` float DEFAULT NULL,
					`scan_count` int(4) unsigned DEFAULT 0 NOT NULL,
					`redirect_count` smallint(5) unsigned DEFAULT 0 NOT NULL,
					`final_url` text DEFAULT NULL,
					`first_failure` datetime DEFAULT NULL,
					`log` text DEFAULT NULL,
					`last_scan_date` datetime DEFAULT NULL,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY ndx_aioseo_blc_link_status_url_hash (url_hash)
				) {$charsetCollate};"
			);
		}

		if ( ! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_links' ) ) {
			$tableName = $db->prefix . 'aioseo_blc_links';

			aioseoBrokenLinkChecker()->core->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`post_id` bigint(20) unsigned NOT NULL,
					`blc_link_status_id` bigint(20) unsigned DEFAULT NULL,
					`url` text NOT NULL,
					`url_hash` varchar(40) NOT NULL,
					`hostname` text NOT NULL,
					`hostname_url` varchar(40) NOT NULL,
					`external` tinyint(1) DEFAULT 0 NOT NULL,
					`anchor` text NOT NULL,
					`phrase` text NOT NULL,
					`phrase_html` text NOT NULL,
					`paragraph` text NOT NULL,
					`paragraph_html` text NOT NULL,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (id),
					KEY ndx_aioseo_blc_links_post_id (post_id),
					KEY ndx_aioseo_blc_links_hostname (hostname(10))
				) {$charsetCollate};"
			);
		}

		if ( ! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_notifications' ) ) {
			$tableName = $db->prefix . 'aioseo_blc_notifications';

			aioseoBrokenLinkChecker()->core->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`notification_id` bigint(20) unsigned DEFAULT NULL,
					`notification_name` varchar(255) DEFAULT NULL,
					`slug` varchar(13) NOT NULL,
					`title` text NOT NULL,
					`content` longtext NOT NULL,
					`type` varchar(64) NOT NULL,
					`level` text NOT NULL,
					`start` datetime DEFAULT NULL,
					`end` datetime DEFAULT NULL,
					`button1_label` varchar(255) DEFAULT NULL,
					`button1_action` varchar(255) DEFAULT NULL,
					`button2_label` varchar(255) DEFAULT NULL,
					`button2_action` varchar(255) DEFAULT NULL,
					`dismissed` tinyint(1) NOT NULL DEFAULT 0,
					`new` tinyint(1) NOT NULL DEFAULT 1,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY ndx__aioseo_blc_notifications_slug (slug),
					KEY ndx__aioseo_blc_notifications_dates (start, end),
					KEY ndx__aioseo_blc_notifications_type (type),
					KEY ndx__aioseo_blc_notifications_dismissed (dismissed)
				) {$charsetCollate};"
			);
		}

		if ( ! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_posts' ) ) {
			$tableName = $db->prefix . 'aioseo_blc_posts';

			aioseoBrokenLinkChecker()->core->db->execute(
				"CREATE TABLE {$tableName} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`post_id` bigint(20) unsigned NOT NULL,
					link_scan_date datetime DEFAULT NULL,
					created datetime NOT NULL,
					updated datetime NOT NULL,
					PRIMARY KEY (id),
					KEY ndx_aioseo_blc_posts_post_id (post_id)
				) {$charsetCollate};"
			);
		}
	}

	/**
	 * Removes all mailto and tel links from the database.
	 *
	 * @since 1.0.5
	 *
	 * @return void
	 */
	private function dropInvalidMediaLinks() {
		$tableName = aioseoBrokenLinkChecker()->core->db->prefix . 'aioseo_blc_links';

		aioseoBrokenLinkChecker()->core->db->execute(
			"DELETE FROM {$tableName} WHERE url LIKE 'mailto:%' OR url LIKE 'tel:%'"
		);

		$tableName = aioseoBrokenLinkChecker()->core->db->prefix . 'aioseo_blc_link_status';

		aioseoBrokenLinkChecker()->core->db->execute(
			"DELETE FROM {$tableName} WHERE url LIKE 'mailto:%' OR url LIKE 'tel:%'"
		);
	}

	/**
	 * Removes all links with percentage signs from the database as these had invalid hashes.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function dropLinksWithInvalidHash() {
		$blcPosts = aioseoBrokenLinkChecker()->core->db->prefix . 'aioseo_blc_posts';
		$blcLinks = aioseoBrokenLinkChecker()->core->db->prefix . 'aioseo_blc_links';

		aioseoBrokenLinkChecker()->core->db->execute(
			"UPDATE {$blcPosts} SET link_scan_date = NULL
			WHERE post_id IN (
				SELECT post_id FROM {$blcLinks} WHERE url LIKE '%\\%%'
			)"
		);

		$blcLinkStatus = aioseoBrokenLinkChecker()->core->db->prefix . 'aioseo_blc_link_status';

		aioseoBrokenLinkChecker()->core->db->execute(
			"DELETE FROM {$blcLinkStatus} WHERE url LIKE '%\\%%'"
		);
	}
}