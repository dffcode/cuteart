<?php
namespace AIOSEO\BrokenLinkChecker\Admin\Notices;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Review plugin notice.
 *
 * @since 1.2.0
 */
class Review {
	/**
	 * Class constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_aioseo-blc-dismiss-review-plugin-cta', [ $this, 'dismissNotice' ] );
	}

	/**
	 * Go through all the checks to see if we should show the notice.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function maybeShowNotice() {
		// Don't show to users that cannot interact with the plugin.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( aioseoBrokenLinkChecker()->admin->isBlcScreen() ) {
			return;
		}

		// Make sure the user has connected/is licensed.
		if ( ! aioseoBrokenLinkChecker()->license->isActive() ) {
			return;
		}

		$dismissed = get_user_meta( get_current_user_id(), '_aioseo_blc_plugin_review_dismissed', true );
		if ( '3' === $dismissed || '4' === $dismissed ) {
			return;
		}

		if ( ! empty( $dismissed ) && $dismissed > time() ) {
			return;
		}

		// Show once plugin has been active for 2 weeks.
		if ( ! aioseoBrokenLinkChecker()->internalOptions->internal->firstActivated ) {
			aioseoBrokenLinkChecker()->internalOptions->internal->firstActivated = time();
		}

		$activated = aioseoBrokenLinkChecker()->internalOptions->internal->firstActivated( time() );
		if ( $activated > strtotime( '-2 weeks' ) ) {
			return;
		}

		$this->showNotice();

		// Print the script to the footer.
		add_action( 'admin_footer', [ $this, 'printScript' ] );
	}

	/**
	 * Actually show the review plugin 2.0.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function showNotice() {
		$string1 = sprintf(
			// Translators: 1 - The plugin name ("Broken Link Checker").
			__( 'Hey, we noticed you have been using %1$s for some time - that’s awesome! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'aioseo-broken-link-checker' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
			'<strong>' . esc_html( AIOSEO_BROKEN_LINK_CHECKER_PLUGIN_NAME ) . '</strong>'
		);

		// Translators: 1 - The plugin name ("Broken Link Checker").
		$string9  = __( 'Ok, you deserve it', 'aioseo-broken-link-checker' );
		$string10 = __( 'Nope, maybe later', 'aioseo-broken-link-checker' );
		$string11 = __( 'I already did', 'aioseo-broken-link-checker' );

		?>
		<div class="notice notice-info aioseo-blc-review-plugin-cta is-dismissible">
			<div class="step-3">
				<p><?php echo $string1; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<p>
					<?php // phpcs:ignore Generic.Files.LineLength.MaxExceeded ?>
					<a href="https://wordpress.org/support/plugin/broken-link-checker-seo/reviews/?filter=5#new-post" class="aioseo-blc-dismiss-review-notice" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $string9 ); ?>
					</a>&nbsp;&bull;&nbsp;
					<a href="#" class="aioseo-blc-dismiss-review-notice-delay" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $string10 ); ?>
					</a>&nbsp;&bull;&nbsp;
					<a href="#" class="aioseo-blc-dismiss-review-notice" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $string11 ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Print the script for dismissing the notice.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function printScript() {
		// Create a nonce.
		$nonce = wp_create_nonce( 'aioseo-blc-dismiss-review' );
		?>
		<style>
			@keyframes dismissBtnVisible {
				from { opacity: 0.99; }
				to { opacity: 1; }
			}
			.aioseo-blc-review-plugin-cta button.notice-dismiss {
				animation-duration: 0.001s;
				animation-name: dismissBtnVisible;
			}
		</style>
		<script>
			window.addEventListener('load', function () {
				var aioseoBlcSetupButton,
					dismissBtn,
					interval

				aioseoBlcSetupButton = function (dismissBtn) {
					var notice      = document.querySelector('.notice.aioseo-blc-review-plugin-cta'),
						delay       = false,
						relay       = true,
						stepOne     = notice.querySelector('.step-1'),
						stepTwo     = notice.querySelector('.step-2'),
						stepThree   = notice.querySelector('.step-3')

					// Add an event listener to the dismiss button.
					dismissBtn.addEventListener('click', function (event) {
						var httpRequest = new XMLHttpRequest(),
							postData    = ''

						// Build the data to send in our request.
						postData += '&delay=' + delay
						postData += '&relay=' + relay
						postData += '&action=aioseo-blc-dismiss-review-plugin-cta'
						postData += '&nonce=<?php echo esc_html( $nonce ); ?>'

						httpRequest.open('POST', '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>')
						httpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
						httpRequest.send(postData)
					})

					notice.addEventListener('click', function (event) {
						if (event.target.matches('.aioseo-blc-review-switch-step-3')) {
							event.preventDefault()
							stepOne.style.display   = 'none'
							stepTwo.style.display   = 'none'
							stepThree.style.display = 'block'
						}
						if (event.target.matches('.aioseo-blc-review-switch-step-2')) {
							event.preventDefault()
							stepOne.style.display   = 'none'
							stepThree.style.display = 'none'
							stepTwo.style.display   = 'block'
						}
						if (event.target.matches('.aioseo-blc-dismiss-review-notice-delay')) {
							event.preventDefault()
							delay = true
							relay = false
							dismissBtn.click()
						}
						if (event.target.matches('.aioseo-blc-dismiss-review-notice')) {
							if ('#' === event.target.getAttribute('href')) {
								event.preventDefault()
							}
							relay = false
							dismissBtn.click()
						}
					})
				}

				dismissBtn = document.querySelector('.aioseo-blc-review-plugin-cta .notice-dismiss')
				if (!dismissBtn) {
					document.addEventListener('animationstart', function (event) {
						if (event.animationName == 'dismissBtnVisible') {
							dismissBtn = document.querySelector('.aioseo-blc-review-plugin-cta .notice-dismiss')
							if (dismissBtn) {
								aioseoBlcSetupButton(dismissBtn)
							}
						}
					}, false)

				} else {
					aioseoBlcSetupButton(dismissBtn)
				}
			});
		</script>
		<?php
	}

	/**
	 * Dismiss the review plugin CTA.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function dismissNotice() {
		// Early exit if we're not on a aioseo-blc-dismiss-review-plugin-cta action.
		if ( ! isset( $_POST['action'] ) || 'aioseo-blc-dismiss-review-plugin-cta' !== $_POST['action'] ) {
			return;
		}

		check_ajax_referer( 'aioseo-blc-dismiss-review', 'nonce' );
		$delay = isset( $_POST['delay'] ) ? 'true' === wp_unslash( $_POST['delay'] ) : false; // phpcs:ignore HM.Security.ValidatedSanitizedInput.InputNotSanitized
		$relay = isset( $_POST['relay'] ) ? 'true' === wp_unslash( $_POST['relay'] ) : false; // phpcs:ignore HM.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $delay ) {
			update_user_meta( get_current_user_id(), '_aioseo_blc_plugin_review_dismissed', $relay ? '4' : '3' );

			wp_send_json_success();

			return;
		}

		update_user_meta( get_current_user_id(), '_aioseo_blc_plugin_review_dismissed', strtotime( '+1 week' ) );

		wp_send_json_success();
	}
}