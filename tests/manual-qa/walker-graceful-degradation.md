# Walker Graceful Degradation

## Universal integration (all themes)

LOW Mega Menu enhances menu items through WordPress’s `walker_nav_menu_start_el` filter. Any theme that renders menus with `wp_nav_menu()` — including themes with custom walkers (Divi, Astra, GeneratePress, etc.) — receives mega menu panels without replacing the theme walker.

The plugin also:

- Adds marker classes (`low-mm-nav-container`, `low-mm-nav`) when the resolved menu has mega menu attachments
- Wraps output with a container when panel markup is present but the theme omitted one
- Defers mobile drawer UI when the front end detects an existing theme hamburger (JavaScript)

## Environment sniffing (not theme names)

`NavEnvironment` detects capabilities:

| Check | Used for |
|-------|----------|
| `wp_is_block_theme()` | Navigation block swap, restore Appearance → Menus |
| `get_registered_nav_menus()` | Skip registering a duplicate `primary` location |
| Menu term ID + attachments | Whether to enhance a specific `wp_nav_menu()` call |

No hard-coded theme names are required for front-end rendering.

## Manual walker (advanced)

`MegaMenuWalker` remains available for themes that bypass `Walker_Nav_Menu` entirely:

```php
add_filter( 'low_mm_nav_menu_args', function ( $args ) {
	$args['walker'] = new \LOW_MM\Nav\MegaMenuWalker();
	return $args;
} );
```

Prefer the default filter-based integration when possible.

## Filters

| Filter | Purpose |
|--------|---------|
| `low_mm_enhance_nav_menu_item` | Skip enhancing a specific item |
| `low_mm_enhance_unresolved_nav_menu` | Enhance when menu ID cannot be resolved (page builders) |
| `low_mm_nav_menu_args` | Adjust args after marker classes are applied |
| `low_mm_wrap_mobile_nav_shell` | Disable plugin mobile drawer wrapper |
| `low_mm_header_nav_location_slugs` | Preferred theme location slugs |
| `low_mm_likely_uses_module_menu_picker` | Admin guidance for builder themes |

## Verification steps

1. Attach a mega menu to a nav item and assign the menu in the theme (location or module picker).
2. View source: attached items should include `data-low-mega-menu` and `.low-mm-panel`.
3. Desktop: click trigger → panel opens below header.
4. Mobile: theme hamburger or plugin drawer; drill-down on mega items.
5. Switch themes (block, classic, page builder) without changing plugin code.
