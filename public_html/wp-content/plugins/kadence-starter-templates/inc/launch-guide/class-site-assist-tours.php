<?php
/**
 * Adds Site Assist Dash
 *
 * @since 3.0.0
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Adds Site Assist Tours
 */
class Site_Assist_Tours {

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
		add_action( 'admin_enqueue_scripts', [ $this, 'tours_scripts' ] );
		add_action( 'admin_footer', [ $this, 'tours_markup' ] );
	}
	/**
	 * Load the tours markup
	 */
	public function tours_markup() {
		echo '<div id="kadence-site-assist-tours"></div>';
	}
	/**
	 * Load the tours scripts
	 */
	public function tours_scripts() {
		$kadence_starter_tours_meta = $this->get_asset_file( 'dist/starter-tours' );
		wp_enqueue_style( 'kadence-starter-tours', KADENCE_STARTER_TEMPLATES_URL . 'dist/starter-tours.css', [ 'wp-pointer' ], KADENCE_STARTER_TEMPLATES_VERSION );
		wp_enqueue_script( 'kadence-starter-tours', KADENCE_STARTER_TEMPLATES_URL . 'dist/starter-tours.js', array_merge( [ 'wp-pointer' ], $kadence_starter_tours_meta['dependencies'] ), $kadence_starter_tours_meta['version'], true );
		wp_localize_script(
			'kadence-starter-tours',
			'kadenceToursParams',
			[
				'tours' => $this->get_tours(),
				'i18n'  => [
					'next'     => __( 'Next', 'kadence-starter-templates' ),
					'previous' => __( 'Previous', 'kadence-starter-templates' ),
					'finish'   => __( 'Finish', 'kadence-starter-templates' ),
					'close'    => __( 'Close', 'kadence-starter-templates' ),
				],
			]
		);
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
	 * Get the tours
	 *
	 * @return array
	 */
	public function get_tours() {
		$tours = [
			'wp-admin'     => [
				'title'       => __( 'Get to know the WordPress admin', 'kadence-starter-templates' ),
				'description' => __( 'Learn about the basics of the WordPress admin and how to navigate.', 'kadence-starter-templates' ),
				'url'         => admin_url( 'admin.php?page=kadence-starter&kad-admin-tour=wp-admin' ),
				'steps'       => [
					[
						'id'        => 'view_site',
						'target'    => '#wp-admin-bar-view-site',
						'hover'     => '#wp-admin-bar-site-name',
						'title'     => __( 'View Site', 'kadence-starter-templates' ),
						'image'     => '',
						'content'   => __( 'To view your site from your visitors\' perspective, hover over your site name in the top right corner and click "View Site".', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_posts',
						'target'    => '#menu-posts',
						'hover'     => '',
						'image'     => '',
						'title'     => __( 'Manage Blog Posts', 'kadence-starter-templates' ),
						'content'   => __( 'You can create, edit, or delete any post on the site.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_media',
						'target'    => '#menu-media',
						'hover'     => '',
						'image'     => '',
						'title'     => __( 'Manage Media', 'kadence-starter-templates' ),
						'content'   => __( 'You can add, edit, or remove uploaded images, videos, and documents.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_pages',
						'target'    => '#menu-pages',
						'hover'     => '',
						'image'     => '',
						'title'     => __( 'Manage Pages', 'kadence-starter-templates' ),
						'content'   => __( 'You can create, edit, or delete any page on the site.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_comments',
						'target'    => '#menu-comments',
						'hover'     => '',
						'image'     => '',
						'title'     => __( 'Manage Blog Post Comments', 'kadence-starter-templates' ),
						'content'   => __( 'If you have comments enabled for your blog posts, you can manage, approve, delete, and reply to comments.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_appearance',
						'target'    => '#menu-appearance',
						'hover'     => '',
						'image'     => '',
						'title'     => __( 'Manage Site Appearance', 'kadence-starter-templates' ),
						'content'   => __( "Customize your site's design, including themes, widgets, menus, and the customizer.", 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_plugins',
						'target'    => '#menu-plugins',
						'hover'     => '',
						'image'     => '',
						'title'     => __( 'Manage Plugins', 'kadence-starter-templates' ),
						'content'   => __( "Extend your site's functionality with plugins. You can install, activate, deactivate, and delete plugins here.", 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_settings',
						'target'    => '#menu-settings',
						'hover'     => '',
						'image'     => '',
						'title'     => __( 'Manage Settings', 'kadence-starter-templates' ),
						'content'   => __( 'Configure various aspects of your site, such as general settings, reading settings, permalinks, and more.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_user',
						'target'    => '#wp-admin-bar-user-actions',
						'hover'     => '#wp-admin-bar-my-account',
						'image'     => '',
						'title'     => __( 'Manage Profile', 'kadence-starter-templates' ),
						'content'   => __( 'Edit your profile information, change your password, and manage your personal settings.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
					[
						'id'        => 'menu_users',
						'target'    => '#menu-users',
						'hover'     => '',
						'image'     => '',
						'title'     => __( 'Manage Users', 'kadence-starter-templates' ),
						'content'   => __( 'Add, edit, or remove users from your site and manage their permissions. You can also reset passwords and update user information, such as email addresses.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'url'       => '',
					],
				],
			],
			'block-editor' => [
				'title'       => __( 'Get to know the Page Editor', 'kadence-starter-templates' ),
				'description' => __( 'Learn about the basics of the WordPress page editor.', 'kadence-starter-templates' ),
				'url'         => admin_url( 'post-new.php?post_type=page&kad-admin-tour=block-editor' ),
				'steps'       => [
					[
						'id'        => 'block_editor_add_title',
						'inEditor'  => true,
						'expand'    => 'editor-load',
						'target'    => '.edit-post-visual-editor__post-title-wrapper',
						'title'     => __( 'Adding a Title', 'kadence-starter-templates' ),
						'content'   => __( 'Define the title for your page by filling it in here.', 'kadence-starter-templates' ),
						'placement' => 'bottom',
					],
					[
						'id'        => 'block_editor_add_text',
						'inEditor'  => true,
						'expand'    => 'editor-load',
						'target'    => '.is-root-container > .wp-block:first-child',
						'title'     => __( 'Adding Basic Content', 'kadence-starter-templates' ),
						'content'   => __( 'You can add basic text by clicking and typing into the editor field.', 'kadence-starter-templates' ),
						'placement' => 'bottom',
					],
					[
						'id'        => 'block_editor_add_block',
						'target'    => '.editor-document-tools__inserter-toggle',
						'title'     => __( 'Adding a Block', 'kadence-starter-templates' ),
						'content'   => __( 'Click the "+" icon to open the block inserter and add new content blocks to your page.', 'kadence-starter-templates' ),
						'placement' => 'bottom-start',
					],
					[
						'id'        => 'block_editor_inserter',
						'expand'    => '.editor-document-tools__inserter-toggle',
						'target'    => '.editor-inserter-sidebar__content',
						'title'     => __( 'Block Inserter', 'kadence-starter-templates' ),
						'content'   => __( 'Scroll through available blocks if you need to find specific functionality.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
					],
					[
						'id'        => 'block_editor_search_block',
						'expand'    => '.editor-document-tools__inserter-toggle',
						'target'    => '.block-editor-inserter__search .components-input-base',
						'title'     => __( 'Searching for Blocks', 'kadence-starter-templates' ),
						'content'   => __( 'Use the search bar to quickly find the block you need.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
						'retract'   => '.editor-document-tools__inserter-toggle',
					],
					[
						'id'        => 'block_editor_add_pattern',
						'target'    => '.kb-toolbar-prebuilt-button',
						'title'     => __( 'Adding Patterns', 'kadence-starter-templates' ),
						'content'   => __( 'You can add ready to use patterns of blocks by clicking on the design library button.', 'kadence-starter-templates' ),
						'placement' => 'bottom',
					],
					[
						'id'        => 'block_editor_search_patterns',
						'expand'    => '.kb-toolbar-prebuilt-button',
						'target'    => '.kb-design-library-categories',
						'title'     => __( 'Filter Patterns by Category', 'kadence-starter-templates' ),
						'content'   => __( 'You can filter through hundreds of patterns to find the perfect fit.', 'kadence-starter-templates' ),
						'placement' => 'right-start',
						'retract'   => '.kb-prebuilt-header-close',
					],
					[
						'id'        => 'block_editor_color_patterns',
						'expand'    => '.kb-toolbar-prebuilt-button',
						'target'    => '.kb-library-sidebar-fixed-bottom',
						'title'     => __( 'Change Pattern Color', 'kadence-starter-templates' ),
						'content'   => __( 'You can change the color of every pattern by clicking the color style.', 'kadence-starter-templates' ),
						'placement' => 'right-start',
						'interact'  => [ '.kb-style-button.kb-style-dark', '.kb-style-button.kb-style-light' ],
						'retract'   => '.kb-prebuilt-header-close',
					],
					[
						'id'        => 'block_editor_page_options',
						'target'    => '.editor-header__settings .components-button[aria-controls*="edit-post"]',
						'title'     => __( 'Settings', 'kadence-starter-templates' ),
						'content'   => __( 'Click on the settings button to open the page settings in the right sidebar.', 'kadence-starter-templates' ),
						'placement' => 'right-start',
					],
					[
						'id'        => 'block_editor_page_options',
						'expand'    => '.editor-header__settings .components-button[aria-controls*="edit-post"]',
						'subExpand' => '.editor-sidebar__panel-tabs button[data-tab-id*="document"]',
						'target'    => '.editor-sidebar',
						'title'     => __( 'Page Settings', 'kadence-starter-templates' ),
						'content'   => __( 'Define general page settings like author, publish date, and page URL.', 'kadence-starter-templates' ),
						'placement' => 'right-start',
					],
					[
						'id'        => 'block_editor_block_options',
						'expand'    => '.editor-header__settings .components-button[aria-controls*="edit-post"]',
						'subExpand' => '.editor-sidebar__panel-tabs button[data-tab-id*="block"]',
						'target'    => '.editor-sidebar',
						'title'     => __( 'Block Settings', 'kadence-starter-templates' ),
						'content'   => __( 'Click on a block to find settings for each block selected in the editor. Each block has its own set of options in the sidebar.', 'kadence-starter-templates' ),
						'placement' => 'right-start',
					],
					[
						'id'        => 'block_editor_move_block',
						'inEditor'  => true,
						'expand'    => 'add-editor-blocks',
						'blocks'    => [
							[
								'name'       => 'core/heading',
								'attributes' => [
									'anchor'  => 'kadence-tour',
									'content' => 'This is an example heading block.',
								],
							],
							[
								'name'       => 'core/paragraph',
								'attributes' => [
									'anchor'  => 'kadence-tour',
									'content' => 'This is an example paragraph block.',
								],
							],
						],
						'target'    => '.block-editor-block-mover',
						'title'     => __( 'Moving Blocks', 'kadence-starter-templates' ),
						'content'   => __( 'Use the up and down arrows or drag the handle to rearrange blocks on your page.', 'kadence-starter-templates' ),
						'placement' => 'top-start',
					],
					[
						'id'        => 'block_editor_delete_block',
						'target'    => '.block-editor-block-toolbar .block-editor-block-settings-menu .components-button',
						'title'     => __( 'Managing and Removing Blocks', 'kadence-starter-templates' ),
						'content'   => __( 'Click the three dots (options) on the block toolbar to copy, duplicate, or delete blocks by selecting "Remove Block".', 'kadence-starter-templates' ),
						'placement' => 'bottom-start',
						'retract'   => 'remove-editor-blocks',
					],
					[
						'id'        => 'block_editor_post_settings',
						'target'    => '.editor-header__settings .components-button[aria-controls*="kadence-theme-layout"]',
						'title'     => __( 'Post Settings', 'kadence-starter-templates' ),
						'content'   => __( 'Click on "Post Settings" in the top right to open the layout settings for the page.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
					],
					[
						'id'        => 'block_editor_post_layout_settings',
						'expand'    => '.editor-header__settings .components-button[aria-controls*="kadence-theme-layout"]',
						'target'    => '.editor-sidebar',
						'title'     => __( 'Layout Settings', 'kadence-starter-templates' ),
						'content'   => __( 'In the layout settings, you can define whether the page title should be shown, if the layout should be boxed or unboxed, full-width or normal content width, or if it should have a sidebar.', 'kadence-starter-templates' ),
						'placement' => 'left-start',
					],
					[
						'id'        => 'block_editor_preview',
						'target'    => '.editor-preview-dropdown__toggle',
						'title'     => __( 'View', 'kadence-starter-templates' ),
						'content'   => __( 'Click the "Preview" button to see how your page or post will look to visitors.', 'kadence-starter-templates' ),
						'placement' => 'bottom-start',
					],
					[
						'id'        => 'block_editor_publish',
						'target'    => '.editor-post-publish-button__button',
						'title'     => __( 'Publish/Update', 'kadence-starter-templates' ),
						'content'   => __( 'Once you are happy with your changes, click "Publish" or "Update" to make them live on your site.', 'kadence-starter-templates' ),
						'placement' => 'bottom-start',
					],
				],
			],
		];
		return apply_filters( 'kadence-starter-assist-tours', $tours );
	}
}
Site_Assist_Tours::get_instance();
