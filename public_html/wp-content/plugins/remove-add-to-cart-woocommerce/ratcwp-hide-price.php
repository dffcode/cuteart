<?php
if ( function_exists('is_multisite') && is_multisite() ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php');
	return true;
} else {

	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {

		function ratcwp_admin_notice() {

			$ratcwp_allowed_tags = array(
				'a' => array(
					'class' => array(),
					'href'  => array(),
					'rel'   => array(),
					'title' => array(),
				),
				'b' => array(),

				'div' => array(
					'class' => array(),
					'title' => array(),
					'style' => array(),
				),
				'p' => array(
					'class' => array(),
				),
				'strong' => array(),

			);

			// Deactivate the plugin
			deactivate_plugins(__FILE__);

			$ratcwp_woo_check = '<div id="message" class="error">
				<p><strong>Remove Add To Cart & Hide Price plugin is inactive.</strong> The <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce plugin</a> must be active for this plugin to work. Please install &amp; activate WooCommerce »</p></div>';
			echo wp_kses( __( $ratcwp_woo_check, 'themelocationratc_hp' ), $ratcwp_allowed_tags);

		}
		add_action('admin_notices', 'ratcwp_admin_notice');
	}
}

if (!class_exists('Ratcwp_Hide_Price') ) {

	class Ratcwp_Hide_Price {

		public function __construct() {

			$this->ratcwp_global_constents_vars();
			
			add_action('wp_loaded', array( $this, 'ratcwp_init' ));
			if (is_admin() ) {
				include_once RATCWP_PLUGIN_DIR . 'admin/class-ratcwp-admin.php';
			} else {
				include_once RATCWP_PLUGIN_DIR . 'front/class-ratcwp-front.php';
			}

		}

		public function ratcwp_global_constents_vars() {

			if (!defined('RATCWP_URL') ) {
				define('RATCWP_URL', plugin_dir_url(__FILE__));
			}

			if (!defined('RATCWP_BASENAME') ) {
				define('RATCWP_BASENAME', plugin_basename(__FILE__));
			}

			if (! defined('RATCWP_PLUGIN_DIR') ) {
				define('RATCWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
			}
		}

		public function ratcwp_init() {
			if (function_exists('load_plugin_textdomain') ) {
				load_plugin_textdomain('themelocationratc_hp', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
		}


	}

	new Ratcwp_Hide_Price();

}
