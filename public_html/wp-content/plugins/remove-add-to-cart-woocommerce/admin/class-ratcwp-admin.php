<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( !class_exists( 'Ratcwp_Hide_Price_Admin' ) ) {

	class Ratcwp_Hide_Price_Admin extends Ratcwp_Hide_Price {

		public function __construct() {

			add_action( 'admin_menu', array( $this, 'ratcwp_custom_menu_admin' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'ratcwp_admin_assets' ) );
			add_action('wp_ajax_cspsearchProducts', array($this, 'cspsearchProducts'));
			add_action('wp_ajax_cspsearchUsers', array($this, 'cspsearchUsers'));

			if (isset($_POST['ratcwprole_save_hide_price']) && '' != $_POST['ratcwprole_save_hide_price']) {
				include_once ABSPATH . 'wp-includes/pluggable.php';
				$this->ratcwprolebase_save_data();
				add_action('admin_notices', array($this, 'ratcwprolebase_author_admin_notice'));
			}
		}

		public function ratcwp_admin_assets() {
		
			wp_enqueue_style( 'themelocationratc_hp_admin_css', plugins_url( '../assets/css/ratcwp_admin.css', __FILE__ ), false, '' );
			wp_enqueue_script( 'themelocationratc_hp_admin_js', plugins_url( '../assets/js/ratcwp_admin.js', __FILE__ ), false, '' );
			$ratcwp_data = array(
				'admin_url'  => admin_url('admin-ajax.php'),
			);
			wp_localize_script( 'themelocationratc_hp_admin_js', 'ratcwp_php_vars', $ratcwp_data );
			//select2 css and js
			wp_enqueue_script('jquery');

			wp_enqueue_script( 'select2', plugins_url( '../assets/js/select2.js', __FILE__ ), false, '' );
			wp_enqueue_style( 'select2', plugins_url( '../assets/css/select2.css', __FILE__ ), false, '' );
			
			
			
		}

		public function ratcwp_custom_menu_admin() {

			add_submenu_page( 'woocommerce', esc_html__('Remove Cart & Hide Price', 'themelocationratc_hp'), esc_html__('Remove Cart & Hide Price', 'themelocationratc_hp'), 'manage_options', 'remove-add-to-cart-woocommerce', array($this, 'ratcwp_settings'), 9); 

			// add_menu_page (
		    //     esc_html__('Hide Price', 'themelocationratc_hp'), // page title 
		    //     esc_html__('Hide Price', 'themelocationratc_hp'), // menu title
		    //     'manage_options', // capability
		    //     'af-hide-price',  // menu-slug
		    //     array($this, 'ratcwp_settings'),   // function that will render its output
		    //     plugins_url( '../assets/images/small_logo_white.png',  __FILE__  ),   // link to the icon that will be displayed in the sidebar
		    //     '7'    // position of the menu option
		    // );
		}


		public function ratcwp_settings() {

			require RATCWP_PLUGIN_DIR . 'admin/ratcwp_setting_template.php';
		}

		public function cspsearchProducts() {


			if (isset($_POST['q']) && '' != $_POST['q']) {


				$pro = sanitize_text_field( $_POST['q'] );

			} else {

				$pro = '';

			}


			$data_array = array();
			$args       = array(
				'post_type' => array('product'),
				'post_status' => 'publish',
				'numberposts' => -1,
				's'	=>  $pro
			);
			$pros       = get_posts($args);

			if ( !empty($pros)) {

				foreach ($pros as $proo) {

					$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
					$data_array[] = array( $proo->ID, $title ); // array( Post ID, Post Title )
				}
			}
			
			echo json_encode( $data_array );

			die();
		}

		public function cspsearchUsers() {

			if (isset($_POST['q']) && '' != $_POST['q']) {
				
				$pro = sanitize_text_field( $_POST['q'] );

			} else {

				$pro = '';

			}


			$data_array  = array();
			$users       = new WP_User_Query( array(
				'search'         => '*' . esc_attr( $pro ) . '*',
				'search_columns' => array(
					'user_login',
					'user_nicename',
					'user_email',
					'user_url',
				),
			) );
			$users_found = $users->get_results();

			if ( !empty($users_found)) {

				foreach ($users_found as $proo) {

					$title        = $proo->display_name . '(' . $proo->user_email . ')';
					$data_array[] = array( $proo->ID, $title ); // array( User ID, User name and email )
				}
			}
			
			echo json_encode( $data_array );

			die();

		}

		public function ratcwprolebase_save_data() {

			global $wp;

			if (!empty($_POST) && isset($_POST['ratcwprole_save_admin_options']) && wp_verify_nonce($_POST['ratcwprole_save_admin_options'], 'ratcwprole_save_hide_price_nonce')) {

			
				$checkbox_fields = array(
					'ratcwp_enable_hide_pirce',
					'ratcwp_enable_hide_pirce_all',
					'ratcwp_enable_hide_pirce_guest',
					'ratcwp_enable_hide_pirce_registered',
					'ratcwp_hide_price',
				);
			
				foreach ($checkbox_fields as $field) {
					update_option($field, isset($_POST[$field]) ? 'yes' : '');
				}

				if( isset($_POST['ratcwp_hide_user_role']) ) {
					update_option('ratcwp_hide_user_role', serialize($_POST['ratcwp_hide_user_role']));
				}		
				
				if( isset($_POST['ratcwp_hide_cart_button']) ) {
					update_option('ratcwp_hide_cart_button', sanitize_text_field($_POST['ratcwp_hide_cart_button']));
				}		
				
				if( isset($_POST['ratcwp_hide_products']) ) {
					update_option('ratcwp_hide_products', serialize($_POST['ratcwp_hide_products']));
				}		
				
				if( isset($_POST['cps_hide_categories']) ) {
					update_option('cps_hide_categories', serialize($_POST['cps_hide_categories']));
				}
			
				$text_fields = array(
					'ratcwp_price_text',
					'ratcwp_cart_button_text',
					'ratcwp_cart_button_link',
				);
			
				foreach ($text_fields as $field) {
					if( isset($_POST[$field]) ) {
						update_option($field, sanitize_text_field($_POST[$field]));
					}
				}
			}
			
		}

		public function ratcwprolebase_author_admin_notice() {
			?>
			<div class="updated notice notice-success is-dismissible">
				<p><?php echo esc_html__('Settings saved successfully.', 'themelocationratc_hp'); ?></p>
			</div>
			<?php
		}

	}

	new Ratcwp_Hide_Price_Admin();

}