<?php
/*
Plugin Name: WP Course Listings
Description: A comprehensive course management plugin with customizable URLs, display settings, SEO features, course programs with ordering, and advanced course listing capabilities.
Version: 0.5.1
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
        'taxonomies' => array('course_category', 'course_program'),
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
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

// Register Custom Taxonomy: Course Program
function wcl_register_course_program_taxonomy() {
    $labels = array(
        'name' => 'Course Programs',
        'singular_name' => 'Course Program',
        'search_items' => 'Search Course Programs',
        'all_items' => 'All Course Programs',
        'edit_item' => 'Edit Course Program',
        'update_item' => 'Update Course Program',
        'add_new_item' => 'Add New Course Program',
        'new_item_name' => 'New Course Program Name',
        'menu_name' => 'Course Programs',
        'parent_item' => 'Parent Program',
        'parent_item_colon' => 'Parent Program:',
    );
    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'course-program'),
        'show_in_rest' => true,
        'meta_box_cb' => 'wcl_course_program_meta_box',
    );
    register_taxonomy('course_program', array('course'), $args);
}
add_action('init', 'wcl_register_course_program_taxonomy');

// Enhanced shortcode to display course listings
function wcl_course_listing_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => get_option('wcl_courses_per_page', 12),
        'category' => '',
        'program' => '',
        'order_by_program' => false,
        'show_featured_image' => get_option('wcl_show_featured_image', true),
        'show_excerpt' => get_option('wcl_show_excerpt', true),
        'show_categories' => get_option('wcl_show_categories', true),
        'show_programs' => get_option('wcl_show_programs', true),
        'show_meta' => get_option('wcl_show_course_meta', true),
    ), $atts);
    
    $args = array(
        'post_type' => 'course',
        'posts_per_page' => intval($atts['limit']),
        'post_status' => 'publish',
    );
    
    // Set up tax_query array for potential filters
    $tax_query = array();
    
    // Add category filter if specified
    if (!empty($atts['category'])) {
        $tax_query[] = array(
            'taxonomy' => 'course_category',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($atts['category']),
        );
    }
    
    // Add program filter if specified
    if (!empty($atts['program'])) {
        $tax_query[] = array(
            'taxonomy' => 'course_program',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($atts['program']),
        );
    }
    
    // Apply tax_query if we have filters
    if (!empty($tax_query)) {
        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_query;
    }
    
    // Order by program order if requested
    if ($atts['order_by_program']) {
        $args['meta_key'] = '_wcl_program_order';
        $args['orderby'] = array(
            'meta_value_num' => 'ASC',
            'title' => 'ASC'
        );
    }
    
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $output = '<div class="wcl-course-listings">';
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<div class="wcl-course">';
            
            // Featured Image
            if ($atts['show_featured_image'] && has_post_thumbnail()) {
                $output .= '<div class="wcl-course-thumb">' . get_the_post_thumbnail(get_the_ID(), 'medium') . '</div>';
            }
            
            $output .= '<h2 class="wcl-course-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
            
            // Excerpt
            if ($atts['show_excerpt']) {
                $output .= '<div class="wcl-course-excerpt">' . get_the_excerpt() . '</div>';
            }
            
            // Course Meta (Duration, Level)
            if ($atts['show_meta']) {
                $duration = get_post_meta(get_the_ID(), '_wcl_duration', true);
                $level = get_post_meta(get_the_ID(), '_wcl_level', true);
                
                if ($duration || $level) {
                    $output .= '<div class="wcl-course-meta">';
                    if ($duration) {
                        $output .= '<span class="wcl-course-duration"><strong>Duration:</strong> ' . esc_html($duration) . '</span>';
                    }
                    if ($level) {
                        if ($duration) $output .= ' | ';
                        $output .= '<span class="wcl-course-level"><strong>Level:</strong> ' . esc_html($level) . '</span>';
                    }
                    $output .= '</div>';
                }
            }
            
            // Categories
            if ($atts['show_categories']) {
                $categories = get_the_terms(get_the_ID(), 'course_category');
                if ($categories && !is_wp_error($categories)) {
                    $output .= '<div class="wcl-course-categories"><strong>Categories:</strong> ';
                    $cat_links = array();
                    foreach ($categories as $cat) {
                        $cat_links[] = '<a href="' . esc_url(get_term_link($cat)) . '">' . esc_html($cat->name) . '</a>';
                    }
                    $output .= implode(', ', $cat_links);
                    $output .= '</div>';
                }
            }
            
            // Programs
            if ($atts['show_programs']) {
                $programs = get_the_terms(get_the_ID(), 'course_program');
                if ($programs && !is_wp_error($programs)) {
                    $program_order = get_post_meta(get_the_ID(), '_wcl_program_order', true);
                    $output .= '<div class="wcl-course-programs"><strong>Program:</strong> ';
                    $prog_links = array();
                    foreach ($programs as $prog) {
                        $prog_links[] = '<a href="' . esc_url(get_term_link($prog)) . '">' . esc_html($prog->name) . '</a>';
                    }
                    $output .= implode(', ', $prog_links);
                    if (!empty($program_order)) {
                        $output .= ' <span class="wcl-program-order">(Course #' . intval($program_order + 1) . ')</span>';
                    }
                    $output .= '</div>';
                }
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
    $archive_slug = get_option('wcl_courses_slug', 'courses');
    $archive_title = get_option('wcl_archive_title', 'Courses');
    
    if (!get_page_by_path($archive_slug)) {
        wp_insert_post(array(
            'post_title' => $archive_title,
            'post_name' => $archive_slug,
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
    wcl_register_course_program_taxonomy();
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
 * Custom meta box for Course Program with ordering functionality.
 */
