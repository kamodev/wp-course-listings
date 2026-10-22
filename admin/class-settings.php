<?php
/**
 * Settings page.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin settings and renders Settings → Course Listings.
 */
final class Settings {

	/**
	 * Settings page slug.
	 */
	const PAGE = 'wcl-settings';

	/**
	 * Settings group used by options.php.
	 */
	const GROUP = 'wcl_settings_group';

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PLUGIN_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Add the settings page under Settings.
	 */
	public function add_page() {
		add_options_page(
			__( 'WP Course Listings Settings', 'wp-course-listings' ),
			__( 'Course Listings', 'wp-course-listings' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue styles for the settings page.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_' . self::PAGE === $hook_suffix ) {
			wp_enqueue_style( 'wp-course-listings-admin', plugin_dir_url( PLUGIN_FILE ) . 'admin/css/wp-course-listings-admin.css', array(), VERSION );
		}
	}

	/**
	 * Add a Settings link to the plugin's row on the Plugins screen.
	 *
	 * @param string[] $links Action links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wp-course-listings' ) . '</a>' );

		return $links;
	}

	/**
	 * Settings sections and their fields.
	 *
	 * @return array<string, array>
	 */
	private function sections() {
		return array(
			'wcl_url_settings_section'     => array(
				'title'       => __( 'URL Settings', 'wp-course-listings' ),
				'description' => sprintf(
					/* translators: %s: link to the Permalinks settings screen. */
					__( 'Configure URL structures for courses and categories. <strong>Important:</strong> After changing any URL settings, you must visit %s and click "Save Changes" to update your site\'s rewrite rules.', 'wp-course-listings' ),
					'<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Settings → Permalinks', 'wp-course-listings' ) . '</a>'
				),
				'fields'      => array(
					'wcl_courses_slug'       => array(
						'label'       => __( 'Courses Archive URL Slug', 'wp-course-listings' ),
						'type'        => 'slug',
						/* translators: %s: example URL path. */
						'description' => __( 'Used for the courses archive page URL, e.g., %s', 'wp-course-listings' ),
						'example'     => '/%s/',
					),
					'wcl_course_single_slug' => array(
						'label'       => __( 'Single Course URL Slug', 'wp-course-listings' ),
						'type'        => 'slug',
						/* translators: %s: example URL path. */
						'description' => __( 'Used for individual course URLs, e.g., %s', 'wp-course-listings' ),
						'example'     => '/%s/course-name/',
					),
					'wcl_category_slug'      => array(
						'label'       => __( 'Course Category URL Slug', 'wp-course-listings' ),
						'type'        => 'slug',
						/* translators: %s: example URL path. */
						'description' => __( 'Used for course category URLs, e.g., %s', 'wp-course-listings' ),
						'example'     => '/%s/category-name/',
					),
					'wcl_program_slug'       => array(
						'label'       => __( 'Course Program URL Slug', 'wp-course-listings' ),
						'type'        => 'slug',
						/* translators: %s: example URL path. */
						'description' => __( 'Used for course program URLs, e.g., %s', 'wp-course-listings' ),
						'example'     => '/%s/program-name/',
					),
				),
			),
			'wcl_display_settings_section' => array(
				'title'       => __( 'Display Settings', 'wp-course-listings' ),
				'description' => __( 'Control what information is displayed in course listings and archives.', 'wp-course-listings' ),
				'fields'      => array(
					'wcl_courses_per_page'    => array(
						'label'       => __( 'Courses Per Page', 'wp-course-listings' ),
						'type'        => 'number',
						'description' => __( 'Number of courses to display per page in archives and listings.', 'wp-course-listings' ),
					),
					'wcl_show_featured_image' => array(
						'label'       => __( 'Show Featured Images', 'wp-course-listings' ),
						'type'        => 'checkbox',
						'description' => __( 'Show featured images in course listings', 'wp-course-listings' ),
					),
					'wcl_show_excerpt'        => array(
						'label'       => __( 'Show Course Excerpts', 'wp-course-listings' ),
						'type'        => 'checkbox',
						'description' => __( 'Show course excerpts in listings', 'wp-course-listings' ),
					),
					'wcl_show_categories'     => array(
						'label'       => __( 'Show Course Categories', 'wp-course-listings' ),
						'type'        => 'checkbox',
						'description' => __( 'Show course categories in listings', 'wp-course-listings' ),
					),
					'wcl_show_course_meta'    => array(
						'label'       => __( 'Show Course Meta (Duration, Level)', 'wp-course-listings' ),
						'type'        => 'checkbox',
						'description' => __( 'Show course meta information (duration, level) in listings', 'wp-course-listings' ),
					),
					'wcl_show_programs'       => array(
						'label'       => __( 'Show Course Programs', 'wp-course-listings' ),
						'type'        => 'checkbox',
						'description' => __( 'Show course programs and program order in listings', 'wp-course-listings' ),
					),
				),
			),
			'wcl_archive_settings_section' => array(
				'title'       => __( 'Archive Page Settings', 'wp-course-listings' ),
				'description' => __( 'Customize the course archive page title and description.', 'wp-course-listings' ),
				'fields'      => array(
					'wcl_archive_title'       => array(
						'label'       => __( 'Archive Page Title', 'wp-course-listings' ),
						'type'        => 'text',
						'description' => __( 'Title displayed on the course archive page.', 'wp-course-listings' ),
					),
					'wcl_archive_description' => array(
						'label'       => __( 'Archive Page Description', 'wp-course-listings' ),
						'type'        => 'textarea',
						'description' => __( 'Optional description text displayed on the course archive page.', 'wp-course-listings' ),
					),
				),
			),
			'wcl_seo_settings_section'     => array(
				'title'       => __( 'SEO & Social Media Settings', 'wp-course-listings' ),
				'description' => __( 'Enable or disable SEO and social media meta fields for individual courses.', 'wp-course-listings' ),
				'fields'      => array(
					'wcl_enable_seo_meta'    => array(
						'label'       => __( 'Enable SEO Meta Fields', 'wp-course-listings' ),
						'type'        => 'checkbox',
						'description' => __( 'Enable SEO meta fields (title, description) for individual courses', 'wp-course-listings' ),
					),
					'wcl_enable_social_meta' => array(
						'label'       => __( 'Enable Social Media Meta Fields', 'wp-course-listings' ),
						'type'        => 'checkbox',
						'description' => __( 'Enable social media meta fields (Open Graph, Twitter) for individual courses', 'wp-course-listings' ),
					),
				),
			),
		);
	}

