<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( !class_exists( 'Ratcwp_Hide_Price_Front' ) ) {

	class Ratcwp_Hide_Price_Front extends Ratcwp_Hide_Price {

		public function __construct() {

			//Hide add to cart shop page.
			add_filter( 'woocommerce_loop_add_to_cart_link', array($this, 'ratcwp_replace_loop_add_to_cart_link'), 10, 2 );

			//Hide add to cart on product page.
			add_action( 'woocommerce_single_product_summary', array($this, 'ratcwp_hide_add_cart_product_page'), 1, 0 );
		}

		public function ratcwp_replace_loop_add_to_cart_link( $html, $product ) {

			$cart_txt = $html;

			$enable_hide_price_feature        = get_option('ratcwp_enable_hide_pirce');
			$enable_for_all_users                 = get_option('ratcwp_enable_hide_pirce_all');
			$enable_for_guest                 = get_option('ratcwp_enable_hide_pirce_guest');
			$ratcwp_enable_hide_pirce_registered = get_option('ratcwp_enable_hide_pirce_registered');
			$ratcwp_hide_user_role               = unserialize(get_option('ratcwp_hide_user_role'));

			$enable_hide_price = get_option('ratcwp_hide_price');
			$cps_price_text    = get_option('ratcwp_price_text');

			$ratcwp_hide_cart_button = get_option('ratcwp_hide_cart_button');
			$ratcwp_cart_button_text = get_option('ratcwp_cart_button_text');
			$ratcwp_cart_button_link = get_option('ratcwp_cart_button_link');

			$ratcwp_hide_products   = unserialize(get_option('ratcwp_hide_products'));
			$cps_hide_categories = unserialize(get_option('cps_hide_categories'));

			//Category Products
			$productids   = array();
			$args         = array(
				'numberposts' => -1,
				'post_type' => 'product',
				'post_stats' => 'publish',
				'tax_query'     => array(
					array(
						'taxonomy'  => 'product_cat',
						'field'     => 'id',
						'terms'     => $cps_hide_categories
					)
				)
			);
			$products_ids = get_posts($args);

			foreach ($products_ids as $proid) {
				$ratcwp_hide_products[] .= $proid->ID;
			}

			//Hide add to cart button if hidden in our module settings.
			if (!empty($ratcwp_hide_cart_button) && 'inquire_us' == $ratcwp_hide_cart_button) {
				//For All Users

				//For Guest Users
				if (!empty($enable_for_guest) && 'yes' == $enable_for_guest) {
					if ( !is_user_logged_in() ) {
						if ( !empty($ratcwp_hide_products) ) {
							if (in_array($product->get_id(), $ratcwp_hide_products)) {
								if (!empty($ratcwp_cart_button_text)) {

									$cart_txt = '<a href="' . esc_url($ratcwp_cart_button_link) . '" rel="nofollow" class="button add_to_cart_button">' . esc_attr($ratcwp_cart_button_text) . '</a>';

								} else {
									$cart_txt = '';
								}
							}
						}
					}
				}

				//For Registered Users
				if ( !empty($ratcwp_enable_hide_pirce_registered) && 'yes' == $ratcwp_enable_hide_pirce_registered) {

					if ( is_user_logged_in() ) {
						// get Current User Role
						$curr_user      = wp_get_current_user();
						$user_data      = get_user_meta( $curr_user->ID );
						$curr_user_role = $curr_user->roles[0];

						if(!empty( $ratcwp_hide_user_role )) {
							if (in_array($curr_user_role, $ratcwp_hide_user_role) && in_array($product->get_id(), $ratcwp_hide_products)) {
								if (!empty($ratcwp_cart_button_text)) {
									$cart_txt = '<a href="' . esc_url($ratcwp_cart_button_link) . '" rel="nofollow" class="button add_to_cart_button">' . esc_attr($ratcwp_cart_button_text) . '</a>';
								} else {
									$cart_txt = '';
								}
							}
						} else {
							if(in_array($product->get_id(), $ratcwp_hide_products)) {
								if (!empty($ratcwp_cart_button_text)) {
									$cart_txt = '<a href="' . esc_url($ratcwp_cart_button_link) . '" rel="nofollow" class="button add_to_cart_button">' . esc_attr($ratcwp_cart_button_text) . '</a>';
								} else {
									$cart_txt = '';
								}
							}
						}
					}
				}

			}		
			
			//Hide add to cart button if hidden in our module settings.
			if (!empty($ratcwp_hide_cart_button) && 'remove_button' == $ratcwp_hide_cart_button) {
				//For All Users

				//For Guest Users
				if (!empty($enable_for_guest) && 'yes' == $enable_for_guest) {
					if ( !is_user_logged_in() ) {
						if ( !empty($ratcwp_hide_products) ) {
							if (in_array($product->get_id(), $ratcwp_hide_products)) {
								if (!empty($ratcwp_cart_button_text)) {

									$cart_txt = '';

								} else {
									$cart_txt = '';
								}
							}
						}
					}
				}

				//For Registered Users
				if ( !empty($ratcwp_enable_hide_pirce_registered) && 'yes' == $ratcwp_enable_hide_pirce_registered) {

					if ( is_user_logged_in() ) {
						// get Current User Role
						$curr_user      = wp_get_current_user();
						$user_data      = get_user_meta( $curr_user->ID );
						$curr_user_role = $curr_user->roles[0];

						if(!empty($ratcwp_hide_user_role)) {
							if (in_array($curr_user_role, $ratcwp_hide_user_role) && in_array($product->get_id(), $ratcwp_hide_products)) {
								if (!empty($ratcwp_cart_button_text)) {
									$cart_txt = '';
								} else {
									$cart_txt = '';
								}
							}
						} else {
							if ( in_array($product->get_id(), $ratcwp_hide_products)) {
								if (!empty($ratcwp_cart_button_text)) {
									$cart_txt = '';
								} else {
									$cart_txt = '';
								}
							}
						}
					}
				}

			}

			return $cart_txt;
		}

		public function ratcwp_hide_add_cart_product_page() {

			global $user, $product;
			$enable_for_all_users             = get_option('ratcwp_enable_hide_pirce_all');
			$enable_hide_price_feature        = get_option('ratcwp_enable_hide_pirce');
			$enable_for_guest                 = get_option('ratcwp_enable_hide_pirce_guest');
			$ratcwp_enable_hide_pirce_registered = get_option('ratcwp_enable_hide_pirce_registered');
			$ratcwp_hide_user_role               = unserialize(get_option('ratcwp_hide_user_role'));

			$enable_hide_price = get_option('ratcwp_hide_price');
			$cps_price_text    = get_option('ratcwp_price_text');

			$ratcwp_hide_cart_button = get_option('ratcwp_hide_cart_button');
			$ratcwp_cart_button_text = get_option('ratcwp_cart_button_text');
			$ratcwp_cart_button_link = get_option('ratcwp_cart_button_link');

			$ratcwp_hide_products   = unserialize(get_option('ratcwp_hide_products'));
			$cps_hide_categories = unserialize(get_option('cps_hide_categories'));

			//Category Products
			$productids   = array();
			$args         = array(
				'numberposts' => -1,
				'post_type' => 'product',
				'post_stats' => 'publish',
				'tax_query'     => array(
					array(
						'taxonomy'  => 'product_cat',
						'field'     => 'id',
						'terms'     => $cps_hide_categories
					)
				)
			);
			$products_ids = get_posts($args);

			foreach ($products_ids as $proid) {
				$ratcwp_hide_products[] .= $proid->ID;
			}

			//Hide add to cart if price is hidden because there is no need of button if price is hidden.

			//Hide add to cart button if hidden in our module settings.
			if (!empty($ratcwp_hide_cart_button) && 'inquire_us' == $ratcwp_hide_cart_button) {

				//For Guest Users

				//For Guest Users
				if (!empty($enable_for_guest) && 'yes' == $enable_for_guest) {
					if ( !is_user_logged_in() ) {
						if ( !empty($ratcwp_hide_products) ) {
							if (in_array($product->get_id(), $ratcwp_hide_products)) {

								if ('variable' == $product->get_type()) {

									remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
									add_action( 'woocommerce_single_variation', array($this, 'ratcwp_custom_button_replacement'), 30 );

								} else {

									remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
									add_action( 'woocommerce_single_product_summary', array($this, 'ratcwp_custom_button_replacement'), 30 );
								}
							}
						}
					}
				}

				//For Registered Users
				
				if ( !empty($ratcwp_enable_hide_pirce_registered) && 'yes' == $ratcwp_enable_hide_pirce_registered) {

					if ( is_user_logged_in() ) {

						// get Current User Role
						$curr_user      = wp_get_current_user();
						$user_data      = get_user_meta( $curr_user->ID );
						$curr_user_role = $curr_user->roles[0];

						if(!empty($ratcwp_hide_user_role)) {
							if (in_array($curr_user_role, $ratcwp_hide_user_role) && in_array($product->get_id(), $ratcwp_hide_products)) {
	
								if ('variable' == $product->get_type()) {
	
									remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
									add_action( 'woocommerce_single_variation', array($this, 'ratcwp_custom_button_replacement'), 30 );
	
								} else {
									remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
									add_action( 'woocommerce_single_product_summary', array($this, 'ratcwp_custom_button_replacement'), 30 );
								}
									
								
							}
						} else {
							if ( in_array($product->get_id(), $ratcwp_hide_products)) {
	
								if ('variable' == $product->get_type()) {
									remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
									add_action( 'woocommerce_single_variation', array($this, 'ratcwp_custom_button_replacement'), 30 );
	
								} else {
									remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
									add_action( 'woocommerce_single_product_summary', array($this, 'ratcwp_custom_button_replacement'), 30 );
								}
							}							
						}
					}
				}
			}

			

			if (!empty($ratcwp_hide_cart_button) && 'remove_button' == $ratcwp_hide_cart_button) {

				//For Guest Users

				//For Guest Users
				if (!empty($enable_for_guest) && 'yes' == $enable_for_guest) {
					if ( !is_user_logged_in() ) {
						if ( !empty($ratcwp_hide_products) ) {
							if (in_array($product->get_id(), $ratcwp_hide_products)) {

								if ('variable' == $product->get_type()) {
									remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
								} else {
									remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
								}
							}
						}
					}
				}

				//For Registered Users
				if ( !empty($ratcwp_enable_hide_pirce_registered) && 'yes' == $ratcwp_enable_hide_pirce_registered) {

					if ( is_user_logged_in() ) {

						// get Current User Role
						$curr_user      = wp_get_current_user();
						$user_data      = get_user_meta( $curr_user->ID );
						$curr_user_role = $curr_user->roles[0];

						if( !empty($ratcwp_hide_user_role) ) {
							if (in_array($curr_user_role, $ratcwp_hide_user_role) && in_array($product->get_id(), $ratcwp_hide_products)) {
								if ('variable' == $product->get_type()) {
									remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
								} else {
									remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
									add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            						add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
								}
							}
						}
					}
				}
			}
		}


		public function ratcwp_custom_button_replacement() {
			global $user, $product;

			$enable_for_guest                 = get_option('ratcwp_enable_hide_pirce_guest');
			$ratcwp_enable_hide_pirce_registered = get_option('ratcwp_enable_hide_pirce_registered');
			$ratcwp_hide_user_role               = unserialize(get_option('ratcwp_hide_user_role'));

			$enable_hide_price = get_option('ratcwp_hide_price');
			$cps_price_text    = get_option('ratcwp_price_text');

			$ratcwp_hide_cart_button = get_option('ratcwp_hide_cart_button');
			$ratcwp_cart_button_text = get_option('ratcwp_cart_button_text');
			$ratcwp_cart_button_link = get_option('ratcwp_cart_button_link');

			$ratcwp_hide_products   = unserialize(get_option('ratcwp_hide_products'));
			$cps_hide_categories = unserialize(get_option('cps_hide_categories'));

			if (!empty($ratcwp_cart_button_text)) {

				echo '<a href="' . esc_url($ratcwp_cart_button_link) . '" rel="nofollow" class="button add_to_cart_button">' . esc_attr($ratcwp_cart_button_text) . '</a>';

			} else {
				echo '';
			}
		}

	}

	new Ratcwp_Hide_Price_Front();
}