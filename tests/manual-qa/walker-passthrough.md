# Mega Menu Walker — No-Attachment Passthrough

Manual verification for Prompt 02, Milestone 2.4.

## Steps

1. Create a nav menu with at least two items.
2. Attach a mega menu to **only one** item via Appearance → Menus.
3. In a theme template or temporary snippet, render the menu twice:

```php
// Default walker
wp_nav_menu( array( 'menu' => 'YOUR_MENU_ID', 'echo' => false ) );

// Mega menu walker
wp_nav_menu( array(
    'menu'   => 'YOUR_MENU_ID',
    'echo'   => false,
    'walker' => new \LOW_MM\Nav\MegaMenuWalker(),
) );
```

4. For each menu item **without** an attachment, compare the `<li>...</li>` chunk from both outputs. They must be identical.
5. For the attached item only, confirm the walker output adds `low-mm-has-panel` and `data-low-mega-menu="{id}"` on the `<li>` opening tag.

## Expected result

- Unattached items: byte-identical markup between default and `MegaMenuWalker`.
- Attached item: same base markup plus panel marker attributes on `<li>`.
