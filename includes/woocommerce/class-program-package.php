<?php
/**
 * WooCommerce package product for a course program.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Sells all of a program's courses as one virtual product.
 *
 * The package's regular price is the sum of the prices of the program's
 * published courses that are sold as products; the optional program
 * discount becomes the sale price. The package is recalculated whenever a
 * course's price, status or program changes, or the program's package
 * settings change.
 */
final class Program_Package {

	/**
	 * Term meta: '1' when the program is sold as a package.
	 */
	const ENABLED_KEY = '_wcl_package_enabled';

	/**
	 * Term meta: discount type, 'percent' or 'fixed'.
	 */
	const DISCOUNT_TYPE_KEY = '_wcl_package_discount_type';

	/**
	 * Term meta: discount amount as a decimal string.
	 */
	const DISCOUNT_KEY = '_wcl_package_discount';

	/**
	 * Term meta: linked package product ID.
	 */
	const PRODUCT_KEY = '_wcl_package_product_id';

	/**
	 * Product meta: ID of the program the package sells.
	 */
	const PROGRAM_KEY = '_wcl_program_id';

	/**
	 * Product meta: IDs of the courses included in the package.
	 */
	const COURSES_KEY = '_wcl_package_course_ids';

	/**
	 * Hook into WordPress.
	 */
	public function init() {
		add_action( 'save_post_course', array( $this, 'sync_course_programs' ), 30 );
		add_action( 'trashed_post', array( $this, 'sync_course_programs' ) );
		add_action( 'untrashed_post', array( $this, 'sync_course_programs' ) );
		add_action( 'set_object_terms', array( $this, 'sync_changed_programs' ), 10, 6 );
		add_action( 'deleted_term_relationships', array( $this, 'sync_removed_programs' ), 10, 3 );
		add_action( 'pre_delete_term', array( $this, 'unlink' ), 10, 2 );
	}

	/**
	 * Whether the program is sold as a package.
	 *
	 * @param int $program_id Program term ID.
	 * @return bool
	 */
	public static function is_enabled( $program_id ) {
		return (bool) get_term_meta( $program_id, self::ENABLED_KEY, true );
	}

	/**
	 * The program's discount settings.
	 *
	 * @param int $program_id Program term ID.
	 * @return array{type: string, amount: string}
	 */
	public static function get_discount( $program_id ) {
		$type = get_term_meta( $program_id, self::DISCOUNT_TYPE_KEY, true );

		return array(
			'type'   => 'fixed' === $type ? 'fixed' : 'percent',
			'amount' => (string) get_term_meta( $program_id, self::DISCOUNT_KEY, true ),
		);
	}

	/**
	 * The package product linked to a program.
	 *
	 * @param int $program_id Program term ID.
	 * @return \WC_Product|null
	 */
	public static function get_product( $program_id ) {
		$product_id = absint( get_term_meta( $program_id, self::PRODUCT_KEY, true ) );
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		return $product ? $product : null;
	}

	/**
	 * IDs of the program's published courses that are sold and have a price, in program order.
	 *
	 * @param int $program_id Program term ID.
	 * @return int[]
	 */
	public static function get_course_ids( $program_id ) {
		$query = new \WP_Query(
			array(
				'post_type'      => 'course',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy'         => 'course_program',
						'field'            => 'term_id',
						'terms'            => $program_id,
						'include_children' => false,
					),
				),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => Course_Product::SELL_KEY,
						'value' => '1',
					),
					array(
						'key'     => Course_Product::PRICE_KEY,
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

		$ids = array_map( 'intval', $query->posts );
		usort(
			$ids,
			static function ( $a, $b ) {
				return (int) get_post_meta( $a, '_wcl_program_order', true ) - (int) get_post_meta( $b, '_wcl_program_order', true );
			}
		);

		return $ids;
	}

	/**
	 * Work out the package's courses and prices.
	 *
	 * @param int $program_id Program term ID.
	 * @return array{courses: int[], total: string, discount: string, price: string}
	 */
	public static function calculate( $program_id ) {
		$courses = self::get_course_ids( $program_id );

		$total = 0.0;
		foreach ( $courses as $course_id ) {
			$total += (float) Course_Product::get_price( $course_id );
		}

		$discount_settings = self::get_discount( $program_id );
		$amount            = max( 0.0, (float) $discount_settings['amount'] );
		$discount          = 'fixed' === $discount_settings['type'] ? $amount : $total * min( $amount, 100.0 ) / 100;
		$discount          = min( $discount, $total );

		$decimals = wc_get_price_decimals();

		return array(
			'courses'  => $courses,
			'total'    => wc_format_decimal( $total, $decimals ),
			'discount' => wc_format_decimal( $discount, $decimals ),
			'price'    => wc_format_decimal( $total - $discount, $decimals ),
		);
	}

