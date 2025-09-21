<?php
namespace AIOSEO\BrokenLinkChecker\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class contains pre-updates necessary for the main Updates class to run.
 *
 * @since 1.0.0
 */
class PreUpdates {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$lastActiveVersion = aioseoBrokenLinkChecker()->internalOptions->internal->lastActiveVersion;
		if ( aioseoBrokenLinkChecker()->version !== $lastActiveVersion ) {
			// Bust the table/columns cache so that we can start the update migrations with a fresh slate.
			aioseoBrokenLinkChecker()->internalOptions->database->installedTables = '';
		}

		if ( version_compare( $lastActiveVersion, '1.0.0', '<' ) ) {
			$this->createCacheTable();
		}
	}

	/**
	 * Creates the cache table.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function createCacheTable() {
		$db             = aioseoBrokenLinkChecker()->core->db->db;
		$charsetCollate = '';

		if ( ! empty( $db->charset ) ) {
			$charsetCollate .= "DEFAULT CHARACTER SET {$db->charset}";
		}
		if ( ! empty( $db->collate ) ) {
			$charsetCollate .= " COLLATE {$db->collate}";
		}

		if ( ! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_cache' ) ) {
			$tableName = $db->prefix . 'aioseo_blc_cache';

			aioseoBrokenLinkChecker()->core->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`key` varchar(80) NOT NULL,
					`value` longtext NOT NULL,
					`expiration` datetime NULL,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY ndx_aioseo_blc_cache_key (`key`),
					KEY ndx_aioseo_blc_cache_expiration (`expiration`)
				) {$charsetCollate};"
			);
		}
	}
}