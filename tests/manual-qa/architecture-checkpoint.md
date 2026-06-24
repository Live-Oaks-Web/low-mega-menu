# Architecture Checkpoint (Phase 10)

## 10.2 — Nested mega menu schema

**Question:** Can a future module store `{ "type": "nested_panel", "settings": { "target_mega_menu_id": 456 } }` without changing the layout envelope?

**Answer: Yes.**

- Outer envelope keys (`version`, `panel_settings`, `layout_preset`, `columns`, `mobile_order`) are unchanged.
- Each module entry is `{ id, type, settings }` where `settings` is validated only by that module's `validate_settings()` — see `LayoutValidator::validate_module()`.
- `LayoutSchema::recognized_module_types()` reads from `ModuleRegistry` — a `nested_panel` type is simply never registered in v1.
- Front end would call `PanelRenderer::render( $target_id )` from the nested module's `render()` method.

No envelope migration required.

---

## 10.3 — Licensing non-entanglement

**Search scope:** `includes/`, `admin-app/src/`, `public/src/`

**Terms:** `freemius`, `edd_`, `easy digital`, `license_key`, `license-server`

**Result:** No licensing vendor SDK, license-key validation, or hardcoded license server URLs found in plugin source (Prompt 05 grep).

GPL-2.0-or-later headers only. Distribution/licensing is explicitly out of scope for v1 build.

---

## 10.4 — Flexible column width

| Layer | Reads `width_fraction` generically? | Preset coupling? |
|-------|-------------------------------------|------------------|
| `PanelRenderer::build_grid_template()` | Yes — `grid-template-columns: Nfr ...` from each column | No |
| `Column.js` builder preview | Yes — `flex: column.width_fraction` | No |
| `presets.js` | Applies preset fractions when user **selects** a preset | Presets are builder shortcuts only; saved JSON carries fractions |
| `LayoutValidator` | Accepts any float `width_fraction > 0` | `layout_preset` string validated separately — does not constrain fractions |

**Gap (minor, builder-only):** Applying a named preset overwrites column `width_fraction` values from `PRESET_DEFINITIONS`. A future "custom" preset could save arbitrary fractions without code changes to the front end — only builder UI would need a custom width control.

**Front end / CSS:** No hardcoded "exactly 4 columns" logic. Safe for future flexible widths.
