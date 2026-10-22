<?php
/**
 * Plugin Name:       WP Course Listings
 * Plugin URI:        https://github.com/kamodev/wp-course-listings
 * Description:       A comprehensive course management plugin with customizable URLs, display settings, SEO features, course programs with ordering, prerequisites, optional WooCommerce selling, and advanced course listing capabilities.
 * Version:           0.6.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Jeremiah Bagula
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-course-listings
 * Domain Path:       /languages
 * Update URI:        https://github.com/kamodev/wp-course-listings
 * WC requires at least: 8.0
 * WC tested up to:      11.2
 *
 * @package WP_Course_Listings
 */

namespace WP_Course_Listings;

defined( 'ABSPATH' ) || exit;

const VERSION     = '0.6.0';
const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__ . '/';

require_once PLUGIN_DIR . 'includes/class-options.php';
require_once PLUGIN_DIR . 'includes/class-course.php';
require_once PLUGIN_DIR . 'includes/class-post-types.php';
require_once PLUGIN_DIR . 'includes/class-activator.php';
require_once PLUGIN_DIR . 'includes/class-plugin.php';
require_once PLUGIN_DIR . 'includes/woocommerce/class-course-product.php';
require_once PLUGIN_DIR . 'includes/woocommerce/class-program-package.php';
require_once PLUGIN_DIR . 'includes/woocommerce/class-woocommerce-integration.php';
require_once PLUGIN_DIR . 'public/class-frontend.php';
require_once PLUGIN_DIR . 'public/class-shortcode.php';

if ( is_admin() ) {
	require_once PLUGIN_DIR . 'admin/class-admin.php';
	require_once PLUGIN_DIR . 'admin/class-settings.php';
	require_once PLUGIN_DIR . 'admin/class-woocommerce-admin.php';
}

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Activator::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( Plugin::class, 'instance' ) );
