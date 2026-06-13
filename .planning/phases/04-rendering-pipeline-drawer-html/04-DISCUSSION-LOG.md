# Phase 4: Rendering Pipeline & Drawer HTML - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-13
**Phase:** 4-rendering-pipeline-drawer-html
**Areas discussed:** Menu item rendering, Drawer header & brand, Panel & back-row structure, JS scope & PHP→JS bridge, Editor preview behavior, ARIA & semantics depth, Full data-attribute contract, WP item icon sourcing & renderer architecture

---

## Menu Item Rendering

### Parent items (has_children) — render & behavior

| Option | Description | Selected |
|--------|-------------|----------|
| Split link + chevron | Label is `<a>` linking to parent URL; separate `›` chevron `<button>` drills in. Preserves nav + drill-down. | ✓ |
| Drill-in only (button) | Single `<button aria-expanded>`; tapping always opens child panel, parent URL ignored. | |
| Drill-in, title links back | Parent is a drill-in button; parent URL becomes a link in the child panel's back-row title. | |

**User's choice:** Split link + chevron
**Notes:** Chosen because WordPress parent items frequently carry their own URL; splitting preserves both navigation and drill-down.

### Chevron indicator rendering

| Option | Description | Selected |
|--------|-------------|----------|
| CSS `::after` | Pseudo-element on the parent row. No extra DOM, themeable in Phase 6. | ✓ |
| Inline SVG | `<svg>` chevron in markup. Crisp, but adds DOM per parent. | |
| Text character `›` | Literal `›` in markup. Least styleable, font-dependent. | |

**User's choice:** CSS `::after`

### Item icon rendering (node `icon` field)

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, both sources | Icons render for WP-menu and custom-builder items from the unified node field. | ✓ |
| Custom items only | Only custom-builder items show their picked icon. | |
| No icons in Phase 4 | Skip icon rendering now; defer to a later phase. | |

**User's choice:** Yes, both sources

### Leaf item rendering

| Option | Description | Selected |
|--------|-------------|----------|
| `<a>` + target + classes | Plain `<a href>` passing through `target=_blank` and WP menu CSS classes. | ✓ |
| Plain `<a>` only | Minimal `<a href>` without class/target passthrough. | |

**User's choice:** `<a>` + target + classes

---

## Drawer Header & Brand

### Default brand source

| Option | Description | Selected |
|--------|-------------|----------|
| Site Logo | Auto-detect WP custom logo; fallback to site name text. Zero config. | ✓ |
| None | Header shows only the close button until configured. | |
| Site name text | Uses `get_bloginfo('name')` as text brand by default. | |

**User's choice:** Site Logo

### Close ✕ button placement

| Option | Description | Selected |
|--------|-------------|----------|
| Header, right side | Standard mobile-drawer position; brand left, ✕ right. | ✓ |
| Header, left side | Brand right, ✕ left. | |
| Floating top-right | ✕ floats at drawer top-right corner, outside header. | |

**User's choice:** Header, right side

### Header visibility when brand = None

| Option | Description | Selected |
|--------|-------------|----------|
| Yes, always present | Header always renders with close button, even with no brand. | ✓ |
| Hide when no brand | Header omitted entirely; close relies on overlay/fallback. | |

**User's choice:** Yes, always present

### Logo sizing

| Option | Description | Selected |
|--------|-------------|----------|
| CSS max-height default | `<img>` constrained to ~40px max-height, themeable in Phase 6. No inline sizes. | ✓ |
| Native image size | `<img>` at uploaded dimensions; no constraint until Phase 6. | |
| Inline size attrs | Inline width/height or style for immediate control. | |

**User's choice:** CSS max-height default

---

## Panel & Back-Row Structure

### Panel ID generation (incl. root)

| Option | Description | Selected |
|--------|-------------|----------|
| Always uniqid | Every panel incl. root gets `ddmm-panel-{uniqid()}`. DRAW-11 compliant, multi-instance safe. | ✓ |
| Root = `root-{widget_id}` | Predictable per-instance root ID; children uniqid. | |
| Root literal `root` | Matches architecture diagram; duplicate-ID risk across instances. | |

**User's choice:** Always uniqid

### Back-navigation contract

| Option | Description | Selected |
|--------|-------------|----------|
| `data-back-target` | Back button carries ancestor panel ID; ID-based reverse nav mirroring `data-target`. | ✓ |
| JS history stack | No attribute; Phase 5 JS tracks a visited-panel stack. | |
| DOM position | Preceding panel by position. Known anti-pattern. | |

**User's choice:** `data-back-target`

### Back-row layout

| Option | Description | Selected |
|--------|-------------|----------|
| Back + title, left | ← Back left, parent name title same row, left-aligned. | ✓ |
| Back left, title centered | ← Back left, title centered/prominent. | |
| Back button only | Row shows only ← Back, no title. | |

**User's choice:** Back + title, left

