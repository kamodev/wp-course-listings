<?php
/**
 * Course data helpers.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Read access to a course's program and prerequisites.
 */
final class Course {

	/**
	 * Post meta key holding the IDs of prerequisite courses.
	 */
	const PREREQUISITES_KEY = '_wcl_prerequisites';

	/**
	 * IDs of the courses required before this one, in the order they were added.
	 *
	 * @param int $course_id Course ID.
	 * @return int[]
	 */
	public static function get_prerequisite_ids( $course_id ) {
		$ids = get_post_meta( $course_id, self::PREREQUISITES_KEY, true );

		return is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : array();
	}

	/**
	 * Published prerequisite courses.
	 *
	 * @param int $course_id Course ID.
	 * @return \WP_Post[]
	 */
	public static function get_prerequisites( $course_id ) {
		$courses = array();

		foreach ( self::get_prerequisite_ids( $course_id ) as $id ) {
			$course = get_post( $id );
			if ( $course && 'course' === $course->post_type && 'publish' === $course->post_status ) {
				$courses[] = $course;
			}
		}

		return $courses;
	}

	/**
	 * The program a course belongs to.
	 *
	 * Courses normally have one program; if several are assigned the first is used.
	 *
	 * @param int $course_id Course ID.
	 * @return \WP_Term|null
	 */
	public static function get_program( $course_id ) {
		$terms = get_the_terms( $course_id, 'course_program' );

		return $terms && ! is_wp_error( $terms ) ? reset( $terms ) : null;
	}

	/**
	 * Markup listing a course's prerequisites as links, or an empty string if it has none.
	 *
	 * @param int $course_id Course ID.
	 * @return string
	 */
	public static function prerequisites_html( $course_id ) {
		$prerequisites = self::get_prerequisites( $course_id );
		if ( ! $prerequisites ) {
			return '';
		}

		$links = array();
		foreach ( $prerequisites as $prerequisite ) {
			$links[] = '<a href="' . esc_url( get_permalink( $prerequisite ) ) . '">' . wp_kses_post( get_the_title( $prerequisite ) ) . '</a>';
		}

		return '<div class="wcl-course-prerequisites"><strong>' . esc_html__( 'Prerequisites:', 'wp-course-listings' ) . '</strong> ' . implode( ', ', $links ) . '</div>';
	}
}