function wcl_course_program_meta_box($post, $box) {
    $taxonomy = $box['args']['taxonomy'];
    $tax = get_taxonomy($taxonomy);
    $name = 'tax_input[' . $taxonomy . ']';
    
    // Get current program assignment
    $terms = wp_get_object_terms($post->ID, $taxonomy);
    $current_program = !empty($terms) ? $terms[0]->term_id : 0;
    
    // Get program order
    $program_order = get_post_meta($post->ID, '_wcl_program_order', true);
    if (empty($program_order)) {
        $program_order = 0;
    }
    
    // Get all programs
    $programs = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ));
    
    ?>
    <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
        <div style="margin-bottom: 15px;">
            <label for="<?php echo $taxonomy; ?>-select"><strong>Select Program:</strong></label><br>
            <select name="<?php echo $name; ?>[]" id="<?php echo $taxonomy; ?>-select" style="width: 100%; max-width: 400px;">
                <option value="">No Program</option>
                <?php if (!empty($programs)): ?>
                    <?php foreach ($programs as $program): ?>
                        <option value="<?php echo esc_attr($program->term_id); ?>" <?php selected($current_program, $program->term_id); ?>>
                            <?php echo esc_html($program->name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="description">Select the program this course belongs to.</p>
        </div>
        
        <div>
            <label for="wcl_program_order"><strong>Order in Program:</strong></label><br>
            <input type="number" 
                   name="wcl_program_order" 
                   id="wcl_program_order" 
                   value="<?php echo esc_attr($program_order); ?>" 
                   min="0" 
                   step="1" 
                   style="width: 100px;" />
            <p class="description">Set the order of this course within the selected program (0 = first, higher numbers appear later).</p>
        </div>
    </div>
    <?php
}

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
 * Save program order when course is saved.
 */
function wcl_save_course_program_order($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['post_type']) && 'course' === $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Save program order
    if (isset($_POST['wcl_program_order'])) {
        $program_order = absint($_POST['wcl_program_order']);
        update_post_meta($post_id, '_wcl_program_order', $program_order);
    }
}
add_action('save_post', 'wcl_save_course_program_order');

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
 * Add SEO meta box for Course posts (conditionally).
 */
