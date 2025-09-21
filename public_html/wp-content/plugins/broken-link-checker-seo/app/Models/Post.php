<?php
namespace AIOSEO\BrokenLinkChecker\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Post DB model class.
 *
 * @since 1.0.0
 */
class Post extends Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'aioseo_blc_posts';

	/**
	 * Fields that should be hidden when serialized.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $hidden = [ 'id' ];

	/**
	 * Fields that are nullable.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $nullFields = [ 'last_scan_date', 'final_url' ];

	/**
	 * Fields that should be json encoded on save and decoded on get.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $jsonFields = [];


	/**
	 * Returns a Post with a given ID.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $postId The Post ID.
	 * @return Post         The Post object.
	 */
	public static function getPost( $postId ) {
		$post = aioseoBrokenLinkChecker()->core->db->start( 'aioseo_blc_posts' )
			->where( 'post_id', $postId )
			->run()
			->model( 'AIOSEO\\BrokenLinkChecker\\Models\\Post' );

		if ( ! $post->exists() ) {
			$post->post_id = $postId;
		}

		return $post;
	}
}