### Parent-name toggle default (DRAW-08)

| Option | Description | Selected |
|--------|-------------|----------|
| Shown by default | Parent name appears as back-row title out of the box. | ✓ |
| Hidden by default | Row shows only ← Back until toggled on. | |

**User's choice:** Shown by default

---

## JS Scope & PHP→JS Bridge

### Phase 4 JS scope

| Option | Description | Selected |
|--------|-------------|----------|
| Bootstrap only | IIFE + class skeleton, dual-path init, `data-ddmm-init` guard, container hookup. No interactions. | ✓ |
| Pure HTML, defer all JS | All JS (incl. bootstrap) moves to Phase 5. | |
| Bootstrap + open/close | Bootstrap + basic trigger→open+overlay. Leaves slide/back/animation for Phase 5. | |

**User's choice:** Bootstrap only

### Config bridge (resolves JSCR-05 vs architecture)

| Option | Description | Selected |
|--------|-------------|----------|
| Data attributes | Per-instance config as data-* on each container + `--ddmm-*` CSS vars. Instance-safe. | ✓ |
| `wp_localize_script` | One global object (literal JSCR-05). Not instance-safe for multiple widgets. | |
| Hybrid | data-* per instance; localize only for truly-global values. | |

**User's choice:** Data attributes
**Notes:** Deliberately supersedes the literal JSCR-05 wording because a single localized global cannot hold per-instance settings safely.

### Container / scope root

| Option | Description | Selected |
|--------|-------------|----------|
| `.ddmm-widget` wrapper | Wraps trigger + overlay + drawer; hosts init guard; queries scoped to it. | ✓ |
| Drawer element | Guard/scope on the drawer; trigger sits outside. | |
| Existing trigger wrapper | Reuse `.ddmm-trigger-wrapper` as scope root. | |

**User's choice:** `.ddmm-widget` wrapper

### Drawer CSS scope

| Option | Description | Selected |
|--------|-------------|----------|
| HTML + base layout CSS | Structural CSS: off-canvas translateX, panel stacking, aria-hidden defaults. Phase 6 adds customization. | ✓ |
| HTML only | Markup only; defer all drawer CSS to Phase 6. | |

**User's choice:** HTML + base layout CSS

---

## Editor Preview Behavior

### Editor output mode

| Option | Description | Selected |
|--------|-------------|----------|
| Trigger + preview block | Trigger + editor-only block showing root panel items inline (static). Editor-only. | ✓ |
| Trigger + hint only | Trigger + hint message; drawer HTML not shown in editor. | |
| Normal render | Full drawer HTML off-canvas; user sees only the trigger. | |

**User's choice:** Trigger + preview block

### Preview content depth

| Option | Description | Selected |
|--------|-------------|----------|
| Full root panel static | Render actual root `<ul>` inline (items + icons + chevrons); sub-panels omitted. | ✓ |
| Summary + sample labels | "Mobile menu: N items" + first few labels. | |
| Placeholder only | Labeled placeholder; relies on frontend for real output. | |

**User's choice:** Full root panel static

### Empty/unconfigured in editor

| Option | Description | Selected |
|--------|-------------|----------|
| Keep source-aware hint | Reuse Phase 2/3 hint ("Select a menu" / "Add menu items"). | ✓ |
| Generic placeholder | Single generic "Configure your mobile menu" placeholder. | |

**User's choice:** Keep source-aware hint

### Frontend DOM presence

| Option | Description | Selected |
|--------|-------------|----------|
| Full HTML, off-canvas | Drawer + panels + overlay always in DOM, translateX(-100%), aria-hidden=true; ready for Phase 5. | ✓ |
| Trigger only for now | Trigger only on frontend; drawer HTML deferred to Phase 5. | |

**User's choice:** Full HTML, off-canvas

---

## ARIA & Semantics Depth

### `<nav>` aria-label value (A11Y-01)

| Option | Description | Selected |
|--------|-------------|----------|
| Configurable, default "Mobile Menu" | Content Tab text control; default translatable "Mobile Menu". | ✓ |
| Fixed "Mobile Menu" | Hardcoded; no control. | |
| WP menu name | Use selected WP menu's name (WP) / generic (custom). | |

**User's choice:** Configurable, default "Mobile Menu"

### Child panel labels

| Option | Description | Selected |
|--------|-------------|----------|
| `aria-labelledby` → title | Panel points to its back-row title span; SR announces parent name. | ✓ |
| `aria-label` per panel | Generated string (e.g. "[Parent] submenu"). | |
| No panel label | Only `<nav>` labelled; panels rely on context. | |

**User's choice:** `aria-labelledby` → title

### Chevron button label (A11Y-02)

| Option | Description | Selected |
|--------|-------------|----------|
| "Show [item] submenu" | Descriptive aria-label + aria-expanded=false (Phase 5 toggles to "Hide"). | ✓ |
| "Expand" | Generic aria-label + aria-expanded=false. | |
| No label | `›` aria-hidden, no accessible name. | |

