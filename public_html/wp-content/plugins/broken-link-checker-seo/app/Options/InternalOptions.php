<?php
namespace AIOSEO\BrokenLinkChecker\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Traits;

/**
 * Class that holds all internal options for Broken Link Checker.
 *
 * @since 1.0.0
 */
class InternalOptions {
	use Traits\Options;

	/**
	 * Holds a list of all the possible deprecated options.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $allDeprecatedOptions = [];

	/**
	 * All the default options.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $defaults = [
		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		'internal' => [
			'firstActivated'      => [ 'type' => 'number', 'default' => 0 ],
			'lastActiveVersion'   => [ 'type' => 'string', 'default' => '0.0' ],
			'scanId'              => [ 'type' => 'string', 'default' => null ],
			'minimumLinkScanDate' => [ 'type' => 'string', 'default' => null ],
			'license'             => [
				'expires'          => [ 'type' => 'number', 'default' => 0 ],
				'expired'          => [ 'type' => 'boolean', 'default' => false ],
				'invalid'          => [ 'type' => 'boolean', 'default' => false ],
				'disabled'         => [ 'type' => 'boolean', 'default' => false ],
				'connectionError'  => [ 'type' => 'boolean', 'default' => false ],
				'activationsError' => [ 'type' => 'boolean', 'default' => false ],
				'requestError'     => [ 'type' => 'boolean', 'default' => false ],
				'lastChecked'      => [ 'type' => 'number', 'default' => 0 ],
				'level'            => [ 'type' => 'string' ],
				'licenseKey'       => [ 'type' => 'string', 'default' => '' ],
				'quota'            => [ 'type' => 'number', 'default' => 0 ],
				'quotaRemaining'   => [ 'type' => 'number', 'default' => 0 ]
			]
		],
		'database' => [
			'installedTables' => [ 'type' => 'string' ]
		]
		// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
	];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $optionsName The options name.
	 */
	public function __construct( $optionsName = 'aioseo_blc_options_internal' ) {
		$this->optionsName = is_network_admin() ? $optionsName . '_network' : $optionsName;

		$this->init();

		add_action( 'shutdown', [ $this, 'save' ] );
	}

	/**
	 * Initializes the options.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function init() {
		// Options from the DB.
		$dbOptions = $this->getDbOptions( $this->optionsName );

		// Refactor options.
		$this->defaultsMerged = array_replace_recursive( $this->defaults, $this->defaultsMerged );

		$options = array_replace_recursive(
			$this->defaultsMerged,
			$this->addValueToValuesArray( $this->defaultsMerged, $dbOptions )
		);

		aioseoBrokenLinkChecker()->core->optionsCache->setOptions( $this->optionsName, apply_filters( 'aioseo_blc_get_options_internal', $options ) );
	}

	/**
	 * Get all the deprecated options.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function getAllDeprecatedOptions() {
		return $this->allDeprecatedOptions;
	}

	/**
	 * Sanitizes, then saves the options to the database.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $options An array of options to sanitize, then save.
	 * @return void
	 */
	public function sanitizeAndSave( $options ) {
		if ( ! is_array( $options ) ) {
			return;
		}

		// Refactor options.
		$cachedOptions = aioseoBrokenLinkChecker()->core->optionsCache->getOptions( $this->optionsName );
		$dbOptions     = array_replace_recursive(
			$cachedOptions,
			$this->addValueToValuesArray( $cachedOptions, $options, [], true )
		);

		aioseoBrokenLinkChecker()->core->optionsCache->setOptions( $this->optionsName, $dbOptions );

		// Update values.
		$this->save( true );
	}
}