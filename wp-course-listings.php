<?php
/*
Plugin Name: WP Course Listings
Plugin URI: https://nrds.life
Description: Manage and display courses with custom fields, categories, and display shortcodes.
Version: 1.0
Author: Jeremiah Bagula
Author URI: https://nrds.life
Text Domain: wp-course-listings
*/

// Show the Course Post Image at the top of the single course post
function wpcl_show_course_post_image($content) {
	if (is_singular('course') && in_the_loop() && is_main_query()) {
		$img = get_post_meta(get_the_ID(), '_wpcl_course_post_image', true);
		if ($img) {
			$img_html = '<div class="wpcl-course-post-image" style="margin-bottom:24px;text-align:center;"><img src="' . esc_url($img) . '" alt="' . esc_attr(get_the_title()) . '" style="max-width:100%;height:auto;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.08);" /></div>';
			return $img_html . $content;
		}
	}
	return $content;
}
add_filter('the_content', 'wpcl_show_course_post_image');

// Plugin activation: register post type/taxonomy and flush rewrite rules
function wpcl_plugin_activate() {
	// Register post type and taxonomy so rewrite rules exist
	wpcl_register_course_post_type();
	wpcl_register_course_category();
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wpcl_plugin_activate');

// Register Custom Post Type: Course
function wpcl_register_course_post_type() {
	$labels = array(
		'name' => 'Courses',
		'singular_name' => 'Course',
		'add_new' => 'Add New',
		'add_new_item' => 'Add New Course',
		'edit_item' => 'Edit Course',
		'new_item' => 'New Course',
		'view_item' => 'View Course',
		'search_items' => 'Search Courses',
		'not_found' => 'No courses found',
		'not_found_in_trash' => 'No courses found in Trash',
		'all_items' => 'All Courses',
		'menu_name' => 'Courses',
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'has_archive' => true,
		'supports' => array('title', 'editor', 'thumbnail', 'author', 'excerpt', 'revisions'),
		'menu_icon' => 'dashicons-welcome-learn-more',
		'show_in_rest' => true,
		'rewrite' => array('slug' => 'course', 'with_front' => true),
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'capability_type' => 'post',
	);
	register_post_type('course', $args);
}
add_action('init', 'wpcl_register_course_post_type');

// Register Custom Taxonomy: Course Category
function wpcl_register_course_category() {
	$labels = array(
		'name' => 'Course Categories',
		'singular_name' => 'Course Category',
		'search_items' => 'Search Course Categories',
		'all_items' => 'All Course Categories',
		'edit_item' => 'Edit Course Category',
		'update_item' => 'Update Course Category',
		'add_new_item' => 'Add New Course Category',
		'new_item_name' => 'New Course Category Name',
		'menu_name' => 'Course Categories',
	);
	$args = array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'show_admin_column' => true,
		'query_var' => true,
		'rewrite' => array('slug' => 'course-category'),
		'show_in_rest' => true,
	);
	register_taxonomy('course_category', array('course'), $args);
}
add_action('init', 'wpcl_register_course_category');

// Add Meta Boxes for Price, Post Image, Thumbnail Image
function wpcl_add_course_meta_boxes() {
	add_meta_box('wpcl_course_price', 'Course Price', 'wpcl_course_price_callback', 'course', 'side');
	add_meta_box('wpcl_course_post_image', 'Post Image URL', 'wpcl_course_post_image_callback', 'course', 'normal');
	add_meta_box('wpcl_course_thumbnail_image', 'Thumbnail Image URL', 'wpcl_course_thumbnail_image_callback', 'course', 'normal');
	add_meta_box('wpcl_course_post_url', 'Course Post URL', 'wpcl_course_post_url_callback', 'course', 'normal');
}
// Meta box callback for Course Post URL
function wpcl_course_post_url_callback($post) {
	$value = get_post_meta($post->ID, '_wpcl_course_post_url', true);
	echo '<input type="url" name="wpcl_course_post_url" value="' . esc_attr($value) . '" style="width:100%;" placeholder="https://..." />';
}
add_action('add_meta_boxes', 'wpcl_add_course_meta_boxes');

function wpcl_course_price_callback($post) {
	$value = get_post_meta($post->ID, '_wpcl_course_price', true);
	echo '<input type="text" name="wpcl_course_price" value="' . esc_attr($value) . '" style="width:100%;" />';
}

function wpcl_course_post_image_callback($post) {
	$value = get_post_meta($post->ID, '_wpcl_course_post_image', true);
	echo '<input type="text" name="wpcl_course_post_image" value="' . esc_attr($value) . '" style="width:100%;" placeholder="https://..." />';
}

function wpcl_course_thumbnail_image_callback($post) {
	$value = get_post_meta($post->ID, '_wpcl_course_thumbnail_image', true);
	echo '<input type="text" name="wpcl_course_thumbnail_image" value="' . esc_attr($value) . '" style="width:100%;" placeholder="https://..." />';
}

