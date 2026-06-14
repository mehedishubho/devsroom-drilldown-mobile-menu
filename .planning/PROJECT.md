# Devsroom DrillDown Mobile Menu

## What This Is

A production-ready WordPress plugin that provides a custom Elementor widget for mobile navigation using a drill-down (push) menu system — identical in behavior to the mobile menu on Packiro.com. The menu works as a slide-in off-canvas drawer from the left. When a user taps a parent menu item, the current panel slides out and the submenu panel slides in from the right, creating a native-app-like navigation experience. Built for WordPress site builders and Elementor users who want a polished mobile menu without touching code.

## Core Value

The drill-down panel navigation must work flawlessly at any depth — parent items slide the current panel left and reveal the child panel from the right, with a ← Back button to reverse. Direct `data-target` → `data-panel-id` ID lookup for navigation reliability.

## Requirements

### Validated

- Plugin bootstrap with PSR-4 autoloader and singleton pattern (Phase 1)
- Elementor dependency check with admin notice (Phase 1)
- Widget registration on modern `elementor/widgets/register` hook (Phase 1)
- Custom "Devsroom" Elementor category with SVG hamburger icon (Phase 1)
- Conditional asset loading via `get_script_depends()`/`get_style_depends()` (Phase 1)
- Four trigger types: Hamburger Lines (CSS 3-span), Custom Icon, Text Only, Icon + Text (Phase 1)
- Trigger renders as `<button>` with `aria-expanded` and `aria-controls` (Phase 1)
- WordPress Menu dropdown listing all registered nav menus by term_id (Phase 2)
- Menu Source toggle (WordPress Menu / Custom Builder) in Content Tab (Phase 2)
- 3-pass ID-based tree builder converting flat wp_get_nav_menu_items() to nested tree (Phase 2)
- Editor-only hint when no menu selected; zero frontend HTML on empty state (Phase 2)
- WooCommerce menu items flow through tree builder unchanged (Phase 2)
- Custom Menu Builder repeater with Label, URL, Depth, Icon, Open in New Tab fields (Phase 3)
- Stack-based depth-field algorithm converting flat repeater data to nested tree (Phase 3)
- CustomTree outputs identical node contract as WpNavTree (8-field with icon) — one Phase 4 render path (Phase 3)
- Repeater indent dashes for visual hierarchy in Elementor editor (Phase 3)
- Source-aware empty-state hints (WP menu vs custom builder) (Phase 3)
- Recursive `DrawerRenderer` converts the unified 8-field tree to off-canvas drawer HTML: nested panels, header (brand + close), back rows, full ARIA markup, complete output escaping (Phase 4)
- ID-based navigation contract — `data-target` ↔ `data-panel-id` ↔ `data-back-target` with single-source-of-truth panel IDs threaded through recursion (Phase 4)
- Drawer Header controls (brand source: site logo / custom image / custom text / none) + Drawer Settings (nav label, show-back-title toggle) wired into the widget Content Tab (Phase 4)
- `render()` integration via `.ddmm-widget` wrapper with `is_edit_mode()` branch → static editor preview vs frontend drawer (Phase 4)
- Base drawer CSS: off-canvas `translateX`, overlay, stacked panels, CSS `::after ›` chevron glyph, `--ddmm-*` custom properties, zero GPU-hostile layout transitions (Phase 4)
- JS bootstrap skeleton: `DrillDownMenu` class with `init(container)`, dual-path init (Elementor `element_ready` + `DOMContentLoaded`), `data-ddmm-init` double-init guard — pure ES6, zero jQuery, no `wp_localize_script` (interaction deferred to Phase 5) (Phase 4)
- Six Elementor Style Tab sections — Trigger STYL-01, Drawer STYL-02, Header STYL-03, Panel/Back Row STYL-04, Menu Items STYL-05 (Normal/Hover/**Active**), Search STYL-06 — all wired through the `--ddmm-*` custom-property bridge on top of a D-01-polished CSS baseline (Phase 6)
- Menu Items Active state via marker classes (`ddmm-current-item` / `ddmm-current-ancestor`) emitted by Phase 5 JS — implemented as a third inner tab keying off the DOM markers (not a pseudo-state), mirroring WP `current-menu-item` / `current-menu-ancestor` (D-04) (Phase 6)
- Strict editor≡published parity (D-07/D-08): `render_editor_preview()` emits every BEM surface with real classes, `.ddmm-editor-preview` carries zero hardcoded widget-content colors, off-canvas transform neutralized in-editor (Pitfall 8); Phase 5 hardcoded active rule removed (Pitfall 2) (Phase 6)
- Full keyboard-navigation + focus-management layer (Phase 7): Esc navigates back then closes (via the Phase 5 single `close()` path), Tab trap bounded by the drawer, roving `tabindex` ArrowUp/ArrowDown between siblings, Enter/Space native activation (label navigates / chevron drills / back goes back), focus moves into the drawer on open and restores to the trigger on close — all per-container scoped (Anti-Pattern 3)
- Screen-reader feedback + CSS a11y (Phase 7): per-container `aria-live` panel-context region (written via `textContent` only), themeable `:focus-visible` ring on the 6 keyboard-focusable BEM surfaces, `@media (prefers-reduced-motion: reduce)` neutralization (0.01ms so `transitionend` still fires), RTL baseline via CSS logical properties (`transform: translateX` deliberately untouched — full RTL is v2)
- WooCommerce-agnostic by construction (COMP-03): `WpNavTree`/`CustomTree` read `$item->url` directly; zero `class_exists`/`wc_get_*`/`aria-disabled` calls — WC items render correct URLs whether WooCommerce is active or inactive
- Translation-ready (COMP-04): `Domain Path: /languages` header, `load_plugin_textdomain` first in `Plugin::init()`, `wp_set_script_translations` + `window.ddmmI18n` bridge (via `wp_json_encode`), `'No results'` literal → bridge lookup with fallback, hand-authored `languages/devsroom-drilldown-mobile-menu.pot` (12 msgid entries; regenerate via WP-CLI `wp i18n make-pot` on release)

### Active

- [ ] Off-canvas drawer slides in from left with overlay when trigger is clicked
- [ ] Root panel displays all top-level menu items; items with children show a right-arrow (›) (Validated in Phase 4 — rendering; slide interaction is Phase 5)
- [ ] Tapping a parent item slides current panel left and brings in child panel from right
- [ ] ← Back button at top of each submenu slides back to previous panel
- [ ] Drill-down works for unlimited nesting levels (root → level 1 → level 2 → level 3…)
- [ ] Close (✕) button in drawer header dismisses the menu
- [ ] Drawer header shows site logo, custom image, custom text, or none (configurable) (Validated in Phase 4 — controls + HTML; close behavior is Phase 5)
- [ ] WordPress Menu source: dropdown of all registered `wp_nav_menus()` (Validated in Phase 2)
- [ ] Custom Menu Builder: repeater with Label, URL, Depth, Icon, Open in New Tab fields (Validated in Phase 3)
- [ ] Depth field drives nesting — flat list in Elementor, nested panels in rendered output (Validated in Phase 3)
- [ ] Custom Menu Builder shows indent dashes for visual hierarchy (— Child, —— Grandchild) (Validated in Phase 3)
- [ ] Trigger button types: Hamburger Lines, Custom Icon, Text Only, Icon + Text
- [ ] 4 animation types: Slide, Fade, Scale, Slide + Fade
- [ ] Configurable transition duration (100ms–2000ms) and easing
- [ ] Optional search box in drawer with configurable placeholder
- [ ] Close menu after link click (configurable toggle)
- [ ] Auto-open current page path (configurable toggle)
- [ ] Close on overlay click (configurable toggle)
- [ ] Plugin admin notice when Elementor is not active
- [ ] Assets only enqueued when widget is present on page (conditional loading)
- [ ] Pure ES6 JavaScript, no jQuery dependency
- [ ] OOP PHP with namespace `Devsroom_DDMM\` and PSR-4 autoloader

### Out of Scope

- Mega menu (desktop dropdown columns) — this is a mobile-only drill-down pattern
- Accordion menu (submenus expanding inline) — panels replace, not expand
- Desktop menu replacement — designed specifically for mobile navigation
- Third-party menu plugin dependency — self-contained Elementor widget
- Theme modification — purely additive plugin approach
- WordPress menu admin page — uses existing WP menus or built-in custom builder

## Context

- **Inspiration:** Packiro.com mobile menu behavior — slide-in left drawer with drill-down panels
- **Ecosystem:** WordPress 6.5+, Elementor Free or Pro, PHP 8.1+
- **Architecture decisions already made:**
  - Navigation uses direct `data-target` → `data-panel-id` ID lookup (not positional heuristics)
  - WP menu tree building uses 3-pass ID-based approach (no PHP references to avoid `foreach &$ref` corruption)
  - Custom menu tree uses stack-based depth-field algorithm
  - JS init uses dual-path: `elementor/frontend/init` event + `DOMContentLoaded` fallback
  - Double-init guard via `data-ddmm-init` attribute on container
  - Each child panel rendered immediately after its parent `</li>` in DOM order
  - Unique panel IDs generated with `uniqid()` at render time
- **Known issues from prior development (v1.3.0):** PHP reference bug, positional panel navigation, JS crash on `elementorFrontend.hooks`, hamburger click cascade failure, mixed PHP syntax, shell brace expansion in ZIP — all resolved

## Constraints

- **WordPress:** Requires 6.5+ — uses modern WP APIs
- **PHP:** Requires 8.1+ — uses modern PHP features
- **Elementor:** Requires Free or Pro — widget registration depends on Elementor being active
- **No jQuery:** All JavaScript must be pure ES6, zero jQuery dependency
- **PSR-4:** Class autoloading via `spl_autoload_register`, no Composer dependency for end users
- **Plugin identity:** Author MEHEDI HASSAN SHUBHO, text domain `devsroom-drilldown-mobile-menu`, version 0.0.01

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Direct ID-based panel navigation (`data-target` → `data-panel-id`) | Positional heuristics caused wrong-panel navigation at depth | ✓ Good — reliable at any nesting level |
| 3-pass ID-based WP menu tree builder | PHP `foreach &$ref` caused menu corruption | ✓ Good — eliminates reference bug |
| Dual JS init path (`elementor/frontend/init` + `DOMContentLoaded`) | `elementorFrontend.hooks` not ready on load | ✓ Good — handles both editor and published mode |
| Pure ES6, no jQuery | Modern WordPress moving away from jQuery, better performance | — Pending |
| Flat repeater with Depth field for custom menus | Simpler Elementor UX than nested repeaters | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-06-15 after Phase 7 completion — **v1.0 milestone complete: all 7 phases executed.** 14 live-behavior UAT cases (keyboard / screen-reader / reduced-motion / RTL / WooCommerce / i18n) pending manual execution in `07-HUMAN-UAT.md`; advisory code-review warnings in `07-REVIEW.md`. Run `/gsd-complete-milestone` for the full PROJECT.md milestone review (incl. reconciling remaining Active items validated in earlier phases).*
