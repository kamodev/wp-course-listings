<?php
/**
 * WooCommerce settings on course and program screens.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Admin fields for selling courses and program packages. Loaded only when WooCommerce is active.
 */
final class WooCommerce_Admin {

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		add_action( 'add_meta_boxes_course', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_course', array( $this, 'save_course' ) );

		add_action( 'course_program_add_form_fields', array( $this, 'render_program_add_fields' ) );
		add_action( 'course_program_edit_form_fields', array( $this, 'render_program_edit_fields' ) );
		add_action( 'created_course_program', array( $this, 'save_program' ) );
		add_action( 'edited_course_program', array( $this, 'save_program' ) );

		add_action( 'edit_form_top', array( $this, 'render_synced_product_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Load the admin stylesheet on the course program screens.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( $hook_suffix ) {
		$screen = get_current_screen();

		if ( in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) && $screen && 'course_program' === $screen->taxonomy ) {
			wp_enqueue_style( 'wp-course-listings-admin', plugin_dir_url( PLUGIN_FILE ) . 'admin/css/wp-course-listings-admin.css', array(), VERSION );
		}
	}

	/**
	 * Register the Sell This Course meta box.
	 */
	public function add_meta_box() {
		add_meta_box( 'wcl_course_product', __( 'Sell This Course', 'wp-course-listings' ), array( $this, 'render_meta_box' ), 'course', 'side', 'default' );
	}

	/**
	 * Sell This Course meta box.
	 *
	 * @param \WP_Post $post Course being edited.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'wcl_save_course_product', 'wcl_course_product_nonce' );

		$product = Course_Product::get_product( $post->ID );
		$type    = 'appointment' === Course_Product::product_type() ? __( 'Appointment', 'wp-course-listings' ) : __( 'Simple (virtual)', 'wp-course-listings' );
		?>
		<p>
			<label>
				<input type="checkbox" name="wcl_sell_as_product" value="1" <?php checked( Course_Product::is_sold( $post->ID ) ); ?> />
				<?php esc_html_e( 'Sell this course as a WooCommerce product', 'wp-course-listings' ); ?>
			</label>
		</p>
		<p>
			<?php /* translators: %s: currency symbol. */ ?>
			<label for="wcl_price"><strong><?php echo esc_html( sprintf( __( 'Price (%s):', 'wp-course-listings' ), html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ) ); ?></strong></label><br>
			<input type="text" name="wcl_price" id="wcl_price" value="<?php echo esc_attr( wc_format_localized_price( Course_Product::get_price( $post->ID ) ) ); ?>" class="widefat" inputmode="decimal" />
		</p>
		<?php /* translators: %s: WooCommerce product type, such as Appointment. */ ?>
		<p class="description"><?php echo esc_html( sprintf( __( 'Product type: %s. The product\'s name, price, image and status follow this course.', 'wp-course-listings' ), $type ) ); ?></p>
		<?php if ( $product ) : ?>
			<p>
				<a href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>"><?php esc_html_e( 'Edit product in WooCommerce', 'wp-course-listings' ); ?></a>
				<?php if ( $product->is_type( 'appointment' ) ) : ?>
					<br><span class="description"><?php esc_html_e( 'Set availability, duration and staff on the product.', 'wp-course-listings' ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save the Sell This Course meta box. Runs before Course_Product::sync().
	 *
	 * @param int $post_id Course ID.
	 */
	public function save_course( $post_id ) {
		if ( ! isset( $_POST['wcl_course_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcl_course_product_nonce'] ) ), 'wcl_save_course_product' ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, Course_Product::SELL_KEY, empty( $_POST['wcl_sell_as_product'] ) ? '' : '1' );
		update_post_meta( $post_id, Course_Product::PRICE_KEY, $this->posted_amount( 'wcl_price' ) );
	}

	/**
	 * Package fields on the Add New Course Program form.
	 */
	public function render_program_add_fields() {
		wp_nonce_field( 'wcl_save_program_package', 'wcl_program_package_nonce' );
		?>
		<div class="form-field">
			<label>
				<input type="checkbox" name="wcl_package_enabled" value="1" />
				<?php esc_html_e( 'Sell this program as a package', 'wp-course-listings' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Creates one WooCommerce product for the whole program, priced at the total of its courses that are sold as products.', 'wp-course-listings' ); ?></p>
		</div>
		<div class="form-field">
			<label for="wcl_package_discount"><?php esc_html_e( 'Package discount', 'wp-course-listings' ); ?></label>
			<?php
			$this->render_discount_inputs(
				array(
					'type'   => 'percent',
					'amount' => '',
				)
			);
			?>
			<p class="description"><?php esc_html_e( 'Optional. Taken off the total price of the program\'s courses.', 'wp-course-listings' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Package fields and summary on the Edit Course Program form.
	 *
	 * @param \WP_Term $program Program being edited.
	 */
	public function render_program_edit_fields( $program ) {
		$enabled = Program_Package::is_enabled( $program->term_id );
		$product = Program_Package::get_product( $program->term_id );
		$package = Program_Package::calculate( $program->term_id );
		?>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Package', 'wp-course-listings' ); ?></th>
			<td>
				<?php wp_nonce_field( 'wcl_save_program_package', 'wcl_program_package_nonce' ); ?>
				<label>
					<input type="checkbox" name="wcl_package_enabled" value="1" <?php checked( $enabled ); ?> />
					<?php esc_html_e( 'Sell this program as a package', 'wp-course-listings' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Creates one WooCommerce product for the whole program, priced at the total of its courses that are sold as products.', 'wp-course-listings' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="wcl_package_discount"><?php esc_html_e( 'Package discount', 'wp-course-listings' ); ?></label></th>
			<td>
				<?php $this->render_discount_inputs( Program_Package::get_discount( $program->term_id ) ); ?>
				<p class="description"><?php esc_html_e( 'Optional. Taken off the total price of the program\'s courses.', 'wp-course-listings' ); ?></p>
			</td>
		</tr>
		<?php if ( $enabled ) : ?>
			<tr class="form-field">
				<th scope="row"><?php esc_html_e( 'Package price', 'wp-course-listings' ); ?></th>
				<td>
					<?php if ( $package['courses'] ) : ?>
						<p>
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: 1: number of courses, 2: total of course prices, 3: discount, 4: package price. */
									_n( '%1$d course totalling %2$s, less %3$s discount: %4$s', '%1$d courses totalling %2$s, less %3$s discount: %4$s', count( $package['courses'] ), 'wp-course-listings' ),
									count( $package['courses'] ),
									wc_price( $package['total'] ),
									wc_price( $package['discount'] ),
									'<strong>' . wc_price( $package['price'] ) . '</strong>'
								)
							);
							?>
						</p>
					<?php else : ?>
						<p><?php esc_html_e( 'No published courses in this program are sold as products yet, so the package is not for sale.', 'wp-course-listings' ); ?></p>
					<?php endif; ?>
					<?php if ( $product ) : ?>
						<p><a href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>"><?php esc_html_e( 'Edit package product in WooCommerce', 'wp-course-listings' ); ?></a></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save the package fields and resync the package product.
	 *
	 * @param int $term_id Program term ID.
	 */
	public function save_program( $term_id ) {
		if ( ! isset( $_POST['wcl_program_package_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcl_program_package_nonce'] ) ), 'wcl_save_program_package' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		$type = isset( $_POST['wcl_package_discount_type'] ) && 'fixed' === sanitize_key( wp_unslash( $_POST['wcl_package_discount_type'] ) ) ? 'fixed' : 'percent';

		$amount = $this->posted_amount( 'wcl_package_discount' );
		if ( 'percent' === $type && '' !== $amount ) {
			$amount = wc_format_decimal( min( 100, (float) $amount ) );
		}

		update_term_meta( $term_id, Program_Package::ENABLED_KEY, empty( $_POST['wcl_package_enabled'] ) ? '' : '1' );
		update_term_meta( $term_id, Program_Package::DISCOUNT_TYPE_KEY, $type );
		update_term_meta( $term_id, Program_Package::DISCOUNT_KEY, $amount );

		Program_Package::sync( $term_id );
	}

	/**
	 * Explain on the product edit screen which fields are kept in step with a course or program.
	 *
	 * @param \WP_Post $post Post being edited.
	 */
	public function render_synced_product_notice( $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		$course_id  = absint( get_post_meta( $post->ID, Course_Product::COURSE_KEY, true ) );
		$program_id = absint( get_post_meta( $post->ID, Program_Package::PROGRAM_KEY, true ) );

		if ( $course_id ) {
			$message = sprintf(
				/* translators: %s: link to the course. */
				__( 'This product sells the course %s. Its name, price, image and status are updated from the course, so change them there.', 'wp-course-listings' ),
				'<a href="' . esc_url( get_edit_post_link( $course_id ) ) . '">' . esc_html( get_the_title( $course_id ) ) . '</a>'
			);
		} elseif ( $program_id && term_exists( $program_id, 'course_program' ) ) {
			$message = sprintf(
				/* translators: %s: link to the program. */
				__( 'This product sells the program %s as a package. Its name, prices and status are calculated from the program and its courses, so change them there.', 'wp-course-listings' ),
				'<a href="' . esc_url( get_edit_term_link( $program_id, 'course_program' ) ) . '">' . esc_html( get_term( $program_id )->name ) . '</a>'
			);
		} else {
			return;
		}

		echo '<div class="notice notice-info inline"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	/**
	 * Discount amount and type inputs.
	 *
	 * @param array $discount Discount settings from Program_Package::get_discount().
	 */
	private function render_discount_inputs( $discount ) {
		?>
		<input type="text" name="wcl_package_discount" id="wcl_package_discount" value="<?php echo esc_attr( wc_format_localized_decimal( $discount['amount'] ) ); ?>" class="wcl-discount-amount" inputmode="decimal" />
		<select name="wcl_package_discount_type" class="wcl-discount-type" aria-label="<?php esc_attr_e( 'Discount type', 'wp-course-listings' ); ?>">
			<option value="percent" <?php selected( $discount['type'], 'percent' ); ?>><?php esc_html_e( '% off', 'wp-course-listings' ); ?></option>
			<?php /* translators: %s: currency symbol. */ ?>
			<option value="fixed" <?php selected( $discount['type'], 'fixed' ); ?>><?php echo esc_html( sprintf( __( '%s off', 'wp-course-listings' ), html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ) ); ?></option>
		</select>
		<?php
	}

	/**
	 * Read a submitted price or discount as a non-negative decimal string, or an empty string.
	 *
	 * @param string $key Field name.
	 * @return string
	 */
	private function posted_amount( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by the caller.
		$value = isset( $_POST[ $key ] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : '';

		return '' === $value || (float) $value < 0 ? '' : $value;
	}
}