// Save Meta Box Data
function wpcl_save_course_meta($post_id) {
	if (array_key_exists('wpcl_course_price', $_POST)) {
		update_post_meta($post_id, '_wpcl_course_price', sanitize_text_field($_POST['wpcl_course_price']));
	}
	if (array_key_exists('wpcl_course_post_image', $_POST)) {
		update_post_meta($post_id, '_wpcl_course_post_image', esc_url_raw($_POST['wpcl_course_post_image']));
	}
	if (array_key_exists('wpcl_course_thumbnail_image', $_POST)) {
		update_post_meta($post_id, '_wpcl_course_thumbnail_image', esc_url_raw($_POST['wpcl_course_thumbnail_image']));
	}
	if (array_key_exists('wpcl_course_post_url', $_POST)) {
		update_post_meta($post_id, '_wpcl_course_post_url', esc_url_raw($_POST['wpcl_course_post_url']));
	}
}
add_action('save_post', 'wpcl_save_course_meta');

// Shortcode to Display Courses
function wpcl_courses_shortcode($atts) {
	$atts = shortcode_atts(array(
		'category' => '',
		'layout' => 'bullet', // bullet, table, thumbnail
	), $atts, 'wpcl_courses');

	$args = array(
		'post_type' => 'course',
		'posts_per_page' => -1,
	);
	if ($atts['category']) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'course_category',
				'field' => 'slug',
				'terms' => $atts['category'],
			),
		);
	}
	$query = new WP_Query($args);
	if (!$query->have_posts()) return '<p>No courses found.</p>';

	ob_start();
	if ($atts['layout'] === 'table') {
		echo '<table class="wpcl-courses-table"><thead><tr><th>Title</th><th>Description</th><th>Price</th><th>Image</th><th>Post URL</th></tr></thead><tbody>';
		while ($query->have_posts()) : $query->the_post();
			$price = get_post_meta(get_the_ID(), '_wpcl_course_price', true);
			$img = get_post_meta(get_the_ID(), '_wpcl_course_post_image', true);
			$url = get_post_meta(get_the_ID(), '_wpcl_course_post_url', true);
			echo '<tr>';
			echo '<td>' . get_the_title() . '</td>';
			echo '<td>' . get_the_excerpt() . '</td>';
			echo '<td>' . esc_html($price) . '</td>';
			echo '<td>' . ($img ? '<img src="' . esc_url($img) . '" style="max-width:100px;" />' : '') . '</td>';
			echo '<td>' . ($url ? '<a href="' . esc_url($url) . '" target="_blank">View</a>' : '') . '</td>';
			echo '</tr>';
		endwhile;
		echo '</tbody></table>';
	} elseif ($atts['layout'] === 'thumbnail') {
		echo '<div class="wpcl-courses-thumbnails">';
		while ($query->have_posts()) : $query->the_post();
			$price = get_post_meta(get_the_ID(), '_wpcl_course_price', true);
			$permalink = get_permalink();
			echo '<div class="wpcl-course-thumb">';
			if (has_post_thumbnail()) {
				$thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
				echo '<a href="' . esc_url($permalink) . '" class="wpcl-thumb-link">';
				echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr(get_the_title()) . '" class="wpcl-thumb-img" />';
				echo '</a>';
			}
			echo '<h3><a href="' . esc_url($permalink) . '" class="wpcl-thumb-link">' . get_the_title() . '</a></h3>';
			echo '<div>' . get_the_excerpt() . '</div>';
			echo '<div><strong>Price:</strong> ' . esc_html($price) . '</div>';
			echo '</div>';
		endwhile;
		echo '</div>';
	} else { // bullet
		echo '<ul class="wpcl-courses-bullet">';
		while ($query->have_posts()) : $query->the_post();
			$price = get_post_meta(get_the_ID(), '_wpcl_course_price', true);
			$url = get_post_meta(get_the_ID(), '_wpcl_course_post_url', true);
			echo '<li><strong>' . get_the_title() . '</strong> (' . esc_html($price) . ')<br />' . get_the_excerpt();
			if ($url) echo '<br /><a href="' . esc_url($url) . '" target="_blank">View Course</a>';
			echo '</li>';
		endwhile;
		echo '</ul>';
	}
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode('wpcl_courses', 'wpcl_courses_shortcode');

// Add basic styles
function wpcl_courses_styles() {
	echo '<style>
	.wpcl-courses-table { width:100%; border-collapse:collapse; }
	.wpcl-courses-table th, .wpcl-courses-table td { border:1px solid #ccc; padding:8px; }
	.wpcl-courses-thumbnails { margin-top:20px; display:flex; flex-wrap:wrap; gap:20px; }
	.wpcl-course-thumb { background:#fafafa; width:200px; border:1px solid #ccc; padding:10px; text-align:center; transition: box-shadow 0.3s, transform 0.3s; position:relative; }
	.wpcl-course-thumb:hover { box-shadow:0 4px 16px rgba(0,0,0,0.15); transform:translateY(-4px) scale(1.03); z-index:2; }
	.wpcl-thumb-link { text-decoration:none; color:inherit; }
	.wpcl-thumb-img { max-width:100%; height:auto; border-radius:6px; transition: box-shadow 0.3s; }
	.wpcl-course-thumb:hover .wpcl-thumb-img { box-shadow:0 2px 12px rgba(0,0,0,0.18); }
	.wpcl-courses-bullet { margin-left:20px; }
	</style>';
}
add_action('wp_head', 'wpcl_courses_styles');

?>
