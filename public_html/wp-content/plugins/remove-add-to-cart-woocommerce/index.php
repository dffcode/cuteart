<?php
/*
  Plugin Name: woo-inquire-us-and-disable-add-to-cart-button
  Plugin URI: https://www.themelocation.com/remove-cart-button-plugin/
  Description: This plugin removes add to cart from individual Product, Whole Category. It changes add to cart button to contact us button. It also hide product Price from Category as well as individual Product. We provide best possible support.
  Requires at least: 4.6
  Tested up to: 6.7.1
  Version: 1.4.7
  Author: themelocation
  Author URI: https://www.themelocation.com
 */
// Create a helper function for easy SDK access.
function ratcw_fs() {
    global $ratcw_fs;

    if ( ! isset( $ratcw_fs ) ) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/freemius/start.php';

        $ratcw_fs = fs_dynamic_init( array(
            'id'                  => '1726',
            'slug'                => 'remove-add-to-cart-woocommerce',
            'type'                => 'plugin',
            'public_key'          => 'pk_fe8e885321d4b25dd41f9fa044ef1',
            'is_premium'          => false,
            // If your plugin is a serviceware, set this option to false.
            'has_premium_version' => true,
            'has_addons'          => false,
            'has_paid_plans'      => true,
            'menu'                => array(
                'slug'           => 'remove-add-to-cart-woocommerce',
                'support'        => false,
                'parent'         => array(
                    'slug' => 'woocommerce',
                ),
            ),
        ) );
    }

    return $ratcw_fs;
}

// Init Freemius.
ratcw_fs();
// Signal that SDK was initiated.
do_action( 'ratcw_fs_loaded' );

require_once dirname( __FILE__ ) . '/ratcwp-hide-price.php';
 

add_action('product_cat_edit_form_fields', 'wpiudacb_edit_form_fields');
add_action('product_cat_add_form_fields', 'wpiudacb_edit_form_fields');


if (!function_exists('wpiudacb_scripts')) {

    /**
     * Adding JS Script
     */
    function wpiudacb_scripts() {
        echo wp_enqueue_script('woo-inquire-us-and-disable-add-to-cart-button', plugin_dir_url(__FILE__) . '/woo-inquire-us-and-disable-add-to-cart-button.js');
    }

    add_action('admin_enqueue_scripts', 'wpiudacb_scripts');
}


/**
 * Category Form Edit Callback
 * @param type $tag
 */
if (!function_exists('wpiudacb_edit_form_fields')) {

    function wpiudacb_edit_form_fields($tag) {
        $wpiudacb_category_disable_add_to_cart = 'default';
        $wpiudacb_inqure_us_link = '';
        if (isset($tag->term_id)) {
            $termid = $tag->term_id;
            $wpiudacb_category_disable_add_to_cart = get_option("wpiudacb_category_disable_add_to_cart_$termid");
            $wpiudacb_inqure_us_link = get_option("wpiudacb_inqure_us_link_$termid");
        }
        ?>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="catpic"><?php _e('Alter Add to Cart Button', 'themelocationratc_hp'); ?></label>
            </th>
            <td>
                <select id="wpiudacb_disable_add_to_cart" name="wpiudacb_category_disable_add_to_cart" class="select short" style="">
                    <option value="default" <?php selected($wpiudacb_category_disable_add_to_cart, 'default'); ?> ><?php _e('Default', 'themelocationratc_hp'); ?></option>
                    <option value="remove_button" <?php selected($wpiudacb_category_disable_add_to_cart, 'remove_button'); ?>><?php _e('Remove Button', 'themelocationratc_hp'); ?></option>
                    <option value="inquire_us" <?php selected($wpiudacb_category_disable_add_to_cart, 'inquire_us'); ?>><?php _e('Inquire Us', 'themelocationratc_hp'); ?></option>
                </select>
                <span class="description"></span>
            </td>
        </tr>
        <tr class="form-field wpiudacb_inqure_us_link_field">
            <th valign="top" scope="row">
                <label for="catpic"><?php _e('Inquire Us Link', 'themelocationratc_hp'); ?></label>
            </th>
            <td>
                <input type="text" class="short" style="" name="wpiudacb_inqure_us_link" id="wpiudacb_inqure_us_link" value="<?php echo esc_url($wpiudacb_inqure_us_link) ?>" placeholder="http://">
                <span class="description"></span>
            </td>
        </tr>

         <tr class="form-field">
            <th valign="top" scope="row">
                <label for="catpic"><?php _e('Hide Price', 'woocommerce'); ?></label>
            </th>
            <td>
                <a href="https://www.themelocation.com/remove-cart-button-plugin" style="text-decoration: none; color:red;"> <?php _e('Upgrade Premium Version', 'woocommerce'); ?></a>
            </td>
        </tr>

         <tr class="form-field">
            <th valign="top" scope="row">
                <label for="catpic"><?php _e('Inquire Us Text', 'woocommerce'); ?></label>
            </th>
            <td>
               <a href="https://www.themelocation.com/remove-cart-button-plugin" style="text-decoration: none; color:red;"> <?php _e('Upgrade Premium Version', 'woocommerce'); ?></a>
            </td>
        </tr>
        <?php
    }

}

