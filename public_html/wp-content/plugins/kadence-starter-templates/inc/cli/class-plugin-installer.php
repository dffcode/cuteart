<?php
/**
 * Plugin Installer Class
 *
 * @package KadenceWP\KadenceStarterTemplates
 */

namespace KadenceWP\KadenceStarterTemplates\CLI;

use KadenceWP\KadenceStarterTemplates\Plugin_Check;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Installer Class
 */
class Plugin_Installer {

	/**
	 * Install plugins
	 *
	 * @param array $plugins The plugins to install.
	 * @return bool|\WP_Error
	 */
	public function install_plugins( $plugins ) {
		$allowed_plugins = $this->get_allowed_plugins();
		foreach ( $plugins as $plugin_slug ) {
			if ( ! isset( $plugin_slug ) || ! isset( $allowed_plugins[ $plugin_slug ]['path'] ) ) {
				continue;
			}
			$path = $allowed_plugins[ $plugin_slug ]['path'];
			$state = Plugin_Check::active_check( $path );
			$base = $allowed_plugins[ $plugin_slug ]['base'];
			$install = true;
			if ( 'notactive' === $state ) {
				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				$all_plugins = get_plugins();
				$plugin_path = '';
				foreach ( $all_plugins as $plugin_file => $plugin_data ) {
					if ( strpos( $plugin_file, $base . '/' ) === 0 || $plugin_file === $base ) {
						$plugin_path = $plugin_file;
						break;
					}
				}
				if ( $plugin_path ) {
					$path = $plugin_path;
					$silent = ( 'give' === $base || 'elementor' === $base || 'wp-smtp' === $base || 'fluentform' === $base || 'restrict-content' === $base ? false : true );
					if ( 'give' === $base ) {
						add_option( 'give_install_pages_created', 1, '', false );
					}
					if ( 'restrict-content' === $base ) {
						update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
					}
					$activate = activate_plugin( $path, '', false, $silent );
					if ( is_wp_error( $activate ) ) {
						$install = false;
					}
				} else {
					if ( ! function_exists( 'plugins_api' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
					}
					if ( ! function_exists( 'install_plugin_information' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					if ( ! function_exists( 'request_filesystem_credentials' ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
					}
					if ( ! function_exists( 'get_file_data' ) ) {
						require_once ABSPATH . 'wp-admin/includes/misc.php';
					}
					if ( ! class_exists( 'WP_Upgrader' ) ) {
						require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
					}
					$api = plugins_api(
						'plugin_information',
						array(
							'slug' => $base,
							'fields' => array(
								'short_description' => false,
								'sections' => false,
								'requires' => false,
								'rating' => false,
								'ratings' => false,
								'downloaded' => false,
								'last_updated' => false,
								'added' => false,
								'tags' => false,
								'compatibility' => false,
								'homepage' => false,
								'donate_link' => false,
							),
						)
					);
					if ( ! is_wp_error( $api ) ) {
						$download_link = $api->download_link;
						if ( 'kadence-starter-templates' === $base ) {
							$download_link = 'https://downloads.wordpress.org/plugin/kadence-starter-templates.latest-stable.zip';
						}
						$skin = new \WP_Ajax_Upgrader_Skin();
						$upgrader = new \Plugin_Upgrader( $skin );
						$result = $upgrader->install( $download_link );
						if ( ! is_wp_error( $result ) && ! is_wp_error( $skin->result ) && ! $skin->get_errors() && $result ) {
							$plugin_file = $upgrader->plugin_info();
							if ( $plugin_file ) {
								$path = $plugin_file;
								if ( 'give' === $base ) {
									add_option( 'give_install_pages_created', 1, '', false );
								}
								if ( 'restrict-content' === $base ) {
									update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
								}
								$silent = ( 'give' === $base || 'elementor' === $base || 'wp-smtp' === $base || 'fluentform' === $base || 'restrict-content' === $base ? false : true );
								$activate = activate_plugin( $path, '', false, $silent );
								if ( is_wp_error( $activate ) ) {
									$install = false;
								}
							} else {
								$install = false;
							}
						} else {
							$install = false;
						}
					} else {
						$install = false;
					}
				}
			} elseif ( 'installed' === $state ) {
				$silent = ( 'give' === $base || 'elementor' === $base || 'wp-smtp' === $base || 'fluentform' === $base || 'restrict-content' === $base ? false : true );
				if ( 'give' === $base ) {
					// Make sure give doesn't add it's pages, prevents having two sets.
					update_option( 'give_install_pages_created', 1, '', false );
				}
				if ( 'restrict-content' === $base ) {
					$silent = true;
					update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
				}
				$activate = activate_plugin( $path, '', false, $silent );
				if ( is_wp_error( $activate ) ) {
					$install = false;
				}
			}
			if ( 'give' === $base ) {
				update_option( 'give_version_upgraded_from', '2.13.2' );
			}
			if ( 'kadence-pro' === $base ) {
				$enabled = json_decode( get_option( 'kadence_pro_theme_config' ), true );
				$enabled['elements'] = true;
				update_option( 'kadence_pro_theme_config', json_encode( $enabled ) );
			}
		}
		return true;
	}

	/**
	 * Get the allowed plugins.
	 *
	 * @access private
	 * @return array
	 */
	private function get_allowed_plugins() {
		$importer_plugins = array(
			'woocommerce' => array(
				'title' => 'Woocommerce',
				'base'  => 'woocommerce',
				'slug'  => 'woocommerce',
				'path'  => 'woocommerce/woocommerce.php',
				'src'   => 'repo',
			),
			'elementor' => array(
				'title' => 'Elementor',
				'base'  => 'elementor',
				'slug'  => 'elementor',
				'path'  => 'elementor/elementor.php',
				'src'   => 'repo',
			),
			'kadence-blocks' => array(
				'title' => 'Kadence Blocks',
				'base'  => 'kadence-blocks',
				'slug'  => 'kadence-blocks',
				'path'  => 'kadence-blocks/kadence-blocks.php',
				'src'   => 'repo',
			),
			'kadence-blocks-pro' => array(
				'title' => 'Kadence Block Pro',
				'base'  => 'kadence-blocks-pro',
				'slug'  => 'kadence-blocks-pro',
				'path'  => 'kadence-blocks-pro/kadence-blocks-pro.php',
				'src'   => 'bundle',
			),
			'kadence-pro' => array(
				'title' => 'Kadence Pro',
				'base'  => 'kadence-pro',
				'slug'  => 'kadence-pro',
				'path'  => 'kadence-pro/kadence-pro.php',
				'src'   => 'bundle',
			),
			'fluentform' => array(
				'title' => 'Fluent Forms',
				'src'   => 'repo',
				'base'  => 'fluentform',
				'slug'  => 'fluentform',
				'path'  => 'fluentform/fluentform.php',
			),
			'wpzoom-recipe-card' => array(
				'title' => 'Recipe Card Blocks by WPZOOM',
				'base'  => 'recipe-card-blocks-by-wpzoom',
				'slug'  => 'wpzoom-recipe-card',
				'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
				'src'   => 'repo',
			),
			'recipe-card-blocks-by-wpzoom' => array(
				'title' => 'Recipe Card Blocks by WPZOOM',
				'base'  => 'recipe-card-blocks-by-wpzoom',
				'slug'  => 'wpzoom-recipe-card',
				'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
				'src'   => 'repo',
			),
			'learndash' => array(
				'title' => 'LearnDash',
				'base'  => 'sfwd-lms',
				'slug'  => 'sfwd_lms',
				'path'  => 'sfwd-lms/sfwd_lms.php',
				'src'   => 'thirdparty',
			),
			'sfwd-lms' => array(
				'title' => 'LearnDash',
				'base'  => 'sfwd-lms',
				'slug'  => 'sfwd_lms',
				'path'  => 'sfwd-lms/sfwd_lms.php',
				'src'   => 'thirdparty',
			),
			'learndash-course-grid' => array(
				'title' => 'LearnDash Course Grid Addon',
				'base'  => 'learndash-course-grid',
				'slug'  => 'learndash_course_grid',
				'path'  => 'learndash-course-grid/learndash_course_grid.php',
				'src'   => 'thirdparty',
			),
			'lifterlms' => array(
				'title' => 'LifterLMS',
				'base'  => 'lifterlms',
				'slug'  => 'lifterlms',
				'path'  => 'lifterlms/lifterlms.php',
				'src'   => 'repo',
			),
			'tutor' => array(
				'title' => 'Tutor LMS',
				'base'  => 'tutor',
				'slug'  => 'tutor',
				'path'  => 'tutor/tutor.php',
				'src'   => 'repo',
			),
			'give' => array(
				'title' => 'GiveWP',
				'base'  => 'give',
				'slug'  => 'give',
				'path'  => 'give/give.php',
				'src'   => 'repo',
			),
			'the-events-calendar' => array(
				'title' => 'The Events Calendar',
				'base'  => 'the-events-calendar',
				'slug'  => 'the-events-calendar',
				'path'  => 'the-events-calendar/the-events-calendar.php',
				'src'   => 'repo',
			),
			'event-tickets' => array(
				'title' => 'Event Tickets',
				'base'  => 'event-tickets',
				'slug'  => 'event-tickets',
				'path'  => 'event-tickets/event-tickets.php',
				'src'   => 'repo',
			),
			'orderable' => array(
				'title' => 'Orderable',
				'base'  => 'orderable',
				'slug'  => 'orderable',
				'path'  => 'orderable/orderable.php',
				'src'   => 'repo',
			),
			'restrict-content' => array(
				'title' => 'Restrict Content',
				'base'  => 'restrict-content',
				'slug'  => 'restrictcontent',
				'path'  => 'restrict-content/restrictcontent.php',
				'src'   => 'repo',
			),
			'kadence-woo-extras' => array(
				'title' => 'Kadence Shop Kit',
				'base'  => 'kadence-woo-extras',
				'slug'  => 'kadence-woo-extras',
				'path'  => 'kadence-woo-extras/kadence-woo-extras.php',
				'src'   => 'bundle',
			),
			'depicter' => array(
				'title' => 'Depicter Slider',
				'base'  => 'depicter',
				'slug'  => 'depicter',
				'path'  => 'depicter/depicter.php',
				'src'   => 'repo',
			),
			'bookit' => array(
				'title' => 'Bookit',
				'base'  => 'bookit',
				'slug'  => 'bookit',
				'path'  => 'bookit/bookit.php',
				'src'   => 'repo',
			),
			'kadence-woocommerce-email-designer' => array(
				'title' => 'Kadence Woocommerce Email Designer',
				'base'  => 'kadence-woocommerce-email-designer',
				'slug'  => 'kadence-woocommerce-email-designer',
				'path'  => 'kadence-woocommerce-email-designer/kadence-woocommerce-email-designer.php',
				'src'   => 'repo',
			),
			'seriously-simple-podcasting' => array(
				'title' => 'Seriously Simple Podcasting',
				'base'  => 'seriously-simple-podcasting',
				'slug'  => 'seriously-simple-podcasting',
				'path'  => 'seriously-simple-podcasting/seriously-simple-podcasting.php',
				'src'   => 'repo',
			),
			'better-wp-security' => array(
				'title' => 'Solid Security',
				'base'  => 'better-wp-security',
				'slug'  => 'better-wp-security',
				'path'  => 'better-wp-security/better-wp-security.php',
				'src'   => 'repo',
			),
			'ithemes-security-pro' => array(
				'title' => 'Solid Security Pro',
				'base'  => 'ithemes-security-pro',
				'slug'  => 'ithemes-security-pro',
				'path'  => 'ithemes-security-pro/ithemes-security-pro.php',
				'src'   => 'thirdparty',
			),
			'wp-smtp' => array(
				'title' => 'Solid Mail',
				'base'  => 'wp-smtp',
				'slug'  => 'wp-smtp',
				'path'  => 'wp-smtp/wp-smtp.php',
				'src'   => 'repo',
			),
			'solid-performance' => array(
				'title' => 'Solid Performance',
				'base'  => 'solid-performance',
				'slug'  => 'solid-performance',
				'path'  => 'solid-performance/solid-performance.php',
				'src'   => 'repo',
			),
		);
		return $importer_plugins;
	}
} 