**User's choice:** "Show [item] submenu"

### Inactive panel state

| Option | Description | Selected |
|--------|-------------|----------|
| aria-hidden on sub-panels | Root active; all sub-panels ship aria-hidden=true; drawer aria-hidden until opened. | ✓ |
| Drawer-level only | Drawer aria-hidden covers all; per-panel state left to Phase 5. | |

**User's choice:** aria-hidden on sub-panels

---

## Full Data-Attribute Contract

### Attribute namespace

| Option | Description | Selected |
|--------|-------------|----------|
| `data-ddmm-*` hooks + short nav | Hooks namespaced; nav attrs short (data-target, data-panel-id, data-back-target). Matches architecture. | ✓ |
| All `data-ddmm-*` | Every attribute prefixed. Uniform, verbose. | |
| All short | data-trigger, data-panel, etc. Collision-prone. | |

**User's choice:** `data-ddmm-*` hooks + short nav

### State representation

| Option | Description | Selected |
|--------|-------------|----------|
| State classes | BEM: `ddmm-panel--active`, `ddmm-is-open`. JS toggles classes; CSS drives transform/opacity. | ✓ |
| Data-attr state | `data-ddmm-active`, `data-ddmm-open`; CSS via attribute selectors. | |
| Hybrid | Classes for panel, data-attr for drawer. | |

**User's choice:** State classes

### Container identification

| Option | Description | Selected |
|--------|-------------|----------|
| Unique ID + class | `id="ddmm-widget-{widget_id}"` + class `ddmm-widget`. Mirrors drawer pattern. | ✓ |
| Class only | `.ddmm-widget`; queries scoped, no unique ID. | |
| `data-ddmm-id` | Instance ID in a data attribute; no native id. | |

**User's choice:** Unique ID + class

### Panel menu structure

| Option | Description | Selected |
|--------|-------------|----------|
| `<ul>/<li>` + sibling panel | Semantic list; parent `<li>` holds split `<a>`+button; child panel sibling after `</li>`. | ✓ |
| Divs only | `<div>` items, no list semantics. | |

**User's choice:** `<ul>/<li>` + sibling panel

---

## WP Item Icon Sourcing & Renderer Architecture

### WP-menu item icons

| Option | Description | Selected |
|--------|-------------|----------|
| Render icon field if non-empty | Output node icon when present; custom items show icons, WP items text-only. No fragile WP parsing. | ✓ |
| Parse WP CSS classes | Scan WP item classes for icon-font classes. Unreliable. | |
| Elementor Pro menu icons | Read Pro's term-meta menu icons. Pro-only coupling. | |

**User's choice:** Render icon field if non-empty

### Icon render mechanism

| Option | Description | Selected |
|--------|-------------|----------|
| `Icons_Manager` + aria-hidden span | `\Elementor\Icons_Manager::render_icon()` in `<span aria-hidden="true">` before label. Matches Phase 1. | ✓ |
| Manual `<i>`/`<img>` | Manual markup; duplicates Icons_Manager logic. | |
| CSS background | Icon as `::before` background. Hardest per-item from an icon array. | |

**User's choice:** `Icons_Manager` + aria-hidden span

### Renderer code location

| Option | Description | Selected |
|--------|-------------|----------|
| Separate `DrawerRenderer` class | `src/Rendering/DrawerRenderer.php`; stateless render(tree, settings, widget_id). Matches architecture. | ✓ |
| Inline on widget | Private render methods on `DrillDownMenu`. Fewer files, larger widget. | |
| Static utility methods | Static helpers; harder to extend. | |

**User's choice:** Separate `DrawerRenderer` class

---

## Claude's Discretion

- Exact BEM class names beyond the core contract
- Exact `--ddmm-*` CSS custom property names for Phase 6 hooks
- `DrawerRenderer` method signatures and helper decomposition
- Base CSS pixel values (drawer width, overlay rgba, transition timing)
- Editor-only preview block styling
- phpcs ignore-comment phrasing for `Icons_Manager` output
- Whether to emit a visually-hidden heading inside `<nav>`
- Exact label/description text for new Content Tab controls

## Deferred Ideas

- Search box rendering/filtering — Phase 5
- Animation system (ANIM-01..04) — Phase 5
- Drawer open/close + panel slide + back nav behavior — Phase 5
- Close-on-link-click / close-on-overlay / auto-open (EXTR-03..05) — Phase 5
- Full Style Tab (STYL-01..06) — Phase 6
- Keyboard nav + focus management (A11Y-04..08) — Phase 7
- WooCommerce URL verification (COMP-03) — Phase 7
- `.pot`/translation packaging (COMP-04) — Phase 7
- `content_template()` live editor preview (PRES-01) — v2
- Elementor Pro menu-item icon meta — v2
- RTL support (RTL-01) — v2
