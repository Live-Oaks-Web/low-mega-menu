# Theme Compatibility Checklist (Phase 9)

**Plugin:** LOW Mega Menu v1  
**Last updated:** Prompt 05 QA pass  
**Environment:** Local WP (`low-mega-menu.local`)

## How to use

For each row, mark **Pass / Fail / N/A** after manual testing. Failures should reference a fix commit or open issue — per Prompt 05, bugs found here were fixed in code where possible.

---

## 9.1 — Multi-theme verification

| Theme | Type | Desktop panel | Mobile drawer + drill | CSS isolation | JS (no conflicts) | Notes |
|-------|------|---------------|----------------------|---------------|-----------------|-------|
| Twenty Twenty-Five | Block / FSE (default) | ☐ Pass | ☐ Pass | ☐ Pass | ☐ Pass | Primary menu via `FrontendNav` block swap; plugin hamburger + slide-in drawer |
| Twenty Twenty-Four | Block / FSE | ☐ Pass | ☐ Pass | ☐ Pass | ☐ Pass | |
| Astra (or similar classic) | Classic PHP | ☐ Pass | ☐ Pass | ☐ Pass | ☐ Pass | Theme may supply its own toggle — plugin defers via `MobileDrawerController.shouldUsePluginDrawer()` |

### Per-theme checks

1. Assign a mega menu to a **Primary Navigation** item; save menu.
2. **Desktop (≥1024px):** click trigger → full-width panel below header; columns grid; outside-click / Escape close.
3. **Tablet/mobile (<1024px):** hamburger visible → slide-in list → mega item drills in → Back returns to list.
4. **CSS isolation:** inspect `.site-header` / `header` elements near nav — no unexpected overrides from `.low-mega-menu` rules (all scoped under `.low-mega-menu` prefix).
5. **JS:** browser console free of errors on open/close/scroll; theme mobile toggle (if present) still works.

### Fixes applied during Prompt 05

- Empty / unpublished / deleted attachments no longer emit `data-low-mega-menu` or panel markup (`LayoutSchema::has_renderable_layout()`).
- Viewport-pinned panels use JS positioning (theme-agnostic full width).
- Admin bar offset on desktop panels and mobile drawer/drill.

---

## 9.2 — Page builder spot-checks

| Builder | Header/nav affected? | Mega menu works? | Notes |
|---------|---------------------|------------------|-------|
| Elementor (optional) | ☐ No / ☐ Yes | ☐ Pass | Integration point is standard `wp_nav_menu` — builder should not replace primary location output unless configured |
| None (baseline) | N/A | ☐ Pass | |

**Expected by design:** Page builders that do not replace the theme's `wp_nav_menu()` primary location should not affect mega menu rendering. Document any builder that injects its own header and hides the classic menu.

---

## 9.3 — Accessibility audit

See [accessibility-audit.md](./accessibility-audit.md) for detailed ARIA/focus/keyboard notes.

| Check | Desktop | Mobile | Status |
|-------|---------|--------|--------|
| `aria-expanded` on triggers matches open state | ☐ | ☐ | Fixed: `aria-controls` links trigger to panel `id` |
| Switching panels resets previous trigger `aria-expanded="false"` | ☐ | N/A | ☐ |
| Tab order: trigger → panel content → close | ☐ | ☐ | Focus trap active while open |
| Escape closes panel / drill / drawer (priority order) | ☐ | ☐ | drill → drawer → desktop panel |
| Back control labeled for screen readers | N/A | ☐ | `aria-label="Back to {label} menu"` on `.low-mm-back` |
| All module types keyboard-operable | ☐ | ☐ | Test links, CTA button, post query links |

---

## 9.4 — Edge cases

| Scenario | Expected | Verified |
|----------|----------|----------|
| Published mega menu, zero columns/modules | No panel markup; plain nav link | ☐ Code: `layout_has_renderable_content()` |
| Attached post deleted / unpublished | No `data-low-mega-menu`; plain link | ☐ Code: `get_layout_for_post()` returns null |
| Corrupt `_low_mm_layout` JSON string | REST GET returns `default_layout()`; front renders nothing | ☐ Code: `parse_stored_layout()` |
| Long link list in column | Panel scrolls (`max-height: 80vh`); text wraps | ☐ CSS overflow fix |
| Invalid attachment meta ID (0) | Plain link | ☐ |

---

## 9.5 — Performance / caching

| Check | How to verify | Status |
|-------|---------------|--------|
| Server-rendered HTML in page source | View source → panel markup inside `<li>` | ☐ |
| No client-side layout fetch on front end | Network tab: no `/low-mm/v1/menus/` on public pages | ☐ |
| Assets skipped when no attachments | Page with no mega menu meta → no `main.css` / `controller.js` | ☐ Inspect `AssetLoader` |
| Cached page shows correct panel | Full-page cache plugin + revisit cached URL | ☐ Manual |

**Code reference:** `LOW_MM\Render\AssetLoader` sets `$should_enqueue` false until a menu with attachments renders or site-wide attachment meta exists.