function wcl_add_seo_meta_box() {
    if (get_option('wcl_enable_seo_meta', true)) {
        add_meta_box(
            'wcl_seo_meta',
            'SEO Metadata',
            'wcl_seo_meta_box_callback',
            'course',
            'normal',
            'default'
        );
    }
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
 * Add Social Media meta box for Course posts (conditionally).
 */
function wcl_add_social_meta_box() {
    if (get_option('wcl_enable_social_meta', true)) {
        add_meta_box(
            'wcl_social_meta',
            'Social Media Metadata',
            'wcl_social_meta_box_callback',
            'course',
            'normal',
            'default'
        );
    }
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
    // Course URL Settings
    register_setting('wcl_settings_group', 'wcl_courses_slug', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_title',
        'default' => 'courses',
    ));
    
    register_setting('wcl_settings_group', 'wcl_course_single_slug', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_title',
        'default' => 'course',
    ));
    
    register_setting('wcl_settings_group', 'wcl_category_slug', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_title',
        'default' => 'course-category',
    ));
    
    register_setting('wcl_settings_group', 'wcl_program_slug', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_title',
        'default' => 'course-program',
    ));
    
    // Display Settings
    register_setting('wcl_settings_group', 'wcl_courses_per_page', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 12,
    ));
    
    register_setting('wcl_settings_group', 'wcl_show_featured_image', array(
        'type' => 'boolean',
        'default' => true,
    ));
    
    register_setting('wcl_settings_group', 'wcl_show_excerpt', array(
        'type' => 'boolean',
        'default' => true,
    ));
    
    register_setting('wcl_settings_group', 'wcl_show_categories', array(
        'type' => 'boolean',
        'default' => true,
    ));
    
    register_setting('wcl_settings_group', 'wcl_show_course_meta', array(
        'type' => 'boolean',
        'default' => true,
    ));
    
    register_setting('wcl_settings_group', 'wcl_show_programs', array(
        'type' => 'boolean',
        'default' => true,
    ));
    
    // Archive Settings
    register_setting('wcl_settings_group', 'wcl_archive_title', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'Courses',
    ));
    
    register_setting('wcl_settings_group', 'wcl_archive_description', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default' => '',
    ));
    
    // SEO Settings
    register_setting('wcl_settings_group', 'wcl_enable_seo_meta', array(
        'type' => 'boolean',
        'default' => true,
    ));
    
    register_setting('wcl_settings_group', 'wcl_enable_social_meta', array(
        'type' => 'boolean',
        'default' => true,
    ));
    
    // URL Settings Section
    add_settings_section(
        'wcl_url_settings_section',
        'URL Settings',
        'wcl_url_settings_section_callback',
        'wcl-settings'
    );
    
    // Display Settings Section
    add_settings_section(
        'wcl_display_settings_section',
        'Display Settings',
        'wcl_display_settings_section_callback',
        'wcl-settings'
    );
    
    // Archive Settings Section
    add_settings_section(
        'wcl_archive_settings_section',
        'Archive Page Settings',
        'wcl_archive_settings_section_callback',
        'wcl-settings'
    );
    
    // SEO Settings Section
    add_settings_section(
        'wcl_seo_settings_section',
        'SEO & Social Media Settings',
        'wcl_seo_settings_section_callback',
        'wcl-settings'
    );
    
    // URL Settings Fields
    add_settings_field(
        'wcl_courses_slug',
        'Courses Archive URL Slug',
        'wcl_courses_slug_field_callback',
        'wcl-settings',
        'wcl_url_settings_section'
    );
    
    add_settings_field(
        'wcl_course_single_slug',
        'Single Course URL Slug',
        'wcl_course_single_slug_field_callback',
        'wcl-settings',
        'wcl_url_settings_section'
    );
    
    add_settings_field(
        'wcl_category_slug',
        'Course Category URL Slug',
        'wcl_category_slug_field_callback',
        'wcl-settings',
        'wcl_url_settings_section'
    );
    
    add_settings_field(
        'wcl_program_slug',
        'Course Program URL Slug',
        'wcl_program_slug_field_callback',
        'wcl-settings',
        'wcl_url_settings_section'
    );
    
    // Display Settings Fields
    add_settings_field(
        'wcl_courses_per_page',
        'Courses Per Page',
        'wcl_courses_per_page_field_callback',
        'wcl-settings',
        'wcl_display_settings_section'
    );
    
    add_settings_field(
        'wcl_show_featured_image',
        'Show Featured Images',
        'wcl_show_featured_image_field_callback',
        'wcl-settings',
        'wcl_display_settings_section'
    );
    
    add_settings_field(
        'wcl_show_excerpt',
        'Show Course Excerpts',
        'wcl_show_excerpt_field_callback',
        'wcl-settings',
        'wcl_display_settings_section'
    );
    
    add_settings_field(
        'wcl_show_categories',
        'Show Course Categories',
        'wcl_show_categories_field_callback',
        'wcl-settings',
        'wcl_display_settings_section'
    );
    
    add_settings_field(
        'wcl_show_course_meta',
        'Show Course Meta (Duration, Level)',
        'wcl_show_course_meta_field_callback',
        'wcl-settings',
        'wcl_display_settings_section'
    );
    
    add_settings_field(
        'wcl_show_programs',
        'Show Course Programs',
        'wcl_show_programs_field_callback',
        'wcl-settings',
        'wcl_display_settings_section'
    );
    
    // Archive Settings Fields
    add_settings_field(
        'wcl_archive_title',
        'Archive Page Title',
        'wcl_archive_title_field_callback',
        'wcl-settings',
        'wcl_archive_settings_section'
    );
    
    add_settings_field(
        'wcl_archive_description',
        'Archive Page Description',
        'wcl_archive_description_field_callback',
        'wcl-settings',
        'wcl_archive_settings_section'
    );
    
    // SEO Settings Fields
    add_settings_field(
        'wcl_enable_seo_meta',
        'Enable SEO Meta Fields',
        'wcl_enable_seo_meta_field_callback',
        'wcl-settings',
        'wcl_seo_settings_section'
    );
    
    add_settings_field(
        'wcl_enable_social_meta',
        'Enable Social Media Meta Fields',
        'wcl_enable_social_meta_field_callback',
        'wcl-settings',
        'wcl_seo_settings_section'
    );
}
add_action('admin_init', 'wcl_register_settings');

