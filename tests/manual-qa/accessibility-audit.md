# Accessibility Audit (Phase 9.3)

## ARIA

| Element | Attribute | Behavior |
|---------|-----------|----------|
| Nav trigger (has panel) | `aria-expanded` | `true` when desktop panel or mobile drill is open |
| Nav trigger | `aria-controls` | Points to panel element `id` (`low-mm-panel-{post_id}`) |
| Panel container | `role="region"`, `aria-label` | Parent nav item label |
| Mobile drill panel | `aria-modal="true"` | Set during drill-in; removed on drill-out |
| Drawer toggle | `aria-expanded`, `aria-controls` | Tied to `.low-mm-mobile-drawer` id |
| Back button | `aria-label` | `Back to {parent label} menu` (translatable) |

### Multi-panel sequence (desktop)

1. Open panel A → A trigger `aria-expanded="true"`.
2. Open panel B → A trigger `aria-expanded="false"`, B trigger `true`.
3. Close via Escape → B trigger `aria-expanded="false"`, focus returns to B trigger.

Implemented in `PanelController.close()` (focus return) and `handlePanelRequestOpen()` (closes prior panel).

## Focus management

- **Desktop:** focus moves to first focusable in panel on open; Tab trapped inside panel; Escape closes and returns focus to trigger.
- **Mobile drill:** same trap inside full-screen panel; Back returns focus to list trigger.
- **Drawer:** body scroll locked via `low-mm-mobile-nav-open`; backdrop click closes drawer.

## Keyboard

| Action | Key / input |
|--------|-------------|
| Open desktop panel | Enter/Space on trigger (click handler) |
| Close desktop panel | Escape, outside click, re-click trigger |
| Open mobile drawer | Enter/Space on hamburger |
| Drill into mega item | Enter/Space on nav link |
| Back to list | Activate `.low-mm-back` or Escape (drill first) |
| Module links/buttons | Standard Tab navigation within open panel |

## Module types — interactive elements to test

| Module | Elements |
|--------|----------|
| Link List | `<a>` per row |
| Excerpt | Optional link to source post |
| Post Query | Post links, optional "View all" link |
| Image | Optional linked image |
| CTA | Button/link |
| Code | Inert text when shortcodes disabled; links if HTML contains them |

## Screen reader notes

- Open/close state should be announced via `aria-expanded` changes on the trigger.
- Mobile Back is not icon-only — visible "‹ Back" text plus `aria-label`.
- Recommend spot-check with NVDA/VoiceOver on one theme before release.
