<?php
namespace AIOSEO\BrokenLinkChecker\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\BrokenLinkChecker\Models;

/**
 * Handles our notifications.
 *
 * @since 1.0.0
 */
class Notifications {
	/**
	 * The URL of the notifications endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $url = 'https://blc-plugin-cdn.aioseo.com/wp-content/notifications.json';

	/**
	 * The review notice class instance.
	 *
	 * @since 1.2.0
	 *
	 * @var Notices\Review
	 */
	private $reviewNotice;

	/**
	 * The Not Connected notice class instance.
	 *
	 * @since 1.2.1
	 *
	 * @var Notices\NotConnected
	 */
	private $notConnectedNotice;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'aioseo_blc_admin_notifications_update', [ $this, 'update' ] );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'init', [ $this, 'init' ], 2 );
		add_action( 'admin_notices', [ $this, 'renderNotices' ] );
	}

	/**
	 * Initialize notifications.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		// If our tables do not exist, create them now.
		if ( ! aioseoBrokenLinkChecker()->core->db->tableExists( 'aioseo_blc_notifications' ) ) {
			aioseoBrokenLinkChecker()->updates->addInitialTables();

			return;
		}

		$this->checkForUpdates();

		$this->notConnectedNotice = new Notices\NotConnected();
		$this->reviewNotice       = new Notices\Review();
	}

	/**
	 * Renders the notices.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function renderNotices() {
		if ( ! is_admin() ) {
			return;
		}

		$this->notConnectedNotice->maybeShowNotice();
		$this->reviewNotice->maybeShowNotice();
	}

	/**
	 * Checks if we should update our notifications.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function checkForUpdates() {
		$nextRun = aioseoBrokenLinkChecker()->core->cache->get( 'admin_notifications_update' );
		if ( null !== $nextRun && time() < $nextRun ) {
			return;
		}

		aioseoBrokenLinkChecker()->actionScheduler->scheduleAsync( 'aioseo_blc_admin_notifications_update' );
		aioseoBrokenLinkChecker()->core->cache->update( 'admin_notifications_update', time() + DAY_IN_SECONDS );
	}

	/**
	 * Pulls in the notifications from our remote endpoint and stores them in the DB.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function update() {
		$notifications = $this->fetch();
		if ( empty( $notifications ) ) {
			return;
		}

		foreach ( $notifications as $notification ) {
			// First, let's check to see if the notification exists. If so, we want to override it.
			$aioseoNotification = aioseoBrokenLinkChecker()->core->db
				->start( 'aioseo_blc_notifications' )
				->where( 'notification_id', $notification->id )
				->run()
				->model( 'AIOSEO\\BrokenLinkChecker\\Models\\Notification' );

			$buttons = [
				'button1' => [
					'label' => ! empty( $notification->btns->main->text ) ? sanitize_text_field( $notification->btns->main->text ) : null,
					'url'   => ! empty( $notification->btns->main->url ) ? esc_url_raw( $notification->btns->main->url ) : null
				],
				'button2' => [
					'label' => ! empty( $notification->btns->alt->text ) ? sanitize_text_field( $notification->btns->alt->text ) : null,
					'url'   => ! empty( $notification->btns->alt->url ) ? esc_url_raw( $notification->btns->alt->url ) : null
				]
			];

			if ( ! $aioseoNotification->exists() ) {
				$aioseoNotification            = new Models\Notification();
				$aioseoNotification->slug      = uniqid();
				$aioseoNotification->dismissed = 0;
			}

			$aioseoNotification->notification_id = $notification->id;
			$aioseoNotification->title           = sanitize_text_field( $notification->title );
			$aioseoNotification->content         = sanitize_text_field( $notification->content );
			$aioseoNotification->type            = ! empty( $notification->notification_type ) ? sanitize_text_field( $notification->notification_type ) : 'info';
			$aioseoNotification->level           = $notification->type;
			$aioseoNotification->start           = ! empty( $notification->start ) ? sanitize_text_field( $notification->start ) : null;
			$aioseoNotification->end             = ! empty( $notification->end ) ? sanitize_text_field( $notification->end ) : null;
			$aioseoNotification->button1_label   = $buttons['button1']['label'];
			$aioseoNotification->button1_action  = $buttons['button1']['url'];
			$aioseoNotification->button2_label   = $buttons['button2']['label'];
			$aioseoNotification->button2_action  = $buttons['button2']['url'];

			$aioseoNotification->save();

			// Trigger the drawer to open.
			aioseoBrokenLinkChecker()->core->cache->update( 'show_notifications_drawer', true );
		}
	}

	/**
	 * Pulls in the notifications from the remote feed.
	 *
	 * @since 1.0.0
	 *
	 * @return array A list of notifications.
	 */
	private function fetch() {
		$response = aioseoBrokenLinkChecker()->helpers->wpRemoteGet( $this->getUrl() );
		if ( is_wp_error( $response ) ) {
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return [];
		}

		$notifications = json_decode( $body );
		if ( empty( $notifications ) ) {
			return [];
		}

		return $this->verify( $notifications );
	}

	/**
	 * Verifies a notification to see if it's valid before it is stored.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $notifications List of notifications items to verify.
	 * @return array                List of verified notifications.
	 */
	private function verify( $notifications ) {
		if ( ! is_array( $notifications ) || empty( $notifications ) ) {
			return [];
		}

		$data = [];
		foreach ( $notifications as $notification ) {
			// The content and type should never be empty. If they are, ignore the notification.
			if ( empty( $notification->content ) || empty( $notification->type ) ) {
				continue;
			}

			if ( ! is_array( $notification->type ) ) {
				$notification->type = [ $notification->type ];
			}

			foreach ( $notification->type as $type ) {
				$type = sanitize_text_field( $type );

				// Ignore the notification if not a single type matches.
				if ( ! $this->validateType( $type ) ) {
					continue 2;
				}
			}

			// Ignore the notification if it already expired.
			if ( ! empty( $notification->end ) && time() > strtotime( $notification->end ) ) {
				continue;
			}

			// Ignore the notification if it existed before installing Broken Link Checker.
			// Prevents spamming the user with notifications after activation.
			$activated = aioseoBrokenLinkChecker()->internalOptions->internal->firstActivated( time() );
			if ( ! empty( $notification->start ) && $activated > strtotime( $notification->start ) ) {
				continue;
			}

			$data[] = $notification;
		}

		return $data;
	}

	/**
	 * Validates the notification type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $type The notification type we are targeting.
	 * @return bool          Whether the notification is valid.
	 */
	public function validateType( $type ) {
		if ( 'all' === $type ) {
			return true;
		}

		// If we are targeting unlicensed users.
		if ( 'free' === $type && ! aioseoBrokenLinkChecker()->license->isActive() ) {
			return true;
		}

		// If we are targeting licensed users.
		if ( 'licensed' === $type && aioseoBrokenLinkChecker()->license->isActive() ) {
			return true;
		}

		// Store notice if version matches.
		if ( $this->versionMatch( aioseoBrokenLinkChecker()->version, $type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether two versions are equal.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $currentVersion The current version being used.
	 * @param  string|array $compareVersion The version to compare with.
	 * @return bool                         Whether it is a match.
	 */
	private function versionMatch( $currentVersion, $compareVersion ) {
		if ( is_array( $compareVersion ) ) {
			foreach ( $compareVersion as $compare_single ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
				$recursiveResult = $this->versionMatch( $currentVersion, $compare_single ); // phpcs:ignore Squiz.NamingConventions.ValidVariableName
				if ( $recursiveResult ) {
					return true;
				}
			}

			return false;
		}

		$currentParse = explode( '.', $currentVersion );
		if ( strpos( $compareVersion, '-' ) ) {
			$compareParse = explode( '-', $compareVersion );
		} elseif ( strpos( $compareVersion, '.' ) ) {
			$compareParse = explode( '.', $compareVersion );
		} else {
			return false;
		}

		$currentCount = count( $currentParse );
		$compareCount = count( $compareParse );
		for ( $i = 0; $i < $currentCount || $i < $compareCount; $i++ ) {
			if ( isset( $compareParse[ $i ] ) && 'x' === strtolower( $compareParse[ $i ] ) ) {
				unset( $compareParse[ $i ] );
			}

			if ( ! isset( $currentParse[ $i ] ) ) {
				unset( $compareParse[ $i ] );
			} elseif ( ! isset( $compareParse[ $i ] ) ) {
				unset( $currentParse[ $i ] );
			}
		}

		foreach ( $compareParse as $index => $subNumber ) {
			if ( $currentParse[ $index ] !== $subNumber ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Returns the URL for the notifications endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL.
	 */
	private function getUrl() {
		if ( defined( 'AIOSEO_BROKEN_LINK_CHECKER_NOTIFICATIONS_URL' ) ) {
			return AIOSEO_BROKEN_LINK_CHECKER_NOTIFICATIONS_URL;
		}

		return $this->url;
	}

	/**
	 * Extends a notice by a (default) 1 week start date.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $notice The notice name.
	 * @param  string $start  How long to extend the notice.
	 * @return void
	 */
	public function remindMeLater( $notice, $start = '+1 week' ) {
		$notification = Models\Notification::getNotificationByName( $notice );
		if ( ! $notification->exists() ) {
			return;
		}

		$notification->start = gmdate( 'Y-m-d H:i:s', strtotime( $start ) );
		$notification->save();
	}
}