	/**
	 * Register settings, sections and fields.
	 */
	public function register_settings() {
		$defaults = Options::defaults();

		$setting_types = array(
			'slug'     => array( 'string', 'sanitize_title' ),
			'text'     => array( 'string', 'sanitize_text_field' ),
			'textarea' => array( 'string', 'sanitize_textarea_field' ),
			'number'   => array( 'integer', 'absint' ),
			'checkbox' => array( 'boolean', 'rest_sanitize_boolean' ),
		);

		foreach ( $this->sections() as $section_id => $section ) {
			add_settings_section(
				$section_id,
				$section['title'],
				static function () use ( $section ) {
					echo '<p>' . wp_kses_post( $section['description'] ) . '</p>';
				},
				self::PAGE
			);

			foreach ( $section['fields'] as $option => $field ) {
				list( $type, $sanitize_callback ) = $setting_types[ $field['type'] ];

				register_setting(
					self::GROUP,
					$option,
					array(
						'type'              => $type,
						'sanitize_callback' => $sanitize_callback,
						'default'           => $defaults[ $option ],
					)
				);

				$field['name'] = $option;

				add_settings_field(
					$option,
					$field['label'],
					array( $this, 'render_field' ),
					self::PAGE,
					$section_id,
					'checkbox' === $field['type'] ? $field : array_merge( $field, array( 'label_for' => $option ) )
				);
			}
		}
	}

	/**
	 * Render a settings field.
	 *
	 * @param array $field Field definition from sections().
	 */
	public function render_field( $field ) {
		$name  = $field['name'];
		$value = Options::get( $name );

		switch ( $field['type'] ) {
			case 'slug':
				printf( '<input type="text" name="%1$s" id="%1$s" value="%2$s" class="regular-text" />', esc_attr( $name ), esc_attr( $value ) );
				printf( '<p class="description">%s</p>', sprintf( esc_html( $field['description'] ), '<code>' . esc_html( sprintf( $field['example'], $value ) ) . '</code>' ) );
				return;

			case 'number':
				printf( '<input type="number" name="%1$s" id="%1$s" value="%2$s" class="small-text" min="1" max="100" />', esc_attr( $name ), esc_attr( $value ) );
				break;

			case 'checkbox':
				printf( '<label><input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s /> %3$s</label>', esc_attr( $name ), checked( $value, true, false ), esc_html( $field['description'] ) );
				return;

			case 'textarea':
				printf( '<textarea name="%1$s" id="%1$s" rows="3" class="large-text">%2$s</textarea>', esc_attr( $name ), esc_textarea( $value ) );
				break;

			default:
				printf( '<input type="text" name="%1$s" id="%1$s" value="%2$s" class="regular-text" />', esc_attr( $name ), esc_attr( $value ) );
		}

		printf( '<p class="description">%s</p>', esc_html( $field['description'] ) );
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings_group = self::GROUP;
		$settings_page  = self::PAGE;
		$version        = VERSION;
		$archive_slug   = Options::get( 'wcl_courses_slug' );
		$course_counts  = wp_count_posts( 'course' );
		$category_count = wp_count_terms( array( 'taxonomy' => 'course_category' ) );
		$program_count  = wp_count_terms( array( 'taxonomy' => 'course_program' ) );

		include PLUGIN_DIR . 'admin/partials/settings-page.php';
	}
}
