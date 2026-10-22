<?php
/**
 * Plugin option names and defaults.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for plugin options and their default values.
 */
final class Options {

	/**
	 * Options that control rewrite slugs. Changing any of them requires a rewrite flush.
	 *
	 * @var string[]
	 */
	const SLUG_OPTIONS = array(
		'wcl_courses_slug',
		'wcl_course_single_slug',
		'wcl_category_slug',
		'wcl_program_slug',
	);

	/**
	 * Default value for every plugin option.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			// URL settings.
			'wcl_courses_slug'        => 'courses',
			'wcl_course_single_slug'  => 'course',
			'wcl_category_slug'       => 'course-category',
			'wcl_program_slug'        => 'course-program',
			// Display settings.
			'wcl_courses_per_page'    => 12,
			'wcl_show_featured_image' => true,
			'wcl_show_excerpt'        => true,
			'wcl_show_categories'     => true,
			'wcl_show_course_meta'    => true,
			'wcl_show_programs'       => true,
			// Archive settings.
			'wcl_archive_title'       => 'Courses',
			'wcl_archive_description' => '',
			// SEO settings.
			'wcl_enable_seo_meta'     => true,
			'wcl_enable_social_meta'  => true,
		);
	}

	/**
	 * Get an option value, falling back to the plugin default.
	 *
	 * @param string $name Option name.
	 * @return mixed
	 */
	public static function get( $name ) {
		$defaults = self::defaults();

		return get_option( $name, isset( $defaults[ $name ] ) ? $defaults[ $name ] : false );
	}
}
