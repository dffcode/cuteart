<?php
namespace AIOSEO\BrokenLinkChecker\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles our cache.
 *
 * @since 1.0.0
 */
class Cache {
	/**
	 * The name of our cache table.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $table = 'aioseo_blc_cache';

	/**
	 * Our cache.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private static $cache = [];

	/**
	 * Prefix for this cache.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $prefix = 'aioseo_blc_';

	/**
	 * Class constructor.
	 *
	 * @since 4.7.8
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'checkIfTableExists' ] ); // This needs to run on init because the DB
		// class gets instantiated along with the cache class.
	}

	/**
	 * Checks if the cache table exists and creates it if it doesn't.
	 *
	 * @since 4.7.8
	 *
	 * @return void
	 */
	public function checkIfTableExists() {
		if ( ! aioseoBrokenLinkChecker()->core->db->tableExists( $this->table ) ) {
			aioseoBrokenLinkChecker()->preUpdates->createCacheTable();
		}
	}

	/**
	 * Returns the cache value if it exists and isn't expired.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key The key name. Use a '%' for a LIKE query.
	 * @return mixed       The value or null if the cache does not exist.
	 */
	public function get( $key ) {
		$key = $this->prepareKey( $key );
		if ( isset( self::$cache[ $key ] ) ) {
			return self::$cache[ $key ];
		}

		// Check if we're supposed to do a LIKE get.
		$isLikeGet = preg_match( '/%/', (string) $key );

		$result = aioseoBrokenLinkChecker()->core->db
			->start( $this->table )
			->select( '`key`, `value`' )
			->whereRaw( '( `expiration` IS NULL OR `expiration` > \'' . aioseoBrokenLinkChecker()->helpers->timeToMysql( time() ) . '\' )' );

		$isLikeGet ?
			$result->whereRaw( '`key` LIKE \'' . $key . '\'' ) :
			$result->where( 'key', $key );

		$result->output( ARRAY_A )->run();

		// If we have nothing in the cache, let's return null.
		$values = $result->nullSet() ? null : $result->result();

		// If we have something, let's normalize it.
		if ( $values ) {
			foreach ( $values as &$value ) {
				$value['value'] = aioseoBrokenLinkChecker()->helpers->maybeUnserialize( $value['value'] );
			}
			// Return only the single cache value.
			if ( ! $isLikeGet ) {
				$values = $values[0]['value'];
			}
		}

		// Return values without a static cache.
		// This is here because clearing the LIKE cache is not simple.
		if ( $isLikeGet ) {
			return $values;
		}

		self::$cache[ $key ] = $values;

		return self::$cache[ $key ];
	}

	/**
	 * Updates the given cache or creates it if it doesn't exist.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key        The key name.
	 * @param  mixed  $value      The value.
	 * @param  int    $expiration The expiration time in seconds. Defaults to 24 hours. 0 to no expiration.
	 * @return void
	 */
	public function update( $key, $value, $expiration = DAY_IN_SECONDS ) {
		// If the value is null we'll convert it and give it a shorter expiration.
		if ( null === $value ) {
			$value      = false;
			$expiration = 10 * MINUTE_IN_SECONDS;
		}

		$value      = serialize( $value );
		$expiration = 0 < $expiration ? aioseoBrokenLinkChecker()->helpers->timeToMysql( time() + $expiration ) : null;

		aioseoBrokenLinkChecker()->core->db->insert( $this->table )
			->set( [
				'key'        => $this->prepareKey( $key ),
				'value'      => $value,
				'expiration' => $expiration,
				'created'    => aioseoBrokenLinkChecker()->helpers->timeToMysql( time() ),
				'updated'    => aioseoBrokenLinkChecker()->helpers->timeToMysql( time() )
			] )->onDuplicate( [
				'value'      => $value,
				'expiration' => $expiration,
				'updated'    => aioseoBrokenLinkChecker()->helpers->timeToMysql( time() )
			] )
			->run();

		$this->clearStatic( $key );
	}

	/**
	 * Deletes the cache record with the given key.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key The key.
	 * @return void
	 */
	public function delete( $key ) {
		$key = $this->prepareKey( $key );

		aioseoBrokenLinkChecker()->core->db->delete( $this->table )
			->where( 'key', $key )
			->run();

		$this->clearStatic( $key );
	}

	/**
	 * Prepares the key before using the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key The key to prepare.
	 * @return string      The prepared key.
	 */
	private function prepareKey( $key ) {
		$key = trim( $key );
		$key = $this->prefix && 0 !== strpos( $key, $this->prefix ) ? $this->prefix . $key : $key;

		if ( aioseoBrokenLinkChecker()->helpers->isDev() && 80 < mb_strlen( $key, 'UTF-8' ) ) {
			throw new \Exception( 'You are using a cache key that is too large, shorten your key and try again: [' . esc_html( $key ) . ']' );
		}

		return $key;
	}

	/**
	 * Clears all of our cache.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear() {
		// Bust the tableExists and columnExists cache.
		aioseoBrokenLinkChecker()->internalOptions->database->installedTables = '';

		if ( $this->prefix ) {
			$this->clearPrefix( '' );

			return;
		}

		aioseoBrokenLinkChecker()->core->db->truncate( $this->table )->run();

		$this->clearStatic();
	}

	/**
	 * Clears all of our cache under a certain prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $prefix A prefix to clear or empty to clear everything.
	 * @return void
	 */
	public function clearPrefix( $prefix ) {
		$prefix = $this->prepareKey( $prefix );

		aioseoBrokenLinkChecker()->core->db->delete( $this->table )
			->whereRaw( "`key` LIKE '$prefix%'" )
			->run();

		$this->clearStaticPrefix( $prefix );
	}

	/**
	 * Clears all of our static in-memory cache of a prefix.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $prefix The prefix to clear.
	 * @return void
	 */
	private function clearStaticPrefix( $prefix ) {
		$prefix = $this->prepareKey( $prefix );
		foreach ( array_keys( self::$cache ) as $key ) {
			if ( 0 === strpos( $key, $prefix ) ) {
				unset( self::$cache[ $key ] );
			}
		}
	}

	/**
	 * Clears all of our static in-memory cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key The key to clear.
	 * @return void
	 */
	private function clearStatic( $key = null ) {
		if ( empty( $key ) ) {
			self::$cache = [];

			return;
		}

		unset( self::$cache[ $this->prepareKey( $key ) ] );
	}
}