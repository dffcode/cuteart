<?php
namespace AIOSEO\BrokenLinkChecker\LinkStatus;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles the Link Status scan.
 *
 * @since 1.0.0
 */
class LinkStatus {
	/**
	 * The base URL for the broken link checker server.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $baseUrl = 'https://check-links.aioseo.com/v1/';

	/**
	 * The action name of the scan.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $actionName = 'aioseo_blc_link_status_scan';

	/**
	 * Data class instance.
	 *
	 * @since 1.1.0
	 *
	 * @var Data
	 */
	public $data = null;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->data = new Data();

		add_action( $this->actionName, [ $this, 'checkLinkStatuses' ], 11, 1 );
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'init', [ $this, 'scheduleScan' ], 3003 );
	}

	/**
	 * Schedules the link status scan.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function scheduleScan() {
		if ( ! aioseoBrokenLinkChecker()->license->isActive() ) {
			return;
		}

		// If there is no action at all, schedule one.
		if ( ! aioseoBrokenLinkChecker()->actionScheduler->isScheduled( $this->actionName ) ) {
			aioseoBrokenLinkChecker()->actionScheduler->scheduleAsync( $this->actionName );
		}
	}

	/**
	 * Sends links to the server to check their status.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function checkLinkStatuses() {
		if ( ! aioseoBrokenLinkChecker()->license->isActive() ) {
			return;
		}

		$scanId = aioseoBrokenLinkChecker()->internalOptions->internal->scanId;
		if ( ! empty( $scanId ) ) {
			// If we have a scan ID, check if the results are ready.
			$this->checkForScanResults();

			return;
		}

		// If we don't have a scan ID, first check if there are links that need to be checked.
		$linksToCheck = $this->data->getlinksToCheck();
		if ( empty( $linksToCheck ) ) {
			// If there are no links to check, wait 15 minutes.
			aioseoBrokenLinkChecker()->actionScheduler->scheduleSingle( $this->actionName, 15 * MINUTE_IN_SECONDS );

			return;
		}

		// If there are links to check, start a new scan.
		$this->startScan();
	}

	/**
	 * Start a scan and store the scan ID.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function startScan() {
		$requestBody = array_merge(
			$this->data->getBaseData(),
			[
				'links' => $this->data->getlinksToCheck()
			]
		);

		$response     = $this->doPostRequest( 'scan/start/', $requestBody );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $responseCode ) {
			aioseoBrokenLinkChecker()->actionScheduler->scheduleSingle( $this->actionName, DAY_IN_SECONDS + wp_rand( 60, 600 ) );

			return;
		}

		if ( 418 === $responseCode ) {
			aioseoBrokenLinkChecker()->actionScheduler->scheduleSingle( $this->actionName, HOUR_IN_SECONDS + wp_rand( 60, 600 ) );

			return;
		}

		$responseBody = json_decode( wp_remote_retrieve_body( $response ) );
		if (
			is_wp_error( $response ) ||
			200 !== $responseCode ||
			empty( $responseBody->success ) ||
			empty( $responseBody->scanId ) ||
			! isset( $responseBody->quotaRemaining )
		) {
			aioseoBrokenLinkChecker()->actionScheduler->scheduleSingle( $this->actionName, MINUTE_IN_SECONDS );

			return;
		}

		aioseoBrokenLinkChecker()->internalOptions->internal->scanId                  = $responseBody->scanId;
		aioseoBrokenLinkChecker()->internalOptions->internal->license->quotaRemaining = $responseBody->quotaRemaining;
		if ( aioseoBrokenLinkChecker()->internalOptions->internal->license->quota !== $responseBody->quota ) {
			// If the quota changed, reactivate the license to pull in the latest date from the marketing site.
			aioseoBrokenLinkChecker()->internalOptions->internal->license->quota = $responseBody->quota;
			aioseoBrokenLinkChecker()->license->activate();
		}

		aioseoBrokenLinkChecker()->actionScheduler->scheduleSingle( $this->actionName, MINUTE_IN_SECONDS );
	}

	/**
	 * Checks if the scan has been completed. If so, parses and stores the results.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function checkForScanResults() {
		$scanId = aioseoBrokenLinkChecker()->internalOptions->internal->scanId;
		if ( empty( $scanId ) ) {
			return;
		}

		$response     = $this->doPostRequest( "scan/{$scanId}/" );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $responseCode ) {
			aioseoBrokenLinkChecker()->actionScheduler->scheduleSingle( $this->actionName, DAY_IN_SECONDS + wp_rand( 60, 600 ) );

			return;
		}

		if ( 418 === $responseCode ) {
			aioseoBrokenLinkChecker()->actionScheduler->scheduleSingle( $this->actionName, HOUR_IN_SECONDS + wp_rand( 60, 600 ) );

			return;
		}

		$responseBody = json_decode( wp_remote_retrieve_body( $response ) );
		if ( is_wp_error( $response ) && 200 !== $responseCode || empty( $responseBody->success ) ) {
			// If the scan data cannot be found on the server, wipe the scan ID so the scan restarts.
			if ( ! empty( $responseBody->error ) && 'missing-scan-data' === strtolower( $responseBody->error ) ) {
				aioseoBrokenLinkChecker()->internalOptions->internal->scanId = '';
			}

			aioseoBrokenLinkChecker()->actionScheduler->scheduleSingle( $this->actionName, MINUTE_IN_SECONDS );

			return;
		}

		$this->parseResults( $responseBody );

		aioseoBrokenLinkChecker()->internalOptions->internal->license->quotaRemaining = $responseBody->quotaRemaining;
		if ( aioseoBrokenLinkChecker()->internalOptions->internal->license->quota !== $responseBody->quota ) {
			// If the quota changed, reactivate the license to pull in the latest date from the marketing site.
			aioseoBrokenLinkChecker()->internalOptions->internal->license->quota = $responseBody->quota;
			aioseoBrokenLinkChecker()->license->activate();
		}

		// Once the request is successful, we know the scan has been completed and we can go ahead and reset it.
		$this->doDeleteRequest( "scan/{$scanId}/" );
		aioseoBrokenLinkChecker()->internalOptions->internal->scanId = '';
	}

	/**
	 * Parse the results that came back from the server.
	 *
	 * @since 1.0.0
	 *
	 * @param  Object $responseBody The response body object.
	 * @return void
	 */
	private function parseResults( $responseBody ) {
		$scanData = json_decode( $responseBody->scanData );
		if ( empty( $scanData ) || empty( $scanData->urls ) ) {
			return;
		}

		foreach ( $scanData->urls as $url ) {
			$this->parseResultsHelper( $url );
		}
	}

	/**
	 * Helper function for parseResults().
	 *
	 * @since 1.0.0
	 *
	 * @param  Object $url The URL object.
	 * @return void
	 */
	public function parseResultsHelper( $url ) {
		$linkStatus = Models\LinkStatus::getByUrl( $url->url );
		if ( ! $linkStatus->exists() || empty( $url->data ) ) {
			return;
		}

		if ( empty( $url->data->status ) ) {
			$linkStatus->scanning         = false;
			$linkStatus->broken           = true;
			$linkStatus->http_status_code = null;
			$linkStatus->request_duration = 0;
			$linkStatus->final_url        = '';
			$linkStatus->scan_count       = $linkStatus->scan_count + 1;
			$linkStatus->last_scan_date   = aioseoBrokenLinkChecker()->helpers->timeToMysql( time() );
			$linkStatus->log              = [
				'error'   => ! empty( $url->data->error ) ? $url->data->error : '',
				'headers' => ! empty( $url->data->headers ) ? $url->data->headers : ''
			];

			if ( ! $linkStatus->first_failure ) {
				$linkStatus->first_failure = aioseoBrokenLinkChecker()->helpers->timeToMysql( time() );
			}

			$linkStatus->save();

			return;
		}

		$success       = (int) $url->data->status < 400;
		$redirectCount = count( $url->data->redirects );
		$finalUrl      = $redirectCount ? $url->data->redirects[ $redirectCount - 1 ] : '';

		$linkStatus->scanning         = false;
		$linkStatus->broken           = ! $success;
		$linkStatus->http_status_code = (int) $url->data->status;
		$linkStatus->redirect_count   = $redirectCount;
		$linkStatus->final_url        = $finalUrl;
		$linkStatus->request_duration = ! empty( $url->data->stats->loadTime ) ? abs( $url->data->stats->loadTime ) : 0;
		$linkStatus->scan_count       = $linkStatus->scan_count + 1;
		$linkStatus->last_scan_date   = aioseoBrokenLinkChecker()->helpers->timeToMysql( time() );
		$linkStatus->log              = [
			'error'   => ! empty( $url->data->error ) ? $url->data->error : '',
			'headers' => ! empty( $url->data->headers ) ? $url->data->headers : ''
		];

		if ( $success ) {
			$linkStatus->last_success  = aioseoBrokenLinkChecker()->helpers->timeToMysql( time() );
			$linkStatus->first_failure = null;
		} elseif ( ! $linkStatus->first_failure ) {
			$linkStatus->first_failure = aioseoBrokenLinkChecker()->helpers->timeToMysql( time() );
		}

		$linkStatus->save();
	}

	/**
	 * Returns the URL for the Broken Link Checker server.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL.
	 */
	public function getUrl() {
		if ( defined( 'AIOSEO_BROKEN_LINK_CHECKER_SCAN_URL' ) ) {
			return AIOSEO_BROKEN_LINK_CHECKER_SCAN_URL;
		}

		return $this->baseUrl;
	}

	/**
	 * Sends a POST request to the server.
	 *
	 * @since 1.0.0
	 *
	 * @param  string          $path        The path.
	 * @param  array           $requestBody The request body.
	 * @return array|\WP_Error              The response or WP_Error on failure.
	 */
	public function doPostRequest( $path, $requestBody = [] ) {
		$requestData = [
			'headers' => [
				'X-AIOSEO-BLC-License' => aioseoBrokenLinkChecker()->internalOptions->internal->license->licenseKey,
				'Content-Type'         => 'application/json'
			],
			'timeout' => 60
		];

		if ( ! empty( $requestBody ) ) {
			$requestData['body'] = wp_json_encode( $requestBody );
		}

		$baseUrl  = $this->getUrl();
		$response = wp_remote_post( $baseUrl . $path, $requestData );

		return $response;
	}

	/**
	 * Sends a DELETE request to the server.
	 *
	 * @since 1.0.0
	 *
	 * @param  string          $path The path.
	 * @return array|\WP_Error       The response or WP_Error on failure.
	 */
	public function doDeleteRequest( $path ) {
		$requestData = [
			'method'  => 'DELETE',
			'headers' => [
				'X-AIOSEO-BLC-License' => aioseoBrokenLinkChecker()->internalOptions->internal->license->licenseKey,
				'Content-Type'         => 'application/json'
			],
			'timeout' => 60
		];

		$baseUrl  = $this->getUrl();
		$response = wp_remote_request( $baseUrl . $path, $requestData );

		return $response;
	}
}