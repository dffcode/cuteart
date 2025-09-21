<?php
/**
 * Adds Site Assist Dash
 *
 * @since 3.0.0
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates;

use ITSEC_Modules;
use ITSEC_Core;
use LearnDash_Setup_Wizard;
use LearnDash_Settings_Section;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_original_domain;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_license_key;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_authorization_token;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_disconnect_url;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_license_domain;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\is_authorized;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\build_auth_url;
use function kadence_blocks_get_current_license_data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Adds Site Assist Dash
 */
class Site_Assist_Dash {

	/**
	 * @var null
	 */
	private static $instance = null;
	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'create_admin_page' ] );
			add_action( 'admin_notices', [ $this, 'inject_before_notices' ], -9999 );
			add_action( 'admin_notices', [ $this, 'inject_after_notices' ], PHP_INT_MAX );
		}
		add_action( 'init', [ $this, 'init_config' ] );
		add_action( 'rest_api_init', [ $this, 'register_api_endpoints' ] );
	}
	/**
	 * Get the current license key for the plugin.
	 *
	 * @return string 
	 */
	public function get_current_license_key() {

		if ( function_exists( 'kadence_blocks_get_current_license_data' ) ) {
			$data = kadence_blocks_get_current_license_data();
			if ( ! empty( $data['key'] ) ) {
				return $data['key'];
			}
		} else {
			$key = get_license_key( 'kadence-starter-templates' );
			if ( ! empty( $key ) ) {
				return $key;
			}
		}
		return '';
	}
	/**
	 * Add the admin page
	 */
	public function init_config() {
		register_setting(
			'kadence_site_assist_tasks',
			'kadence_site_assist_tasks',
			[
				'type'              => 'object',
				'description'       => __( 'Site Assist Tasks', 'kadence-starter-templates' ),
				'sanitize_callback' => [ $this, 'sanitize_site_assist_tasks' ],
				'show_in_rest'      => true,
				'default'           => [],
				'show_in_rest'      => [
					'schema' => [
						'properties' => [
							'language'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the language tasks.', 'kadence-starter-templates' ),
							],
							'logo'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the logo tasks.', 'kadence-starter-templates' ),
							],
							'timezone'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the timezone tasks.', 'kadence-starter-templates' ),
							],
							'colors'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the color tasks.', 'kadence-starter-templates' ),
							],
							'header'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the header tasks.', 'kadence-starter-templates' ),
							],
							'footer'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the footer tasks.', 'kadence-starter-templates' ),
							],
							'typography'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the typography tasks.', 'kadence-starter-templates' ),
							],
							'buttons'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the button tasks.', 'kadence-starter-templates' ),
							],
							'posts'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the post tasks.', 'kadence-starter-templates' ),
							],
							'archive'      => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the archive tasks.', 'kadence-starter-templates' ),
							],
							'email-test' => [
								'type'        => 'boolean',
								'description' => esc_html__( 'The current status of the email test tasks.', 'kadence-starter-templates' ),
							],
						],
					],
				],
			]
		);
	}
	/**
	 * Sanitize the site assist tasks
	 */
	public function sanitize_site_assist_tasks( $values ) {
		// Check if the value is an array with a slug key and a boolean value and remove any invalid values
		if ( ! is_array( $values ) ) {
			return [];
		}
		foreach ( $values as $key => $value ) {
			if ( ! is_string( $key ) || ! is_bool( $value ) ) {
				unset( $values[ $key ] );
			}
		}
		return $values;
	}
	/**
	 * Register endpoints for the REST API.
	 */
	public function register_api_endpoints() {
		$library_rest = new Site_Assist_REST_Controller();
		$library_rest->register_routes();
	}
	/**
	 * Returns a base64 URL for the SVG for use in the menu.
	 *
	 * @param  bool $base64 Whether or not to return base64-encoded SVG.
	 * @return string
	 */
	private function get_icon_svg( $base64 = true ) {
		$svg = '<svg width="100%" height="100%" viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
  <path d="M475.616,28.584c-0.398,0.019 -0.794,0.07 -1.184,0.152c-41.857,0.197 -80.407,8.853 -115.41,23.485c-0.201,0.072 -0.398,0.153 -0.592,0.242c-0.326,0.152 -0.681,0.213 -1.007,0.35c-0.649,0.277 -1.258,0.645 -1.807,1.091c-2.867,1.35 -6.019,2.982 -9.387,4.827c-0.201,0.082 -0.399,0.173 -0.593,0.273c-0.363,0.181 -0.703,0.409 -1.066,0.591c-1.461,0.803 -2.309,1.228 -3.701,2.013c-49.347,25.976 -90.967,62.559 -124.531,103.623c-6.451,-2.698 -13.076,-4.723 -19.93,-5.568c-25.576,-3.152 -53.394,6.099 -79.25,24.196c-34.476,24.13 -66.389,64.184 -87.986,114.728c-0.4,0.939 -0.607,1.95 -0.607,2.971c0,4.16 3.423,7.583 7.582,7.583c1.677,-0 3.307,-0.556 4.634,-1.581c31.29,-24.27 55.619,-37.352 72.646,-40.424c8.513,-1.536 15.004,-0.743 20.433,1.836c4.681,2.22 8.799,6.022 12.557,11.698c-0.974,2.266 -2.179,4.554 -3.109,6.812c-1.167,2.83 -0.51,6.101 1.658,8.262l16.288,16.289c-14.141,21.311 -28.706,45.57 -44.451,72.763c-0.028,0 -0.063,-0.003 -0.09,0.152c-0.062,0.105 -0.121,0.211 -0.178,0.319l-0.148,0.363c-0.087,0.194 -0.166,0.392 -0.237,0.592c-0.02,0.05 -0.04,0.101 -0.06,0.152c-0.056,0.184 -0.106,0.372 -0.148,0.56c-0.062,0.246 -0.112,0.493 -0.149,0.743c-0.024,0.146 -0.044,0.293 -0.059,0.44c-0.011,0.101 -0.021,0.202 -0.028,0.304c-0.001,0.06 -0.001,0.121 -0,0.182c-0.004,0.257 0.005,0.515 0.028,0.773c0.013,0.197 0.032,0.395 0.059,0.591c0.009,0.05 0.019,0.101 0.029,0.151c0.046,0.251 0.107,0.499 0.177,0.743c0.069,0.232 0.148,0.459 0.238,0.683c0.072,0.19 0.15,0.377 0.236,0.561c0.1,0.212 0.209,0.419 0.326,0.621l0.031,0c0.029,0.051 0.059,0.102 0.089,0.152c0.113,0.161 0.231,0.318 0.356,0.47c0.029,0.051 0.059,0.102 0.089,0.152c0.132,0.163 0.27,0.319 0.415,0.47c0.124,0.126 0.252,0.247 0.385,0.364l0.266,0.333c0.154,0.123 0.312,0.239 0.474,0.349c0.039,0.051 0.079,0.102 0.119,0.152c0.172,0.123 0.351,0.239 0.533,0.349c0.221,0.127 0.448,0.243 0.681,0.348c0.117,0.054 0.236,0.105 0.356,0.152c0.107,0.053 0.215,0.104 0.325,0.152l0.149,-0c0.195,0.058 0.393,0.108 0.592,0.151c0.176,0.057 0.354,0.107 0.533,0.152c0.118,0.003 0.237,0.003 0.356,-0c0.207,0.008 0.415,0.008 0.621,-0l0.118,-0c0.508,-0 1.014,-0.051 1.511,-0.152c0.24,-0.049 0.477,-0.11 0.711,-0.182c0.251,-0.078 0.499,-0.169 0.741,-0.273c0.283,-0.119 0.561,-0.256 0.828,-0.409c0.177,-0.151 0.299,-0.197 0.474,-0.303c27.052,-15.699 51.225,-30.234 72.468,-44.334l15.725,15.726c2.161,2.169 5.433,2.826 8.263,1.659c2.214,-0.91 4.47,-2.096 6.693,-3.051c12.192,7.985 16.535,17.314 13.83,33.199c-2.928,17.189 -15.817,41.644 -40.069,72.912c-1.024,1.326 -1.58,2.957 -1.58,4.632c0,4.16 3.423,7.583 7.582,7.583c1.022,0 2.032,-0.206 2.971,-0.607c50.545,-21.598 90.598,-53.51 114.728,-87.987c23.029,-32.9 31.443,-68.925 18.51,-99.505c40.819,-33.008 77.333,-73.813 103.326,-122.161c1.053,-1.846 1.698,-3.07 2.843,-5.064c2.387,-4.158 4.474,-8.052 6.19,-11.668c0.253,-0.383 0.471,-0.79 0.651,-1.213c0.012,-0 0.018,-0 0.03,-0.152c0.127,-0.288 0.387,-0.667 0.504,-0.94c0.239,-0.569 0.409,-1.165 0.503,-1.776c14.521,-35.218 22.715,-74.397 22.715,-116.302c-0,-4.16 -3.424,-7.582 -7.582,-7.582l-0.238,-0l0.001,0.062Zm-417.155,417.746l-7.167,7.196l7.167,7.168l7.196,-7.168l-7.196,-7.196Zm28.726,-0l-7.166,7.196l7.166,7.168l7.197,-7.168l-7.197,-7.196Zm80.197,-65.803c-11.105,6.697 -22.816,13.562 -35.004,20.641l-37.996,37.996l7.196,7.166l65.804,-65.803Zm-57.866,14.778l-43.861,43.859l7.197,7.166l43.86,-43.858c-0.19,-0 -0.383,-0.152 -0.563,-0.243c-0.06,-0.05 -0.12,-0.1 -0.178,-0.151c-0.241,-0.152 -0.478,-0.289 -0.71,-0.441l0.089,0.152c-0.282,-0.166 -0.559,-0.364 -0.829,-0.561c-0.326,-0.228 -0.641,-0.485 -0.948,-0.742c-0.06,-0.06 -0.119,-0.121 -0.177,-0.182c-0.122,-0.103 -0.241,-0.21 -0.356,-0.319l-0.118,0c-0.03,-0.05 -0.06,-0.101 -0.089,-0.152c-0.107,-0.152 -0.223,-0.152 -0.326,-0.303c-0.041,-0.06 -0.08,-0.121 -0.118,-0.182c-0.128,-0.152 -0.263,-0.197 -0.386,-0.349c-0.225,-0.242 -0.442,-0.485 -0.652,-0.742c-0.02,-0.061 -0.04,-0.121 -0.059,-0.182c-0.267,-0.334 -0.474,-0.698 -0.71,-1.062c-0.36,-0.448 -0.669,-0.937 -0.918,-1.455c-0.044,-0.152 -0.105,-0.182 -0.148,-0.258l-0,0.005Zm-43.861,15.131l-7.196,7.168l7.196,7.196l7.167,-7.196l-7.167,-7.168Zm66.338,-66.365l-59.141,59.169l7.167,7.196l31.362,-31.36c7.067,-12.192 13.922,-23.908 20.612,-35.005Zm193.12,-31.402c7.033,27.367 -1.707,48.456 -13.544,67.545c-18.535,29.892 -44.567,30.723 -42.031,15.83c2.93,-17.199 -3.986,-31.051 -15.37,-41.875c7.963,-3.708 5.838,-2.802 13.817,-7.048c0.393,-0.212 0.762,-0.425 1.154,-0.621c7.036,-3.768 14.068,-7.424 21.056,-11.609c14.961,-8.729 15.78,-9.267 29.07,-18.944l5.848,-3.278Zm-53.457,-72.28c-0.193,21.969 -10.763,42.089 -34.738,63.672c-21.505,19.36 -53.912,39.947 -91.125,62.161c22.196,-37.234 42.763,-69.657 62.133,-91.155c21.6,-23.973 41.742,-34.5 63.73,-34.678Zm-97.084,-59.278c8.282,0.152 16.046,2.331 23.722,4.739c-2.483,3.38 0.072,1.341 -2.363,4.824c-0.186,0.258 -5.095,9.343 -5.282,9.616c-5.922,8.505 -5.55,7.705 -11.124,16.856c-4.695,7.604 -8.986,15.235 -13.179,22.921c-0.74,1.38 -1.49,2.699 -2.221,4.086c-4.109,7.705 -3.899,5.961 -7.501,13.667c-3.883,-4.299 -7.155,-9.542 -12.201,-11.934c-8.698,-4.125 -16.132,-5.094 -27.129,-4.612c-23.183,1.016 -10.871,-22.675 13.138,-40.916c11.523,-8.755 24.419,-16.934 35.671,-18.531c2.906,-0.412 5.709,-0.743 8.469,-0.712l0,-0.004Zm176.812,-50.14c20.576,-20.577 50.934,-32.359 56.039,-27.254c5.106,5.106 -6.232,35.907 -26.809,56.484c-20.577,20.577 -42.016,33.202 -51.898,22.667c-10.119,-10.787 2.091,-31.321 22.668,-51.897Z" />
</svg>';
		if ( $base64 ) {
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		return $svg;
	}
	/**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function create_admin_page() {
		$config = get_option( 'kadence_starter_templates_config', '' );
		$use_site_assist = apply_filters( 'kadence_starter_site_assist_enabled', true );
		if ( ! empty( $config ) ) {
			$config = json_decode( $config, true );
			if ( isset( $config['siteAssist'] ) && 'disable' === $config['siteAssist'] ) {
				$use_site_assist = false;
			}
		}
		if ( $use_site_assist ) {
			add_menu_page( __( 'Site Assist by Kadence WP', 'kadence-starter-templates' ), __( 'Site Assist', 'kadence-starter-templates' ), $this->user_capabilities(), 'kadence-starter', null, $this->get_icon_svg(), -6 );
			$page = add_submenu_page( 'kadence-starter', esc_html__( 'Site Assist by Kadence WP', 'kadence-starter-templates' ), esc_html__( 'Site Assist', 'kadence-starter-templates' ), 'manage_options', 'kadence-starter', [ $this, 'render_admin_page' ], 0 );
			add_action( 'admin_print_styles-' . $page, [ $this, 'scripts' ] );
		}
	}
	/**
	 * Allow settings visibility to be changed.
	 */
	public function user_capabilities() {
		return apply_filters( 'kadence_starter_templates_admin_settings_capability', 'manage_options' );
	}
	/**
	 * Plugin page display.
	 * Output (HTML) is in another file.
	 */
	public function render_admin_page() {
		?>
		<div class="wrap kadence_starter_dash">
			<div class="kadence_starter_assist_dashboard">
			<?php settings_errors(); ?>
				<div class="kadence_starter_site_assist_main">
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Loads admin style sheets and scripts
	 */
	public function scripts() {
		$kadence_starter_templates_meta = $this->get_asset_file( 'dist/starter-dash' );
		wp_enqueue_style( 'kadence-starter-dash', KADENCE_STARTER_TEMPLATES_URL . 'dist/starter-dash.css', [ 'wp-components' ], KADENCE_STARTER_TEMPLATES_VERSION );
		wp_enqueue_script( 'kadence-starter-dash', KADENCE_STARTER_TEMPLATES_URL . 'dist/starter-dash.js', array_merge( [ 'wp-api', 'wp-components', 'wp-plugins', 'wp-edit-post' ], $kadence_starter_templates_meta['dependencies'] ), $kadence_starter_templates_meta['version'], true );
		wp_localize_script(
			'kadence-starter-dash',
			'kadenceAssistParams',
			[
				'adminDashboard' => admin_url( 'index.php' ),
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'homeUrl'      => home_url( '/' ),
				'adminOptionsUrl' => admin_url( 'options-general.php' ),
				'siteTitle'    => get_bloginfo( 'name' ),
				'pagesUrl'     => admin_url( 'edit.php?post_type=page' ),
				'siteUrl'      => get_original_domain(),
				'primaryColor' => '#0073aa',
				'header'       => $this->get_header_content(),
				'actionCards'  => $this->get_action_content(),
				'tours'        => $this->get_tours(),
				'settings'     => get_option( 'kadence_site_assist_tasks' ),
				'outsideKB'    => $this->get_outside_kb(),
				'stellarKB'    => $this->get_stellar_kb(),
			]
		);
	}
	/**
	 * Get Stellar KB
	 */
	public function get_stellar_kb() {
		if ( ! class_exists( '\StellarWP\StellarSites\Plugin' ) ) {
			return [];
		}
		return [
			[
				'title' => __( 'StellarSites Support Hub', 'kadence-starter-templates' ),
				'url'   => 'https://my.stellarwp.com/my-account/support/',
			],
			[
				'title' => __( 'StellarSites Documentation', 'kadence-starter-templates' ),
				'url'   => 'https://stellarwp.com/docs/stellarsites/',
			],
		];
	}
	/**
	 * Get Outside KB
	 */
	public function get_outside_kb() {
		$knowledge_bases = [
			[
				'title' => __( 'Kadence Theme', 'kadence-starter-templates' ),
				'url'   => 'https://www.kadencewp.com/help-center/knowledge-base/kadence-theme/',
			],
		];
		if ( class_exists( '\KadenceWP\KadenceBlocks\App' ) ) {
			$knowledge_bases[] = [
				'title' => __( 'Kadence Blocks', 'kadence-starter-templates' ),
				'url'   => 'https://www.kadencewp.com/help-center/knowledge-base/kadence-blocks/',
			];
		}
		if ( class_exists( 'WP_SMTP' ) ) { 
			$knowledge_bases[] = [
				'title' => __( 'Solid Mail', 'kadence-starter-templates' ),
				'url'   => 'https://solidwp.com/documentation/mail/',
			];
		}
		if ( class_exists( 'ITSEC_Modules' ) ) {
			$knowledge_bases[] = [
				'title' => __( 'Solid Security', 'kadence-starter-templates' ),
				'url'   => 'https://solidwp.com/documentation/security/',
			];
		}
		if ( class_exists( 'SWPSP_PLUGIN_FILE' ) ) {
			$knowledge_bases[] = [
				'title' => __( 'Solid Performance', 'kadence-starter-templates' ),
				'url'   => 'https://solidwp.com/documentation/performance/',
			];
		}
		if ( class_exists( '\Give' ) ) {
			$knowledge_bases[] = [
				'title' => __( 'GiveWP', 'kadence-starter-templates' ),
				'url'   => 'https://givewp.com/documentation/',
			];
		}
		return $knowledge_bases;
	}
	/**
	 * Loads admin style sheets and scripts
	 */
	public function get_header_content() {
		return [
			'logo'      => KADENCE_STARTER_TEMPLATES_URL . 'assets/images/kadence_logo.png',
			'pageTitle' => __( 'Site Assist by Kadence WP', 'kadence-starter-templates' ),
		];
	}
	/**
	 * Get Tours
	 */
	public function get_tours() {
		$tours          = Site_Assist_Tours::get_instance();
		$tours_array    = $tours->get_tours();
		$tours_filtered = [];
		foreach ( $tours_array as $key => $tour ) {
			$tours_filtered[] = [
				'slug'  => $key,
				'title' => $tour['title'],
				'url'   => $tour['url'],
			];
		}
		return $tours_filtered;
	}
	/**
	 * Checks if any of the top WordPress caching plugins are active
	 *
	 * @return bool|string Returns plugin name if a caching plugin is active, false otherwise
	 */
	public function is_other_caching_plugin_active() {
		// List of top caching plugins and their main plugin files
		$caching_plugins = array(
			'WP Rocket' => 'wp-rocket/wp-rocket.php',
			'W3 Total Cache' => 'w3-total-cache/w3-total-cache.php',
			'WP Super Cache' => 'wp-super-cache/wp-super-cache.php',
			'LiteSpeed Cache' => 'litespeed-cache/litespeed-cache.php',
			'WP Fastest Cache' => 'wp-fastest-cache/wpFastestCache.php',
			'Cache Enabler' => 'cache-enabler/cache-enabler.php',
			'FlyingPress' => 'flying-press/flying-press.php',
		);
		
		// Check if any of the caching plugins are active
		foreach ($caching_plugins as $name => $plugin_file) {
			if (is_plugin_active($plugin_file)) {
				return $name; // Return the name of the active caching plugin
			}
		}
		
		return false; // No caching plugin is active
	}
	/**
	 * Get Security Data
	 */
	public function get_performance_data() {
		$performance_data    = [
			'plugin_state'      => 'notactive',
			'plugin_state_link' => 'solid-performance',
			'caching'    => false,
			'lazy_loading'    => false,
			'htaccess' => false,
		];
		$performance_data['plugin_state'] = Plugin_Check::active_check( 'solid-performance/solid-performance.php' );
		if ( defined( 'SWPSP_PLUGIN_FILE' ) ) { 
			$settings = get_option( 'solid_performance_settings', [] );
			if ( isset( $settings['page_cache']['enabled'] ) && true === $settings['page_cache']['enabled'] ) {
				$performance_data['caching'] = true;
			}
			if ( isset( $settings['page_cache']['lazy_loading']['enabled'] ) && true === $settings['page_cache']['lazy_loading']['enabled'] ) {
				$performance_data['lazy_loading'] = true;
			}
			if ( isset( $settings['page_cache']['cache_delivery']['method'] ) && 'htaccess' === $settings['page_cache']['cache_delivery']['method'] ) {
				$performance_data['htaccess'] = true;
			}
		}
		if ( $performance_data['plugin_state'] === 'notactive' ) {
			// Make sure is_plugin_active function is available
			if ( ! function_exists('is_plugin_active' ) ) {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			$active_caching_plugin = $this->is_other_caching_plugin_active();
			if ( $active_caching_plugin ) {
				return [];
			}
		}
		$return_data = [
			'title'       => __( 'Website Performance Setup', 'kadence-starter-templates' ),
			'description' => __( 'Set up the solid performance caching plugin to make sure your site loads fast.', 'kadence-starter-templates' ),
			'slug'        => 'performance-setup',
			'tasks'       => [
				[
					'title'        => ( 'installed' === $performance_data['plugin_state'] ? __( 'Activate Performance plugin', 'kadence-starter-templates' ) : __( 'Install Performance plugin', 'kadence-starter-templates' ) ),
					'description'  => __( 'Install and Activate the Solid Performance plugin.', 'kadence-starter-templates' ),
					'button'       => 'installed' === $performance_data['plugin_state'] ? __( 'Activate', 'kadence-starter-templates' ) : __( 'Install', 'kadence-starter-templates' ),
					'link'         => $performance_data['plugin_state_link'],
					'action'       => 'install_plugin',
					'completed'    => 'active' === $performance_data['plugin_state'] ? true : false,
					'plugin_state' => $performance_data['plugin_state'],
					'enables'      => 'next',
				],
				[
					'title'        => __( 'Enable Page Caching', 'kadence-starter-templates' ),
					'description'  => __( 'Enable page caching to make sure your site loads fast.', 'kadence-starter-templates' ),
					'button'       => $performance_data['caching'] ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Enable', 'kadence-starter-templates' ),
					'link'         => admin_url( 'options-general.php?page=swpsp-settings' ),
					'completed'    => $performance_data['caching'] ? true : false,
					'requires'    => $performance_data['plugin_state'] === 'active' ? false : true,
					'sameTab'     => true,
				],
				[
					'title'        => __( 'Enable Lazy Load Images', 'kadence-starter-templates' ),
					'description'  => __( 'Enable lazy load images to make sure your site loads fast.', 'kadence-starter-templates' ),
					'button'       => $performance_data['lazy_loading'] ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Enable', 'kadence-starter-templates' ),
					'link'         => admin_url( 'options-general.php?page=swpsp-settings' ),
					'completed'    => $performance_data['lazy_loading'] ? true : false,
					'requires'    => $performance_data['plugin_state'] === 'active' ? false : true,
					'sameTab'     => true,
				],
			],
		];
		if ( class_exists( '\StellarWP\StellarSites\Plugin' ) ) {
			$return_data['tasks'][] = [
				'title'        => __( 'Enable htaccess Delivery', 'kadence-starter-templates' ),
				'description'  => __( 'Enable "htaccess" delivery to bypass PHP and improve the performance of your site when cached.', 'kadence-starter-templates' ),
				'button'       => $performance_data['htaccess'] ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Enable', 'kadence-starter-templates' ),
				'link'         => admin_url( 'options-general.php?page=swpsp-settings' ),
				'completed'    => $performance_data['htaccess'] ? true : false,
				'requires'    => $performance_data['plugin_state'] === 'active' ? false : true,
				'sameTab'     => true,
			];
		}
		return $return_data;
	}
	/**
	 * Get LearnDash Data
	 */
	public function get_learndash_data() {
		$data    = [
			'plugin_setup'    => false,
			'payment_connected'    => false,
			'course_created'    => false,
			'live_mode' => false,
		];
		$is_completed = get_option('learndash_setup_wizard_status') === LearnDash_Setup_Wizard::STATUS_COMPLETED;
		if ( $is_completed ) {
			$data['plugin_setup'] = true;
		}
		$stripe_connect_activated = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Stripe_Connect', 'enabled') === 'yes';
		if ( $stripe_connect_activated ) {
			$data['payment_connected'] = true;
		}
		$courses = get_posts( [ 'post_type' => 'sfwd-courses', 'post_status' => 'publish', 'posts_per_page' => 1 ] );
		if ( ! empty( $courses ) ) {
			$data['course_created'] = true;
		}
		$learndash_data = [
			'title'       => __( 'LearnDash Setup', 'kadence-starter-templates' ),
			'description' => __( 'Set up LearnDash to make sure you have a way to create and manage courses.', 'kadence-starter-templates' ),
			'slug'        => 'learndash-setup',
			'tasks'       => [
				[
					'title'       => __( 'Install LearnDash', 'kadence-starter-templates' ),
					'description' => __( 'Install and Activate the LearnDash plugin.', 'kadence-starter-templates' ),
					'button'      => __( 'Install', 'kadence-starter-templates' ),
					'completed'   => true,
					'action'       => 'install_plugin',
					'plugin_state' => 'active',
				],
				[
					'title'        => __( 'Configure LearnDash', 'kadence-starter-templates' ),
					'description'  => __( 'Configure the LearnDash plugin.', 'kadence-starter-templates' ),
					'button'       => __( 'Configure', 'kadence-starter-templates' ),
					'link'         => admin_url( 'admin.php?page=learndash-setup-wizard' ),
					'completed'    => $data['plugin_setup'] ? true : false,
					'sameTab'     => true,
				],
				[
					'title'        => __( 'Configure Payments', 'kadence-starter-templates' ),
					'description'  => __( 'Configure the LearnDash payments.', 'kadence-starter-templates' ),
					'button'       => __( 'Configure', 'kadence-starter-templates' ),
					'link'         => admin_url( 'admin.php?page=learndash-setup' ),
					'completed'    => $data['payment_connected'] ? true : false,
					'requires'    => $data['plugin_setup'] ? false : true,
					'sameTab'     => true,
				],
				[
					'title'       => __( 'Create a Course', 'kadence-starter-templates' ),
					'description' => __( 'Create a course to make sure you have a way to create and manage courses.', 'kadence-starter-templates' ),
					'button'      => __( 'Create', 'kadence-starter-templates' ),
					'link'        => admin_url( 'edit.php?post_type=sfwd-courses' ),
					'completed'   => $data['course_created'] ? true : false,
					'requires'    => $data['plugin_setup'] ? false : true,
					'sameTab'     => true,
				],
			],
		];
		return $learndash_data;
	}
	/**
	 * Get Security Data
	 */
	public function get_donation_data() {
		$donation_data    = [
			'plugin_state'      => 'notactive',
			'plugin_state_link' => 'give',
			'plugin_setup'    => false,
			'payment_connected'    => false,
			'form_created'    => false,
			'live_mode' => false,
		];
		$donation_data['plugin_state'] = Plugin_Check::active_check( 'give/give.php' );
		// Check if give_settings has content.
		$give_settings = function_exists( 'give_get_settings' ) ? give_get_settings() : '';
		if ( ! empty( $give_settings ) ) {
			$give_onboarding = get_option( 'give_onboarding', [] );
			if ( ! empty( $give_onboarding ) ) {
				$donation_data['plugin_setup'] = true;
				$give_forms = get_posts( [ 'post_type' => 'give_forms', 'post_status' => 'publish', 'posts_per_page' => 1 ] );
				if ( ! empty( $give_forms ) ) {
					$donation_data['form_created'] = true;
				}
			}
		}
		if ( ! empty( $give_settings['gateways'] ) ) {
			if ( \Give\Helpers\Gateways\Stripe::isAccountConfigured() || (bool)give(\Give\PaymentGateways\PayPalCommerce\Repositories\MerchantDetails::class)->accountIsConnected() ) {
				$donation_data['payment_connected'] = true;
			}
		}
		if ( isset( $give_settings['test_mode'] ) && ! $give_settings['test_mode'] ) {
			$donation_data['live_mode'] = true;
		}
		return [
			'title'       => __( 'Donation Form Setup', 'kadence-starter-templates' ),
			'description' => __( 'Set up a donation form to make sure you have a way to collect donations.', 'kadence-starter-templates' ),
			'slug'        => 'donation-setup',
			'tasks'       => [
				[
					'title'        => ( 'installed' === $donation_data['plugin_state'] ? __( 'Activate GiveWP plugin', 'kadence-starter-templates' ) : __( 'Install GiveWP plugin', 'kadence-starter-templates' ) ),
					'description'  => __( 'Install and Activate the GiveWP plugin.', 'kadence-starter-templates' ),
					'button'       => 'installed' === $donation_data['plugin_state'] ? __( 'Activate', 'kadence-starter-templates' ) : __( 'Install', 'kadence-starter-templates' ),
					'link'         => $donation_data['plugin_state_link'],
					'action'       => 'install_plugin',
					'completed'    => 'active' === $donation_data['plugin_state'] ? true : false,
					'plugin_state' => $donation_data['plugin_state'],
					'enables'      => 'next',
				],
				[
					'title'        => __( 'Configure GiveWP', 'kadence-starter-templates' ),
					'description'  => __( 'Configure the GiveWP plugin.', 'kadence-starter-templates' ),
					'button'       => __( 'Configure', 'kadence-starter-templates' ),
					'link'         => admin_url( '?page=give-onboarding-wizard' ),
					'completed'    => $donation_data['plugin_setup'] ? true : false,
					'requires'    => $donation_data['plugin_state'] === 'active' ? false : true,
					'sameTab'     => true,
				],
				[
					'title'        => __( 'Connect a Payment Gateway', 'kadence-starter-templates' ),
					'description'  => __( 'Publish a donation form to make sure it is working.', 'kadence-starter-templates' ),
					'button'       => __( 'Connect', 'kadence-starter-templates' ),
					'link'         => admin_url( 'edit.php?post_type=give_forms&page=give-setup' ),
					'completed'    => $donation_data['payment_connected'] ? true : false,
					'requires'    => $donation_data['plugin_setup'] ? false : true,
					'sameTab'     => true,
				],
				[
					'title'        => __( 'Publish a Donation Form', 'kadence-starter-templates' ),
					'description'  => __( 'Publish a donation form to make sure it is working.', 'kadence-starter-templates' ),
					'button'       => $donation_data['form_created'] ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Create', 'kadence-starter-templates' ),
					'link'         => admin_url( 'edit.php?post_type=give_forms&page=give-forms' ),
					'completed'    => $donation_data['form_created'] ? true : false,
					'requires'    => $donation_data['plugin_setup'] ? false : true,
					'sameTab'     => true,
				],
				[
					'title'        => __( 'Set GiveWP in Live Mode', 'kadence-starter-templates' ),
					'description'  => __( 'Set GiveWP in live mode to start accepting donations.', 'kadence-starter-templates' ),
					'button'       => __( 'Disable Test Mode', 'kadence-starter-templates' ),
					'link'         => admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways' ),
					'completed'    => $donation_data['live_mode'] ? true : false,
					'requires'    => $donation_data['plugin_setup'] ? false : true,
					'sameTab'     => true,
				],
			],
		];
	}
	/**
	 * Get Security Data
	 */
	public function get_security_data() {
		$security_data    = [
			'plugin_state'      => 'notactive',
			'plugin_state_link' => 'ithemes-security-pro',
			'plugin_setup'    => false,
			'two_factor' => false,
			'trusted_devices' => false,
			'firewall' => false,
		];
		
		$security_data['plugin_state'] = Plugin_Check::active_check( 'ithemes-security-pro/ithemes-security-pro.php' );
		if ( 'notactive' === $security_data['plugin_state'] ) {
			$security_data['plugin_state'] = Plugin_Check::active_check( 'better-wp-security/better-wp-security.php' );
			$security_data['plugin_state_link'] = 'better-wp-security';
		} else if ( 'installed' === $security_data['plugin_state'] ) {
			$free_active = Plugin_Check::active_check( 'better-wp-security/better-wp-security.php' );
			if ( 'active' === $free_active ) {
				$security_data['plugin_state'] = 'active';
				$security_data['plugin_state_link'] = 'better-wp-security';
			}
		}
		$onboard_complete = class_exists( 'ITSEC_Modules' ) ? ITSEC_Modules::get_setting( 'global', 'onboard_complete' ) : false;
		if ( $onboard_complete ) {
			$security_data['plugin_setup'] = true;
		}
		$two_factor = class_exists( 'ITSEC_Modules' ) ? ITSEC_Modules::is_active( 'two-factor' ) : false;
		if ( $two_factor ) {
			$security_data['two_factor'] = true;
		}
		$trusted_devices = class_exists( 'ITSEC_Modules' ) ? ITSEC_Modules::is_active( 'fingerprinting' ) : false;
		if ( $trusted_devices ) {
			$security_data['trusted_devices'] = true;
		}
		$firewall = class_exists( 'ITSEC_Core' ) ? ITSEC_Core::has_patchstack() : false;
		if ( $firewall ) {
			$security_data['firewall'] = true;
		}

		$return_data = [
			'title'       => __( 'Site Security Setup', 'kadence-starter-templates' ),
			'description' => __( 'Set up important security standards to prevent hacks and malware.', 'kadence-starter-templates' ),
			'slug'        => 'site-security',
			'tasks'       => [
				[
					'title'        => ( 'installed' === $security_data['plugin_state'] ? __( 'Activate Site Security plugin', 'kadence-starter-templates' ) : __( 'Install Site Security plugin', 'kadence-starter-templates' ) ),
					'description'  => __( 'Install and Activate the Solid Security plugin.', 'kadence-starter-templates' ),
					'button'       => 'installed' === $security_data['plugin_state'] ? __( 'Activate', 'kadence-starter-templates' ) : __( 'Install', 'kadence-starter-templates' ),
					'link'         => $security_data['plugin_state_link'],
					'action'       => 'install_plugin',
					'completed'    => 'active' === $security_data['plugin_state'] ? true : false,
					'plugin_state' => $security_data['plugin_state'],
					'enables'      => 'next',
				],
				[
					'title'       =>  __( 'Set Up Security plugin', 'kadence-starter-templates' ),
					'description' => __( 'Complete the one-time setup of the security plugin.', 'kadence-starter-templates' ),
					'button'      => $security_data['plugin_setup'] ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Set Up', 'kadence-starter-templates' ),
					'link'        => $security_data['plugin_setup'] ? admin_url( 'admin.php?page=itsec&path=%2Fsettings%2Fglobal' ) : admin_url( 'admin.php?page=itsec&path=%2Fonboard%2Fsite-type' ),
					'completed'    => $security_data['plugin_setup'],
					'requires'    => 'active' === $security_data['plugin_state'] ? false : true,
					'sameTab'     => true,
				],
				[
					'title'       => __( '(Recommended) Enable Two Factor Login Authentication', 'kadence-starter-templates' ),
					'description' => __( 'Two-Factor Authentication greatly increases the security of your WordPress user account by requiring an additional code along with your username and password to log in.', 'kadence-starter-templates' ),
					'button'      => ! empty( $security_data['two_factor'] ) ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Set Up', 'kadence-starter-templates' ),
					'link'        => admin_url( 'admin.php?page=itsec&path=%2Fsettings%2Fconfigure%2Flogin' ),
					'completed'   => ! empty( $security_data['two_factor'] ) ? true : false,
					'requires'    => $security_data['plugin_setup'] ? false : true,
					'sameTab'     => true,
				],
			],
		];
		if ( $security_data['plugin_state_link'] !== 'better-wp-security' ) {
			$return_data['tasks'][] = [
				'title'       => __( '(Recommended) Enable Trusted Devices', 'kadence-starter-templates' ),
				'description' => __( 'Trusted Devices identifies the devices users use to log in and can apply additional restrictions to unknown devices.', 'kadence-starter-templates' ),
				'button'      => ! empty( $security_data['trusted_devices'] ) ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Set Up', 'kadence-starter-templates' ),
				'link'        => admin_url( 'admin.php?page=itsec&path=%2Fsettings%2Fconfigure%2Flogin' ),
				'completed'   => ! empty( $security_data['trusted_devices'] ) ? true : false,
				'requires'    => $security_data['plugin_setup'] ? false : true,
				'sameTab'     => true,
			];
			$return_data['tasks'][] = [
				'title'       => __( '(Recommended) Virtual Patching', 'kadence-starter-templates' ),
				'description' => __( 'Get instant protection against new threats with virtual patching.', 'kadence-starter-templates' ),
				'button'      => ! empty( $security_data['firewall'] ) ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Set Up', 'kadence-starter-templates' ),
				'link'        => admin_url( 'admin.php?page=itsec-firewall&path=%2Fautomated' ),
				'completed'   => ! empty( $security_data['firewall'] ) ? true : false,
				'requires'    => $security_data['plugin_setup'] ? false : true,
				'sameTab'     => true,
			];
		}
		if ( $security_data['plugin_state'] === 'notactive' ) {
			// Check for Wordfence
			if (function_exists('wordfence_install') || 
			(defined('WORDFENCE_VERSION') && WORDFENCE_VERSION) || 
			class_exists('wordfence')) { 
				return [];
			}
			// Check for MalCare
			if (function_exists('bvmc_install') || 
			defined('BVMCVERSION') || 
			class_exists('MalCare\\Plugin')) {
				return [];
			}
			// Check for All In One WP Security
			if (function_exists('aiowps_activate') || 
			defined('AIO_WP_SECURITY_VERSION') || 
			class_exists('AIOWPSEC_Installer')) {
				return [];
			}
		}
		return $return_data;
	}
	/**
	 * Get email Data
	 */
	public function get_email_data($site_assist_data) {
		$email_data    = [
			'plugin_state'      => 'notactive',
			'plugin_state_link' => 'wp-smtp',
			'plugin_setup'    => false,
			'plugin_verified' => ! empty( $site_assist_data['email_test'] ) ? true : false,
		];
		$email_data['plugin_state'] = Plugin_Check::active_check( 'wp-smtp/wp-smtp.php' );
		if ( class_exists( 'WP_SMTP' ) ) { 
			$providers = get_option( 'solid_smtp_providers', [] );
			if ( ! empty( $providers ) && is_array( $providers ) ) {
				foreach ( $providers as $provider ) {
					if ( ! empty( $provider['is_active'] ) && true === $provider['is_active'] ) {
						$email_data['plugin_setup'] = true;
					}
				}
			}
		}
		if ( $email_data['plugin_state'] === 'notactive' ) {
		// Check for Fluent SMTP
			if (function_exists('fluentSmtpInit') || 
			defined('FLUENTMAIL') || 
			class_exists('FluentMail\\App\\App')) {
				return [];
			}
		
			// Check for WP Mail SMTP
			if (function_exists('wp_mail_smtp') || 
			defined('WPMS_PLUGIN_VER') || 
			class_exists('WPMailSMTP\\WP_Mail_SMTP')) {
				return [];
			}
		
			// Check for Sure Mail
			if (function_exists('sure_mail_init') || 
			defined('SURE_MAIL_VERSION') || 
			class_exists('SureMail\\Plugin')) {
				return [];
			}
		}
		return [
			'title'       => __( 'Website Email Setup', 'kadence-starter-templates' ),
			'description' => __( 'Set up SMTP email to make sure you have good delivery of site notifications and transactional emails.', 'kadence-starter-templates' ),
			'slug'        => 'email-setup',
			'tasks'       => [
				[
					'title'        => ( 'installed' === $email_data['plugin_state'] ? __( 'Activate SMTP plugin', 'kadence-starter-templates' ) : __( 'Install SMTP plugin', 'kadence-starter-templates' ) ),
					'description'  => __( 'By default, WordPress uses the PHP mail send out site-related messages (such as password resets, contact form notifications, and order notifications). However, this default method often runs into problems with email reliability and deliverability because the source can cause email providers to flag the email as spam. An SMTP (Simple Mail Transfer Protocol) plugin bypasses those limitations by authenticating and relaying your site\'s emails through a trusted mail server. This improves the overall reliability of email delivery and reduces the chances of messages being blocked or ending up in spam folders. Install and Activate the SMTP plugin.', 'kadence-starter-templates' ),
					'button'       => 'installed' === $email_data['plugin_state'] ? __( 'Activate', 'kadence-starter-templates' ) : __( 'Install', 'kadence-starter-templates' ),
					'link'         => $email_data['plugin_state_link'],
					'action'       => 'install_plugin',
					'completed'    => 'active' === $email_data['plugin_state'] ? true : false,
					'plugin_state' => $email_data['plugin_state'],
					'enables'      => 'next',
				],
				[
					'title'        => __( 'Configure Email Connection', 'kadence-starter-templates' ),
					'description'  => __( 'Configure the SMTP plugin to connect to your email provider. An SMTP (Simple Mail Transfer Protocol) connection authenticates and relays your site\'s emails through a trusted mail server. This improves the overall reliability of email delivery and reduces the chances of messages being blocked or ending up in spam folders. ', 'kadence-starter-templates' ),
					'button'       => __( 'Configure', 'kadence-starter-templates' ),
					'link'         => admin_url( 'admin.php?page=solidwp-mail' ),
					'completed'    => $email_data['plugin_setup'] ? true : false,
					'requires'    => $email_data['plugin_state'] === 'active' ? false : true,
					'sameTab'     => true,
				],
				[
					'title'        => __( 'Test Email Connection', 'kadence-starter-templates' ),
					'description'  => __( 'Test the email connection to make sure it is working and emails are arriving in your inbox.', 'kadence-starter-templates' ),
					'button'       => __( 'Test', 'kadence-starter-templates' ),
					'link'         => admin_url( 'admin.php?page=solidwp-mail#/email-test' ),
					'completed'    => $email_data['plugin_verified'] ? true : false,
					'manual'       => 'email-test',
					'requires'    => $email_data['plugin_setup'] ? false : true,
					'sameTab'     => true,
				],
			],
		];
	}
	/**
	 * Loads admin style sheets and scripts
	 */
	public function get_action_content() {
		$slug = class_exists( '\KadenceWP\KadenceBlocks\App' ) ? 'kadence-blocks' : 'kadence-starter-templates';
		if ( class_exists( '\KadenceWP\KadenceBlocks\App' ) ) {
			$token          = \KadenceWP\KadenceBlocks\StellarWP\Uplink\get_authorization_token( $slug );
			$auth_url       = \KadenceWP\KadenceBlocks\StellarWP\Uplink\build_auth_url( apply_filters( 'kadence-blocks-auth-slug', $slug ), get_license_domain() );
		} else {
			$token          = get_authorization_token( $slug );
			$auth_url       = build_auth_url( apply_filters( 'kadence-blocks-auth-slug', $slug ), get_license_domain() );
		}
		$license_key    = $this->get_current_license_key();
		$disconnect_url = '';
		$is_authorized  = false;
		if ( ! empty( $license_key ) ) {
			$is_authorized = is_authorized( $license_key, apply_filters( 'kadence-blocks-auth-slug', $slug ), ( ! empty( $token ) ? $token : '' ), get_license_domain() );
		}
		if ( $is_authorized ) {
			$disconnect_url = get_disconnect_url( apply_filters( 'kadence-blocks-auth-slug', $slug ) );
		}
		$prophecy_data = json_decode( get_option( 'kadence_blocks_prophecy' ), true );
		$site_assist_data = get_option( 'kadence_site_assist_tasks' );
		// Get site title and tagline.
		$site_title = get_bloginfo( 'name' );
		$site_tagline = get_bloginfo( 'description' );
		$site_title_subtitle_label = $site_title . ' - ' . $site_tagline;
		$site_title_tagline_completed = true;
		if ( empty( $site_title ) ) {
			$site_title_subtitle_label = __( 'Empty Site Title', 'kadence-starter-templates' );
			$site_title_tagline_completed = false;
		} else if ( empty( $site_tagline ) ) {
			$site_title_subtitle_label = __( 'Empty Site Tagline', 'kadence-starter-templates' );
			$site_title_tagline_completed = false;
		} else if ( $site_tagline === 'Just another WordPress site' ) {
			$site_title_subtitle_label = __( 'Default WordPress Tagline', 'kadence-starter-templates' );
			$site_title_tagline_completed = false;
		}
		// Get site icon.
		$site_icon = get_site_icon_url();
		// Get site logo.
		$site_logo = get_custom_logo();
		$site_logo_completed = false;
		if ( ! empty( $site_logo ) ) {
			$site_logo_completed = true;
		} else if ( ! empty( $site_assist_data['logo'] ) ) {
			$site_logo_completed = true;
		}
		// Get goals.
		$goals = isset( $prophecy_data['goals'] ) && is_array( $prophecy_data['goals'] ) ? $prophecy_data['goals'] : [];
		$donation_data = [];

		if ( in_array( 'donations', $goals ) ) {
			$donation_data = $this->get_donation_data();
		}
		$learndash_data = [];
		if ( class_exists( 'LearnDash_Settings_Section' ) ) {
			$learndash_data = $this->get_learndash_data();
		}
		$security_data = $this->get_security_data();
		$email_data = $this->get_email_data( $site_assist_data );
		$performance_data = $this->get_performance_data();
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$translations = wp_get_available_translations();
		$current_language = get_locale();
		if ( 'en_US' === $current_language ) {
			$current_language_label = 'English (United States)';
		} else {
			$current_language_label = isset( $translations[ $current_language ]['native_name'] ) ? $translations[ $current_language ]['native_name'] : $current_language;
		}
		// Get Timezone Label
		$current_offset = get_option( 'gmt_offset' );
		$tzstring       = get_option( 'timezone_string' );
		$timesone_set = false;
		if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists.
			if ( 0 === (int) $current_offset ) {
				$tzstring = 'UTC+0';
			} elseif ( $current_offset < 0 ) {
				$timesone_set = true;
				$tzstring = 'UTC' . $current_offset;
			} else {
				$timesone_set = true;
				$tzstring = 'UTC+' . $current_offset;
			}
		} else {
			$timesone_set = true;
		}
		$old_data = get_option( '_kadence_starter_templates_last_import_data', array() );
		$has_content = false;
		$has_previous = false;
		if ( ! empty( $old_data ) ) {
			$has_content  = true;
			$has_previous = true;
		}
		// Check for multiple posts.
		if ( false === $has_content ) {
			$has_content = ( 1 < wp_count_posts()->publish ? true : false );
		}
		if ( false === $has_content ) {
			// Check for multiple pages.
			$has_content = ( 1 < wp_count_posts( 'page' )->publish ? true : false );
		}
		if ( false === $has_content ) {
			// Check for multiple images.
			$has_content = ( 0 < wp_count_posts( 'attachment' )->inherit ? true : false );
		}
		$has_ai_profile = ( !empty( $goals ) ? true : false );
		$policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
		$policy_page_published = false;
		$policy_url = admin_url( 'options-privacy.php' );
		if ( ! empty( $policy_page_id ) ) {
			$policy_page = get_post( $policy_page_id );
			if ( ! empty( $policy_page ) ) {
				$policy_url = admin_url( 'post.php?post=' . $policy_page_id . '&action=edit' );
				// Check if the policy page is published.
				if ( 'publish' === $policy_page->post_status ) {
					$policy_page_published = true;
				}
			}
		}
		// Get the current user display name.
		$current_user = wp_get_current_user();
		$display_name = $current_user->display_name;
		// Username.
		$username = $current_user->user_login;
		$return_data = [
			[
				'title'       => __( 'AI Starter Site', 'kadence-starter-templates' ),
				'description' => __( 'Get started with your new site by setting up key information and importing a starter site.', 'kadence-starter-templates' ),
				'slug'        => 'ai-starter-site',
				'tasks'       => [
					[
						'title'       => __( 'Activate Kadence AI', 'kadence-starter-templates' ),
						'description' => __( 'Connect Kadence AI to your site by clicking the button below.', 'kadence-starter-templates' ),
						'button'      => ! $is_authorized ? __( 'Activate', 'kadence-starter-templates' ) : __( 'Connected', 'kadence-starter-templates' ),
						'link'        => ! $is_authorized ? $auth_url : '',
						'completed'   => $is_authorized ? true : false,
						'requires'    => ! $is_authorized ? false : true,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Set up Site AI Profile', 'kadence-starter-templates' ),
						'description' => __( 'Set up your site AI profile to get started with your new site.', 'kadence-starter-templates' ),
						'button'      => $has_ai_profile ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Set Up', 'kadence-starter-templates' ),
						'link'        => admin_url( 'admin.php?page=kadence-starter-templates&ai=wizard' ),
						'completed'   => $has_ai_profile,
						'requires'    => $is_authorized ? false : true,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Import AI Starter Site', 'kadence-starter-templates' ),
						'description' => __( 'Import an AI Starter Site to get started with your new site.', 'kadence-starter-templates' ),
						'button'      => $has_previous ? __( 'Re-Import', 'kadence-starter-templates' ) : __( 'Import', 'kadence-starter-templates' ),
						'link'        => admin_url( 'admin.php?page=kadence-starter-templates' ),
						'completed'   => $has_previous,
						'sameTab'     => true,
						'requires'    => $has_ai_profile ? false : true,
					]
				]
			],
			[
				'title'       => __( 'Basic Site Setup', 'kadence-starter-templates' ),
				'description' => __( 'Get started with your new site by setting up key information.', 'kadence-starter-templates' ),
				'slug'        => 'site-setup',
				'tasks'       => [
					[
						'title'       => __( 'Provide Site Title & Tagline', 'kadence-starter-templates' ),
						'description' => __( 'Give your site a name and a tagline.', 'kadence-starter-templates' ),
						'image'       => KADENCE_STARTER_TEMPLATES_URL . 'assets/images/tasks/site-title-tagline.jpg',
						'button'      => $site_title_tagline_completed ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Set Up', 'kadence-starter-templates' ),
						'link'        => admin_url( 'options-general.php' ),
						'completed'   => $site_title_tagline_completed,
						'subtitle'    => $site_title_subtitle_label,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Set Site Icon', 'kadence-starter-templates' ),
						'description' => __( 'Set the site icon that appears in browser tabs, bookmark bars, and within the WordPress mobile apps.', 'kadence-starter-templates' ),
						'subtitle'    => ! empty( $site_icon ) ? '<img src="' . $site_icon . '" alt="' . __( 'Site Icon', 'kadence-starter-templates' ) . '" />' : __( 'Unset', 'kadence-starter-templates' ),
						'image'       => KADENCE_STARTER_TEMPLATES_URL . 'assets/images/tasks/site-icon.jpg',
						'button'      => ! empty( $site_icon ) ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Set Up', 'kadence-starter-templates' ),
						'link'        => admin_url( 'options-general.php' ),
						'completed'   => ! empty( $site_icon ) ? true : false,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Upload Site Logo', 'kadence-starter-templates' ),
						'subtitle'    => ! empty( $site_logo ) ? $site_logo : __( 'Unset', 'kadence-starter-templates' ),
						'description' => __( 'Upload a logo for your site.', 'kadence-starter-templates' ),
						'image'       => KADENCE_STARTER_TEMPLATES_URL . 'assets/images/tasks/site-logo.jpg',
						'button'      => ! empty( $site_logo ) ? __( 'Edit', 'kadence-starter-templates' ) : __( 'Set Up', 'kadence-starter-templates' ),
						'manual'      => 'logo',
						'link'        => admin_url( 'customize.php?autofocus%5Bsection%5D=title_tagline' ),
						'completed'   => ! empty( $site_logo ) || ! empty( $site_assist_data['logo'] ) ? true : false,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Set Site Language', 'kadence-starter-templates' ),
						'subtitle'    => $current_language_label,
						'image'       => KADENCE_STARTER_TEMPLATES_URL . 'assets/images/tasks/language.jpg',
						'description' => __( 'Set the language of your site.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'options-general.php' ),
						'manual'      => 'language',
						'completed'   => ! empty( $site_assist_data['language'] ) ? true : false,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Set Site Timezone', 'kadence-starter-templates' ),
						'subtitle'    => $tzstring,
						'image'       => KADENCE_STARTER_TEMPLATES_URL . 'assets/images/tasks/timezone.jpg',
						'description' => __( 'Set the timezone of your site.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'options-general.php' ),
						'manual'      => 'timezone',
						'completed'   => ! empty( $site_assist_data['timezone'] ) || $timesone_set ? true : false,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Set your display name', 'kadence-starter-templates' ),
						'subtitle'    => $display_name,
						'image'       => KADENCE_STARTER_TEMPLATES_URL . 'assets/images/tasks/display-name.jpg',
						'description' => __( 'Set your display name so that it appears correctly on your site for authored posts and comments.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'profile.php' ),
						'manual'      => 'display_name',
						'completed'   => $display_name !== $username ? true : false,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Publish Privacy Policy Page', 'kadence-starter-templates' ),
						'description' => __( 'Publish a privacy policy page.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => $policy_url,
						'completed'   => $policy_page_published ? true : false,
						'sameTab'     => true,
					]
				],
			],
			[
				'title'       => __( 'Design & Customization', 'kadence-starter-templates' ),
				'description' => __( 'Customize your site to your liking.', 'kadence-starter-templates' ),
				'slug'        => 'design-customization',
				'tasks'       => [
					[
						'title'       => __( 'Customize Colors', 'kadence-starter-templates' ),
						'description' => __( 'Customize the colors of your site.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'customize.php?autofocus%5Bsection%5D=kadence_customizer_general_colors' ),
						'completed'   => ! empty( $site_assist_data['colors'] ) ? true : false,
						'manual'      => 'colors',
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Customize Header', 'kadence-starter-templates' ),
						'description' => __( 'Customize the header of your site.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'manual'      => 'header',
						'link'        => admin_url( 'customize.php?autofocus%5Bpanel%5D=kadence_customizer_header' ),
						'completed'   => ! empty( $site_assist_data['header'] ) ? true : false,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Customize Footer', 'kadence-starter-templates' ),
						'description' => __( 'Customize the footer of your site.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'manual'      => 'footer',
						'link'        => admin_url( 'customize.php?autofocus%5Bpanel%5D=kadence_customizer_footer' ),
						'completed'   => ! empty( $site_assist_data['footer'] ) ? true : false,
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Customize Typography', 'kadence-starter-templates' ),
						'description' => __( 'Customize the typography of your site.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'customize.php?autofocus%5Bsection%5D=kadence_customizer_general_typography' ),
						'completed'   => ! empty( $site_assist_data['typography'] ) ? true : false,
						'manual'      => 'typography',
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Customize Buttons', 'kadence-starter-templates' ),
						'description' => __( 'Customize the button styles for your site.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'customize.php?autofocus%5Bsection%5D=kadence_customizer_general_buttons' ),
						'completed'   => ! empty( $site_assist_data['buttons'] ) ? true : false,
						'manual'      => 'buttons',
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Customize Posts Layout', 'kadence-starter-templates' ),
						'description' => __( 'Customize the layout of your posts.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'customize.php?autofocus%5Bsection%5D=kadence_customizer_post_layout' ),
						'completed'   => ! empty( $site_assist_data['posts'] ) ? true : false,
						'manual'      => 'posts',
						'sameTab'     => true,
					],
					[
						'title'       => __( 'Customize Archive Layout', 'kadence-starter-templates' ),
						'description' => __( 'Customize the layout of your archive pages.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'customize.php?autofocus%5Bsection%5D=kadence_customizer_post_archive' ),
						'completed'   => ! empty( $site_assist_data['archive'] ) ? true : false,
						'manual'      => 'archive',
						'sameTab'     => true,
					],
				],
			],
		];
		if ( !empty ( $email_data ) ) {
			$return_data[] = $email_data;
		}
		if ( !empty ( $security_data ) ) {
			$return_data[] = $security_data;
		}
		if ( !empty ( $performance_data ) ) {
			$return_data[] = $performance_data;
		}
		if ( !empty ( $donation_data ) ) {
			$return_data[] = $donation_data;
		}
		if ( !empty ( $learndash_data ) ) {
			$return_data[] = $learndash_data;
		}
		if ( class_exists( '\StellarWP\StellarSites\Plugin' ) ) {
			$return_data[] = [
				'title'       => __( 'Ready to Launch', 'kadence-starter-templates' ),
				'description' => __( 'Get ready to launch your new site.', 'kadence-starter-templates' ),
				'slug'        => 'ready-to-launch',
				'tasks'       => [
					[
						'title'       => __( 'Turn Off Coming Soon Mode', 'kadence-starter-templates' ),
						'description' => __( 'Turn off the coming soon mode that temporarily hides your site.', 'kadence-starter-templates' ),
						'button'      => __( 'Edit', 'kadence-starter-templates' ),
						'link'        => admin_url( 'admin.php?page=stellarsites' ),
						'completed'   => get_option( 'stellarsites_coming_soon' ) ? false : true,
						'sameTab'     => true,
					]
				],
			];
		}
		return $return_data;
	}
	/**
	 * Get the asset file produced by wp scripts.
	 *
	 * @param string $filepath the file path.
	 * @return array
	 */
	public function get_asset_file( $filepath ) {
		$asset_path = KADENCE_STARTER_TEMPLATES_PATH . $filepath . '.asset.php';
		return file_exists( $asset_path )
			? include $asset_path
			: [
				'dependencies' => [ 'lodash', 'react', 'react-dom', 'wp-block-editor', 'wp-blocks', 'wp-data', 'wp-element', 'wp-i18n', 'wp-polyfill', 'wp-primitives', 'wp-api' ],
				'version'      => KADENCE_STARTER_TEMPLATES_VERSION,
			];
	}
	/**
	 * Returns true if we are on a JS powered admin page.
	 */
	public static function is_admin_page() {
		// phpcs:disable WordPress.Security.NonceVerification
		return isset( $_GET['page'] ) && 'kadence-starter' === $_GET['page'];
		// phpcs:enable WordPress.Security.NonceVerification
	}
	/**
	 * Runs before admin notices action and hides them.
	 */
	public static function inject_before_notices() {
		if ( ! self::is_admin_page() ) {
			return;
		}

		// The JITMs won't be shown in the Onboarding Wizard.
		$is_onboarding   = isset( $_GET['path'] ) && '/setup-wizard' === wc_clean( wp_unslash( $_GET['path'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$maybe_hide_jitm = $is_onboarding ? '-hide' : '';

		echo '<div class="kadence-dash-js-notices" id="jp-admin-notices"></div>';

		// Wrap the notices in a hidden div to prevent flickering before
		// they are moved elsewhere in the page by WordPress Core.
		echo '<div class="kadence-dash__notice-list-hide" id="wp__notice-list">';

		// Capture all notices and hide them. WordPress Core looks for
		// `.wp-header-end` and appends notices after it if found.
		// https://github.com/WordPress/WordPress/blob/f6a37e7d39e2534d05b9e542045174498edfe536/wp-admin/js/common.js#L737 .
		echo '<div class="wp-header-end" id="kadence-dash__notice-catcher"></div>';
	}

	/**
	 * Runs after admin notices and closes div.
	 */
	public static function inject_after_notices() {
		if ( ! self::is_admin_page() ) {
			return;
		}

		// Close the hidden div used to prevent notices from flickering before
		// they are inserted elsewhere in the page.
		echo '</div>';
	}
}
Site_Assist_Dash::get_instance();