/**
 * Settings section callbacks.
 */
function wcl_url_settings_section_callback() {
    echo '<p>Configure URL structures for courses and categories. <strong>Important:</strong> After changing any URL settings, you must visit <a href="' . admin_url('options-permalink.php') . '">Settings → Permalinks</a> and click "Save Changes" to update your site\'s rewrite rules.</p>';
}

function wcl_display_settings_section_callback() {
    echo '<p>Control what information is displayed in course listings and archives.</p>';
}

function wcl_archive_settings_section_callback() {
    echo '<p>Customize the course archive page title and description.</p>';
}

function wcl_seo_settings_section_callback() {
    echo '<p>Enable or disable SEO and social media meta fields for individual courses.</p>';
}

/**
 * Field callbacks for URL settings.
 */
function wcl_courses_slug_field_callback() {
    $slug = get_option('wcl_courses_slug', 'courses');
    echo '<input type="text" name="wcl_courses_slug" value="' . esc_attr($slug) . '" class="regular-text" />';
    echo '<p class="description">Used for the courses archive page URL, e.g., <code>/' . esc_html($slug) . '/</code></p>';
}

function wcl_course_single_slug_field_callback() {
    $slug = get_option('wcl_course_single_slug', 'course');
    echo '<input type="text" name="wcl_course_single_slug" value="' . esc_attr($slug) . '" class="regular-text" />';
    echo '<p class="description">Used for individual course URLs, e.g., <code>/' . esc_html($slug) . '/course-name/</code></p>';
}

function wcl_category_slug_field_callback() {
    $slug = get_option('wcl_category_slug', 'course-category');
    echo '<input type="text" name="wcl_category_slug" value="' . esc_attr($slug) . '" class="regular-text" />';
    echo '<p class="description">Used for course category URLs, e.g., <code>/' . esc_html($slug) . '/category-name/</code></p>';
}

function wcl_program_slug_field_callback() {
    $slug = get_option('wcl_program_slug', 'course-program');
    echo '<input type="text" name="wcl_program_slug" value="' . esc_attr($slug) . '" class="regular-text" />';
    echo '<p class="description">Used for course program URLs, e.g., <code>/' . esc_html($slug) . '/program-name/</code></p>';
}

/**
 * Field callbacks for display settings.
 */
function wcl_courses_per_page_field_callback() {
    $per_page = get_option('wcl_courses_per_page', 12);
    echo '<input type="number" name="wcl_courses_per_page" value="' . esc_attr($per_page) . '" class="small-text" min="1" max="100" />';
    echo '<p class="description">Number of courses to display per page in archives and listings.</p>';
}

function wcl_show_featured_image_field_callback() {
    $show = get_option('wcl_show_featured_image', true);
    echo '<label><input type="checkbox" name="wcl_show_featured_image" value="1" ' . checked($show, true, false) . ' /> Show featured images in course listings</label>';
}

function wcl_show_excerpt_field_callback() {
    $show = get_option('wcl_show_excerpt', true);
    echo '<label><input type="checkbox" name="wcl_show_excerpt" value="1" ' . checked($show, true, false) . ' /> Show course excerpts in listings</label>';
}

function wcl_show_categories_field_callback() {
    $show = get_option('wcl_show_categories', true);
    echo '<label><input type="checkbox" name="wcl_show_categories" value="1" ' . checked($show, true, false) . ' /> Show course categories in listings</label>';
}

function wcl_show_course_meta_field_callback() {
    $show = get_option('wcl_show_course_meta', true);
    echo '<label><input type="checkbox" name="wcl_show_course_meta" value="1" ' . checked($show, true, false) . ' /> Show course meta information (duration, level) in listings</label>';
}

function wcl_show_programs_field_callback() {
    $show = get_option('wcl_show_programs', true);
    echo '<label><input type="checkbox" name="wcl_show_programs" value="1" ' . checked($show, true, false) . ' /> Show course programs and program order in listings</label>';
}

/**
 * Field callbacks for archive settings.
 */
function wcl_archive_title_field_callback() {
    $title = get_option('wcl_archive_title', 'Courses');
    echo '<input type="text" name="wcl_archive_title" value="' . esc_attr($title) . '" class="regular-text" />';
    echo '<p class="description">Title displayed on the course archive page.</p>';
}

function wcl_archive_description_field_callback() {
    $description = get_option('wcl_archive_description', '');
    echo '<textarea name="wcl_archive_description" rows="3" class="large-text">' . esc_textarea($description) . '</textarea>';
    echo '<p class="description">Optional description text displayed on the course archive page.</p>';
}

/**
 * Field callbacks for SEO settings.
 */
