<?php
/**
 * Urls class.
 *
 * @since 2.2.0
 *
 * @package OMAPI
 * @author  Justin Sternberg
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Urls class.
 *
 * @since 2.2.0
 */
class OMAPI_Urls {

	/**
	 * Get the settings url.
	 *
	 * @since 2.2.0
	 *
	 * @param  array $args Array of query args.
	 *
	 * @return string
	 */
	public static function settings( $args = array() ) {
		return self::om_admin( 'settings', $args );
	}

	/**
	 * Get the campaigns url.
	 *
	 * @since 2.2.0
	 *
	 * @param  array $args Array of query args.
	 *
	 * @return string
	 */
	public static function campaigns( $args = array() ) {
		return self::om_admin( 'campaigns', $args );
	}

	/**
	 * Get the templates url.
	 *
	 * @since 2.2.0
	 *
	 * @param  array $args Array of query args.
	 *
	 * @return string
	 */
	public static function templates( $args = array() ) {
		return self::om_admin( 'templates', $args );
	}

	/**
	 * Get the playbooks url.
	 *
	 * @since 2.12.0
	 *
	 * @param  array $args Array of query args.
	 *
	 * @return string
	 */
	public static function playbooks( $args = array() ) {
		return self::om_admin( 'playbooks', $args );
	}

	/**
	 * Get the OM wizard url.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public static function wizard() {
		return self::dashboard( array( 'onboarding' => true ) );
	}

	/**
	 * Get the contextual OM dashboard url.
	 *
	 * @since 2.2.0
	 *
	 * @param  array $args Array of query args.
	 *
	 * @return string
	 */
	public static function dashboard( $args = array() ) {
		return self::om_admin( 'dashboard', $args );
	}

	/**
	 * Get the contextual OM university url.
	 *
	 * @since 2.13.8
	 *
	 * @param  array $args Array of query args.
	 *
	 * @return string
	 */
	public static function university( $args = array() ) {
		return self::om_admin( 'university', $args );
	}

	/**
	 * Get the campaign output settings edit url.
	 *
	 * @since 2.2.0
	 *
	 * @param  string $campaign_slug The campaign slug to edit.
	 * @param  array  $args Array of query args.
	 *
	 * @return string
	 */
	public static function campaign_output_settings( $campaign_slug, $args = array() ) {
		$args = array_merge( $args, array( 'campaignId' => $campaign_slug ) );

		return self::campaigns( $args );
	}

	/**
	 * Get the OM onboarding dashboard url.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public static function onboarding() {
		return self::wizard();
	}

	/**
	 * Get a link to an OM admin page.
	 *
	 * @since 2.2.0
	 *
	 * @param  string $page Page shortened slug.
	 * @param  array  $args Array of query args.
	 *
	 * @return string
	 */
	public static function om_admin( $page, $args ) {
		$defaults = array(
			'page' => 'optin-monster-' . $page,
		);

		return self::admin( wp_parse_args( $args, $defaults ) );
	}

	/**
	 * Get an admin page url.
	 *
	 * @since 2.2.0
	 *
	 * @param  array $args Array of query args.
	 *
	 * @return string
	 */
	public static function admin( $args = array() ) {
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		return esc_url_raw( $url );
	}

	/**
	 * Get app url, with proper query args set to ensure going to correct account, and setting return
	 * query arg to come back (if relevant on the destination page).
	 *
	 * @since 2.2.0
	 *
	 * @param  string $path The path on the app.
	 * @param  string $return_url Url to return. Will default to wp_get_referer().
	 *
	 * @return string        The app url.
	 */
	public static function om_app( $path, $return_url = '' ) {
		$app_url           = OPTINMONSTER_APP_URL . '/';
		$final_destination = $app_url . $path;

		if ( empty( $return_url ) ) {

			$return_url = wp_get_referer();
			if ( empty( $return_url ) ) {
				$return_url = self::dashboard();
			}
		}
		$return_url = rawurlencode( $return_url );

		$final_destination = add_query_arg( 'return', $return_url, $final_destination );

		$url = add_query_arg( 'redirect_to', rawurlencode( $final_destination ), $app_url );

		$account_id = OMAPI::get_instance()->get_option( 'accountUserId' );
		if ( ! empty( $account_id ) ) {
			$url = add_query_arg( 'accountId', $account_id, $url );
		}

		return $url;
	}

	/**
	 * Get upgrade url, with utm_medium param and optional feature.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $utm_medium The utm_medium query param.
	 * @param  string $feature    The feature to pass to the upgrade page.
	 * @param  string $return_url Url to return. Will default to wp_get_referer().
	 * @param  array  $args       Additional query args.
	 *
	 * @return string        The upgrade url.
	 */
	public static function upgrade( $utm_medium, $feature = 'none', $return_url = '', $args = array() ) {
		$args = self::upgrade_params( $utm_medium, $feature, $args );
		$path = add_query_arg( $args, 'account/wp-upgrade/' );

		return self::om_app( $path, $return_url );
	}

