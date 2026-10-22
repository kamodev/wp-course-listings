<?php
/**
 * Remove plugin settings when the plugin is deleted.
 *
 * Courses, their categories, programs and post meta are kept so that
 * content is not lost if the plugin is reinstalled.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete the plugin's options on the current site.
 */
function delete_options() {
	$options = array(
		'wcl_courses_slug',
		'wcl_course_single_slug',
		'wcl_category_slug',
		'wcl_program_slug',
		'wcl_courses_per_page',
		'wcl_show_featured_image',
		'wcl_show_excerpt',
		'wcl_show_categories',
		'wcl_show_course_meta',
		'wcl_show_programs',
		'wcl_show_prerequisites',
		'wcl_show_price',
		'wcl_archive_title',
		'wcl_archive_description',
		'wcl_enable_seo_meta',
		'wcl_enable_social_meta',
		'wcl_flush_rewrite_rules',
		// Written by earlier versions to detect slug changes.
		'wcl_courses_slug_flushed',
		'wcl_course_single_slug_flushed',
		'wcl_category_slug_flushed',
		'wcl_program_slug_flushed',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}
}

/**
 * Delete the plugin's options on every site.
 */
function uninstall() {
	if ( ! is_multisite() ) {
		delete_options();
		return;
	}

	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $site_id ) {
		switch_to_blog( $site_id );
		delete_options();
		restore_current_blog();
	}
}

uninstall();