function wcl_enable_seo_meta_field_callback() {
    $enable = get_option('wcl_enable_seo_meta', true);
    echo '<label><input type="checkbox" name="wcl_enable_seo_meta" value="1" ' . checked($enable, true, false) . ' /> Enable SEO meta fields (title, description) for individual courses</label>';
}

function wcl_enable_social_meta_field_callback() {
    $enable = get_option('wcl_enable_social_meta', true);
    echo '<label><input type="checkbox" name="wcl_enable_social_meta" value="1" ' . checked($enable, true, false) . ' /> Enable social media meta fields (Open Graph, Twitter) for individual courses</label>';
}

/**
 * Render enhanced settings page.
 */
function wcl_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>WP Course Listings Settings</h1>
        
        <?php 
        // Show admin notices
        if (isset($_GET['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        }
        ?>
        
        <div class="wcl-settings-wrapper" style="display: flex; gap: 20px;">
            <div class="wcl-settings-main" style="flex: 2;">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wcl_settings_group');
                    do_settings_sections('wcl-settings');
                    submit_button();
                    ?>
                </form>
            </div>
            
            <div class="wcl-settings-sidebar" style="flex: 1;">
                <div class="postbox">
                    <h3 class="hndle"><span>Quick Actions</span></h3>
                    <div class="inside">
                        <p><a href="<?php echo admin_url('options-permalink.php'); ?>" class="button button-secondary">Flush Permalinks</a></p>
                        <p class="description">Click this after changing URL settings to update rewrite rules.</p>
                        
                        <hr>
                        
                        <p><a href="<?php echo admin_url('post-new.php?post_type=course'); ?>" class="button button-primary">Add New Course</a></p>
                        <p><a href="<?php echo admin_url('edit.php?post_type=course'); ?>" class="button button-secondary">Manage Courses</a></p>
                        <p><a href="<?php echo admin_url('edit-tags.php?taxonomy=course_category&post_type=course'); ?>" class="button button-secondary">Manage Categories</a></p>
                        <p><a href="<?php echo admin_url('edit-tags.php?taxonomy=course_program&post_type=course'); ?>" class="button button-secondary">Manage Programs</a></p>
                        
                        <hr>
                        
                        <h4>Shortcodes</h4>
                        <p><strong>Display all courses:</strong><br>
                        <code>[course_listings]</code></p>
                        
                        <p><strong>With custom limit:</strong><br>
                        <code>[course_listings limit="6"]</code></p>
                        
                        <p><strong>From specific category:</strong><br>
                        <code>[course_listings category="web-development"]</code></p>
                        
                        <p><strong>From specific program:</strong><br>
                        <code>[course_listings program="certification-program"]</code></p>
                        
                        <p><strong>Ordered by program sequence:</strong><br>
                        <code>[course_listings program="cert-program" order_by_program="true"]</code></p>
                    </div>
                </div>
                
                <div class="postbox">
                    <h3 class="hndle"><span>Plugin Info</span></h3>
                    <div class="inside">
                        <p><strong>Version:</strong> 0.5.1</p>
                        <p><strong>Active Courses:</strong> <?php echo wp_count_posts('course')->publish; ?></p>
                        <p><strong>Course Categories:</strong> <?php echo wp_count_terms('course_category'); ?></p>
                        <p><strong>Course Programs:</strong> <?php echo wp_count_terms('course_program'); ?></p>
                        <p><strong>Current Slug:</strong> <code>/<?php echo esc_html(get_option('wcl_courses_slug', 'courses')); ?>/</code></p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .wcl-settings-wrapper .postbox {
            margin-bottom: 20px;
        }
        .wcl-settings-wrapper .postbox h3 {
            padding: 8px 12px;
            margin: 0;
            line-height: 1.4;
        }
        .wcl-settings-wrapper .inside {
            padding: 6px 12px 12px;
        }
        .wcl-settings-wrapper .inside p {
            margin: 6px 0;
        }
        .wcl-settings-wrapper .inside hr {
            margin: 15px 0;
            border: none;
            border-top: 1px solid #ddd;
        }
        .wcl-settings-wrapper code {
            background: #f1f1f1;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 12px;
        }
        </style>
    </div>
    <?php
}

/**
 * Use the custom slugs for the course post type and taxonomy.
 */
function wcl_dynamic_course_slug($args, $post_type) {
    if ($post_type === 'course') {
        $archive_slug = get_option('wcl_courses_slug', 'courses');
        $single_slug = get_option('wcl_course_single_slug', 'course');
        
        $args['rewrite'] = array('slug' => $single_slug);
        $args['has_archive'] = $archive_slug;
    }
    return $args;
}
add_filter('register_post_type_args', 'wcl_dynamic_course_slug', 10, 2);

/**
 * Use custom slug for course category and program taxonomies.
 */
function wcl_dynamic_taxonomy_slugs($args, $taxonomy) {
    if ($taxonomy === 'course_category') {
        $category_slug = get_option('wcl_category_slug', 'course-category');
        $args['rewrite'] = array('slug' => $category_slug);
    } elseif ($taxonomy === 'course_program') {
        $program_slug = get_option('wcl_program_slug', 'course-program');
        $args['rewrite'] = array('slug' => $program_slug);
    }
    return $args;
}
add_filter('register_taxonomy_args', 'wcl_dynamic_taxonomy_slugs', 10, 2);

/**
 * Add rewrite rule for the custom archive slug.
 */
function wcl_dynamic_courses_rewrite_rule() {
    $archive_slug = get_option('wcl_courses_slug', 'courses');
    add_rewrite_rule('^' . $archive_slug . '/?$', 'index.php?post_type=course', 'top');
}
add_action('init', 'wcl_dynamic_courses_rewrite_rule', 20);

/**
 * Flush rewrite rules when URL settings change.
 */
function wcl_maybe_flush_rewrite_rules() {
    if (!is_admin()) {
        return;
    }
    
    $settings_to_check = array(
        'wcl_courses_slug' => 'courses',
        'wcl_course_single_slug' => 'course', 
        'wcl_category_slug' => 'course-category',
        'wcl_program_slug' => 'course-program'
    );
    
    $needs_flush = false;
    foreach ($settings_to_check as $setting => $default) {
        $old_value = get_option($setting . '_flushed', '');
        $current_value = get_option($setting, $default);
        
        if ($old_value !== $current_value) {
            update_option($setting . '_flushed', $current_value);
            $needs_flush = true;
        }
    }
    
    if ($needs_flush) {
        flush_rewrite_rules();
    }
}
add_action('admin_init', 'wcl_maybe_flush_rewrite_rules');

/**
 * Customize archive title and description.
 */
function wcl_custom_archive_title($title) {
    if (is_post_type_archive('course')) {
        $custom_title = get_option('wcl_archive_title', 'Courses');
        if (!empty($custom_title)) {
            return $custom_title;
        }
    }
    return $title;
}
add_filter('get_the_archive_title', 'wcl_custom_archive_title');

/**
 * Add archive description.
 */
function wcl_custom_archive_description($description) {
    if (is_post_type_archive('course')) {
        $custom_description = get_option('wcl_archive_description', '');
        if (!empty($custom_description)) {
            return '<p>' . esc_html($custom_description) . '</p>';
        }
    }
    return $description;
}
add_filter('get_the_archive_description', 'wcl_custom_archive_description');

/**
 * Control posts per page for course archive.
 */
function wcl_course_archive_posts_per_page($query) {
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('course')) {
        $per_page = get_option('wcl_courses_per_page', 12);
        $query->set('posts_per_page', $per_page);
    }
}
add_action('pre_get_posts', 'wcl_course_archive_posts_per_page');

/**
 * Automatically order courses by program order on program archive pages.
 */
function wcl_program_archive_ordering($query) {
    if (!is_admin() && $query->is_main_query() && is_tax('course_program')) {
        $query->set('meta_key', '_wcl_program_order');
        $query->set('orderby', array(
            'meta_value_num' => 'ASC',
            'title' => 'ASC'
        ));
    }
}
add_action('pre_get_posts', 'wcl_program_archive_ordering');

/**
 * Add basic styling for course listings.
 */
function wcl_add_frontend_styles() {
    if (is_post_type_archive('course') || is_singular('course') || is_tax('course_category')) {
        echo '<style>
        .wcl-course-listings {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .wcl-course {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            background: #fff;
        }
        .wcl-course-thumb {
            margin-bottom: 15px;
        }
        .wcl-course-thumb img {
            width: 100%;
            height: auto;
            border-radius: 3px;
        }
        .wcl-course-title {
            margin: 0 0 10px 0;
            font-size: 1.2em;
        }
        .wcl-course-title a {
            text-decoration: none;
            color: #333;
        }
        .wcl-course-title a:hover {
            color: #0073aa;
        }
        .wcl-course-excerpt {
            margin-bottom: 10px;
            color: #666;
        }
        .wcl-course-meta {
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #666;
        }
        .wcl-course-categories {
            font-size: 0.9em;
            color: #666;
        }
        .wcl-course-categories a {
            color: #0073aa;
            text-decoration: none;
        }
        .wcl-course-categories a:hover {
            text-decoration: underline;
        }
        .wcl-course-programs {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        .wcl-course-programs a {
            color: #8e44ad;
            text-decoration: none;
            font-weight: 500;
        }
        .wcl-course-programs a:hover {
            text-decoration: underline;
        }
        .wcl-program-order {
            color: #8e44ad;
            font-weight: 600;
            font-size: 0.85em;
        }
        /* Program-specific course listing styles */
        .wcl-course-listings.program-ordered .wcl-course {
            position: relative;
        }
        .wcl-course-listings.program-ordered .wcl-course::before {
            content: attr(data-program-order);
            position: absolute;
            top: 10px;
            right: 10px;
            background: #8e44ad;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        /* Program archive page styles */
        .tax-course_program .wcl-course-listings {
            position: relative;
        }
        .tax-course_program .wcl-course-listings::before {
            content: "Courses in Program Order";
            display: block;
            background: #f8f9fa;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-left: 4px solid #8e44ad;
            font-weight: 600;
            color: #333;
        }
        </style>';
    }
}
add_action('wp_head', 'wcl_add_frontend_styles');

/**
 * Remove author-related functionality from courses.
 */

// Hide author column in admin course list and add program column
function wcl_modify_course_columns($columns) {
    unset($columns['author']);
    
    // Add program column after title
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['course_program'] = 'Program';
            $new_columns['program_order'] = 'Program Order';
        }
    }
    
    return $new_columns;
}
add_filter('manage_course_posts_columns', 'wcl_modify_course_columns');

// Populate custom columns
function wcl_populate_course_columns($column, $post_id) {
    switch ($column) {
        case 'course_program':
            $programs = get_the_terms($post_id, 'course_program');
            if ($programs && !is_wp_error($programs)) {
                $program_names = array();
                foreach ($programs as $program) {
                    $program_names[] = '<a href="' . esc_url(add_query_arg(array('course_program' => $program->slug), admin_url('edit.php?post_type=course'))) . '">' . esc_html($program->name) . '</a>';
                }
                echo implode(', ', $program_names);
            } else {
                echo '—';
            }
            break;
            
        case 'program_order':
            $order = get_post_meta($post_id, '_wcl_program_order', true);
            echo !empty($order) ? intval($order) + 1 : '—';
            break;
    }
}
add_action('manage_course_posts_custom_column', 'wcl_populate_course_columns', 10, 2);

// Make program order column sortable
function wcl_sortable_course_columns($columns) {
    $columns['program_order'] = 'program_order';
    $columns['course_program'] = 'course_program';
    return $columns;
}
add_filter('manage_edit-course_sortable_columns', 'wcl_sortable_course_columns');

// Handle sorting for program order column
function wcl_course_column_orderby($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    
    if ('program_order' === $orderby) {
        $query->set('meta_key', '_wcl_program_order');
        $query->set('orderby', 'meta_value_num');
    } elseif ('course_program' === $orderby) {
        $query->set('orderby', 'taxonomy');
        $query->set('taxonomy', 'course_program');
    }
}
add_action('pre_get_posts', 'wcl_course_column_orderby');

// Add program filter dropdown to admin course list
function wcl_add_program_filter() {
    global $typenow;
    
    if ($typenow === 'course') {
        $taxonomy = 'course_program';
        $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ));
        
        if (!empty($terms)) {
            echo '<select name="' . $taxonomy . '" id="' . $taxonomy . '">';
            echo '<option value="">All Programs</option>';
            foreach ($terms as $term) {
                printf(
                    '<option value="%s"%s>%s (%d)</option>',
                    $term->slug,
                    selected($selected, $term->slug, false),
                    $term->name,
                    $term->count
                );
            }
            echo '</select>';
        }
    }
}
add_action('restrict_manage_posts', 'wcl_add_program_filter');

