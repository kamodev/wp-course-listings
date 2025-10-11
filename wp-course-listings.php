<?php
/*
Plugin Name: WP Course Listings
Plugin URI: https://nrds.life
Description: Manage and display courses with custom fields, categories, and display shortcodes.
Version: 1.0.3
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
}
add_action('add_meta_boxes', 'wpcl_add_course_meta_boxes');

function wpcl_course_price_callback($post) {
	$value = get_post_meta($post->ID, '_wpcl_course_price', true);
	echo '<input type="text" name="wpcl_course_price" value="' . esc_attr($value) . '" style="width:100%;" />';
}

function wpcl_course_post_image_callback($post) {
		$value = get_post_meta($post->ID, '_wpcl_course_post_image', true);
		$image_preview = $value ? '<img src="' . esc_url($value) . '" style="max-width:100%;height:auto;display:block;margin-bottom:10px;" />' : '';
		echo '<div id="wpcl-course-post-image-wrapper">' . $image_preview . '</div>';
		echo '<input type="hidden" id="wpcl_course_post_image" name="wpcl_course_post_image" value="' . esc_attr($value) . '" />';
		echo '<button type="button" class="button" id="wpcl_select_post_image">Select Image</button> ';
		echo '<button type="button" class="button" id="wpcl_remove_post_image">Remove Image</button>';
		?>
		<script>
		(function($){
			$(function(){
				var frame;
				$('#wpcl_select_post_image').on('click', function(e){
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: 'Select or Upload Image',
						button: { text: 'Use this image' },
						multiple: false
					});
					frame.on('select', function(){
						var attachment = frame.state().get('selection').first().toJSON();
						$('#wpcl_course_post_image').val(attachment.url);
						$('#wpcl-course-post-image-wrapper').html('<img src="'+attachment.url+'" style="max-width:100%;height:auto;display:block;margin-bottom:10px;" />');
					});
					frame.open();
				});
				$('#wpcl_remove_post_image').on('click', function(e){
					e.preventDefault();
					$('#wpcl_course_post_image').val('');
					$('#wpcl-course-post-image-wrapper').html('');
				});
			});
		})(jQuery);
		</script>
		<?php
// Enqueue media uploader for admin
function wpcl_enqueue_admin_media($hook) {
	if ('post.php' === $hook || 'post-new.php' === $hook) {
		wp_enqueue_media();
	}
}
add_action('admin_enqueue_scripts', 'wpcl_enqueue_admin_media');
}


// Save Meta Box Data
function wpcl_save_course_meta($post_id) {
	if (array_key_exists('wpcl_course_price', $_POST)) {
		update_post_meta($post_id, '_wpcl_course_price', sanitize_text_field($_POST['wpcl_course_price']));
	}
	if (array_key_exists('wpcl_course_post_image', $_POST)) {
		update_post_meta($post_id, '_wpcl_course_post_image', esc_url_raw($_POST['wpcl_course_post_image']));
	}
}
add_action('save_post', 'wpcl_save_course_meta');

// Shortcode to Display Courses
function wpcl_courses_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
        'layout' => 'bullet', // bullet, table, thumbnail
    ), $atts, 'wpcl_courses');

    $hide_price = get_option('wpcl_disable_price', 0);
    $title_text = get_option('wpcl_table_title_text', 'Course Listings');
    $title_placement = get_option('wpcl_table_title_placement', 'above');

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
		if ($title_placement === 'above') echo '<div class="wpcl-courses-table-title" style="font-size:1.3em;font-weight:600;margin-bottom:10px;">' . esc_html($title_text) . '</div>';
		echo '<table class="wpcl-courses-table"><thead><tr><th>Title</th><th>Description</th>';
		if (!$hide_price) echo '<th>Price</th>';
		echo '</tr></thead><tbody>';
		while ($query->have_posts()) : $query->the_post();
			$price = get_post_meta(get_the_ID(), '_wpcl_course_price', true);
			$permalink = get_permalink(get_the_ID());
			$img = get_post_meta(get_the_ID(), '_wpcl_course_post_image', true);
			echo '<tr>';
			echo '<td><a href="' . esc_url($permalink) . '">' . get_the_title() . '</a></td>';
			echo '<td>' . get_the_excerpt() . '</td>';
			if (!$hide_price) echo '<td>' . esc_html($price) . '</td>';
			echo '</tr>';
	 endwhile;
	 echo '</tbody></table>';
	 if ($title_placement === 'below') echo '<div class="wpcl-courses-table-title" style="font-size:1.3em;font-weight:600;margin-top:10px;">' . esc_html($title_text) . '</div>';
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
			if (!$hide_price) echo '<div><strong>Price:</strong> ' . esc_html($price) . '</div>';
			echo '</div>';
		endwhile;
		echo '</div>';
	 } else { // bullet
	 echo '<ul class="wpcl-courses-bullet">';
        while ($query->have_posts()) : $query->the_post();
            $price = get_post_meta(get_the_ID(), '_wpcl_course_price', true);
            $categories = get_the_terms(get_the_ID(), 'course_category');
            $cat_links = '';
            if ($categories && !is_wp_error($categories)) {
                $cat_arr = array();
                foreach ($categories as $cat) {
                    $cat_arr[] = '<a href="' . esc_url(get_term_link($cat)) . '">' . esc_html($cat->name) . '</a>';
                }
                $cat_links = ' | Category: ' . implode(', ', $cat_arr);
            }
            $permalink = get_permalink();
            echo '<li><a href="' . esc_url($permalink) . '"><strong>' . get_the_title() . '</strong></a>' . $cat_links;
            if (!$hide_price) echo ' (' . esc_html($price) . ')';
            echo '<br />' . get_the_excerpt() . '</li>';
        endwhile;
    echo '</ul>';
	}
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode('wpcl_courses', 'wpcl_courses_shortcode');

// [wpcl_course_listing_by_category] shortcode: Show all courses grouped by category
function wpcl_courses_by_category_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_empty' => false,
    ), $atts, 'wpcl_course_listing_by_category');

    $terms = get_terms(array(
        'taxonomy' => 'course_category',
        'hide_empty' => !$atts['show_empty'],
    ));
    if (empty($terms) || is_wp_error($terms)) return '<p>No course categories found.</p>';

    $hide_price = get_option('wpcl_disable_price', 0);
    ob_start();
    echo '<div class="wpcl-courses-by-category">';
    foreach ($terms as $term) {
        echo '<section class="wpcl-category-section" style="margin-bottom:2.5em;">';
        echo '<h2 class="wpcl-category-title" style="font-size:1.2em;margin-bottom:0.5em;">' . esc_html($term->name) . '</h2>';
        $query = new WP_Query(array(
            'post_type' => 'course',
            'posts_per_page' => -1,
            'tax_query' => array(array(
                'taxonomy' => 'course_category',
                'field' => 'term_id',
                'terms' => $term->term_id,
            )),
        ));
        if ($query->have_posts()) {
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
                if (!$hide_price) echo '<div><strong>Price:</strong> ' . esc_html($price) . '</div>';
                echo '</div>';
            endwhile;
            echo '</div>';
        } else {
            echo '<p>No courses in this category.</p>';
        }
        wp_reset_postdata();
        echo '</section>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('wpcl_course_listing_by_category', 'wpcl_courses_by_category_shortcode');

// Add basic styles
function wpcl_courses_styles() {
	echo '<style>
	.wpcl-courses-table { width:100%; border-collapse:collapse; background:#fff; box-shadow:none; border:1px solid #e0e0e0; }
	.wpcl-courses-table th, .wpcl-courses-table td { border:1px solid #e0e0e0; padding:8px 10px; background:#fff; font-weight:400; }
	.wpcl-courses-table th { background:#f7f7f7; font-weight:600; }
	.wpcl-courses-table-title { font-size:1.3em; font-weight:600; margin-bottom:10px; }
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

// Add category to course permalinks
add_filter('post_type_link', function($post_link, $post) {
    if ($post->post_type === 'course') {
        $terms = get_the_terms($post->ID, 'course_category');
        if ($terms && !is_wp_error($terms)) {
            $category = array_shift($terms);
            if ($category) {
                $post_link = str_replace('course/', 'course/' . $category->slug . '/', $post_link);
            }
        }
    }
    return $post_link;
}, 10, 2);

// Add rewrite rule for course permalinks with category
add_action('init', function() {
    add_rewrite_rule('^course/([^/]+)/([^/]+)/?$', 'index.php?course=$matches[2]', 'top');
});

// Admin setting: Disable showing price on courses
add_action('admin_menu', function() {
    add_options_page('WPCL Settings', 'WPCL Settings', 'manage_options', 'wpcl-settings', 'wpcl_settings_page');
});

// Admin setting: Custom table title and placement
add_action('admin_menu', function() {
    add_options_page('WPCL Settings', 'WPCL Settings', 'manage_options', 'wpcl-settings', 'wpcl_settings_page');
});

function wpcl_settings_page() {
    $title_text = get_option('wpcl_table_title_text', 'Course Listings');
    $title_placement = get_option('wpcl_table_title_placement', 'above');
    ?>
    <div class="wrap">
        <h1>WP Course Listings Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wpcl_settings_group'); do_settings_sections('wpcl_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Disable Price Display</th>
                    <td><input type="checkbox" name="wpcl_disable_price" value="1" <?php checked(1, get_option('wpcl_disable_price', 0)); ?> /> Hide price on all course listings</td>
                </tr>
                <tr valign="top">
                    <th scope="row">Table Title Text</th>
                    <td><input type="text" name="wpcl_table_title_text" value="<?php echo esc_attr($title_text); ?>" style="width: 300px;" />
                    <p class="description">Custom title for course tables. Default: "Course Listings"</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Table Title Placement</th>
                    <td>
                        <select name="wpcl_table_title_placement">
                            <option value="above" <?php selected($title_placement, 'above'); ?>>Above Table</option>
                            <option value="below" <?php selected($title_placement, 'below'); ?>>Below Table</option>
                            <option value="none" <?php selected($title_placement, 'none'); ?>>Do Not Show</option>
                        </select>
                        <p class="description">Where to display the table title.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
add_action('admin_init', function() {
    register_setting('wpcl_settings_group', 'wpcl_disable_price');
    register_setting('wpcl_settings_group', 'wpcl_table_title_text');
    register_setting('wpcl_settings_group', 'wpcl_table_title_placement');
});
