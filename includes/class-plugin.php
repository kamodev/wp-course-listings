<?php
/**
 * Main plugin class.
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin's components and wires them into WordPress.
 */
final class Plugin {

	/**
	 * The single plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin instance, booting it on first call.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}

		return self::$instance;
	}

	/**
	 * Register hooks for every component.
	 */
	private function boot() {
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );

		( new Post_Types() )->init();
		( new Shortcode() )->init();
		( new Frontend() )->init();

		if ( is_admin() ) {
			( new Admin() )->init();
			( new Settings() )->init();
		}
	}

	/**
	 * Load translations bundled in the plugin's languages folder.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-course-listings', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );
	}
}
