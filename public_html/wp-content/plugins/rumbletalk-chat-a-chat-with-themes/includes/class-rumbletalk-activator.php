<?php

require_once plugin_dir_path(__FILE__) . '../admin/ajax-rumbletalk-admin.php';

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    RumbleTalk
 * @subpackage RumbleTalk/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    RumbleTalk
 * @subpackage RumbleTalk/includes
 * @author     Your Name <email@example.com>
 */
class RumbleTalk_Activator
{
    private static $options = array(
        'rumbletalk_chat_chats',
        'rumbletalk_chat_token_key',
        'rumbletalk_chat_token_secret',
        'rumbletalk_accesstoken'
    );

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        $key = get_option('rumbletalk_chat_token_key');
        $secret = get_option('rumbletalk_chat_token_secret');

        foreach (self::$options as $opt) {
            add_option($opt);
        }

        if ($key && $secret) {
            $ajaxHandler = new RumbleTalk_AJAX($key, $secret);

            $ajaxHandler->reloadChats(true);
        }
    }

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        foreach (self::$options as $opt) {
            delete_option($opt);
        }
    }
}
