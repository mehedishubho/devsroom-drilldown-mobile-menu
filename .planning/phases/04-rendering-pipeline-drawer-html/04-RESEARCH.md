# Phase 4: Rendering Pipeline & Drawer HTML - Research

**Researched:** 2026-06-13
**Domain:** PHP rendering pipeline for a WordPress Elementor widget (recursive tree → drawer HTML) + JS bootstrap skeleton + base layout CSS
**Confidence:** HIGH

## Summary

Phase 4 converts the unified `$tree` (produced by `WpNavTree::build()` / `CustomTree::build()` in Phases 2 & 3) into complete drawer HTML — nested panels, the `data-target` / `data-panel-id` / `data-back-target` contract for Phase 5's ID-based navigation, a header area with configurable brand + close button, back-button rows, and correct ARIA markup. It also ships the JS bootstrap skeleton (init paths + double-init guard, no interactions) and the base layout CSS so the drawer exists off-canvas in the DOM.

All 31 implementation decisions (D-01..D-31) in CONTEXT.md are locked. This research investigates **how** to implement them — the exact class structure, the panel-ID threading strategy, the Elementor/WP API calls, the escaping discipline for recursive output, the ARIA attribute wiring, the `element_ready` hook string, and the CSS layout that lets Phase 5 animate via `transform`/`opacity` only.

The codebase is well-prepared for this phase: the autoloader already maps `Devsroom_DDMM\Rendering\` → `src/Rendering/` (verified in `devsroom-drilldown-mobile-menu.php`); `render()` already builds `$tree` from either source, has the empty-state guard, and has a `// Phase 4 will render…` stub; the trigger already emits `aria-controls="ddmm-drawer-{widget_id}"` that the drawer must satisfy; and the `--ddmm-*` custom-property skeleton exists in `ddmm-frontend.css`. The only structural prerequisite — creating `src/Rendering/` — is trivial (directory + one file).

**Primary recommendation:** Build `DrawerRenderer` as a single stateless class with a public static `render($tree, $settings, $widget_id)` entry point and private recursive helpers that pass the parent's panel ID and the ancestor-panel ID down the call stack (no shared mutable state, no ID re-derivation). Generate every panel ID — root + every parent — with `uniqid('ddmm-panel-', false)` at the moment a panel is opened, store it in a local variable for the lifetime of that `render_panel()` call, and pass it both *down* (as the ancestor ID for the panel's children) and *into the sibling child panels* (as `data-back-target` on the back button). Editor preview uses `is_edit_mode()` branching already proven in `render()`.

<user_constraints>
## User Constraints (from CONTEXT.md)

> Copied verbatim. The planner MUST honor these. Do not re-litigate any locked decision.

### Locked Decisions

**Menu Item Rendering (D-01..D-04)**
- D-01: Parent items (`has_children = true`) render **split**: label is `<a href>` linking to the parent's own URL + a separate `›` chevron `<button aria-expanded="false">` that drills into the child panel.
- D-02: The `›` drill-down indicator is injected via a **CSS `::after` pseudo-element** on the parent row — no extra DOM nodes per item.
- D-03: Item icons render **before the label for both sources**, from the unified node `icon` field.
- D-04: Leaf items (no children) render as plain `<a href>` passing through `target="_blank"` and WP menu CSS `classes`.

**Drawer Header & Brand (D-05..D-08)**
- D-05: Brand source is a SELECT with four options — **Site Logo / Custom Image / Custom Text / None**. Default = **Site Logo** (auto-detect via WP custom logo API; fall back to site name text if none).
- D-06: Close ✕ button sits in **header-right** (DRAW-04), rendered via a CSS glyph (no inline image). Brand sits header-left.
- D-07: The header row **always renders** — even when brand = None — so the close button is always reachable.
- D-08: Brand logo renders as an `<img>` constrained by a **CSS `max-height`** (~40px default), themeable in Phase 6. No inline `width`/`height` style attributes.

**Panel & Back-Row Structure (D-09..D-13)**
- D-09: **Every panel — including root — gets a `ddmm-panel-{uniqid()}` ID**. Root panel ID is generated first and stored so level-1 back buttons can reference it.
- D-10: Back navigation uses **`data-back-target` = ancestor panel ID** on each back button.
- D-11: Back-row layout: **← Back button on the left + parent-name title on the same row**, left-aligned.
- D-12: "Show parent name in back row" toggle (DRAW-08) defaults to **ON**.
- D-13: Each child panel is rendered **immediately after its parent `</li>`** as a sibling in DOM order.

**JS Scope & PHP→JS Bridge (D-14..D-17)**
- D-14: Phase 4 ships **JS bootstrap only**: IIFE + `DrillDownMenu` class skeleton, dual-path init (`elementor/frontend/init` + `DOMContentLoaded`), `data-ddmm-init` guard, container hookup. **No interactions** (open/close/slide/back/animation = Phase 5).
- D-15: PHP→JS config passes via **data-* attributes on each widget container + `--ddmm-*` CSS custom properties** — instance-safe. **Supersedes literal JSCR-05 `wp_localize_script`** (reserved for any future truly-global values).
- D-16: The scope root is the **`.ddmm-widget` wrapper** (contains trigger + overlay + drawer). It hosts the `data-ddmm-init` guard; all JS queries scoped to it (Anti-Pattern 3: no globals).
- D-17: Phase 4 ships **HTML + base layout CSS**: drawer off-canvas via `translateX(-100%)`, overlay/panel stacking, `aria-hidden` defaults. Phase 6 adds Style-Tab customization.

**Editor Preview (D-18..D-20)**
- D-18: In Elementor **edit mode**, `render()` outputs the trigger **plus an editor-only preview block** rendering the root panel `<ul>` inline (static, not off-canvas). Sub-panels omitted. Editor-only — never on published frontend.
- D-19: Empty/unconfigured in the editor **keeps the existing source-aware hint** (Phase 2/3 pattern).
- D-20: On the **published frontend**, the full drawer + panels + overlay HTML is **always present in the DOM**, positioned off-canvas (`translateX(-100%)`) with `aria-hidden="true"` — ready for Phase 5.

**ARIA & Semantics (D-21..D-24)**
- D-21: The drawer `<nav>` `aria-label` is a **configurable Content Tab text control**, defaulting to a translatable **"Mobile Menu"** (A11Y-01). **Never** `role="menu"`.
- D-22: Each **child panel carries `aria-labelledby`** pointing to its back-row title span. Root panel has no back row; relies on the nav label.
- D-23: Parent chevron `<button>` gets **`aria-label="Show [item] submenu"` + `aria-expanded="false"`** (A11Y-02). Phase 5 toggles to "Hide …" / `true`.
- D-24: Initial static state: **drawer `aria-hidden="true"`**, trigger `aria-expanded="false"` (already from Phase 1). **All sub-panels ship `aria-hidden="true"`**; root panel is the active one.

**Data-Attribute Contract (D-25..D-28)**
- D-25: **Hook attributes are `data-ddmm-*`**: `data-ddmm-trigger`, `data-ddmm-overlay`, `data-ddmm-drawer`, `data-ddmm-init`. **Navigation attributes are short**: `data-target`, `data-panel-id`, `data-back-target`.
- D-26: Dynamic state via **BEM state classes**: active root panel has `ddmm-panel--active`; drawer gains `ddmm-is-open` when opened. JS toggles classes; CSS drives `transform`/`opacity`.
- D-27: Widget container has **unique `id="ddmm-widget-{widget_id}"` + class `ddmm-widget`** (mirrors existing `ddmm-drawer-{widget_id}` pattern).
- D-28: Panel menu structure is **semantic `<ul>/<li>`**. A parent `<li>` holds the split `<a>` + chevron `<button>`; the child panel `<div>` is a sibling inserted **right after the parent `</li>`**.

**Icons & Renderer Architecture (D-29..D-31)**
- D-29: WP-menu item icons render **only when node `icon` field is non-empty** (WP items are text-only by default; their `icon` is `[]`). **No fragile WP icon parsing** in Phase 4.
- D-30: Present icons render via **`\Elementor\Icons_Manager::render_icon()` inside a `<span aria-hidden="true">`** before the label — matching the Phase 1 trigger-icon pattern (same `phpcs:ignore … OutputNotEscaped` justification). Decorative icons skipped by screen readers.
- D-31: Rendering code lives in a **separate class `src/Rendering/DrawerRenderer.php`** (namespace `Devsroom_DDMM\Rendering`). Stateless `render($tree, $settings, $widget_id)`. Autoloader already maps the namespace.