add_action('edited_product_cat', 'wpiudacb_save_extra_fileds');
add_action('created_product_cat', 'wpiudacb_save_extra_fileds');

/**
 * save extra category extra fields callback function
 * @param type $term_id
 */
if (!function_exists('wpiudacb_save_extra_fields')) { // Corrected the typo here
    function wpiudacb_save_extra_fields($term_id) {
        $termid = $term_id;

        // Save or update 'category disable add to cart' option
        if (isset($_POST['wpiudacb_category_disable_add_to_cart'])) {
            $cat_meta = get_option("wpiudacb_category_disable_add_to_cart_$termid");
            $value = sanitize_text_field($_POST['wpiudacb_category_disable_add_to_cart']);
            if ($cat_meta !== false) {
                update_option("wpiudacb_category_disable_add_to_cart_$termid", $value);
            } else {
                add_option("wpiudacb_category_disable_add_to_cart_$termid", $value, '', 'yes');
            }
        }

        // Save or update 'inquire us link' option
        if (isset($_POST['wpiudacb_inqure_us_link'])) {
            $cat_meta = get_option("wpiudacb_inqure_us_link_$termid");
            $value = esc_url($_POST['wpiudacb_inqure_us_link']);
            if ($cat_meta !== false) {
                update_option("wpiudacb_inqure_us_link_$termid", $value);
            } else {
                add_option("wpiudacb_inqure_us_link_$termid", $value, '');
            }
        }
    }
}

// when a category is removed
add_filter('deleted_term_taxonomy', 'wpiudacb_remove_tax_Extras');

/**
 * when a category is removed
 * @param type $term_id
 */
if (!function_exists('wpiudacb_remove_tax_Extras')) {
    function wpiudacb_remove_tax_Extras($term_id) {
        $termid = $term_id;

        // Check if 'taxonomy' exists in $_POST and sanitize the value
        if (isset($_POST['taxonomy']) && sanitize_text_field($_POST['taxonomy']) === 'product_cat') {
            // Delete the 'category disable add to cart' option if it exists
            if (get_option("wpiudacb_category_disable_add_to_cart_$termid")) {
                delete_option("wpiudacb_category_disable_add_to_cart_$termid");
            }
        }
    }
}

add_filter('manage_edit-product_cat_columns', 'wpiudacb_taxonomy_columns_type');
add_filter('manage_product_cat_custom_column', 'wpiudacb_taxonomy_columns_type_manage', 10, 3);

/**
 * Taxonomy Columns Type
 * @param array $columns
 * @return type
 */
