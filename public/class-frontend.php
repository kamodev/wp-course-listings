<?php
/**
 * Front-end behavior.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end output: styles, SEO and social tags, archive tweaks and
 * removal of author and date information from course views.
 */
final class Frontend {

	/**
	 * Handle of the front-end stylesheet.
	 */
	const STYLE_HANDLE = 'wp-course-listings';

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		// Course details on single course pages.
		add_filter( 'wcl_course_listing_meta', array( $this, 'course_meta_html' ), 10, 2 );
		add_filter( 'wcl_course_listing_meta', array( $this, 'prerequisites_html' ), 15, 2 );
		add_filter( 'the_content', array( $this, 'append_course_meta' ) );

		// SEO and social media tags.
		add_filter( 'pre_get_document_title', array( $this, 'seo_document_title' ) );
		add_action( 'wp_head', array( $this, 'output_seo_meta_tags' ) );
		add_action( 'wp_head', array( $this, 'output_social_meta_tags' ) );

		// Archives.
		add_filter( 'get_the_archive_title', array( $this, 'archive_title' ) );
		add_filter( 'get_the_archive_description', array( $this, 'archive_description' ) );
		add_action( 'pre_get_posts', array( $this, 'archive_posts_per_page' ) );
		add_action( 'pre_get_posts', array( $this, 'program_archive_ordering' ) );

