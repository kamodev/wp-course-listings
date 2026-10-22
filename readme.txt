=== WP Course Listings ===
Tags: courses, course catalog, education, listings, shortcode
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage courses as a custom post type, group them into categories and ordered programs, and list them anywhere with a shortcode.

== Description ==

WP Course Listings adds a **Course** post type to WordPress with everything needed to publish a course catalog:

* **Course categories** and **course programs** (both hierarchical).
* **Program ordering** so courses appear in sequence on program archives and in listings.
* **Course details**: duration and level.
* **SEO fields** (title and description) and **social media fields** (Open Graph and Twitter title, description and image) per course.
* **Customizable URLs** for the course archive, single courses, categories and programs.
* **Display settings** for what course listings show.
* **Author and date information hidden** on course pages.

= Shortcode =

`[course_listings]` lists courses in a responsive grid. Attributes:

* `limit` – number of courses to show. Defaults to the Courses Per Page setting.
* `category` – course category slug to filter by.
* `program` – course program slug to filter by.
* `order_by_program` – `true` to order courses by their position in the program.
* `show_featured_image`, `show_excerpt`, `show_categories`, `show_programs`, `show_meta` – `true` or `false` to override the display settings.

Example: `[course_listings program="certification-program" order_by_program="true"]`

== Installation ==

1. Download `wp-course-listings.zip` from the [releases page](https://github.com/kamodev/wp-course-listings/releases).
2. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin** and upload the zip.
3. Activate the plugin. A **Courses** page containing `[course_listings]` is created if no page uses the archive slug yet.
4. Configure the plugin under **Settings → Course Listings**.

== Frequently Asked Questions ==

= I changed a URL slug and now get a 404 =

Rewrite rules are refreshed automatically on the next page load after saving. If a URL still returns a 404, visit **Settings → Permalinks** and click **Save Changes**.

= What happens to my courses if I delete the plugin? =

Deleting the plugin removes its settings. Courses, categories, programs and course details stay in the database so nothing is lost if you reinstall.

== Changelog ==

= Unreleased =
* Restructured the plugin into separate classes under `includes/`, `admin/` and `public/` following the Plugin Handbook layout.
* Moved inline styles and scripts into enqueued CSS and JavaScript files. Listing styles now also load on pages that use the shortcode and on course program archives.
* The Order in Program field is now in the Course Details box, so it can be set in the block editor.
* Course pages now output a single `<title>` tag, using the SEO title when set.
* Shortcode flags such as `show_excerpt="false"` are now respected.
* Course program archives now hide author and date information like the other course views.
* Added translation support, a Settings link on the Plugins screen and an uninstall routine that removes the plugin's settings.
* Hardened input sanitization, output escaping and nonce checks.

= 0.5.1 =
* First tagged release.