if (!function_exists('wpiudacb_taxonomy_columns_type')) {

    function wpiudacb_taxonomy_columns_type($columns) {
        $columns['keywords'] = __('Detailed Description', 'themelocationratc_hp');
        return $columns;
    }

}

/**
 * Columns Type Manage
 * @global type $wp_version
 * @param type $out
 * @param type $column_name
 * @param type $term
 * @return type
 */
if (!function_exists('wpiudacb_taxonomy_columns_type_manage')) {

    function wpiudacb_taxonomy_columns_type_manage($out, $column_name, $term) {
        global $wp_version;

        // Check if $term is an object and get the term ID
        $term_id = is_object($term) ? $term->term_id : $term;
        
        // Get the option value for the term, with a default value if not set
        $out = get_option("wpiudacb_category_disable_add_to_cart_$term_id", '');

        // If WordPress version is less than 3.1, return the option value
        if ((float) $wp_version < 3.1) {
            return $out;
        } else {
            // Display $out if column_name is neither "thumb" nor "handle"
            if ($column_name !== "thumb" && $column_name !== "handle") {
                echo esc_html($out); // Escape output for safety
            }
        }

        return $out; // Ensure the function consistently returns a value
    }
}


//add_action('woocommerce_before_shop_loop_item', 'wpiudacb_replace_add_to_cart');

/**
 * Replacing add to card button
 * @global type $product
 */
if (!function_exists('wpiudacb_replace_add_to_cart')) {

    function wpiudacb_replace_add_to_cart() {
        global $product;
    
        // Ensure $product is available
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }
    
        // Get the link and custom field value
        $link = esc_url($product->get_permalink());
        $text = get_post_meta($product->get_id(), 'wpiudacb_disable_add_to_cart', true);
    
        // Default category option value
        $cat_option = 'default';
    
        // Get product categories
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if (!empty($terms) && is_array($terms)) {
            foreach ($terms as $cat) {
                // Check if the option is set for the category and is not set to 'Default'
                $category_option = get_option("wpiudacb_category_disable_add_to_cart_$cat->term_id");
                if ($category_option && $category_option !== 'default') {
                    $cat_option = $category_option;
                    break; // Exit loop early if a relevant option is found
                }
            }
        }
    
        // Check conditions to add or remove the "Add to Cart" action
        if (($text && $text !== 'default') || $cat_option !== 'default') {
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
        } else {
            add_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
        }
    }
    

}

/**
 * Adding Inquire Us button to listing page
 * @global type $product
 */
//add_action('woocommerce_after_shop_loop_item', 'wpiudacb_replace_add_to_cart_with_inqure_us_on_listing_page');
if (!function_exists('wpiudacb_replace_add_to_cart_with_inqure_us_on_listing_page')) {

    function wpiudacb_replace_add_to_cart_with_inqure_us_on_listing_page() {
        global $product;
    
        // Ensure $product is available
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }
    
        // Get custom 'inquire us' link from product meta
        $wpiudacb_inqure_us_link = get_post_meta($product->get_id(), 'wpiudacb_inqure_us_link', true);
        $disable_cart_option = get_post_meta($product->get_id(), 'wpiudacb_disable_add_to_cart', true);
    
        // Set default values for category options
        $cat_option = 'default';
        $cat_inquire_us_link = '';
    
        // Get product categories and check for 'disable add to cart' and 'inquire us' link settings
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if (!empty($terms) && is_array($terms)) {
            foreach ($terms as $cat) {
                $category_disable_option = get_option("wpiudacb_category_disable_add_to_cart_$cat->term_id");
                if ($category_disable_option && $category_disable_option !== 'default') {
                    $cat_option = $category_disable_option;
                    $cat_inquire_us_link = get_option("wpiudacb_inqure_us_link_$cat->term_id");
                    break; // Exit loop early if a relevant option is found
                }
            }
        }
    
        // Remove "Add to Cart" if any condition requires it
        if (($cat_option !== 'default' && $cat_option === 'inquire_us') || ($disable_cart_option === 'inquire_us')) {
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    
            // Determine which "Inquire Us" link to display
            $inquire_link = $cat_option === 'inquire_us' ? $cat_inquire_us_link : $wpiudacb_inqure_us_link;
    
            if (!empty($inquire_link)) {
                echo do_shortcode('<a href="' . esc_url($inquire_link) . '" target="_blank" class="button">' . esc_html__('Inquire Us', 'themelocationratc_hp') . '</a>');
            }
        }
    }
      
}

