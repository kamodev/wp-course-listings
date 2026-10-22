<?php
/**
 * Course post type and taxonomies.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the course post type, its taxonomies and their rewrite rules.
 */
final class Post_Types {

	/**
	 * Option used to request a rewrite flush on the next request.
	 */
	const FLUSH_FLAG = 'wcl_flush_rewrite_rules';

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( $this, 'add_archive_rewrite_rule' ), 20 );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 99 );

		foreach ( Options::SLUG_OPTIONS as $option ) {
			add_action( "add_option_{$option}", array( $this, 'request_rewrite_flush' ) );
			add_action( "update_option_{$option}", array( $this, 'request_rewrite_flush' ) );
		}
	}

	/**
	 * Register the post type and taxonomies.
	 *
	 * Static so the activator can register them before flushing rewrite rules.
	 */
	public static function register() {
		self::register_course_post_type();
		self::register_course_category_taxonomy();
		self::register_course_program_taxonomy();
	}

	/**
	 * Register the Course post type.
	 */
	private static function register_course_post_type() {
		$labels = array(
			'name'               => _x( 'Courses', 'post type general name', 'wp-course-listings' ),
			'singular_name'      => _x( 'Course', 'post type singular name', 'wp-course-listings' ),
			'add_new'            => __( 'Add New', 'wp-course-listings' ),
			'add_new_item'       => __( 'Add New Course', 'wp-course-listings' ),
			'edit_item'          => __( 'Edit Course', 'wp-course-listings' ),
			'new_item'           => __( 'New Course', 'wp-course-listings' ),
			'view_item'          => __( 'View Course', 'wp-course-listings' ),
			'search_items'       => __( 'Search Courses', 'wp-course-listings' ),
			'not_found'          => __( 'No courses found', 'wp-course-listings' ),
			'not_found_in_trash' => __( 'No courses found in Trash', 'wp-course-listings' ),
			'all_items'          => __( 'All Courses', 'wp-course-listings' ),
			'menu_name'          => __( 'Courses', 'wp-course-listings' ),
		);

		register_post_type(
			'course',
			array(
				'labels'            => $labels,
				'public'            => true,
				'has_archive'       => Options::get( 'wcl_courses_slug' ),
				'rewrite'           => array( 'slug' => Options::get( 'wcl_course_single_slug' ) ),
				'supports'          => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'show_in_rest'      => true,
				'taxonomies'        => array( 'course_category', 'course_program' ),
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
			)
		);
	}

	/**
	 * Register the Course Category taxonomy.
	 */
	private static function register_course_category_taxonomy() {
		$labels = array(
			'name'          => _x( 'Course Categories', 'taxonomy general name', 'wp-course-listings' ),
			'singular_name' => _x( 'Course Category', 'taxonomy singular name', 'wp-course-listings' ),
			'search_items'  => __( 'Search Course Categories', 'wp-course-listings' ),
			'all_items'     => __( 'All Course Categories', 'wp-course-listings' ),
			'edit_item'     => __( 'Edit Course Category', 'wp-course-listings' ),
			'update_item'   => __( 'Update Course Category', 'wp-course-listings' ),
			'add_new_item'  => __( 'Add New Course Category', 'wp-course-listings' ),
			'new_item_name' => __( 'New Course Category Name', 'wp-course-listings' ),
			'menu_name'     => __( 'Course Categories', 'wp-course-listings' ),
		);

		register_taxonomy(
			'course_category',
			array( 'course' ),
			array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => Options::get( 'wcl_category_slug' ) ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register the Course Program taxonomy.
	 */
	private static function register_course_program_taxonomy() {
		$labels = array(
			'name'              => _x( 'Course Programs', 'taxonomy general name', 'wp-course-listings' ),
			'singular_name'     => _x( 'Course Program', 'taxonomy singular name', 'wp-course-listings' ),
			'search_items'      => __( 'Search Course Programs', 'wp-course-listings' ),
			'all_items'         => __( 'All Course Programs', 'wp-course-listings' ),
			'edit_item'         => __( 'Edit Course Program', 'wp-course-listings' ),
			'update_item'       => __( 'Update Course Program', 'wp-course-listings' ),
			'add_new_item'      => __( 'Add New Course Program', 'wp-course-listings' ),
			'new_item_name'     => __( 'New Course Program Name', 'wp-course-listings' ),
			'menu_name'         => __( 'Course Programs', 'wp-course-listings' ),
			'parent_item'       => __( 'Parent Program', 'wp-course-listings' ),
			'parent_item_colon' => __( 'Parent Program:', 'wp-course-listings' ),
		);

		register_taxonomy(
			'course_program',
			array( 'course' ),
			array(
				'hierarchical'         => true,
				'labels'               => $labels,
				'show_ui'              => true,
				'show_admin_column'    => true,
				'rewrite'              => array( 'slug' => Options::get( 'wcl_program_slug' ) ),
				'show_in_rest'         => true,
				// Classic editor only; the block editor uses its own taxonomy panel.
				'meta_box_cb'          => array( Admin::class, 'render_program_meta_box' ),
				'meta_box_sanitize_cb' => 'taxonomy_meta_box_sanitize_cb_checkboxes',
			)
		);
	}

	/**
	 * Map the course archive slug to the course archive.
	 *
	 * Keeps the archive reachable at the bare slug even when the site's
	 * permalink structure has a front base such as /blog/.
	 */
	public function add_archive_rewrite_rule() {
		$archive_slug = Options::get( 'wcl_courses_slug' );
		add_rewrite_rule( '^' . $archive_slug . '/?$', 'index.php?post_type=course', 'top' );
	}

	/**
	 * Flag that rewrite rules need regenerating after a slug setting changes.
	 *
	 * The flush itself waits for the next request, once the post type and
	 * taxonomies have been registered with the new slugs.
	 */
	public function request_rewrite_flush() {
		update_option( self::FLUSH_FLAG, 1 );
	}

	/**
	 * Flush rewrite rules if a slug setting changed since the last request.
	 */
	public function maybe_flush_rewrite_rules() {
		if ( get_option( self::FLUSH_FLAG ) ) {
			delete_option( self::FLUSH_FLAG );
			flush_rewrite_rules();
		}
	}
}
