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
			wp_enqueue_script( 'wp-course-listings-admin', plugin_dir_url( PLUGIN_FILE ) . 'admin/js/wp-course-listings-admin.js', array( 'jquery', 'wp-i18n' ), VERSION, true );
			wp_set_script_translations( 'wp-course-listings-admin', 'wp-course-listings', PLUGIN_DIR . 'languages' );
		}
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
	 * Program picker shown in place of the default taxonomy box in the classic editor.
	 *
	 * @param \WP_Post $post Course being edited.
	 * @param array    $box  Meta box arguments.
	 */
	public static function render_program_meta_box( $post, $box ) {
		$taxonomy = $box['args']['taxonomy'];

		$terms           = wp_get_object_terms( $post->ID, $taxonomy );
		$current_program = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0]->term_id : 0;

		$programs = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		?>
		<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>" class="categorydiv">
			<label for="<?php echo esc_attr( $taxonomy ); ?>-select"><strong><?php esc_html_e( 'Select Program:', 'wp-course-listings' ); ?></strong></label><br>
			<select name="tax_input[<?php echo esc_attr( $taxonomy ); ?>][]" id="<?php echo esc_attr( $taxonomy ); ?>-select" class="wcl-program-select">
				<option value=""><?php esc_html_e( 'No Program', 'wp-course-listings' ); ?></option>
				<?php if ( ! empty( $programs ) && ! is_wp_error( $programs ) ) : ?>
					<?php foreach ( $programs as $program ) : ?>
						<option value="<?php echo esc_attr( $program->term_id ); ?>" <?php selected( $current_program, $program->term_id ); ?>>
							<?php echo esc_html( $program->name ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Select the program this course belongs to.', 'wp-course-listings' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Course Details meta box: duration, level and order within the program.
	 *
	 * @param \WP_Post $post Course being edited.
	 */
	public function render_course_details_meta_box( $post ) {
		wp_nonce_field( 'wcl_save_course_meta', 'wcl_course_meta_nonce' );

		$duration      = get_post_meta( $post->ID, '_wcl_duration', true );
		$level         = get_post_meta( $post->ID, '_wcl_level', true );
		$program_order = get_post_meta( $post->ID, '_wcl_program_order', true );
		?>
		<p>
			<label for="wcl_duration"><strong><?php esc_html_e( 'Duration:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="text" name="wcl_duration" id="wcl_duration" value="<?php echo esc_attr( $duration ); ?>" class="widefat" />
		</p>
		<p>
			<label for="wcl_level"><strong><?php esc_html_e( 'Level:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="text" name="wcl_level" id="wcl_level" value="<?php echo esc_attr( $level ); ?>" class="widefat" />
		</p>
		<p>
			<label for="wcl_program_order"><strong><?php esc_html_e( 'Order in Program:', 'wp-course-listings' ); ?></strong></label><br>
			<input type="number" name="wcl_program_order" id="wcl_program_order" value="<?php echo esc_attr( absint( $program_order ) ); ?>" min="0" step="1" class="wcl-program-order" />
		</p>
		<p class="description"><?php esc_html_e( 'Set the order of this course within its program (0 = first, higher numbers appear later).', 'wp-course-listings' ); ?></p>
		<?php
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

		if ( isset( $_POST['wcl_program_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in can_save().
			update_post_meta( $post_id, '_wcl_program_order', absint( wp_unslash( $_POST['wcl_program_order'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in can_save().
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
