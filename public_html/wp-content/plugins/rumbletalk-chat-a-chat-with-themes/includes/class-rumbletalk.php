<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    RumbleTalk
 * @subpackage RumbleTalk/includes
 */

use RumbleTalk\RumbleTalkSDK;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    RumbleTalk
 * @subpackage RumbleTalk/includes
 * @author     Your Name <email@example.com>
 */
class RumbleTalk
{
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * @var string  URL to the CDN base address
     */
    public static $cdn = 'https://d1pfint8izqszg.cloudfront.net/';

    /**
     * @var RumbleTalk_Admin
     */
    protected static $plugin_admin;

    /**
     * @var RumbleTalk_Public
     */
    protected static $plugin_public;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('RUMBLETALK_VERSION')) {
            $this->version = RUMBLETALK_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'rumbletalk';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - RumbleTalk_Loader. Orchestrates the hooks of the plugin.
     * - RumbleTalk_i18n. Defines internationalization functionality.
     * - RumbleTalk_Admin. Defines all hooks for the admin area.
     * - RumbleTalk_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-rumbletalk-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-rumbletalk-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-rumbletalk-public.php';

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the RumbleTalk_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new RumbleTalk_i18n();

        add_action('plugins_loaded', array(&$plugin_i18n, 'load_plugin_textdomain'));
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        self::$plugin_admin = new RumbleTalk_Admin($this->get_plugin_name(), $this->get_version());
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        self::$plugin_public = new RumbleTalk_Public($this->get_plugin_name(), $this->get_version());
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * @param int $size - the user supplied size
     * @param bool $force - if set to true, will return default value on invalid user dimensions
     * @return string
     */
    private static function parseDimension($size, $force = false)
    {
        $matches = array();
        if (preg_match('/^(\d+)(%|px)?$/', $size, $matches)) {
            return $matches[1] . (isset($matches[2]) ? $matches[2] : 'px');
        } else {
            return $force
                ? '500px'
                : '';
        }
    }

    /**
     * get the login name of a user based on RumbleTalk's attribute names
     * @param WP_User $user - the user
     * @param string $key - the key set in the chat settings
     * @return string - the unescaped login
     */
    private static function getLoginName($user, $key)
    {
        $loginName = '';
        switch ($key) {
            case 'nickname':
                $loginName = get_the_author_meta('nickname', $user->ID);
                break;

            # user_description (Display Name + Bio)
            case 'user_description':
                $loginName = get_the_author_meta('display_name', $user->ID) . ' | ' .
                    get_the_author_meta('user_description', $user->ID);

                if (strlen($loginName) >= 64) {
                    $loginName = substr($loginName, 0, 57) . '...';
                }
                break;

            # username (Username + Bio)
            case 'username':
                $loginName = get_the_author_meta('user_login', $user->ID) . ' | ' .
                    get_the_author_meta('user_description', $user->ID);
                if (strlen($loginName) >= 64) {
                    $loginName = substr($loginName, 0, 57) . '...';
                }
                break;

            # nicknameBio (Nickname + Bio)
            case 'nicknameBio':
                $loginName = get_the_author_meta('nickname', $user->ID) . ' | ' .
                    get_the_author_meta('user_description', $user->ID);
                if (strlen($loginName) >= 64) {
                    $loginName = substr($loginName, 0, 57) . '...';
                }
                break;

            # firstnameBio (Firstname + Bio)
            case 'firstnameBio':
                $loginName = get_the_author_meta('user_firstname', $user->ID) . ' | ' .
                    get_the_author_meta('user_description', $user->ID);
                if (strlen($loginName) >= 64) {
                    $loginName = substr($loginName, 0, 57) . '...';
                }
                break;

            # lastnameBio (Lastname + Bio)
            case 'lastnameBio':
                $loginName = get_the_author_meta('user_lastname', $user->ID) . ' | ' .
                    get_the_author_meta('user_description', $user->ID);
                if (strlen($loginName) >= 64) {
                    $loginName = substr($loginName, 0, 57) . '...';
                }
                break;

            case $key:
                $key = explode(' ', $key);
                if (count($key) == 2) {
                    $loginName = trim($user->{$key[0]} . ' ' . $user->{$key[1]});
                } else {
                    $loginName = $user->{$key[0]};
                }
                break;

        }

        # decode HTML entities so we don't get funky usernames; we later encode using json_encode
        return html_entity_decode($loginName ?: $user->display_name);
    }

    /**
     * overwrites attributes of the chat options from the [shortcode]
     * @param array $chatOptions - the saved chat options
     * @param array $attr - the chat options set in the [shortcode]
     */
    private static function overwriteAttributes(&$chatOptions, $attr)
    {
        foreach (array('height', 'width', 'floating', 'members', 'force-login') as $key) {
            if (isset($attr[$key])) {
                if ($attr[$key] == 'true') {
                    $attr[$key] = true;
                } elseif ($attr[$key] == 'false') {
                    $attr[$key] = false;
                }

                # [shortcode] turns attributes to lowercase
                if ($key == 'members') {
                    $chatOptions['membersOnly'] = $attr[$key];
                } elseif ($key == 'force-login') {
                    $chatOptions['forceLogin'] = $attr[$key];
                } else {
                    $chatOptions[$key] = $attr[$key];
                }
            }
        }
    }

    /**
     * overwrite the default values of the chat
     * string 'hash' - the chat's hash; can be ignored to use the default chat
     * int 'height' - the height of the chat
     * int 'width' - the width of the chat
     * boolean 'floating' - whether or not to display the chat in floating mode
     * boolean 'members' - whether or not to automatically log into the chat verified users
     * @param array $attr - described above
     * @return string
     */
    public static function embed($attr = array())
    {
        $issueMessage = 'Your RumbleTalk plug-in is not connected to your RumbleTalk account. ' .
            'Go to the plug-in\'s settings page to connect your account.';

        $chats = RumbleTalk_Admin::getChats();

        if (!$chats) {
            if (!get_option('rumbletalk_chat_token_key') || !get_option('rumbletalk_chat_token_secret')) {
                return $issueMessage;
            }

            self::$plugin_admin->ajaxHandler->setToken(
                get_option('rumbletalk_chat_token_key'),
                get_option('rumbletalk_chat_token_secret')
            );

            if (!self::$plugin_admin->ajaxHandler->updateAccessToken()) {
                return $issueMessage;
            }

            $chats = self::$plugin_admin->ajaxHandler->reloadChats(true);
        }

        # get the chat's hash
        $hash = isset($attr['hash'])
            ? $attr['hash']
            : null;

        if (!$hash && is_array($chats)) {
            $hash = current(array_keys($chats));
        }

        if (empty($hash) || !RumbleTalkSDK::validateHashStructure($hash)) {
            return $issueMessage;
        }

        if (isset($chats[$hash])) {
            $chatOptions = $chats[$hash];
        } else {
            # default options
            $chatOptions = array(
                'height' => '',
                'width' => '',
                'floating' => false,
                'membersOnly' => false,
                'forceLogin' => false
            );
        }

        self::overwriteAttributes($chatOptions, $attr);

        $str = '';
        if ($chatOptions['membersOnly'] && $attr !== false) {
            $current_user = wp_get_current_user();
            if ($current_user->display_name) {
                $loginInfo = array(
                    'username' => self::getLoginName($current_user, $chatOptions['loginName']),
                    'forceLogin' => !!$chatOptions['forceLogin'],
                    'image' => get_avatar_url($current_user->ID, ['size' => 128]),
                    'hash' => $hash
                );
                $loginInfo = apply_filters('rumbletalk_login_args', $loginInfo);
                $loginInfo = json_encode($loginInfo);

                $str = "<script>rtmq('login', $loginInfo);</script>";

            }
        }

        $url = "https://rumbletalk.com/client/?$hash";
        if ($chatOptions['floating']) {
            $str .= '<div class="rumbletalk-handle">';
            $url .= '&1';
        } else {
            $width = self::parseDimension($chatOptions['width']);
            if ($width) {
                $width = "max-width: $width;";
            }
            $height = 'height: ' . self::parseDimension($chatOptions['height'], true) . ';';
            $str .= '<div class="rumbletalk-handle" style="' . $height . $width . '">';
        }

        $divId = 'rt-' . md5($hash);
        $str .= '<div id="' . $divId . '"></div>';
        $str .= '<script src="' . $url . '"></script>';
        $str .= '</div>';

        return $str;
    }
}