### Claude's Discretion
- Exact BEM class names beyond the core contract (e.g. `.ddmm-menu__item`, `.ddmm-back`).
- Exact `--ddmm-*` CSS custom property names exposed as Phase 6 theming hooks.
- `DrawerRenderer` method signatures and internal helper decomposition.
- Base CSS pixel values (drawer width default ~85vw/320px, overlay rgba, transition timing).
- Editor-only preview block styling (`.ddmm-editor-preview`).
- phpcs ignore-comment phrasing for `Icons_Manager` output (follow the existing Phase 1 pattern).
- Whether to emit a visually-hidden heading inside `<nav>` for extra structure.
- Exact label/description text for the new Content Tab controls.

### Deferred Ideas (OUT OF SCOPE)
- Search box rendering/filtering (EXTR-01/02) — Phase 5
- Animation system (transition type/duration/easing + Slide/Fade/Scale/Slide+Fade) (ANIM-01..04) — Phase 5
- Drawer open/close + panel slide + back nav behavior (the JS interactions) — Phase 5
- Close-on-link-click, close-on-overlay-click, auto-open current path (EXTR-03..05) — Phase 5
- Full Style Tab customization (STYL-01..06) — Phase 6
- Keyboard nav + focus management + Tab trap (A11Y-04..08) — Phase 7
- WooCommerce URL correctness verification (COMP-03) — Phase 7
- `.pot` file / translation packaging (COMP-04) — Phase 7
- `content_template()` live editor preview (PRES-01) — v2
- Elementor Pro menu-item icon meta support — v2 (WP items stay text-only in Phase 4, D-29)
- RTL layout support (RTL-01) — v2
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| DRAW-01 | Drawer slides in from the left as off-canvas panel when trigger clicked | Base layout CSS ships the off-canvas `translateX(-100%)` drawer in the DOM (D-17, D-20). The *slide behavior* is Phase 5, but the HTML+CSS that makes sliding possible is Phase 4. |
| DRAW-02 | Semi-transparent overlay covers page content | `.ddmm-overlay` element emitted by `DrawerRenderer` with `data-ddmm-overlay` hook + fixed full-screen hidden CSS (D-25, Base layout CSS). |
| DRAW-03 | Drawer header displays configurable brand: Site Logo / Custom Image / Custom Text / None | New Content Tab "Drawer Header" section: brand_source SELECT + conditional brand_image MEDIA + brand_text TEXT + nav_label TEXT (D-05..D-08). Brand rendering in `render_header()`. |
| DRAW-04 | Close (✕) button in drawer header dismisses the menu | ✕ close `<button>` in header-right with `data-ddmm-close` hook, CSS glyph (D-06). *Behavior* = Phase 5. |
| DRAW-05 | Root panel shows all top-level items; parent items display right-arrow (›) | Root panel emits `<ul>` of top-level nodes; parents render split (D-01) with chevron via CSS `::after` (D-02). |
| DRAW-06 | Tapping parent slides current panel left and brings child panel from right | DOM contract: parent chevron `<button data-target="{child panel id}">` + child panel sibling `<div data-panel-id="{same id}">` (D-13, D-25). *Slide* = Phase 5. |
| DRAW-07 | Each submenu panel has ← Back button at top | Back row emitted at the top of each child panel: `render_back_row()` with `data-back-target` = ancestor panel ID (D-10, D-11). |
| DRAW-08 | Back button row shows parent item name (configurable) | Back-row title `<span id="{unique}">` shows parent name when `show_back_title` SWITCHER is ON (default ON, D-12). Child panel `aria-labelledby` → this span (D-22). |
| DRAW-09 | Drill-down works for unlimited nesting levels | Recursive `render_item()` calls itself for `has_children` nodes; sibling child panels recurse via `render_panel()` — depth-agnostic by construction. |
| DRAW-10 | Navigation uses direct `data-target` → `data-panel-id` ID lookup — no positional heuristics | Contract enforced: every parent chevron carries `data-target`; every panel carries `data-panel-id`; every back button carries `data-back-target` (Anti-Pattern 2 avoided by design). |
| DRAW-11 | Unique panel IDs generated with `uniqid()` at render time | `uniqid('ddmm-panel-', false)` for every panel incl. root (D-09). |
| A11Y-01 | Drawer uses `<nav aria-label>` (never `role="menu"`) | `<nav aria-label="{setting}">` wrapping panels (D-21). No `role="menu"` anywhere. |
| A11Y-02 | Parent items use `<button>`/`<span role="button" tabindex="0">` with `aria-expanded` | Parent chevron `<button aria-expanded="false" aria-controls="{child panel id}" aria-label="Show {title} submenu">` (D-23). |
| A11Y-03 | Trigger has `aria-expanded` and `aria-controls` pointing to drawer ID | Already satisfied by Phase 1 trigger (`aria-controls="ddmm-drawer-{widget_id}"`); Phase 4 must emit `id="ddmm-drawer-{widget_id}"` on the drawer so the contract holds. |
| JSCR-01 | Pure ES6 JavaScript, zero jQuery dependency | JS bootstrap is pure ES6 IIFE (D-14); no jQuery. |
| JSCR-02 | IIFE-wrapped — no global namespace pollution | Bootstrap wrapped in `(function() { 'use strict'; … })();` extending the existing shell. |
| JSCR-03 | Dual-path init: `elementor/frontend/init` + `DOMContentLoaded` | Bootstrap registers both paths: `elementorFrontend.hooks.addAction('frontend/element_ready/ddmm-drilldown-menu.default', …)` + `document.addEventListener('DOMContentLoaded', …)` (D-14). |
| JSCR-04 | Double-init guard via `data-ddmm-init` attribute on container | `if (container.dataset.ddmmInit) return; container.dataset.ddmmInit = 'true';` (D-14, D-16). |
| JSCR-05 | PHP settings passed to JS via `wp_localize_script()` | **Superseded by D-15:** config passes via data-* attributes + `--ddmm-*` CSS vars (instance-safe). `wp_localize_script` is reserved for future global values; none needed yet. Planner must implement the data-* bridge, NOT `wp_localize_script`. |
</phase_requirements>

## Project Constraints (from CLAUDE.md)

These are stack-level mandates the planner must not contradict:

- **PHP 8.1+** (8.3 recommended) — use readonly properties, named args, match, enums where helpful.
- **WordPress 6.5+** — modern WP APIs available.
- **Elementor Free 3.29+** — use `elementor/widgets/register` hook (not deprecated `widgets_registered`), extend `\Elementor\Widget_Base`.
- **No jQuery** — all JS is pure ES6.
- **PSR-4 autoloading** via `spl_autoload_register` — no Composer runtime dependency for end users. Namespace `Devsroom_DDMM\` → `src/`.
- **No build step** — ships raw `.js` and `.css`. No webpack/Vite/SCSS.
- **CSS native nesting** (2023+ spec, 93%+ browser support) + BEM naming + `--ddmm-*` custom properties for theming.
- **IIFE-wrapped JS** for scope isolation.
- **Escaping** — all dynamic output via `esc_attr()`, `esc_url()`, `esc_html()`. `Icons_Manager` output is pre-escaped (use the established `phpcs:ignore` pattern).
- **Plugin identity** — text domain `devsroom-drilldown-mobile-menu`, version `0.0.01`, author MEHEDI HASSAN SHUBHO.
- **GSD workflow** — work proceeds via plan files; the `// Phase 4 will render…` stub in `render()` is the explicit integration point.

## Standard Stack

This phase adds **no new dependencies**. It uses only APIs already available in the project's stack. Versions are the project minimums (no npm packages — this is a no-build PHP/CSS/JS plugin).

### Core (already in project — Phase 4 consumes them)
| API / Facility | Version | Purpose in Phase 4 | Why |
|----------------|---------|--------------------|-----|
| PHP core | 8.1+ | Recursive renderer, string building, `uniqid()` | Required by project. `uniqid()` is a PHP core function — no extension needed. |
| `\Elementor\Controls_Manager` | 3.29+ | New Content Tab controls (SELECT, MEDIA, TEXT, SWITCHER) | Same API already used for trigger + menu sections in `_register_controls()`. |
| `\Elementor\Icons_Manager::render_icon()` | 3.29+ | Render node `icon` field before label | Exact same pattern as Phase 1 trigger icons (`ob_start`/`ob_get_clean` capture + `phpcs:ignore`). |
| `\Elementor\Plugin::$instance->editor->is_edit_mode()` | 3.29+ | Branch editor preview vs frontend | Already used in `render()` for the empty-state hint. |
| WordPress Custom Logo API | WP 4.5+ | `has_custom_logo()` / `get_custom_logo()` / `get_theme_mod('custom_logo')` | Default brand source (D-05). Returns full `<a><img></a>` markup. |
| `wp_get_attachment_image_url()` | WP core | Custom Image brand source URL | For MEDIA control brand option. |
| `esc_html()` / `esc_attr()` / `esc_url()` | WP core | Recursive escaping | Mandatory (PLUG-06 complete; Phase 4 must maintain). |