		// Author and date removal.
		add_action( 'wp', array( $this, 'hide_author_and_date' ) );
		add_filter( 'the_content', array( $this, 'filter_course_content' ), 20 );
		add_filter( 'the_excerpt', array( $this, 'filter_course_excerpt' ), 20 );
		add_filter( 'rest_prepare_course', array( $this, 'remove_author_from_rest' ), 10, 2 );
	}

	/**
	 * Whether the current request shows courses: a single course, the course
	 * archive, or a course category or program archive.
	 *
	 * @return bool
	 */
	public static function is_course_view() {
		return is_singular( 'course' ) || is_post_type_archive( 'course' ) || is_tax( array( 'course_category', 'course_program' ) );
	}

	/**
	 * Register the front-end stylesheet so the shortcode can enqueue it on any page.
	 */
	public function register_styles() {
		wp_register_style( self::STYLE_HANDLE, plugin_dir_url( PLUGIN_FILE ) . 'public/css/wp-course-listings.css', array(), VERSION );
	}

	/**
	 * Enqueue the stylesheet on course views.
	 */
	public function enqueue_styles() {
		if ( self::is_course_view() ) {
			wp_enqueue_style( self::STYLE_HANDLE );
		}
	}

	/**
	 * Default markup for the `wcl_course_listing_meta` filter: duration and level.
	 *
	 * @param string $output  Markup so far.
	 * @param int    $post_id Course ID.
	 * @return string
	 */
	public function course_meta_html( $output, $post_id ) {
		$duration = get_post_meta( $post_id, '_wcl_duration', true );
		$level    = get_post_meta( $post_id, '_wcl_level', true );

		if ( $duration ) {
			$output .= '<div class="wcl-course-duration"><strong>' . esc_html__( 'Duration:', 'wp-course-listings' ) . '</strong> ' . esc_html( $duration ) . '</div>';
		}
		if ( $level ) {
			$output .= '<div class="wcl-course-level"><strong>' . esc_html__( 'Level:', 'wp-course-listings' ) . '</strong> ' . esc_html( $level ) . '</div>';
		}

		return $output;
	}

	/**
	 * Add the course's prerequisites to the details shown on single course pages.
	 *
	 * @param string $output  Markup so far.
	 * @param int    $post_id Course ID.
	 * @return string
	 */
	public function prerequisites_html( $output, $post_id ) {
		return $output . Course::prerequisites_html( $post_id );
	}

	/**
	 * Append course details to the content of single course pages.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append_course_meta( $content ) {
		$post = get_post();

		if ( $post && 'course' === $post->post_type && is_singular( 'course' ) ) {
			/**
			 * Filters the course details shown below a single course's content.
			 *
			 * @param string $output  Markup to append. Default empty.
			 * @param int    $post_id Course ID.
			 */
			$content .= apply_filters( 'wcl_course_listing_meta', '', $post->ID );
		}

		return $content;
	}

	/**
	 * Use the course's SEO title as the document title.
	 *
	 * @param string $title Document title. Empty to let WordPress build it.
	 * @return string
	 */
	public function seo_document_title( $title ) {
		if ( is_singular( 'course' ) ) {
			$seo_title = get_post_meta( get_queried_object_id(), '_wcl_seo_title', true );
			if ( $seo_title ) {
				return $seo_title;
			}
		}

		return $title;
	}

	/**
	 * Output SEO meta tags on single course pages.
	 */
	public function output_seo_meta_tags() {
		if ( ! is_singular( 'course' ) ) {
			return;
		}

		$post_id         = get_queried_object_id();
		$seo_title       = get_post_meta( $post_id, '_wcl_seo_title', true );
		$seo_description = get_post_meta( $post_id, '_wcl_seo_description', true );

		if ( $seo_title ) {
			echo '<meta name="title" content="' . esc_attr( $seo_title ) . '">' . "\n";
		}
		if ( $seo_description ) {
			echo '<meta name="description" content="' . esc_attr( $seo_description ) . '">' . "\n";
		}
	}

	/**
	 * Output Open Graph and Twitter meta tags on single course pages.
	 */
	public function output_social_meta_tags() {
		if ( ! is_singular( 'course' ) ) {
			return;
		}

		$post_id        = get_queried_object_id();
		$og_title       = get_post_meta( $post_id, '_wcl_og_title', true );
		$og_description = get_post_meta( $post_id, '_wcl_og_description', true );
		$og_image       = get_post_meta( $post_id, '_wcl_og_image', true );

		if ( $og_title ) {
			echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
			echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">' . "\n";
		}
		if ( $og_description ) {
			echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '">' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr( $og_description ) . '">' . "\n";
		}
		if ( $og_image ) {
			echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '">' . "\n";
		}
		echo '<meta property="og:type" content="article">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	}

	/**
	 * Use the configured title on the course archive.
	 *
	 * @param string $title Archive title.
	 * @return string
	 */
	public function archive_title( $title ) {
		if ( is_post_type_archive( 'course' ) ) {
			$custom_title = Options::get( 'wcl_archive_title' );
			if ( ! empty( $custom_title ) ) {
				return $custom_title;
			}
		}

		return $title;
	}

	/**
	 * Use the configured description on the course archive.
	 *
	 * @param string $description Archive description.
	 * @return string
	 */
	public function archive_description( $description ) {
		if ( is_post_type_archive( 'course' ) ) {
			$custom_description = Options::get( 'wcl_archive_description' );
			if ( ! empty( $custom_description ) ) {
				return '<p>' . esc_html( $custom_description ) . '</p>';
			}
		}

		return $description;
	}

	/**
	 * Apply the configured page size to the course archive.
	 *
	 * @param \WP_Query $query Query being prepared.
	 */
	public function archive_posts_per_page( $query ) {
		if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'course' ) ) {
			$query->set( 'posts_per_page', Options::get( 'wcl_courses_per_page' ) );
		}
	}

	/**
	 * Order courses by their position in the program on program archives.
	 *
	 * @param \WP_Query $query Query being prepared.
	 */
	public function program_archive_ordering( $query ) {
		if ( ! is_admin() && $query->is_main_query() && $query->is_tax( 'course_program' ) ) {
			$query->set( 'meta_key', '_wcl_program_order' );
			$query->set(
				'orderby',
				array(
					'meta_value_num' => 'ASC',
					'title'          => 'ASC',
				)
			);
		}
	}

	/**
	 * Strip author and date information from course views.
	 */
	public function hide_author_and_date() {
		if ( ! self::is_course_view() ) {
			return;
		}

		$empty_filters = array(
			// Author information.
			'the_author',
			'get_the_author',
			'the_author_link',
			'get_the_author_link',
			'get_the_author_meta',
			// Dates.
			'the_date',
			'get_the_date',
			'the_time',
			'get_the_time',
			'get_the_modified_date',
			'get_the_modified_time',
			// Posted-on output from default themes.
			'twentytwentyone_posted_on',
			'twentytwenty_posted_on',
			'twentynineteen_posted_on',
			'twentyseventeen_posted_on',
			'twentysixteen_posted_on',
		);
		foreach ( $empty_filters as $filter ) {
			add_filter( $filter, '__return_empty_string' );
		}

		add_filter( 'post_class', array( $this, 'remove_author_post_classes' ) );
		add_filter( 'get_post_metadata', array( $this, 'hide_edit_metadata' ), 10, 3 );
	}

	/**
	 * Remove author-* classes from post classes.
	 *
	 * @param string[] $classes Post classes.
	 * @return string[]
	 */
	public function remove_author_post_classes( $classes ) {
		return array_filter(
			$classes,
			static function ( $css_class ) {
				return 0 !== strpos( $css_class, 'author-' );
			}
		);
	}

	/**
	 * Hide the last editor and edit lock of courses.
	 *
	 * @param mixed  $value     Short-circuit value. Null to read from the database.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @return mixed
	 */
	public function hide_edit_metadata( $value, $object_id, $meta_key ) {
		$post = get_post();

		if ( $post && 'course' === $post->post_type && in_array( $meta_key, array( '_edit_last', '_edit_lock' ), true ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Remove author and date text left in single course content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function filter_course_content( $content ) {
		if ( ! is_singular( 'course' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post || 'course' !== $post->post_type ) {
			return $content;
		}

		$patterns = array(
			'/Posted by[^<]*<[^>]*>[^<]*<\/[^>]*>/i',
			'/By[^<]*<[^>]*>[^<]*<\/[^>]*>/i',
			'/Published on[^<]*\d{4}/i',
			'/Posted on[^<]*\d{4}/i',
			'/<time[^>]*>.*?<\/time>/i',
			'/<span[^>]*class="[^"]*author[^"]*"[^>]*>.*?<\/span>/i',
			'/<span[^>]*class="[^"]*date[^"]*"[^>]*>.*?<\/span>/i',
		);

		return preg_replace( $patterns, '', $content );
	}

	/**
	 * Remove author and date text left in course excerpts.
	 *
	 * @param string $excerpt Post excerpt.
	 * @return string
	 */
	public function filter_course_excerpt( $excerpt ) {
		$post = get_post();
		if ( ! $post || 'course' !== $post->post_type ) {
			return $excerpt;
		}

		$patterns = array(
			'/Posted by[^<]*<[^>]*>[^<]*<\/[^>]*>/i',
			'/By[^<]*<[^>]*>[^<]*<\/[^>]*>/i',
			'/Published on[^<]*\d{4}/i',
			'/Posted on[^<]*\d{4}/i',
		);

		return preg_replace( $patterns, '', $excerpt );
	}

	/**
	 * Remove author and date fields from course REST API responses.
	 *
	 * @param \WP_REST_Response $response Response.
	 * @param \WP_Post          $post     Course.
	 * @return \WP_REST_Response
	 */
	public function remove_author_from_rest( $response, $post ) {
		if ( 'course' === $post->post_type ) {
			foreach ( array( 'author', 'author_name', 'date', 'date_gmt', 'modified', 'modified_gmt' ) as $field ) {
				unset( $response->data[ $field ] );
			}
		}

		return $response;
	}
}
