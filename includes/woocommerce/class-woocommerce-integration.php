<?php
/**
 * Optional WooCommerce integration.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Sells courses and program packages through WooCommerce when it is active.
 */
final class WooCommerce_Integration {

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Hook into WordPress and WooCommerce.
	 */
	public function init() {
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );

		if ( ! self::is_available() ) {
			return;
		}

		( new Course_Product() )->init();
		( new Program_Package() )->init();

		add_filter( 'wcl_course_purchase_html', array( $this, 'course_purchase_html' ), 10, 2 );
		add_filter( 'wcl_course_listing_meta', array( $this, 'course_purchase_html' ), 20, 2 );
		add_filter( 'wcl_program_package_html', array( $this, 'program_package_html' ), 10, 2 );
		add_filter( 'get_the_archive_description', array( $this, 'program_archive_package' ) );

		add_action( 'woocommerce_single_product_summary', array( $this, 'product_course_details' ), 25 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_package_courses_to_order_item' ), 10, 3 );
	}

	/**
	 * Declare compatibility with WooCommerce's order storage and block checkout.
	 */
	public function declare_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PLUGIN_FILE, true );
		}
	}

	/**
	 * Price and booking button for a course that is sold as a product.
	 *
	 * @param string $html      Markup so far.
	 * @param int    $course_id Course ID.
	 * @return string
	 */
	public function course_purchase_html( $html, $course_id ) {
		if ( ! Course_Product::is_sold( $course_id ) ) {
			return $html;
		}

		$product = Course_Product::get_product( $course_id );
		if ( ! $product || 'publish' !== $product->get_status() ) {
			return $html;
		}

		wp_enqueue_style( Frontend::STYLE_HANDLE );

		return $html . '<div class="wcl-course-purchase">' . $this->price_and_button( $product ) . '</div>';
	}

	/**
	 * Package offer for a program that is sold as a package.
	 *
	 * @param string $html       Markup so far.
	 * @param int    $program_id Program term ID.
	 * @return string
	 */
	public function program_package_html( $html, $program_id ) {
		if ( ! Program_Package::is_enabled( $program_id ) ) {
			return $html;
		}

		$product = Program_Package::get_product( $program_id );
		if ( ! $product || 'publish' !== $product->get_status() ) {
			return $html;
		}

		wp_enqueue_style( Frontend::STYLE_HANDLE );

		$count = count( (array) $product->get_meta( Program_Package::COURSES_KEY ) );

		/* translators: %d: number of courses in the program package. */
		$title = sprintf( _n( 'Get the full program: %d course', 'Get the full program: %d courses', $count, 'wp-course-listings' ), $count );

		return $html . '<div class="wcl-program-package"><p class="wcl-program-package-title">' . esc_html( $title ) . '</p>' . $this->price_and_button( $product ) . '</div>';
	}

	/**
	 * Show the package offer in a program archive's description.
	 *
	 * @param string $description Archive description.
	 * @return string
	 */
	public function program_archive_package( $description ) {
		if ( ! is_tax( 'course_program' ) ) {
			return $description;
		}

		return $this->program_package_html( $description, get_queried_object_id() );
	}

	/**
	 * On product pages, link to the course a product sells or list the courses a package includes.
	 */
	public function product_course_details() {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$course_id = absint( $product->get_meta( Course_Product::COURSE_KEY ) );
		if ( $course_id && 'publish' === get_post_status( $course_id ) ) {
			printf(
				'<p class="wcl-product-course"><a href="%s">%s</a></p>',
				esc_url( get_permalink( $course_id ) ),
				esc_html__( 'View course details', 'wp-course-listings' )
			);
			return;
		}

		$courses = $this->package_courses( $product );
		if ( $courses ) {
			echo '<div class="wcl-product-package-courses"><p><strong>' . esc_html__( 'This program includes:', 'wp-course-listings' ) . '</strong></p><ul>';
			foreach ( $courses as $course ) {
				printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $course ) ), wp_kses_post( get_the_title( $course ) ) );
			}
			echo '</ul></div>';
		}
	}

	/**
	 * Record which courses a package included on its order line item.
	 *
	 * @param \WC_Order_Item_Product $item          Order line item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values        Cart item data.
	 */
	public function add_package_courses_to_order_item( $item, $cart_item_key, $values ) {
		$product = isset( $values['data'] ) ? $values['data'] : null;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$courses = $this->package_courses( $product );
		if ( $courses ) {
			$item->add_meta_data( __( 'Included courses', 'wp-course-listings' ), implode( ', ', wp_list_pluck( $courses, 'post_title' ) ) );
		}
	}

	/**
	 * Courses included in a package product.
	 *
	 * @param \WC_Product $product Product.
	 * @return \WP_Post[]
	 */
	private function package_courses( $product ) {
		if ( ! $product->get_meta( Program_Package::PROGRAM_KEY ) ) {
			return array();
		}

		$courses = array();
		foreach ( (array) $product->get_meta( Program_Package::COURSES_KEY ) as $course_id ) {
			$course = get_post( $course_id );
			if ( $course && 'course' === $course->post_type ) {
				$courses[] = $course;
			}
		}

		return $courses;
	}

	/**
	 * Price and add-to-cart (or booking) button for a product.
	 *
	 * Appointment products link to the product page, where a time is chosen.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	private function price_and_button( $product ) {
		return sprintf(
			'<span class="wcl-price">%s</span> <a href="%s" class="button wcl-buy-button" rel="nofollow">%s</a>',
			wp_kses_post( $product->get_price_html() ),
			esc_url( $product->add_to_cart_url() ),
			esc_html( $product->add_to_cart_text() )
		);
	}
}
