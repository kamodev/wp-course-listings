<?php
/**
 * The [course_listings] shortcode.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders course listings via the [course_listings] shortcode.
 */
final class Shortcode {

	/**
	 * Shortcode tag.
	 */
	const TAG = 'course_listings';

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'               => Options::get( 'wcl_courses_per_page' ),
				'category'            => '',
				'program'             => '',
				'order_by_program'    => false,
				'show_featured_image' => Options::get( 'wcl_show_featured_image' ),
				'show_excerpt'        => Options::get( 'wcl_show_excerpt' ),
				'show_categories'     => Options::get( 'wcl_show_categories' ),
				'show_programs'       => Options::get( 'wcl_show_programs' ),
				'show_meta'           => Options::get( 'wcl_show_course_meta' ),
			),
			$atts,
			self::TAG
		);

		foreach ( array( 'order_by_program', 'show_featured_image', 'show_excerpt', 'show_categories', 'show_programs', 'show_meta' ) as $flag ) {
			$atts[ $flag ] = wp_validate_boolean( $atts[ $flag ] );
		}

		wp_enqueue_style( Frontend::STYLE_HANDLE );

		$query = new \WP_Query( $this->query_args( $atts ) );

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No courses found.', 'wp-course-listings' ) . '</p>';
		}

		$output = '<div class="wcl-course-listings">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$output .= $this->render_course( get_the_ID(), $atts );
		}
		$output .= '</div>';

		wp_reset_postdata();

		return $output;
	}

	/**
	 * Build WP_Query arguments from the shortcode attributes.
	 *
	 * @param array $atts Normalized shortcode attributes.
	 * @return array
	 */
	private function query_args( $atts ) {
		$args = array(
			'post_type'      => 'course',
			'posts_per_page' => intval( $atts['limit'] ),
			'post_status'    => 'publish',
		);

		$tax_query = array();

		if ( ! empty( $atts['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'course_category',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['category'] ),
			);
		}

		if ( ! empty( $atts['program'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'course_program',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['program'] ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		if ( $atts['order_by_program'] ) {
			$args['meta_key'] = '_wcl_program_order'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = array(
				'meta_value_num' => 'ASC',
				'title'          => 'ASC',
			);
		}

		return $args;
	}

	/**
	 * Render a single course card.
	 *
	 * @param int   $post_id Course ID.
	 * @param array $atts    Normalized shortcode attributes.
	 * @return string
	 */
	private function render_course( $post_id, $atts ) {
		$output = '<div class="wcl-course">';

		if ( $atts['show_featured_image'] && has_post_thumbnail( $post_id ) ) {
			$output .= '<div class="wcl-course-thumb">' . get_the_post_thumbnail( $post_id, 'medium' ) . '</div>';
		}

		$output .= '<h2 class="wcl-course-title"><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . wp_kses_post( get_the_title( $post_id ) ) . '</a></h2>';

		if ( $atts['show_excerpt'] ) {
			$output .= '<div class="wcl-course-excerpt">' . wp_kses_post( get_the_excerpt( $post_id ) ) . '</div>';
		}

		if ( $atts['show_meta'] ) {
			$output .= $this->render_meta( $post_id );
		}

		if ( $atts['show_categories'] ) {
			$categories = get_the_terms( $post_id, 'course_category' );
			if ( $categories && ! is_wp_error( $categories ) ) {
				$output .= '<div class="wcl-course-categories"><strong>' . esc_html__( 'Categories:', 'wp-course-listings' ) . '</strong> ';
				$output .= implode( ', ', array_map( array( $this, 'term_link' ), $categories ) );
				$output .= '</div>';
			}
		}

		if ( $atts['show_programs'] ) {
			$programs = get_the_terms( $post_id, 'course_program' );
			if ( $programs && ! is_wp_error( $programs ) ) {
				$program_order = get_post_meta( $post_id, '_wcl_program_order', true );

				$output .= '<div class="wcl-course-programs"><strong>' . esc_html__( 'Program:', 'wp-course-listings' ) . '</strong> ';
				$output .= implode( ', ', array_map( array( $this, 'term_link' ), $programs ) );
				if ( ! empty( $program_order ) ) {
					/* translators: %d: position of the course within its program, starting at 1. */
					$output .= ' <span class="wcl-program-order">' . esc_html( sprintf( __( '(Course #%d)', 'wp-course-listings' ), intval( $program_order ) + 1 ) ) . '</span>';
				}
				$output .= '</div>';
			}
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render the duration and level line.
	 *
	 * @param int $post_id Course ID.
	 * @return string
	 */
	private function render_meta( $post_id ) {
		$duration = get_post_meta( $post_id, '_wcl_duration', true );
		$level    = get_post_meta( $post_id, '_wcl_level', true );

		if ( ! $duration && ! $level ) {
			return '';
		}

		$output = '<div class="wcl-course-meta">';
		if ( $duration ) {
			$output .= '<span class="wcl-course-duration"><strong>' . esc_html__( 'Duration:', 'wp-course-listings' ) . '</strong> ' . esc_html( $duration ) . '</span>';
		}
		if ( $level ) {
			if ( $duration ) {
				$output .= ' | ';
			}
			$output .= '<span class="wcl-course-level"><strong>' . esc_html__( 'Level:', 'wp-course-listings' ) . '</strong> ' . esc_html( $level ) . '</span>';
		}
		$output .= '</div>';

		return $output;
	}

	/**
	 * Render a link to a term archive.
	 *
	 * @param \WP_Term $term Term.
	 * @return string
	 */
	private function term_link( $term ) {
		return '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
	}
}
