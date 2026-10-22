# WP Course Listings

A WordPress plugin for managing courses as a custom post type, grouping them into categories and ordered programs, and listing them anywhere with a shortcode.

See [`readme.txt`](readme.txt) for the full feature list, shortcode reference and changelog.

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later

## Installation

1. Download `wp-course-listings.zip` from the [latest release](https://github.com/kamodev/wp-course-listings/releases/latest).
2. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin** and upload the zip.
3. Activate the plugin and configure it under **Settings → Course Listings**.

## Usage

Add courses under **Courses** in the admin menu, then list them with the shortcode:

```
[course_listings]
[course_listings category="web-development" limit="6"]
[course_listings program="certification-program" order_by_program="true"]
```

| Attribute | Description | Default |
| --- | --- | --- |
| `limit` | Number of courses to show | Courses Per Page setting (12) |
| `category` | Course category slug | All categories |
| `program` | Course program slug | All programs |
| `order_by_program` | Order by each course's position in its program | `false` |
| `show_featured_image` | Show the featured image | Display setting |
| `show_excerpt` | Show the excerpt | Display setting |
| `show_categories` | Show category links | Display setting |
| `show_programs` | Show program links and position | Display setting |
| `show_meta` | Show duration and level | Display setting |

## Project structure

```
wp-course-listings.php     Plugin header, constants and bootstrap
uninstall.php              Removes plugin settings when the plugin is deleted
readme.txt                 WordPress readme (description, FAQ, changelog)
includes/
  class-plugin.php         Wires the components into WordPress
  class-options.php        Option names and defaults
  class-post-types.php     Course post type, taxonomies and rewrite rules
  class-activator.php      Activation and deactivation
admin/
  class-admin.php          Meta boxes, saving and the course list table
  class-settings.php       Settings → Course Listings
  partials/                Admin templates
  css/, js/                Admin assets
public/
  class-frontend.php       Front-end output, SEO tags and archive tweaks
  class-shortcode.php      [course_listings]
  css/                     Front-end styles
languages/                 Translation template (.pot)
```

All PHP classes live in the `WP_Course_Listings` namespace. Stored data keeps the `wcl_` prefix: option names, `_wcl_*` post meta keys and the `wcl_course_listing_meta` filter.

## Development

Install the development tools with [Composer](https://getcomposer.org/), then check the code against the WordPress Coding Standards:

```sh
composer install
composer lint        # or: make lint
composer lint:fix    # or: make fix
```

Other tasks:

```sh
make dist            # Build wp-course-listings.zip from the committed files
make pot             # Regenerate languages/wp-course-listings.pot (requires WP-CLI)
make check-version   # Check that all version numbers match
```

CI runs the coding standards check, a PHP syntax check on PHP 7.4 to 8.5, and the version check on pull requests and pushes to `main`.

## Releasing

1. Update the version in the plugin header and the `VERSION` constant in `wp-course-listings.php`, and the `Stable tag` in `readme.txt`. Move the `Unreleased` changelog entries under the new version.
2. Merge to `main`, then tag the release and push the tag:

   ```sh
   git tag -a v1.2.3 -m "WP Course Listings 1.2.3"
   git push origin v1.2.3
   ```

The [release workflow](.github/workflows/release.yml) checks that the tag matches the plugin version, builds `wp-course-listings.zip` and publishes a GitHub release with the zip attached.

To release without pushing a tag, run the **Release** workflow from the Actions tab and enter the branch, tag or commit to release. The workflow creates the `v<version>` tag on that commit.

## License

GPL-2.0-or-later