// Remove author metabox from course edit screen
function wcl_remove_course_author_metabox() {
    remove_meta_box('authordiv', 'course', 'normal');
}
add_action('admin_menu', 'wcl_remove_course_author_metabox');

// Hide author info and post meta from course queries and displays
function wcl_hide_course_author_info() {
    if (is_singular('course') || is_post_type_archive('course') || is_tax('course_category')) {
        // Remove author info from post meta
        add_filter('the_author', '__return_empty_string');
        add_filter('get_the_author', '__return_empty_string');
        add_filter('the_author_link', '__return_empty_string');
        add_filter('get_the_author_link', '__return_empty_string');
        add_filter('get_the_author_meta', '__return_empty_string');
        
        // Remove date info from post meta
        add_filter('the_date', '__return_empty_string');
        add_filter('get_the_date', '__return_empty_string');
        add_filter('the_time', '__return_empty_string');
        add_filter('get_the_time', '__return_empty_string');
        add_filter('get_the_modified_date', '__return_empty_string');
        add_filter('get_the_modified_time', '__return_empty_string');
        
        // Hide author in post class
        add_filter('post_class', function($classes) {
            return array_filter($classes, function($class) {
                return strpos($class, 'author-') !== 0;
            });
        });
    }
}
add_action('wp', 'wcl_hide_course_author_info');