### Supporting (Phase 4 touches these files)
| File | Role | Change |
|------|------|--------|
| `src/Rendering/DrawerRenderer.php` | NEW — the renderer | Create directory + file (D-31). |
| `src/Elementor/Widget/DrillDownMenu.php` | Integration point | Add "Drawer Header" Content Tab section; replace `// Phase 4 will render…` stub with `DrawerRenderer::render()` call inside `.ddmm-widget` wrapper; add `is_edit_mode()` editor-preview branch. |
| `assets/css/ddmm-frontend.css` | Base styles | Append `.ddmm-widget`, `.ddmm-overlay`, `.ddmm-drawer` (off-canvas), `.ddmm-panel`, `.ddmm-back`, `.ddmm-menu`, `.ddmm-editor-preview`, `--ddmm-*` layout vars. |
| `assets/js/ddmm-frontend.js` | JS shell | Fill the empty IIFE with bootstrap (class skeleton + dual-path init + guard). No behavior. |

### Alternatives Considered (all REJECTED per locked decisions — do not revisit)
| Instead of | Could Use | Why NOT (locked) |
|------------|-----------|------------------|
| Inline rendering in `render()` | Separate `DrawerRenderer` class | D-31 mandates separation. |
| `role="menu"` / `role="menuitem"` | ARIA navigation roles | Pitfall 4 + D-21: never `role="menu"` on site nav. |
| `wp_localize_script` for config | data-* attributes + CSS vars | D-15 supersedes JSCR-05 literal wording — per-instance safety. |
| jQuery in JS | vanilla ES6 | Project mandate (no jQuery). |
| `wp_enqueue_script_module` | classic `wp_enqueue_script` | PROJECT.md: no `wp_localize_script` equivalent for modules; Elementor's frontend init was designed for classic scripts. (Already decided in earlier phases.) |

**Installation:** None — no packages. Only `mkdir src/Rendering` (or let the file write create it).

**Version verification:** N/A — all APIs are WordPress/Elementor core, already present at the project's declared minimums.

## Architecture Patterns

### Recommended Project Structure (delta — only Phase 4 additions)
```
src/
├── Rendering/                      # NEW directory (Phase 4)
│   └── DrawerRenderer.php          # NEW — stateless recursive tree→HTML renderer
├── Elementor/Widget/
│   └── DrillDownMenu.php           # MODIFIED — new controls + DrawerRenderer::render() call
├── MenuBuilder/
│   ├── WpNavTree.php               # UNCHANGED — feeds $tree (consumer)
│   └── CustomTree.php              # UNCHANGED — feeds $tree (consumer)
assets/
├── css/ddmm-frontend.css           # MODIFIED — append drawer/panel base layout
└── js/ddmm-frontend.js             # MODIFIED — fill IIFE with bootstrap (no behavior)
```

### Pattern 1: Stateless Recursive Renderer with ID Threading (THE core pattern)

**What:** `DrawerRenderer` exposes one static entry point. Private recursive helpers walk the tree. The key insight: **panel IDs are generated exactly once per panel — at the moment the panel is opened — and passed by value down the recursion stack** so child panels and back buttons reference the correct ancestor without any shared mutable state or ID re-derivation.

**When to use:** Any recursive tree→HTML conversion where generated IDs must be referenced both "downward" (child panel knows its ancestor for back nav) and "across" (parent's `data-target` must match child's `data-panel-id`).

