<?php

/**
 * Plugin Name: RumbleTalk Chat
 * Plugin URI: https://wordpress.org/plugins/rumbletalk-chat-a-chat-with-themes/
 * Description: Group chat room for WordPress and BuddyPress websites. Use one or many advanced stylish chat rooms for your community.
 * Tags: Group chat, BuddyPress
 * Version: 6.3.4
 * Author: RumbleTalk Ltd
 * Author URI: https://rumbletalk.com
 * License: GPL2
 *
 * Copyright 2012-2017 RumbleTalk Ltd (email : support@rumbletalk.com)
 *
 * This program is free trial software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Update this as you release new versions.
 */
define('RUMBLETALK_VERSION', '6.3.4');

add_filter('plugin_action_links', 'rumbletalk_settings_link', 10, 2);

function rumbletalk_settings_link($actions, $plugin_file)
{
    static $plugin;

    if (!isset($plugin)) {
        $plugin = plugin_basename(__FILE__);
    }

    if ($plugin == $plugin_file) {
        $settings = array('settings' =>
            '<a href="' . admin_url('options-general.php?page=rumbletalk-chat') . '">Settings</a>');
        $actions = array_merge($settings, $actions);
    }

    return $actions;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links');

function add_action_links($actions)
{
    ob_start();
    ?>
    <a href="https://www.youtube.com/embed/-Xodwu-hvBo"
       target="popup"
       onclick="window.open('https://www.youtube.com/embed/-Xodwu-hvBo', 'popup', 'width=1066, height=600'); return false;">
        Take a tour
    </a>
<?php
    $anchor = ob_get_clean();
    return array_merge($actions, array($anchor));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rumbletalk-activator.php
 */
function activate_rumbletalk()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-rumbletalk-activator.php';
    RumbleTalk_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rumbletalk-deactivator.php
 */
function deactivate_rumbletalk()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-rumbletalk-activator.php';
    RumbleTalk_Activator::deactivate();
}

register_activation_hook(__FILE__, 'activate_rumbletalk');
register_deactivation_hook(__FILE__, 'deactivate_rumbletalk');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-rumbletalk.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rumbletalk()
{
    new RumbleTalk();
}

run_rumbletalk();
