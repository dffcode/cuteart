<?php
/**
 * Support Class, handles generating info for support.
 *
 * @since 1.9.10
 *
 * @package OMAPI
 * @author  Justin Sternberg
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rest Api class.
 *
 * @since 1.9.10
 */
class OMAPI_Support {

	/**
	 * The Base OMAPI Object
	 *
	 *  @since 1.9.10
	 *
	 * @var OMAPI
	 */
	protected $base;

	/**
	 * Class constructor.
	 *
	 * @since 1.9.10
	 */
	public function __construct() {
		$this->base = OMAPI::get_instance();
	}

	/**
	 * Combine Support data together.
	 *
	 * @since 1.9.10
	 *
	 * @param string $format The format to return the data in.
	 *
	 * @return array
	 */
	public function get_support_data( $format = 'raw' ) {
		return array(
			'server'    => $this->get_server_data( $format ),
			'settings'  => $this->get_settings_data( $format ),
			'campaigns' => $this->get_campaign_data( $format ),
		);
	}

	/**
	 * Build Current Optin data array to localize
	 *
	 * @since 1.9.10
	 *
	 * @param string $format The format to return the data in.
	 *
	 * @return array
	 */
	public function get_campaign_data( $format = 'raw' ) {

		$campaigns = $this->base->get_optins( array( 'post_status' => 'any' ) );
		$data      = array();

		if ( empty( $campaigns ) ) {
			return $data;
		}

		foreach ( (array) $campaigns as $campaign ) {
			if ( empty( $campaign->ID ) ) {
				continue;
			}

			$slug        = $campaign->post_name;
			$design_type = get_post_meta( $campaign->ID, '_omapi_type', true );

			$data[ $slug ] = array(
				'Campaign Type'                    => $design_type,
				'WordPress ID'                     => $campaign->ID,
				'Associated IDs'                   => get_post_meta( $campaign->ID, '_omapi_ids', true ),
				'Current Status'                   => get_post_meta( $campaign->ID, '_omapi_enabled', true ) ? 'Enabled' : 'Disabled',
				'User Settings'                    => get_post_meta( $campaign->ID, '_omapi_users', true ),
				'Pages to Never show on'           => get_post_meta( $campaign->ID, '_omapi_never', true ),
				'Pages to Only show on'            => get_post_meta( $campaign->ID, '_omapi_only', true ),
				'Categories'                       => get_post_meta( $campaign->ID, '_omapi_categories', true ),
				'Taxonomies'                       => get_post_meta( $campaign->ID, '_omapi_taxonomies', true ),
				'Template types to Show on'        => get_post_meta( $campaign->ID, '_omapi_show', true ),
				'Shortcodes Synced and Recognized' => get_post_meta( $campaign->ID, '_omapi_shortcode', true ) ? htmlspecialchars_decode( get_post_meta( $campaign->ID, '_omapi_shortcode_output', true ) ) : 'None recognized',
			);

			if ( OMAPI_Utils::is_inline_type( $design_type ) ) {
				$data[ $slug ]['Automatic Output Status']   = get_post_meta( $campaign->ID, '_omapi_automatic', true ) ? 'Enabled' : 'Disabled';
				$data[ $slug ]['Automatic Output Location'] = get_post_meta( $campaign->ID, '_omapi_auto_location', true );
			}

			if ( 'raw' === $format ) {
				$data[ $slug ]['raw'] = $this->base->collect_campaign_data( $campaign );
			}
		}

		return $data;
	}