/**
 * Adding Inquire Us button to listing page
 * @global type $product
 * @since 1.4
 */
add_filter( 'woocommerce_loop_add_to_cart_link', 'hide_add_to_cart_link', 10, 2 );
function hide_add_to_cart_link( $html, $product ) {
    // Ensure $product is valid
    if (!$product || !is_a($product, 'WC_Product')) {
        return $html;
    }

    // Get custom 'inquire us' link and disable cart option
    $wpiudacb_inqure_us_link = get_post_meta($product->get_id(), 'wpiudacb_inqure_us_link', true);
    $disable_cart_option = get_post_meta($product->get_id(), 'wpiudacb_disable_add_to_cart', true);

    // Default values for category options
    $cat_option = 'default';
    $cat_inquire_us_link = '';

    // Get product categories and check for category-level options
    $terms = get_the_terms($product->get_id(), 'product_cat');
    if (!empty($terms) && is_array($terms)) {
        foreach ($terms as $cat) {
            $category_disable_option = get_option("wpiudacb_category_disable_add_to_cart_$cat->term_id");
            if ($category_disable_option && $category_disable_option !== 'default') {
                $cat_option = $category_disable_option;
                $cat_inquire_us_link = get_option("wpiudacb_inqure_us_link_$cat->term_id");
                break; // Exit loop early if a relevant option is found
            }
        }
    }

    // Handle category-level 'inquire us' option
    if ($cat_option === 'inquire_us') {
        $html = '<a href="' . esc_url($cat_inquire_us_link) . '" target="_blank" class="button add_to_cart_button">' . esc_html__('Inquire Us', 'themelocationratc_hp') . '</a>';
        return $html;
    }

    // Handle product-level options
    if ($disable_cart_option === 'remove_button') {
        $html = ''; // Remove "Add to Cart" button
        return $html;
    }
    
    if ($disable_cart_option === 'inquire_us') {
        $html = '<a href="' . esc_url($wpiudacb_inqure_us_link) . '" target="_blank" class="button add_to_cart_button">' . esc_html__('Inquire Us', 'themelocationratc_hp') . '</a>';
        return $html;
    }

    return $html; // Return the original HTML if no conditions are met
}

/**
 * Using cutom div for remove Add To Cart Button
 * @global type $product
 * @since 1.4
 */
function wpiudacb_user_woocommerce_before_add_to_cart_button(  ) { 
    echo '<div style="display: none;">';
}
function wpiudacb_user_woocommerce_after_add_to_cart_button(  ) { 
    echo '</div>';
}

/* remove add-to-cart from single product  page for product author  */
add_action('woocommerce_before_single_product_summary', 'wpiudacb_user_filter_addtocart_for_single_product_page');

/**
 * Appling Filter on single product page
 * @global type $product
 */
