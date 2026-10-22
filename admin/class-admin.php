<?php
/**
 * Course editing screens.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Meta boxes, saving and the course list table.
 */
final class Admin {

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		add_action( 'add_meta_boxes_course', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_course', array( $this, 'save_course_details' ) );
		add_action( 'save_post_course', array( $this, 'save_seo_meta' ) );
		add_action( 'save_post_course', array( $this, 'save_social_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		add_filter( 'manage_course_posts_columns', array( $this, 'list_columns' ) );
		add_action( 'manage_course_posts_custom_column', array( $this, 'render_list_column' ), 10, 2 );
		add_filter( 'manage_edit-course_sortable_columns', array( $this, 'sortable_list_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'list_column_orderby' ) );
		add_action( 'restrict_manage_posts', array( $this, 'render_program_filter' ) );
	}

	/**
	 * Enqueue styles and scripts for the course edit screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || 'course' !== get_current_screen()->post_type ) {
			return;
		}

		wp_enqueue_style( 'wp-course-listings-admin', plugin_dir_url( PLUGIN_FILE ) . 'admin/css/wp-course-listings-admin.css', array(), VERSION );

		if ( Options::get( 'wcl_enable_social_meta' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_script(
			'wp-course-listings-admin',
			plugin_dir_url( PLUGIN_FILE ) . 'admin/js/wp-course-listings-admin.js',
			array( 'jquery', 'jquery-ui-autocomplete', 'wp-a11y', 'wp-api-fetch', 'wp-html-entities', 'wp-i18n', 'wp-url' ),
			VERSION,
			true
		);
		wp_set_script_translations( 'wp-course-listings-admin', 'wp-course-listings', PLUGIN_DIR . 'languages' );
	}

	/**
	 * Hide the block editor's program panel; programs are set in Course Details.
	 */
	public function enqueue_block_editor_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'course' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'wp-course-listings-editor',
			plugin_dir_url( PLUGIN_FILE ) . 'admin/js/wp-course-listings-editor.js',
			array( 'wp-data', 'wp-dom-ready', 'wp-edit-post' ),
			VERSION,
			true
		);
	}

	/**
	 * Register the course meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box( 'wcl_course_meta', __( 'Course Details', 'wp-course-listings' ), array( $this, 'render_course_details_meta_box' ), 'course', 'normal', 'default' );

		if ( Options::get( 'wcl_enable_seo_meta' ) ) {
			add_meta_box( 'wcl_seo_meta', __( 'SEO Metadata', 'wp-course-listings' ), array( $this, 'render_seo_meta_box' ), 'course', 'normal', 'default' );
		}

		if ( Options::get( 'wcl_enable_social_meta' ) ) {
			add_meta_box( 'wcl_social_meta', __( 'Social Media Metadata', 'wp-course-listings' ), array( $this, 'render_social_meta_box' ), 'course', 'normal', 'default' );
		}

		remove_meta_box( 'authordiv', 'course', 'normal' );
	}

	/**
	 * Course Details meta box: duration, level, program, order within the program and prerequisites.
	 *
	 * @param \WP_Post $post Course being edited.
	 */
	public function render_course_details_meta_box( $post ) {
		wp_nonce_field( 'wcl_save_course_meta', 'wcl_course_meta_nonce' );

		$duration      = get_post_meta( $post->ID, '_wcl_duration', true );
		$level         = get_post_meta( $post->ID, '_wcl_level', true );
		$program_order = get_post_meta( $post->ID, '_wcl_program_order', true );
		$program       = Course::get_program( $post->ID );

		$prerequisites = array();
		foreach ( Course::get_prerequisite_ids( $post->ID ) as $id ) {
			if ( 'course' === get_post_type( $id ) ) {
				$prerequisites[ $id ] = $this->course_label( $id );
			}
		}
		?>
		<p>
			<label for="wcl_duration"><strong><?php esc_html_e( 'Duration:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="text" name="wcl_duration" id="wcl_duration" value="<?php echo esc_attr( $duration ); ?>" class="widefat" />
		</p>
		<p>
			<label for="wcl_level"><strong><?php esc_html_e( 'Level:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="text" name="wcl_level" id="wcl_level" value="<?php echo esc_attr( $level ); ?>" class="widefat" />
		</p>
		<div class="wcl-field">
			<label for="wcl_program_search"><strong><?php esc_html_e( 'Program:', 'wp-course-listings' ); ?></strong></label>
			<input type="hidden" name="wcl_program_submitted" value="1" />
			<?php
			$this->render_lookup(
				array(
					'id'          => 'wcl_program_search',
					'name'        => 'wcl_program',
					'source'      => 'program',
					'multiple'    => false,
					'items'       => $program ? array( $program->term_id => $program->name ) : array(),
					'placeholder' => __( 'Search programs…', 'wp-course-listings' ),
				)
			);
			?>
			<p class="description"><?php esc_html_e( 'Optional. Start typing to find the program this course belongs to.', 'wp-course-listings' ); ?></p>
		</div>
		<p>
			<label for="wcl_program_order"><strong><?php esc_html_e( 'Order in Program:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="number" name="wcl_program_order" id="wcl_program_order" value="<?php echo esc_attr( absint( $program_order ) ); ?>" min="0" step="1" class="wcl-program-order" />
		</p>
		<p class="description"><?php esc_html_e( 'Set the order of this course within its program (0 = first, higher numbers appear later).', 'wp-course-listings' ); ?></p>
		<div class="wcl-field">
			<label for="wcl_prerequisites_search"><strong><?php esc_html_e( 'Prerequisites:', 'wp-course-listings' ); ?></strong></label>
			<input type="hidden" name="wcl_prerequisites_submitted" value="1" />
			<?php
			$this->render_lookup(
				array(
					'id'          => 'wcl_prerequisites_search',
					'name'        => 'wcl_prerequisites[]',
					'source'      => 'course',
					'multiple'    => true,
					'items'       => $prerequisites,
					'exclude'     => $post->ID,
					'placeholder' => __( 'Search courses…', 'wp-course-listings' ),
				)
			);
			?>
			<p class="description"><?php esc_html_e( 'Optional. Courses students should complete before this one.', 'wp-course-listings' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render a type-ahead field. Selected items are kept as hidden inputs named $args['name'].
	 *
	 * @param array $args {
	 *     Field arguments.
	 *
	 *     @type string $id          ID of the search input.
	 *     @type string $name        Name of the hidden inputs holding selected IDs.
	 *     @type string $source      What to search: 'course' or 'program'.
	 *     @type bool   $multiple    Whether more than one item can be selected.
	 *     @type array  $items       Selected items as ID => label.
	 *     @type int    $exclude     ID to leave out of search results.
	 *     @type string $placeholder Search input placeholder.
	 * }
	 */
	private function render_lookup( $args ) {
		$args = wp_parse_args( $args, array( 'exclude' => 0 ) );
		?>
		<div class="wcl-lookup" data-source="<?php echo esc_attr( $args['source'] ); ?>" data-name="<?php echo esc_attr( $args['name'] ); ?>" data-multiple="<?php echo $args['multiple'] ? '1' : '0'; ?>" data-exclude="<?php echo esc_attr( $args['exclude'] ); ?>">
			<ul class="wcl-lookup-selected">
				<?php foreach ( $args['items'] as $id => $label ) : ?>
					<li class="wcl-lookup-item">
						<span class="wcl-lookup-label"><?php echo esc_html( $label ); ?></span>
						<?php /* translators: %s: name of the selected course or program. */ ?>
						<button type="button" class="button-link wcl-lookup-remove" aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'wp-course-listings' ), $label ) ); ?>"><span aria-hidden="true">&times;</span></button>
						<input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $id ); ?>" />
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="text" id="<?php echo esc_attr( $args['id'] ); ?>" class="wcl-lookup-input widefat" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>" autocomplete="off" />
		</div>
		<?php
	}

	/**
	 * Label for a course in a type-ahead field, noting when it is not published.
	 *
	 * @param int $course_id Course ID.
	 * @return string
	 */
	private function course_label( $course_id ) {
		$label  = wp_strip_all_tags( get_the_title( $course_id ) );
		$status = get_post_status_object( get_post_status( $course_id ) );

		if ( $status && 'publish' !== $status->name ) {
			/* translators: 1: course title, 2: post status such as Draft. */
			$label = sprintf( __( '%1$s (%2$s)', 'wp-course-listings' ), $label, $status->label );
		}

		return $label;
	}

	/**
	 * SEO meta box.
	 *
	 * @param \WP_Post $post Course being edited.
	 */
	public function render_seo_meta_box( $post ) {
		wp_nonce_field( 'wcl_save_seo_meta', 'wcl_seo_meta_nonce' );

		$seo_title       = get_post_meta( $post->ID, '_wcl_seo_title', true );
		$seo_description = get_post_meta( $post->ID, '_wcl_seo_description', true );
		?>
		<p>
			<label for="wcl_seo_title"><strong><?php esc_html_e( 'SEO Title:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="text" name="wcl_seo_title" id="wcl_seo_title" value="<?php echo esc_attr( $seo_title ); ?>" class="widefat" maxlength="60" />
			<small><?php esc_html_e( 'Recommended: 50-60 characters.', 'wp-course-listings' ); ?></small>
		</p>
		<p>
			<label for="wcl_seo_description"><strong><?php esc_html_e( 'SEO Description:', 'wp-course-listings' ); ?></strong></label><br>
			<textarea name="wcl_seo_description" id="wcl_seo_description" class="widefat" rows="3" maxlength="160"><?php echo esc_textarea( $seo_description ); ?></textarea>
			<small><?php esc_html_e( 'Recommended: 120-160 characters.', 'wp-course-listings' ); ?></small>
		</p>
		<?php
	}

	/**
	 * Social media meta box.
	 *
	 * @param \WP_Post $post Course being edited.
	 */
	public function render_social_meta_box( $post ) {
		wp_nonce_field( 'wcl_save_social_meta', 'wcl_social_meta_nonce' );

		$og_title       = get_post_meta( $post->ID, '_wcl_og_title', true );
		$og_description = get_post_meta( $post->ID, '_wcl_og_description', true );
		$og_image       = get_post_meta( $post->ID, '_wcl_og_image', true );
		?>
		<p>
			<label for="wcl_og_title"><strong><?php esc_html_e( 'Open Graph Title:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="text" name="wcl_og_title" id="wcl_og_title" value="<?php echo esc_attr( $og_title ); ?>" class="widefat" maxlength="60" />
			<small><?php esc_html_e( 'Recommended: 50-60 characters.', 'wp-course-listings' ); ?></small>
		</p>
		<p>
			<label for="wcl_og_description"><strong><?php esc_html_e( 'Open Graph Description:', 'wp-course-listings' ); ?></strong></label><br>
			<textarea name="wcl_og_description" id="wcl_og_description" class="widefat" rows="3" maxlength="160"><?php echo esc_textarea( $og_description ); ?></textarea>
			<small><?php esc_html_e( 'Recommended: 120-160 characters.', 'wp-course-listings' ); ?></small>
		</p>
		<p>
			<label for="wcl_og_image"><strong><?php esc_html_e( 'Open Graph Image URL:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="text" name="wcl_og_image" id="wcl_og_image" value="<?php echo esc_url( $og_image ); ?>" class="widefat" />
			<small><?php esc_html_e( 'Recommended size: 1200x630px. You can paste a URL or use the "Add Media" button.', 'wp-course-listings' ); ?></small>
		</p>
		<p>
			<button type="button" class="button" id="wcl_og_image_upload"><?php esc_html_e( 'Add Media', 'wp-course-listings' ); ?></button>
		</p>
		<?php
	}

	/**
	 * Save the Course Details meta box.
	 *
	 * @param int $post_id Course ID.
	 */
	public function save_course_details( $post_id ) {
		if ( ! $this->can_save( $post_id, 'wcl_course_meta_nonce', 'wcl_save_course_meta' ) ) {
			return;
		}

		update_post_meta( $post_id, '_wcl_duration', $this->posted_text( 'wcl_duration' ) );
		update_post_meta( $post_id, '_wcl_level', $this->posted_text( 'wcl_level' ) );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in can_save().
		if ( isset( $_POST['wcl_program_order'] ) ) {
			update_post_meta( $post_id, '_wcl_program_order', absint( wp_unslash( $_POST['wcl_program_order'] ) ) );
		}

		if ( isset( $_POST['wcl_program_submitted'] ) ) {
			$this->save_program( $post_id, isset( $_POST['wcl_program'] ) ? absint( wp_unslash( $_POST['wcl_program'] ) ) : 0 );
		}

		if ( isset( $_POST['wcl_prerequisites_submitted'] ) ) {
			$ids = isset( $_POST['wcl_prerequisites'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['wcl_prerequisites'] ) ) : array();
			$this->save_prerequisites( $post_id, $ids );
		}
		// phpcs:enable
	}

	/**
	 * Assign the course to a program, or remove it from its program when $program_id is 0.
	 *
	 * Left alone when the program is unchanged, so extra programs added
	 * through Quick Edit are not dropped.
	 *
	 * @param int $post_id    Course ID.
	 * @param int $program_id Program term ID.
	 */
	private function save_program( $post_id, $program_id ) {
		$current = Course::get_program( $post_id );
		if ( ( $current ? $current->term_id : 0 ) === $program_id ) {
			return;
		}

		$terms = $program_id && term_exists( $program_id, 'course_program' ) ? array( $program_id ) : array();
		wp_set_object_terms( $post_id, $terms, 'course_program' );
	}

	/**
	 * Store the course's prerequisites, ignoring anything that is not another course.
	 *
	 * @param int   $post_id Course ID.
	 * @param int[] $ids     Prerequisite course IDs.
	 */
	private function save_prerequisites( $post_id, $ids ) {
		$ids = array_values(
			array_unique(
				array_filter(
					$ids,
					static function ( $id ) use ( $post_id ) {
						return $id && $id !== $post_id && 'course' === get_post_type( $id );
					}
				)
			)
		);

		if ( $ids ) {
			update_post_meta( $post_id, Course::PREREQUISITES_KEY, $ids );
		} else {
			delete_post_meta( $post_id, Course::PREREQUISITES_KEY );
		}
	}

	/**
	 * Save the SEO meta box.
	 *
	 * @param int $post_id Course ID.
	 */
	public function save_seo_meta( $post_id ) {
		if ( ! $this->can_save( $post_id, 'wcl_seo_meta_nonce', 'wcl_save_seo_meta' ) ) {
			return;
		}

		update_post_meta( $post_id, '_wcl_seo_title', $this->posted_text( 'wcl_seo_title' ) );
		update_post_meta( $post_id, '_wcl_seo_description', $this->posted_text( 'wcl_seo_description', 'sanitize_textarea_field' ) );
	}

	/**
	 * Save the social media meta box.
	 *
	 * @param int $post_id Course ID.
	 */
	public function save_social_meta( $post_id ) {
		if ( ! $this->can_save( $post_id, 'wcl_social_meta_nonce', 'wcl_save_social_meta' ) ) {
			return;
		}

		update_post_meta( $post_id, '_wcl_og_title', $this->posted_text( 'wcl_og_title' ) );
		update_post_meta( $post_id, '_wcl_og_description', $this->posted_text( 'wcl_og_description', 'sanitize_textarea_field' ) );
		update_post_meta( $post_id, '_wcl_og_image', $this->posted_text( 'wcl_og_image', 'esc_url_raw' ) );
	}

	/**
	 * Whether a meta box submission should be saved.
	 *
	 * @param int    $post_id      Course ID.
	 * @param string $nonce_field  Nonce field name.
	 * @param string $nonce_action Nonce action.
	 * @return bool
	 */
	private function can_save( $post_id, $nonce_field, $nonce_action ) {
		if ( ! isset( $_POST[ $nonce_field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), $nonce_action ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Read a submitted field, sanitized and slashed again for update_post_meta(), which unslashes its value.
	 *
	 * @param string   $key      Field name.
	 * @param callable $sanitize Sanitization callback.
	 * @return string
	 */
	private function posted_text( $key, $sanitize = 'sanitize_text_field' ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in can_save(); sanitized by $sanitize.
		if ( ! isset( $_POST[ $key ] ) ) {
			return '';
		}

		return wp_slash( call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) ) );
		// phpcs:enable
	}

	/**
	 * Replace the author column with program columns.
	 *
	 * @param string[] $columns List table columns.
	 * @return string[]
	 */
	public function list_columns( $columns ) {
		unset( $columns['author'] );

		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['course_program'] = __( 'Program', 'wp-course-listings' );
				$new_columns['program_order']  = __( 'Program Order', 'wp-course-listings' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render the program columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Course ID.
	 */
	public function render_list_column( $column, $post_id ) {
		switch ( $column ) {
			case 'course_program':
				$programs = get_the_terms( $post_id, 'course_program' );
				if ( $programs && ! is_wp_error( $programs ) ) {
					$links = array();
					foreach ( $programs as $program ) {
						$url     = add_query_arg( array( 'course_program' => $program->slug ), admin_url( 'edit.php?post_type=course' ) );
						$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $program->name ) . '</a>';
					}
					echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
				} else {
					echo '&#8212;';
				}
				break;

			case 'program_order':
				$order = get_post_meta( $post_id, '_wcl_program_order', true );
				echo ! empty( $order ) ? esc_html( intval( $order ) + 1 ) : '&#8212;';
				break;
		}
	}

	/**
	 * Make the program columns sortable.
	 *
	 * @param string[] $columns Sortable columns.
	 * @return string[]
	 */
	public function sortable_list_columns( $columns ) {
		$columns['program_order']  = 'program_order';
		$columns['course_program'] = 'course_program';

		return $columns;
	}

	/**
	 * Apply sorting by the program columns.
	 *
	 * @param \WP_Query $query Query being prepared.
	 */
	public function list_column_orderby( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'program_order' === $orderby ) {
			$query->set( 'meta_key', '_wcl_program_order' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'course_program' === $orderby ) {
			$query->set( 'orderby', 'taxonomy' );
			$query->set( 'taxonomy', 'course_program' );
		}
	}

	/**
	 * Program filter dropdown above the course list.
	 *
	 * @param string $post_type Post type being listed.
	 */
	public function render_program_filter( $post_type ) {
		if ( 'course' !== $post_type ) {
			return;
		}

		$taxonomy = 'course_program';
		$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		echo '<select name="' . esc_attr( $taxonomy ) . '" id="' . esc_attr( $taxonomy ) . '">';
		echo '<option value="">' . esc_html__( 'All Programs', 'wp-course-listings' ) . '</option>';
		foreach ( $terms as $term ) {
			printf(
				'<option value="%s"%s>%s (%d)</option>',
				esc_attr( $term->slug ),
				selected( $selected, $term->slug, false ),
				esc_html( $term->name ),
				absint( $term->count )
			);
		}
		echo '</select>';
	}
}