	/**
	 * Create or update the program's package product.
	 *
	 * @param int $program_id Program term ID.
	 */
	public static function sync( $program_id ) {
		$program = get_term( $program_id, 'course_program' );
		if ( ! $program instanceof \WP_Term ) {
			return;
		}

		$product = self::get_product( $program_id );

		if ( ! self::is_enabled( $program_id ) ) {
			if ( $product && 'draft' !== $product->get_status() ) {
				$product->set_status( 'draft' );
				$product->save();
			}
			return;
		}

		if ( ! $product ) {
			$product = new \WC_Product_Simple();
		}

		$package = self::calculate( $program_id );

		/* translators: %s: program name. */
		$product->set_name( sprintf( __( '%s – Full Program', 'wp-course-listings' ), $program->name ) );
		$product->set_status( $package['courses'] ? 'publish' : 'draft' );
		$product->set_virtual( true );
		$product->set_regular_price( $package['total'] );
		$product->set_sale_price( (float) $package['discount'] > 0 ? $package['price'] : '' );
		$product->update_meta_data( self::PROGRAM_KEY, $program_id );
		$product->update_meta_data( self::COURSES_KEY, $package['courses'] );

		/**
		 * Fires before a program's package product is saved, for setting extra product properties.
		 *
		 * @param \WC_Product $product Product being saved.
		 * @param \WP_Term    $program Program the package sells.
		 * @param array       $package Courses and prices from Program_Package::calculate().
		 */
		do_action( 'wcl_before_program_package_save', $product, $program, $package );

		update_term_meta( $program_id, self::PRODUCT_KEY, $product->save() );
	}

	/**
	 * Recalculate the packages of a course's programs after the course changes.
	 *
	 * @param int $post_id Course ID.
	 */
	public function sync_course_programs( $post_id ) {
		if ( 'course' !== get_post_type( $post_id ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$programs = get_the_terms( $post_id, 'course_program' );
		if ( $programs && ! is_wp_error( $programs ) ) {
			foreach ( $programs as $program ) {
				self::sync( $program->term_id );
			}
		}
	}

	/**
	 * Recalculate packages for programs a course was added to or removed from.
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      Terms passed in.
	 * @param int[]  $tt_ids     New term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy.
	 * @param bool   $append     Whether terms were appended.
	 * @param int[]  $old_tt_ids Previous term taxonomy IDs.
	 */
	public function sync_changed_programs( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( 'course_program' !== $taxonomy ) {
			return;
		}

		$tt_ids     = array_map( 'intval', $tt_ids );
		$old_tt_ids = array_map( 'intval', $old_tt_ids );
		$changed    = array_merge( array_diff( $tt_ids, $old_tt_ids ), array_diff( $old_tt_ids, $tt_ids ) );

		$this->sync_term_taxonomy_ids( $changed );
	}

	/**
	 * Recalculate packages for programs a course was removed from when it was deleted.
	 *
	 * @param int    $object_id Object ID.
	 * @param int[]  $tt_ids    Removed term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy.
	 */
	public function sync_removed_programs( $object_id, $tt_ids, $taxonomy ) {
		if ( 'course_program' === $taxonomy ) {
			$this->sync_term_taxonomy_ids( array_map( 'intval', (array) $tt_ids ) );
		}
	}

	/**
	 * Set a program's package product to draft when the program is deleted.
	 *
	 * @param int    $term_id  Term being deleted.
	 * @param string $taxonomy Taxonomy.
	 */
	public function unlink( $term_id, $taxonomy ) {
		if ( 'course_program' !== $taxonomy ) {
			return;
		}

		$product = self::get_product( $term_id );
		if ( $product ) {
			$product->set_status( 'draft' );
			$product->delete_meta_data( self::PROGRAM_KEY );
			$product->save();
		}
	}

	/**
	 * Sync the programs behind a list of term taxonomy IDs.
	 *
	 * @param int[] $tt_ids Term taxonomy IDs.
	 */
	private function sync_term_taxonomy_ids( $tt_ids ) {
		foreach ( array_unique( $tt_ids ) as $tt_id ) {
			$program = get_term_by( 'term_taxonomy_id', $tt_id, 'course_program' );
			if ( $program ) {
				self::sync( $program->term_id );
			}
		}
	}
}
