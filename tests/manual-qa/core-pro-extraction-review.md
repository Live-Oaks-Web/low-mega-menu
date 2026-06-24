# Core/Pro Extraction Dry-Run (Phase 10.1)

**Exercise:** Could the CTA module move to a hypothetical `low-mega-menu-pro` plugin without Core changes?

## PHP (CTA module)

| Step | Core today | Pro add-on |
|------|------------|------------|
| Module class | `includes/modules/cta/class-cta-module.php` self-registers via `ModuleRegistry::register()` at file bottom | Same file in Pro plugin; loaded from Pro bootstrap |
| Bootstrap | `Plugin::load_modules()` glob-loads `includes/modules/*/class-*-module.php` | Pro plugin `require_once` its module file on `plugins_loaded` |
| Registry API | `ModuleRegistry::register( 'cta', CtaModule::class )` | **Same call** — no Core change |
| REST validation | `LayoutValidator` delegates module settings to `Module::validate_settings()` | Works if Pro registers module before save |
| Front render | `ModuleRegistry::render( 'cta', $settings )` | Works if Pro active on front end |

**Surprises / gaps:** None for CTA. Core never hardcodes CTA in PHP outside the module file and registry.

## React (builder)

| Piece | Core today | Pro add-on |
|-------|------------|------------|
| Fields UI | `admin-app/src/modules/CtaFields.js` | Pro admin bundle exports fields component |
| Registry | `admin-app/src/modules/moduleRegistry.js` static `registry` object | Core could expose `window.lowMmRegisterModuleType( type, def )` hook — **not required for v1**; Pro could ship a separate admin script enqueued after Core that mutates `window.lowMmBuilderData.moduleTypes` (already merged in `getModuleRegistry()`) |
| `ModuleSettingsPanel` | Looks up `getModuleDefinition( type )` | Pro registers type in localized `moduleTypes` + separate fields script via dynamic import filter (future) |

**Minimal Core change for cleanest Pro split (future, not v1):** add `apply_filters( 'low_mm_module_registry', $registry )` in `getModuleRegistry()` — optional enhancement.

## Verdict

CTA can move to Pro with **zero mandatory Core changes** if Pro:

1. Registers PHP module via `ModuleRegistry::register()` on load.
2. Enqueues its own admin fields and extends `lowMmBuilderData.moduleTypes` (pattern already used for PHP→JS label sync).

No changes to `ModuleRegistry` class itself required.
