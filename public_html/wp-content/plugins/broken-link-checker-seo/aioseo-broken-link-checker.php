<?php
/**
 * Plugin Name: Broken Link Checker by AIOSEO
 * Plugin URI:  https://aioseo.com/broken-link-checker
 * Description: Monitor and test all internal and external links on your site for broken links. By AIOSEO, the original SEO plugin for WordPress.
 * Author:      All in One SEO Team
 * Author URI:  https://aioseo.com
 * Version:     1.2.5
 * Text Domain: aioseo-broken-link-checker
 * Domain Path: languages
 *
 * Broken Link Checker by AIOSEO is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Broken Link Checker by AIOSEO is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Broken Link Checker by AIOSEO. If not, see <https://www.gnu.org/licenses/>.
 *
 * @since     1.0.0
 * @author    All in One SEO
 * @license   GPL-3.0+
 * @package   AIOSEO\BrokenLinkChecker
 * @copyright Copyright © 2025, All in One SEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AIOSEO_BROKEN_LINK_CHECKER_PHP_VERSION_DIR' ) ) {
	define( 'AIOSEO_BROKEN_LINK_CHECKER_PHP_VERSION_DIR', basename( dirname( __FILE__ ) ) );
}

require_once dirname( __FILE__ ) . '/app/init/init.php';

// Check if this plugin should be disabled.
if ( aioseo_blc_is_plugin_disabled() ) {
	return;
}

require_once dirname( __FILE__ ) . '/app/init/notices.php';

// We require PHP 7.0 or higher for the whole plugin to work.
if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
	add_action( 'admin_notices', 'aioseo_broken_link_checker_php_notice' );

	// Do not process the plugin code further.
	return;
}

// We require WP 5.3+ for the whole plugin to work.
global $wp_version; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
if ( version_compare( $wp_version, '5.3', '<' ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
	add_action( 'admin_notices', 'aioseo_broken_link_checker_wordpress_notice' );

	// Do not process the plugin code further.
	return;
}

// Plugin constants.
if ( ! defined( 'AIOSEO_BROKEN_LINK_CHECKER_DIR' ) ) {
	define( 'AIOSEO_BROKEN_LINK_CHECKER_DIR', __DIR__ );
}
if ( ! defined( 'AIOSEO_BROKEN_LINK_CHECKER_FILE' ) ) {
	define( 'AIOSEO_BROKEN_LINK_CHECKER_FILE', __FILE__ );
}

// Define the class and the function.
require_once dirname( __FILE__ ) . '/app/BrokenLinkChecker.php';

aioseoBrokenLinkChecker();