// Remove author from REST API responses for courses
function wcl_remove_course_author_rest($response, $post, $request) {
    if ($post->post_type === 'course') {
        unset($response->data['author']);
        unset($response->data['author_name']);
        unset($response->data['date']);
        unset($response->data['date_gmt']);
        unset($response->data['modified']);
        unset($response->data['modified_gmt']);
    }
    return $response;
}
add_filter('rest_prepare_course', 'wcl_remove_course_author_rest', 10, 3);

// Remove post meta line completely from course posts
function wcl_remove_course_post_meta() {
    if (is_singular('course') || is_post_type_archive('course') || is_tax('course_category')) {
        // Remove common post meta elements via CSS
        add_action('wp_head', function() {
            echo '<style>
            /* Hide post meta information on course posts */
            .course .entry-meta,
            .course .post-meta,
            .course .byline,
            .course .posted-on,
            .course .bypostauthor,
            .single-course .entry-meta,
            .single-course .post-meta,
            .single-course .byline,
            .single-course .posted-on,
            .single-course .bypostauthor,
            .post-type-archive-course .entry-meta,
            .post-type-archive-course .post-meta,
            .post-type-archive-course .byline,
            .post-type-archive-course .posted-on,
            .post-type-archive-course .bypostauthor,
            body.single-course .entry-header .entry-meta,
            body.single-course .entry-footer .entry-meta,
            body.post-type-archive-course .entry-header .entry-meta,
            body.post-type-archive-course .entry-footer .entry-meta {
                display: none !important;
            }
            
            /* Hide author and date specific elements */
            .course .author,
            .course .published,
            .course .updated,
            .single-course .author,
            .single-course .published, 
            .single-course .updated,
            .post-type-archive-course .author,
            .post-type-archive-course .published,
            .post-type-archive-course .updated {
                display: none !important;
            }
            </style>';
        });
    }
}
add_action('wp', 'wcl_remove_course_post_meta');

