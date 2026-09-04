=== LOW Mega Menu ===
Contributors: TBD
Tags: mega menu, navigation, menu
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build multi-column mega menu panels and attach them to WordPress nav menu items.

== Description ==

LOW Mega Menu lets site owners build multi-column dropdown panels and attach them to items in any WordPress navigation menu.

Features:

* Visual drag-and-drop builder for multi-column mega menu panels.
* Per-column width fractions, labels, and optional left/right borders.
* Content modules: Link List, Post Query, Image, CTA, Excerpt, Scroll To, and custom Code.
* Post Query module with thumbnail, category, and date layout.
* Scroll To module that links a menu item to a specific heading on any page or post.
* AJAX search bar: results (posts and pages) appear in a mega menu panel on desktop and inside the drawer on mobile, with a clear button and no-JS fallback.
* Mobile takeover navigation with drill-down panels.
* Theme-agnostic styling that resists host-theme navigation CSS (tested with Divi).

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/low-mega-menu/`, or install through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.

== Changelog ==

= 1.5.0 =
* Redesigned the search bar: soft shadowed white field with rounded corners, placeholder on the left, and a clickable magnifying-glass submit control using the plugin SVG icon.
* Added a custom title override to the Page / Post Excerpt module (leave blank to use the page or post title).

= 1.4.0 =
* Added a custom excerpt override to the Page / Post Excerpt module: enter your own teaser text to replace the post's excerpt (takes precedence over the length and full-content options).
* Fixed a double-escaping issue on the auto-generated excerpt so characters like ampersands render correctly.
* Search field, clear, and Back controls now inherit the active theme's font instead of the browser default.

= 1.3.0 =
* Added a lightweight WYSIWYG editor (bold, italic, links, lists, plus a code view) to the Call to Action body and the Code / Shortcode content fields. The Code field defaults to the code view so shortcodes and raw HTML stay intact.
* Added Call to Action color controls: text color (heading and body), button text color, and button background color.
* Minor CSS refinements: centered search status message, fixed a 1px seam under Divi's fixed header, and added spacing below the CTA body.

= 1.2.0 =
* Added an AJAX search bar to the mega menu: live results for posts and pages with thumbnail, type, and excerpt.
* Desktop results open in a full-width mega menu panel; mobile relocates the search to the top of the drawer and expands into a full search view with a Back button.
* Added a clear (X) button inside the search field and a settings toggle to enable/disable search (on by default).
* Added a public REST endpoint (low-mm/v1/search) with filters for searchable post types and result count.
* Divi: hide the built-in Divi search, vertically center the menu, and refine desktop/mobile header and drawer layout.

= 1.1.0 =
* Added Scroll To module: link a menu item to a heading on any page or post, with smooth scrolling and an adjustable offset.
* Added per-column left/right border options in the builder.
* Added Post Query module styling: floated thumbnail with title, category, and date.
* Added a theme-agnostic CSS reset so host-theme navigation styles no longer hide or mis-size panel content (Divi compatibility).
* Hardened Post Query rendering with normalized settings and a graceful empty state for editors.
* Performance and coding-standards improvements (cached attachment lookups, PHPCS fixes).

= 1.0.0 =
* Initial development started.
