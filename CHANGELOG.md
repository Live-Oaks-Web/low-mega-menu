# Changelog

## 1.2.0

- Added an AJAX search bar to the mega menu: live results for posts and pages with thumbnail, type, and excerpt.
- Desktop results open in a full-width mega menu panel; mobile relocates the search to the top of the drawer and expands into a full search view with a Back button.
- Added a clear (X) button inside the search field and a settings toggle to enable/disable search (on by default).
- Added a public REST endpoint (`low-mm/v1/search`) with `low_mm_search_post_types` and `low_mm_search_results_count` filters.
- Divi: hide the built-in Divi search, vertically center the menu within the header, and refine desktop/mobile header and drawer layout (drawer/panel anchored to the header bottom).

## 1.1.0

- Added Scroll To module: link a menu item to a heading on any page or post, with smooth scrolling and an adjustable offset.
- Added per-column left/right border options in the builder.
- Added Post Query module styling: floated thumbnail with title, category, and date.
- Added a theme-agnostic CSS reset so host-theme navigation styles no longer hide or mis-size panel content (Divi compatibility).
- Hardened Post Query rendering with normalized settings and a graceful empty state for editors.
- Performance and coding-standards improvements (cached attachment lookups, PHPCS fixes).

## 1.0.0 — Unreleased

- Initial development started.