// Remove post meta from course content via content filter
function wcl_filter_course_content($content) {
    if (is_singular('course') && in_the_loop() && is_main_query()) {
        global $post;
        if ($post->post_type === 'course') {
            // Remove any remaining author/date information that might be in content
            $patterns = array(
                '/Posted by[^<]*<[^>]*>[^<]*<\/[^>]*>/i',
                '/By[^<]*<[^>]*>[^<]*<\/[^>]*>/i',
                '/Published on[^<]*\d{4}/i',
                '/Posted on[^<]*\d{4}/i',
                '/<time[^>]*>.*?<\/time>/i',
                '/<span[^>]*class="[^"]*author[^"]*"[^>]*>.*?<\/span>/i',
                '/<span[^>]*class="[^"]*date[^"]*"[^>]*>.*?<\/span>/i',
            );
            
            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }
        }
    }
    return $content;
}
add_filter('the_content', 'wcl_filter_course_content', 20);

// Remove post meta from excerpts
function wcl_filter_course_excerpt($excerpt) {
    global $post;
    if ($post && $post->post_type === 'course') {
        // Similar pattern removal for excerpts
        $patterns = array(
            '/Posted by[^<]*<[^>]*>[^<]*<\/[^>]*>/i',
            '/By[^<]*<[^>]*>[^<]*<\/[^>]*>/i',
            '/Published on[^<]*\d{4}/i',
            '/Posted on[^<]*\d{4}/i',
        );
        
        foreach ($patterns as $pattern) {
            $excerpt = preg_replace($pattern, '', $excerpt);
        }
    }
    return $excerpt;
}
add_filter('the_excerpt', 'wcl_filter_course_excerpt', 20);

// Override common theme functions that display post meta
function wcl_override_theme_post_meta() {
    if (is_singular('course') || is_post_type_archive('course') || is_tax('course_category')) {
        // Common theme functions that display post meta
        add_filter('twentytwentyone_posted_on', '__return_empty_string');
        add_filter('twentytwenty_posted_on', '__return_empty_string');
        add_filter('twentynineteen_posted_on', '__return_empty_string');
        add_filter('twentyseventeen_posted_on', '__return_empty_string');
        add_filter('twentysixteen_posted_on', '__return_empty_string');
        
        // Generic post meta filters
        add_filter('get_post_metadata', function($value, $object_id, $meta_key) {
            global $post;
            if ($post && $post->post_type === 'course' && in_array($meta_key, array('_edit_last', '_edit_lock'))) {
                return '';
            }
            return $value;
        }, 10, 3);
    }
}
add_action('wp', 'wcl_override_theme_post_meta');