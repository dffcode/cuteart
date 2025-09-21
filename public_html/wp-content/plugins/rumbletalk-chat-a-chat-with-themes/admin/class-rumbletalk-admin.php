<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/ajax-rumbletalk-admin.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    RumbleTalk
 * @subpackage RumbleTalk/admin
 * @author     Your Name <email@example.com>
 */
class RumbleTalk_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * @var RumbleTalk_AJAX - instance of the ajax handler
     */
    public $ajaxHandler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->ajaxHandler = new RumbleTalk_AJAX(
            get_option('rumbletalk_chat_token_key'),
            get_option('rumbletalk_chat_token_secret')
        );

        add_action('admin_init', array(&$this, 'adminInit'));
        add_action('admin_menu', array(&$this, 'adminMenu'));

        add_action('wp_ajax_rumbletalk_ajax', array($this->ajaxHandler, 'handleRequest'));
    }

    public static function updateChats($chats)
    {
        update_option('rumbletalk_chat_chats', json_encode($chats));
    }

    public static function removeChats()
    {
        update_option('rumbletalk_chat_chats', false);
    }

    public static function getChats($decoded = true)
    {
        $chats = get_option('rumbletalk_chat_chats');

        return $decoded
            ? json_decode($chats, true)
            : $chats;
    }

    public function registerTinyMceButton($buttons)
    {
        array_push($buttons, 'button_rumbletalk_chat');
        return $buttons;
    }

    public function addTinyMceButton($plugin_array)
    {
        $plugin_array['rumbletalk_mce_buttons'] = plugins_url('/js/add-mce-buttons.js', __FILE__);
        return $plugin_array;
    }

    public function adminMenu()
    {
        add_options_page(
            'RumbleTalk Chat',
            'RumbleTalk Chat',
            'administrator',
            'rumbletalk-chat',
            array(&$this, 'drawAdminPage')
        );
    }

    public function adminInit()
    {
        if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
            add_filter('mce_buttons', array(&$this, 'registerTinyMceButton'));
            add_filter('mce_external_plugins', array(&$this, 'addTinyMceButton'));
        }
    }

    public function drawAdminPage()
    {
        $chats = self::getChats(false);
        if (!$chats) {
            $chats = 'false';
        }

        $accessToken = $this->ajaxHandler->getAccessToken();
        if ($accessToken) {
            $account = $this->ajaxHandler->getAccountInfo(true);
            if ($account['status']) {
                $account = $account['data'];
            } else {
                $invalidToken = true;
            }
        } else {
            $account = array();
        }

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/rumbletalk-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        $token = $this->ajaxHandler->getToken(true);

        # JS global variables; creates the _resources object in the global scope
        wp_localize_script(
            $this->plugin_name,
            '_resources',
            array(
                'rollingGif' => esc_url(plugins_url('/images/rolling.gif', __FILE__)),
                'chats' => $chats,
                'accessToken' => $accessToken,
                'account' => $account,
                'tokenKey' => $token['key'],
                'tokenSecret' => $token['secret'],
                'invalidToken' => isset($invalidToken),
                'nonce' => wp_create_nonce(RumbleTalk_AJAX::NONCE_NAME)
            )
        );

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/rumbletalk-admin.css',
            array(),
            $this->version,
            'all'
        );

        require_once 'partials/rumbletalk-admin-display.php';
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Plugin_Name_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Plugin_Name_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

//        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/rumbletalk-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Plugin_Name_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Plugin_Name_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

//        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/rumbletalk-admin.js', array('jquery'), $this->version, false);
    }
}
