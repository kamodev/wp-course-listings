<?php
/**
 * WooCommerce product for a course.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps a WooCommerce product in step with each course that is sold.
 *
 * The course is the source of truth for the product's name, price, image
 * and status. When WooCommerce Appointments is active the product is an
 * Appointment product, so availability, staff and scheduling are set on
 * the product itself; otherwise it is a virtual simple product.
 *
 * Products are never deleted. When a course stops being sold, is trashed
 * or deleted, its product is set to draft.
 */
final class Course_Product {

	/**
	 * Course meta: '1' when the course is sold as a product.
	 */
	const SELL_KEY = '_wcl_sell_as_product';

	/**
	 * Course meta: price as a decimal string.
	 */
	const PRICE_KEY = '_wcl_price';

	/**
	 * Course meta: linked product ID.
	 */
	const PRODUCT_KEY = '_wcl_product_id';

	/**
	 * Product meta: ID of the course the product sells.
	 */
	const COURSE_KEY = '_wcl_course_id';

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		add_action( 'save_post_course', array( $this, 'sync' ), 20 );
		add_action( 'trashed_post', array( $this, 'sync' ) );
		add_action( 'untrashed_post', array( $this, 'sync' ) );
		add_action( 'before_delete_post', array( $this, 'unlink' ) );
	}

	/**
	 * Whether the course is sold through WooCommerce.
	 *
	 * @param int $course_id Course ID.
	 * @return bool
	 */
	public static function is_sold( $course_id ) {
		return (bool) get_post_meta( $course_id, self::SELL_KEY, true );
	}

	/**
	 * The course's price as a decimal string, or an empty string if none is set.
	 *
	 * @param int $course_id Course ID.
	 * @return string
	 */
	public static function get_price( $course_id ) {
		return (string) get_post_meta( $course_id, self::PRICE_KEY, true );
	}

	/**
	 * The product linked to a course.
	 *
	 * @param int $course_id Course ID.
	 * @return \WC_Product|null
	 */
	public static function get_product( $course_id ) {
		$product_id = absint( get_post_meta( $course_id, self::PRODUCT_KEY, true ) );
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		return $product ? $product : null;
	}

	/**
	 * Product type used for new course products.
	 *
	 * @return string
	 */
	public static function product_type() {
		$type = class_exists( 'WC_Product_Appointment' ) ? 'appointment' : 'simple';

		/**
		 * Filters the WooCommerce product type created for a course.
		 *
		 * @param string $type 'appointment' when WooCommerce Appointments is active, otherwise 'simple'.
		 */
		return apply_filters( 'wcl_course_product_type', $type );
	}

	/**
	 * Create or update the course's product to match the course.
	 *
	 * @param int $post_id Course ID.
	 */
	public function sync( $post_id ) {
		$course = get_post( $post_id );
		if ( ! $course || 'course' !== $course->post_type || wp_is_post_revision( $course ) || wp_is_post_autosave( $course ) ) {
			return;
		}

		$product = self::get_product( $post_id );

		if ( ! self::is_sold( $post_id ) || 'trash' === $course->post_status ) {
			if ( $product && 'draft' !== $product->get_status() ) {
				$product->set_status( 'draft' );
				$product->save();
			}
			return;
		}

		if ( ! $product ) {
			$product = self::new_product();
			$product->set_short_description( $course->post_excerpt );
		}

		$price = self::get_price( $post_id );

		$product->set_name( $course->post_title );
		$product->set_status( '' === $price ? 'draft' : self::product_status( $course->post_status ) );
		$product->set_regular_price( $price );
		$product->set_virtual( true );
		$product->set_image_id( get_post_thumbnail_id( $course ) );
		$product->update_meta_data( self::COURSE_KEY, $post_id );

		// WooCommerce Appointments prices appointments from the base cost.
		if ( method_exists( $product, 'set_cost' ) ) {
			$product->set_cost( $price );
		}

		/**
		 * Fires before a course's product is saved, for setting extra product properties.
		 *
		 * @param \WC_Product $product Product being saved.
		 * @param \WP_Post    $course  Course the product sells.
		 */
		do_action( 'wcl_before_course_product_save', $product, $course );

		update_post_meta( $post_id, self::PRODUCT_KEY, $product->save() );
	}

	/**
	 * Set a course's product to draft when the course is deleted.
	 *
	 * @param int $post_id Post being deleted.
	 */
	public function unlink( $post_id ) {
		if ( 'course' !== get_post_type( $post_id ) ) {
			return;
		}

		$product = self::get_product( $post_id );
		if ( $product ) {
			$product->set_status( 'draft' );
			$product->delete_meta_data( self::COURSE_KEY );
			$product->save();
		}
	}

	/**
	 * Create an unsaved product of the configured type.
	 *
	 * @return \WC_Product
	 */
	private static function new_product() {
		$classname = \WC_Product_Factory::get_product_classname( 0, self::product_type() );

		if ( ! $classname || ! class_exists( $classname ) ) {
			$classname = 'WC_Product_Simple';
		}

		return new $classname();
	}

	/**
	 * Product status matching a course status.
	 *
	 * @param string $course_status Course post status.
	 * @return string
	 */
	private static function product_status( $course_status ) {
		return in_array( $course_status, array( 'publish', 'private' ), true ) ? $course_status : 'draft';
	}
}
