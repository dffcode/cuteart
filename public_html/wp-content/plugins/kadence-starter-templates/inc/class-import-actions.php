<?php
/**
 * Class for the import actions used in the One Click Demo Import plugin.
 * Register default WP actions for OCDI plugin.
 *
 * @package Kadence Starter Templates
 */

namespace KadenceWP\KadenceStarterTemplates;

use WP_Query;
use function post_exists;
use function wp_get_attachment_url;
use function wp_get_attachment_thumb_url;
use function download_url;
use function media_handle_sideload;
use function wp_get_attachment_metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ImportActions {
	/**
	 * Register all action hooks for this class.
	 */
	public function register_hooks() {
		// Before content import.
		add_action( 'kadence-starter-templates/before_content_import_execution', array( $this, 'before_content_import_action' ), 10, 5 );
		//add_action( 'kadence-starter-templates/before_content_import_execution', array( $this, 'before_content_import_reset_setting' ), 10, 5 );

		// After content import.
		add_action( 'kadence-starter-templates/after_content_import_execution', array( $this, 'before_widget_import_action' ), 10, 5 );
		add_action( 'kadence-starter-templates/after_content_import_execution', array( $this, 'widgets_import' ), 20, 5 );

		add_action( 'kadence-starter-templates/after_content_import_execution', array( $this, 'forms_import' ), 20, 5 );
		add_action( 'kadence-starter-templates/after_content_import_execution', array( $this, 'donations_import' ), 20, 5 );
		add_action( 'kadence-starter-templates/after_content_import_execution', array( $this, 'give_forms_import' ), 20, 5 );
		add_action( 'kadence-starter-templates/after_content_import_execution', array( $this, 'depicter_import' ), 20, 5 );

		// Customizer import.
		add_action( 'kadence-starter-templates/customizer_import_execution', array( $this, 'customizer_import' ), 10, 1 );

		// Customizer Color Only import.
		add_action( 'kadence-starter-templates/customizer_import_color_only_execution', array( $this, 'customizer_import_color_only' ), 10, 1 );

		// Customizer font Only import.
		add_action( 'kadence-starter-templates/customizer_import_font_only_execution', array( $this, 'customizer_import_font_only' ), 10, 1 );

		// After full import action.
		add_action( 'kadence-starter-templates/after_all_import_execution', array( $this, 'after_import_action' ), 10, 5 );

		// Special widget import cases.
		add_action( 'kadence-starter-templates/widget_settings_array', array( $this, 'fix_custom_menu_widget_ids' ) );
		// Add action to import the widgets.
		add_action( 'kadence-starter-templates/widget_settings_array', array( $this, 'fix_widget_images' ) );
		// Add action to import the widgets.
		add_action( 'kadence-starter-templates/widget_settings_array', array( $this, 'fix_widget_links' ) );
	}
	/**
	 * Find image urls in content and retrieve urls by array
	 *
	 * @param string $content the post content.
	 * @return array
	 */
	public function find_all_image_urls( $content ) {
		preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $content, $link_matching );
		$link_match = ( isset( $link_matching[0] ) ? array_unique( $link_matching[0] ) : array() );
		$urls = array();
		if ( ! empty( $link_match ) ) {
			// Extract normal and image links.
			foreach ( $link_match as $key => $link ) {
				if ( preg_match( '/^((https?:\/\/)|(www\.))([a-z0-9-].?)+(:[0-9]+)?\/[\w\-]+\.(jpg|png|gif|jpeg|webp|svg|mp4)\/?$/i', $link ) ) {
					$urls[] = $link;
				}
			}
		}
		if ( count( $urls ) == 0 ) {
			return array();
		}
		$images = array();
		foreach ( $urls as $index => $url ) {
			$images[ $index ]['url'] = $url;
		}
		foreach ( array_unique( $urls ) as $index => $url ) {
			$unique_array[] = $images[ $index ];
		}
		return $unique_array;
	}
	/**
	 * Change the Images to use the current site.
	 *
	 * @param array $widget The widget settings array.
	 */
	public function fix_widget_images( $widget ) {
		if ( empty( $widget['content'] ) ) {
			return $widget;
		}
		$images = $this->find_all_image_urls( stripslashes( $widget['content'] ) );
		if ( count( $images ) == 0 ) {
			return $widget;
		}
		foreach ( $images as $image ) {
			if ( ! empty( $image['url'] ) ) {
				$url_already = $this->check_for_image( $image['url'] );
				if ( $url_already ) {
					$widget['content'] = preg_replace( '/' . preg_quote( $image['url'], '/' ) . '/', $url_already, $widget['content'] );
				} else {
					$image_data = self::sideload_image( $image['url'] );
					if ( is_object( $image_data ) ) {
						$image_url = $image_data->url;
						$widget['content'] = preg_replace( '/' . preg_quote( $image['url'], '/' ) . '/', $image_url, $widget['content'] );
					}
				}
			}
		}
		return $widget;
	}
	/**
	 * Helper function: Sideload Image import
	 * Taken from the core media_sideload_image function and
	 * modified to return an array of data instead of html.
	 *
	 * @since 1.1.1.
	 * @param string $file The image file path.
	 * @return array An array of image data.
	 */
	private function check_for_image( $file ) {
		if ( ! empty( $file ) ) {
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png|webp|mp4)\b/i', $file, $matches );
			$file_name = basename( $matches[0] );
			$ext = array( ".png", ".jpg", ".gif", ".jpeg", ".webp", ".mp4" );
			$clean_filename = str_replace( $ext, "", $file_name );
			$clean_filename = trim( html_entity_decode( sanitize_title( $clean_filename ) ) );
			if ( post_exists( $clean_filename ) ) {
				$attachment = $this->get_page_by_title( $clean_filename, OBJECT, 'attachment' );
				if ( ! empty( $attachment ) ) {
					return wp_get_attachment_url( $attachment->ID );
				}
			}
		}
		return false;

	}
	/**
	 * Helper function: Sideload Image import
	 * Taken from the core media_sideload_image function and
	 * modified to return an array of data instead of html.
	 *
	 * @since 1.1.1.
	 * @param string $file The image file path.
	 * @return array An array of image data.
	 */
	private static function sideload_image( $file ) {
		$data = new \stdClass();

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}
		if ( ! empty( $file ) ) {
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png|webp)\b/i', $file, $matches );
			$file_array = array();
			$file_array['name'] = basename( $matches[0] );

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, 0 );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				unlink( $file_array['tmp_name'] );
				return $id;
			}

			// Build the object to return.
			$meta                = wp_get_attachment_metadata( $id );
			$data->attachment_id = $id;
			$data->url           = wp_get_attachment_url( $id );
			$data->thumbnail_url = wp_get_attachment_thumb_url( $id );
			$data->height        = $meta['height'];
			$data->width         = $meta['width'];
		}

		return $data;
	}
	/**
	 * Get Page by title.
	 */
	public function get_page_by_title( $page_title, $output = OBJECT, $post_type = 'page' ) {
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
	 * Change the links to use the current site.
	 *
	 * @param array $widget The widget settings array.
	 */
	public function fix_widget_links( $widget ) {
		if ( empty( $widget['content'] ) ) {
			return $widget;
		}
		$edit_content = stripslashes( $widget['content'] );
		// Extract all links.
		preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $edit_content, $match );
		$all_links = array_unique( $match[0] );
		$link_mapping = array();
		$page_links  = array();
		$some_links  = array();
		// Not have any link.
		if ( ! empty( $all_links ) ) {
			// Extract normal and image links.
			foreach ( $all_links as $key => $link ) {
				if ( ! preg_match( '/^((https?:\/\/)|(www\.))([a-z0-9-].?)+(:[0-9]+)?\/[\w\-]+\.(jpg|png|gif|jpeg|webp|svg|mp4)\/?$/i', $link ) )  {
					$page_links[] = $link;
				}
			}
			$demo_data = get_option( '_kadence_starter_templates_last_import_data', array() );
			if ( ! empty( $demo_data['url'] ) ) {
				$site_url = get_site_url();
				$demo_url = rtrim( sanitize_text_field( $demo_data['url'] ), '/' );
				foreach ( $page_links as $key => $link ) {
					$new_link = str_replace( $demo_url, $site_url, $link );
					if ( $new_link !== $link ) {
						$link_mapping[ $link ] = $new_link;
					}
				}
			}
			if ( ! empty( $link_mapping ) ) {
				foreach ( $link_mapping as $old_url => $new_url ) {
					$old_url_full = '"' . $old_url . '"';
					$new_url_full = '"' . $new_url . '"';
					$widget['content'] = str_replace( $old_url_full, $new_url_full, $widget['content'] );

					// Replace the slashed URLs if any exist.
					$old_url = str_replace( '/', '/\\', $old_url );
					$new_url = str_replace( '/', '/\\', $new_url );
					$widget['content'] = str_replace( $old_url, $new_url, $widget['content'] );
				}
			}
		}

		return $widget;
	}
	/**
	 * Change the menu IDs in the custom menu widgets in the widget import data.
	 * This solves the issue with custom menu widgets not having the correct (new) menu ID, because they
	 * have the old menu ID from the export site.
	 *
	 * @param array $widget The widget settings array.
	 */
	public function fix_custom_menu_widget_ids( $widget ) {
		// Skip (no changes needed), if this is not a custom menu widget.
		if ( ! array_key_exists( 'nav_menu', $widget ) || empty( $widget['nav_menu'] ) || ! is_int( $widget['nav_menu'] ) ) {
			return $widget;
		}

		// Get import data, with new menu IDs.
		$ocdi                = Starter_Templates::get_instance();
		$content_import_data = $ocdi->importer->get_importer_data();
		$term_ids            = $content_import_data['mapping']['term_id'];

		// Set the new menu ID for the widget.
		if ( is_array( $term_ids ) && isset( $term_ids[ $widget['nav_menu'] ] ) ) {
			$widget['nav_menu'] = $term_ids[ $widget['nav_menu'] ];
		}

		return $widget;
	}

	/**
	 * Execute the forms import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function forms_import( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		if ( ! empty( $selected_import_files['forms'] ) && class_exists( 'KadenceWP\KadenceStarterTemplates\Kadence_Starter_Templates_Fluent_Import' ) ) {
			Kadence_Starter_Templates_Fluent_Import::import( $selected_import_files['forms'] );
		}
	}

	/**
	 * Execute the Give donations import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function donations_import( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		if ( ! empty( $selected_import_files['give-donations'] ) && class_exists( 'KadenceWP\KadenceStarterTemplates\Kadence_Starter_Templates_Give_Import' ) ) {
			Kadence_Starter_Templates_Give_Import::import( $selected_import_files['give-donations'] );
		}
	}
	/**
	 * Execute the Give forms import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function give_forms_import( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		if ( ! empty( $selected_import_files['give-forms'] ) && class_exists( 'KadenceWP\KadenceStarterTemplates\Kadence_Starter_Templates_Give_Import' ) ) {
			Kadence_Starter_Templates_Give_Import::import_forms( $selected_import_files['give-forms'] );
		}
	}
	/**
	 * Execute the Depictor imports.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function depicter_import( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		if ( ! empty( $selected_import_files['depicter'] ) && class_exists( 'KadenceWP\KadenceStarterTemplates\Kadence_Starter_Templates_Depicter_Import' ) ) {
			Kadence_Starter_Templates_Depicter_Import::import_slider( $selected_import_files['depicter'] );
		}
	}
	/**
	 * Execute the widgets import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function widgets_import( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		if ( ! empty( $selected_import_files['widgets'] ) ) {
			WidgetImporter::import( $selected_import_files['widgets'] );
		}
	}

	/**
	 * Execute the customizer import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function customizer_import_color_only( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			CustomizerImporter::import_color( $selected_import_files['customizer'] );
		}
	}

	/**
	 * Execute the customizer import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function customizer_import_font_only( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			CustomizerImporter::import_font( $selected_import_files['customizer'] );
		}
	}
	/**
	 * Execute the customizer import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function customizer_import( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			CustomizerImporter::import( $selected_import_files['customizer'] );
		}
	}

	/**
	 * Before Content Import lets reset the theme options.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function before_content_import_reset_setting( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {

		//$old_data = get_option( '_kadence_starter_templates_last_import_data', array() );

	}
	/**
	 * Execute the action: 'kadence-starter-templates/before_content_import'.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function before_content_import_action( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		$this->do_import_action( 'kadence-starter-templates/before_content_import', $import_files[ $selected_index ], $selected_palette, $selected_font );
	}


	/**
	 * Execute the action: 'kadence-starter-templates/before_widgets_import'.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function before_widget_import_action( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		$this->do_import_action( 'kadence-starter-templates/before_widgets_import', $import_files[ $selected_index ], $selected_palette, $selected_font );
	}


	/**
	 * Execute the action: 'kadence-starter-templates/after_import'.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `kadence-starter-templates/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function after_import_action( $selected_import_files, $import_files, $selected_index, $selected_palette, $selected_font ) {
		$this->do_import_action( 'kadence-starter-templates/after_import', $import_files[ $selected_index ], $selected_palette, $selected_font );
	}


	/**
	 * Register the do_action hook, so users can hook to these during import.
	 *
	 * @param string $action          The action name to be executed.
	 * @param array  $selected_import The data of selected import from `kadence-starter-templates/import_files` filter.
	 */
	private function do_import_action( $action, $selected_import, $selected_palette, $selected_font ) {
		if ( false !== has_action( $action ) ) {
			$kadence_starter_templates         = Starter_Templates::get_instance();
			$log_file_path = $kadence_starter_templates->get_log_file_path();

			ob_start();
				do_action( $action, $selected_import, $selected_palette, $selected_font );
			$message = ob_get_clean();
			if ( apply_filters( 'kadence_starter_templates_save_log_files', false ) ) {
				// Add this message to log file.
				$log_added = Helpers::append_to_file(
					$message,
					$log_file_path,
					$action
				);
			}
		}
	}
}
