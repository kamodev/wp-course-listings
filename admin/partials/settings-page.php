<?php
/**
 * Settings page markup.
 *
 * @package WP_Course_Listings
 *
 * Included from WP_Course_Listings\Settings::render_page(), which sets:
 *
 * @var string        $settings_group Settings group name.
 * @var string        $settings_page  Settings page slug.
 * @var string        $version        Plugin version.
 * @var string        $archive_slug   Course archive slug.
 * @var stdClass      $course_counts  Course counts by status.
 * @var int|WP_Error  $category_count Number of course categories.
 * @var int|WP_Error  $program_count  Number of course programs.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="wcl-settings-wrapper">
		<div class="wcl-settings-main">
			<form method="post" action="options.php">
				<?php
				settings_fields( $settings_group );
				do_settings_sections( $settings_page );
				submit_button();
				?>
			</form>
		</div>

		<div class="wcl-settings-sidebar">
			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Quick Actions', 'wp-course-listings' ); ?></span></h2>
				<div class="inside">
					<p><a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Flush Permalinks', 'wp-course-listings' ); ?></a></p>
					<p class="description"><?php esc_html_e( 'Click this after changing URL settings to update rewrite rules.', 'wp-course-listings' ); ?></p>

					<hr>

					<p><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=course' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Course', 'wp-course-listings' ); ?></a></p>
					<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=course' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Manage Courses', 'wp-course-listings' ); ?></a></p>
					<p><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=course_category&post_type=course' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Manage Categories', 'wp-course-listings' ); ?></a></p>
					<p><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=course_program&post_type=course' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Manage Programs', 'wp-course-listings' ); ?></a></p>

					<hr>

					<h4><?php esc_html_e( 'Shortcodes', 'wp-course-listings' ); ?></h4>
					<p><strong><?php esc_html_e( 'Display all courses:', 'wp-course-listings' ); ?></strong><br>
					<code>[course_listings]</code></p>

					<p><strong><?php esc_html_e( 'With custom limit:', 'wp-course-listings' ); ?></strong><br>
					<code>[course_listings limit="6"]</code></p>

					<p><strong><?php esc_html_e( 'From specific category:', 'wp-course-listings' ); ?></strong><br>
					<code>[course_listings category="web-development"]</code></p>

					<p><strong><?php esc_html_e( 'From specific program:', 'wp-course-listings' ); ?></strong><br>
					<code>[course_listings program="certification-program"]</code></p>

					<p><strong><?php esc_html_e( 'Ordered by program sequence:', 'wp-course-listings' ); ?></strong><br>
					<code>[course_listings program="cert-program" order_by_program="true"]</code></p>
				</div>
			</div>

			<div class="postbox">
				<h2 class="hndle"><span><?php esc_html_e( 'Plugin Info', 'wp-course-listings' ); ?></span></h2>
				<div class="inside">
					<p><strong><?php esc_html_e( 'Version:', 'wp-course-listings' ); ?></strong> <?php echo esc_html( $version ); ?></p>
					<p><strong><?php esc_html_e( 'Active Courses:', 'wp-course-listings' ); ?></strong> <?php echo esc_html( number_format_i18n( $course_counts->publish ) ); ?></p>
					<p><strong><?php esc_html_e( 'Course Categories:', 'wp-course-listings' ); ?></strong> <?php echo esc_html( number_format_i18n( is_wp_error( $category_count ) ? 0 : $category_count ) ); ?></p>
					<p><strong><?php esc_html_e( 'Course Programs:', 'wp-course-listings' ); ?></strong> <?php echo esc_html( number_format_i18n( is_wp_error( $program_count ) ? 0 : $program_count ) ); ?></p>
					<p><strong><?php esc_html_e( 'Current Slug:', 'wp-course-listings' ); ?></strong> <code>/<?php echo esc_html( $archive_slug ); ?>/</code></p>
				</div>
			</div>
		</div>
	</div>
</div>