**Recommended method decomposition (Claude's discretion area — this is a concrete recommendation):**

```php
namespace Devsroom_DDMM\Rendering;

class DrawerRenderer {

    /**
     * Stateless entry point. Called from DrillDownMenu::render().
     *
     * @param array  $tree      Root-level nodes from WpNavTree/CustomTree.
     * @param array  $settings  Elementor widget settings (brand_source, nav_label, etc.).
     * @param string $widget_id Elementor widget unique ID.
     * @return void  Echos HTML directly (Elementor render() context expects echo).
     */
    public static function render( array $tree, array $settings, string $widget_id ): void {
        $root_panel_id = uniqid( 'ddmm-panel-', false );  // D-09: root gets uniqid too.

        // 1. Overlay (D-25 data-ddmm-overlay hook).
        // 2. Drawer <div id="ddmm-drawer-{widget_id}" data-ddmm-drawer aria-hidden="true"> (D-20, D-24, A11Y-03).
        //    3. Header via render_header( $settings )  (D-05..D-08, D-07 always present).
        //    4. <nav aria-label="{nav_label}">
        //       5. <div class="ddmm-panels">
        //          6. render_panel( $tree, $settings, $root_panel_id, $is_root = true )
        //             — opens root <div class="ddmm-panel ddmm-panel--active" data-panel-id="{root_panel_id}">
        //             — iterates items via render_item()
        //       </div>
        //    </nav>
        // </div>
    }

    /**
     * Render one panel (root or child).
     *
     * @param array  $items      The nodes belonging to this panel.
     * @param array  $settings   Widget settings.
     * @param string $panel_id   THIS panel's uniqid (used for data-panel-id).
     * @param bool   $is_root    Root panel: no back row, gets --active class.
     */
    private static function render_panel( array $items, array $settings, string $panel_id, bool $is_root ): void {
        // <div class="ddmm-panel [ddmm-panel--active]" data-panel-id="{panel_id}" [aria-labelledby for child] [aria-hidden for child]>
        //   if ! $is_root: render_back_row( $parent_title, $panel_id_of_ancestor, $settings )
        //   <ul class="ddmm-menu">
        //     foreach $items as $node: render_item( $node, $settings, $panel_id )  // $panel_id is THIS panel's id = the ancestor for THIS node's children
        //   </ul>
        // </div>
    }

    /**
     * Render one menu item. Emits <li> + split <a>/<button> for parents, <li>+<a> for leaves.
     * For parents: ALSO emits the child panel as a sibling IMMEDIATELY AFTER </li> (D-13).
     *
     * @param array  $node              Tree node (8-field contract).
     * @param array  $settings          Widget settings.
     * @param string $ancestor_panel_id The panel this item lives in (= back-target for this item's child panel).
     */
    private static function render_item( array $node, array $settings, string $ancestor_panel_id ): void {
        // <li class="ddmm-menu__item">
        //   [icon if non-empty — D-29/D-30]
        //   <a href="{url}" [target] [class="{wp classes}"]>{title}</a>
        //   if has_children:
        //     $child_panel_id = uniqid( 'ddmm-panel-', false );   // generated HERE, passed into child panel
        //     <button type="button" class="ddmm-chevron"
        //             data-target="{child_panel_id}"
        //             aria-expanded="false"
        //             aria-controls="{child_panel_id}"
        //             aria-label="Show {title} submenu"></button>   // label via CSS ::after (D-02)
        // </li>
        //   if has_children:  render_panel( $node['children'], $settings, $child_panel_id, false )
        //     — NOTE: pass $ancestor_panel_id into render_back_row as data-back-target (D-10).
        //     — The child panel's own $panel_id = $child_panel_id; its children's ancestor = $child_panel_id.
    }

    private static function render_back_row( string $parent_title, string $ancestor_panel_id, string $title_id, array $settings ): void {
        // <div class="ddmm-back">
        //   <button type="button" class="ddmm-back__button" data-back-target="{ancestor_panel_id}">← Back</button>
        //   if show_back_title (default ON, D-12): <span class="ddmm-back__title" id="{title_id}">{parent_title}</span>
        // </div>
    }

    private static function render_header( array $settings ): void {
        // <div class="ddmm-header">
        //   brand block per brand_source (Site Logo / Custom Image / Custom Text / None — D-05..D-08)
        //   <button type="button" class="ddmm-close" data-ddmm-close aria-label="Close menu">✕</button>  // D-06 header-right
        // </div>
    }

    private static function render_icon( array $icon ): string {
        // Returns captured <span aria-hidden="true">{Icons_Manager output}</span> or '' if empty.
        // Empty detection: empty( $icon['value'] ) — D-29.
    }
}
```

**Critical threading rule (the part the planner must get right):** Each panel's `$panel_id` serves THREE roles simultaneously:
1. It is the `data-panel-id` on this panel's own `<div>` (so Phase 5 can find it).
2. It is the `ancestor_panel_id` passed to every `render_item()` inside this panel — so when an item has children, the child panel's back button references THIS panel via `data-back-target="{ancestor_panel_id}"`.
3. It is **not** the `data-target` on this panel's items' chevrons — that is the **child**'s freshly-generated `$child_panel_id`.

This is why `render_item()` takes `$ancestor_panel_id` (the panel it lives in) but generates a *new* `$child_panel_id` for its own child panel. There is no global ID map; the recursion stack carries everything.

### Pattern 2: Editor Preview vs Frontend Branching (D-18, D-20)

```php
// Inside DrillDownMenu::render(), AFTER the empty-state guard (existing code):
$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

// .ddmm-widget wrapper (D-16, D-27) wraps BOTH trigger and drawer.
echo '<div class="ddmm-widget" id="ddmm-widget-' . esc_attr( $widget_id ) . '" data-ddmm-init>';
//   ... existing trigger button output ...
echo '</div>';  // NO — see note below; trigger + drawer must share the .ddmm-widget parent.

// CORRECT structure: .ddmm-widget wraps trigger + overlay + drawer together:
?>
<div class="ddmm-widget" id="ddmm-widget-<?php echo esc_attr( $widget_id ); ?>" data-ddmm-init>
    <?php /* existing trigger button markup here */ ?>

    <?php if ( $is_editor ) : ?>
        <div class="ddmm-editor-preview">
            <?php DrawerRenderer::render_editor_preview( $tree, $settings ); // static root <ul> only ?>
        </div>
    <?php else : ?>
        <?php DrawerRenderer::render( $tree, $settings, $widget_id ); // overlay + drawer + panels ?>
    <?php endif; ?>
</div>
<?php
```

**Why:** Phase 5's JS scopes every query to `.ddmm-widget`; trigger, overlay, and drawer must all be descendants of the same container. The existing `aria-controls="ddmm-drawer-{widget_id}"` on the trigger points into the same wrapper.

### Pattern 3: Dual-Path JS Bootstrap (D-14, JSCR-01..04)

**What:** Fill the empty IIFE in `ddmm-frontend.js` with a class skeleton + init hookup. NO behavior.

**The hook string (VERIFIED):** `frontend/element_ready/{get_name()}.default`. Since `get_name()` returns `'ddmm-drilldown-menu'`, the action is `'frontend/element_ready/ddmm-drilldown-menu.default'`. `[CITED: developers.elementor.com/docs/hooks/js + ibenic.com]` — see Sources.

```javascript
( function() {
    'use strict';

    /**
     * DrillDownMenu — Phase 4 bootstrap skeleton.
     * Phase 5 adds open/close, panel slide, back nav, animation.
     */
    class DrillDownMenu {
        init( container ) {
            if ( ! container || container.dataset.ddmmInit ) {
                return; // JSCR-04 double-init guard (D-14).
            }
            container.dataset.ddmmInit = 'true';

            // Phase 4: locate elements only (no listeners yet).
            // Phase 5 will wire: trigger click → open, overlay/close click → close,
            // chevron click → drillDown(data-target), back click → goBack(data-back-target).
        }
    }

    const ddmm = new DrillDownMenu();

    // Path 1 (JSCR-03): Elementor frontend element_ready — fires per widget instance.
    // Hook string uses widget get_name() = 'ddmm-drilldown-menu'.
    function onElementorFrontend() {
        if ( typeof elementorFrontend === 'undefined' || ! elementorFrontend.hooks ) {
            return;
        }
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/ddmm-drilldown-menu.default',
            function( $scope ) {
                // Elementor passes a jQuery-wrapped scope; normalize to HTMLElement.
                const el = $scope && $scope[ 0 ] ? $scope[ 0 ] : $scope;
                ddmm.init( el );
            }
        );
    }

    // Path 2 (JSCR-03): DOMContentLoaded fallback — covers non-Elementor-rendered pages,
    // PJAX, and editor preview iframe scenarios where element_ready may not fire.
    function onDomReady() {
        document.querySelectorAll( '.ddmm-widget' ).forEach( function( el ) {
            ddmm.init( el );
        } );
    }

    if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks ) {
        // elementorFrontend already available.
        onElementorFrontend();
    } else {
        // Wait for Elementor's frontend/init event, then register the action.
        jQuery( window ).on( 'elementor/frontend/init', onElementorFrontend );
    }

    document.addEventListener( 'DOMContentLoaded', onDomReady );
} )();
```

**Note on `elementor/frontend/init`:** The standard Elementor pattern is to wait for the `elementor/frontend/init` event (via jQuery, since Elementor ships jQuery internally — using jQuery here does NOT violate the project's "no jQuery for our logic" rule; it's just hooking Elementor's own event bus). Alternatively, gate on `typeof elementorFrontend` and register the action immediately if available. Both work; the example above combines them. `[CITED: developers.elementor.com/docs/hooks/js]`

**Emphasize for the planner:** The `jQuery( window ).on( 'elementor/frontend/init', … )` line is Elementor's own event subscription mechanism — it is NOT using jQuery for DOM manipulation. The project's "no jQuery" mandate concerns the plugin's *own* DOM logic (use `querySelector`/`addEventListener`), not subscribing to Elementor's jQuery-based event bus. This is the same approach the Phase 1 research endorsed. `[ASSUMED — confirm with team if strict no-jQuery-globals is required; the safer alternative is a pure check on `window.elementorFrontend` with a polling/timeout fallback, but that is fragile.]`

### Anti-Patterns to Avoid (Phase-4-relevant subset of ARCHITECTURE.md / PITFALLS.md)
- **Positional panel navigation (Anti-Pattern 2 / Pitfall — prior bug v1.3.0):** Never use array index or DOM child position to map parents to child panels. The ID-based contract (`data-target` ↔ `data-panel-id`, `data-back-target` ↔ ancestor `data-panel-id`) makes navigation depth-agnostic. **Verification:** every parent chevron's `data-target` value must appear exactly once as a `data-panel-id` in the output.
- **Global JS state (Anti-Pattern 3):** Never use `window.ddmm` or `document.querySelector` (unscoped). All JS queries scope to the `.ddmm-widget` container passed to `init()`.
- **`role="menu"` (Pitfall 4):** Never apply `role="menu"`/`role="menuitem"`. Use `<nav aria-label>`.
- **Animating layout properties (Pitfall 3 — Phase 5 concern, but Phase 4 must enable GPU compositing):** The base CSS must position panels via `transform: translateX(…)` and `opacity` only — never `left`/`right`/`margin`/`width`. Phase 5 will only toggle classes.
- **Multiple-instance ID collision (Pitfall / Performance trap):** `uniqid()` per panel + `ddmm-widget-{widget_id}` per container prevents collisions when 2+ widgets share a page.
- **Unescaped recursive output (Security mistake):** Every `echo` in the renderer must pass through `esc_html`/`esc_url`/`esc_attr` — see Escaping section.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Panel ID uniqueness | Custom counter / hash / microtime logic | `uniqid('ddmm-panel-', false)` | PHP core, collision-safe for this use case, matches D-09/D-11 exactly. Prefix guarantees IDs don't collide with theme IDs. |
| Icon rendering (SVG/Font Awesome) | Manual `<svg>`/`<i class>` emission | `\Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] )` | Handles FA + SVG + custom libraries uniformly; pre-escaped output; matches Phase 1 trigger pattern. |
| Site logo markup | Manual `<img>` with `get_option('site_logo')` | `has_custom_logo()` + `get_custom_logo()` (returns `<a><img></a>`) OR `get_theme_mod('custom_logo')` + `wp_get_attachment_image_url()` for a bare `<img>` | WP core API; respects theme customizer; correct fallback semantics. `[CITED: developer.wordpress.org/reference/functions/get_custom_logo]` |
| Elementor editor detection | URL sniffing / `$_GET['elementor-preview']` | `\Elementor\Plugin::$instance->editor->is_edit_mode()` | Already used in `render()`; the canonical check. |
| Elementor controls | Custom HTML form fields | `\Elementor\Controls_Manager::SELECT` / `MEDIA` / `TEXT` / `SWITCHER` with `condition` arrays | Same API already used for trigger + menu sections. |
| Asset conditional loading | Manual `is_widget_present()` detection | Existing `get_script_depends()` / `get_style_depends()` (Phase 1) | Already wired; no change needed in Phase 4. |
| Escaping | Manual `htmlspecialchars` | `esc_html()` / `esc_attr()` / `esc_url()` | WordPress-correct, context-aware. |
| CSS nesting | SCSS / PostCSS | Native CSS nesting (`.ddmm-drawer { & .ddmm-panel { … } }`) | Project mandate: no build step; 93%+ browser support. |

**Key insight:** Phase 4 is almost entirely composition of already-available APIs. The novel work is the *recursive structure* and *ID threading*, not new tooling.

## Common Pitfalls

### Pitfall 1: Panel ID Mismatch Between `data-target` and `data-panel-id`
**What goes wrong:** A parent chevron's `data-target` doesn't match any `data-panel-id`, so Phase 5's `querySelector('[data-panel-id="…"]')` returns null and the drill-down silently fails.
**Why it happens:** Generating the child panel ID in one place (e.g., a pre-pass) and re-deriving it in another (e.g., the chevron emission) — the two derivations disagree. Or generating the ID inside `render_panel` but referencing it from `render_item` without passing it through.
**How to avoid:** Generate `$child_panel_id` **exactly once**, inside `render_item()`, the moment the item is identified as a parent. Use that single variable for BOTH the chevron's `data-target`/`aria-controls` AND the recursive `render_panel(…, $child_panel_id, …)` call. (See Pattern 1.)
**Warning signs:** In rendered HTML, grep `data-target` values and confirm each appears as a `data-panel-id`. This is an automatable Phase-4 verification step.

### Pitfall 2: Back Button Pointing to Wrong Ancestor
**What goes wrong:** A level-2 panel's back button points to the root instead of the level-1 panel (or vice versa).
**Why it happens:** Confusing "this panel's ID" with "the ancestor's ID" in the recursion. The back button needs the **ancestor** (the panel the user returns to), which is the panel the parent item *lives in* — NOT the child panel being created.
**How to avoid:** `render_item()` receives `$ancestor_panel_id` (= the panel it lives in). When it emits the child panel, it passes `$ancestor_panel_id` to `render_back_row()` as `data-back-target`. The child panel's OWN `$panel_id` (= `$child_panel_id`) is separate. (See Pattern 1 threading rule.)
**Warning signs:** Drill all the way to level 3, hit back — if it skips to root, the threading is wrong.

### Pitfall 3: Child Panel Placed INSIDE the Parent `<li>` (violates D-13)
**What goes wrong:** Child panel rendered as a child of the parent `<li>` instead of a sibling after `</li>`. Breaks `<ul>/<li>` semantics and complicates Phase 5 CSS transforms.
**Why it happens:** Easiest to write the child-panel echo inside the `<li>` block before closing it.
**How to avoid:** `render_item()` must emit `<li>…</li>` **completely**, close it, THEN echo the child `<div class="ddmm-panel">…</div>` as a sibling. The child panel is a sibling of the `<li>`, both children of the parent `<ul>`. (D-13, D-28.)
**Warning signs:** HTML validator warns about `<div>` inside `<ul>` as anything other than a direct `<li>` sibling.

### Pitfall 4: `aria-labelledby` Target Missing or Duplicate
**What goes wrong:** Child panel's `aria-labelledby` points to a non-existent ID, or multiple back-row title spans share an ID.
**Why it happens:** Hardcoding the back-row title ID, or forgetting to give each one a unique ID.
**How to avoid:** Each back-row title `<span>` gets its own `uniqid('ddmm-back-title-', false)`; the child panel's `aria-labelledby` references that exact ID. Generate the title ID in `render_back_row()` and thread it to the panel wrapper. Root panel has NO `aria-labelledby` (D-22).
**Warning signs:** axe/WAVE flags "aria-labelledby element does not exist" or duplicate-ID warnings.

### Pitfall 5: Unescaped Output in Deep Recursion
**What goes wrong:** A nested title/URL/CSS-class reaches the output unescaped → stored XSS (PITFALLS.md Security Mistakes).
**Why it happens:** In a recursive renderer, it's easy to `echo $node['title']` deep in the stack where escaping discipline slips.
**How to avoid:** Enforce a rule: **no dynamic string is ever echoed without an `esc_*` wrapper**. Specifically: `esc_html( $node['title'] )`, `esc_url( $node['url'] )`, `esc_attr( $node['target'] )`, `esc_attr( implode( ' ', array_map( 'sanitize_html_class', $node['classes'] ) ) )` for WP menu classes, `esc_attr( $panel_id )` on all data-* and id attributes. The only exception is `Icons_Manager::render_icon()` output (pre-escaped; `phpcs:ignore`).
**Warning signs:** PHPCS `WordPress.Security.EscapeOutput` warnings; grep for `echo \$` without a preceding `esc_`.

### Pitfall 6: Editor Preview Emits Off-Canvas Drawer (breaks editor UX)
**What goes wrong:** The full off-canvas drawer renders in the editor, invisible or overlapping the canvas.
**Why it happens:** Forgetting the `is_edit_mode()` branch (D-18).
**How to avoid:** `render()` branches: editor → static root `<ul>` preview block (`.ddmm-editor-preview`); frontend → full overlay + drawer. The editor preview reuses the item-rendering helper but skips overlay/drawer/header wrapper.
**Warning signs:** Drawer HTML appears in the editor DOM but is off-screen; or the editor shows nothing because `translateX(-100%)` hides it.

### Pitfall 7: Drawer Missing `id="ddmm-drawer-{widget_id}"` (breaks A11Y-03)
**What goes wrong:** The trigger's existing `aria-controls="ddmm-drawer-{widget_id}"` points to a non-existent ID.
**Why it happens:** The drawer is emitted by `DrawerRenderer` which must know the widget_id to set the id attribute.
**How to avoid:** `DrawerRenderer::render()` receives `$widget_id` and emits `id="ddmm-drawer-{esc_attr($widget_id)}"` on the drawer `<div>`. This closes the A11Y-03 contract Phase 1 opened.
**Warning signs:** axe flags "aria-controls element does not exist" on the trigger.

### Pitfall 8: `get_custom_logo()` Returns Linked Markup (brand inside another link)
**What goes wrong:** `get_custom_logo()` returns `<a href="home"><img …></a>`. If the brand is intended to be non-clickable in the drawer header, the link is unwanted; if it's fine, it nests cleanly. Either way, the renderer must decide.
**Why it happens:** `get_custom_logo()` always wraps in a home link (unless theme unlinks on front page). `[CITED: developer.wordpress.org]`
**How to avoid:** For the default "Site Logo" brand, accepting the home link is reasonable (it's the site logo → home is expected). If a bare `<img>` is wanted, use `get_theme_mod('custom_logo')` to get the attachment ID, then `wp_get_attachment_image( $logo_id, 'full', false, [ 'class' => 'ddmm-brand__img' ] )` which returns just `<img>`. Recommend the bare-`<img>` approach for predictable CSS `max-height` control (D-08 says no inline width/height — `wp_get_attachment_image` adds them, so either strip via the `wp_get_attachment_image_src` + manual `<img>` route, or accept them). `[ASSUMED: recommend `wp_get_attachment_image_src` + manual `<img class="ddmm-brand__img">` for full control over D-08's no-inline-dimensions rule.]`
**Warning signs:** Brand image has unexpected inline `width`/`height` overriding the CSS `max-height`.

## Code Examples

### Example 1: Elementor Content Tab "Drawer Header" Section (new controls)
Source: existing `_register_controls()` pattern in `DrillDownMenu.php` (SELECT + condition array proven for trigger_type).

```php
// Append to _register_controls(), after section_menu:
$this->start_controls_section(
    'section_drawer_header',
    [
        'label'     => esc_html__( 'Drawer Header', 'devsroom-drilldown-mobile-menu' ),
        'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
        'separator' => 'before',
    ]
);

// D-05: Brand source SELECT (Site Logo / Custom Image / Custom Text / None).
$this->add_control(
    'brand_source',
    [
        'label'   => esc_html__( 'Brand', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'site_logo',
        'options' => [
            'site_logo'    => esc_html__( 'Site Logo', 'devsroom-drilldown-mobile-menu' ),
            'custom_image' => esc_html__( 'Custom Image', 'devsroom-drilldown-mobile-menu' ),
            'custom_text'  => esc_html__( 'Custom Text', 'devsroom-drilldown-mobile-menu' ),
            'none'         => esc_html__( 'None', 'devsroom-drilldown-mobile-menu' ),
        ],
    ]
);

// Custom Image — MEDIA control (conditional on brand_source = custom_image).
$this->add_control(
    'brand_image',
    [
        'label'     => esc_html__( 'Choose Image', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::MEDIA,
        'default'   => [ 'url' => '' ],
        'condition' => [ 'brand_source' => 'custom_image' ],
    ]
);

// Custom Text — TEXT control (conditional on brand_source = custom_text).
$this->add_control(
    'brand_text',
    [
        'label'       => esc_html__( 'Brand Text', 'devsroom-drilldown-mobile-menu' ),
        'type'        => \Elementor\Controls_Manager::TEXT,
        'default'     => get_bloginfo( 'name' ),  // sensible default = site name
        'placeholder' => esc_html__( 'Brand text', 'devsroom-drilldown-mobile-menu' ),
        'condition'   => [ 'brand_source' => 'custom_text' ],
    ]
);

$this->end_controls_section();

// Separate section for nav + back-row toggles (D-21, D-12).
$this->start_controls_section(
    'section_drawer_settings',
    [
        'label'     => esc_html__( 'Drawer Settings', 'devsroom-drilldown-mobile-menu' ),
        'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
        'separator' => 'before',
    ]
);

// D-21: nav aria-label (default translatable "Mobile Menu").
$this->add_control(
    'nav_label',
    [
        'label'   => esc_html__( 'Navigation Label', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::TEXT,
        'default' => esc_html__( 'Mobile Menu', 'devsroom-drilldown-mobile-menu' ),
    ]
);

// D-12: show parent name in back row (default ON).
$this->add_control(
    'show_back_title',
    [
        'label'   => esc_html__( 'Show Parent Name in Back Row', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => 'yes',
    ]
);

$this->end_controls_section();
```

### Example 2: Brand Rendering (Site Logo default with fallback)
Source: `[CITED: developer.wordpress.org/reference/functions/get_custom_logo]` + WP Custom Logo API.

```php
private static function render_brand( array $settings ): void {
    $source = $settings['brand_source'] ?? 'site_logo';

    echo '<div class="ddmm-brand">';

    switch ( $source ) {
        case 'site_logo':
            // D-05: auto-detect via WP custom logo; fallback to site name text.
            if ( has_custom_logo() ) {
                $logo_id = get_theme_mod( 'custom_logo' );
                if ( $logo_id ) {
                    // Bare <img> via attachment src — full control over D-08 (no inline w/h from wp_get_attachment_image).
                    $src = wp_get_attachment_image_url( (int) $logo_id, 'full' );
                    if ( $src ) {
                        printf(
                            '<img class="ddmm-brand__img" src="%1$s" alt="%2$s">',
                            esc_url( $src ),
                            esc_attr( get_bloginfo( 'name' ) )  // logo alt = site name
                        );
                    }
                }
            } else {
                // Fallback: site name as text.
                printf( '<span class="ddmm-brand__text">%s</span>', esc_html( get_bloginfo( 'name' ) ) );
            }
            break;

        case 'custom_image':
            $img = $settings['brand_image']['url'] ?? '';
            if ( $img ) {
                printf(
                    '<img class="ddmm-brand__img" src="%s" alt="%s">',
                    esc_url( $img ),
                    esc_attr( get_bloginfo( 'name' ) )
                );
            }
            break;

        case 'custom_text':
            $text = $settings['brand_text'] ?? get_bloginfo( 'name' );
            printf( '<span class="ddmm-brand__text">%s</span>', esc_html( $text ) );
            break;

        case 'none':
        default:
            // D-07: header still renders with close button; brand block is empty.
            break;
    }

    echo '</div>';
}
```

### Example 3: Icon Rendering (node icon field, D-29/D-30)
Source: existing Phase 1 trigger-icon pattern in `DrillDownMenu.php` lines 360-388.

```php
private static function render_icon( array $icon ): string {
    // D-29: only render when non-empty. WP nodes have icon = [] (text-only).
    if ( empty( $icon ) || empty( $icon['value'] ) ) {
        return '';
    }

    ob_start();
    \Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
    $icon_html = ob_get_clean();

    // D-30: wrap in aria-hidden span; output is pre-escaped by Icons_Manager.
    return '<span class="ddmm-menu__icon" aria-hidden="true">' . $icon_html . '</span>';
    // phpcs:ignore on echo of this string — same justification as trigger icon.
}
```

### Example 4: Escaping WP Menu Item Classes (D-04)
Source: WordPress escaping conventions.

```php
$classes_attr = '';
if ( ! empty( $node['classes'] ) && is_array( $node['classes'] ) ) {
    // Filter empties, sanitize each as an HTML class, join.
    $clean = array_filter( array_map( 'sanitize_html_class', $node['classes'] ) );
    if ( ! empty( $clean ) ) {
        $classes_attr = ' class="' . esc_attr( implode( ' ', $clean ) ) . '"';
    }
}
```

### Example 5: Target HTML Output Structure (the DOM contract Phase 5 consumes)
Source: ARCHITECTURE.md rendering data flow + D-13/D-25/D-28.

```html
<!-- .ddmm-widget wraps everything (D-16, D-27) -->
<div class="ddmm-widget" id="ddmm-widget-{widget_id}" data-ddmm-init>

  <!-- Existing trigger (Phase 1) — aria-controls already points to drawer id -->
  <button class="ddmm-trigger …" aria-expanded="false" aria-controls="ddmm-drawer-{widget_id}">…</button>

  <!-- Overlay (D-25 hook) -->
  <div class="ddmm-overlay" data-ddmm-overlay aria-hidden="true"></div>

  <!-- Off-canvas drawer (D-20 always in DOM; D-24 aria-hidden=true until Phase 5) -->
  <div class="ddmm-drawer"
       id="ddmm-drawer-{widget_id}"
       data-ddmm-drawer
       aria-hidden="true">

    <!-- Header (D-07 always present) -->
    <div class="ddmm-header">
      <div class="ddmm-brand">…</div>
      <button type="button" class="ddmm-close" data-ddmm-close aria-label="Close menu">✕</button>
    </div>

    <!-- Nav (D-21 configurable aria-label, never role=menu) -->
    <nav class="ddmm-nav" aria-label="Mobile Menu">
      <div class="ddmm-panels">

        <!-- ROOT panel (D-09 uniqid, D-26 --active) -->
        <div class="ddmm-panel ddmm-panel--active"
             data-panel-id="ddmm-panel-{rootUniqid}">  <!-- NO aria-hidden on root (active) -->
          <ul class="ddmm-menu">

            <!-- LEAF item (D-04) -->
            <li class="ddmm-menu__item">
              <a class="menu-item menu-item-type-post_type" href="/about">About</a>
            </li>

            <!-- PARENT item, split (D-01): <a> for the URL + chevron <button> for drill-down -->
            <li class="ddmm-menu__item">
              <span class="ddmm-menu__icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
              <a href="/shop">Shop</a>
              <!-- Chevron: data-target + aria-controls BOTH = child panel id (D-23) -->
              <button type="button" class="ddmm-chevron"
                      data-target="ddmm-panel-{childUniqid}"
                      aria-expanded="false"
                      aria-controls="ddmm-panel-{childUniqid}"
                      aria-label="Show Shop submenu"></button>
            </li>

            <!-- CHILD panel = sibling AFTER parent </li> (D-13) -->
            <div class="ddmm-panel"
                 data-panel-id="ddmm-panel-{childUniqid}"
                 aria-labelledby="ddmm-back-title-{titleUniqid}"
                 aria-hidden="true">  <!-- D-24: sub-panels ship hidden -->

              <!-- Back row (D-10, D-11) -->
              <div class="ddmm-back">
                <button type="button" class="ddmm-back__button"
                        data-back-target="ddmm-panel-{rootUniqid}">← Back</button>
                <span class="ddmm-back__title" id="ddmm-back-title-{titleUniqid}">Shop</span>
              </div>

              <ul class="ddmm-menu">
                <!-- recurse… grandchildren produce another sibling panel -->
              </ul>
            </div>

          </ul>
        </div>

      </div>
    </nav>
  </div>
</div>
```

**On `data-target` + `aria-controls` both present (D-23 / A11Y-02):** Yes — emit BOTH on the chevron. `data-target` is the JS hook (short attr per D-25); `aria-controls` is the a11y hook (screen readers announce the controlled panel). They reference the same child panel ID. Including both satisfies JSCR nav contract AND A11Y-02 simultaneously. `[VERIFIED against ARIA spec — aria-controls takes an ID reference; data-target is a custom attr.]`

### Example 6: Base Layout CSS (native nesting + --ddmm-* vars)
Source: project CSS conventions (CLAUDE.md) + existing `ddmm-frontend.css` skeleton.

```css
/* Extends existing .elementor-widget-ddmm-drilldown-menu { --ddmm-* } block */
.elementor-widget-ddmm-drilldown-menu {
    /* Phase 4 layout vars (Phase 6 Style Tab will override) */
    --ddmm-drawer-width: 320px;
    --ddmm-drawer-bg: #ffffff;
    --ddmm-overlay-bg: rgba(0, 0, 0, 0.5);
    --ddmm-header-height: 56px;
    --ddmm-brand-max-height: 40px;       /* D-08 */
    --ddmm-panel-bg: #ffffff;
    --ddmm-menu-min-height: 48px;        /* WCAG touch target */
    --ddmm-z-overlay: 1000;
    --ddmm-z-drawer: 1001;
}

/* .ddmm-widget scope root (D-16) */
.ddmm-widget { position: relative; }

/* Overlay — fixed, full-screen, hidden by default (DRAW-02) */
.ddmm-overlay {
    position: fixed;
    inset: 0;                  /* top/right/bottom/left: 0 shorthand */
    background: var(--ddmm-overlay-bg);
    opacity: 0;
    visibility: hidden;
    z-index: var(--ddmm-z-overlay);
    transition: opacity var(--ddmm-transition-duration) ease;  /* Phase 5 toggles via .ddmm-is-open */
}

/* Drawer — fixed left, off-canvas (DRAW-01, D-17, D-20) */
.ddmm-drawer {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;             /* or 100dvh for mobile */
    width: var(--ddmm-drawer-width);
    max-width: 85vw;
    background: var(--ddmm-drawer-bg);
    transform: translateX(-100%);   /* off-canvas — GPU friendly (Pitfall 3) */
    z-index: var(--ddmm-z-drawer);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform var(--ddmm-transition-duration) ease;  /* Phase 5 toggles via .ddmm-is-open */
    will-change: transform;
}

/* Header (D-07 always present) */
.ddmm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: var(--ddmm-header-height);
    padding: 0 16px;
    flex-shrink: 0;

    & .ddmm-brand__img { max-height: var(--ddmm-brand-max-height); width: auto; height: auto; }  /* D-08 */
}

/* Panels container — clips overflow so only one panel shows */
.ddmm-panels {
    position: relative;
    flex: 1 1 auto;
    overflow: hidden;
}

/* Panel — absolute stacked, slide-ready (Phase 5 toggles translateX) */
.ddmm-panel {
    position: absolute;
    inset: 0;
    overflow-y: auto;
    transform: translateX(100%);   /* off-stage right by default (Pitfall 3: transform only) */
    transition: transform var(--ddmm-transition-duration) ease, opacity var(--ddmm-transition-duration) ease;
    will-change: transform, opacity;
    background: var(--ddmm-panel-bg);

    &.ddmm-panel--active { transform: translateX(0); }  /* D-26 root active in-place */
}

/* Menu list semantic structure (D-28) */
.ddmm-menu { list-style: none; margin: 0; padding: 0; }
.ddmm-menu__item {
    display: flex;
    align-items: center;
    min-height: var(--ddmm-menu-min-height);
    padding: 0 16px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

/* Chevron via CSS ::after (D-02 — no extra DOM node) */
.ddmm-chevron {
    margin-left: auto;            /* push to row-right */
    background: none;
    border: none;
    cursor: pointer;
    padding: 0 8px;
    &::after { content: '›'; font-size: 1.4em; line-height: 1; }
}

/* Back row (D-11) */
.ddmm-back {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

/* Editor-only preview (D-18) — never on frontend */
.ddmm-editor-preview {
    padding: 12px;
    background: #f9f9f9;
    border: 1px dashed #ccc;
    & .ddmm-menu { position: static; }  /* override off-stage positioning for inline preview */
}
```

**Why this CSS structure (for the planner):** Every animated property is `transform` or `opacity`. Phase 5 will ONLY add/remove classes (`ddmm-is-open` on `.ddmm-widget`/`.ddmm-drawer`; `ddmm-panel--active` on panels). No Phase-5 CSS will touch layout properties. This satisfies ANIM-04 (GPU-composited) preemptively.

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `wp_enqueue_script_module()` (WP 6.5+) | Classic `wp_enqueue_script()` (this project) | Decided in PROJECT.md | No `wp_localize_script` equivalent for modules; Elementor frontend init designed for classic. Project uses classic. |
| `widgets_registered` hook | `elementor/widgets/register` hook | Elementor 3.5+ | Project already uses the modern hook (Phase 1). |
| Manual `<i class>` icon emission | `\Elementor\Icons_Manager::render_icon()` | Elementor 3.x | Handles FA + SVG + custom libs; pre-escaped. Used in Phase 1; reused in Phase 4. |
| `role="menu"` on nav | `<nav aria-label>` | WCAG best practice | Pitfall 4 — never `role="menu"` for site nav. |
| SCSS nesting | Native CSS nesting | 2023+ (93% browser support) | Project: no build step; native nesting used. |

**Deprecated/outdated (avoid):**
- `wp_localize_script()` for per-instance config — superseded by D-15 (data-* + CSS vars).
- `role="menu"` / `role="menuitem"` — WCAG anti-pattern for site nav.
- PHP references in tree building — already avoided in Phases 2/3 (Pitfall 2).

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `jQuery( window ).on( 'elementor/frontend/init', … )` is acceptable for subscribing to Elementor's event bus despite the "no jQuery" project mandate (the mandate concerns the plugin's own DOM logic, not Elementor's internal event system). | Pattern 3 (JS Bootstrap) | LOW — this is the documented Elementor pattern. If strict no-jQuery-globals is required, alternative is polling `window.elementorFrontend` or relying solely on `DOMContentLoaded` + `data-ddmm-init` guard. Recommend confirming with team, but the jQuery-event-bus line is industry-standard for Elementor widgets. |
| A2 | Bare `<img>` via `wp_get_attachment_image_src` + manual `<img>` markup is preferred over `get_custom_logo()` (which wraps in a home link) to satisfy D-08's "no inline width/height" rule cleanly. | Example 2 (Brand Rendering) / Pitfall 8 | LOW — either works; `get_custom_logo()` is simpler but adds a link + inline dims. The bare-`<img>` route gives full CSS control. Team may prefer the linked logo for UX (logo → home). |
| A3 | Editor preview reuses `render_item()` but skips overlay/drawer/header; the planner decides whether to add a `render_editor_preview()` static method that calls a shared item helper. | Pattern 2 | LOW — implementation detail within Claude's discretion (D-18 specifies the behavior, not the method split). |

**All other claims are VERIFIED (codebase read) or CITED (official Elementor/WP docs).**

## Open Questions

1. **Close button hook attribute name (D-25 lists trigger/overlay/drawer/init but DRAW-04/D-06 add a close button):** The close button needs a JS hook. Recommendation: `data-ddmm-close` (consistent with the `data-ddmm-*` namespacing for hooks, distinct from the short nav attrs). The planner should add this to the hook list — it is implied by D-06 but not explicitly named in D-25. **Recommendation:** use `data-ddmm-close`.

2. **Drawer `ddmm-is-open` target (D-26):** "the drawer gains `ddmm-is-open` when opened" — should the class go on `.ddmm-widget` (the scope root) or `.ddmm-drawer`? Putting it on `.ddmm-widget` lets CSS target both overlay (`.ddmm-widget.ddmm-is-open .ddmm-overlay`) and drawer (`.ddmm-widget.ddmm-is-open .ddmm-drawer`) from one toggle. **Recommendation:** toggle `ddmm-is-open` on `.ddmm-widget` (Phase 5 decision, but Phase 4 CSS must anticipate it).

3. **`100vh` vs `100dvh` for drawer height:** `100dvh` handles mobile browser chrome better but has older-browser caveats. **Recommendation:** use `100dvh` with `100vh` fallback (Phase 5/6 polish; Phase 4 can ship `100vh` and refine). `[ASSUMED — minor CSS detail.]`

4. **Should the editor preview show back rows / nested structure?** D-18 says "root panel `<ul>` inline (static), sub-panels omitted." So NO back rows in preview — just the root `<ul>` with items + chevrons. Confirmed by D-18.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.1+ | Renderer, autoloader | ✓ (declared min) | 8.1/8.3 | — |
| WordPress 6.5+ | Custom Logo API, nav menu API | ✓ (declared min) | 6.5+ | — |
| Elementor Free 3.29+ | Controls_Manager, Icons_Manager, is_edit_mode | ✓ (declared min) | 3.29+ | — |
| `\Elementor\Icons_Manager` | Node icon rendering (D-30) | ✓ | bundled w/ Elementor | If icon empty → skip render (D-29) |
| WP Custom Logo (`has_custom_logo`) | Default brand (D-05) | conditional | — | Fallback to `get_bloginfo('name')` text |
| `php -l` (PHP lint) | Validation (no WP harness needed) | to verify on dev machine | — | manual visual inspection |

**Missing dependencies with no fallback:** None.
**Missing dependencies with fallback:** If WP custom logo is not set (theme doesn't support it), brand falls back to site name text (D-05) — this is a *data* condition, not a *tool* condition.

## Validation Architecture

**Context:** `workflow.nyquist_validation` is `true` in `.planning/config.json` → this section is required. This is a PHP rendering phase with no JS behavior (JS is bootstrap-only). There is **no WordPress/Elementor test harness** in the project (no `phpunit`, no `wp-env`, no test bootstrap detected). Validation is therefore: PHP lint + structural/grep checks on rendered HTML + manual visual inspection.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | None (no PHPUnit / wp-env configured) |
| Config file | none |
| Quick run command | `php -l src/Rendering/DrawerRenderer.php` (per-file lint) |
| Full suite command | `find src -name '*.php' -exec php -l {} \;` (lint all) + grep checks below |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DRAW-01 | Drawer off-canvas in DOM (`translateX(-100%)`) | static (CSS grep) | `grep -c "translateX(-100%)" assets/css/ddmm-frontend.css` (expect ≥1) | n/a (CSS file) |
| DRAW-02 | Overlay element present | static (PHP grep) | `grep -c "ddmm-overlay" src/Rendering/DrawerRenderer.php` (expect ≥1) | ❌ Wave 0 (file created this phase) |
| DRAW-03 | Brand source SELECT + 4 options | static (PHP grep) | `grep -c "brand_source" src/Elementor/Widget/DrillDownMenu.php` (expect ≥1) | ✅ exists (modified) |
| DRAW-04 | Close button in header | static (PHP grep) | `grep -c "ddmm-close" src/Rendering/DrawerRenderer.php` (expect ≥1) | ❌ Wave 0 |
| DRAW-05 | Root panel + top-level items | static (PHP grep) | `grep -c "ddmm-panel--active" src/Rendering/DrawerRenderer.php` (expect ≥1) | ❌ Wave 0 |
| DRAW-06 | `data-target` on chevron ↔ `data-panel-id` on child | manual (rendered HTML) | Render in WP, inspect DOM: every `data-target` value appears as a `data-panel-id` | n/a |
| DRAW-07 | Back button per child panel | static (PHP grep) | `grep -c "data-back-target" src/Rendering/DrawerRenderer.php` (expect ≥1) | ❌ Wave 0 |
| DRAW-08 | Back-row title toggle (default ON) | static (PHP grep) | `grep -c "show_back_title" src/Elementor/Widget/DrillDownMenu.php` (expect ≥1) | ✅ |
| DRAW-09 | Unlimited nesting (recursive render) | manual (deep menu test) | Configure 4-level custom menu, render, verify 4 nested panels | n/a |
| DRAW-10 | ID-based (no positional) | static (grep negative) | `grep -nE "children\[|nextSibling|index" assets/js/ddmm-frontend.js` (expect 0 — Phase 5 concern but verify bootstrap has none) | ✅ exists |
| DRAW-11 | `uniqid()` panel IDs | static (PHP grep) | `grep -c "uniqid('ddmm-panel-'" src/Rendering/DrawerRenderer.php` (expect ≥1) | ❌ Wave 0 |
| A11Y-01 | `<nav aria-label>`, no `role="menu"` | static (grep positive+negative) | `grep -c "aria-label" …` ≥1 AND `grep -c 'role="menu"' src/ assets/` (expect 0) | ❌ Wave 0 |
| A11Y-02 | Chevron `aria-expanded` + `aria-controls` | static (PHP grep) | `grep -cE "aria-expanded|aria-controls" src/Rendering/DrawerRenderer.php` (expect ≥2) | ❌ Wave 0 |
| A11Y-03 | Drawer `id="ddmm-drawer-{widget_id}"` matches trigger `aria-controls` | static (PHP grep) | `grep -c "ddmm-drawer-" src/Rendering/DrawerRenderer.php` (expect ≥1) | ❌ Wave 0 |
| JSCR-01..04 | IIFE, dual-path init, guard, no jQuery-for-logic | static (JS grep) | `grep -c "element_ready/ddmm-drilldown-menu.default" assets/js/ddmm-frontend.js` ≥1; `grep -c "ddmmInit" …` ≥1 | ✅ exists (modified) |
| JSCR-05 | data-* bridge (NOT wp_localize_script) | static (negative grep) | `grep -c "wp_localize_script" src/` (expect 0) | n/a |

### Sampling Rate
- **Per task commit:** `php -l` on touched PHP files + relevant grep checks.
- **Per wave merge:** full `find src -name '*.php' -exec php -l {} \;` + all grep checks + manual WP render inspection.
- **Phase gate:** Full lint green + manual render in Elementor (editor preview shows root `<ul>`; frontend shows off-canvas drawer with correct `data-target`/`data-panel-id`/`data-back-target` contract) before `/gsd-verify-work`.

### Wave 0 Gaps
- [ ] `src/Rendering/DrawerRenderer.php` — does not exist yet; create as first Wave 0 task.
- [ ] No PHPUnit harness — validation relies on `php -l` + grep + manual inspection. If the team wants automated HTML-structure tests, that is out of Phase 4 scope (would require a WP test bootstrap).
- [ ] No "rendered HTML fixture" exists — consider capturing a sample rendered output for diff-based verification in later phases. `[ASSUMED: not required for Phase 4 gate.]`

**What CANNOT be automated without a WP test harness:** actual rendered HTML output (requires loading WordPress + Elementor + a configured widget). The grep checks validate that the *code emits* the right attributes/classes, but not that the *runtime output* is correct end-to-end. Manual render-in-WP inspection is the gap-closer.

## Security Domain

**Context:** `security_enforcement` not explicitly set in config → treat as enabled. This phase is PHP rendering — output escaping is the primary security concern.

### Applicable ASVS Categories
| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | n/a (no auth in rendering) |
| V3 Session Management | no | n/a |
| V4 Access Control | no | n/a (render reads settings, not user caps) |
| V5 Input Validation / Output Encoding | **yes** | `esc_html()` / `esc_url()` / `esc_attr()` on ALL dynamic output; `sanitize_html_class` on WP menu classes; `Icons_Manager` pre-escaped output (phpcs:ignore). PLUG-06 already complete — Phase 4 must maintain. |
| V6 Cryptography | no | n/a |

### Known Threat Patterns for PHP/Elementor Rendering
| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Stored XSS via menu item title | Tampering | `esc_html( $node['title'] )` on every echo. Admin-set titles render to all visitors. |
| Stored XSS / open redirect via menu URL | Tampering | `esc_url( $node['url'] )`. |
| Attribute injection via panel ID / class | Tampering | `esc_attr()` on all id/data-*/class values; `uniqid()` for panel IDs (server-generated, not user input). |
| ARIA-label injection via "Show {title} submenu" | Tampering | `esc_attr()` on the composed aria-label string (title inside is already esc_html-safe; whole attr still esc_attr'd). |
| Custom Image brand URL injection | Tampering | `esc_url( $settings['brand_image']['url'] )`. |

## Sources

### Primary (HIGH confidence)
- **Codebase (read this session):** `devsroom-drilldown-mobile-menu.php` (autoloader — confirms `Devsroom_DDMM\Rendering\` → `src/Rendering/`), `src/Plugin.php`, `src/Elementor/Widget/DrillDownMenu.php` (render() stub + control patterns + trigger icon ob_start pattern), `src/MenuBuilder/WpNavTree.php` + `CustomTree.php` (8-field node contract), `assets/css/ddmm-frontend.css` (--ddmm-* skeleton), `assets/js/ddmm-frontend.js` (empty IIFE shell). `[VERIFIED: codebase read]`
- **`.planning/research/ARCHITECTURE.md`** — target HTML output structure (lines 398-449), rendering data flow, Anti-Patterns 2 & 3, build order 4.1-4.5. `[VERIFIED: project doc]`
- **`.planning/research/PITFALLS.md`** — Pitfalls 1, 3, 4, 7 (relevant to rendering); Security Mistakes table (escaping). `[VERIFIED: project doc]`
- **WordPress Developer Resources — `get_custom_logo()`** — returns full `<a><img></a>` linked to home; `has_custom_logo()` boolean; `get_theme_mod('custom_logo')` returns attachment ID. `[CITED: developer.wordpress.org/reference/functions/get_custom_logo]`
- **Elementor JS Hooks (Official Docs)** — `element_ready` action format: `frontend/element_ready/{widget_name}.default` where `{widget_name}` = `get_name()` return value → `ddmm-drilldown-menu`. `[CITED: developers.elementor.com/docs/hooks/js]`
- **Igor Benic — Ultimate Guide for JS in Elementor Widgets** — confirms `element_ready` action uses `get_name()` slug; `$scope` is jQuery-wrapped. `[CITED: ibenic.com/ultimate-guide-for-javascript-in-elementor-widgets]`

### Secondary (MEDIUM confidence)
- **WordPress StackExchange / WP-Kama** — `wp_get_attachment_image_url()` for bare logo URL. `[CITED]`

### Tertiary (LOW confidence — flagged for validation)
- `100dvh` vs `100vh` mobile browser behavior — `[ASSUMED, minor CSS detail]`.
- `jQuery( window ).on( 'elementor/frontend/init' )` acceptability under no-jQuery mandate — `[ASSUMED, see A1]`.

## Metadata

**Confidence breakdown:**
- Standard stack: **HIGH** — all APIs already in use in the project; no new dependencies; verified by codebase read.
- Architecture (recursive renderer + ID threading): **HIGH** — the threading rule is unambiguous once stated; the codebase has no conflicting patterns.
- Elementor integration (controls, is_edit_mode, Icons_Manager): **HIGH** — all patterns already proven in Phases 1-3.
- JS bootstrap (hook string): **HIGH** — verified via official docs + Igor Benic.
- Brand/logo API: **HIGH** for function existence/behavior; **MEDIUM** for the bare-`<img>` vs linked-logo choice (A2).
- CSS layout: **HIGH** — standard off-canvas pattern; Pitfall 3 guidance well-documented.
- ARIA specifics: **HIGH** — D-21..D-24 are explicit; `aria-controls` on chevron confirmed against ARIA spec.

**Research date:** 2026-06-13
**Valid until:** 2026-07-13 (30 days — stable stack, no fast-moving dependencies)

## RESEARCH COMPLETE
