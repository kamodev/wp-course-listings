<?php
/**
 * Activation and deactivation routines.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Runs on plugin activation and deactivation.
 */
final class Activator {

	/**
	 * Create the course listing page and register rewrite rules.
	 */
	public static function activate() {
		self::create_course_listing_page();

		Post_Types::register();
		flush_rewrite_rules();
	}

	/**
	 * Remove the plugin's rewrite rules.
	 */
	public static function deactivate() {
		unregister_post_type( 'course' );
		unregister_taxonomy( 'course_category' );
		unregister_taxonomy( 'course_program' );
		flush_rewrite_rules();
	}

	/**
	 * Create a page that lists all courses, unless one already exists at the archive slug.
	 */
	private static function create_course_listing_page() {
		$archive_slug = Options::get( 'wcl_courses_slug' );

		if ( get_page_by_path( $archive_slug ) ) {
			return;
		}

		wp_insert_post(
			array(
				'post_title'   => Options::get( 'wcl_archive_title' ),
				'post_name'    => $archive_slug,
				'post_content' => '[course_listings]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
	}
}
