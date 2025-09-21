<?php
/**
 * Class for pulling in template database and saving locally
 * Based on a package from the WPTT Team for local fonts.
 *
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use KadenceWP\KadenceStarterTemplates\Cache\Ai_Cache;
use KadenceWP\KadenceStarterTemplates\Cache\Block_Library_Cache;
use KadenceWP\KadenceStarterTemplates\Image_Downloader\Cache_Primer;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Storage\Exceptions\NotFoundException;
use KadenceWP\KadenceStarterTemplates\Traits\Rest\Image_Trait;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Server;
use WP_Filesystem;
use WP_Error;
use function wp_safe_remote_get;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_response_code;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_original_domain;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_license_key;
/**
 * REST API prebuilt library.
 */
class Site_Assist_REST_Controller extends WP_REST_Controller {

	use Image_Trait;

	/**
	 * Include ai prompt.
	 */
	const PROP_CONTEXT = 'context';
	/**
	 * Library slug.
	 */
	const PROP_LIBRARY = 'library';
	/**
	 * Library URL.
	 */
	const PROP_LIBRARY_URL = 'library_url';
	/**
	 * Force reload.
	 */
	const PROP_FORCE_RELOAD = 'force_reload';
	/**
	 * Handle Library Key.
	 */
	const PROP_KEY = 'key';

	/**
	 * Handle API Key.
	 */
	const PROP_API_KEY = 'api_key';

	/**
	 * Handle API Key.
	 */
	const PROP_API_EMAIL = 'api_email';

	/**
	 * Handle API product Key.
	 */
	const PROP_API_PRODUCT = 'product_id';

	/**
	 * Handle plugins array.
	 */
	const PROP_PLUGINS = 'plugins';
	/**
	 * Handle pages array.
	 */
	const PROP_PAGES = 'pages';
	/**
	 * Handle page slug.
	 */
	const PROP_PAGE = 'page';
	/**
	 * Handle image Type.
	 */
	const PROP_IMAGE_TYPE = 'image_type';
	/**
	 * Handle image sizes.
	 */
	const PROP_IMAGE_SIZES = 'image_sizes';
	/**
	 * Handle image Industry.
	 */
	const PROP_INDUSTRIES = 'industries';
	/**
	 * Handle image Industry.
	 */
	const PROP_INDUSTRY = 'industry';


	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * API key for kadence membership
	 *
	 * @var null
	 */
	private $api_key = '';

	/**
	 * API key for kadence membership
	 *
	 * @var null
	 */
	private $site_url = '';

	/**
	 * API email for kadence membership
	 *
	 * @var string
	 */
	private $api_email = '';
	/**
	 * Environment.
	 *
	 * @var string
	 */
	private $env = '';
	/**
	 * API email for kadence membership
	 *
	 * @var string
	 */
	private $template_type = 'blocks';
	/**
	 * Base URL.
	 *
	 * @access protected
	 * @var string
	 */
	protected $base_url;
	/**
	 * Base path.
	 *
	 * @access protected
	 * @var string
	 */
	protected $base_path;
	/**
	 * Force a reload.
	 *
	 * @access protected
	 * @var string
	 */
	protected $reload = false;
	/**
	 * Subfolder name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $subfolder_name;

	/**
	 * The starter templates folder.
	 *
	 * @access protected
	 * @var string
	 */
	protected $starter_templates_folder;
	/**
	 * The local stylesheet's path.
	 *
	 * @access protected
	 * @var string
	 */
	protected $local_template_data_path;

	/**
	 * The local stylesheet's URL.
	 *
	 * @access protected
	 * @var string
	 */
	protected $local_template_data_url;
	/**
	 * The remote URL.
	 *
	 * @access protected
	 * @var string
	 */
	protected $remote_url = 'https://api.startertemplatecloud.com/wp-json/kadence-starter/v1/get/';

	/**
	 * The remote URL.
	 *
	 * @access protected
	 * @var string
	 */
	protected $remote_help_url = 'https://help.startertemplatecloud.com/wp-json/kadence-cloud/v1/get';

	/**
	 * The remote URL.
	 *
	 * @access protected
	 * @var string
	 */
	protected $remote_ai_url = 'https://content.startertemplatecloud.com/wp-json/prophecy/v1/';

	/**
	 * The remote URL.
	 *
	 * @access protected
	 * @var string
	 */
	protected $remote_credits_url = 'https://content.startertemplatecloud.com/wp-json/kadence-credits/v1/';

