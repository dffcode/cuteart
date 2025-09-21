<?php
namespace AIOSEO\BrokenLinkChecker\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the registration, validation and authorization of API routes.
 *
 * @since 1.0.0
 */
class Api {
	/**
	 * The REST API namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $namespace = 'aioseoBrokenLinkChecker/v1';

	/**
	 * The routes we use in the rest API.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $routes = [
		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		'GET'    => [
			'options' => [ 'callback' => [ 'VueSettings', 'getOptions' ], 'access' => 'everyone' ],
			'ping'    => [ 'callback' => [ 'Ping', 'ping' ], 'access' => 'everyone' ]
		],
		'POST'   => [
			'broken-links/scan'            => [ 'callback' => [ 'BrokenLinks', 'getScanPercent' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'edit-row'                     => [ 'callback' => [ 'EditRow', 'update' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'license/activate'             => [ 'callback' => [ 'License', 'activate' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'license/deactivate'           => [ 'callback' => [ 'License', 'deactivate' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'link-status-table'            => [ 'callback' => [ 'LinkStatusTable', 'fetchData' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'link-status-table/bulk'       => [ 'callback' => [ 'LinkStatusTable', 'bulk' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'link-status-table/deletePost' => [ 'callback' => [ 'LinkStatusTable', 'deletePost' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'link-status-table/dismiss'    => [ 'callback' => [ 'LinkStatusTable', 'dismiss' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'link-status-table/recheck'    => [ 'callback' => [ 'LinkStatusTable', 'recheck' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'link-status-table/undismiss'  => [ 'callback' => [ 'LinkStatusTable', 'undismiss' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'link-status-table/unlink'     => [ 'callback' => [ 'LinkStatusTable', 'unlink' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'links-table'                  => [ 'callback' => [ 'LinksTable', 'fetchData' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'links-table/bulk'             => [ 'callback' => [ 'LinksTable', 'bulk' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'links-table/unlink'           => [ 'callback' => [ 'LinksTable', 'unlink' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'link-status-detail'           => [ 'callback' => [ 'LinkStatusDetail', 'getLinkStatusData' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'notifications/dismiss'        => [ 'callback' => [ 'Notifications', 'dismissNotifications' ], 'access' => 'any' ],
			'objects'                      => [ 'callback' => [ 'PostsTerms', 'searchForObjects' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'options'                      => [ 'callback' => [ 'VueSettings', 'saveChanges' ], 'access' => 'aioseo_blc_broken_links_page' ],
			'plugins/deactivate'           => [ 'callback' => [ 'Plugins', 'deactivatePlugins' ], 'access' => 'install_plugins' ],
			'plugins/install'              => [ 'callback' => [ 'Plugins', 'installPlugins' ], 'access' => 'install_plugins' ],
			'redirects/url'                => [ 'callback' => [ 'Redirects', 'getRedirectUrl' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ],
			'settings/toggle-card'         => [ 'callback' => [ 'VueSettings', 'toggleCard' ], 'access' => 'aioseo_blc_broken_links_page' ],
			'settings/toggle-radio'        => [ 'callback' => [ 'VueSettings', 'toggleRadio' ], 'access' => 'aioseo_blc_broken_links_page' ],
			'settings/items-per-page'      => [ 'callback' => [ 'VueSettings', 'changeItemsPerPage' ], 'access' => 'aioseo_blc_broken_links_page' ]
		],
		'DELETE' => [
			'post' => [ 'callback' => [ 'Post', 'deletePost' ], 'access' => [ 'aioseo_blc_broken_links_page' ] ]
		]
		// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
	];

	/**
	 * Class contructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'rest_allowed_cors_headers', [ $this, 'allowedHeaders' ] );
		add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
	}

	/**
	 * Registers the API routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerRoutes() {
		$class = new \ReflectionClass( get_called_class() );
		foreach ( $this->routes as $method => $data ) {
			foreach ( $data as $route => $options ) {
				register_rest_route(
					$this->namespace,
					$route,
					[
						'methods'             => $method,
						'permission_callback' => empty( $options['permissions'] ) ? [ $this, 'validRequest' ] : [ $this, $options['permissions'] ],
						'callback'            => is_array( $options['callback'] )
							? [
								(
									! empty( $options['callback'][2] )
										? $options['callback'][2] . '\\' . $options['callback'][0]
										: (
											class_exists( $class->getNamespaceName() . '\\' . $options['callback'][0] )
												? $class->getNamespaceName() . '\\' . $options['callback'][0]
												: __NAMESPACE__ . '\\' . $options['callback'][0]
										)
								),
								$options['callback'][1]
							]
							: [ $this, $options['callback'] ]
					]
				);
			}
		}
	}

	/**
	 * Sets headers that are allowed for our API routes.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $allowHeaders The allowed request headers.
	 * @return array               The allowed request headers.
	 */
	public function allowedHeaders( $allowHeaders ) {
		if ( ! array_search( 'X-WP-Nonce', $allowHeaders, true ) ) {
			$allowHeaders[] = 'X-WP-Nonce';
		}

		return $allowHeaders;
	}

