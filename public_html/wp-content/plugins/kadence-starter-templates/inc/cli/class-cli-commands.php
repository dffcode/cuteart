<?php
/**
 * CLI Commands Handler
 *
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates\CLI;

use WP_CLI;
use KadenceWP\KadenceStarterTemplates\Starter_Import_Processes;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_original_domain;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_license_key;
use function kadence_blocks_get_current_license_data;
use function wp_remote_get;
use function is_wp_error;
use function wp_remote_retrieve_response_code;
use function wp_remote_retrieve_body;
/**
 * CLI Commands Class
 */
class CLI_Commands {

	/**
	 * Import selection data
	 *
	 * @var array
	 */
	private $import_selection_data;

	/**
	 * Import base sites
	 *
	 * @var array
	 */
	private $import_base_sites;

	/**
	 * Import base site
	 *
	 * @var array
	 */
	private $import_base_site;

	/**
	 * Plugins to check.
	 */
	private $plugins_to_check = [
		'woocommerce',
		'elementor',
		'kadence-blocks',
		'kadence-blocks-pro',
		'kadence-pro',
		'fluentform',
		'wpzoom-recipe-card',
		'learndash',
		'learndash-course-grid',
		'lifterlms',
		'tutor',
		'give',
		'the-events-calendar',
		'event-tickets',
		'orderable',
		'restrict-content',
		'kadence-woo-extras',
		'seriously-simple-podcasting',
		'bookit',
	];
	/**
	 * Product IDs
	 *
	 * @var array
	 */
	private $product_ids = [];
	/**
	 * Give form ID
	 *
	 * @var string
	 */
	private $give_form_id;
	/**
	 * Ai content
	 *
	 * @var array
	 */
	private $ai_content = [];

	/**
	 * Team image collection
	 *
	 * @var array
	 */
	private $team_image_collection = [];
	/**
	 * Import key
	 *
	 * @var string
	 */
	private $import_key;