	/**
	 * The final data.
	 *
	 * @access protected
	 * @var string
	 */
	protected $data;
	/**
	 * The api namespace.
	 *
	 * @access protected
	 * @var string
	 */
	protected $namespace;
	/**
	 * The library folder.
	 *
	 * @access protected
	 * @var string
	 */
	protected $block_ai_folder;
	/**
	 * @var Block_Library_Cache
	 */
	protected $block_library_cache;

	/**
	 * @var Ai_Cache
	 */
	protected $ai_cache;

	/**
	 * @var Cache_Primer
	 */
	protected $cache_primer;

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
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace           = 'kadence-starter-library/v1';
		$this->block_library_cache = kadence_starter_templates()->get( Block_Library_Cache::class );
		$this->ai_cache            = kadence_starter_templates()->get( Ai_Cache::class );
		$this->cache_primer        = kadence_starter_templates()->get( Cache_Primer::class );
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/starter-help-docs',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_help_docs' ],
					'permission_callback' => [ $this, 'get_items_permission_check' ],
					'args'                => $this->get_collection_params(),
				],
			]
		);
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_help_docs( $request ) {
		$this->get_license_keys();
		$reload = $request->get_param( self::PROP_FORCE_RELOAD );

		$identifier = 'help-docs' . KADENCE_STARTER_TEMPLATES_VERSION;

		// Check if we have a local file.
		if ( ! $reload ) {
			try {
				return rest_ensure_response( $this->block_library_cache->get( $identifier ) );
			} catch ( NotFoundException $e ) {
			}
		}

		$args    = [
			'key'      => 'help',
			'site_url' => $this->site_url,
			'beta'     => defined( 'KADENCE_STARTER_TEMPLATES_BETA' ) && KADENCE_STARTER_TEMPLATES_BETA ? 'true' : 'false',
		];
		$api_url = add_query_arg( $args, $this->remote_help_url );
		// Get the response.
		$response = wp_safe_remote_get(
			$api_url,
			[
				'timeout' => 20,
			]
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'getting_help_docs_failed', __( 'Failed to get remote help docs' ), [ 'status' => 500 ] );
		}
		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return new WP_Error( 'getting_help_docs_failed', __( 'Failed to get remote help docs' ), [ 'status' => 500 ] );
		}

		$this->block_library_cache->cache( $identifier, $contents );

		return rest_ensure_response( $contents );
	}
	/**
	 * Retrieves remaining credits.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_auth_data( $request ) {
		$this->get_license_keys();
		$auth = [
			'domain' => $this->site_url,
			'key'    => $this->api_key,
		];
		return rest_ensure_response( $auth );
	}
	
	
	
	/**
	 * Write the data to the filesystem.
	 *
	 * @access protected
	 * @return string|false Returns the absolute path of the file on success, or false on fail.
	 */
	protected function create_ai_data_file( $content, $prompt_data ) {
		$file_path  = $this->get_local_ai_data_path( $prompt_data );
		$filesystem = $this->get_filesystem();

		// If the folder doesn't exist, create it.
		if ( ! file_exists( $this->get_ai_library_folder() ) ) {
			$chmod_dir = ( 0755 & ~ umask() );
			if ( defined( 'FS_CHMOD_DIR' ) ) {
				$chmod_dir = FS_CHMOD_DIR;
			}
			$this->get_filesystem()->mkdir( $this->get_ai_library_folder(), $chmod_dir );
		}

		// If the file doesn't exist, create it. Return false if it can not be created.
		if ( ! $filesystem->exists( $file_path ) && ! $filesystem->touch( $file_path ) ) {
			return false;
		}

		// Put the contents in the file. Return false if that fails.
		if ( ! $filesystem->put_contents( $file_path, $content ) ) {
			return false;
		}

		return $file_path;
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
	 * Retrieves the path to the local data file.
	 *
	 * @param array $prompt_data The prompt data.
	 *
	 * @return string of the path to local data file.
	 */
	public function get_local_ai_data_path( $prompt_data ) {
		return $this->get_ai_library_folder() . '/' . $this->get_local_ai_data_filename( $prompt_data ) . '.json';
	}
	/**
	 * Get the local data filename.
	 *
	 * This is a hash, generated from the current site url, the wp-content path, the prompt data.
	 * This way we can avoid issues with sites changing their URL, or the wp-content path etc.
	 *
	 * @param array $prompt_data The prompt data.
	 *
	 * @return string
	 */
	public function get_local_ai_data_filename( $prompt_data ) {
		return $this->hash( [ 'kadence-ai-generated-content', $prompt_data ] );
	}
	/**
	 * Create a hash from different types of data.
	 *
	 * @param string|object|array|int|float $data   The data to hash.
	 * @param bool                          $binary Output in raw binary.
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException|RuntimeException
	 */
	public function hash( $data, bool $binary = false ): string {
		if ( $data === null ) {
			throw new InvalidArgumentException( '$data cannot be null.' );
		}

		$data = is_scalar( $data ) ? (string) $data : (string) json_encode( $data );

		if ( strlen( $data ) <= 0 ) {
			throw new RuntimeException( 'Cannot hash an empty data string. Perhaps JSON encoding failed?' );
		}

		return hash( 'md5', $data, $binary );
	}
	/**
	 * Get local data contents.
	 *
	 * @access public
	 * @return string|false Returns the data contents.
	 */
	public function get_local_data_contents( $file_path ) {
		// Check if the file path is set.
		if ( empty( $file_path ) ) {
			return false;
		}
		ob_start();
		include $file_path;
		return ob_get_clean();
	}
	/**
	 * Get the folder for templates data.
	 *
	 * @access public
	 * @return string
	 */
	public function get_ai_library_folder() {
		if ( ! $this->block_ai_folder ) {
			$this->block_ai_folder  = $this->get_base_path();
			$this->block_ai_folder .= $this->get_ai_subfolder_name();
		}
		return $this->block_ai_folder;
	}
	
	/**
	 * Constructs a consistent Token header.
	 *
	 * @param array $args An array of arguments to include in the encoded header.
	 *
	 * @return string The base64 encoded string.
	 */
	public function get_token_header( $args = [] ) {
		$this->get_license_keys();
		$site_name = get_bloginfo( 'name' );
		$defaults  = [
			'domain'          => $this->site_url,
			'key'             => ! empty( $this->api_key ) ? $this->api_key : '',
			'email'           => ! empty( $this->api_email ) ? $this->api_email : '',
			'site_name'       => sanitize_title( $site_name ),
			'product_slug'    => apply_filters( 'kadence-blocks-auth-slug', 'kadence-starter-templates' ),
			'product_version' => KADENCE_STARTER_TEMPLATES_VERSION,
		];
		if ( ! empty( $this->env ) ) {
			$defaults['env'] = $this->env;
		}
		$parsed_args = wp_parse_args( $args, $defaults );

		return base64_encode( json_encode( $parsed_args ) );
	}


	/**
	 * Checks if a given request has access to search content.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has search access, WP_Error object otherwise.
	 */
	public function get_items_permission_check( $request ) {
		return current_user_can( apply_filters( 'kadence_starter_templates_admin_settings_capability', 'manage_options' ) );
	}
	/**
	 * Retrieves the query params for the search results collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params[ self::PROP_LIBRARY ] = [
			'description'       => __( 'The requested library.', 'kadence-starter-templates' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		];

		$query_params[ self::PROP_FORCE_RELOAD ] = [
			'description' => __( 'Force a refresh of the context.', 'kadence-starter-templates' ),
			'type'        => 'boolean',
			'default'     => false,
		];
		$query_params[ self::PROP_KEY ]          = [
			'description'       => __( 'Library Key.', 'kadence-starter-templates' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		];
		return $query_params;
	}
	/**
	 * Get the local data file if there, else query the api.
	 *
	 * @access public
	 * @return string
	 */
	public function get_template_data( $skip_local = false ) {
		if ( 'custom' === $this->template_type ) {
			return wp_json_encode( apply_filters( 'kadence_starter_templates_custom_array', [] ) );
		}
		// Check if the local data file exists.
		if ( $skip_local || ! $this->has_local_file() ) {
			// Attempt to create the file.
			if ( $this->create_template_data_file() ) {
				return $this->get_local_template_data_contents();
			}
		} elseif ( '[]' === $this->get_local_template_data_contents() ) {
			// Check if the local file is empty for some reason.
			if ( $this->create_template_data_file() ) {
				return $this->get_local_template_data_contents();
			}
		}
		// If the local file exists, return it's data.
		return file_exists( $this->get_local_template_data_path() )
			? $this->get_local_template_data_contents()
			: '';
	}
	/**
	 * Write the data to the filesystem.
	 *
	 * @access protected
	 * @return string|false Returns the absolute path of the file on success, or false on fail.
	 */
	protected function create_template_data_file() {
		$file_path  = $this->get_local_template_data_path();
		$filesystem = $this->get_filesystem();

		// If the folder doesn't exist, create it.
		if ( ! file_exists( $this->get_starter_templates_folder() ) ) {
			$chmod_dir = ( 0755 & ~ umask() );
			if ( defined( 'FS_CHMOD_DIR' ) ) {
				$chmod_dir = FS_CHMOD_DIR;
			}
			$this->get_filesystem()->mkdir( $this->get_starter_templates_folder(), $chmod_dir );
		}

		// If the file doesn't exist, create it. Return false if it can not be created.
		if ( ! $filesystem->exists( $file_path ) && ! $filesystem->touch( $file_path ) ) {
			return false;
		}

		// If we got this far, we need to write the file.
		// Get the data.
		$this->get_data();
		if ( ! $this->data ) {
			// No Data.
			return false;
		}
		// Put the contents in the file. Return false if that fails.
		if ( ! $filesystem->put_contents( $file_path, $this->data ) ) {
			return false;
		}

		return $file_path;
	}
	/**
	 * Get data.
	 *
	 * @access public
	 * @return string
	 */
	public function get_data() {
		// Get the remote URL contents.
		$this->data = $this->get_remote_url_contents();

		return $this->data;
	}
	/**
	 * Get local data contents.
	 *
	 * @access public
	 * @return string|false Returns the data contents.
	 */
	public function get_local_template_data_contents() {
		$local_path = $this->get_local_template_data_path();

		// Check if the local file is present.
		if ( ! $this->has_local_file() ) {
			return false;
		}

		ob_start();
		include $local_path;
		return ob_get_clean();
	}
	/**
	 * Get remote file contents.
	 *
	 * @access public
	 * @return string Returns the remote URL contents.
	 */
	public function get_remote_url_contents() {
		$args = apply_filters(
			'kadence_starter_get_templates_args',
			[
				'request'   => ( $this->template_type ? $this->template_type : 'blocks' ),
				'api_email' => $this->api_email,
				'api_key'   => $this->api_key,
				'site_url'  => $this->site_url,
			]
		);
		if ( ! empty( $this->env ) ) {
			$args['env'] = $this->env;
		}
		// Get the response.
		$api_url  = add_query_arg( $args, $this->remote_url );
		$response = wp_safe_remote_get(
			$api_url,
			[
				'timeout' => 20,
			]
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return '';
		}

		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return;
		}

		return $contents;
	}
	/**
	 * Check if the local file exists.
	 *
	 * @access public
	 * @return bool
	 */
	public function has_local_file() {
		return file_exists( $this->get_local_template_data_path() );
	}
	/**
	 * Get the data path.
	 *
	 * @access public
	 * @return string
	 */
	public function get_local_template_data_path() {
		if ( ! $this->local_template_data_path ) {
			$this->local_template_data_path = $this->get_starter_templates_folder() . '/' . $this->get_local_template_data_filename() . '.json';
		}
		return $this->local_template_data_path;
	}
	/**
	 * Get the local data filename.
	 *
	 * This is a hash, generated from the site-URL, the wp-content path and the URL.
	 * This way we can avoid issues with sites changing their URL, or the wp-content path etc.
	 *
	 * @access public
	 * @return string
	 */
	public function get_local_template_data_filename() {
		$ktp_api = $this->get_current_license_key();
		if ( empty( $ktp_api ) ) {
			$ktp_api = 'free';
		}
		return md5( $this->get_base_url() . $this->get_base_path() . $this->template_type . KADENCE_STARTER_TEMPLATES_VERSION . $ktp_api );
	}
	/**
	 * Get the folder for templates data.
	 *
	 * @access public
	 * @return string
	 */
	public function get_starter_templates_folder() {
		if ( ! $this->starter_templates_folder ) {
			$this->starter_templates_folder = $this->get_base_path();
			if ( $this->get_subfolder_name() ) {
				$this->starter_templates_folder .= $this->get_subfolder_name();
			}
		}
		return $this->starter_templates_folder;
	}
	/**
	 * Get the subfolder name.
	 *
	 * @access public
	 * @return string
	 */
	public function get_ai_subfolder_name() {
		return apply_filters( 'kadence_block_ai_local_data_subfolder_name', 'kadence_ai' );
	}
	/**
	 * Get the subfolder name.
	 *
	 * @access public
	 * @return string
	 */
	public function get_subfolder_name() {
		if ( ! $this->subfolder_name ) {
			$this->subfolder_name = apply_filters( 'kadence_starter_templates_local_data_subfolder_name', 'kadence_starter_templates' );
		}
		return $this->subfolder_name;
	}
	/**
	 * Get the base path.
	 *
	 * @access public
	 * @return string
	 */
	public function get_base_path() {
		if ( ! $this->base_path ) {
			$upload_dir      = wp_upload_dir();
			$this->base_path = apply_filters( 'kadence_block_library_local_data_base_path', trailingslashit( $upload_dir['basedir'] ) );
		}
		return $this->base_path;
	}
	/**
	 * Get the base URL.
	 *
	 * @access public
	 * @return string
	 */
	public function get_base_url() {
		if ( ! $this->base_url ) {
			$this->base_url = apply_filters( 'kadence_block_library_local_data_base_url', content_url() );
		}
		return $this->base_url;
	}
	/**
	 * Get the filesystem.
	 *
	 * @access protected
	 * @return WP_Filesystem
	 */
	protected function get_filesystem() {
		global $wp_filesystem;

		// If the filesystem has not been instantiated yet, do it here.
		if ( ! $wp_filesystem ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once wp_normalize_path( ABSPATH . '/wp-admin/includes/file.php' );
			}
			$credentials = apply_filters( 'kadence_wpfs_credentials', false );
			WP_Filesystem( $credentials );
		}
		return $wp_filesystem;
	}
	/**
	 * Sanitizes the list of subtypes, to ensure only subtypes of the passed type are included.
	 *
	 * @param string|array    $subtypes  One or more subtypes.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @param string          $parameter Parameter name.
	 * @return array|WP_Error List of valid subtypes, or WP_Error object on failure.
	 */
	public function sanitize_pages( $pages, $request ) {
		$keys = array_keys( $pages );
		$keys = array_map( 'sanitize_key', $keys );

		$values = array_values( $pages );
		$values = array_map( 'sanitize_text_field', $values );

		$pages = array_combine( $keys, $values );

		return $pages;
	}
	/**
	 * Sanitizes the list of subtypes, to ensure only subtypes of the passed type are included.
	 *
	 * @param string|array    $subtypes  One or more subtypes.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @param string          $parameter Parameter name.
	 * @return array|WP_Error List of valid subtypes, or WP_Error object on failure.
	 */
	public function sanitize_plugins( $plugins, $request ) {
		$allowed_plugins = array_keys( $this->get_allowed_plugins() );

		return array_unique( array_intersect( $plugins, $allowed_plugins ) );
	}

	/**
	 * Validates the list of subtypes, to ensure it's an array.
	 *
	 * @param array $value  One or more subtypes.
	 * @return bool    true or false.
	 */
	public function validate_array( $value ) {
		return is_array( $value );
	}
	/**
	 * Validates the list of subtypes, to ensure it's an array.
	 *
	 * @param array $value  One or more subtypes.
	 * @return bool    true or false.
	 */
	public function get_license_keys() {
		$data = $this->get_pro_license_data();
		if ( ! empty( $data['api_key'] ) ) {
			$this->api_key = $data['api_key'];
		}
		if ( ! empty( $data['api_email'] ) ) {
			$this->api_email = $data['api_email'];
		}
		if ( ! empty( $data['site_url'] ) ) {
			$this->site_url = $data['site_url'];
		}
		if ( ! empty( $data['env'] ) ) {
			$this->env = $data['env'];
		}
		return $data;
	}
	/**
	 * Get the current environment.
	 */
	public function get_current_env() {
		if ( defined( 'STELLARWP_UPLINK_API_BASE_URL' ) ) {
			switch ( STELLARWP_UPLINK_API_BASE_URL ) {
				case 'https://licensing-dev.stellarwp.com':
					return 'dev';
				case 'https://licensing-staging.stellarwp.com':
					return 'staging';
					
			}
		}
		return '';
	}
	/**
	 * Get the current license key for the plugin.
	 */
	public function get_current_license_key() {
		if ( function_exists( 'kadence_blocks_get_current_license_data' ) ) {
			$data = kadence_blocks_get_current_license_data();
			if ( ! empty( $data['key'] ) ) {
				return $data['key'];
			}
		}
		return get_license_key( 'kadence-starter-templates' );
	}
	/**
	 * Get the current license key for the plugin.
	 */
	public function get_pro_license_data() {
		return [
			'api_key'   => $this->get_current_license_key(),
			'api_email' => '',
			'site_url'  => get_original_domain(),
			'env'       => $this->get_current_env(),
		];
	}
}