	/**
	 * Determine if the user is logged in and has the proper permissions.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request $request The REST Request.
	 * @return bool                      Whether the user is allowed access to the route.
	 */
	public function validRequest( $request ) {
		return is_user_logged_in() && $this->validateAccess( $request );
	}

	/**
	 * Validates access for the routes.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request $request The REST Request.
	 * @return bool                      Whether the user is allowed access to the route.
	 */
	private function validateAccess( $request ) {
		$routeData = $this->getRouteData( $request );
		if ( empty( $routeData ) || empty( $routeData['access'] ) ) {
			return false;
		}

		// Admins users always have access.
		if ( aioseoBrokenLinkChecker()->access->isAdmin() ) {
			return true;
		}

		switch ( $routeData['access'] ) {
			case 'everyone':
				// All users are able to access the route.
				return true;
			case 'any':
				// Users with any Broken Link Checker permission can access the route.
				$user = wp_get_current_user();
				foreach ( $user->get_role_caps() as $capability => $enabled ) {
					if ( $enabled && preg_match( '/^aioseo_blc_/', (string) $capability ) ) {
						return true;
					}
				}

				return false;
			default:
				// The user has access if he has any of the required capabilities.
				if ( ! is_array( $routeData['access'] ) ) {
					$routeData['access'] = [ $routeData['access'] ];
				}

				foreach ( $routeData['access'] as $access ) {
					if ( current_user_can( $access ) ) {
						return true;
					}
				}

				return false;
		}
	}

	/**
	 * Returns the data for the route that is being accessed.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request $request The REST Request.
	 * @return array                     The route data.
	 */
	private function getRouteData( $request ) {
		// NOTE: Since WordPress uses case-insensitive patterns to match routes,
		// we are forcing everything to lower case to ensure we have the proper route.
		// This prevents users with lower privileges from accessing routes they shouldn't.
		$route     = aioseoBrokenLinkChecker()->helpers->toLowercase( $request->get_route() );
		$route     = untrailingslashit( str_replace( '/' . $this->namespace . '/', '', $route ) );
		$routeData = isset( $this->routes[ $request->get_method() ][ $route ] ) ? $this->routes[ $request->get_method() ][ $route ] : [];

		// No direct route name, let's try the regexes.
		if ( empty( $routeData ) ) {
			foreach ( $this->routes[ $request->get_method() ] as $routeRegex => $routeInfo ) {
				$routeRegex = str_replace( '@', '\@', $routeRegex );
				if ( preg_match( "@{$routeRegex}@", (string) $route ) ) {
					$routeData = $routeInfo;
					break;
				}
			}
		}

		return $routeData;
	}
}