	/**
	 * Initialize the CLI commands
	 */
	public function __construct() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'kadence-starter run', array( $this, 'run_commands' ) );
		}
	}

	/**
	 * Run sequential commands
	 *
	 * ## OPTIONS
	 *
	 * [--task=<task>]
	 * : The specific task to run
	 * 
	 * [--id=<id>]
	 * : The specific id to run
	 *
	 * [--env=<env>]
	 * : The specific environment to run (dev, staging, live)
	 * 
	 * ## EXAMPLES
	 *
	 * wp kadence-starter run --task=import
	 *
	 * @when after_wp_load
	 * @param array $args Arguments.
	 * @param array $assoc_args Associated arguments.
	 */
	public function run_commands( $args, $assoc_args ) {
		$task = isset( $assoc_args['task'] ) ? $assoc_args['task'] : 'default';
		$id = isset( $assoc_args['id'] ) ? $assoc_args['id'] : '';
		$env = isset( $assoc_args['env'] ) ? $assoc_args['env'] : 'live';

		try {
			switch ( $task ) {
				case 'import':
					WP_CLI::log( 'Starting import task sequence...' );
					
					// First task
					WP_CLI::log( 'Getting import selection data...' );
					$this->get_import_selection_data( $id, $env );
					WP_CLI::success( 'Import selection data received' );

					// Second task
					WP_CLI::log( 'Removing default content...' );
					$this->remove_content();
					WP_CLI::success( 'Default content removed' );

					// Third task
					WP_CLI::log( 'Perhaps install plugins...' );
					$this->install_plugins();
					WP_CLI::success( 'Plugins stage complete' );

					// Fourth task
					WP_CLI::log( 'Perhaps install posts...' );
					$this->install_posts();
					WP_CLI::success( 'Posts stage complete' );

					// Fifth task
					WP_CLI::log( 'Perhaps install products...' );
					$this->install_products();
					WP_CLI::success( 'Products stage complete' );

					// Sixth task
					WP_CLI::log( 'Perhaps install events...' );
					$this->install_events();
					WP_CLI::success( 'Events stage complete' );

					// Seventh task
					WP_CLI::log( 'Perhaps install course...' );
					$this->install_course();
					WP_CLI::success( 'Course stage complete' );

					// Eighth task
					WP_CLI::log( 'Perhaps install give form...' );
					$this->install_give_form();
					WP_CLI::success( 'Give form stage complete' );

					// Ninth task
					WP_CLI::log( 'Perhaps install pages...' );
					$this->install_pages();
					WP_CLI::success( 'Pages stage complete' );

					// Ninth task
					WP_CLI::log( 'Perhaps install theme settings...' );
					$this->install_theme();
					WP_CLI::success( 'Theme stage complete' );
					
					// Tenth task
					WP_CLI::log( 'Perhaps install navigation...' );
					$this->install_navigation();
					WP_CLI::success( 'Navigation stage complete' );
					
					// Eleventh task
					WP_CLI::log( 'Perhaps install widgets...' );
					$this->install_widgets();
					WP_CLI::success( 'Widgets stage complete' );
				

					break;

				default:
					WP_CLI::error( 'Invalid task specified.' );
					break;
			}

			WP_CLI::success( 'All tasks completed successfully!' );

		} catch ( \Exception $e ) {
			WP_CLI::error( 'Error: ' . $e->getMessage() );
		}
	}
	/**
	 * Check if a response code is an error.
	 *
	 * @access public
	 * @return string Returns the remote URL contents.
	 */
	public function is_response_code_error( $response ) {
		$response_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $response_code >= 200 && $response_code < 300 ) {
			return false;
		} else {
			return true;
		}
	}
	/**
	 * Example first task
	 */
	private function get_import_selection_data( $id = '', $env = 'live' ) {
		// Get the local license key
		$license_data = Starter_Import_Processes::get_instance()->get_pro_license_data();
		// Make a request to the API to get the import selection data
		$key = ( !empty( $license_data['api_key'] ) ? $license_data['api_key'] : '' );
		$args = [
			'key'       => ( !empty( $id ) ? $id : $key ),
			'site_url'  => ( !empty( $license_data['site_url'] ) ? $license_data['site_url'] : '' ),
		];
		if ( empty( $args['key'] ) && 'live' === $env ) {
			WP_CLI::log( 'No license key found.' );
			WP_CLI::halt( 0 );
			return;
		}
		if ( 'dev' === $env ) {
			$args['env'] = 'dev';
		}
		if ( 'staging' === $env ) {
			$args['env'] = 'staging';
		}
		$api_url  = add_query_arg( $args, 'https://base.startertemplatecloud.com/wp-json/kadence-starter-base/v1/data' );
		// Get the response.
		$response = wp_safe_remote_get(
			$api_url,
			array(
				'timeout' => 20,
			)
		);
		$temp_site_name = get_option( 'blogname' );
		// if the temp site name contains .nxcli.io then set it to the site url as a better temp name.
		if ( strpos( $temp_site_name, '.nxcli.io' ) !== false ) {
			// Get site url.
			$site_url = get_option( 'siteurl' );
			if ( ! empty( $site_url ) ) {
				// Get the domain from the site url.
				$domain = wp_parse_url( $site_url, PHP_URL_HOST );
				// Update the site name to the domain.
				update_option( 'blogname', $domain );
			}
		}
		// Early exit if there was an error.
		if ( is_wp_error( $response ) ) {
			WP_CLI::log( 'Failed to get import selection data: ' . $response->get_error_message() );
			WP_CLI::halt( 0 );
			return;
		}
		if ( $this->is_response_code_error( $response ) ) {
			$response_code = (int) wp_remote_retrieve_response_code( $response );
			if ( 501 === $response_code ) {
				WP_CLI::log( 'Import selection data is not available for this license key.' );
				WP_CLI::halt( 0 );
				return;
			} else {
				$contents = wp_remote_retrieve_body( $response );
				if ( ! empty( $contents ) ) {
					$contents = json_decode( $contents, true );
				}
				$message  = isset( $contents['message'] ) ? $contents['message'] : 'Unknown Response Message';
				WP_CLI::log( 'Failed to get import selection data, message: ' . $message );
				WP_CLI::halt( 0 );
				return;
			}
		}
		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			WP_CLI::log( 'Failed to get import selection data: ' . $contents->get_error_message() );
			WP_CLI::halt( 0 );
			return;
		}
		$this->import_selection_data = json_decode( $contents, true );
		if ( empty( $this->import_selection_data['ai_data']['template'] ) ) {
			WP_CLI::log( 'No import selection data found.' );
			WP_CLI::halt( 0 );
			return;
		}
		$this->import_key = $this->import_selection_data['ai_data']['template'];
		$auth = null;
		if ( ! empty( $this->import_selection_data['ai_data']['ai_request_id'] ) ) {
			$auth = $this->import_selection_data['ai_data']['ai_request_id'];
			unset( $this->import_selection_data['ai_data']['ai_request_id'] );
		}
		update_option( 'kadence_blocks_prophecy', wp_json_encode( $this->import_selection_data['ai_data'] ) );
		if ( ! empty( $this->import_selection_data['ai_data']['timezone'] ) ) {
			update_option( 'timezone_string', $this->import_selection_data['ai_data']['timezone'] );
		}

		$base_site = Starter_Import_Processes::get_instance()->get_ai_base_site( $this->import_key );
		if ( is_wp_error( $base_site ) ) {
			WP_CLI::error( 'Failed to get ai base site: ' . $base_site->get_error_message() );
			return;
		}
		if ( ! isset( $base_site[$this->import_key] ) ) {
			WP_CLI::error( 'No import base site found.' );
			return;
		}
		$this->import_base_site = $base_site[$this->import_key];
		// Get the ai content.
		if ( ! empty( $this->import_selection_data['ai_jobs'] ) && is_array( $this->import_selection_data['ai_jobs'] ) ) {
			$ai_data = Starter_Import_Processes::get_instance()->get_all_local_ai_items( $this->import_selection_data['ai_jobs'], $auth );
			if ( empty( $ai_data ) || is_wp_error( $ai_data ) ) {
				WP_CLI::error( 'Failed to get ai content: ' . $ai_data->get_error_message() );
				return;
			}
			$this->ai_content = $ai_data;
			update_option( 'kb_design_library_prompts', $this->import_selection_data['ai_jobs'] );
		}
		// Get team image collection.
		$team_image_collection = Starter_Import_Processes::get_instance()->get_images_by_industry( ['Other'], '', 'JPEG', [], false );
		if ( is_wp_error( $team_image_collection ) ) {
			WP_CLI::log(  'Failed to get team image collection: ' . $team_image_collection->get_error_message() );
		}
		update_option( '_kadence_starter_templates_last_import_data', array( $this->import_key ), 'no' );
		$this->team_image_collection = ! empty( $team_image_collection ) && is_array( $team_image_collection ) ? $team_image_collection : [];
		Starter_Import_Processes::get_instance()->trigger_writing_cache();
	}

	/**
	 * Remove content
	 */
	private function remove_content() {
		$remove_content = Starter_Import_Processes::get_instance()->remove_content();
		if ( is_wp_error( $remove_content ) ) {
			WP_CLI::error( 'Failed to remove content: ' . $remove_content->get_error_message() );
			return;
		}
	}

	/**
	 * Install plugins
	 */
	private function install_plugins() {
		// Check if the plugins are already installed.
		$plugins = [
			'kadence-blocks',
		];
		if ( ! empty( $this->import_selection_data['ai_data']['goals'] ) && is_array( $this->import_selection_data['ai_data']['goals'] ) ) {
			$goals = $this->import_selection_data['ai_data']['goals'];
			if ( in_array( 'ecommerce', $goals ) ) {
				$plugins[] = 'woocommerce';
			}
			if ( in_array( 'events', $goals ) ) {
				$plugins[] = 'the-events-calendar';
				$plugins[] = 'event-tickets';
			}
			if ( in_array( 'tickets', $goals ) ) {
				$plugins[] = 'event-tickets';
			}
			if ( in_array( 'booking', $goals ) ) {
				$plugins[] = 'bookit';
			}
			if ( in_array( 'courses', $goals ) ) {
				$plugins[] = 'learndash';
				$plugins[] = 'learndash-course-grid';
			}
			if ( in_array( 'donations', $goals ) ) {
				$plugins[] = 'give';
			}
			if ( in_array( 'podcasting', $goals ) ) {
				$plugins[] = 'seriously-simple-podcasting';
			}
		}
		$installer = new Plugin_Installer();
		$result = $installer->install_plugins($plugins);
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( 'Failed to install plugins: ' . $result->get_error_message() );
			return;
		}
	}
	/**
	 * Install posts
	 */
	private function install_posts() {
		$posts = Starter_Import_Processes::get_instance()->get_remote_posts('base');
		if ( is_wp_error( $posts ) ) {
			WP_CLI::error( 'Failed to get posts: ' . $posts->get_error_message() );
			return;
		}
		$images = [];
		if ( ! empty( $this->import_selection_data['ai_data']['imageCollection'] ) && is_array( $this->import_selection_data['ai_data']['imageCollection'] ) ) {
			$images = $this->import_selection_data['ai_data']['imageCollection'];
		}
		$prepared_posts = Starter_Import_Processes::get_instance()->prepare_posts( $posts, $images );
		if ( is_wp_error( $prepared_posts ) ) {
			WP_CLI::error( 'Failed to prepare posts: ' . $prepared_posts->get_error_message() );
			return;
		}
		$prepared_posts = Starter_Import_Processes::get_instance()->install_posts_extras( $prepared_posts, $images );
		if ( is_wp_error( $prepared_posts ) ) {
			WP_CLI::error( 'Failed to install posts extras: ' . $prepared_posts->get_error_message() );
			return;
		}
		$install_posts = Starter_Import_Processes::get_instance()->install_posts( $prepared_posts );
		if ( is_wp_error( $install_posts ) ) {
			WP_CLI::error( 'Failed to install posts: ' . $install_posts->get_error_message() );
			return;
		}
	}
	/**
	 * Install products
	 */
	private function install_products() {
		$goals = [];
		if ( ! empty( $this->import_selection_data['ai_data']['goals'] ) && is_array( $this->import_selection_data['ai_data']['goals'] ) ) {
			$goals = $this->import_selection_data['ai_data']['goals'];
		}
		if ( ! in_array( 'ecommerce', $goals ) ) {
			WP_CLI::log( 'No ecommerce goals found, skipping products installation...' );
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			WP_CLI::log( 'WooCommerce is not installed, skipping products installation...' );
			return;
		}
		$products = Starter_Import_Processes::get_instance()->get_remote_products();
		if ( is_wp_error( $products ) ) {
			WP_CLI::error( 'Failed to get products: ' . $products->get_error_message() );
			return;
		}
		$images = [];
		if ( ! empty( $this->import_selection_data['ai_data']['imageCollection'] ) && is_array( $this->import_selection_data['ai_data']['imageCollection'] ) ) {
			$images = $this->import_selection_data['ai_data']['imageCollection'];	
		}
		$prepared_products = Starter_Import_Processes::get_instance()->prepare_products( $products, $images );
		if ( is_wp_error( $prepared_products ) ) {
			WP_CLI::error( 'Failed to prepare products: ' . $prepared_products->get_error_message() );
			return;
		}
		$install_products = Starter_Import_Processes::get_instance()->install_products( $prepared_products, $images );
		if ( is_wp_error( $install_products ) ) {
			WP_CLI::error( 'Failed to install products: ' . $install_products->get_error_message() );
			return;
		}
		$this->product_ids = $install_products;
	}
	/**
	 * Install events
	 */
	private function install_events() {
		$goals = [];
		if ( ! empty( $this->import_selection_data['ai_data']['goals'] ) && is_array( $this->import_selection_data['ai_data']['goals'] ) ) {
			$goals = $this->import_selection_data['ai_data']['goals'];
		}
		if ( ! in_array( 'events', $goals ) ) {
			WP_CLI::log( 'No events goals found, skipping events installation...' );
			return;
		}
		if ( ! class_exists( '\Tribe__Events__Main' ) ) {
			WP_CLI::log( 'The Events Calendar is not installed, skipping events installation...' );
			return;
		}
		$events = Starter_Import_Processes::get_instance()->get_remote_events();
		if ( is_wp_error( $events ) ) {
			WP_CLI::error( 'Failed to get events: ' . $events->get_error_message() );
			return;
		}
		$images = [];
		if ( ! empty( $this->import_selection_data['ai_data']['imageCollection'] ) && is_array( $this->import_selection_data['ai_data']['imageCollection'] ) ) {
			$images = $this->import_selection_data['ai_data']['imageCollection'];	
		}
		$prepared_events = Starter_Import_Processes::get_instance()->prepare_events( $events, $images );
		if ( is_wp_error( $prepared_events ) ) {
			WP_CLI::error( 'Failed to prepare events: ' . $prepared_events->get_error_message() );
			return;
		}
		$install_events = Starter_Import_Processes::get_instance()->install_events( $prepared_events, $images);
		if ( is_wp_error( $install_events ) ) {
			WP_CLI::error( 'Failed to install events: ' . $install_events->get_error_message() );
			return;
		}
	}
	/**
	 * Install course
	 */
	private function install_course() {
		$goals = [];
		if ( ! empty( $this->import_selection_data['ai_data']['goals'] ) && is_array( $this->import_selection_data['ai_data']['goals'] ) ) {
			$goals = $this->import_selection_data['ai_data']['goals'];
		}
		if ( ! in_array( 'courses', $goals ) ) {
			WP_CLI::log( 'No courses goals found, skipping course installation...' );
			return;
		}
		if ( ! class_exists( '\Learndash_Admin_Import_Export' ) ) {
			WP_CLI::log( 'LearnDash is not installed, skipping course installation...' );
			return;
		}
		$course = Starter_Import_Processes::get_instance()->install_course();
		if ( is_wp_error( $course ) ) {
			WP_CLI::log( 'Failed to install course: ' . $course->get_error_message() );
			return;
		}
	}
	/**
	 * Install give form
	 */
	private function install_give_form() {
		$goals = [];
		if ( ! empty( $this->import_selection_data['ai_data']['goals'] ) && is_array( $this->import_selection_data['ai_data']['goals'] ) ) {
			$goals = $this->import_selection_data['ai_data']['goals'];
		}
		if ( ! in_array( 'donations', $goals ) ) {
			WP_CLI::log( 'No non-profit goals found, skipping give form installation...' );
			return;
		}
		if ( ! class_exists( '\Give' ) ) {
			WP_CLI::log( 'Give is not installed, skipping give form installation...' );
			return;
		}
		$images = [];
		if ( ! empty( $this->import_selection_data['ai_data']['imageCollection'] ) && is_array( $this->import_selection_data['ai_data']['imageCollection'] ) ) {
			$images = $this->import_selection_data['ai_data']['imageCollection'];
		}
		$site_name = ( !empty( $this->import_selection_data['ai_data']['companyName'] ) ? $this->import_selection_data['ai_data']['companyName'] : 'GiveWP' );
		$primary_color = ( !empty( $this->import_selection_data['ai_data']['colorPalette']['colors'][0] ) ? $this->import_selection_data['ai_data']['colorPalette']['colors'][0] : '' );
		$give_form = Starter_Import_Processes::get_instance()->install_give_form( $this->ai_content, $images, $site_name, $primary_color );
		if ( is_wp_error( $give_form ) ) {
			WP_CLI::error( 'Failed to install give form: ' . $give_form->get_error_message() );
			return;
		}
		$this->give_form_id = $give_form;
	}
	/**
	 * Install pages
	 */
	private function install_pages() {
		$pages = [];
		$include_pages = ['home', 'contact', 'about'];
		if ( ! empty( $this->import_selection_data['ai_data']['includePages'] ) && is_array( $this->import_selection_data['ai_data']['includePages'] ) ) {
			$include_pages = $this->import_selection_data['ai_data']['includePages'];	
		}
		$template_pages = $this->import_base_site['pages'];
		if ( ! empty( $template_pages ) && is_array( $template_pages ) ) {
			foreach ( $template_pages as $page ) {
				if ( in_array( $page['slug'], $include_pages ) ) {
					$pages[] = $page;
				}
			}
		}
		$images = [];
		if ( ! empty( $this->import_selection_data['ai_data']['imageCollection'] ) && is_array( $this->import_selection_data['ai_data']['imageCollection'] ) ) {
			$images = $this->import_selection_data['ai_data']['imageCollection'];
		}
		$goals = [];
		if ( ! empty( $this->import_selection_data['ai_data']['goals'] ) && is_array( $this->import_selection_data['ai_data']['goals'] ) ) {
			$goals = $this->import_selection_data['ai_data']['goals'];
		}
		$color_palette = [];
		if ( ! empty( $this->import_selection_data['ai_data']['colorPalette'] ) && is_array( $this->import_selection_data['ai_data']['colorPalette'] ) ) {
			$color_palette = $this->import_selection_data['ai_data']['colorPalette'];
		}
		
		$prepared_pages = Starter_Import_Processes::get_instance()->prepare_pages( $pages, $images, $this->ai_content, $color_palette, $goals, $this->product_ids, $this->team_image_collection, $this->give_form_id );
		if ( is_wp_error( $prepared_pages ) ) {
			WP_CLI::error( 'Failed to prepare pages: ' . $prepared_pages->get_error_message() );
			return;
		}
		$prepared_pages = Starter_Import_Processes::get_instance()->install_pages_extras( $prepared_pages, $images );
		if ( is_wp_error( $prepared_pages ) ) {
			WP_CLI::error( 'Failed to install pages extras: ' . $prepared_pages->get_error_message() );
			return;
		}
		$install_pages = Starter_Import_Processes::get_instance()->install_pages( $prepared_pages, $images );
		if ( is_wp_error( $install_pages ) ) {
			WP_CLI::error( 'Failed to install pages: ' . $install_pages->get_error_message() );
			return;
		}
	}
	/**
	 * Install theme
	 */
	private function install_theme() {
		$site_name = ( !empty( $this->import_selection_data['ai_data']['companyName'] ) ? $this->import_selection_data['ai_data']['companyName'] : 'Site Name' );
		$color_palette = ( !empty( $this->import_selection_data['ai_data']['colorPalette'] ) ? $this->import_selection_data['ai_data']['colorPalette'] : [] );
		$dark_footer = ( !empty( $this->import_selection_data['ai_data']['darkFooter'] ) ? $this->import_selection_data['ai_data']['darkFooter'] : false );
		$fonts = ( !empty( $this->import_selection_data['ai_data']['fontPair'] ) ? $this->import_selection_data['ai_data']['fontPair'] : [] );
		$donation_form_id = ( !empty( $this->give_form_id ) ? $this->give_form_id : '' );
		$theme = Starter_Import_Processes::get_instance()->install_settings( $this->import_key, $site_name, $color_palette, $dark_footer, $fonts, $donation_form_id );
		if ( is_wp_error( $theme ) ) {
			WP_CLI::error( 'Failed to install theme: ' . $theme->get_error_message() );
			return;
		}
	}
	/**
	 * Install navigation
	 */
	private function install_navigation() {
		$goals = ( ! empty( $this->import_selection_data['ai_data']['goals'] ) && is_array( $this->import_selection_data['ai_data']['goals'] ) ? $this->import_selection_data['ai_data']['goals'] : [] );
		$navigation = Starter_Import_Processes::get_instance()->install_navigation( $this->import_key, $goals );
		if ( is_wp_error( $navigation ) ) {
			WP_CLI::error( 'Failed to install navigation: ' . $navigation->get_error_message() );
			return;
		}
	}
	/**
	 * Install widgets
	 */
	private function install_widgets() {
		$site_name = ( !empty( $this->import_selection_data['ai_data']['companyName'] ) ? $this->import_selection_data['ai_data']['companyName'] : 'Site Name' );
		$widgets = Starter_Import_Processes::get_instance()->install_widgets( $this->import_key, $site_name );
		if ( is_wp_error( $widgets ) ) {
			WP_CLI::error( 'Failed to install widgets: ' . $widgets->get_error_message() );
			return;
		}
	}
	
} 