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

use KadenceWP\KadenceStarterTemplates\Plugin_Check;
use KadenceWP\KadenceStarterTemplates\Cache\Ai_Cache;
use KadenceWP\KadenceStarterTemplates\Cache\Block_Library_Cache;
use KadenceWP\KadenceStarterTemplates\Image_Downloader\Image_Downloader;
use KadenceWP\KadenceStarterTemplates\Image_Downloader\Cache_Primer;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\ImageDownloader\Exceptions\ImageDownloadException;
use KadenceWP\KadenceStarterTemplates\StellarWP\ProphecyMonorepo\Storage\Exceptions\NotFoundException;
use KadenceWP\KadenceStarterTemplates\Traits\Rest\Image_Trait;
use KadenceWP\KadenceStarterTemplates\ContentReplace\Image_Replacer;
use KadenceWP\KadenceStarterTemplates\ContentReplace\Content_Remover;
use KadenceWP\KadenceStarterTemplates\ContentReplace\Woo_Content_Handler;
use KadenceWP\KadenceStarterTemplates\ContentReplace\Content_Replacer;
use KadenceWP\KadenceStarterTemplates\ContentReplace\Color_Handler;
use KadenceWP\KadenceStarterTemplates\ContentReplace\Donation_Form_Handler;
use Give\DonationForms\Models\DonationForm;
use Give\FormBuilder\Actions\GenerateDefaultDonationFormBlockCollection;
use Give\DonationForms\ValueObjects\DonationFormStatus;
use Give\DonationForms\Properties\FormSettings;
use Give\Campaigns\Models\Campaign;
use Give\Campaigns\ValueObjects\CampaignGoalType;
use Give\Campaigns\ValueObjects\CampaignStatus;
use Give\Campaigns\ValueObjects\CampaignType;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Server;
use WP_Filesystem;
use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Product_Attribute;
use WP_Error;
use WC_Install;
use WP_Query;
use LearnDash_Settings_Section;
use function sanitize_file_name;
use function wp_safe_remote_get;
use function flush_rewrite_rules;
use function wp_cache_flush;
use function wp_send_json;
use function wp_remote_get;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_response_code;
use function wp_get_attachment_url;
use function wc_create_page;
use function wc_get_product_object;
use function wc_switch_to_site_locale;
use function wc_get_page_id;
use function post_type_archive_title;
use function get_post_type_archive_url;
use function tribe_create_event;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_license_domain;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_original_domain;
use function KadenceWP\KadenceStarterTemplates\StellarWP\Uplink\get_license_key;

/**
 * Starter Import Processes.
 */
class Starter_Import_Processes {

	use Image_Trait;


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
	protected $remote_pages_url = 'https://patterns.startertemplatecloud.com/wp-json/kadence-cloud/v1/pages/';

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
	 * The rest_base.
	 *
	 * @access protected
	 * @var string
	 */
	protected $rest_base;
	/**
	 * The library folder.
	 *
	 * @access protected
	 * @var string
	 */
	protected $block_ai_folder;
	/**
	 * The library folder.
	 *
	 * @access protected
	 * @var string
	 */
	protected $initial_contexts = array(
		'about',
		'achievements',
		// 'blog',
		'call-to-action',
		// 'careers',
		'contact-form',
		'donate',
		'events',
		'faq',
		'get-started',
		// 'history',
		'industries',
		'location',
		'mission',
		// 'news',
		// 'partners',
		// 'podcast',
		'pricing-table',
		'product-details',
		'products-services',
		// 'profile',
		'subscribe-form',
		// 'support',
		'team',
		'testimonials',
		'value-prop',
		// 'volunteer',
		'welcome',
		'work',
	);
	/**
	 * The library folder.
	 *
	 * @access protected
	 * @var string
	 */
	protected $all_contexts = array(
		'about',
		'achievements',
		'blog',
		'call-to-action',
		'careers',
		'contact-form',
		'donate',
		'events',
		'faq',
		'get-started',
		'history',
		'industries',
		'location',
		'mission',
		'news',
		'partners',
		// 'podcast',
		'pricing-table',
		//'product-details',
		'products-services',
		'profile',
		'subscribe-form',
		'support',
		'team',
		'testimonials',
		'value-prop',
		'volunteer',
		'welcome',
		'work',
	);
	/**
	 * Blocks that are based on CPT
	 *
	 * @var array
	 */
	private $kadence_cpt_blocks = array(
		'kadence/header',
		'kadence/navigation',
		'kadence/query',
		'kadence/query-card',
		'kadence/advanced-form',
	);
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
		$this->block_library_cache = kadence_starter_templates()->get( Block_Library_Cache::class );
		$this->ai_cache            = kadence_starter_templates()->get( Ai_Cache::class );
		$this->cache_primer        = kadence_starter_templates()->get( Cache_Primer::class );
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param bool $reload Whether to reload the data.
	 * @return string The contents.
	 */
	public function get_ai_base_site( $site_id, $reload = false ) {
		$this->get_license_keys();

		$identifier = 'ai-base-site-' . $site_id . KADENCE_STARTER_TEMPLATES_VERSION;

		if ( ! empty( $this->api_key ) ) {
			$identifier .= '_' . $this->api_key;
		}

		// Check if we have a local file.
		if ( ! $reload ) {
			try {
				return json_decode( $this->block_library_cache->get( $identifier ), true );
			} catch ( NotFoundException $e ) {
			}
		}

		$args = array(
			'key'       => $this->api_key,
			'site_url'  => $this->site_url,
		);
		if ( ! empty( $this->env ) ) {
			$args['env'] = $this->env;
		}
		$api_url  = add_query_arg( $args, 'https://base.startertemplatecloud.com/' . $site_id . '/wp-json/kadence-starter-base/v1/single-site' );
		// Get the response.
		$response = wp_safe_remote_get(
			$api_url,
			array(
				'timeout' => 30,
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'getting_ai_sites_failed', __( 'Failed to get AI Template' ), array( 'status' => 500 ) );
		}
		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return new WP_Error( 'getting_ai_sites_failed', __( 'Failed to get AI Template' ), array( 'status' => 500 ) );
		}

		$this->block_library_cache->cache( $identifier, $contents );

		return json_decode( $contents, true );
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param bool $reload Whether to reload the data.
	 * @return string The contents.
	 */
	public function get_ai_base_sites( $reload = false ) {
		$this->get_license_keys();

		$identifier = 'ai-base-templates' . KADENCE_STARTER_TEMPLATES_VERSION;

		if ( ! empty( $this->api_key ) ) {
			$identifier .= '_' . $this->api_key;
		}

		// Check if we have a local file.
		if ( ! $reload ) {
			try {
				return json_decode( $this->block_library_cache->get( $identifier ), true );
			} catch ( NotFoundException $e ) {
			}
		}

		$args = array(
			'key'       => $this->api_key,
			'site_url'  => $this->site_url,
			'beta'      => defined( 'KADENCE_STARTER_TEMPLATES_BETA' ) && KADENCE_STARTER_TEMPLATES_BETA ? 'true' : 'false',
		);
		if ( ! empty( $this->env ) ) {
			$args['env'] = $this->env;
		}
		$api_url  = add_query_arg( $args, 'https://base.startertemplatecloud.com/wp-json/kadence-starter-base/v1/sites' );
		// Get the response.
		$response = wp_safe_remote_get(
			$api_url,
			array(
				'timeout' => 20,
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'getting_ai_sites_failed', __( 'Failed to get AI Templates' ), array( 'status' => 500 ) );
		}
		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return new WP_Error( 'getting_ai_sites_failed', __( 'Failed to get AI Templates' ), array( 'status' => 500 ) );
		}

		$this->block_library_cache->cache( $identifier, $contents );

