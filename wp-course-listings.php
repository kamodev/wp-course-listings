<?php
/*
Plugin Name: WP Course Listings
Description: Adds a "Course" custom post type with categories and a course listing page.
Version: 1.0
Author: Jeremiah Bagula
*/

// Register Custom Post Type: Course
function wcl_register_course_post_type() {
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
        'rewrite' => array('slug' => 'courses'),
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest' => true,
        'taxonomies' => array('course_category'),
    );
    register_post_type('course', $args);
}
add_action('init', 'wcl_register_course_post_type');

// Register Custom Taxonomy: Course Category
function wcl_register_course_category_taxonomy() {
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
        'rewrite' => array('slug' => 'course-category'),
        'show_in_rest' => true,
    );
    register_taxonomy('course_category', array('course'), $args);
}
add_action('init', 'wcl_register_course_category_taxonomy');

// Shortcode to display course listings
function wcl_course_listing_shortcode($atts) {
    $args = array(
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $output = '<div class="wcl-course-listings">';
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<div class="wcl-course">';
            if (has_post_thumbnail()) {
                $output .= '<div class="wcl-course-thumb">' . get_the_post_thumbnail(get_the_ID(), 'medium') . '</div>';
            }
            $output .= '<h2 class="wcl-course-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
            $output .= '<div class="wcl-course-excerpt">' . get_the_excerpt() . '</div>';
            $categories = get_the_terms(get_the_ID(), 'course_category');
            if ($categories && !is_wp_error($categories)) {
                $output .= '<div class="wcl-course-categories"><strong>Categories:</strong> ';
                $cat_links = array();
                foreach ($categories as $cat) {
                    $cat_links[] = esc_html($cat->name);
                }
                $output .= implode(', ', $cat_links);
                $output .= '</div>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
    } else {
        $output = '<p>No courses found.</p>';
    }
    return $output;
}
add_shortcode('course_listings', 'wcl_course_listing_shortcode');

// Optional: Add a rewrite rule for /courses/ to show the listing page
function wcl_add_courses_rewrite_rule() {
    add_rewrite_rule('^courses/?$', 'index.php?post_type=course', 'top');
}
add_action('init', 'wcl_add_courses_rewrite_rule');

// Optional: Create a page on plugin activation
function wcl_create_course_listing_page() {
    if (!get_page_by_path('courses')) {
        wp_insert_post(array(
            'post_title' => 'Courses',
            'post_name' => 'courses',
            'post_content' => '[course_listings]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ));
    }
}
register_activation_hook(__FILE__, 'wcl_create_course_listing_page');

// Flush rewrite rules on activation/deactivation
function wcl_flush_rewrite_rules() {
    wcl_register_course_post_type();
    wcl_register_course_category_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wcl_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

/**
 * Add meta box for Course meta data.
 */
function wcl_add_course_meta_box() {
    add_meta_box(
        'wcl_course_meta',
        'Course Details',
        'wcl_course_meta_box_callback',
        'course',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'wcl_add_course_meta_box');

/**
 * Meta box display callback.
 */
function wcl_course_meta_box_callback($post) {
    wp_nonce_field('wcl_save_course_meta', 'wcl_course_meta_nonce');
    $duration = get_post_meta($post->ID, '_wcl_duration', true);
    $level = get_post_meta($post->ID, '_wcl_level', true);
    ?>
    <p>
        <label for="wcl_duration"><strong>Duration:</strong></label><br>
        <input type="text" name="wcl_duration" id="wcl_duration" value="<?php echo esc_attr($duration); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="wcl_level"><strong>Level:</strong></label><br>
        <input type="text" name="wcl_level" id="wcl_level" value="<?php echo esc_attr($level); ?>" style="width:100%;" />
    </p>
    <?php
}

/**
 * Save meta box content.
 */
function wcl_save_course_meta($post_id) {
    if (!isset($_POST['wcl_course_meta_nonce']) || !wp_verify_nonce($_POST['wcl_course_meta_nonce'], 'wcl_save_course_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['post_type']) && 'course' === $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    $fields = array(
        '_wcl_duration'   => isset($_POST['wcl_duration']) ? sanitize_text_field($_POST['wcl_duration']) : '',
        '_wcl_level'      => isset($_POST['wcl_level']) ? sanitize_text_field($_POST['wcl_level']) : '',
    );

    foreach ($fields as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }
}
add_action('save_post', 'wcl_save_course_meta');

/**
 * Display course meta data in the course listing shortcode.
 */
add_filter('wcl_course_listing_meta', function($output, $post_id) {
    $duration = get_post_meta($post_id, '_wcl_duration', true);
    $level = get_post_meta($post_id, '_wcl_level', true);

    $meta = '';
    if ($duration) {
        $meta .= '<div class="wcl-course-duration"><strong>Duration:</strong> ' . esc_html($duration) . '</div>';
    }
    if ($level) {
        $meta .= '<div class="wcl-course-level"><strong>Level:</strong> ' . esc_html($level) . '</div>';
    }
    return $output . $meta;
}, 10, 2);

// Update the shortcode output to include meta data
add_filter('wcl_course_listing_output', function($output, $post_id) {
    $meta = apply_filters('wcl_course_listing_meta', '', $post_id);
    return $output . $meta;
}, 10, 2);

// Modify the shortcode to use the filter for meta data
add_filter('the_content', function($content) {
    global $post;
    if ($post && $post->post_type === 'course' && is_singular('course')) {
        $meta = apply_filters('wcl_course_listing_meta', '', $post->ID);
        return $content . $meta;
    }
    return $content;
});
/**
 * Add SEO meta box for Course posts.
 */
function wcl_add_seo_meta_box() {
    add_meta_box(
        'wcl_seo_meta',
        'SEO Metadata',
        'wcl_seo_meta_box_callback',
        'course',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'wcl_add_seo_meta_box');

/**
 * SEO meta box display callback.
 */
function wcl_seo_meta_box_callback($post) {
    wp_nonce_field('wcl_save_seo_meta', 'wcl_seo_meta_nonce');
    $seo_title = get_post_meta($post->ID, '_wcl_seo_title', true);
    $seo_description = get_post_meta($post->ID, '_wcl_seo_description', true);
    ?>
    <p>
        <label for="wcl_seo_title"><strong>SEO Title:</strong></label><br>
        <input type="text" name="wcl_seo_title" id="wcl_seo_title" value="<?php echo esc_attr($seo_title); ?>" style="width:100%;" maxlength="60" />
        <small>Recommended: 50-60 characters.</small>
    </p>
    <p>
        <label for="wcl_seo_description"><strong>SEO Description:</strong></label><br>
        <textarea name="wcl_seo_description" id="wcl_seo_description" style="width:100%;" rows="3" maxlength="160"><?php echo esc_textarea($seo_description); ?></textarea>
        <small>Recommended: 120-160 characters.</small>
    </p>
    <?php
}

/**
 * Save SEO meta box content.
 */
function wcl_save_seo_meta($post_id) {
    if (!isset($_POST['wcl_seo_meta_nonce']) || !wp_verify_nonce($_POST['wcl_seo_meta_nonce'], 'wcl_save_seo_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['post_type']) && 'course' === $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    $seo_title = isset($_POST['wcl_seo_title']) ? sanitize_text_field($_POST['wcl_seo_title']) : '';
    $seo_description = isset($_POST['wcl_seo_description']) ? sanitize_textarea_field($_POST['wcl_seo_description']) : '';

    update_post_meta($post_id, '_wcl_seo_title', $seo_title);
    update_post_meta($post_id, '_wcl_seo_description', $seo_description);
}
add_action('save_post', 'wcl_save_seo_meta');

/**
 * Output SEO meta tags in the head for single course posts.
 */
function wcl_output_seo_meta_tags() {
    if (is_singular('course')) {
        global $post;
        $seo_title = get_post_meta($post->ID, '_wcl_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_wcl_seo_description', true);

        if ($seo_title) {
            echo '<meta name="title" content="' . esc_attr($seo_title) . '">' . "\n";
            echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
        }
        if ($seo_description) {
            echo '<meta name="description" content="' . esc_attr($seo_description) . '">' . "\n";
        }
    }
}
add_action('wp_head', 'wcl_output_seo_meta_tags');

/**
 * Add Social Media meta box for Course posts.
 */
function wcl_add_social_meta_box() {
    add_meta_box(
        'wcl_social_meta',
        'Social Media Metadata',
        'wcl_social_meta_box_callback',
        'course',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'wcl_add_social_meta_box');

/**
 * Social Media meta box display callback.
 */
function wcl_social_meta_box_callback($post) {
    wp_nonce_field('wcl_save_social_meta', 'wcl_social_meta_nonce');
    $og_title = get_post_meta($post->ID, '_wcl_og_title', true);
    $og_description = get_post_meta($post->ID, '_wcl_og_description', true);
    $og_image = get_post_meta($post->ID, '_wcl_og_image', true);
    ?>
    <p>
        <label for="wcl_og_title"><strong>Open Graph Title:</strong></label><br>
        <input type="text" name="wcl_og_title" id="wcl_og_title" value="<?php echo esc_attr($og_title); ?>" style="width:100%;" maxlength="60" />
        <small>Recommended: 50-60 characters.</small>
    </p>
    <p>
        <label for="wcl_og_description"><strong>Open Graph Description:</strong></label><br>
        <textarea name="wcl_og_description" id="wcl_og_description" style="width:100%;" rows="3" maxlength="160"><?php echo esc_textarea($og_description); ?></textarea>
        <small>Recommended: 120-160 characters.</small>
    </p>
    <p>
        <label for="wcl_og_image"><strong>Open Graph Image URL:</strong></label><br>
        <input type="text" name="wcl_og_image" id="wcl_og_image" value="<?php echo esc_url($og_image); ?>" style="width:100%;" />
        <small>Recommended size: 1200x630px. You can paste a URL or use the "Add Media" button.</small>
    </p>
    <p>
        <button type="button" class="button" id="wcl_og_image_upload">Add Media</button>
    </p>
    <script>
    jQuery(document).ready(function($){
        $('#wcl_og_image_upload').on('click', function(e){
            e.preventDefault();
            var image_frame;
            if(image_frame){
                image_frame.open();
            }
            // Define image_frame as wp.media object
            image_frame = wp.media({
                title: 'Select Media',
                multiple : false,
                library : {
                    type : 'image',
                }
            });

            image_frame.on('select', function(){
                var attachment = image_frame.state().get('selection').first().toJSON();
                $('#wcl_og_image').val(attachment.url);
            });
            image_frame.open();
        });
    });
    </script>
    <?php
}

/**
 * Save Social Media meta box content.
 */
function wcl_save_social_meta($post_id) {
    if (!isset($_POST['wcl_social_meta_nonce']) || !wp_verify_nonce($_POST['wcl_social_meta_nonce'], 'wcl_save_social_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['post_type']) && 'course' === $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    $og_title = isset($_POST['wcl_og_title']) ? sanitize_text_field($_POST['wcl_og_title']) : '';
    $og_description = isset($_POST['wcl_og_description']) ? sanitize_textarea_field($_POST['wcl_og_description']) : '';
    $og_image = isset($_POST['wcl_og_image']) ? esc_url_raw($_POST['wcl_og_image']) : '';

    update_post_meta($post_id, '_wcl_og_title', $og_title);
    update_post_meta($post_id, '_wcl_og_description', $og_description);
    update_post_meta($post_id, '_wcl_og_image', $og_image);
}
add_action('save_post', 'wcl_save_social_meta');

/**
 * Output Social Media meta tags in the head for single course posts.
 */
function wcl_output_social_meta_tags() {
    if (is_singular('course')) {
        global $post;
        $og_title = get_post_meta($post->ID, '_wcl_og_title', true);
        $og_description = get_post_meta($post->ID, '_wcl_og_description', true);
        $og_image = get_post_meta($post->ID, '_wcl_og_image', true);

        if ($og_title) {
            echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '">' . "\n";
        }
        if ($og_description) {
            echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($og_description) . '">' . "\n";
        }
        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
            echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";
        }
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    }
}
add_action('wp_head', 'wcl_output_social_meta_tags');

/**
 * Add settings page for WP Course Listings.
 */
function wcl_add_settings_page() {
    add_options_page(
        'WP Course Listings Settings',
        'Course Listings',
        'manage_options',
        'wcl-settings',
        'wcl_render_settings_page'
    );
}
add_action('admin_menu', 'wcl_add_settings_page');

/**
 * Register settings.
 */
function wcl_register_settings() {
    register_setting('wcl_settings_group', 'wcl_courses_slug', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_title',
        'default' => 'courses',
    ));
    add_settings_section(
        'wcl_settings_section',
        'Course Listings Settings',
        '__return_false',
        'wcl-settings'
    );
    add_settings_field(
        'wcl_courses_slug',
        'Courses URL Slug (Plural)',
        'wcl_courses_slug_field_callback',
        'wcl-settings',
        'wcl_settings_section'
    );
}
add_action('admin_init', 'wcl_register_settings');

/**
 * Slug field callback.
 */
function wcl_courses_slug_field_callback() {
    $slug = get_option('wcl_courses_slug', 'courses');
    echo '<input type="text" name="wcl_courses_slug" value="' . esc_attr($slug) . '" class="regular-text" />';
    echo '<p class="description">This will be used for the plural URL, e.g., <code>/' . esc_html($slug) . '/</code></p>';
}

/**
 * Render settings page.
 */
function wcl_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>WP Course Listings Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wcl_settings_group');
            do_settings_sections('wcl-settings');
            submit_button();
            ?>
        </form>
        <p><strong>Note:</strong> After changing the slug, you must <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>">save your permalinks</a> for changes to take effect.</p>
    </div>
    <?php
}

/**
 * Use the custom slug for the course post type and rewrite rules.
 */
function wcl_dynamic_course_slug($args, $post_type) {
    if ($post_type === 'course') {
        $slug = get_option('wcl_courses_slug', 'courses');
        $args['rewrite'] = array('slug' => $slug);
        $args['has_archive'] = $slug;
    }
    return $args;
}
add_filter('register_post_type_args', 'wcl_dynamic_course_slug', 10, 2);

/**
 * Add rewrite rule for the custom slug.
 */
function wcl_dynamic_courses_rewrite_rule() {
    $slug = get_option('wcl_courses_slug', 'courses');
    add_rewrite_rule('^' . $slug . '/?$', 'index.php?post_type=course', 'top');
}
add_action('init', 'wcl_dynamic_courses_rewrite_rule', 20);

/**
 * Flush rewrite rules when the slug changes.
 */
function wcl_maybe_flush_rewrite_rules() {
    if (!is_admin()) {
        return;
    }
    $old_slug = get_option('wcl_courses_slug_flushed', '');
    $current_slug = get_option('wcl_courses_slug', 'courses');
    if ($old_slug !== $current_slug) {
        flush_rewrite_rules();
        update_option('wcl_courses_slug_flushed', $current_slug);
    }
}
add_action('admin_init', 'wcl_maybe_flush_rewrite_rules');