<?php
namespace AIOSEO\BrokenLinkChecker\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles license update/removal and related notices.
 *
 * @since 1.0.0
 */
class License {
	/**
	 * The base URL for the licensing API.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $baseUrl = 'https://blc-licensing.aioseo.com/v1/';

	/**
	 * Options class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var \AIOSEO\BrokenLinkChecker\Options\Options
	 */
	protected $options = null;

	/**
	 * InternalOptions class instance.
	 *
	 * @since 1.0.0
	 *t
	 * @var \AIOSEO\BrokenLinkChecker\Options\InternalOptions
	 */
	protected $internalOptions = null;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->internalOptions = aioseoBrokenLinkChecker()->internalOptions;

		add_action( 'init', [ $this, 'checkIfNeedsValidation' ] );
	}

	/**
	 * Checks if we should validate the license key or not.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function checkIfNeedsValidation() {
		if ( ! $this->internalOptions->internal->license->licenseKey ) {
			if ( $this->needsReset() ) {
				$this->internalOptions->internal->license->reset(
					[
						'expires',
						'expired',
						'invalid',
						'disabled',
						'activationsError',
						'connectionError',
						'requestError',
						'level'
					]
				);
			}

			return;
		}

		// Validate the license key every 12 hours.
		$timestamp = $this->internalOptions->internal->license->lastChecked;
		if ( time() < $timestamp ) {
			return;
		}

		$success = $this->activate();
		if ( $success || aioseoBrokenLinkChecker()->core->cache->get( 'failed_update' ) ) {
			aioseoBrokenLinkChecker()->core->cache->delete( 'failed_update' );
			$this->internalOptions->internal->license->lastChecked = strtotime( '+12 hours' );

			return;
		}

		// If update failed, check again after one hour. If the second check fails too, we'll wait 12 hours.
		aioseoBrokenLinkChecker()->core->cache->update( 'failed_update', time() );
		$this->internalOptions->internal->license->lastChecked = strtotime( '+1 hour' );
	}

	/**
	 * Validate the license key.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether or not it was activated.
	 */
	public function activate() {
		$this->internalOptions->internal->license->reset(
			[
				'expires',
				'expired',
				'invalid',
				'disabled',
				'activationsError',
				'connectionError',
				'requestError',
				'level'
			]
		);

		$licenseKey = $this->internalOptions->internal->license->licenseKey;
		if ( empty( $licenseKey ) ) {
			return false;
		}

		$site    = aioseoBrokenLinkChecker()->helpers->getSite();
		$domains = [
			'domain' => $site->domain,
			'path'   => $site->path
		];

		$response = $this->sendLicenseRequest( 'activate', $licenseKey, [ $domains ] );

		if ( empty( $response ) ) {
			// Something bad happened, error unknown.
			$this->internalOptions->internal->license->connectionError = true;

			return false;
		}

		if ( ! empty( $response->error ) ) {
			if ( 'missing-key-or-domain' === $response->error ) {
				$this->internalOptions->internal->license->requestError = true;

				return false;
			}

			if ( 'missing-license' === $response->error ) {
				$this->internalOptions->internal->license->invalid = true;

				return false;
			}

			if ( 'disabled' === $response->error ) {
				$this->internalOptions->internal->license->disabled = true;

				return false;
			}

			if ( 'activations' === $response->error ) {
				$this->internalOptions->internal->license->activationsError = true;

				return false;
			}

			if ( 'expired' === $response->error ) {
				$this->internalOptions->internal->license->expires = strtotime( $response->expires );
				$this->internalOptions->internal->license->expired = true;

				return false;
			}
		}

		// Something bad happened, error unknown.
		if ( empty( $response->success ) || empty( $response->level ) || empty( $response->broken_links_count ) ) {
			return false;
		}

		$oldQuota = $this->internalOptions->internal->license->quota;

		$this->internalOptions->internal->license->level   = $response->level;
		$this->internalOptions->internal->license->expires = strtotime( $response->expires );
		$this->internalOptions->internal->license->quota   = intval( $response->broken_links_count );

		// Set the remaining quota if it's never been set or if the user's plan has changed.
		if (
			! $this->internalOptions->internal->license->quotaRemaining ||
			( intval( $response->broken_links_count ) !== (int) $oldQuota )
		) {
			$this->internalOptions->internal->license->quotaRemaining = intval( $response->broken_links_count );
		}

		// Cancel all Link Status scans. The next request will fire off a new one.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( aioseoBrokenLinkChecker()->main->linkStatus->actionName );
		}

		return true;
	}

	/**
	 * Deactivate the license key.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether or not it was deactivated.
	 */
	public function deactivate() {
		$licenseKey = $this->internalOptions->internal->license->licenseKey;
		if ( empty( $licenseKey ) ) {
			return false;
		}

		$site    = aioseoBrokenLinkChecker()->helpers->getSite();
		$domains = [
			'domain' => $site->domain,
			'path'   => $site->path
		];

		$response = $this->sendLicenseRequest( 'deactivate', $licenseKey, [ $domains ] );

		if ( empty( $response ) ) {
			// Something bad happened, error unknown.
			$this->internalOptions->internal->license->connectionError = true;

			return false;
		}

		if ( ! empty( $response->error ) ) {
			if ( 'missing-key-or-domain' === $response->error || 'not-activated' === $response->error ) {
				$this->internalOptions->internal->license->requestError = true;

				return false;
			}

			if ( 'missing-license' === $response->error ) {
				$this->internalOptions->internal->license->invalid = true;

				return false;
			}

			if ( 'disabled' === $response->error ) {
				$this->internalOptions->internal->license->disabled = true;

				return false;
			}
		}

		$this->internalOptions->internal->license->reset(
			[
				'expires',
				'expired',
				'invalid',
				'disabled',
				'activationsError',
				'connectionError',
				'requestError',
				'level'
			]
		);

		// Cancel all Link Status scans.
		as_unschedule_all_actions( aioseoBrokenLinkChecker()->main->linkStatus->actionName );

		return true;
	}

	/**
	 * Returns the URL to check licenses.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL.
	 */
	public function getUrl() {
		if ( defined( 'AIOSEO_BROKEN_LINK_CHECKER_LICENSING_URL' ) ) {
			return AIOSEO_BROKEN_LINK_CHECKER_LICENSING_URL;
		}

		return $this->baseUrl;
	}

	/**
	 * Checks to see if the current license is expired.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the license is expired.
	 */
	public function isExpired() {
		$networkIsExpired = false;
		$licenseKey       = $this->internalOptions->internal->license->licenseKey;
		if ( empty( $licenseKey ) ) {
			return $networkIsExpired;
		}

		$expired = $this->internalOptions->internal->license->expired || $this->internalOptions->internal->license->expires < time();
		if ( $expired ) {
			$didActivationAttempt = $this->maybeReactivateExpiredLicense();

			// If we tried to activate the license again, start over. Otherwise, return true.
			return $didActivationAttempt ? $this->isExpired() : true;
		}

		$expires = $this->internalOptions->internal->license->expires;

		return 0 !== $expires && $expires < time();
	}

	/**
	 * Checks to see if the current license is disabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the license is disabled.
	 */
	public function isDisabled() {
		$networkIsDisabled = false;
		$licenseKey        = $this->internalOptions->internal->license->licenseKey;
		if ( empty( $licenseKey ) ) {
			return $networkIsDisabled;
		}

		return $this->internalOptions->internal->license->disabled;
	}

	/**
	 * Checks to see if the current license is invalid.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the license is invalid.
	 */
	public function isInvalid() {
		$networkIsInvalid = false;
		$licenseKey       = $this->internalOptions->internal->license->licenseKey;
		if ( empty( $licenseKey ) ) {
			return $networkIsInvalid;
		}

		return $this->internalOptions->internal->license->invalid;
	}

	/**
	 * Checks to see if the current license is active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the license is active.
	 */
	public function isActive() {
		$networkIsActive = false;
		$licenseKey      = $this->internalOptions->internal->license->licenseKey;
		if ( empty( $licenseKey ) ) {
			return $networkIsActive;
		}

		return ! $this->isExpired() && ! $this->isDisabled() && ! $this->isInvalid();
	}

	/**
	 * Get the license level for the activated license.
	 *
	 * @since 1.0.0
	 *
	 * @return string The license level.
	 */
	public function getLicenseLevel() {
		return $this->internalOptions->internal->license->level;
	}

	/**
	 * Checks if the license data needs to be reset.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the license data needs to be reet.
	 */
	private function needsReset() {
		if ( ! empty( $this->internalOptions->internal->license->licenseKey ) ) {
			return false;
		}

		if ( $this->internalOptions->internal->license->level ) {
			return true;
		}

		if ( $this->internalOptions->internal->license->invalid ) {
			return true;
		}

		if ( $this->internalOptions->internal->license->disabled ) {
			return true;
		}

		$expired = $this->internalOptions->internal->license->expired;
		if ( $expired ) {
			return true;
		}

		$expires = $this->internalOptions->internal->license->expires;

		return 0 !== $expires;
	}

	/**
	 * Sends the license request.
	 *
	 * @since 1.0.0
	 *
	 * @param  string      $type       The type of request, either activate or deactivate.
	 * @param  string      $licenseKey The license key we are using for this request.
	 * @param  array       $domains    List of domains to activate or deactivate.
	 * @return Object|null             The JSON response as an object.
	 */
	public function sendLicenseRequest( $type, $licenseKey, $domains ) {
		$payload = [
			'sku'         => 'aioseo-broken-link-checker',
			'version'     => AIOSEO_BROKEN_LINK_CHECKER_VERSION,
			'php_version' => PHP_VERSION,
			'license'     => $licenseKey,
			'domains'     => $domains,
			'wp_version'  => get_bloginfo( 'version' )
		];

		return aioseoBrokenLinkChecker()->helpers->sendRequest( $this->getUrl() . $type . '/', $payload );
	}

	/**
	 * Checks if the current site is licensed at the network level.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the site is licensed at the network level.
	 */
	public function isNetworkLicensed() {
		if ( ! property_exists( aioseoBrokenLinkChecker(), 'networkLicense' ) ) {
			return false;
		}

		return aioseoBrokenLinkChecker()->networkLicense->isActive();
	}

	/**
	 * Whether the current license plan is the free plan.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function isFree() {
		return 'free' === strtolower( (string) $this->getLicenseLevel() );
	}

	/**
	 * Checks if the license is expired and attempts to activate it again.
	 *
	 * @since 1.1.0
	 *
	 * @return bool True if an attempt was made to activate the license, false if not.
	 */
	private function maybeReactivateExpiredLicense() {
		// If the license is expired, send out a request to check if it's still expired.
		// We cache this for a few hours so we don't spam the server.
		$transientName = 'expired_license_check';
		if ( aioseoBrokenLinkChecker()->core->cache->get( $transientName ) ) {
			return false;
		}

		$this->activate();
		aioseoBrokenLinkChecker()->core->cache->update( $transientName, true, 4 * HOUR_IN_SECONDS );

		return true;
	}
}