if (!function_exists('wpiudacb_user_filter_addtocart_for_single_product_page')) {

    function wpiudacb_user_filter_addtocart_for_single_product_page() {
        global $product, $post;
    
        // Ensure $product is valid
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }
    
        // Default values for category options
        $cat_option = 'default';
        $cat_inquire_us_link = '';
    
        // Get product categories and check for 'disable add to cart' and 'inquire us' link settings
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if (!empty($terms) && is_array($terms)) {
            foreach ($terms as $cat) {
                $category_option = get_option("wpiudacb_category_disable_add_to_cart_$cat->term_id");
                if ($category_option && $category_option !== 'default') {
                    $cat_option = $category_option;
                    $cat_inquire_us_link = get_option("wpiudacb_inqure_us_link_$cat->term_id");
                    break; // Exit loop early if a relevant option is found
                }
            }
        }
    
        // Check product-level settings
        $product_option = get_post_meta($product->get_id(), 'wpiudacb_disable_add_to_cart', true);
    
        // Remove the "Add to Cart" button and display the "Inquire Us" button if necessary
        if ($cat_option === 'inquire_us' || $product_option === 'inquire_us') {
            $inquire_us_link = $cat_option === 'inquire_us' ? $cat_inquire_us_link : get_post_meta($post->ID, 'wpiudacb_inqure_us_link', true);
    
            if (!empty($inquire_us_link)) {
                $product->inqure_us_url = $inquire_us_link;
                add_action('woocommerce_after_add_to_cart_form', 'wpiudacb_add_inqure_us_button');
            }
        }
    
        // Remove the "Add to Cart" button if any option disables it
        if ($cat_option !== 'default' || $product_option && $product_option !== 'default') {
            add_action('woocommerce_before_add_to_cart_button', 'wpiudacb_user_woocommerce_before_add_to_cart_button', 10);
            add_action('woocommerce_after_add_to_cart_button', 'wpiudacb_user_woocommerce_after_add_to_cart_button', 10);
        }
    }   

}

/**
 * Add Inqure Us Button
 * @global type $post
 */
if (!function_exists('wpiudacb_add_inqure_us_button')) {

    function wpiudacb_add_inqure_us_button($as) {
        global $post;
        global $product;
        echo '<div style="clear: both; display: block; overflow: hidden; width: 100%; margin: 10px 0;"><a href="' . esc_url($product->inqure_us_url) . '" target="_blank" class="button add_to_cart_button">'.__('Inquire Us', 'themelocationratc_hp').'</a></div>';
    }

}
// add_action('woocommerce_product_options_general_product_data', 'woocommerce_general_product_data_custom_field');

/**
 * Product Data Custom Field
 * @global type $woocommerce
 * @global type $post
 */
if (!function_exists('woocommerce_general_product_data_custom_field')) {


    function woocommerce_general_product_data_custom_field() {
        global $woocommerce, $post;
        echo '<div class="options_group">';
        woocommerce_wp_select(
                array(
                    'id' => 'wpiudacb_disable_add_to_cart',
                    'label' => __('Add to Cart Button', 'woocommerce'),
                    'options' => array(
                        'default' => __('Default', 'woocommerce'),
                        'remove_button' => __('Remove Button', 'woocommerce'),
                        'inquire_us' => __('Inquire Us', 'woocommerce')
                    )
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => 'wpiudacb_inqure_us_link',
                    'label' => __('Inquire Us Link', 'woocommerce'),
                    'placeholder' => 'http://',
                    'desc_tip' => 'true',
                    'description' => __('Enter the URL to Inquire Us button.', 'woocommerce'),
                    'value' => get_post_meta($post->ID, 'wpiudacb_inqure_us_link', true)
                )
        );

        echo '</div>';
    }

}

// Save Fields using WooCommerce Action Hook
add_action('woocommerce_process_product_meta', 'woocommerce_process_product_meta_fields_save');

/**
 * Product Meta Fields Save
 * @param type $post_id
 */
if (!function_exists('woocommerce_process_product_meta_fields_save')) {

    function woocommerce_process_product_meta_fields_save($post_id) {
        // Sanitize and save 'disable add to cart' option
        $wpiudacb_disable_add_to_cart = isset($_POST['wpiudacb_disable_add_to_cart']) ? sanitize_text_field($_POST['wpiudacb_disable_add_to_cart']) : '';
        update_post_meta($post_id, 'wpiudacb_disable_add_to_cart', $wpiudacb_disable_add_to_cart);
    
        // Sanitize and save 'inquire us' link
        $wpiudacb_inqure_us_link = isset($_POST['wpiudacb_inqure_us_link']) ? esc_url_raw($_POST['wpiudacb_inqure_us_link']) : '';
        update_post_meta($post_id, 'wpiudacb_inqure_us_link', $wpiudacb_inqure_us_link);
    }   

}

