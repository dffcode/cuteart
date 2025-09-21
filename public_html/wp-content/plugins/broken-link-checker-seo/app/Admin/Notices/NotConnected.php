<?php
namespace AIOSEO\BrokenLinkChecker\Admin\Notices;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Not Connected notice.
 *
 * @since 1.2.1
 */
class NotConnected {
	/**
	 * Class constructor.
	 *
	 * @since 1.2.1
	 */
	public function __construct() {
		add_action( 'wp_ajax_aioseo-blc-dismiss-not-connected', [ $this, 'dismissNotice' ] );
	}

	/**
	 * Go through all the checks to see if we should show the notice.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function maybeShowNotice() {
		// Don't show to users that cannot interact with the plugin.
		if ( ! current_user_can( 'edit_posts' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( aioseoBrokenLinkChecker()->admin->isBlcScreen() ) {
			return;
		}

		// Make sure the user is not connected/licensed.
		if ( aioseoBrokenLinkChecker()->license->isActive() ) {
			return;
		}

		$dismissed = get_user_meta( get_current_user_id(), '_aioseo_blc_not_connected', true );
		if ( ! empty( $dismissed ) && $dismissed > time() ) {
			return;
		}

		$this->showNotice();

		add_action( 'admin_footer', [ $this, 'printScript' ] );
	}

	/**
	 * Actually show the review plugin 2.0.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function showNotice() {
		$string = sprintf(
			// Translators: 1 - The plugin name ("Broken Link Checker").
			__( 'Your site is not connected with %1$s. %2$sConnect now%3$s to start scanning for broken links and fix them to improve your SEO.', 'aioseo-broken-link-checker' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
			'<strong>' . esc_html( AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_NAME ) . '</strong>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=broken-link-checker#/settings' ) ) . '">',
			'</a>'
		);

		?>
		<div class="notice notice-error aioseo-blc-not-connected is-dismissible">
			<div class="step-3">
				<p><?php echo $string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Dismiss the notice.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function dismissNotice() {
		if ( ! isset( $_POST['action'] ) || 'aioseo-blc-dismiss-not-connected' !== $_POST['action'] ) {
			return;
		}

		check_ajax_referer( 'aioseo-blc-dismiss-not-connected', 'nonce' );
		update_user_meta( get_current_user_id(), '_aioseo_blc_not_connected', strtotime( '+1 week' ) );

		wp_send_json_success();
	}

	/**
	 * Print the script for dismissing the notice.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function printScript() {
		// Create a nonce.
		$nonce = wp_create_nonce( 'aioseo-blc-dismiss-not-connected' );
		?>
		<style>
			@keyframes dismissBtnVisible {
				from { opacity: 0.99; }
				to { opacity: 1; }
			}
			.aioseo-blc-not-connected button.notice-dismiss {
				animation-duration: 0.001s;
				animation-name: dismissBtnVisible;
			}
		</style>
		<script>
			window.addEventListener('load', function () {
				dismissNotice = function (dismissBtn) {
					dismissBtn.addEventListener('click', function (event) {
						var httpRequest = new XMLHttpRequest(),
							postData    = ''

						// Build the data to send in our request.
						postData += '&action=aioseo-blc-dismiss-not-connected'
						postData += '&nonce=<?php echo esc_html( $nonce ); ?>'

						httpRequest.open('POST', '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>')
						httpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
						httpRequest.send(postData)
					})
				}

				dismissBtn = document.querySelector('.aioseo-blc-not-connected .notice-dismiss')
				dismissNotice(dismissBtn)
			});
		</script>
		<?php
	}
}