	/**
	 * Get the query args for the upgrade url.
	 *
	 * @since 2.15.0
	 *
	 * @param  string $utm_medium The utm_medium query param.
	 * @param  string $feature    The feature to pass to the upgrade page.
	 * @param  array  $args       Additional query args.
	 *
	 * @return array              The query args.
	 */
	public static function upgrade_params( $utm_medium, $feature = 'none', $args = array() ) {
		$defaults = wp_parse_args(
			self::get_partner_params( OPTINMONSTER_APP_URL . '/account/wp-upgrade/' ),
			array(
				'utm_source'   => 'WordPress',
				'utm_medium'   => $utm_medium,
				'utm_campaign' => 'Plugin',
				'feature'      => $feature,
			)
		);

		foreach ( $defaults as $key => $value ) {
			if ( null === $value ) {
				unset( $defaults[ $key ] );
			}
		}

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Get marketing url, with utm_medium params.
	 *
	 * @since 2.11.0
	 *
	 * @param  string $path The path on the app.
	 * @param  array  $args Additional query args.
	 *
	 * @return string        The marketing url.
	 */
	public static function marketing( $path = '', $args = array() ) {
		$url      = sprintf( OPTINMONSTER_URL . '/%1$s', $path );
		$defaults = wp_parse_args(
			self::get_partner_params( $url ),
			array(
				'utm_source'   => 'WordPress',
				'utm_medium'   => '',
				'utm_campaign' => 'Plugin',
			)
		);
		$args     = wp_parse_args( $args, $defaults );

		return add_query_arg( $args, $url );
	}

	/**
	 * Returns the API credentials for OptinMonster.
	 *
	 * @since 2.2.0
	 *
	 * @return string The API url to use for embedding on the page.
	 */
	public static function om_api() {
		$custom_api_url = OMAPI::get_instance()->get_option( 'customApiUrl' );
		return ! empty( $custom_api_url ) ? $custom_api_url : OPTINMONSTER_APIJS_URL;
	}

	/**
	 * Sets the partner id param if found, and parses the partner url for additional args to set.
	 *
	 * @since 2.15.0
	 *
	 * @param  string $destination_url The destination url to compare against.
	 *
	 * @return array                   The additional args.
	 */
	protected static function get_partner_params( $destination_url = '' ) {
		$args = array();

		// Add the partner id attribution query arg, if it exists.
		$id = OMAPI_Partners::get_id();
		if ( ! empty( $id ) ) {
			$args['sscid'] = $id;
		}

		// Next, let's parse the partner url for additional query args
		// stuffed on the urllink query arg redirect url.
		$partner_url = OMAPI_Partners::has_partner_url();
		if (
			! $partner_url
			|| false === strpos( $partner_url, 'urllink' )
		) {
			return $args;
		}

		// No params, no problem.
		$parsed = wp_parse_url( $partner_url );
		if ( empty( $parsed['query'] ) ) {
			return $args;
		}

		// No urllink param, do not pass go, do not collect $200.
		$query = wp_parse_args( $parsed['query'] );
		if ( empty( $query['urllink'] ) ) {
			return $args;
		}

		// Normalize the url.
		$url = urldecode( $query['urllink'] );
		$url = false === strpos( $url, 'http' )
			? 'https://' . $url
			: str_replace( 'http://', 'https://', $url );

		// Now let's make sure the url matches the destination url,
		// before we go attaching its query args.
		if (
			$destination_url
			&& rtrim( $destination_url, '/' ) && 0 !== stripos( $url, $destination_url )
		) {
			return $args;
		}

		// No args, do not pass go, do not collect $200.
		$bits = wp_parse_url( $url );
		if ( empty( $bits['query'] ) ) {
			return $args;
		}

		$query = wp_parse_args( $bits['query'] );
		if ( ! empty( $query ) ) {
			// Ok, let's add the found query args to the args array.
			$args = wp_parse_args( $query, $args );
		}

		return $args;
	}

	/**
	 * Filters the `allowed_redirect_hosts`.
	 *
	 * Adds the OptinMonster app and OptinMonster site to the allowed hosts.
	 *
	 * @since 2.16.3
	 *
	 * @param array $hosts Array of allowed hosts.
	 *
	 * @return array The allowed hosts.
	 */
	public static function allowed_redirect_hosts( $hosts = array() ) {
		if ( ! is_array( $hosts ) ) {
			$hosts = array();
		}

		$hosts[] = str_replace( 'https://', '', OPTINMONSTER_APP_URL );
		$hosts[] = str_replace( 'https://', '', OPTINMONSTER_URL );

		return $hosts;
	}
}