/*
 * Edit - 11/12/2017
 * By Figarts - https://figarts.co
 */

// First Register the Tab by hooking into the 'woocommerce_product_data_tabs' filter
function wpiudacb_remove_cart_data_tab( $product_data_tabs ) {
    $product_data_tabs['wpiudacb-cart'] = array(
        'label' => esc_html__( 'Remove Cart Button', 'woocommerce' ),
        'target' => 'wpiudacb_remove_cart_button',
        'class'   => array( 'show_if_wpiudacb_remove_cart_button'  ),
    );
    return $product_data_tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'wpiudacb_remove_cart_data_tab' );

/**
 * Contents of the drug options product tab.
 */
function wpiudacb_remove_cart_data_content() {
    global $post;
    ?>
    <div id='wpiudacb_remove_cart_button' class='panel woocommerce_options_panel'>
        <div class='options_group'>
            <?php
            woocommerce_wp_select(
              array(
                'id' => 'wpiudacb_disable_add_to_cart',
                'label' => __('Alter Add to Cart Button', 'woocommerce'),
                'options' => array(
                    'default' => __('Default', 'woocommerce'),
                    'remove_button' => __('Remove Button', 'woocommerce'),
                    'inquire_us' => __('Inquire Us', 'woocommerce')
                )
              )
            );
            woocommerce_wp_text_input(
              array(
                'id' => 'wpiudacb_inqure_us_link',
                'label' => __('Inquire Us Link', 'woocommerce'),
                'placeholder' => 'http://',
                'desc_tip' => 'true',
                'description' => __('Enter the URL to Inquire Us button.', 'woocommerce'),
                'value' => get_post_meta($post->ID, 'wpiudacb_inqure_us_link', true)
              )
            );
            ?>

            <p class="form-field wpiudacb_inqure_us_link_field ">
                <label for="wpiudacb_inqure_us_link"><?php echo esc_html__( 'Hide Price', 'woocommerce' ); ?></label>
                <a href="https://www.themelocation.com/remove-cart-button-plugin" style="text-decoration: none; color:red;"><?php echo esc_html__( 'Upgrade Premium Version', 'woocommerce' ); ?></a>
            </p>
            <p class="form-field wpiudacb_inqure_us_link_field ">
                <label for="wpiudacb_inqure_us_link"><?php echo esc_html__('Inquire Us Text', 'woocommerce'); ?></label>
                <a href="https://www.themelocation.com/remove-cart-button-plugin" style="text-decoration: none; color:red;"><?php echo esc_html__( 'Upgrade Premium Version', 'woocommerce' ); ?></a>
            </p>
        </div>
    </div>
    <?php
}
add_action('woocommerce_product_data_panels', 'wpiudacb_remove_cart_data_content');

function wpiudacb_custom_css() {
    // CSS to be applied in the admin area
    $custom_css = '
        #woocommerce-product-data ul.wc-tabs li.wpiudacb-cart_options a::before {
            font-family: Dashicons;
            content: "\\f174"; /* Use double backslashes to avoid accidental escapes */
        }
    ';
    // Using `wp_add_inline_style` is ideal if you have a registered admin style; otherwise, use `admin_print_styles`, but be cautious.
    echo '<style type="text/css">' . esc_attr($custom_css) . '</style>';
}

add_action('admin_head', 'wpiudacb_custom_css');

/*ENDS FIGARTS customizations*/
/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wpiudacb() {
	flush_rewrite_rules();	
}
register_deactivation_hook( __FILE__, 'deactivate_wpiudacb' );