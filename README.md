
# WP Course Listings Plugin

This plugin lets you manage and display courses as a custom post type in WordPress, with custom fields and flexible display options.

## Features
- Custom post type: **Course**
- Fields: Title, Description, Price, Post Image URL, Thumbnail Image URL, Course Post URL
- Assign courses to categories (custom taxonomy)
- Add/edit/delete courses in the WordPress admin under **Courses**
- Edit the course permalink (slug) in the editor, just like posts/pages
- Shortcode to display courses by category and layout
- Thumbnail layout: image and title link to the course, with a hover effect

## How to Use

### 1. Add/Edit Courses
- Go to **Courses** in the WordPress admin menu
- Add a new course or edit an existing one
- Fill in the fields: Title, Description, Price, Post Image URL, Thumbnail Image URL, Course Post URL
- Assign the course to one or more categories
- You can edit the course permalink (URL slug) under the title

### 2. Display Courses with Shortcode
Add the following shortcode to any post or page:

```
[wpcl_courses category="your-category-slug" layout="bullet|table|thumbnail"]
```

**Parameters:**
- `category` (optional): The slug of the course category to display. Leave blank to show all courses.
- `layout` (optional): Display style. Options:
	- `bullet`: Simple bullet list
	- `table`: Table with title, description, price, image, and post URL
	- `thumbnail`: Grid of thumbnails. Image and title link to the course. Hover effect highlights the card.

### 3. Example

Display all courses in the "web-development" category as thumbnails:

```
[wpcl_courses category="web-development" layout="thumbnail"]
```

### 4. Notes
- The plugin adds basic styles for table, bullet, and thumbnail layouts.
- In the thumbnail layout, clicking the image or title takes you to the course page. Hovering over a card highlights it.
- The plugin works with the standard WordPress permalink editor for courses.

---
For support or questions, contact the plugin author.