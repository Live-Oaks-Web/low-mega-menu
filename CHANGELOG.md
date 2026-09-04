# Changelog

## 1.7.2

- Search results: when a post has no authored excerpt, show the first 8 words of content instead of an empty excerpt.

## 1.7.1

- Plugins screen: added Settings and Check for update action links (manual GitHub update check with an admin notice).

## 1.7.0

- Added Settings → Styling with a color palette (text, headings, links, buttons, panel background, muted, border, accent) prefilled from the current defaults.
- Added a custom CSS textarea with useful mega menu class names listed.
- Palette and CSS apply only to mega panels, search, and drawer chrome — not theme top-level nav items.
- Added `npm run package` / `bin/package-release.ps1` for a lean installable ZIP without node_modules.

## 1.6.4

- Search: magnifying-glass icon is decorative (not a clickable submit); the clear (X) control replaces it in the same slot when the field has text. Enter still submits the form.

## 1.6.3

- Divi: restore logo link clicks by setting `pointer-events: none` on the full-width nav shell and re-enabling pointer events only on interactive controls (links, search, toggle, panels).

## 1.6.2

- Divi: keep the plugin hamburger visible at the mobile breakpoint (`display: inline-flex !important` on `.low-mm-menu-toggle--divi-slot`), and force the replaced `#et-top-navigation` / header nav to stay visible when Divi’s mobile CSS would hide it.

## 1.6.1

- Hardened the mobile breakpoint setting so CSS media queries always match the saved value (rewritten on enqueue, cache-busted by breakpoint).
- Prevent search from stacking above the hamburger on mobile: hide in-header search until it is relocated into the drawer; early `html.low-mm-is-mobile` / `low-mm-is-desktop` classes keep first paint in sync with JS.

## 1.6.0

- Added a configurable mobile breakpoint setting (default 1024px) under Mega Menu → Settings → Layout. CSS media queries are rewritten on enqueue when customized; JS viewport checks use the same value.
- Added GitHub Releases-based plugin updates (`Live-Oaks-Web/low-mega-menu`). Prefers an attached `low-mega-menu.zip` release asset; falls back to the tag source archive.

## 1.5.0

- Redesigned the search bar: soft shadowed white field with rounded corners, placeholder on the left, and a clickable magnifying-glass submit control using `includes/img/search.svg`.
- Added a custom title override to the Page / Post Excerpt module (leave blank to use the page or post title).

## 1.4.0

- Added a custom excerpt override to the Page / Post Excerpt module: author-provided teaser text replaces the post's excerpt and takes precedence over the length and full-content options.
- Fixed a double-escaping issue on the auto-generated excerpt so characters like ampersands render correctly.
- Search field, clear, and Back controls now inherit the active theme's font (`font-family: inherit`) instead of the browser default.

## 1.3.0

- Added a lightweight WYSIWYG editor (bold, italic, links, lists, plus a code view) to the Call to Action body and the Code / Shortcode content fields. The Code field defaults to the code view so shortcodes and raw HTML stay intact.
- Added Call to Action color controls: text color (heading and body), button text color, and button background color (sanitized to valid hex on output).
- Minor CSS refinements: centered search status message, fixed a 1px seam under Divi's fixed header (`.et-fixed-header`), and added spacing below the CTA body.

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