	/**
	 * Build array of server information to localize
	 *
	 * @since 1.9.10
	 *
	 * @param string $format The format to return the data in.
	 *
	 * @return array
	 */
	public function get_server_data( $format = 'raw' ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$theme_data = wp_get_theme();
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$theme = 'raw' === $format
			? array(
				'Name'             => $theme_data->Name,
				'Author'           => $theme_data->Author,
				'Author Name'      => $theme_data->{'Author Name'},
				'Author URI'       => $theme_data->{'Author URI'},
				'Description'      => $theme_data->Description,
				'Version'          => $theme_data->Version,
				'Template'         => $theme_data->Template,
				'Stylesheet'       => $theme_data->Stylesheet,
				'Template Files'   => $theme_data->{'Template Files'},
				'Stylesheet Files' => $theme_data->{'Stylesheet Files'},
				'Template Dir'     => $theme_data->{'Template Dir'},
				'Stylesheet Dir'   => $theme_data->{'Stylesheet Dir'},
				'Screenshot'       => $theme_data->Screenshot,
				'Tags'             => $theme_data->Tags,
				'Theme Root'       => $theme_data->{'Theme Root'},
				'Theme Root URI'   => $theme_data->{'Theme Root URI'},
				'Parent Theme'     => $theme_data->{'Parent Theme'},
			)
			: $theme_data->Name . ' ' . $theme_data->Version;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$active_plugins = get_option( 'active_plugins', array() );
		$plugins        = 'raw' === $format ? array() : "\n";
		foreach ( get_plugins() as $plugin_path => $plugin ) {
			if ( ! in_array( $plugin_path, $active_plugins, true ) ) {
				continue;
			}

			if ( 'raw' === $format ) {
				$plugins[] = $plugin;
			} else {
				$plugins .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
			}
		}

		$api_ping = wp_remote_request( OPTINMONSTER_API_URL . '/v2/ping' );

		$array = array(
			'Plugin Version'      => esc_html( $this->base->version ),
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'Server Info'         => esc_html( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ),
			'PHP Version'         => function_exists( 'phpversion' ) ? esc_html( phpversion() ) : 'Unable to check.',
			'Error Log Location'  => function_exists( 'ini_get' ) ? ini_get( 'error_log' ) : 'Unable to locate.',
			'Default Timezone'    => date_default_timezone_get(),
			'Site Timezone'       => wp_timezone_string(),
			'Site Name'           => esc_html( get_option( 'blogname' ) ),
			'Admin Email'         => esc_html( get_site_option( 'admin_email' ) ),
			'WordPress Home URL'  => esc_url_raw( get_home_url() ),
			'WordPress Site URL'  => esc_url_raw( get_site_url() ),
			'WordPress REST URL'  => esc_url_raw( get_rest_url() ),
			'WordPress Admin URL' => esc_url_raw( OMAPI_Urls::admin() ),
			'WordPress Version'   => $GLOBALS['wp_version'],
			'Multisite'           => is_multisite(),
			'Language'            => get_locale(),
			'API Ping Response'   => wp_remote_retrieve_response_code( $api_ping ),
			'Active Theme'        => $theme,
			'Active Plugins'      => $plugins,
		);

		if ( 'raw' !== $format ) {
			$array['Multisite'] = $array['Multisite'] ? 'Multisite Enabled' : 'Not Multisite';
		}

		return $array;
	}

	/**
	 * Includes the plugin settings.
	 *
	 * @since 2.4.0
	 *
	 * @return array Array of plugin settings.
	 */
	public function get_settings_data() {
		$options = $this->base->get_option();

		// Remove the optins key. We don't need this in the settings data.
		unset( $options['optins'] );

		// List of keys to mask in the settings array.
		$sensitive_keys = array(
			array( 'api', 'apikey' ),
			array( 'api', 'key' ),
			array( 'api', 'user' ),
			array( 'edd', 'key' ),
			array( 'edd', 'token' ),
			array( 'woocommerce', 'key_id' ),
		);

		/**
		 * Filters the extra keys array, allowing additional keys to be added.
		 *
		 * @since 2.16.3
		 *
		 * @param array $extra_keys The list of sensitive keys. Defaults to an empty array.
		 */
		$extra_keys = (array) apply_filters( 'optin_monster_redacted_sensitive_keys', array() );

		$this->mask_sensitive_data_recursive( $options, array_merge( $sensitive_keys, $extra_keys ) );

		return $options;
	}

	/**
	 * Recursively mask sensitive data in an array.
	 *
	 * @since 2.16.3
	 *
	 * @param array $data           The data array.
	 * @param array $sensitive_keys The list of sensitive keys.
	 *
	 * @return void
	 */
	public function mask_sensitive_data_recursive( &$data, $sensitive_keys = array() ) {
		foreach ( $sensitive_keys as $path ) {
			$ref        = &$data;
			$path_count = 0;

			foreach ( (array) $path as $key ) {
				$path_count++;

				// If the key doesn't exist, break out of the loop.
				if ( ! isset( $ref[ $key ] ) ) {
					break;
				}

				// Set a reference to the next level of the array.
				$ref = &$ref[ $key ];

				// If we're at the end of the path array, mask the value.
				if ( count( $path ) === $path_count && ! empty( $ref ) ) {
					$ref = substr( (string) $ref, 0, 2 )
						. str_repeat( '*', strlen( (string) $ref ) - 4 )
						. substr( (string) $ref, -2 );
				}
			}
		}
	}
}