		return json_decode( $contents, true );
	}
	/**
	 * Retrieves all the currently available ai content.
	 *
	 * @param array $available_prompts The available prompts.
	 * @return array The ai content.
	 */
	public function get_all_local_ai_items( $available_prompts, $auth = null ) {
		$this->get_license_keys();
		$return_data = [];
		$error_messages = [];
		if ( ! empty( $available_prompts ) && is_array( $available_prompts ) ) {
			foreach ( $available_prompts as $context => $prompt ) {
				// Check local cache.
				try {
					$return_data[ $context ] = json_decode( $this->ai_cache->get( $available_prompts[ $context ] ), true );
				} catch ( NotFoundException $e ) {
					// Check if we have a remote file.
					$response = $this->get_remote_job( $available_prompts[ $context ], $auth );
					if ( is_wp_error( $response ) ) {
						$has_error = true;
						$error_messages[] = $response->get_error_message();
					} else if ( !empty( $response ) && is_string( $response ) && 'error' === $response ) {
						$error_messages[] = 'Unknown Error';
						$has_error = true;
					} else if ( !empty( $response ) && is_string( $response ) && 'not-found' === $response ) {
						$error_messages[] = 'Not Found';
						$has_error = true;
						// Clean up, the token and job are no longer valid.
						$current_prompts = get_option( 'kb_design_library_prompts', [] );
						if ( isset( $current_prompts[ $context ] ) ) {
							unset( $current_prompts[ $context ] );
							update_option( 'kb_design_library_prompts', $current_prompts );
						}
					} else { 
						$data     = json_decode( $response, true );
						if ( $response === 'processing' || isset( $data['data']['status'] ) && 409 === $data['data']['status'] ) {
							$error_messages[] = 'Processing';
							$has_error = true;
							$ready = false;
						} else if ( isset( $data['data']['status'] ) ) {
							$error_messages[] = 'Unknown Error';
							$has_error = true;
						} else {
							$this->ai_cache->cache( $available_prompts[ $context ], $response );
							$return_data[ $context ] = $data;
						}
					}
				}
			}
		}
		// Return data if we have some.
		if ( ! empty( $return_data ) ) {
			return $return_data;
		}
		// Return error if we have some.
		if ( ! empty( $error_messages ) ) {
			return new WP_Error( 'getting_ai_items_failed', __( 'Failed to get AI Items' ), array( 'status' => 500 ) );
		}
		return [];
	}
	
	/**
	 * Get remote file contents.
	 *
	 * @access public
	 * @return string Returns the remote URL contents.
	 */
	public function get_remote_job( $job, $auth = null ) {
		if ( empty( $auth ) ) {
			$auth = base64_encode( json_encode( [
				'domain' => $this->site_url,
				'key'    => $this->api_key,
			] ) );
		}
		$api_url  = $this->remote_ai_url . 'content/job/' . $job;
		$response = wp_safe_remote_get(
			$api_url,
			array(
				'timeout' => 20,
				'headers' => array(
					'X-Prophecy-Token' => $auth,
				),
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) ) {
			return 'error';
		}
		$response_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 409 === $response_code ) {
			return 'processing';
		}
		if ( 404 === $response_code ) {
			return 'not-found';
		}
		if ( $this->is_response_code_error( $response ) ) {
			return 'error';
		}

		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );
		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return 'error';
		}

		return $contents;
	}
	/**
	 * Get remote file contents.
	 *
	 * @access public
	 * @return string Returns the remote URL contents.
	 */
	public function get_new_remote_contents( $context ) {
		$auth = array(
			'domain' => $this->site_url,
			'key'    => $this->api_key,
		);
		$prophecy_data = json_decode( get_option( 'kadence_blocks_prophecy' ), true );
		// Get the response.
		$body = array(
			'context' => 'kadence',
		);
		$body['company'] = ! empty( $prophecy_data['companyName'] )	? $prophecy_data['companyName'] : '';
		if ( ! empty( $prophecy_data['industrySpecific'] ) && 'Other' !== $prophecy_data['industrySpecific'] ) {
			$body['industry'] = ! empty( $prophecy_data['industrySpecific'] ) ? $prophecy_data['industrySpecific'] : '';
		} elseif ( ! empty( $prophecy_data['industrySpecific'] ) && 'Other' === $prophecy_data['industrySpecific'] && ! empty( $prophecy_data['industryOther'] ) ) {
			$body['industry'] = ! empty( $prophecy_data['industryOther'] ) ? $prophecy_data['industryOther'] : '';
		} elseif ( ! empty( $prophecy_data['industry'] ) && 'Other' === $prophecy_data['industry'] && ! empty( $prophecy_data['industryOther'] ) ) {
			$body['industry'] = ! empty( $prophecy_data['industryOther'] ) ? $prophecy_data['industryOther'] : '';
		} else {
			$body['industry'] = ! empty( $prophecy_data['industry'] ) ? $prophecy_data['industry'] : '';
		}
		$body['location'] = ! empty( $prophecy_data['location'] ) ? $prophecy_data['location'] : '';
		$body['mission'] = ! empty( $prophecy_data['missionStatement'] ) ? $prophecy_data['missionStatement'] : '';
		$body['tone'] = ! empty( $prophecy_data['tone'] ) ? $prophecy_data['tone'] : '';
		$body['keywords'] = ! empty( $prophecy_data['keywords'] ) ? $prophecy_data['keywords'] : '';
		$body['lang'] = ! empty( $prophecy_data['lang'] ) ? $prophecy_data['lang'] : '';

		switch ( $context ) {
			case 'about':
				$body['prompts'] = array(
					'about',
					'about-hero',
					'about-columns',
					'about-list',
					'about-videos',
				);
				break;
			case 'achievements':
				$body['prompts'] = array(
					'achievements',
					'achievements-columns',
					'achievements-list',
					'achievements-videos',
				);
				break;
			case 'blog':
				$body['prompts'] = array(
					'blog-post-loop',
					'blog-table-contents',
				);
				break;
			case 'call-to-action':
				$body['prompts'] = array(
					'call-to-action',
					'call-to-action-columns',
					'call-to-action-list',
					'call-to-action-videos',
				);
				break;
			case 'careers':
				$body['prompts'] = array(
					'careers',
					'careers-hero',
					'careers-columns',
					'careers-list',
					'careers-videos',
				);
				break;
			case 'contact-form':
				$body['prompts'] = array(
					'contact-form',
				);
				break;
			case 'donate':
				$body['prompts'] = array(
					'donate',
					'donate-hero',
					'donate-columns',
					'donate-list',
					'donate-videos',
				);
				break;
			case 'events':
				$body['prompts'] = array(
					'events',
					'events-hero',
					'events-columns',
					'events-list',
					'events-videos',
				);
				break;
			case 'faq':
				$body['prompts'] = array(
					'faq-accordion',
				);
				break;
			case 'get-started':
				$body['prompts'] = array(
					'get-started',
					'get-started-accordion',
					'get-started-columns',
					'get-started-list',
				);
				break;
			case 'history':
				$body['prompts'] = array(
					'history',
					'history-columns',
					'history-list',
					'history-videos',
				);
				break;
			case 'industries':
				$body['prompts'] = array(
					'industries',
					'industries-accordion',
					'industries-list',
					'industries-columns',
					'industries-tabs',
				);
				break;
			case 'location':
				$body['prompts'] = array(
					'location',
					'location-columns',
					'location-tabs',
				);
				break;
			case 'mission':
				$body['prompts'] = array(
					'mission',
					'mission-columns',
					'mission-list',
					'mission-videos',
				);
				break;
			case 'news':
				$body['prompts'] = array(
					'news-post-loop',
				);
				break;
			case 'partners':
				$body['prompts'] = array(
					'partners',
					'partners-columns',
					'partners-list',
				);
				break;
			case 'podcast':
				$body['prompts'] = array(
					'podcast',
				);
				break;
			case 'pricing-table':
				$body['prompts'] = array(
					'pricing-pricing-table',
				);
				break;
			case 'product-details':
				$body['prompts'] = array(
					'product-details-accordion',
				);
				break;
			case 'products-services':
				$body['prompts'] = array(
					'products-services',
					'products-services-columns',
					'products-services-hero',
					'products-services-list',
					'products-services-single',
					'products-services-tabs',
					'products-services-videos',
					'product-details-accordion',
				);
				break;
			case 'profile':
				$body['prompts'] = array(
					'profile',
					'profile-columns',
					'profile-list',
					'profile-videos',
				);
				break;
			case 'subscribe-form':
				$body['prompts'] = array(
					'subscribe-form',
				);
				break;
			case 'support':
				$body['prompts'] = array(
					'support',
					'support-columns',
					'support-list',
					'support-videos',
				);
				break;
			case 'team':
				$body['prompts'] = array(
					'team',
					'team-columns',
					'team-list',
					'team-people',
					'team-videos',
				);
				break;
			case 'testimonials':
				$body['prompts'] = array(
					'testimonials-testimonials',
				);
				break;
			case 'value-prop':
				$body['prompts'] = array(
					'value-prop',
					'value-prop-columns',
					'value-prop-hero',
					'value-prop-list',
					'value-prop-tabs',
					'value-prop-videos',
				);
				break;
			case 'volunteer':
				$body['prompts'] = array(
					'volunteer',
					'volunteer-hero',
					'volunteer-list',
					'volunteer-columns',
					'volunteer-videos',
				);
				break;
			case 'welcome':
				$body['prompts'] = array(
					'welcome',
					'welcome-hero',
					'welcome-list',
					'welcome-columns',
					'welcome-videos',
				);
				break;
			case 'work':
				$body['prompts'] = array(
					'work',
					'work-columns',
					'work-counter-stats',
					'work-list',
					'work-videos',
				);
				break;
		}
		$response = wp_remote_post(
			$this->remote_ai_url . 'content/create',
			array(
				'timeout' => 20,
				'headers' => array(
					'X-Prophecy-Token' => base64_encode( json_encode( $auth ) ),
					'Content-Type' => 'application/json',
				),
				'body' => json_encode( $body ),
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			$contents = wp_remote_retrieve_body( $response );
			if ( ! empty( $contents ) && is_string( $contents ) && json_decode( $contents, true ) ) {
				$error_message = json_decode( $contents, true );
				if ( ! empty( $error_message['detail'] ) && 'Failed, unable to use credits.' === $error_message['detail'] ) {
					return 'credits';
				}
			}
			return 'error';
		}

		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );
		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return 'error';
		}

		return $contents;
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
		return $this->hash( array( 'kadence-ai-generated-content', $prompt_data ) );
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
			$this->block_ai_folder = $this->get_base_path();
			$this->block_ai_folder .= $this->get_ai_subfolder_name();
		}
		return $this->block_ai_folder;
	}
	/**
	 * Remove Past Content.
	 *
	 * @return boolean/WP_Error
	 */
	public function remove_content() {
		global $wpdb;
		// Prevents elementor from pushing out an confrimation and breaking the import.
		$_GET['force_delete_kit'] = true;
		$removed_content = true;

		$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_kadence_starter_templates_imported_post'" );
		$term_ids = $wpdb->get_col( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key='_kadence_starter_templates_imported_term'" );
		if ( isset( $post_ids ) && is_array( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				$worked = wp_delete_post( $post_id, true );
				if ( false === $worked ) {
					$removed_content = false;
				}
			}
		}
		if ( isset( $term_ids ) && is_array( $term_ids ) ) {
			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) ) {
					wp_delete_term( $term_id, $term->taxonomy );
				}
			}
		}
		if ( false === $removed_content ) {
			return new WP_Error( 'remove_failed', __( 'Remove past content failed.' ), array( 'status' => 500 ) );
		}
		/**
		 * Clean up default contents.
		 */
		$hello_world = $this->get_post_by_title( 'Hello World', OBJECT, 'post' );
		if ( $hello_world ) {
			wp_delete_post( $hello_world->ID, true );// Hello World.
		}
		$sample_page = $this->get_post_by_title( 'Sample Page' );
		if ( $sample_page ) {
			wp_delete_post( $sample_page->ID, true ); // Sample Page.
		}
		wp_delete_comment( 1, true ); // WordPress comment.

		return true;
	}
	/**
	 * Get Post by title.
	 * 
	 * @param string $page_title The title of the post.
	 * @param string $output The output type.
	 * @param string $post_type The post type.
	 * @return object|null The post object or null if not found.
	 */
	public function get_post_by_title( $page_title, $output = OBJECT, $post_type = 'page' ) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'title'                  => $page_title,
				'post_status'            => 'all',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'orderby'                => 'date',
				'order'                  => 'ASC',
			)
		);

		if ( ! empty( $query->post ) ) {
			$_post = $query->post;

			if ( ARRAY_A === $output ) {
				return $_post->to_array();
			} elseif ( ARRAY_N === $output ) {
				return array_values( $_post->to_array() );
			}

			return $_post;
		}

		return null;
	}
	/**
	 * Get remote download link.
	 *
	 * @access public
	 * @return string
	 */
	public function get_bundle_download_link( $base ) {
		$data = $this->get_license_keys();
		if ( empty( $data['api_key'] ) ) {
			return '';
		}
		return 'https://licensing.kadencewp.com/api/plugins/v2/download?plugin=' . $base . '&key=' . urlencode( $data['api_key'] );
	}
	/**
	 * Install Plugins.
	 *
	 * @param array $plugins The plugins to install.
	 * @param string $import_key The import key.
	 * @return boolean/WP_Error
	 */
	public function install_plugins( $plugins, $import_key ) {
		update_option( '_kadence_starter_templates_last_import_data', array( $import_key ), 'no' );
		$install = true;
		if ( ! empty( $plugins ) && is_array( $plugins ) ) {
			$importer_plugins = $this->get_allowed_plugins();
			if ( ! function_exists( 'request_filesystem_credentials' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}
			if ( ! class_exists( 'WP_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			foreach ( $plugins as $plugin ) {
				$path = false;
				if ( strpos( $plugin, '/' ) !== false ) {
					$path = $plugin;
					$arr  = explode( '/', $plugin, 2 );
					$base = $arr[0];
					if ( isset( $importer_plugins[ $base ] ) && isset( $importer_plugins[ $base ]['src'] ) ) {
						$src = $importer_plugins[ $base ]['src'];
					} else {
						$src = 'unknown';
					}
				} elseif ( isset( $importer_plugins[ $plugin ] ) ) {
					$path = $importer_plugins[ $plugin ]['path'];
					$base = $importer_plugins[ $plugin ]['base'];
					$src  = $importer_plugins[ $plugin ]['src'];
				}
				if ( $path ) {
					$state = Plugin_Check::active_check( $path );
					if ( 'unknown' === $src ) {
						$check_api = plugins_api(
							'plugin_information',
							array(
								'slug' => $base,
								'fields' => array(
									'short_description' => false,
									'sections' => false,
									'requires' => false,
									'rating' => false,
									'ratings' => false,
									'downloaded' => false,
									'last_updated' => false,
									'added' => false,
									'tags' => false,
									'compatibility' => false,
									'homepage' => false,
									'donate_link' => false,
								),
							)
						);
						if ( ! is_wp_error( $check_api ) ) {
							$src = 'repo';
						}
					}
					if ( 'notactive' === $state && 'repo' === $src ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							return new WP_Error( 'install_failed', __( 'Permissions Issue.' ), array( 'status' => 500 ) );
						}
						$api = plugins_api(
							'plugin_information',
							array(
								'slug' => $base,
								'fields' => array(
									'short_description' => false,
									'sections' => false,
									'requires' => false,
									'rating' => false,
									'ratings' => false,
									'downloaded' => false,
									'last_updated' => false,
									'added' => false,
									'tags' => false,
									'compatibility' => false,
									'homepage' => false,
									'donate_link' => false,
								),
							)
						);
						if ( ! is_wp_error( $api ) ) {

							// Use AJAX upgrader skin instead of plugin installer skin.
							// ref: function wp_ajax_install_plugin().
							$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
							$installed = $upgrader->install( $api->download_link );
							if ( $installed ) {
								$silent = ( 'give' === $base || 'elementor' === $base || 'wp-smtp' === $base || 'fluentform' === $base || 'restrict-content' === $base ? false : true );
								if ( 'give' === $base ) {
									add_option( 'give_install_pages_created', 1, '', false );
								}
								if ( 'restrict-content' === $base ) {
									update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
								}
								$activate = activate_plugin( $path, '', false, $silent );
								if ( is_wp_error( $activate ) ) {
									$install = false;
								}
							} else {
								$install = false;
							}
						} else {
							$install = false;
						}
					} elseif ( 'notactive' === $state && 'bundle' === $src ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							return new WP_Error( 'install_failed', __( 'Permissions Issue.' ), array( 'status' => 500 ) );
						}
						$download_link = $this->get_bundle_download_link( $base );
						if ( $download_link ) {

							// Use AJAX upgrader skin instead of plugin installer skin.
							// ref: function wp_ajax_install_plugin().
							$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
							$installed = $upgrader->install( $download_link );
							if ( $installed ) {
								$silent = ( 'give' === $base || 'elementor' === $base || 'wp-smtp' === $base || 'fluentform' === $base || 'restrict-content' === $base ? false : true );
								if ( 'give' === $base ) {
									add_option( 'give_install_pages_created', 1, '', false );
								}
								if ( 'restrict-content' === $base ) {
									update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
								}
								$activate = activate_plugin( $path, '', false, $silent );
								if ( is_wp_error( $activate ) ) {
									$install = false;
								}
							} else {
								$install = false;
							}
						} else {
							$install = false;
						}
					} elseif ( 'installed' === $state ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							return new WP_Error( 'install_failed', __( 'Permissions Issue.' ), array( 'status' => 500 ) );
						}
						$silent = ( 'give' === $base || 'elementor' === $base || 'wp-smtp' === $base || 'fluentform' === $base || 'restrict-content' === $base ? false : true );
						if ( 'give' === $base ) {
							// Make sure give doesn't add it's pages, prevents having two sets.
							update_option( 'give_install_pages_created', 1, '', false );
						}
						if ( 'restrict-content' === $base ) {
							$silent = true;
							update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
						}
						$activate = activate_plugin( $path, '', false, $silent );
						if ( is_wp_error( $activate ) ) {
							$install = false;
						}
					}
					if ( 'give' === $base ) {
						update_option( 'give_version_upgraded_from', '2.13.2' );
					}
					if ( 'kadence-pro' === $base ) {
						$enabled = json_decode( get_option( 'kadence_pro_theme_config' ), true );
						$enabled['elements'] = true;
						$enabled['header_addons'] = true;
						$enabled['mega_menu'] = true;
						$enabled = json_encode( $enabled );
						update_option( 'kadence_pro_theme_config', $enabled );
					}
					if ( 'elementor' === $base ) {
						$elementor_redirect = get_transient( 'elementor_activation_redirect' );

						if ( ! empty( $elementor_redirect ) && '' !== $elementor_redirect ) {
							delete_transient( 'elementor_activation_redirect' );
						}
					}
					if ( 'woocommerce' === $base ) {
						// Create WooCommerce database tables.
						if ( is_callable( '\Automattic\WooCommerce\Admin\Install::create_tables' ) ) {
							\Automattic\WooCommerce\Admin\Install::create_tables();
							\Automattic\WooCommerce\Admin\Install::create_events();
						}

						if ( is_callable( 'WC_Install::install' ) ) {
							WC_Install::install();
						}
					}
				}
			}
		}
		if ( false === $install ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}

		return true;
	}
	/**
	 * Replace Colors.
	 *
	 * @param string $content The content to replace colors in.
	 * @param string $style The style to replace colors in.
	 * @param array $color_palette The color palette to use.
	 * @return string The content.
	 */
	public function replace_colors( $content, $style, $color_palette ) {
		if ( in_array( $style, array( 'dark', 'highlight' ) ) ) {
			$content = Color_Handler::replace_colors( $content, $style, ( !empty( $color_palette['btnColor'] ) ? $color_palette['btnColor'] : '#ffffff' ) );
		}
		if ( isset( $color_palette['isLight'] ) && $color_palette['isLight'] === false && 'highlight' === $style && '#ffffff' === $color_palette['btnColor'] ) {
			$content = Color_Handler::replace_contrast_colors( $content, 'highlight' );
		}
		if ( isset( $color_palette['isLight'] ) && $color_palette['isLight'] === false && 'light' === $style ) {
			$content = Color_Handler::replace_logo_farm_colors( $content, 'dark' );
		}
		return $content;
	}
	/**
	 * Build Page Content.
	 *
	 * @param array $rows The rows to build.
	 * @param array $image_library The image library to use.
	 * @return string The content.
	 */
	public function build_page_content( $rows, $image_library, $ai_content, $color_palette, $goals, $product_ids, $page_data, $team_image_collection, $i, $give_form_id ) {
		$content = '';
		$real_variation = 0;
		$variation = $i;
		$is_home = ( $page_data['slug'] === 'home' ? true : false );
		foreach ( $rows as $row ) {
			if ( $variation > 11 ) {
				$variation = 0;
			}
			// Get only the keys in the pattern category array.
			$row_categories = ( isset( $row['pattern_category'] ) ? array_keys( $row['pattern_category'] ) : [] );
			$row_style = ( isset( $row['pattern_style'] ) ? $row['pattern_style'] : 'light' );
			$row_content = ( isset( $row['pattern_content'] ) ? $row['pattern_content'] : '' );
			$row_context = ( isset( $row['pattern_context'] ) ? $row['pattern_context'] : '' );
			$row_pattern_id = ( isset( $row['pattern_id'] ) ? $row['pattern_id'] : $row_context );
			$row_condition = ( isset( $row['pattern_condition'] ) ? $row['pattern_condition'] : '' );
			if ( $row_context === 'contact' ) {
				$row_context = 'contact-form';
			} else if ( $row_context === 'subscribe' ) {
				$row_context = 'subscribe-form';
			} else if ( $row_context === 'pricing' ) {
				$row_context = 'pricing-table';
			}
			$row_hero = false;
			if ( $is_home && $real_variation === 0 ) {
				$row_hero = 'hero';
			} else if ( $is_home && $real_variation === 1 ) {
				$row_hero = 'secondary';
			}
			// Remove content that should be removed.
			$row_content = Content_Remover::remove_content( $row_content );
			// Don't output product loops if we don't have any products.
			if ( in_array( 'product-loop', $row_categories ) ) {
				if ( in_array( 'ecommerce', $goals ) && $product_ids ) {
					$row_content = Woo_Content_Handler::replace_woo_content( $row_content, $product_ids );
				} else {
					continue;
				}
			}
			// Don't output donation form if the site isn't wanting to accept donations.
			if ( in_array( 'donation-form', $row_categories ) ) {
				if ( in_array( 'donations', $goals ) ) {
					$row_content = Donation_Form_Handler::replace_donation_content( $row_content, $give_form_id );
				} else {
					continue;
				}
			}
			// Don't output sections that don't match the goals.
			if ( $row_condition !== '' && $row_condition !== 'general' ) {
				if ( $row_condition === 'replace' && ( in_array( 'ecommerce', $goals ) || in_array( 'events', $goals ) || in_array( 'donations', $goals ) || in_array( 'learning', $goals ) || in_array( 'membership', $goals ) || in_array( 'photography', $goals ) || in_array( 'landing', $goals ) || in_array( 'blogging', $goals ) ) ) {
					continue;
				} else if ( ! in_array( $row_condition, $goals ) ) {
					continue;
				}
			}
			// Replace colors.
			$row_content = $this->replace_colors( $row_content, $row_style, $color_palette );
			// Replace images.
			$row_content = Image_Replacer::replace_images(
				$row_content,
				$image_library,
				$row_categories,
				$row_pattern_id,
				$variation,
				$team_image_collection,
				$row_hero,
			);
			// Replace content.
			$row_content = Content_Replacer::replace_content(
				$row_content,
				$ai_content,
				$row_categories,
				$row_context,
				$variation,
				false,
				$page_data,
			);
			$content .= $row_content;
			$variation++;
			$real_variation++;
		}
		return $content;
	}

	/**
	 * Prepare Posts.
	 *
	 * @param array $pages The pages to install.
	 * @param array $image_library The image library to use.
	 * @return array The pages.
	 */
	public function prepare_pages( $pages, $image_library, $ai_content, $color_palette, $goals, $product_ids, $team_image_collection, $give_form_id ) {
		if ( empty( $pages ) || ! is_array( $pages ) ) {
			return new WP_Error( 'no_pages', __( 'No pages to prepare.' ), array( 'status' => 500 ) );
		}
		$processed_pages = $this->process_pages( $pages );
		if ( is_wp_error( $processed_pages ) ) {
			return $processed_pages;
		}
		$i = 0;
		$prepared_pages = array();
		foreach ( $processed_pages as $page_data ) {
			if ( empty( $page_data['rows'] ) ) {
				continue;
			}
			$page_item = [
				'key' => $i,
				'slug' => ( isset( $page_data['slug'] ) ? $page_data['slug'] : '' ),
				'title' => ( isset( $page_data['title'] ) ? $page_data['title'] : '' ),
				'content' => $this->build_page_content( $page_data['rows'], $image_library, $ai_content, $color_palette, $goals, $product_ids, $page_data, $team_image_collection, $i, $give_form_id ),
			];
			$prepared_pages[] = $page_item;
			$i++;
		}
		return $prepared_pages;
	}
	/**
	 * Install Pages.
	 *
	 * @param array $pages The pages to install.
	 * @param array $image_library The image library to use.
	 * @return array The pages.
	 */
	public function install_pages_extras( $pages, $image_library ) {
		if ( empty( $pages ) ) {
			return new WP_Error( 'no_pages', __( 'No pages to install.' ), array( 'status' => 500 ) );
		}
		$new_pages = array();
		foreach ( $pages as $page_data ) {
			// Create page using wp_insert_post.
			$page_item = array(
				'post_title'   => ( isset( $page_data['title'] ) ? wp_strip_all_tags( $page_data['title'] ) : '' ),
				'post_content' => $this->process_page_content( $page_data['content'], $image_library ),
			);
			$new_pages[] = $page_item;
		}
		if ( empty( $new_pages ) ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}
		return $new_pages;
	}
	/**
	 * Install Pages.
	 *
	 * @param array $pages The posts to install.
	 * @return bool/WP_Error The posts.
	 */
	public function install_pages( $pages ) {
		if ( empty( $pages ) ) {
			return new WP_Error( 'no_posts', __( 'No posts to install.' ), array( 'status' => 500 ) );
		}
		$new_pages = [];
		foreach ( $pages as $post_data ) {
			$args = [
				'post_title'   => $post_data['post_title'],
				'post_content' => $post_data['post_content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
			];
			$page_id = wp_insert_post(wp_slash( $args ) );
			if ( is_wp_error( $page_id ) ) {
				return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
			}
			update_post_meta( $page_id, '_kad_post_title', 'hide' );
			update_post_meta( $page_id, '_kad_post_content_style', 'unboxed' );
			update_post_meta( $page_id, '_kad_post_vertical_padding', 'hide' );
			update_post_meta( $page_id, '_kad_post_feature', 'hide' );
			update_post_meta( $page_id, '_kad_post_layout', 'fullwidth' );
			update_post_meta( $page_id, '_kadence_starter_templates_imported_post', true );
			$new_pages[] = $page_id;
			if ( isset( $post_data['post_title'] ) && 'Home' === $post_data['post_title'] ) {
				update_option( 'page_on_front', $page_id );
				update_option( 'show_on_front', 'page' );
			}
		}
		return $new_pages;
	}
	/**
	 * Trigger writing cache.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger_writing_cache() {
		$this->block_library_cache->terminate();
		$this->ai_cache->terminate();
	}
	/**
	 * Get remote file contents.
	 *
	 * @access public
	 * @return string Returns the remote URL contents.
	 */
	public function get_remote_image_collections() {
		$api_url  = $this->remote_ai_url . 'images/collections';
		$response = wp_safe_remote_get(
			$api_url,
			array(
				'timeout' => 20,
				'headers' => array(
					'X-Prophecy-Token' => $this->get_token_header(),
				),
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return 'error';
		}

		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );
		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return 'error';
		}

		return $contents;
	}
	/**
	 * Install Navigation.
	 *
	 * @param string $site_id The site ID.
	 * @param array $install_goals The install goals.
	 * @return bool|WP_Error True on success, or WP_Error object on failure.
	 */
	public function install_navigation( $site_id, $install_goals ) {
		$install_goal = ( isset( $install_goals[0] ) ? $install_goals[0] : '' );
		$url = 'https://base.startertemplatecloud.com/' . $site_id . '/wp-json/kadence-starter-base/v1/navigation';
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 20,
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get navigation from source.' ), array( 'status' => 500 ) );
		}

		// Get the body from our response.
		$navigation = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $navigation ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get navigation from source.' ), array( 'status' => 500 ) );
		}
		$navigation = json_decode( $navigation, true );
		if ( ! is_array( $navigation ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get navigation from source.' ), array( 'status' => 500 ) );
		}
		$data = array();
		foreach ( $navigation as $location_key => $menu ) {
			$menu_exists = wp_get_nav_menu_object( $menu['name'] );
			if ( $menu_exists ) {
				if ( $location_key !== 'primary' && $navigation[$location_key] === $navigation['primary'] ) {
					$locations = get_theme_mod( 'nav_menu_locations' );
					$locations[ $location_key ] = $menu_exists->term_id;
					set_theme_mod( 'nav_menu_locations', $locations );
					continue;
				} else {
					wp_delete_nav_menu( $menu_exists->term_id );
				}
			}
			$menu_id = wp_create_nav_menu( $menu['name'] );
			$updates = array();
			$extra_order = 0;
			// Set up default menu items
			foreach ( $menu['items'] as $item ) {
				if ( 'Shop' === $item['title'] ) {
					$extra_order = $item['menu_order'];
					continue;
				}
				$args = array(
					'menu-item-title' => $item['title'],
					'menu-item-url' => '#',
					'menu-item-status' => 'publish',
					'menu-item-position' => $item['menu_order'],
				);
				// Lets not duplicate pages.
				$has_page = get_posts( [
					'post_type'  => 'page',
					'title'      => $item['title'],
				] );
				if ( $has_page ) {
					$args = array(
						'menu-item-title' => get_the_title( $has_page[0]->ID ),
						'menu-item-object-id' => $has_page[0]->ID,
						'menu-item-object'    => 'page',
						'menu-item-status'    => 'publish',
						'menu-item-type'      => 'post_type',
						'menu-item-position'  => $item['menu_order'],
					);
				} else if ( ! empty( $item['title'] ) && 'Blog' === $item['title'] ) {
					// Create Blog page using wp_insert_post
					$page_id = wp_insert_post(
						array(
						'post_title'   => wp_strip_all_tags( $item['title'] ),
						'post_content' => '',
						'post_status'  => 'publish',
						'post_type'    => 'page',
						)
					);
					if ( ! is_wp_error( $page_id ) ) {
						$args = array(
							'menu-item-title' => $item['title'],
							'menu-item-object-id' => $page_id,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $item['menu_order'],
						);
						update_option( 'page_for_posts', $page_id );
						update_post_meta( $page_id, '_kadence_starter_templates_imported_post', true );
					}
				}
				if ( ! empty( $item['menu_item_parent'] ) ) {
					$args['menu-item-parent-id'] = $updates[ $item['menu_item_parent'] ];
				}
				$updates[ $item['id'] ] = wp_update_nav_menu_item(
					$menu_id,
					0,
					$args
				);
			}
			update_term_meta( $menu_id, '_kadence_starter_templates_imported_term', true );
			$header_button_text = 'Get Started';
			$header_button_url = '#';
			$extra_added = false;
			if ( $location_key === 'primary' || $location_key === 'mobile' ) {
				if ( 'events' === $install_goal && post_type_exists( 'tribe_events' ) ) {
					$args = array(
						'menu-item-title' => 'Events',
						'menu-item-url' => get_post_type_archive_link( 'tribe_events' ),
						'menu-item-status' => 'publish',
						'menu-item-position'  => $extra_order,
					);
					$item_id = wp_update_nav_menu_item(
						$menu_id,
						0,
						$args
					);
					$header_button_text = 'Calendar';
					$header_button_url = get_post_type_archive_link( 'tribe_events' );
					$extra_added = true;
				} else if ( 'tickets' === $install_goal ) {
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Pricing',
					] );
					if ( $has_page ) {
						$args = array(
							'menu-item-title' => 'Pricing',
							'menu-item-object-id' => $has_page[0]->ID,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$header_button_text = 'Get Tickets';
						$header_button_url = get_the_permalink( $has_page[0]->ID );
						$extra_added = true;
					}
				} else if ( 'ecommerce' === $install_goal && class_exists( 'WooCommerce' ) ) {
					$page_id   = wc_get_page_id( 'shop' );
					$shop_page = get_post( $page_id );
					if ( ! $shop_page ) {
						// Create Shop page using wp_insert_post
						$page_id = wp_insert_post(
							array(
							'post_title'   => 'Shop',
							'post_content' => '',
							'post_status'  => 'publish',
							'post_type'    => 'page',
							)
						);
						if ( ! is_wp_error( $page_id ) ) {
							update_option( 'woocommerce_shop_page_id', $page_id );
							update_post_meta( $page_id, '_kadence_starter_templates_imported_post', true );
						}
					}
					if ( ! empty( $page_id  ) && ! is_wp_error( $page_id ) ) {
						$args = array(
							'menu-item-title'     => 'Shop',
							'menu-item-object-id' => $page_id,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$header_button_text = 'Shop Now';
						$header_button_url = get_the_permalink( $page_id );
						$extra_added = true;
					}
				} else if ( 'courses' === $install_goal && post_type_exists( 'sfwd-courses' ) ) {
					// Lets not duplicate pages.
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Courses',
					] );
					if ( $has_page ) {
						$args = array(
							'menu-item-title'     => get_the_title( $has_page[0]->ID ),
							'menu-item-object-id' => $has_page[0]->ID,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$header_button_text = 'View Courses';
						$header_button_url = get_the_permalink( $has_page[0]->ID );
						$extra_added = true;
					} else {
						if ( defined( 'LEARNDASH_COURSE_GRID_VERSION' ) ) {
							$page_content = '<!-- wp:learndash/ld-course-grid {"per_page":"12","thumbnail_size":"medium","ribbon":false,"title_clickable":true,"post_meta":false,"button":true,"pagination":"false","grid_height_equal":true,"progress_bar":true,"filter":false,"card":"grid-3","items_per_row":"3","font_family_title":"inter","font_family_description":"inter","font_size_title":"24px","font_size_description":"14px","font_color_description":"#4a4a68","id":"ld-cg-lxdnpir6oz","filter_search":false,"filter_price":false,"className":"home-course-grid"} /-->';
							// Create Courses using wp_insert_post
							$page_id = wp_insert_post(
								array(
								'post_title'   => 'Courses',
								'post_name'    => 'our-courses',
								'post_content' => $page_content,
								'post_status'  => 'publish',
								'post_type'    => 'page',
								)
							);
							if ( ! is_wp_error( $page_id ) ) {
								update_post_meta( $page_id, '_kadence_starter_templates_imported_post', true );
								update_post_meta( $page_id, '_kad_post_layout', 'normal' );
								$args = array(
									'menu-item-title'     => 'Courses',
									'menu-item-object-id' => $page_id,
									'menu-item-object'    => 'page',
									'menu-item-status'    => 'publish',
									'menu-item-type'      => 'post_type',
									'menu-item-position'  => $extra_order,
								);
								$item_id = wp_update_nav_menu_item(
									$menu_id,
									0,
									$args
								);
								$header_button_text = 'View Courses';
								$header_button_url = get_the_permalink( $page_id );
								$extra_added = true;
							}
						} else {
							$args = array(
								'menu-item-title' => 'Courses',
								'menu-item-url' => get_post_type_archive_link( 'sfwd-courses' ),
								'menu-item-status' => 'publish',
								'menu-item-position'  => $extra_order,
							);
							$item_id = wp_update_nav_menu_item(
								$menu_id,
								0,
								$args
							);
							$extra_added = true;
							$header_button_text = 'View Courses';
							$header_button_url = get_post_type_archive_link( 'sfwd-courses' );
						}
					}
				} else if ( 'donations' === $install_goal ) {
					// Find the our mission page.
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Our Mission',
					] );
					if ( $has_page ) {
						$args = array(
							'menu-item-title'     => get_the_title( $has_page[0]->ID ),
							'menu-item-object-id' => $has_page[0]->ID,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$header_button_text = 'Donate Now';
						$header_button_url = get_the_permalink( $has_page[0]->ID );
						$extra_added = true;
					}
				} else if ( 'services' === $install_goal ) {
					// Find the our mission page.
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Services',
					] );
					if ( $has_page ) {
						$args = array(
							'menu-item-title'     => get_the_title( $has_page[0]->ID ),
							'menu-item-object-id' => $has_page[0]->ID,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$header_button_text = 'Get Started';
						$header_button_url = get_the_permalink( $has_page[0]->ID );
						$extra_added = true;
					}
				} else if ( 'landing' === $install_goal || 'booking' === $install_goal || 'membership' === $install_goal ) {
					// Find the our pricing page.
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Pricing',
					] );
					if ( $has_page ) {
						$args = array(
							'menu-item-title'     => get_the_title( $has_page[0]->ID ),
							'menu-item-object-id' => $has_page[0]->ID,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$header_button_text = 'Get Started';
						$header_button_url = get_the_permalink( $has_page[0]->ID );
						$extra_added = true;
					}
				} else if ( 'blogging' === $install_goal ) {
					// Create Blog page using wp_insert_post
					$page_id = wp_insert_post(
						array(
						'post_title'   => 'Blog',
						'post_content' => '',
						'post_status'  => 'publish',
						'post_type'    => 'page',
						)
					);
					if ( ! is_wp_error( $page_id ) ) {
						$args = array(
							'menu-item-title'     => 'Blog',
							'menu-item-object-id' => $page_id,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$extra_added = true;
						update_option( 'page_for_posts', $page_id );
						update_post_meta( $page_id, '_kadence_starter_templates_imported_post', true );
					}
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Contact',
					] );
					if ( $has_page ) {
						$header_button_text = 'Subscribe';
						$header_button_url = get_the_permalink( $has_page[0]->ID );
					}
				} else if ( 'podcasting' === $install_goal ) {
					// Create Blog page using wp_insert_post
					$page_id = wp_insert_post(
						array(
						'post_title'   => 'Podcast',
						'post_content' => '',
						'post_status'  => 'publish',
						'post_type'    => 'page',
						)
					);
					if ( ! is_wp_error( $page_id ) ) {
						$args = array(
							'menu-item-title'     => 'Podcast',
							'menu-item-object-id' => $page_id,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$extra_added = true;
						update_option( 'page_for_posts', $page_id );
						update_post_meta( $page_id, '_kadence_starter_templates_imported_post', true );
					}
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Contact',
					] );
					if ( $has_page ) {
						$header_button_text = 'Subscribe';
						$header_button_url = get_the_permalink( $has_page[0]->ID );
					}
				} else if ( 'photography' === $install_goal ) {
					// Find the our Gallery page.
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Gallery',
					] );
					if ( $has_page ) {
						$args = array(
							'menu-item-title'     => get_the_title( $has_page[0]->ID ),
							'menu-item-object-id' => $has_page[0]->ID,
							'menu-item-object'    => 'page',
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'post_type',
							'menu-item-position'  => $extra_order,
						);
						$item_id = wp_update_nav_menu_item(
							$menu_id,
							0,
							$args
						);
						$extra_added = true;
					}
					$has_page = get_posts( [
						'post_type'  => 'page',
						'title'      => 'Contact',
					] );
					if ( $has_page ) {
						$header_button_text = 'Contact Me';
						$header_button_url = get_the_permalink( $has_page[0]->ID );
					}
				}

			}
			// Update the header button text and url.
			set_theme_mod( 'header_button_label', $header_button_text );
			set_theme_mod( 'header_button_link', $header_button_url );
			// if ( ! $extra_added ) {
			// 	// Check if any of the goals contained in $install_goals are installed.
			// }
			$locations = get_theme_mod( 'nav_menu_locations' );
			$locations[ $location_key ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
		// Make sure woocommerce pages are built and set.
		if ( class_exists( 'WooCommerce' ) ) {
			if ( is_callable( 'WC_Install::create_pages' ) ) {
				WC_Install::create_pages();
			}
		}
		flush_rewrite_rules();
		wp_cache_flush();
		return true;
	}
	/**
	 * Available widgets.
	 *
	 * Gather site's widgets into array with ID base, name, etc.
	 *
	 * @global array $wp_registered_widget_controls
	 * @return array $available_widgets, Widget information
	 */
	private function available_widgets() {
		global $wp_registered_widget_controls;

		$widget_controls   = $wp_registered_widget_controls;
		$available_widgets = array();

		foreach ( $widget_controls as $widget ) {
			if ( ! empty( $widget['id_base'] ) && ! isset( $available_widgets[ $widget['id_base'] ] ) ) {
				$available_widgets[ $widget['id_base'] ]['id_base'] = $widget['id_base'];
				$available_widgets[ $widget['id_base'] ]['name']    = $widget['name'];
			}
		}

		return $available_widgets;
	}
	/**
	 * Move footer widgets to inactive.
	 */
	public function move_widgets_to_inactive() {
		// Get all widgets.
		$sidebars_widgets = wp_get_sidebars_widgets();
		// Check if the footer widget areas are set and not empty.
		foreach ( array( 'sidebar-primary', 'sidebar-secondary', 'footer1', 'footer2', 'footer3', 'footer4', 'footer5', 'footer6' ) as $widget_area ) {
			if ( ! empty( $sidebars_widgets[ $widget_area ] ) ) {
				// Move all footer-1 widgets to inactive widgets.
				foreach ( $sidebars_widgets[ $widget_area ] as $widget_id ) {
					$sidebars_widgets['wp_inactive_widgets'][] = $widget_id;
				}
				$sidebars_widgets[ $widget_area ] = array();
			}
		}
		// Save the updated widgets configuration.
		wp_set_sidebars_widgets( $sidebars_widgets );
	}
	/**
	 * Install Widgets.
	 *
	 * @param string $site_id The site ID.
	 * @param string $site_name The site name.
	 * @return array The results.
	 */
	public function install_widgets( $site_id, $site_name ) {
		global $wp_registered_sidebars;
		$url = 'https://base.startertemplatecloud.com/' . $site_id . '/wp-json/kadence-starter-base/v1/widgets';
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 20,
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get widgets from source.' ), array( 'status' => 500 ) );
		}

		// Get the body from our response.
		$sidebars = wp_remote_retrieve_body( $response );
		// Early exit if there was an error.
		if ( empty( $sidebars ) ) {
			return rest_ensure_response( 'no widgets to import' );
		}
		// Early exit if there was an error.
		if ( is_wp_error( $sidebars ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get widgets from source.' ), array( 'status' => 500 ) );
		}
		$sidebars = json_decode( $sidebars, true );
		if ( ! is_array( $sidebars ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get widgets from source.' ), array( 'status' => 500 ) );
		}
		$this->move_widgets_to_inactive();
		// Get all available widgets site supports.
		$available_widgets = $this->available_widgets();

		// Begin results.
		$results = array();
		foreach ( $sidebars as $sidebar_id => $widgets ) {
			// Skip inactive widgets (should not be in export).
			if ( 'wp_inactive_widgets' == $sidebar_id ) {
				continue;
			}

			// Check if sidebar is available on this site. Otherwise add widgets to inactive, and say so.
			if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
				$sidebar_available    = true;
				$use_sidebar_id       = $sidebar_id;
				$sidebar_message_type = 'success';
				$sidebar_message      = '';
			} else {
				$sidebar_available    = false;
				$use_sidebar_id       = 'wp_inactive_widgets'; // Add to inactive if sidebar does not exist in theme.
				$sidebar_message_type = 'error';
				$sidebar_message      = __( 'Sidebar does not exist in theme (moving widget to Inactive)', 'kadence-starter-templates' );
			}

			// Result for sidebar.
			$results[ $sidebar_id ]['name']         = ! empty( $wp_registered_sidebars[ $sidebar_id ]['name'] ) ? $wp_registered_sidebars[ $sidebar_id ]['name'] : $sidebar_id; // Sidebar name if theme supports it; otherwise ID.
			$results[ $sidebar_id ]['message_type'] = $sidebar_message_type;
			$results[ $sidebar_id ]['message']      = $sidebar_message;
			$results[ $sidebar_id ]['widgets']      = array();

			// Loop widgets.
			foreach ( $widgets as $widget_instance_id => $widget ) {
				$fail = false;

				// Get id_base (remove -# from end) and instance ID number.
				$id_base            = preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
				$instance_id_number = str_replace( $id_base . '-', '', $widget_instance_id );

				// Does site support this widget?
				if ( ! $fail && ! isset( $available_widgets[ $id_base ] ) ) {
					$fail                = true;
					$widget_message_type = 'error';
					$widget_message      = __( 'Site does not support widget', 'kadence-starter-templates' ); // Explain why widget not imported.
				}
				// Convert multidimensional objects to multidimensional arrays.
				// Some plugins like Jetpack Widget Visibility store settings as multidimensional arrays.
				// Without this, they are imported as objects and cause fatal error on Widgets page.
				$widget = json_decode( json_encode( $widget ), true );

				// Filter to modify settings array.
				$widget = apply_filters( 'kadence-starter-templates/rest_widget_settings_array', $widget );
				// Skip (no changes needed), if this is not a custom menu widget.
				if ( array_key_exists( 'nav_menu', $widget ) && ! empty( $widget['nav_menu'] ) && ! is_int( $widget['nav_menu'] ) ) {
					$menu_exists = wp_get_nav_menu_object( $widget['nav_menu'] );
					if ( $menu_exists ) {
						$widget['nav_menu'] = $menu_exists->term_id;
					}
				}
				if ( ! empty( $widget['content'] ) ) {
					$widget['content'] = str_replace( 'Redwood', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Laurel', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Acorn', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Cedar', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Maple', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Sequoia', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Acacia', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Magnolia', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Willow', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Hemlock', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Fig', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Aspen', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Juniper', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Almond', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Elm', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Mahogany', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Oakleaf', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Olive', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Pinecone', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Birch', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Cherry', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Beech', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Cypress', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Fir', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Eucalyptus', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Banyan', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Ash', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Sycamore', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Palm', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Hawthorn', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Chestnut', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Mango', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Pecan', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Baobab', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Teak', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Apple', $site_name, $widget['content'] );
					$widget['content'] = str_replace( 'Pear', $site_name, $widget['content'] );
				}

				// No failure.
				if ( ! $fail ) {
					// Add widget instance.
					$single_widget_instances   = get_option( 'widget_' . $id_base ); // All instances for that widget ID base, get fresh every time.
					$single_widget_instances   = ! empty( $single_widget_instances ) ? $single_widget_instances : array( '_multiwidget' => 1 ); // Start fresh if have to.
					$single_widget_instances[] = $widget; // Add it.

					// Get the key it was given.
					end( $single_widget_instances );
					$new_instance_id_number = key( $single_widget_instances );

					// If key is 0, make it 1.
					// When 0, an issue can occur where adding a widget causes data from other widget to load, and the widget doesn't stick (reload wipes it).
					if ( '0' === strval( $new_instance_id_number ) ) {
						$new_instance_id_number                           = 1;
						$single_widget_instances[ $new_instance_id_number ] = $single_widget_instances[0];
						unset( $single_widget_instances[0] );
					}

					// Move _multiwidget to end of array for uniformity.
					if ( isset( $single_widget_instances['_multiwidget'] ) ) {
						$multiwidget = $single_widget_instances['_multiwidget'];
						unset( $single_widget_instances['_multiwidget'] );
						$single_widget_instances['_multiwidget'] = $multiwidget;
					}

					// Update option with new widget.
					update_option( 'widget_' . $id_base, $single_widget_instances );

					// Assign widget instance to sidebar.
					$sidebars_widgets = get_option( 'sidebars_widgets' ); // Which sidebars have which widgets, get fresh every time.

					// Avoid rarely fatal error when the option is an empty string
					// https://github.com/churchthemes/widget-importer-exporter/pull/11.
					if ( ! $sidebars_widgets ) {
						$sidebars_widgets = array();
					}

					$new_instance_id = $id_base . '-' . $new_instance_id_number; // Use ID number from new widget instance.
					$sidebars_widgets[ $use_sidebar_id ][] = $new_instance_id; // Add new instance to sidebar.
					update_option( 'sidebars_widgets', $sidebars_widgets ); // Save the amended data.

					// After widget import action.
					$after_widget_import = array(
						'sidebar'           => $use_sidebar_id,
						'sidebar_old'       => $sidebar_id,
						'widget'            => $widget,
						'widget_type'       => $id_base,
						'widget_id'         => $new_instance_id,
						'widget_id_old'     => $widget_instance_id,
						'widget_id_num'     => $new_instance_id_number,
						'widget_id_num_old' => $instance_id_number,
					);

					// Success message.
					if ( $sidebar_available ) {
						$widget_message_type = 'success';
						$widget_message      = __( 'Imported', 'kadence-starter-templates' );
					} else {
						$widget_message_type = 'warning';
						$widget_message      = __( 'Imported to Inactive', 'kadence-starter-templates' );
					}
				}

				// Result for widget instance.
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['name']         = isset( $available_widgets[ $id_base ]['name'] ) ? $available_widgets[ $id_base ]['name'] : $id_base; // Widget name or ID if name not available (not supported by site).
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['title']        = ! empty( $widget['title'] ) ? $widget['title'] : __( 'No Title', 'kadence-starter-templates' ); // Show "No Title" if widget instance is untitled.
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message_type'] = $widget_message_type;
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message']      = $widget_message;

			}
		}
		return $results;
	}
	/**
	 * Install Pages.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function install_settings( $site_id, $site_name, $color_palette, $dark_footer, $fonts, $donation_form_id = '' ) {
		if ( empty( $site_id ) ) {
			return new WP_Error( 'instal_failed', __( 'No settings set.' ), array( 'status' => 500 ) );
		}
		$url = 'https://base.startertemplatecloud.com/' . $site_id . '/wp-json/kadence-starter-base/v1/settings';
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 20,
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get settings from source.' ), array( 'status' => 500 ) );
		}

		// Get the body from our response.
		$settings = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $settings ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get settings from source.' ), array( 'status' => 500 ) );
		}
		$settings = json_decode( $settings, true );
		if ( ! is_array( $settings ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get settings from source.' ), array( 'status' => 500 ) );
		}

		$data = array();
		// Clear out the theme mods.
		delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );

		if ( isset( $settings['mods'] ) ) {
			$data['mods'] = $this->process_options_images( $settings['mods'] );
		}
		if ( isset( $settings['wp_css'] ) ) {
			$data['wp_css'] = $settings['wp_css'];
		}
		if ( isset( $settings['options'] ) ) {
			$keys = array_keys( $settings['options'] );
			$keys = array_map( 'sanitize_key', $keys );

			$values = array_values( $settings['options'] );
			$values = array_map( 'sanitize_text_field', $values );

			$options_array = array_combine( $keys, $values );
			$data['options'] = $options_array;
		}
		// Set the site name.
		if ( ! empty( $site_name ) ) {
			update_option( 'blogname', $site_name );
		}
		$primary_color = '';
		// Import custom options.
		if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
			foreach ( $data['options'] as $option_key => $option_value ) {
				update_option( $option_key, $option_value );
				if ( 'kadence_global_palette' === $option_key ) {
					$palette = json_decode( $option_value, true );
					$active = ( ! empty($palette['active'] ) ? $palette['active'] : 'palette' );
					$primary_color = ( ! empty( $palette[$active][0]['color'] ) ? $palette[$active][0]['color'] : '' );
				}
			}
		}
		// Loop through the mods.
		foreach ( $data['mods'] as $key => $val ) {
			// Save the mod.
			set_theme_mod( $key, $val );
		}
		if ( empty( $color_palette['colors'] ) && ! empty( $primary_color ) && ! empty( $donation_form_id ) ) {
			$this->update_donation_form_primary_color( $primary_color, $donation_form_id );
		}
		if ( ! empty( $color_palette ) ) {
			if ( ! empty( $color_palette['colors'] ) && is_array( $color_palette['colors'] ) ) {
				$palette = get_option( 'kadence_global_palette' );
				if ( ! empty( $palette ) ) {
					$palette = json_decode( $palette, true );
					$palette['palette'][0]['color'] = $color_palette['colors'][0];
					$palette['palette'][1]['color'] = $color_palette['colors'][1];
					$palette['palette'][2]['color'] = $color_palette['colors'][2];
					$palette['palette'][3]['color'] = $color_palette['colors'][3];
					$palette['palette'][4]['color'] = $color_palette['colors'][4];
					$palette['palette'][5]['color'] = $color_palette['colors'][5];
					$palette['palette'][6]['color'] = $color_palette['colors'][6];
					$palette['palette'][7]['color'] = $color_palette['colors'][7];
					$palette['palette'][8]['color'] = $color_palette['colors'][8];
					$palette['active'] = 'palette';
					update_option( 'kadence_global_palette', json_encode( $palette ) );
				}
				if ( ! empty( $color_palette['btnColor'] ) ) {
					set_theme_mod(
						'buttons_color',
						array(
							'color'  => $color_palette['btnColor'],
							'hover'  => $color_palette['btnColor'],
						)
					);
				}
				if ( isset( $color_palette['isLight'] ) && ! $color_palette['isLight'] ) {
					if ( isset( $dark_footer ) && $dark_footer ) {
						$color_check = array(
							'palette3',
							'palette4',
							'palette5',
							'palette6',
							'palette7',
							'palette8',
							'palette9',
						);
						$color_conversion = array(
							'palette3' => 'palette7',
							'palette4' => 'palette8',
							'palette5' => 'palette9',
							'palette6' => 'palette9',
							'palette7' => 'palette6',
							'palette8' => 'palette5',
							'palette9' => 'palette4',
						);
						foreach ( array( 'footer_wrap_background', 'footer_top_background', 'footer_middle_background', 'footer_bottom_background' ) as $footer_area ) {
							$footer_area_mod = get_theme_mod( $footer_area );
							if ( ! empty( $footer_area_mod['desktop']['color'] ) && in_array( $footer_area_mod['desktop']['color'], $color_check ) ) {
								$footer_area_mod['desktop']['color'] = $color_conversion[ $footer_area_mod['desktop']['color'] ];
								set_theme_mod( $footer_area, $footer_area_mod );
							}
						}
						foreach ( array( 'footer_top_widget_title', 'footer_top_widget_content', 'footer_middle_widget_title', 'footer_middle_widget_content', 'footer_bottom_widget_title', 'footer_bottom_widget_content', 'footer_html_typography' ) as $footer_title ) {
							$footer_title_mod = get_theme_mod( $footer_title );
							if ( ! empty( $footer_title_mod['color'] ) && in_array( $footer_title_mod['color'], $color_check ) ) {
								$footer_title_mod['color'] = $color_conversion[ $footer_title_mod['color'] ];
								set_theme_mod( $footer_title, $footer_title_mod );
							}
						}
						foreach ( array( 'footer_top_widget_content_color', 'footer_middle_widget_content_color', 'footer_bottom_widget_content_color', 'footer_navigation_color', 'footer_navigation_background', 'footer_social_color', 'footer_social_background', 'footer_social_border_colors', 'footer_html_link_color' ) as $footer_color ) {
							$footer_color_mod = get_theme_mod( $footer_color );
							$update = false;
							if ( ! empty( $footer_color_mod['color'] ) && in_array( $footer_color_mod['color'], $color_check ) ) {
								$footer_color_mod['color'] = $color_conversion[ $footer_color_mod['color'] ];
								$update = true;
							}
							if ( ! empty( $footer_color_mod['hover'] ) && in_array( $footer_color_mod['hover'], $color_check ) ) {
								$footer_color_mod['hover'] = $color_conversion[ $footer_color_mod['hover'] ];
								$update = true;
							}
							if ( ! empty( $footer_color_mod['active'] ) && in_array( $footer_color_mod['active'], $color_check ) ) {
								$footer_color_mod['active'] = $color_conversion[ $footer_color_mod['active'] ];
								$update = true;
							}
							if ( $update ) {
								set_theme_mod( $footer_color, $footer_color_mod );
							}
						}
						foreach ( array( 'footer_top_top_border', 'footer_top_bottom_border', 'footer_top_column_border', 'footer_middle_top_border', 'footer_middle_bottom_border', 'footer_middle_column_border', 'footer_bottom_top_border', 'footer_bottom_bottom_border', 'footer_bottom_column_border' ) as $footer_border ) {
							$footer_border_mod = get_theme_mod( $footer_border );
							$update = false;
							if ( ! empty( $footer_border_mod['desktop']['color'] ) && in_array( $footer_border_mod['desktop']['color'], $color_check ) ) {
								$footer_border_mod['desktop']['color'] = $color_conversion[ $footer_border_mod['desktop']['color'] ];
								$update = true;
							}
							if ( ! empty( $footer_border_mod['tablet']['color'] ) && in_array( $footer_border_mod['tablet']['color'], $color_check ) ) {
								$footer_border_mod['tablet']['color'] = $color_conversion[ $footer_border_mod['tablet']['color'] ];
								$update = true;
							}
							if ( ! empty( $footer_border_mod['mobile']['color'] ) && in_array( $footer_border_mod['mobile']['color'], $color_check ) ) {
								$footer_border_mod['mobile']['color'] = $color_conversion[ $footer_border_mod['mobile']['color'] ];
								$update = true;
							}
							if ( $update ) {
								set_theme_mod( $footer_border, $footer_border_mod );
							}
						}
					}
				}
			}
		}
		// If wp_css is set then import it.
		if ( function_exists( 'wp_update_custom_css_post' ) && isset( $data['wp_css'] ) && '' !== $data['wp_css'] ) {
			wp_update_custom_css_post( $data['wp_css'] );
		}
		if ( ! empty( $fonts ) ) {
			if ( ! empty( $fonts['font'] ) ) {
				switch ( $fonts['font'] ) {
					case 'montserrat':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Montserrat';
						$current['google']  = true;
						$current['variant'] = array( '100', '100italic', '200', '200italic', '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Source Sans Pro';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;
					case 'playfair':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Playfair Display';
						$current['google']  = true;
						$current['variant'] = array( 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
						set_theme_mod( 'heading_font', $current );
						$h1_font = \Kadence\kadence()->option( 'h1_font' );
						$h1_font['weight'] = 'normal';
						$h1_font['variant'] = 'regualar';
						set_theme_mod( 'h1_font', $h1_font );
						$h2_font = \Kadence\kadence()->option( 'h2_font' );
						$h2_font['weight'] = 'normal';
						$h2_font['variant'] = 'regualar';
						set_theme_mod( 'h2_font', $h2_font );
						$h3_font = \Kadence\kadence()->option( 'h3_font' );
						$h3_font['weight'] = 'normal';
						$h3_font['variant'] = 'regualar';
						set_theme_mod( 'h3_font', $h3_font );
						$h4_font = \Kadence\kadence()->option( 'h4_font' );
						$h4_font['weight'] = 'normal';
						$h4_font['variant'] = 'regualar';
						set_theme_mod( 'h4_font', $h4_font );
						$h5_font = \Kadence\kadence()->option( 'h5_font' );
						$h5_font['weight'] = 'normal';
						$h5_font['variant'] = 'regualar';
						set_theme_mod( 'h5_font', $h5_font );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Raleway';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;
					case 'oswald':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Oswald';
						$current['google']  = true;
						$current['variant'] = array( '200', '300', 'regular', '500', '600', '700' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Open Sans';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;
					case 'antic':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Antic Didone';
						$current['google']  = true;
						$current['variant'] = array( 'regular' );
						set_theme_mod( 'heading_font', $current );
						$h1_font = \Kadence\kadence()->option( 'h1_font' );
						$h1_font['weight'] = 'normal';
						$h1_font['variant'] = 'regualar';
						set_theme_mod( 'h1_font', $h1_font );
						$h2_font = \Kadence\kadence()->option( 'h2_font' );
						$h2_font['weight'] = 'normal';
						$h2_font['variant'] = 'regualar';
						set_theme_mod( 'h2_font', $h2_font );
						$h3_font = \Kadence\kadence()->option( 'h3_font' );
						$h3_font['weight'] = 'normal';
						$h3_font['variant'] = 'regualar';
						set_theme_mod( 'h3_font', $h3_font );
						$h4_font = \Kadence\kadence()->option( 'h4_font' );
						$h4_font['weight'] = 'normal';
						$h4_font['variant'] = 'regualar';
						set_theme_mod( 'h4_font', $h4_font );
						$h5_font = \Kadence\kadence()->option( 'h5_font' );
						$h5_font['weight'] = 'normal';
						$h5_font['variant'] = 'regualar';
						set_theme_mod( 'h5_font', $h5_font );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Raleway';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;
					case 'gilda':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Gilda Display';
						$current['google']  = true;
						$current['variant'] = array( 'regular' );
						set_theme_mod( 'heading_font', $current );
						$h1_font = \Kadence\kadence()->option( 'h1_font' );
						$h1_font['weight'] = 'normal';
						$h1_font['variant'] = 'regualar';
						set_theme_mod( 'h1_font', $h1_font );
						$h2_font = \Kadence\kadence()->option( 'h2_font' );
						$h2_font['weight'] = 'normal';
						$h2_font['variant'] = 'regualar';
						set_theme_mod( 'h2_font', $h2_font );
						$h3_font = \Kadence\kadence()->option( 'h3_font' );
						$h3_font['weight'] = 'normal';
						$h3_font['variant'] = 'regualar';
						set_theme_mod( 'h3_font', $h3_font );
						$h4_font = \Kadence\kadence()->option( 'h4_font' );
						$h4_font['weight'] = 'normal';
						$h4_font['variant'] = 'regualar';
						set_theme_mod( 'h4_font', $h4_font );
						$h5_font = \Kadence\kadence()->option( 'h5_font' );
						$h5_font['weight'] = 'normal';
						$h5_font['variant'] = 'regualar';
						set_theme_mod( 'h5_font', $h5_font );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Raleway';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;
					case 'cormorant':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Cormorant Garamond';
						$current['google']  = true;
						$current['variant'] = array( '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Proza Libre';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;
					case 'libre':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Libre Franklin';
						$current['google']  = true;
						$current['variant'] = array( '100', '100italic', '200', '200italic', '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Libre Baskerville';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;

					case 'lora':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Lora';
						$current['google']  = true;
						$current['variant'] = array( 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic' );
						set_theme_mod( 'heading_font', $current );
						$h1_font = \Kadence\kadence()->option( 'h1_font' );
						$h1_font['weight'] = 'normal';
						$h1_font['variant'] = 'regualar';
						set_theme_mod( 'h1_font', $h1_font );
						$h2_font = \Kadence\kadence()->option( 'h2_font' );
						$h2_font['weight'] = 'normal';
						$h2_font['variant'] = 'regualar';
						set_theme_mod( 'h2_font', $h2_font );
						$h3_font = \Kadence\kadence()->option( 'h3_font' );
						$h3_font['weight'] = 'normal';
						$h3_font['variant'] = 'regualar';
						set_theme_mod( 'h3_font', $h3_font );
						$h4_font = \Kadence\kadence()->option( 'h4_font' );
						$h4_font['weight'] = 'normal';
						$h4_font['variant'] = 'regualar';
						set_theme_mod( 'h4_font', $h4_font );
						$h5_font = \Kadence\kadence()->option( 'h5_font' );
						$h5_font['weight'] = 'normal';
						$h5_font['variant'] = 'regualar';
						set_theme_mod( 'h5_font', $h5_font );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Merriweather';
						$body['google'] = true;
						$body['weight'] = '300';
						$body['variant'] = '300';
						set_theme_mod( 'base_font', $body );
						break;

					case 'proza':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Proza Libre';
						$current['google']  = true;
						$current['variant'] = array( 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Open Sans';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;

					case 'worksans':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Work Sans';
						$current['google']  = true;
						$current['variant'] = array( '100', '100italic', '200', '200italic', '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Work Sans';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;

					case 'josefin':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Josefin Sans';
						$current['google']  = true;
						$current['variant'] = array( '100', '100italic', '200', '200italic', '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Lato';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;

					case 'nunito':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Nunito';
						$current['google']  = true;
						$current['variant'] = array( '200', '200italic', '300', '300italic', 'regular', 'italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Roboto';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;
					case 'rubik':
						$current = \Kadence\kadence()->option( 'heading_font' );
						$current['family']  = 'Rubik';
						$current['google']  = true;
						$current['variant'] = array( '300', '300italic', 'regular', 'italic', '500', '500italic', '600', '600italic', '700', '700italic', '800', '800italic', '900', '900italic' );
						set_theme_mod( 'heading_font', $current );
						$body = \Kadence\kadence()->option( 'base_font' );
						$body['family'] = 'Karla';
						$body['google'] = true;
						set_theme_mod( 'base_font', $body );
						break;
				}
			}
		}
		// Check if sitename is longer then 16 characters.
		if ( strlen( $site_name ) > 16 ) {
			$logo_font = \Kadence\kadence()->option( 'brand_typography' );
			if ( isset( $logo_font['size']['desktop'] ) ) {
				$size = $logo_font['size']['desktop'];
				$size_type = ( ! empty( $logo_font['sizeType'] ) ? $logo_font['sizeType'] : 'px' );
				if ( 'px' === $size_type ) {
					// Make the size 70% of the original size.
					$size = $size * 0.7;
					// Round to the nearest whole number.
					$size = round( $size );
					$logo_font['size']['desktop'] = $size;
					set_theme_mod( 'brand_typography', $logo_font );
				}
			}
		}
		// Setup Learndash.
		$this->setup_learndash();
		// Check permalink settings:
		$current_permalink_structure = get_option( 'permalink_structure' );

		// Check if permalinks are set to default.
		if ( empty( $current_permalink_structure ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}
		// Flush Permalinks.
		flush_rewrite_rules();

		return true;
	}
	/**
	 * Imports images for settings saved as mods.
	 *
	 * @since 0.1
	 * @access private
	 * @param array $mods An array of customizer mods.
	 * @return array The mods array with any new import data.
	 */
	private function process_options_images( $mods ) {
		foreach ( $mods as $key => $val ) {
			if ( $this->is_image_url( $val ) ) {
				$image = array(
					'id'  => 0,
					'url' => $val,
				);
				$data = $this->import_image( $image );

				$mods[ $key ] = $data['url'];
			}
		}

		return $mods;
	}
	/**
	 * Checks to see whether a string is an image url or not.
	 *
	 * @since 0.1
	 * @access private
	 * @param string $string The string to check.
	 * @return bool Whether the string is an image url or not.
	 */
	private function is_image_url( $string = '' ) {
		if ( is_string( $string ) ) {
			if ( preg_match( '/\.(jpg|jpeg|png|webp|gif)/i', $string ) ) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Get Posts.
	 *
	 * @param string $post_group The post group to get.
	 * @return array/WP_Error The posts.
	 */
	public function get_remote_posts( $post_group = 'normal' ) {
		switch ( $post_group ) {
			case 'soap':
				$url = 'https://base.startertemplatecloud.com/g32/wp-json/kadence-starter-base/v1/posts';
				break;
			default:
				$url = 'https://base.startertemplatecloud.com/wp-json/kadence-starter-base/v1/posts';
				break;
		}
		// Get the response.
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 20,
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get posts from source.' ), array( 'status' => 500 ) );
		}

		// Get the body from our response.
		$posts = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $posts ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get posts from source.' ), array( 'status' => 500 ) );
		}
		$posts = json_decode( $posts, true );
		if ( ! is_array( $posts ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get posts from source.' ), array( 'status' => 500 ) );
		}
		return $posts;
	}

	/**
	 * Prepare Posts.
	 *
	 * @param array $posts The posts to install.
	 * @param array $image_library The image library to use.
	 * @return array The posts.
	 */
	public function prepare_posts( $posts, $image_library ) {
		if ( empty( $posts ) || ! is_array( $posts ) ) {
			return new WP_Error( 'no_posts', __( 'No posts to prepare.' ), array( 'status' => 500 ) );
		}
		$i = 0;
		$prepared_posts = array();
		foreach ( $posts as $post_data ) {
			$post_item = [
				'key' => $i,
				'title' => $post_data['title'],
				'categories' => $post_data['categories'],
				'tags' => $post_data['tags'],
				'image' => Image_Replacer::replace_images(
					$post_data['image'],
					$image_library,
					[],
					'',
					$i,
					[],
					false
				),
				'content' => Image_Replacer::replace_images(
					$post_data['content'],
					$image_library,
					[],
					'',
					$i,
					[],
				),
			];
			$prepared_posts[] = $post_item;
			$i++;
		}
		return $prepared_posts;
	}
	/**
	 * Install Posts Extras.
	 *
	 * @param array $posts The posts to install.
	 * @param array $image_library The image library to use.
	 * @return array The posts.
	 */
	public function install_posts_extras( $posts, $image_library ) {
		if ( empty( $posts ) ) {
			return new WP_Error( 'no_posts', __( 'No posts to install extras.' ), array( 'status' => 500 ) );
		}
		$new_posts = array();
		foreach ( $posts as $post_data ) {
			// Prepare Post content.
			$categories = $this->set_post_category_data( $post_data );
			$tags       = $this->set_post_tag_data( $post_data );
			$downloaded_image = array();
			if ( ! empty( $post_data['image'] ) ) {
				$image            = array(
					'url' => $post_data['image'],
					'id'  => 0,
				);
				if ( substr( $post_data['image'], 0, strlen( 'https://images.pexels.com' ) ) === 'https://images.pexels.com' ) {
					$image_data = $this->get_image_info( $image_library, $post_data['image'] );
					if ( $image_data ) {
						$alt                        = ! empty( $image_data['alt'] ) ? $image_data['alt'] : '';
						$image['filename']          = ! empty( $image_data['filename'] ) ? $image_data['filename'] : $this->create_filename_from_alt( $alt );
						$image['photographer']      = ! empty( $image_data['photographer'] ) ? $image_data['photographer'] : '';
						$image['photographer_url']  = ! empty( $image_data['photographer_url'] ) ? $image_data['photographer_url'] : '';
						$image['photograph_url']    = ! empty( $image_data['url'] ) ? $image_data['url'] : '';
						$image['alt']               = $alt;
						$image['title']             = __( 'Photo by', 'kadence-starter-templates' ) . ' ' . $image['photographer'];
					}
				}
				$downloaded_image = $this->import_image( $image );
			}
			$post_item = array(
				'post_title'   => ( isset( $post_data['title'] ) ? wp_strip_all_tags( $post_data['title'] ) : '' ),
				'post_content' => $this->process_page_content( $post_data['content'], $image_library ),
				'image' => ! empty( $downloaded_image['id'] ) ? $downloaded_image['id'] : '',
				'categories' => $categories,
				'tags' => $tags,
			);
			$new_posts[] = $post_item;
		}
		if ( empty( $new_posts ) ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}
		return $new_posts;
	}
	/**
	 * Install Posts.
	 *
	 * @param array $posts The posts to install.
	 * @return bool/WP_Error The posts.
	 */
	public function install_posts( $posts ) {
		if ( empty( $posts ) ) {
			return new WP_Error( 'no_posts', __( 'No posts to install.' ), array( 'status' => 500 ) );
		}
		foreach ( $posts as $post_data ) {
			$args = [
				'post_title'   => $post_data['post_title'],
				'post_content' => $post_data['post_content'],
				'post_status'  => 'publish',
				'post_type'    => 'post',
			];
			$post_id = wp_insert_post( wp_slash( $args ) );
			if ( is_wp_error( $post_id ) ) {
				return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
			}
			update_post_meta( $post_id, '_kadence_starter_templates_imported_post', true );
			// Set the post thumbnail.
			if ( ! empty( $post_data['image'] ) ) {
				set_post_thumbnail( $post_id, $post_data['image'] );
			}
			// Set the post categories.
			if ( ! empty( $post_data['categories'] ) ) {
				wp_set_post_terms( $post_id, $post_data['categories'], 'category' );
			}
			// Set the post tags.
			if ( ! empty( $post_data['tags'] ) ) {
				wp_set_post_terms( $post_id, $post_data['tags'], 'post_tag' );	
			}
		}
		return true;
	}
	/**
	 * Get Products.
	 *
	 * @param string $post_group The post group to get.
	 * @return array/WP_Error The posts.
	 */
	public function get_remote_products( $post_group = 'normal' ) {
		switch ( $post_group ) {
			case 'soap':
				$url = 'https://base.startertemplatecloud.com/g32/wp-json/kadence-starter-base/v1/products';
				break;
			default:
				$url = 'https://base.startertemplatecloud.com/wp-json/kadence-starter-base/v1/products';
				break;
		}
		// Get the response.
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 20,
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get products from source.' ), array( 'status' => 500 ) );
		}

		// Get the body from our response.
		$posts = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $posts ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get products from source.' ), array( 'status' => 500 ) );
		}
		$posts = json_decode( $posts, true );
		if ( ! is_array( $posts ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get products from source.' ), array( 'status' => 500 ) );
		}
		return $posts;
	}
	/**
	 * Prepare Products.
	 *
	 * @param array $products The products to prepare.
	 * @param array $image_library The image library to use.
	 * @return array/WP_Error The products.
	 */
	public function prepare_products( $products, $image_library ) {
		if ( empty( $products ) ) {
			return new WP_Error( 'no_products', __( 'No products to prepare.' ), array( 'status' => 500 ) );
		}
		$i = 0;
		$prepared_products = array();
		foreach ( $products as $product_data ) {
			if ( ! empty( $product_data['image'][0]['src'] ) ) {
				$product_data['image'][0]['src'] = Image_Replacer::replace_images(
					$product_data['image'][0]['src'],
					$image_library,
					[],
					'',
					$i,
					[],
				);
			}
			if ( ! empty( $product_data['gallery_images'] ) ) {
				// Replace the images in the gallery images.
				foreach ( $product_data['gallery_images'] as $key => $gallery_image ) {
					$product_data['gallery_images'][ $key ]['src'] = Image_Replacer::replace_images(
						$gallery_image['src'],
						$image_library,
						[],
						'',
						$i,
						[],
					);
				}
			}
			$prepared_products[] = $product_data;
			$i++;
		}
		return $prepared_products;
	}
	/**
	 * Install Products.
	 *
	 * @param array $products The products to install.
	 * @param array $image_library The image library to use.
	 * @return array/WP_Error The products.
	 */
	public function install_products( $products, $image_library ) {
		if ( empty( $products ) ) {
			return new WP_Error( 'no_products', __( 'No products to install.' ), array( 'status' => 500 ) );
		}
		$new_products = array();
		foreach ( $products as $product_data ) {
			if ( empty( $product_data['name'] ) ) {
				continue;
			}
			// Lets not duplicate products.
			$has_product = get_posts( [
				'post_type'  => 'product',
				'title' => $product_data['name'],
			] );
			if ( $has_product ) {
				$new_products[] = $has_product[0]->ID;
				continue;
			}
			$product = wc_get_product_object( $product_data['type'] ); // new WC_Product_Simple(); // Use WC_Product_Variable for variable products
			if ( is_wp_error( $product ) ) {
				return $product;
			}
			if ( 'external' === $product->get_type() ) {
				unset( $product_data['manage_stock'], $product_data['stock_status'], $product_data['backorders'], $product_data['low_stock_amount'] );
			}

			$product->set_name( $product_data['name'] );
			$product->set_status( 'publish' );  // or 'draft', 'pending', etc.
			$product->set_regular_price( $product_data['regular_price'] );
			if ( ! empty( $product_data['sale_price'] ) ) {
				$product->set_sale_price( $product_data['sale_price'] );
			}
			$product->set_description( $product_data['description'] );
			$product->set_short_description( $product_data['short_description'] );
			$this->set_image_data( $product, $product_data, $image_library );
			$this->set_category_data( $product, $product_data );
			$this->set_attribute_data( $product, $product_data );

			$product_id = $product->save();
			// Check for errors and handle them accordingly
			if ( is_wp_error( $product_id ) ) {
				return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
			}
			if ( 'external' === $product->get_type() ) {
				if ( ! empty( $product_data['product_url'] ) ) {
					update_post_meta( $product_id, '_product_url', esc_url_raw( $product_data['product_url'] ) );
				}
				// Update the button text.
				if ( ! empty( $product_data['button_text'] ) ) {
					update_post_meta( $product_id, '_button_text', sanitize_text_field( $product_data['button_text'] ) );
				}
			}
			if ( $product_data['type'] === 'variable' ) {
				$variations = $product_data['variations'];
				foreach ( $variations as $variation_data ) {
					$variation = new WC_Product_Variation();
					$variation->set_parent_id( $product_id );
					$variation->set_status( 'publish' );
					$variation->set_regular_price( $variation_data['display_regular_price'] );
					if ( ! empty( $variation_data['display_sale_price'] ) ) {
						$variation->set_sale_price( $variation_data['display_sale_price'] );
					}
					if ( ! empty( $variation_data['variation_description'] ) ) {
						$variation->set_description( $variation_data['variation_description'] );
					}
					$this->set_image_data( $variation, $variation_data, $image_library );
					$variation->set_attributes( $variation_data['attributes'] );
					$variation_id = $variation->save();
				}
			}
			update_post_meta( $product_id, '_kadence_starter_templates_imported_post', true );
			$new_products[] = $product_id;
		}
		if ( empty( $new_products ) ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}
		return $new_products;
	}
	/**
	 * Get Remote Events.
	 *
	 * @return array/WP_Error The events.
	 */
	public function get_remote_events() {
		$url = 'https://base.startertemplatecloud.com/wp-json/kadence-starter-base/v1/events';
		// Get the response.
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 20,
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get events from source.' ), array( 'status' => 500 ) );
		}

		// Get the body from our response.
		$posts = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $posts ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get events from source.' ), array( 'status' => 500 ) );
		}
		$posts = json_decode( $posts, true );
		if ( ! is_array( $posts ) ) {
			return new WP_Error( 'install_failed', __( 'Could not get events from source.' ), array( 'status' => 500 ) );
		}
		return $posts;
	}
	/**
	 * Prepare Events.
	 *
	 * @param array $events The events to prepare.
	 * @param array $image_library The image library to use.
	 * @return array/WP_Error The events.
	 */
	public function prepare_events( $events, $image_library ) {
		if ( empty( $events ) ) {
			return new WP_Error( 'no_events', __( 'No events to prepare.' ), array( 'status' => 500 ) );
		}
		$i = 0;
		$prepared_events = array();
		foreach ( $events as $event_data ) {
			if ( ! empty( $event_data['image'] ) ) {
				$event_data['image'] = Image_Replacer::replace_images(
					$event_data['image'],
					$image_library,
					[],
					'',
					$i,
					[],
				);
			}
			$prepared_events[] = $event_data;
			$i++;
		}
		return $prepared_events;
	}
	/**
	 * 
	 */
	public function install_donation_form( $form_data ) {
		if ( empty( $form_data ) ) {
			return new WP_Error( 'no_form_data', __( 'No form data to install.' ), array( 'status' => 500 ) );
		}
		if ( ! class_exists( '\Give' ) ) {
			return 'none';
		}
		$site_name = ( !empty( $form_data['companyName'] ) ? $form_data['companyName'] : 'GiveWP' );
		$form_args = [
			'enableDonationGoal' => true,
			'goalAmount' => 1000,
			'designId' => 'multi-step',
			'showHeading' => true,
			'showDescription' => true,
			'formTitle' => $site_name . ' - ' . __('Donation Form', 'kadence-starter-templates'),
			'heading' => $form_data['heading'],
			'description' => $form_data['description'],
			'formStatus' => DonationFormStatus::PUBLISHED(),
		];
		if ( !empty( $form_data['primaryColor'] ) ) {
			$form_args['primaryColor'] = $form_data['primaryColor'];
		}
		if ( ! empty( $form_data['type'] ) && $form_data['type'] === 'image' && ! empty( $form_data['image'] ) ) {
			$form_args['designSettingsImageUrl'] = $form_data['image'];
			$form_args['designSettingsImageStyle'] = 'above';
			$form_args['designSettingsImageAlt'] = $form_data['heading'];
		}
		if ( version_compare( GIVE_VERSION, '4.0.0', '<' ) ) {
			$form = DonationForm::create([
				'title' => $site_name . ' - ' . __('Donation Form', 'kadence-starter-templates'),
				'status' => DonationFormStatus::PUBLISHED(),
				'settings' => FormSettings::fromArray($form_args),
				'blocks' => (new GenerateDefaultDonationFormBlockCollection())(),
			]);
		} else {
			$campaign_args = [
				'type' => CampaignType::CORE(),
				'title' => $site_name . ' - ' . __('Donation Campaign', 'kadence-starter-templates'),
				'shortDescription' => $form_data['description'],
				'longDescription' => '',
				'logo' => '',
				'image' => '',
				'primaryColor' => '#0b72d9',
            	'secondaryColor' => '#27ae60',
				'goal' => 1000,
            	'goalType' => CampaignGoalType::AMOUNT(),
            	'status' => CampaignStatus::ACTIVE(),
			];
			
			if ( ! empty( $form_data['image'] ) ) {
				$campaign_args['image'] = $form_data['image'];
			}
			if ( !empty( $form_data['primaryColor'] ) ) {
				$campaign_args['primaryColor'] = $form_data['primaryColor'];
			}
			$campaign = Campaign::create( $campaign_args );
			$form = DonationForm::find($campaign->defaultFormId);
			$form->title = $site_name . ' - ' . __('Donation Form', 'kadence-starter-templates');
            $form->status = DonationFormStatus::PUBLISHED();
			$form->settings = FormSettings::fromArray($form_args);
			$form->save();
		}
		if ( ! isset( $form->id ) ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}
		update_post_meta( $form->id, '_kadence_starter_templates_imported_post', true );
		return $form->id;
	}
	/**
	 * Update Donation Form Primary Color.
	 *
	 * @param string $primary_color The primary color to update.
	 * @param string $form_id The form id to update.
	 *
	 */
	public function update_donation_form_primary_color( $primary_color, $form_id ) {
		if ( empty( $primary_color ) || empty( $form_id ) ) {
			return;
		}
		if ( ! class_exists( '\Give' ) ) {
			return;
		}
		$form = DonationForm::find( $form_id );
		if ( ! $form ) {
			return;
		}
		$form->settings->primaryColor = $primary_color;
		$form->save();
	}
	/**
	 * Install Give Form.
	 *
	 * @param array $image_library The image library to use.
	 * @return string/WP_Error The form.
	 */
	public function install_give_form( $ai_content, $image_library, $site_name = '', $primary_color = '' ) {
		if ( ! class_exists( '\Give' ) ) {
			return 'none';
		}
		$form_data = json_encode(['heading' => 'Write a short headline', 'description' => 'Use this paragraph section to get your website visitors to know you. Consider writing about you or your organization, the products or services you offer, or why you exist. Keep a consistent communication style.'], true);
		$form_data = Content_Replacer::replace_content(
			$form_data,
			$ai_content,
			['donation-form'],
			'donate',
			1,
			false,
			[],
		);
		$form_data = json_decode($form_data, true);
		$form_data['companyName'] = $site_name;
		if ( ! empty( $primary_color ) ) {
			$form_data['primaryColor'] = $primary_color;
		}
		// $image = Image_Replacer::replace_images(
		// 	'https://patterns.startertemplatecloud.com/wp-content/uploads/2023/02/Example-A-Roll-Image-1024x793.jpg',
		// 	$image_library,
		// 	[],
		// 	'',
		// 	3,
		// 	[],
		// 	false
		// );
		// $image_data = $this->get_image_info( $image_library, $image );
		// if ( $image_data ) {
		// 	$alt = ! empty( $image_data['alt'] ) ? $image_data['alt'] : 'Donation Form';
		// }
		return $this->install_donation_form( $form_data );
	}
	/**
	 * Install Events.
	 *
	 * @param array $events The events to install.
	 * @param array $image_library The image library to use.
	 * @return array/WP_Error The events.
	 */
	public function install_events( $events, $image_library ) {
		if ( empty( $events ) ) {
			return new WP_Error( 'no_events', __( 'No events to install.' ), array( 'status' => 500 ) );
		}
		if ( ! class_exists( '\Tribe__Events__Main' ) ) {
			return new WP_Error( 'no_events', __( 'Tribe Events is not installed.' ), array( 'status' => 500 ) );
		}
		$new_events    = array();
		$variation = 1;
		foreach ( $events as $event_data ) {
			// Lets not duplicate products.
			$has_event = get_posts( [
				'post_type'  => 'tribe_events',
				'title' => $event_data['title'],
			] );
			if ( $has_event ) {
				$new_events[] = $has_event[0]->ID;
				continue;
			}
			// Prepare Post content.
			$category_ids  = $this->set_taxonomy_data( $event_data, 'categories', 'tribe_events_cat' );
			$venue_ids     = $this->set_event_venue_data( $event_data );
			$organizer_ids = $this->set_event_organizers_data( $event_data );
			$downloaded_image = array();
			if ( ! empty( $event_data['image'] ) ) {
				$image            = array(
					'url' => $event_data['image'],
					'id'  => 0,
				);
				if ( substr( $event_data['image'], 0, strlen( 'https://images.pexels.com' ) ) === 'https://images.pexels.com' ) {
					$image_data = $this->get_image_info( $image_library, $event_data['image'] );
					if ( $image_data ) {
						$alt                        = ! empty( $image_data['alt'] ) ? $image_data['alt'] : '';
						$image['filename']          = ! empty( $image_data['filename'] ) ? $image_data['filename'] : $this->create_filename_from_alt( $alt );
						$image['photographer']      = ! empty( $image_data['photographer'] ) ? $image_data['photographer'] : '';
						$image['photographer_url']  = ! empty( $image_data['photographer_url'] ) ? $image_data['photographer_url'] : '';
						$image['photograph_url']    = ! empty( $image_data['url'] ) ? $image_data['url'] : '';
						$image['alt']               = $alt;
						$image['title']             = __( 'Photo by', 'kadence-starter-templates' ) . ' ' . $image['photographer'];
					}
				}
				$downloaded_image = $this->import_image( $image );
			}
			$date       = strtotime( '+' . (string)$variation .' months' );
			$start_date = date( 'Y-m-d', $date );
			$event_item = array(
				'post_status'  => 'publish',
				'post_title'   => ( isset( $event_data['title'] ) ? wp_strip_all_tags( $event_data['title'] ) : '' ),
				'post_content' => $this->process_page_content( $event_data['content'], $image_library ),
				'EventStartDate' => $start_date,
				'EventStartMeridian' => 'pm',
				'EventStartMinute' => '00',
				'EventStartHour' => '01',
				'EventEndMeridian' => 'pm',
				'EventEndMinute' => '00',
				'EventEndHour' => '05',
				'EventEndDate' => $start_date,
				'FeaturedImage' => ! empty( $downloaded_image['id'] ) ? $downloaded_image['id'] : '',
				'EventCost' => '0',
				'venue' => isset( $event_data['venues'][0] ) ? $event_data['venues'][0] : '',
			);
			$event_id = tribe_create_event( $event_item );
			// Check for errors and handle them accordingly
			if ( is_wp_error( $event_id ) ) {
				return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
			}
			update_post_meta( $event_id, '_kadence_starter_templates_imported_post', true );
			foreach ( $organizer_ids as $organizer_id ) {
				add_post_meta( $event_id, '_EventOrganizerID', $organizer_id );
			}
			wp_set_post_terms( $event_id, $category_ids, 'tribe_events_cat' );
			$new_events[] = $event_id;
			$variation++;
		}
		if ( empty( $new_events ) ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}
		return $new_events;
	}
	/**
	 * Install Course.
	 *
	 * @return array/WP_Error The course.
	 */
	public function install_course() {
		if ( ! class_exists( '\Learndash_Admin_Import_Export' ) ) {
			return new WP_Error( 'no_course', __( 'No LearnDash' ), array( 'status' => 500 ) );
		}
		$has_course = get_posts( [
			'post_type'  => 'sfwd-courses',
			'title' => 'Getting Started with LearnDash',
		] );
		if ( $has_course ) {
			return $has_course[0]->ID;
		}
		$user_id = get_current_user_id();
		$options = json_decode( '{"post_types":["sfwd-courses","sfwd-lessons","sfwd-topic","sfwd-quiz","sfwd-question","groups","sfwd-assignment","sfwd-certificates"],"post_type_settings":["sfwd-courses","sfwd-lessons","sfwd-topic","sfwd-quiz","sfwd-question","groups","sfwd-assignment","sfwd-certificates"],"users":[],"other":["settings"],"info":{"ld_version":"4.15.2","wp_version":"6.5.5","db_prefix":"wp_","is_multisite":true,"blog_id":1,"home_url":"https:\/\/base.startertemplatecloud.com"}}', true );
		$ld_file_handler = new \Learndash_Admin_Import_File_Handler();
		$ld_file_handler->set_working_directory( KADENCE_STARTER_TEMPLATES_PATH . 'assets/ld-demo/learndash-demo' );
		$ld_importers_mapper = new \Learndash_Admin_Import_Mapper( $ld_file_handler,
		new \Learndash_Import_Export_Logger( \Learndash_Import_Export_Logger::$log_type_import ) );
		$course_ids = array();
		foreach ( $ld_importers_mapper->map( $options, $user_id ) as $importer ) {
			$importer->import_data();
			\Learndash_Admin_Import::clear_wpdb_query_cache();

			/**
			 * Fires after an importer had been processed.
			 *
			 * @param Learndash_Admin_Import $importer The Learndash_Admin_Import instance.
			 *
			 * @since 4.3.0
			 */
			do_action( 'learndash_import_importer_processed', $importer );

			$new_post = $importer->get_new_post_id_by_old_post_id( 7214 );
			if ( $new_post && ! in_array( $new_post, $course_ids ) ) {
				$course_ids[] = $new_post;
			}
		}
		( new \Learndash_Admin_Import_Associations_Handler() )->handle();
		$new_courses = true;
		if ( empty( $new_courses ) ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}
		return $new_courses;
	}
	/**
	 * Install Learndash Settings.
	 *
	 * @return null;
	 */
	public function setup_learndash() {
		if ( ! class_exists( '\LearnDash_Settings_Section' ) ) {
			return;
		}
		// LearnDash Theme Settings.
		$instance = \LearnDash_Settings_Section::get_section_instance( 'LearnDash_Settings_Theme_LD30' );
		$instance::set_setting( 'color_primary', 'var(--global-palette1, #0073aa)' );
		$instance::set_setting( 'color_secondary', 'var(--global-palette2, #215387)' );
		// Enable login and registration.
		$instance::set_setting( 'login_mode_enabled', 'yes' );
		// Focused mode.
		$instance::set_setting( 'focus_mode_enabled', 'yes' );

		// LearnDash Page Settings.
		$ld_page_instance = \LearnDash_Settings_Section::get_section_instance( 'LearnDash_Settings_Section_Registration_Pages' );
		$success_id = $ld_page_instance::get_setting( 'registration_success', '' );
		if ( ! empty( $success_id ) ) {
			update_post_meta( $success_id, '_kad_post_layout', 'narrow' );
			update_post_meta( $success_id, '_kad_post_vertical_padding', 'show' );
		} else {
			$success_id = wp_insert_post(
				array(
					'post_title'  => 'Registration Success',
					'post_type'   => 'page',
					'post_status' => 'publish',
				)
			);
			if ( ! is_wp_error( $success_id ) ) {
				update_post_meta( $success_id, '_kadence_starter_templates_imported_post', true );
				update_post_meta( $success_id, '_kad_post_layout', 'narrow' );
				update_post_meta( $success_id, '_kad_post_vertical_padding', 'show' );
				$ld_page_instance::set_setting( 'registration_success', $success_id );
			}
		}
		$registration_id = $ld_page_instance::get_setting( 'registration', '' );
		if ( ! empty( $registration_id ) ) {
			update_post_meta( $registration_id, '_kad_post_layout', 'narrow' );
			update_post_meta( $registration_id, '_kad_post_vertical_padding', 'show' );
		} else {
			$registration_id = wp_insert_post(
				array(
					'post_title'  => 'Registration',
					'post_type'   => 'page',
					'post_content' => '<!-- wp:learndash/ld-registration /-->',
					'post_status' => 'publish',
				)
			);
			if ( ! is_wp_error( $registration_id ) ) {
				update_post_meta( $registration_id, '_kadence_starter_templates_imported_post', true );
				update_post_meta( $registration_id, '_kad_post_layout', 'narrow' );
				update_post_meta( $registration_id, '_kad_post_vertical_padding', 'show' );
				$ld_page_instance::set_setting( 'registration', $registration_id );
			}
		}
		$reset_id = $ld_page_instance::get_setting( 'reset_password', '' );
		if ( ! empty( $reset_id ) ) {
			update_post_meta( $reset_id, '_kad_post_layout', 'narrow' );
			update_post_meta( $reset_id, '_kad_post_vertical_padding', 'show' );
		} else {
			$reset_id = wp_insert_post(
				array(
					'post_title'  => 'Reset Password',
					'post_type'   => 'page',
					'post_content' => '<!-- wp:learndash/ld-reset-password {"width":""} /-->',
					'post_status' => 'publish',
				)
			);
			if ( ! is_wp_error( $reset_id ) ) {
				update_post_meta( $reset_id, '_kadence_starter_templates_imported_post', true );
				update_post_meta( $reset_id, '_kad_post_layout', 'narrow' );
				update_post_meta( $reset_id, '_kad_post_vertical_padding', 'show' );
				$ld_page_instance::set_setting( 'reset_password', $reset_id );
			}
		}

		// Update Course Layout.
		set_theme_mod( 'sfwd-courses_layout', 'narrow' );
		set_theme_mod( 'sfwd-courses_content_style', 'unboxed' );
		// Update Lesson Layout.
		set_theme_mod( 'sfwd-lessons_layout', 'narrow' );
		set_theme_mod( 'sfwd-lessons_content_style', 'unboxed' );
		// Make sure anyone can register.
		update_option( 'users_can_register', 1 );

		return;
	}
	/**
	 * Update block ID in content with new ID
	 */
	private function update_block_ids($content, $id_map) {
		$blocks = parse_blocks($content);

		foreach ($blocks as &$block) {
			if ( in_array( $block['blockName'], $this->kadence_cpt_blocks )
				&& !empty($block['attrs']['id'])
				&& isset($id_map[$block['attrs']['id']])) {
				$block['attrs']['id'] = $id_map[$block['attrs']['id']];
			}

			if (!empty($block['innerBlocks'])) {
				$inner_content = serialize_blocks($block['innerBlocks']);
				$updated_inner_content = $this->update_block_ids($inner_content, $id_map);
				$block['innerBlocks'] = parse_blocks($updated_inner_content);
			}
		}

		return serialize_blocks($blocks);
	}
	/**
	 * Install CPT.
	 *
	 * @param array $cpt_data The cpt data.
	 * @param string $style The style.
	 * @return int The cpt id.
	 */
	public function install_single_cpt( $cpt_data, $id_map = [], $style = 'light' ) {
		// Check if the post already exists.
		$title       = ! empty( $style ) && 'light' !== $style ? $cpt_data['post_title'] . ' ' . ucfirst( $style ) : $cpt_data['post_title'];
		$post_exists = get_posts( [
			'post_type' => $cpt_data['post_type'],
			'title' => $title,
		] );
		if ( $post_exists ) {
			return $post_exists[0]->ID;
		}
		$temp_content = $cpt_data['post_content'];
		$new_post_id  = wp_insert_post([
			'post_type' => $cpt_data['post_type'],
			'post_title' => $title,
			'post_content' => '',
			'post_status' => 'publish',
		], true);

		if ( ! is_wp_error($new_post_id) ) {
			if ( ! empty( $cpt_data['meta'])) {
				foreach ($cpt_data['meta'] as $meta_key => $meta_values) {
					foreach ($meta_values as $meta_value) {
						if ( ! empty( $style ) && 'light' !== $style ) {
							$meta_value = $this->cpt_switch_meta_color( $meta_value, $meta_key, $style );
						}
						add_post_meta($new_post_id, $meta_key, $meta_value);
					}
				}
			}
			if ( ! empty( $id_map ) ) {
				$temp_content = $this->update_block_ids($temp_content, $id_map);
			}
			if ( ! empty( $style ) && 'light' !== $style ) {
				$temp_content = $this->cpt_switch_color( $temp_content, $style );
			}
			wp_update_post(array(
				'ID' => $new_post_id,
				'post_content' => $temp_content
			));

			return $new_post_id;
		}
		return false;
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param string $content The content to search.
	 * @param string $style The style to search for.
	 * @return string The content with the style replaced.
	 */
	public function cpt_switch_meta_color( $meta_value, $meta_key, $style ) {
		$temp_meta_value = wp_json_encode( $meta_value );
		if ( $style === 'highlight' ) {
			$temp_meta_value = str_replace( 'palette1', 'placeholder-kb-pal9', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette2', 'placeholder-kb-pal8', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette3', 'placeholder-kb-pal9', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette4', 'placeholder-kb-pal9', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette5', 'placeholder-kb-pal8', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette6', 'placeholder-kb-pal7', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette7', 'placeholder-kb-pal2', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette8', 'placeholder-kb-pal2', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette9', 'placeholder-kb-pal1', $temp_meta_value );
		} else {
			$temp_meta_value = str_replace( 'palette3', 'placeholder-kb-pal9', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette4', 'placeholder-kb-pal8', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette5', 'placeholder-kb-pal7', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette6', 'placeholder-kb-pal7', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette7', 'placeholder-kb-pal3', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette8', 'placeholder-kb-pal3', $temp_meta_value );
			$temp_meta_value = str_replace( 'palette9', 'placeholder-kb-pal4', $temp_meta_value );
		}
		$temp_meta_value = str_replace( 'placeholder-kb-pal1', 'palette1', $temp_meta_value );
		$temp_meta_value = str_replace( 'placeholder-kb-pal2', 'palette2', $temp_meta_value );
		$temp_meta_value = str_replace( 'placeholder-kb-pal3', 'palette3', $temp_meta_value );
		$temp_meta_value = str_replace( 'placeholder-kb-pal4', 'palette4', $temp_meta_value );
		$temp_meta_value = str_replace( 'placeholder-kb-pal5', 'palette5', $temp_meta_value );
		$temp_meta_value = str_replace( 'placeholder-kb-pal6', 'palette6', $temp_meta_value );
		$temp_meta_value = str_replace( 'placeholder-kb-pal7', 'palette7', $temp_meta_value );
		$temp_meta_value = str_replace( 'placeholder-kb-pal8', 'palette8', $temp_meta_value );
		$temp_meta_value = str_replace( 'placeholder-kb-pal9', 'palette9', $temp_meta_value );

		return json_decode( $temp_meta_value, true );
	}
	/**
	 * Retrieves a collection of objects.
	 */
	public function cpt_switch_color( $content, $style ) {
		$content = str_replace( 'Logo-ploaceholder.png', 'Logo-ploaceholder-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-1.png', 'Logo-ploaceholder-1-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-2.png', 'Logo-ploaceholder-2-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-3.png', 'Logo-ploaceholder-3-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-4.png', 'Logo-ploaceholder-4-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-5.png', 'Logo-ploaceholder-5-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-6.png', 'Logo-ploaceholder-6-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-7.png', 'Logo-ploaceholder-7-white.png', $content );
		$content = str_replace( 'Logo-ploaceholder-8.png', 'Logo-ploaceholder-8-white.png', $content );

		$content = str_replace( 'logo-placeholder.png', 'logo-placeholder-white.png', $content );
		$content = str_replace( 'logo-placeholder-1.png', 'logo-placeholder-1-white.png', $content );
		$content = str_replace( 'logo-placeholder-2.png', 'logo-placeholder-2-white.png', $content );
		$content = str_replace( 'logo-placeholder-3.png', 'logo-placeholder-3-white.png', $content );
		$content = str_replace( 'logo-placeholder-4.png', 'logo-placeholder-4-white.png', $content );
		$content = str_replace( 'logo-placeholder-5.png', 'logo-placeholder-5-white.png', $content );
		$content = str_replace( 'logo-placeholder-6.png', 'logo-placeholder-6-white.png', $content );
		$content = str_replace( 'logo-placeholder-7.png', 'logo-placeholder-7-white.png', $content );
		$content = str_replace( 'logo-placeholder-8.png', 'logo-placeholder-8-white.png', $content );
		$content = str_replace( 'logo-placeholder-9.png', 'logo-placeholder-9-white.png', $content );
		$content = str_replace( 'logo-placeholder-10.png', 'logo-placeholder-10-white.png', $content );
		
		if ( $style === 'highlight' ) {
			$form_content = $this->get_string_inbetween( $content, '"submit":[{', ']}', 'wp:kadence/form' );
			if ( $form_content ) {
				$form_content_org = $form_content;
				$form_content     = str_replace( '"color":""', '"color":"placeholder-kb-pal9"', $form_content );
				$form_content     = str_replace( '"background":""', '"background":"placeholder-kb-pal3"', $form_content );
				$form_content     = str_replace( '"colorHover":""', '"colorHover":"placeholder-kb-pal9"', $form_content );
				$form_content     = str_replace( '"backgroundHover":""', '"backgroundHover":"placeholder-kb-pal4"', $form_content );
				$content          = str_replace( $form_content_org, $form_content, $content );
			}
			$content = str_replace( '"inheritStyles":"inherit"', '"color":"placeholder-kb-pal9","background":"placeholder-kb-pal3","colorHover":"placeholder-kb-pal9","backgroundHover":"placeholder-kb-pal4","inheritStyles":"inherit"', $content );

			$content = str_replace( 'has-theme-palette-1', 'placeholder-kb-class9', $content );
			$content = str_replace( 'has-theme-palette-2', 'placeholder-kb-class8', $content );
			$content = str_replace( 'has-theme-palette-3', 'placeholder-kb-class9', $content );
			$content = str_replace( 'has-theme-palette-4', 'placeholder-kb-class9', $content );
			$content = str_replace( 'has-theme-palette-5', 'placeholder-kb-class8', $content );
			$content = str_replace( 'has-theme-palette-6', 'placeholder-kb-class7', $content );
			$content = str_replace( 'has-theme-palette-7', 'placeholder-kb-class2', $content );
			$content = str_replace( 'has-theme-palette-8', 'placeholder-kb-class2', $content );
			$content = str_replace( 'has-theme-palette-9', 'placeholder-kb-class1', $content );

			$content = str_replace( 'theme-palette1', 'placeholder-class-pal9', $content );
			$content = str_replace( 'theme-palette2', 'placeholder-class-pal8', $content );
			$content = str_replace( 'theme-palette3', 'placeholder-class-pal9', $content );
			$content = str_replace( 'theme-palette4', 'placeholder-class-pal9', $content );
			$content = str_replace( 'theme-palette5', 'placeholder-class-pal8', $content );
			$content = str_replace( 'theme-palette6', 'placeholder-class-pal7', $content );
			$content = str_replace( 'theme-palette7', 'placeholder-class-pal2', $content );
			$content = str_replace( 'theme-palette8', 'placeholder-class-pal2', $content );
			$content = str_replace( 'theme-palette9', 'placeholder-class-pal1', $content );

			$content = str_replace( 'palette1', 'placeholder-kb-pal9', $content );
			$content = str_replace( 'palette2', 'placeholder-kb-pal8', $content );
			$content = str_replace( 'palette3', 'placeholder-kb-pal9', $content );
			$content = str_replace( 'palette4', 'placeholder-kb-pal9', $content );
			$content = str_replace( 'palette5', 'placeholder-kb-pal8', $content );
			$content = str_replace( 'palette6', 'placeholder-kb-pal7', $content );
			$content = str_replace( 'palette7', 'placeholder-kb-pal2', $content );
			$content = str_replace( 'palette8', 'placeholder-kb-pal2', $content );
			$content = str_replace( 'palette9', 'placeholder-kb-pal1', $content );

		} else {
			$white_text_content = $this->get_string_inbetween_when( $content, '<!-- wp:kadence/column', 'kt-inside-inner-col', 'kb-pattern-light-color', 0 );
			if ( $white_text_content ) {
				$white_text_content_org = $white_text_content;
				$white_text_content     = str_replace( '"textColor":"palette9"', '"textColor":"placeholder-kb-pal9"', $white_text_content );
				$white_text_content     = str_replace( '"linkColor":"palette9"', '"linkColor":"placeholder-kb-pal9"', $white_text_content );
				$white_text_content     = str_replace( '"linkHoverColor":"palette9"', '"linkHoverColor":"placeholder-kb-pal9"', $white_text_content );
				$content                = str_replace( $white_text_content_org, $white_text_content, $content );
			}
			$content = str_replace( 'has-theme-palette-3', 'placeholder-kb-class9', $content );
			$content = str_replace( 'has-theme-palette-4', 'placeholder-kb-class8', $content );
			$content = str_replace( 'has-theme-palette-5', 'placeholder-kb-class7', $content );
			$content = str_replace( 'has-theme-palette-6', 'placeholder-kb-class7', $content );
			$content = str_replace( 'has-theme-palette-7', 'placeholder-kb-class3', $content );
			$content = str_replace( 'has-theme-palette-8', 'placeholder-kb-class3', $content );
			$content = str_replace( 'has-theme-palette-9', 'placeholder-kb-class4', $content );

			$content = str_replace( 'theme-palette3', 'placeholder-class-pal9', $content );
			$content = str_replace( 'theme-palette4', 'placeholder-class-pal8', $content );
			$content = str_replace( 'theme-palette5', 'placeholder-class-pal7', $content );
			$content = str_replace( 'theme-palette6', 'placeholder-class-pal7', $content );
			$content = str_replace( 'theme-palette7', 'placeholder-class-pal3', $content );
			$content = str_replace( 'theme-palette8', 'placeholder-class-pal3', $content );
			$content = str_replace( 'theme-palette9', 'placeholder-class-pal4', $content );

			$content = str_replace( 'palette3', 'placeholder-kb-pal9', $content );
			$content = str_replace( 'palette4', 'placeholder-kb-pal8', $content );
			$content = str_replace( 'palette5', 'placeholder-kb-pal7', $content );
			$content = str_replace( 'palette6', 'placeholder-kb-pal7', $content );
			$content = str_replace( 'palette7', 'placeholder-kb-pal3', $content );
			$content = str_replace( 'palette8', 'placeholder-kb-pal3', $content );
			$content = str_replace( 'palette9', 'placeholder-kb-pal4', $content );
		}
		$content = str_replace( 'placeholder-kb-class1', 'has-theme-palette-1', $content );
		$content = str_replace( 'placeholder-kb-class2', 'has-theme-palette-2', $content );
		$content = str_replace( 'placeholder-kb-class3', 'has-theme-palette-3', $content );
		$content = str_replace( 'placeholder-kb-class4', 'has-theme-palette-4', $content );
		$content = str_replace( 'placeholder-kb-class5', 'has-theme-palette-5', $content );
		$content = str_replace( 'placeholder-kb-class6', 'has-theme-palette-6', $content );
		$content = str_replace( 'placeholder-kb-class7', 'has-theme-palette-7', $content );
		$content = str_replace( 'placeholder-kb-class8', 'has-theme-palette-8', $content );
		$content = str_replace( 'placeholder-kb-class9', 'has-theme-palette-9', $content );

		$content = str_replace( 'placeholder-class-pal1', 'theme-palette1', $content );
		$content = str_replace( 'placeholder-class-pal2', 'theme-palette2', $content );
		$content = str_replace( 'placeholder-class-pal3', 'theme-palette3', $content );
		$content = str_replace( 'placeholder-class-pal4', 'theme-palette4', $content );
		$content = str_replace( 'placeholder-class-pal5', 'theme-palette5', $content );
		$content = str_replace( 'placeholder-class-pal6', 'theme-palette6', $content );
		$content = str_replace( 'placeholder-class-pal7', 'theme-palette7', $content );
		$content = str_replace( 'placeholder-class-pal8', 'theme-palette8', $content );
		$content = str_replace( 'placeholder-class-pal9', 'theme-palette9', $content );

		$content = str_replace( 'placeholder-kb-pal1', 'palette1', $content );
		$content = str_replace( 'placeholder-kb-pal2', 'palette2', $content );
		$content = str_replace( 'placeholder-kb-pal3', 'palette3', $content );
		$content = str_replace( 'placeholder-kb-pal4', 'palette4', $content );
		$content = str_replace( 'placeholder-kb-pal5', 'palette5', $content );
		$content = str_replace( 'placeholder-kb-pal6', 'palette6', $content );
		$content = str_replace( 'placeholder-kb-pal7', 'palette7', $content );
		$content = str_replace( 'placeholder-kb-pal8', 'palette8', $content );
		$content = str_replace( 'placeholder-kb-pal9', 'palette9', $content );
		return $content;
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param string $string The string to search.
	 * @param string $start The start of the string.
	 * @param string $end The end of the string.
	 * @param string $verify The string to verify.
	 * @return string The string in between the start and end.
	 */
	public function get_string_inbetween( $string, $start, $end, $verify ) {
		if ( strpos( $string, $verify ) == 0 ) {
			return '';
		}
		$ini = strpos( $string, $start );
		if ( $ini == 0 ) {
			return '';
		}
		$ini += strlen( $start );
		$len  = strpos( $string, $end, $ini ) - $ini;
		return substr( $string, $ini, $len );
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param string $string The string to search.
	 * @param string $start The start of the string.
	 * @param string $end The end of the string.
	 * @param string $verify The string to verify.
	 * @param int    $from The position to start searching from.
	 * @return string The string in between the start and end.
	 */
	public function get_string_inbetween_when( $string, $start, $end, $verify, $from ) {
		$ini = strpos( $string, $start, $from );
		if ( $ini == 0 ) {
			return '';
		}
		$ini       += strlen( $start );
		$len        = strpos( $string, $end, $ini ) - $ini;
		$sub_string = substr( $string, $ini, $len );
		if ( strpos( $sub_string, $verify ) == 0 ) {
			return $this->get_string_inbetween_when( $string, $start, $end, $verify, $ini );
		}
		return $sub_string;
	}
	/**
	 * Process Form Replace.
	 *
	 * @param int $old_id The old ID.
	 * @param int $new_id The new ID.
	 * @return string The processed content.
	 */
	public function process_form_replace( $old_id, $new_id, $content ) {
		$old_id = absint( $old_id );
		$new_id = absint( $new_id );
		$content = str_replace( '"id":' . $old_id, '"id":' . $new_id, $content );
		return $content;
	}
	/**
	 * Process Page Content for CPTs.
	 *
	 * @param string $content The content.
	 * @param string $image_library The image library.
	 * @return string The processed content.
	 */
	public function process_pages( $pages ) {
		if ( empty( $pages ) || ! is_array( $pages ) ) {
			return new WP_Error( 'no_pages', __( 'No pages to process.' ), array( 'status' => 500 ) );
		}
		// Loop through the pages and install every cpt in each row and replace the ID with the new ID.
		foreach ( $pages as $key => $page_data ) {
			// Loop through the rows and install every cpt.
			foreach ( $page_data['rows'] as $row_key => $row_data ) {
				if ( isset( $row_data['pattern_cpt_blocks']['kadence/advanced-form'] ) ) {
					$style = ( ! empty( $row_data['pattern_style'] ) ) ? $row_data['pattern_style'] : 'light';
					foreach ( $row_data['pattern_cpt_blocks']['kadence/advanced-form'] as $cpt_key => $cpt_data ) {
						$old_id = $cpt_data['ID'];
						$id_map = [];
						$id = $this->install_single_cpt( $cpt_data, $id_map, $style);
						if ( $id ) {
							$id_map[$old_id] = $id;
							$pages[$key]['rows'][$row_key]['pattern_content'] = $this->update_block_ids( $pages[$key]['rows'][$row_key]['pattern_content'], $id_map );
						}
					}
				}
			}
		}
		return $pages;
	}
	/**
	 * Install Block CPT.
	 *
	 * @param array $block_cpt Block CPT.
	 * @return array/WP_Error The block cpt.
	 */
	public function install_block_cpt( $block_cpt ) {
		if ( empty( $block_cpt ) ) {
			return new WP_Error( 'no_block_cpt', __( 'No block cpt to install.' ), array( 'status' => 500 ) );
		}
		$block_id = wp_insert_post( $block_cpt );
		if ( is_wp_error( $block_id ) ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}
		return $block_id;
	}
	/**
	 * Install Block CPTs.
	 *
	 * @param array $block_cpts Block CPTs.
	 * @return array/WP_Error The block cpts.
	 */
	public function install_cpts( $block_cpts ) {
		if ( empty( $block_cpts ) ) {
			return new WP_Error( 'no_block_cpts', __( 'No block cpts to install.' ), array( 'status' => 500 ) );
		}
		$block_ids = array();
		foreach ( $block_cpts as $block_cpt ) {
			$block_ids[] = $this->install_block_cpt( $block_cpt );
		}
		if ( empty( $block_ids ) ) {
			return new WP_Error( 'install_failed', __( 'Install failed.' ), array( 'status' => 500 ) );
		}
		return $block_ids;
	}
	
	/**
	 * Convert raw image URLs to IDs and set.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $data    Item data.
	 */
	protected function set_image_data( &$product, $data, $image_library ) {
		// Image URLs need converting to IDs before inserting.
		if ( ! empty( $data['image'][0]['src'] ) ) {
			$image            = array(
				'url' => $data['image'][0]['src'],
				'id'  => 0,
			);
			if ( substr( $image['url'], 0, strlen( 'https://images.pexels.com' ) ) === 'https://images.pexels.com' ) {
				$image_data = $this->get_image_info( $image_library, $image['url'] );
				if ( $image_data ) {
					$alt                        = ! empty( $image_data['alt'] ) ? $image_data['alt'] : '';
					$image['filename']          = ! empty( $image_data['filename'] ) ? $image_data['filename'] : $this->create_filename_from_alt( $alt );
					$image['photographer']      = ! empty( $image_data['photographer'] ) ? $image_data['photographer'] : '';
					$image['photographer_url']  = ! empty( $image_data['photographer_url'] ) ? $image_data['photographer_url'] : '';
					$image['photograph_url']    = ! empty( $image_data['url'] ) ? $image_data['url'] : '';
					$image['alt']               = $alt;
					$image['title']             = __( 'Photo by', 'kadence-starter-templates' ) . ' ' . $image['photographer'];
				}
			}
			$downloaded_image = $this->import_image( $image );
			if ( ! empty( $downloaded_image['id'] ) ) {
				$product->set_image_id( $downloaded_image['id'] );
			}
		}

		// Gallery image URLs need converting to IDs before inserting.
		if ( ! empty( $data['gallery_images'] ) ) {
			$gallery_image_ids = array();

			foreach ( $data['gallery_images'] as $single_image ) {
				if ( ! empty( $single_image['src'] ) ) {
					$image            = array(
						'url' => $single_image['src'],
						'id'  => 0,
					);
					if ( substr( $image['url'], 0, strlen( 'https://images.pexels.com' ) ) === 'https://images.pexels.com' ) {
						$image_data = $this->get_image_info( $image_library, $image['url'] );
						if ( $image_data ) {
							$alt                        = ! empty( $image_data['alt'] ) ? $image_data['alt'] : '';
							$image['filename']          = ! empty( $image_data['filename'] ) ? $image_data['filename'] : $this->create_filename_from_alt( $alt );
							$image['photographer']      = ! empty( $image_data['photographer'] ) ? $image_data['photographer'] : '';
							$image['photographer_url']  = ! empty( $image_data['photographer_url'] ) ? $image_data['photographer_url'] : '';
							$image['photograph_url']    = ! empty( $image_data['url'] ) ? $image_data['url'] : '';
							$image['alt']               = $alt;
							$image['title']             = __( 'Photo by', 'kadence-starter-templates' ) . ' ' . $image['photographer'];
						}
					}
					$downloaded_image = $this->import_image( $image );
					if ( ! empty( $downloaded_image['id'] ) ) {
						$gallery_image_ids[] = $downloaded_image['id'];
					}
				}
			}
			if ( ! empty( $gallery_image_ids ) ) {
				$product->set_gallery_image_ids( $gallery_image_ids );
			}
		}
	}
	/**
	 * Constructs a consistent Token header.
	 *
	 * @param array $args An array of arguments to include in the encoded header.
	 *
	 * @return string The base64 encoded string.
	 */
	public function get_token_header( $args = array() ) {
		$this->get_license_keys();
		$site_name    = get_bloginfo( 'name' );
		$defaults = [
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
	 * Set the venue and tag ids.
	 *
	 * @param array      $data    Item data.
	 */
	protected function set_event_venue_data( $data ) {
		$venue_ids = array();
		// Set the categories.
		if ( ! empty( $data['venues'] ) ) {
			foreach ( $data['venues'] as $venue ) {
				// Lets not duplicate venues.
				$has_venue = get_posts( [
					'post_type'  => 'tribe_venue',
					'title'      => $venue['Venue'],
				] );
				if ( $has_venue ) {
					$venue_ids[] = $has_venue[0]->ID;
					continue;
				}
				// Insert the venue.
				$venue_id = wp_insert_post(
					array(
					'post_title'   => wp_strip_all_tags( $venue['Venue'] ),
					'post_content' => '',
					'post_status'  => 'publish',
					'post_type'    => 'tribe_venue',
					)
				);
				if ( ! is_wp_error( $venue_id ) ) {
					$venue_ids[] = $venue_id;
					update_post_meta( $venue_id, '_kadence_starter_templates_imported_post', true );
					if ( isset( $venue['Address'] ) ) {
						update_post_meta( $venue_id, '_VenueAddress', $venue['Address'] );
					}
					if ( isset( $venue['Country'] ) ) {
						update_post_meta( $venue_id, '_VenueCountry', $venue['Country'] );
					}
					if ( isset( $venue['City'] ) ) {
						update_post_meta( $venue_id, '_VenueCity', $venue['City'] );
					}
					if ( isset( $venue['Province'] ) ) {
						update_post_meta( $venue_id, '_VenueProvince', $venue['Province'] );
					}
					if ( isset( $venue['State'] ) ) {
						update_post_meta( $venue_id, '_VenueState', $venue['State'] );
					}
					if ( isset( $venue['State_Province'] ) ) {
						update_post_meta( $venue_id, '_VenueStateProvince', $venue['State_Province'] );
					}
					if ( isset( $venue['Zip'] ) ) {
						update_post_meta( $venue_id, '_VenueZip', $venue['Zip'] );
					}
					if ( isset( $venue['Phone'] ) ) {
						update_post_meta( $venue_id, '_VenuePhone', $venue['Phone'] );
					}
					if ( isset( $venue['Website'] ) ) {
						update_post_meta( $venue_id, '_VenueWebsite', $venue['Website'] );
					}
				}
			}
		}
		return $venue_ids;
	}
	/**
	 * Set the venue and tag ids.
	 *
	 * @param array      $data    Item data.
	 */
	protected function set_event_organizers_data( $data ) {
		$organizer_ids = array();
		// Set the categories.
		if ( ! empty( $data['organizers'] ) ) {
			foreach ( $data['organizers'] as $organizer ) {
				// Lets not duplicate venues.
				$has_organizer = get_posts( [
					'post_type'  => 'tribe_organizer',
					'title'      => $organizer['Organizer'],
				] );
				if ( $has_organizer ) {
					$organizer_ids[] = $has_organizer[0]->ID;
					continue;
				}
				// Insert the venue.
				$organizer_id = wp_insert_post(
					array(
					'post_title'   => wp_strip_all_tags( $organizer['Organizer'] ),
					'post_content' => '',
					'post_status'  => 'publish',
					'post_type'    => 'tribe_organizer',
					)
				);
				if ( ! is_wp_error( $organizer_id ) ) {
					$organizer_ids[] = $organizer_id;
					update_post_meta( $organizer_id, '_kadence_starter_templates_imported_post', true );
					if ( isset ( $organizer['Email'] ) ) {
						update_post_meta( $organizer_id, '_OrganizerEmail', $organizer['Email'] );
					}
					if ( isset ( $organizer['Website'] ) ) {
						update_post_meta( $organizer_id, '_OrganizerWebsite', $organizer['Website'] );
					}
					if ( isset ( $organizer['Phone'] ) ) {
						update_post_meta( $organizer_id, '_OrganizerPhone', $organizer['Phone'] );
					}
				}
			}
		}
		return $organizer_ids;
	}
		/**
	 * Set the category and tag ids.
	 *
	 * @param array      $data    Item data.
	 */
	protected function set_taxonomy_data( $data, $key, $taxonomy ) {
		$taxonomy_ids = array();
		// Set the categories.
		if ( ! empty( $data[$key] ) ) {
			foreach ( $data[$key] as $slug => $name ) {
				$taxonomy_term = get_term_by( 'slug', $slug, $taxonomy );
				if ( ! $taxonomy_term ) {
					$taxonomy_term = wp_insert_term(
						$name, // the term.
						$taxonomy, // the taxonomy.
						array(
							'slug' => $slug
						)
					);
				}
				if ( ! is_wp_error( $taxonomy_term ) && ! empty( $taxonomy_term->term_id ) ) {
					$taxonomy_ids[] = $taxonomy_term->term_id;
					update_term_meta( $taxonomy_term->term_id, '_kadence_starter_templates_imported_term', true );
				} else if ( ! empty( $taxonomy_term['term_id'] ) ) {
					$taxonomy_ids[] = $taxonomy_term['term_id'];
				}
			}
		}
		return $taxonomy_ids;
	}
	/**
	 * Set the category and tag ids.
	 *
	 * @param array      $data    Item data.
	 */
	protected function set_post_category_data( $data ) {
		$category_ids = array();
		// Set the categories.
		if ( ! empty( $data['categories'] ) ) {
			foreach ( $data['categories'] as $slug => $name ) {
				$category_term = get_term_by( 'slug', $slug, 'category' );
				if ( ! $category_term ) {
					$category_term = wp_insert_term(
						$name, // the term.
						'category', // the taxonomy.
						array(
							'slug' => $slug
						)
					);
				}
				if ( ! is_wp_error( $category_term ) && ! empty( $category_term->term_id ) ) {
					$category_ids[] = $category_term->term_id;
					update_term_meta( $category_term->term_id, '_kadence_starter_templates_imported_term', true );
				} else if ( ! empty( $category_term['term_id'] ) ) {
					$category_ids[] = $category_term['term_id'];
				}
			}
		}
		return $category_ids;
	}

	/**
	 * Set the tag ids.
	 *
	 * @param array      $data    Item data.
	 */
	protected function set_post_tag_data( $data ) {
		$tag_ids = array();
		// Set the tags.
		if ( ! empty( $data['tags'] ) ) {
			foreach ( $data['tags'] as $key => $tag ) {
				$tag_term = get_term_by( 'slug', $tag['slug'], 'tag' );
				if ( ! $tag_term ) {
					$tag_term = wp_insert_term(
						$tag['name']. // the term 
						'tag', // the taxonomy
						array(
							'slug' => $tag['slug']
						)
					);
				}
				if ( ! is_wp_error( $tag_term ) && ! empty( $tag_term->term_id ) ) {
					$tag_ids[] = $tag_term->term_id;
					update_term_meta( $tag_term->term_id, '_kadence_starter_templates_imported_term', true );
				} else if ( ! empty( $tag_term['term_id'] ) ) {
					$tag_ids[] = $tag_term['term_id'];
				}
			}
		}
		return $tag_ids;
	}
	/**
	 * Set the category and tag ids.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $data    Item data.
	 */
	protected function set_category_data( &$product, $data ) {
		// Set the categories.
		if ( ! empty( $data['categories'] ) ) {
			$category_ids = array();

			foreach ( $data['categories'] as $key => $cat ) {
				$category_term = get_term_by( 'slug', $cat['slug'], 'product_cat' );
				if ( ! $category_term ) {
					$category_term = wp_insert_term(
						$cat['name'], // the term.
						'product_cat', // the taxonomy.
						array(
							'slug' => $cat['slug']
						)
					);
				}
				if ( ! is_wp_error( $category_term ) && ! empty( $category_term->term_id ) ) {
					$category_ids[] = $category_term->term_id;
					update_term_meta( $category_term->term_id, '_kadence_starter_templates_imported_term', true );
				} else if ( ! is_wp_error( $category_term ) && ! empty( $category_term['term_id'] ) ) {
					$category_ids[] = $category_term['term_id'];
				}
			}
			if ( ! empty( $category_ids ) ) {
				$product->set_category_ids( $category_ids );
			}
		}
		// Set the tags.
		if ( ! empty( $data['tags'] ) ) {
			$tag_ids = array();

			foreach ( $data['tags'] as $key => $tag ) {
				$tag_term = get_term_by( 'slug', $tag['slug'], 'product_tag' );
				if ( ! $tag_term ) {
					$tag_term = wp_insert_term(
						$tag['name']. // the term 
						'product_tag', // the taxonomy
						array(
							'slug' => $tag['slug']
						)
					);
				}
				if ( ! is_wp_error( $tag_term ) && ! empty( $tag_term->term_id ) ) {
					$tag_ids[] = $tag_term->term_id;
					update_term_meta( $tag_term->term_id, '_kadence_starter_templates_imported_term', true );
				} else if ( ! empty( $tag_term['term_id'] ) ) {
					$tag_ids[] = $tag_term['term_id'];
				}
			}
			if ( ! empty( $tag_ids ) ) {
				$product->set_tag_ids( $tag_ids );
			}
		}
	}
	/**
	 * Set the category and tag ids.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $data    Item data.
	 */
	protected function set_attribute_data( &$product, $data ) {
		// Set the categories.
		if ( ! empty( $data['attributes'] ) ) {
			$attributes          = array();
			$default_attributes  = ! empty( $data['default_attributes'] ) ? $data['default_attributes'] : array();
			$existing_attributes = $product->get_attributes();
			// Example Global: "attributes":[{"id":1,"name":"color","taxonomy":"pa_color","has_variations":true,"terms":[{"id":18,"name":"Blue","slug":"blue"},{"id":19,"name":"Red","slug":"red"},{"id":20,"name":"Yellow","slug":"yellow"}]}]
			// Example Local: "attributes":[{"id":0,"name":"Size","taxonomy":null,"has_variations":true,"terms":[{"id":0,"name":"small","slug":"small"},{"id":0,"name":"Large","slug":"Large"}]}]
			foreach ( $data['attributes'] as $position => $attribute ) {
				$attribute_id = 0;
				// Get ID if is a global attribute.
				if ( ! empty( $attribute['taxonomy'] ) ) {
					$attribute_id = $this->get_attribute_taxonomy_id( $attribute['name'] );
				}

				// Set attribute visibility.
				$is_visible = 0;
				if ( ! empty( $attribute['is_visible'] ) && $attribute['is_visible'] ) {
					$is_visible = 1;
				}
				// Get name.
				$attribute_name = $attribute_id ? wc_attribute_taxonomy_name_by_id( $attribute_id ) : $attribute['name'];

				$is_variation = 0;
				if ( ! empty( $attribute['has_variations'] ) && $attribute['has_variations'] ) {
					$is_variation = 1;
				}

				if ( $attribute_id ) {
					if ( isset( $attribute['terms'] ) ) {
						$options = $this->add_attribute_terms_by_id( $attribute_id, $attribute['terms'] );
					} else {
						$options = array();
					}

					if ( ! empty( $options ) ) {
						$attribute_object = new WC_Product_Attribute();
						$attribute_object->set_id( $attribute_id );
						$attribute_object->set_name( $attribute_name );
						$attribute_object->set_options( $options );
						$attribute_object->set_position( $position );
						$attribute_object->set_visible( $is_visible );
						$attribute_object->set_variation( $is_variation );
						$attributes[] = $attribute_object;
					}
				} elseif ( isset( $attribute['terms'] ) ) {
					$slug_array = [];
					// Loop through each item in the array
					foreach ( $attribute['terms'] as $item ) {
						// Add the slug value to the slugArray
						$slug_array[] = $item['slug'];
					}
					$attribute_object = new WC_Product_Attribute();
					$attribute_object->set_name( $attribute['name'] );
					$attribute_object->set_options( $slug_array );
					$attribute_object->set_position( $position );
					$attribute_object->set_visible( $is_visible );
					$attribute_object->set_variation( $is_variation );
					$attributes[] = $attribute_object;
				}
			}

			$product->set_attributes( $attributes );

			// Set variable default attributes.
			if ( $product->is_type( 'variable' ) ) {
				$product->set_default_attributes( $default_attributes );
			}
		}
	}
	/**
	 * Get attribute taxonomy ID from the imported data.
	 * If does not exists register a new attribute.
	 *
	 * @param  string $raw_name Attribute name.
	 * @return int|false Returns the attribute ID if successful, false if failed.
	 */
	public function get_attribute_taxonomy_id( $raw_name ) {
		try {
			global $wpdb, $wc_product_attributes;

			// These are exported as labels, so convert the label to a name if possible first.
			$attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
			$attribute_name   = array_search( $raw_name, $attribute_labels, true );

			if ( ! $attribute_name ) {
				$attribute_name = wc_sanitize_taxonomy_name( $raw_name );
			}

			$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );

			// Get the ID from the name.
			if ( $attribute_id ) {
				return $attribute_id;
			}

			// If the attribute does not exist, create it.
			$attribute_id = wc_create_attribute(
				array(
					'name'         => $raw_name,
					'slug'         => $attribute_name,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				)
			);

			if ( is_wp_error( $attribute_id ) ) {
				return false;
			}

			// Register as taxonomy while importing.
			$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );
			register_taxonomy(
				$taxonomy_name,
				apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
				apply_filters(
					'woocommerce_taxonomy_args_' . $taxonomy_name,
					array(
						'labels'       => array(
							'name' => $raw_name,
						),
						'hierarchical' => true,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					)
				)
			);

			return $attribute_id;
		} catch ( Exception $e ) {
			return false;
		}
	}
	/**
	 * Add terms to attribute and return an array of term ids 
	 */
	public function add_attribute_terms_by_id( $attribute_id, $terms ) {
		$term_ids = [];
		foreach ( $terms as $term ) {
			$term_id = $this->add_attribute_term_by_id( $attribute_id, $term );
			if ( $term_id ) {
				$term_ids[] = $term_id;
			}
		}
		return $term_ids;
	}
	/**
	 * Add terms to attribute and return an array of term ids 
	 */
	public function add_attribute_term_by_id( $attribute_id, $term ) {
		$term_id = 0;
		if ( ! empty( $term['slug'] ) ) {
			$term_id = get_term_by( 'slug', $term['slug'], wc_attribute_taxonomy_name_by_id( $attribute_id ) );
		}
		if ( ! $term_id ) {
			$term_id = wp_insert_term(
				$term['name'], // the term.
				wc_attribute_taxonomy_name_by_id( $attribute_id ), // the taxonomy.
				array(
					'slug' => $term['slug']
				)
			);
		}
		if ( ! is_wp_error( $term_id ) ) {
			if ( is_array( $term_id ) && ! empty( $term_id['term_id'] ) ) {
				return $term_id['term_id'];
			} else if ( is_object( $term_id ) && ! empty( $term_id->term_id ) ) {
				update_term_meta( $term_id->term_id, '_kadence_starter_templates_imported_term', true );
				return $term_id->term_id;
			}
		}
		return 0;
	}
	/**
	 * Process images and links in page content.
	 *
	 * @param  string page content.
	 * @param  string image library.
	 * @return string page content.
	 */
	public function process_page_content( $content, $image_library = array() ) {
		// Check if the content is empty.
		if ( empty( $content ) ) {
			return $content;
		}
		// Check if the content is block content.
		if ( str_contains( (string) $content, '<!-- wp:' ) ) {
			$content = $this->process_images_for_block_content( $content, $image_library );
		} else {
			$content = $this->process_images_for_regular_content( $content, $image_library );
		}
		return $content;
	}
	/**
	 * Process images for block content.
	 */
	public function process_images_for_block_content( $content, $image_library ) {
		// First check if there are any images in the content. Use regex to find all urls
		preg_match_all( '/https?:\/\/[^\'" ]+/i', $content, $match );

		$all_urls = array_unique( $match[0] );
		if ( empty( $all_urls ) ) {
			return $content;
		}
		$map_urls    = array();
		$image_urls  = array();
		// Find all the images.
		foreach ( $all_urls as $key => $link ) {
			if ( $this->check_for_image( $link ) ) {
				$image_urls[] = $link;
			}
		}
		if ( empty( $image_urls ) ) {
			return $content;
		}
		// Process images.
		if ( ! empty( $image_urls ) ) {
			foreach ( $image_urls as $key => $image_url ) {
				// Download remote image.
				$image = [
					'url' => $image_url,
					'id'  => 0,
				];
				// If it's a pexels image, get the data.
				if ( substr( $image_url, 0, strlen( 'https://images.pexels.com' ) ) === 'https://images.pexels.com' ) {
					$image_data = $this->get_image_info( $image_library, $image_url );
					if ( $image_data ) {
						$alt                       = ! empty( $image_data['alt'] ) ? $image_data['alt'] : '';
						$image['filename']         = ! empty( $image_data['filename'] ) ? $image_data['filename'] : $this->create_filename_from_alt( $alt );
						$image['photographer']     = ! empty( $image_data['photographer'] ) ? $image_data['photographer'] : '';
						$image['photographer_url'] = ! empty( $image_data['photographer_url'] ) ? $image_data['photographer_url'] : '';
						$image['photograph_url']   = ! empty( $image_data['url'] ) ? $image_data['url'] : '';
						$image['alt']              = $alt;
						$image['title']            = __( 'Photo by', 'kadence-blocks' ) . ' ' . $image['photographer'];
					}
				}
				$downloaded_image       = $this->import_image( $image );
				$map_urls[ $image_url ] = [
					'url' => $downloaded_image['url'],
					'id'  => $downloaded_image['id'],
					'width' => $downloaded_image['width'],
					'height' => $downloaded_image['height'],
				];
			}
		}
		// parse the content into blocks.
		$content = $this->loop_through_block_content_for_images( $content, $map_urls );
		
		// Replace the rest of images in content if missed.
		foreach ( $map_urls as $old_url => $new_image ) {
			$content = str_replace( $old_url, $new_image['url'], $content );
			// Replace the slashed URLs if any exist.
			$old_url          = str_replace( '/', '/\\', $old_url );
			$new_image['url'] = str_replace( '/', '/\\', $new_image['url'] );
			$content          = str_replace( $old_url, $new_image['url'], $content );
		}
		return $content;
	}

	/**
	 * Process images for block content.
	 */
	public function loop_through_block_content_for_images( $content, $map_urls ) {
		if ( empty( $content ) ) {
			return $content;
		}
		$blocks = parse_blocks( $content );
		if ( ! empty( $blocks ) ) {
			foreach ( $blocks as &$block ) {
				if ( !empty( $block['blockName'] ) ) {
					switch ( $block['blockName'] ) {
						case 'kadence/image':
							// We need to extract the url from $block['innerHTML'].
							$image_url = $this->extract_image_url_from_block_content( $block['innerHTML'] );
							if ( !empty( $image_url ) && isset( $map_urls[ $image_url ] ) ) {
								$current_id = ( !empty( $block['attrs']['id'] ) ) ? $block['attrs']['id'] : '';
								$block['innerHTML'] = str_replace( $image_url, $map_urls[ $image_url ]['url'], $block['innerHTML'] );
								$block['innerHTML'] = str_replace( 'wp-image-' . $current_id, 'wp-image-' . $map_urls[ $image_url ]['id'], $block['innerHTML'] );
								$block['innerContent'] = str_replace( $image_url, $map_urls[ $image_url ]['url'], $block['innerContent'] );
								$block['innerContent'] = str_replace( 'wp-image-' . $current_id, 'wp-image-' . $map_urls[ $image_url ]['id'], $block['innerContent'] );
								$block['attrs']['id'] = absint( $map_urls[ $image_url ]['id'] );
								$block['attrs']['globalAlt'] = true;
							}
							break;
						case 'kadence/advancedgallery':
							if ( !empty ( $block['attrs']['imagesDynamic'] ) && is_array( $block['attrs']['imagesDynamic'] ) ) {
								$ids = [];
								foreach ( $block['attrs']['imagesDynamic'] as &$image ) {
									if ( !empty( $image['thumbUrl'] ) && isset( $map_urls[ $image['thumbUrl'] ] ) ) {
										$ids[] = absint( $map_urls[ $image['thumbUrl'] ]['id'] );
										$image['id'] = $map_urls[ $image['thumbUrl'] ]['id'];
										$image['width'] = $map_urls[ $image['thumbUrl'] ]['width'];
										$image['height'] = $map_urls[ $image['thumbUrl'] ]['height'];
										$image['lightUrl'] = $map_urls[ $image['thumbUrl'] ]['url'];
										$image['url'] = $map_urls[ $image['thumbUrl'] ]['url'];
										$image['thumbUrl'] = $map_urls[ $image['thumbUrl'] ]['url'];
									}
								}
								$block['attrs']['ids'] = $ids;
							}
							break;
						case 'kadence/column':
							if ( !empty( $block['attrs']['backgroundImg'][0]['bgImg'] ) ) {
								$image_url = $block['attrs']['backgroundImg'][0]['bgImg'];
								if ( !empty( $image_url ) && isset( $map_urls[ $image_url ] ) ) {
									$block['attrs']['backgroundImg'][0]['bgImg'] = $map_urls[ $image_url ]['url'];
									$block['attrs']['backgroundImg'][0]['bgImgID'] = $map_urls[ $image_url ]['id'];
								}
							}
							if ( !empty( $block['attrs']['overlayImg'][0]['bgImg'] ) ) {
								$image_url = $block['attrs']['overlayImg'][0]['bgImg'];
								if ( !empty( $image_url ) && isset( $map_urls[ $image_url ] ) ) {
									$block['attrs']['overlayImg'][0]['bgImg'] = $map_urls[ $image_url ]['url'];
									$block['attrs']['overlayImg'][0]['bgImgID'] = $map_urls[ $image_url ]['id'];
								}
							}
							break;
						case 'kadence/rowlayout':
							if ( !empty( $block['attrs']['bgImg'] ) ) {
								$image_url = $block['attrs']['bgImg'];
								if ( !empty( $image_url ) && isset( $map_urls[ $image_url ] ) ) {
									$block['attrs']['bgImg'] = $map_urls[ $image_url ]['url'];
									$block['attrs']['bgImgID'] = $map_urls[ $image_url ]['id'];
								}
							}
							if ( !empty( $block['attrs']['overlayBgImg'] ) ) {
								$image_url = $block['attrs']['overlayBgImg'];
								if ( !empty( $image_url ) && isset( $map_urls[ $image_url ] ) ) {
									$block['attrs']['overlayBgImg'] = $map_urls[ $image_url ]['url'];
									$block['attrs']['overlayBgImgID'] = $map_urls[ $image_url ]['id'];
								}
							}
							if ( !empty( $block['attrs']['backgroundSlider'] ) && is_array( $block['attrs']['backgroundSlider'] ) ) {
								foreach ( $block['attrs']['backgroundSlider'] as &$image ) {
									if ( !empty( $image['bgImg'] ) && isset( $map_urls[ $image['bgImg'] ] ) ) {
										$image['bgImg'] = $map_urls[ $image['bgImg'] ]['url'];
										$image['bgImgID'] = $map_urls[ $image['bgImg'] ]['id'];
									}
								}
							}
							break;
						case 'kadence/infobox':
							if ( !empty( $block['attrs']['mediaImage'][0]['url'] ) ) {
								$image_url = $block['attrs']['mediaImage'][0]['url'];
								if ( !empty( $image_url ) && isset( $map_urls[ $image_url ] ) ) {
									$current_id = ( !empty( $block['attrs']['mediaImage'][0]['id'] ) ) ? $block['attrs']['mediaImage'][0]['id'] : 0;
									$block['innerHTML'] = str_replace( $image_url, $map_urls[ $image_url ]['url'], $block['innerHTML'] );
									$block['innerHTML'] = str_replace( 'wp-image-' . $current_id, 'wp-image-' . $map_urls[ $image_url ]['id'], $block['innerHTML'] );
									$block['innerContent'] = str_replace( $image_url, $map_urls[ $image_url ]['url'], $block['innerContent'] );
									$block['innerContent'] = str_replace( 'wp-image-' . $current_id, 'wp-image-' . $map_urls[ $image_url ]['id'], $block['innerContent'] );
									$block['attrs']['mediaImage'][0]['url'] = $map_urls[ $image_url ]['url'];
									$block['attrs']['mediaImage'][0]['id'] = $map_urls[ $image_url ]['id'];
								}
							}
							break;
						case 'kadence/testimonial':
							if ( !empty( $block['attrs']['url'] ) ) {
								$image_url = $block['attrs']['url'];
								if ( !empty( $image_url ) && isset( $map_urls[ $image_url ] ) ) {
									$block['attrs']['url'] = $map_urls[ $image_url ]['url'];
									$block['attrs']['id'] = absint( $map_urls[ $image_url ]['id'] );
									$block['attrs']['sizes'] = [];
								}
							}
							break;
						case 'kadence/videopopup':
							if ( !empty( $block['attrs']['background'][0]['img'] ) ) {
								$image_url = $block['attrs']['background'][0]['img'];
								if ( !empty( $image_url ) && isset( $map_urls[ $image_url ] ) ) {
									$current_id = ( !empty( $block['attrs']['background'][0]['imgID'] ) ) ? $block['attrs']['background'][0]['imgID'] : 0;
									$block['innerHTML'] = str_replace( $image_url, $map_urls[ $image_url ]['url'], $block['innerHTML'] );
									$block['innerHTML'] = str_replace( 'wp-image-' . $current_id, 'wp-image-' . $map_urls[ $image_url ]['id'], $block['innerHTML'] );
									$block['innerContent'] = str_replace( $image_url, $map_urls[ $image_url ]['url'], $block['innerContent'] );
									$block['innerContent'] = str_replace( 'wp-image-' . $current_id, 'wp-image-' . $map_urls[ $image_url ]['id'], $block['innerContent'] );
									$block['attrs']['background'][0]['img'] = $map_urls[ $image_url ]['url'];
									$block['attrs']['background'][0]['imgID'] = $map_urls[ $image_url ]['id'];
								}
							}
							break;
					}
				}
				if (!empty($block['innerBlocks'])) {
					$inner_content = serialize_blocks($block['innerBlocks']);
					$updated_inner_content = $this->loop_through_block_content_for_images($inner_content, $map_urls);
					$block['innerBlocks'] = parse_blocks($updated_inner_content);
				}
			}
		}

		return serialize_blocks($blocks);
	}
	/**
	 * Extract image URL from block content.
	 */
	public function extract_image_url_from_block_content( $content ) {
		// Use regex to find the src attribute.
		preg_match_all( '/src="([^"]+)"/', $content, $match );
		return isset( $match[1][0] ) ? $match[1][0] : '';
	}
	
	/**
	 * Process images for regular content.
	 */
	public function process_images_for_regular_content( $content, $image_library ) {
		preg_match_all( '/https?:\/\/[^\'" ]+/i', $content, $match );

		$all_urls = array_unique( $match[0] );

		if ( empty( $all_urls ) ) {
			return $content;
		}

		$map_urls    = array();
		$image_urls  = array();
		// Find all the images.
		foreach ( $all_urls as $key => $link ) {
			if ( $this->check_for_image( $link ) ) {
				// Avoid srcset images.
				if (
					false === strpos( $link, '-150x' ) &&
					false === strpos( $link, '-300x' )
				) {
					$image_urls[] = $link;
				}
			}
		}
		// Process images.
		if ( ! empty( $image_urls ) ) {
			foreach ( $image_urls as $key => $image_url ) {
				// Download remote image.
				$image            = array(
					'url' => $image_url,
					'id'  => 0,
				);
				if ( substr( $image_url, 0, strlen( 'https://images.pexels.com' ) ) === 'https://images.pexels.com' ) {
					$image_data = $this->get_image_info( $image_library, $image_url );
					if ( $image_data ) {
						$alt                        = ! empty( $image_data['alt'] ) ? $image_data['alt'] : '';
						$image['filename']          = ! empty( $image_data['filename'] ) ? $image_data['filename'] : $this->create_filename_from_alt( $alt );
						$image['photographer']      = ! empty( $image_data['photographer'] ) ? $image_data['photographer'] : '';
						$image['photographer_url']  = ! empty( $image_data['photographer_url'] ) ? $image_data['photographer_url'] : '';
						$image['photograph_url']    = ! empty( $image_data['url'] ) ? $image_data['url'] : '';
						$image['alt']               = $alt;
						$image['title']             = __( 'Photo by', 'kadence-starter-templates' ) . ' ' . $image['photographer'];
					}
				}
				$downloaded_image       = $this->import_image( $image );
				$map_urls[ $image_url ] = $downloaded_image['url'];
			}
		}
		// Replace images in content.
		foreach ( $map_urls as $old_url => $new_url ) {
			$content = str_replace( $old_url, $new_url, $content );
			// Replace the slashed URLs if any exist.
			$old_url = str_replace( '/', '/\\', $old_url );
			$new_url = str_replace( '/', '/\\', $new_url );
			$content = str_replace( $old_url, $new_url, $content );
		}
		return $content;
	}
	/**
	 * Sanitizes a string for a filename.
	 *
	 * @param string $filename The filename.
	 * @return string a sanitized filename.
	 */
	public function sanitize_filename( $filename, $ext ) {
		return sanitize_file_name( $filename ) . '.' . $ext;
	}
	/**
	 * Create a filename from alt text.
	 */
	public function create_filename_from_alt( $alt ) {
		if ( empty( $alt ) ) {
			return '';
		}
		// Split the string into words.
		$words = explode( ' ', strtolower( $alt ) );
		// Limit to the first 7 words.
		$limited_words = array_slice( $words, 0, 7 );
		// Join the words with dashes.
		return implode( '-', $limited_words );
	}
	/**
	 * Check if image is already imported.
	 *
	 * @param array $image_data the image data to import.
	 */
	public function check_for_local_image( $image_data ) {
		global $wpdb;
		$image_id = '';
		if ( ! empty( $image_data['url'] ) && strpos( $image_data['url'], get_site_url() ) !== false ) {
			$image_id = attachment_url_to_postid( $image_data['url'] );
			if ( empty( $image_id ) ) {
				// Get unsized version use Regular expression to find the pattern -numberxnumber
				$pattern = "/-\d+x\d+/";
				// Replace the pattern with an empty string.
				$cleaned_url = preg_replace( $pattern, '', $image_data['url'] );
				$image_id = attachment_url_to_postid( $cleaned_url );
			}
		}
		if ( empty( $image_id ) ) {
			// Thanks BrainstormForce for this idea.
			// Check if image is already local based on meta key and custom hex value.
			$image_id = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT `post_id` FROM `' . $wpdb->postmeta . '`
						WHERE `meta_key` = \'_kadence_blocks_image_hash\'
							AND `meta_value` = %s
					;',
					sha1( $image_data['url'] )
				)
			);
		}
		if ( ! empty( $image_id ) ) {
			$image_sizes = wp_get_attachment_metadata( $image_id );
			$local_image = array(
				'id'  => $image_id,
				'url' => wp_get_attachment_url( $image_id ),
				'width' => $image_sizes['width'],
				'height' => $image_sizes['height'],
			);
			return array(
				'status' => true,
				'image'  => $local_image,
			);
		}
		return array(
			'status' => false,
			'image'  => $image_data,
		);
	}
	/**
	 * Import an image for the design library/patterns.
	 *
	 * @param array $image_data the image data to import.
	 */
	public function import_image( $image_data ) {
		$local_image = $this->check_for_local_image( $image_data );
		if ( $local_image['status'] ) {
			return $local_image['image'];
		}
		$filename   = basename( $image_data['url'] );
		$image_path = $image_data['url'];
		// Check if the image is from Pexels and get the filename.
		if ( substr( $image_data['url'], 0, strlen( 'https://images.pexels.com' ) ) === 'https://images.pexels.com' ) {
			$image_path = parse_url( $image_data['url'], PHP_URL_PATH );
			$filename = basename( $image_path );
		}
		$info = wp_check_filetype( $image_path );
		$ext  = empty( $info['ext'] ) ? '' : $info['ext'];
		$type = empty( $info['type'] ) ? '' : $info['type'];
		// If we don't allow uploading the file type or ext, return.
		if ( ! $type || ! $ext ) {
			return $image_data;
		}
		// Custom filename if passed as data.
		$filename = ! empty( $image_data['filename'] ) ? $this->sanitize_filename( $image_data['filename'], $ext ) : $filename;

		$file_content = wp_remote_retrieve_body(
			wp_safe_remote_get(
				$image_data['url'],
				array(
					'timeout'   => '60',
					'sslverify' => false,
				)
			)
		);
		// Empty file content?
		if ( empty( $file_content ) ) {
			return $image_data;
		}

		$upload = wp_upload_bits( $filename, null, $file_content );
		$post = array(
			'post_title' => ( ! empty( $image_data['title'] ) ? $image_data['title'] : $filename ),
			'guid'       => $upload['url'],
		);
		$post['post_mime_type'] = $type;
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			include( ABSPATH . 'wp-admin/includes/image.php' );
		}
		$post_id = wp_insert_attachment( $post, $upload['file'] );
		wp_update_attachment_metadata(
			$post_id,
			wp_generate_attachment_metadata( $post_id, $upload['file'] )
		);
		if ( ! empty( $image_data['alt'] ) ) {
			update_post_meta( $post_id, '_wp_attachment_image_alt', $image_data['alt'] );
		}
		if ( ! empty( $image_data['photographer'] ) ) {
			update_post_meta( $post_id, '_pexels_photographer', $image_data['photographer'] );
		}
		if ( ! empty( $image_data['photographer_url'] ) ) {
			update_post_meta( $post_id, '_pexels_photographer_url', $image_data['photographer_url'] );
		}
		if ( ! empty( $image_data['photograph_url'] ) ) {
			update_post_meta( $post_id, '_pexels_photograph_url', $image_data['photograph_url'] );
		}
		update_post_meta( $post_id, '_kadence_blocks_image_hash', sha1( $image_data['url'] ) );
		update_post_meta( $post_id, '_kadence_starter_templates_imported_post', true );
		$image_sizes = wp_get_attachment_metadata( $post_id );
		return array(
			'id'  => $post_id,
			'url' => $upload['url'],
			'width' => $image_sizes['width'],
			'height' => $image_sizes['height'],
		);
	}
	/**
	 * Get information for our image.
	 *
	 * @param array $images the image url.
	 * @param string $target_src the image url.
	 */
	public function get_image_info( $images, $target_src ) {
		foreach ( $images['data'] as $image_group ) {
			foreach ( $image_group['images'] as $image ) {
				foreach ( $image['sizes'] as $size ) {
					if ( $size['src'] === $target_src ) {
						return array(
							'alt'              => ! empty( $image['alt'] ) ? $image['alt'] : '',
							'photographer'     => ! empty( $image['photographer'] ) ? $image['photographer'] : '',
							'url'              => ! empty( $image['url'] ) ? $image['url'] : '',
							'photographer_url' => ! empty( $image['photographer_url'] ) ? $image['photographer_url'] : '',
						);
					}
				}
			}
		}
		return false;
	}
	/**
	 * Check if link is for an image.
	 *
	 * @param string $link url possibly to an image.
	 */
	public function check_for_image( $link = '' ) {
		if ( empty( $link ) ) {
			return false;
		}
		if ( substr( $link, 0, strlen( 'https://images.pexels.com' ) ) === 'https://images.pexels.com' ) {
			return true;
		}
		return preg_match( '/^((https?:\/\/)|(www\.))([a-z0-9-].?)+(:[0-9]+)?\/[\w\-]+\.(jpg|png|gif|webp|jpeg|mp4)\/?$/i', $link );
	}
	/**
	 * Checks if a given request has access to search content.
	 *
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has search access, WP_Error object otherwise.
	 */
	public function get_items_permission_check( $request ) {
		return current_user_can( 'manage_options' );
	}
	
	/**
	 * Sanitizes an array of industries.
	 *
	 * @param array    $industries One or more size arrays.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @param string          $parameter Parameter name.
	 * @return array|WP_Error List of valid subtypes, or WP_Error object on failure.
	 */
	public function sanitize_industries_array( $industries, $request ) {
		if ( ! empty( $industries ) && is_array( $industries ) ) {
			$new_industries = array();
			foreach ( $industries as $key => $value ) {
				$new_industries[] = sanitize_text_field( $value );
			}
			return $new_industries;
		}
		return array();
	}
	/**
	 * Imports a collection of images.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array<array{id: int, url: string}> A list of local or pexels images, where the ID is an attachment_id or pexels_id.
	 * @throws InvalidArgumentException
	 * @throws Throwable
	 * @throws ImageDownloadException
	 */
	public function process_images( WP_REST_Request $request ): array {
		$parameters = (array) $request->get_json_params();
		return kadence_starter_templates()->get( Image_Downloader::class )->download( $parameters );
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_image_collections( WP_REST_Request $request ) {
		$reload        = $request->get_param( self::PROP_FORCE_RELOAD );
		$this->get_license_keys();
		$identifier    = 'image_collections';

		if ( ! $reload ) {
			try {
				return rest_ensure_response( $this->block_library_cache->get( $identifier ) );
			} catch ( NotFoundException $e ) {
			}
		}

		// Check if we have a remote file.
		$response = $this->get_remote_image_collections();

		if ( $response === 'error' ) {
			return rest_ensure_response( 'error' );
		}

		$this->block_library_cache->cache( $identifier, $response );

		return rest_ensure_response( $response );
	}
	/**
	 * Retrieves a collection of objects.
	 *
	 * @param array $industries One or more size arrays.
	 * @param string $search_query The search query.
	 * @param string $image_type The image type.
	 * @param array $image_sizes The image sizes.
	 * @param bool $reload Whether to reload the data.
	 *
	 * @return array|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @throws InvalidArgumentException
	 */
	public function get_images_by_industry( $industries, $search_query = '', $image_type = 'JPEG', $image_sizes = array(), $reload = false ) {
		$this->get_license_keys();

		if ( empty( $industries ) || ! is_array( $industries ) ) {
			return new WP_Error( 'invalid_industries', 'Invalid industries' );
		}

		$identifier = 'imageCollection' . json_encode( $industries ) . ( defined( 'KADENCE_BLOCKS_VERSION' ) ? KADENCE_BLOCKS_VERSION : KADENCE_STARTER_TEMPLATES_VERSION );

		if ( ! empty( $image_type ) ) {
			$identifier .= '_' . $image_type;
		}

		if ( ! empty( $image_sizes ) && is_array( $image_sizes ) ) {
			$identifier .= '_' . json_encode( $image_sizes );
		}

		if ( ! empty( $search_query ) ) {
			$identifier .= '_' . $search_query;
		}

		// Whether this request will get saved to cache.
		$store = false;

		// Try to get results from the cache.
		if ( ! $reload ) {
			try {
				$response = $this->block_library_cache->get( $identifier );
			} catch ( NotFoundException $e ) {

			}
		}

		// No cache, fetch live.
		if ( ! isset( $response ) ) {
			$store = true;

			if ( ! empty( $search_query ) && in_array( 'aiGenerated', $industries, true ) ) {
				// Fetch search image data.
				$response = $this->get_remote_search_images( $search_query, $image_type, $image_sizes );
			} else {
				// Fetch industry image data.
				$response = $this->get_remote_industry_images( $industries, $image_type, $image_sizes );
			}
		}
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( $response === 'error' ) {
			return new WP_Error( 'invalid_response', 'Invalid response' );
		}

		$data = json_decode( $response, true );

		if ( ! isset( $data['data'] ) ) {
			return new WP_Error( 'invalid_response', 'Invalid response' );
		}

		if ( $store ) {
			// Create a cache file.
			$this->block_library_cache->cache( $identifier, $response );
		}

		// Prime the cache for all image sizes for potential download.
		$this->cache_primer->init( $data['data'] );

		return $data;
	}
	/**
	 * Get remote file contents.
	 *
	 * @access public
	 * @return string Returns the remote URL contents.
	 */
	public function get_remote_search_images( $search_query, $image_type = 'JPEG', $sizes = array() ) {
		if ( empty( $search_query ) ) {
			return 'error';
		}
		if ( empty( $sizes ) ) {
			$sizes = array(
				array(
					"id" => "2048x2048",
					"width" => 2048,
					"height" => 2048,
					"crop" => false,
				),
			);
		}
		if ( empty( $image_type ) ) {
			$image_type = 'JPEG';
		}
		$body = array(
			'query' => $search_query,
			'image_type' => $image_type,
			'sizes' => $sizes,
			'page' => 1,
			'per_page' => 24,
		);
		$response = wp_remote_post(
			$this->remote_ai_url . 'images/search',
			array(
				'timeout' => 20,
				'headers' => array(
					'X-Prophecy-Token' => $this->get_token_header(),
					'Content-Type'     => 'application/json',
				),
				'body'    => json_encode( $body ),
			)
		);
		// Early exit if there was an error.
		if ( is_wp_error( $response ) || $this->is_response_code_error( $response ) ) {
			return 'error';
		}

		// Get the CSS from our response.
		$contents = wp_remote_retrieve_body( $response );
		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return 'error';
		}

		return $contents;
	}
	/**
	 * Get the Pexels industry image JSON definitions.
	 *
	 * @return string Returns the remote URL contents.
	 */
	public function get_remote_industry_images( $industries, $image_type = 'JPEG', $sizes = array() ) {
		if ( empty( $industries ) ) {
			return 'error';
		}

		if ( empty( $sizes ) ) {
			$sizes = array(
				array(
					'id'     => '2048x2048',
					'width'  => 2048,
					'height' => 2048,
					'crop'   => false,
				),
			);
		}

		if ( empty( $image_type ) ) {
			$image_type = 'JPEG';
		}

		$body = array(
			'industries' => $industries,
			'image_type' => $image_type,
			'sizes'      => $sizes,
		);

		$response = wp_remote_post(
			$this->remote_ai_url . 'images/collections',
			array(
				'timeout' => 20,
				'headers' => array(
					'X-Prophecy-Token' => $this->get_token_header(),
					'Content-Type'     => 'application/json',
				),
				'body'    => json_encode( $body ),
			)
		);

		// Early exit if there was an error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( $this->is_response_code_error( $response ) ) {
			return new WP_Error( 'invalid_response', 'Invalid response' );
		}
		// Get the image JSON from our response.
		$contents = wp_remote_retrieve_body( $response );

		// Early exit if there was an error.
		if ( is_wp_error( $contents ) ) {
			return $contents;
		}

		return $contents;
	}
	/**
	 * Get the local data file if there, else query the api.
	 *
	 * @access public
	 * @return string
	 */
	public function get_template_data( $skip_local = false ) {
		if ( 'custom' === $this->template_type ) {
			return wp_json_encode( apply_filters( 'kadence_starter_templates_custom_array', array() ) );
		}
		// Check if the local data file exists.
		if ( $skip_local || ! $this->has_local_file() ) {
			// Attempt to create the file.
			if ( $this->create_template_data_file() ) {
				return $this->get_local_template_data_contents();
			}
		} else if ( '[]' === $this->get_local_template_data_contents() ) {
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
			array(
				'request'   => ( $this->template_type ? $this->template_type : 'blocks' ),
				'api_email' => $this->api_email,
				'api_key'   => $this->api_key,
				'site_url'  => $this->site_url,
			)
		);
		if ( ! empty( $this->env ) ) {
			$args['env'] = $this->env;
		}
		// Get the response.
		$api_url  = add_query_arg( $args, $this->remote_url );
		$response = wp_safe_remote_get(
			$api_url,
			array(
				'timeout' => 20,
			)
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
	 * Schedule a cleanup.
	 *
	 * Deletes the templates files on a regular basis.
	 * This way templates get updated regularly.
	 *
	 * @access public
	 * @return void
	 */
	public function schedule_cleanup() {
		if ( ! is_multisite() || ( is_multisite() && is_main_site() ) ) {
			if ( ! wp_next_scheduled( 'delete_starter_templates_folder' ) && ! wp_installing() ) {
				wp_schedule_event( time(), self::CLEANUP_FREQUENCY, 'delete_starter_templates_folder' );
			}
		}
	}
	/**
	 * Delete the fonts folder.
	 *
	 * This runs as part of a cleanup routine.
	 *
	 * @access public
	 * @return bool
	 */
	public function delete_starter_templates_folder() {
		return $this->get_filesystem()->delete( $this->get_starter_templates_folder(), true );
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
		$subfolder_name = apply_filters( 'kadence_block_ai_local_data_subfolder_name', 'kadence_ai' );
		return $subfolder_name;
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
			$upload_dir = wp_upload_dir();
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
	 * Get the allowed plugins.
	 *
	 * @access public
	 * @return array
	 */
	private function get_allowed_plugins() {
		$importer_plugins = array(
			'woocommerce' => array(
				'title' => 'Woocommerce',
				'base'  => 'woocommerce',
				'slug'  => 'woocommerce',
				'path'  => 'woocommerce/woocommerce.php',
				'src'   => 'repo',
			),
			'elementor' => array(
				'title' => 'Elementor',
				'base'  => 'elementor',
				'slug'  => 'elementor',
				'path'  => 'elementor/elementor.php',
				'src'   => 'repo',
			),
			'kadence-blocks' => array(
				'title' => 'Kadence Blocks',
				'base'  => 'kadence-blocks',
				'slug'  => 'kadence-blocks',
				'path'  => 'kadence-blocks/kadence-blocks.php',
				'src'   => 'repo',
			),
			'kadence-blocks-pro' => array(
				'title' => 'Kadence Block Pro',
				'base'  => 'kadence-blocks-pro',
				'slug'  => 'kadence-blocks-pro',
				'path'  => 'kadence-blocks-pro/kadence-blocks-pro.php',
				'src'   => 'bundle',
			),
			'kadence-pro' => array(
				'title' => 'Kadence Pro',
				'base'  => 'kadence-pro',
				'slug'  => 'kadence-pro',
				'path'  => 'kadence-pro/kadence-pro.php',
				'src'   => 'bundle',
			),
			'fluentform' => array(
				'title' => 'Fluent Forms',
				'src'   => 'repo',
				'base'  => 'fluentform',
				'slug'  => 'fluentform',
				'path'  => 'fluentform/fluentform.php',
			),
			'wpzoom-recipe-card' => array(
				'title' => 'Recipe Card Blocks by WPZOOM',
				'base'  => 'recipe-card-blocks-by-wpzoom',
				'slug'  => 'wpzoom-recipe-card',
				'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
				'src'   => 'repo',
			),
			'recipe-card-blocks-by-wpzoom' => array(
				'title' => 'Recipe Card Blocks by WPZOOM',
				'base'  => 'recipe-card-blocks-by-wpzoom',
				'slug'  => 'wpzoom-recipe-card',
				'path'  => 'recipe-card-blocks-by-wpzoom/wpzoom-recipe-card.php',
				'src'   => 'repo',
			),
			'learndash' => array(
				'title' => 'LearnDash',
				'base'  => 'sfwd-lms',
				'slug'  => 'sfwd_lms',
				'path'  => 'sfwd-lms/sfwd_lms.php',
				'src'   => 'thirdparty',
			),
			'sfwd-lms' => array(
				'title' => 'LearnDash',
				'base'  => 'sfwd-lms',
				'slug'  => 'sfwd_lms',
				'path'  => 'sfwd-lms/sfwd_lms.php',
				'src'   => 'thirdparty',
			),
			'learndash-course-grid' => array(
				'title' => 'LearnDash Course Grid Addon',
				'base'  => 'learndash-course-grid',
				'slug'  => 'learndash_course_grid',
				'path'  => 'learndash-course-grid/learndash_course_grid.php',
				'src'   => 'thirdparty',
			),
			'lifterlms' => array(
				'title' => 'LifterLMS',
				'base'  => 'lifterlms',
				'slug'  => 'lifterlms',
				'path'  => 'lifterlms/lifterlms.php',
				'src'   => 'repo',
			),
			'tutor' => array(
				'title' => 'Tutor LMS',
				'base'  => 'tutor',
				'slug'  => 'tutor',
				'path'  => 'tutor/tutor.php',
				'src'   => 'repo',
			),
			'give' => array(
				'title' => 'GiveWP',
				'base'  => 'give',
				'slug'  => 'give',
				'path'  => 'give/give.php',
				'src'   => 'repo',
			),
			'the-events-calendar' => array(
				'title' => 'The Events Calendar',
				'base'  => 'the-events-calendar',
				'slug'  => 'the-events-calendar',
				'path'  => 'the-events-calendar/the-events-calendar.php',
				'src'   => 'repo',
			),
			'event-tickets' => array(
				'title' => 'Event Tickets',
				'base'  => 'event-tickets',
				'slug'  => 'event-tickets',
				'path'  => 'event-tickets/event-tickets.php',
				'src'   => 'repo',
			),
			'orderable' => array(
				'title' => 'Orderable',
				'base'  => 'orderable',
				'slug'  => 'orderable',
				'path'  => 'orderable/orderable.php',
				'src'   => 'repo',
			),
			'restrict-content' => array(
				'title' => 'Restrict Content',
				'base'  => 'restrict-content',
				'slug'  => 'restrictcontent',
				'path'  => 'restrict-content/restrictcontent.php',
				'src'   => 'repo',
			),
			'kadence-woo-extras' => array(
				'title' => 'Kadence Shop Kit',
				'base'  => 'kadence-woo-extras',
				'slug'  => 'kadence-woo-extras',
				'path'  => 'kadence-woo-extras/kadence-woo-extras.php',
				'src'   => 'bundle',
			),
			'depicter' => array(
				'title' => 'Depicter Slider',
				'base'  => 'depicter',
				'slug'  => 'depicter',
				'path'  => 'depicter/depicter.php',
				'src'   => 'repo',
			),
			'bookit' => array(
				'title' => 'Bookit',
				'base'  => 'bookit',
				'slug'  => 'bookit',
				'path'  => 'bookit/bookit.php',
				'src'   => 'repo',
			),
			'kadence-woocommerce-email-designer' => array(
				'title' => 'Kadence Woocommerce Email Designer',
				'base'  => 'kadence-woocommerce-email-designer',
				'slug'  => 'kadence-woocommerce-email-designer',
				'path'  => 'kadence-woocommerce-email-designer/kadence-woocommerce-email-designer.php',
				'src'   => 'repo',
			),
			'seriously-simple-podcasting' => array(
				'title' => 'Seriously Simple Podcasting',
				'base'  => 'seriously-simple-podcasting',
				'slug'  => 'seriously-simple-podcasting',
				'path'  => 'seriously-simple-podcasting/seriously-simple-podcasting.php',
				'src'   => 'repo',
			),
			'better-wp-security' => array(
				'title' => 'Solid Security',
				'base'  => 'better-wp-security',
				'slug'  => 'better-wp-security',
				'path'  => 'better-wp-security/better-wp-security.php',
				'src'   => 'repo',
			),
			'solid-performance' => array(
				'title' => 'Solid Performance',
				'base'  => 'solid-performance',
				'slug'  => 'solid-performance',
				'path'  => 'solid-performance/solid-performance.php',
				'src'   => 'repo',
			),
		);
		return $importer_plugins;
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
	 * @param array    $value  One or more subtypes.
	 * @return bool    true or false.
	 */
	public function validate_array( $value ) {
		return is_array( $value );
	}
	/**
	 * Validates the list of subtypes, to ensure it's an array.
	 *
	 * @param array    $value  One or more subtypes.
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
	public function get_current_license_email() {
		// Check if we have pro active.
		if ( class_exists( 'Kadence_Blocks_Pro' ) ) {
			$license_key = get_option( 'stellarwp_uplink_license_key_kadence-blocks-pro', '' );
			if ( ! empty( $license_key ) ) {
				return '';
			} else {
				$license_data = $this->get_old_pro_license_data();
				if ( $license_data && ! empty( $license_data['api_email'] ) ) {
					return $license_data['api_email'];
				}
			}
		}
		return '';
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
	public function get_pro_license_data() {
		$license_data = array(
			'api_key'   => $this->get_current_license_key(),
			'api_email' => $this->get_current_license_email(),
			'site_url'  => get_original_domain(),
			'env'       => $this->get_current_env(),
		);
		return $license_data;
	}
	/**
	 * Get the license information.
	 *
	 * @return array
	 */
	public function get_old_pro_license_data() {
		$data = false;
		if ( is_multisite() && ! apply_filters( 'kadence_activation_individual_multisites', true ) ) {
			$data = get_site_option( 'kt_api_manager_kadence_gutenberg_pro_data' );
		} else {
			$data = get_option( 'kt_api_manager_kadence_gutenberg_pro_data' );
		}
		return $data;
	}
}
Starter_Import_Processes::get_instance();