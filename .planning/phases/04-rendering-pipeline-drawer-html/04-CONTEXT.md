# Phase 4: Rendering Pipeline & Drawer HTML - Context

**Gathered:** 2026-06-13
**Status:** Ready for planning

<domain>
## Phase Boundary

The PHP rendering pipeline converts the unified `$tree` (produced by `WpNavTree` or `CustomTree` in Phases 2 & 3) into complete browser-ready drawer HTML — nested panels, `data-target` / `data-panel-id` / `data-back-target` attributes for ID-based navigation, a header area with configurable brand + close button, back-button rows, and correct ARIA markup. This phase delivers the **DOM contract** that Phase 5's JavaScript will consume. It also ships the JS bootstrap skeleton (init paths + guard) and the base layout CSS so the drawer exists off-canvas in the DOM.

**In scope:**
- `src/Rendering/DrawerRenderer.php` — stateless renderer that walks `$tree` → drawer/panel HTML
- Drawer wrapper (`.ddmm-widget`), overlay, off-canvas drawer, header (brand + ✕ close), root panel, recursive child panels, back-button rows
- Full data-attribute contract (`data-ddmm-*` hooks + short nav attrs) for Phase 5
- ARIA markup (A11Y-01/02/03): `<nav aria-label>`, parent chevron `aria-expanded`, `aria-labelledby` panels, `aria-hidden` state
- JS bootstrap only (IIFE, dual-path init, `data-ddmm-init` guard, container hookup) — NO interactions
- Per-instance config via data-* attributes + `--ddmm-*` CSS vars (supersedes literal JSCR-05 `wp_localize_script`)
- Base layout CSS (off-canvas `translateX(-100%)`, overlay, panel stacking) in `assets/css/ddmm-frontend.css`
- Editor-only root-panel preview block (static, not off-canvas)
- New Content Tab controls: Drawer Header (brand source + brand text/image + nav-label text), back-row parent-name toggle

**Out of scope:**
- Drawer open/close, panel slide, back navigation *behavior*, animation system — **Phase 5** (this phase ships the HTML/contract; Phase 5 animates it)
- Optional search box rendering/filtering (EXTR-01/02) — Phase 5
- Full Style Tab customization (STYL-01..06) — Phase 6
- Keyboard interaction, focus management, Tab trap (A11Y-04..08) — Phase 7
- `content_template()` live editor preview (PRES-01) — v2
- Elementor Pro menu-item icon meta, RTL — v2

</domain>

<decisions>
## Implementation Decisions

### Menu Item Rendering
- **D-01:** Parent items (`has_children = true`) render **split**: the label is an `<a href>` linking to the parent's own URL, with a separate `›` chevron `<button aria-expanded="false">` that drills into the child panel. This preserves WordPress parent items that carry a URL (e.g. "Shop") while still enabling drill-down — the standard mobile-menu pattern.
- **D-02:** The `›` drill-down indicator is injected via a **CSS `::after` pseudo-element** on the parent row — no extra DOM nodes per item, fully themeable in the Phase 6 Style Tab.
- **D-03:** Item icons render **before the label for both sources**, taken from the unified node `icon` field (see D-29 for WP-item behavior).
- **D-04:** Leaf items (no children) render as a plain `<a href>` that passes through `target="_blank"` and the WP menu CSS `classes`. Respects "Open in New Tab" (custom items) and theme styling hooks (WP items).

### Drawer Header & Brand
- **D-05:** Brand source is a SELECT control with four options per DRAW-03 — **Site Logo / Custom Image / Custom Text / None**. Default = **Site Logo**: auto-detect via the WP custom logo API; if the site has no custom logo, fall back to the site name as text. Zero-config sensible default.
- **D-06:** Close ✕ button sits in the **header-right** (DRAW-04), rendered via a CSS glyph (no inline image). Brand sits header-left.
- **D-07:** The header row **always renders** — even when brand = None — so the close button is always reachable and the layout stays consistent.
- **D-08:** The brand logo renders as an `<img>` constrained by a **CSS `max-height`** (~40px default), fully themeable via Phase 6. No inline `width`/`height` style attributes in the markup.

### Panel & Back-Row Structure
- **D-09:** **Every panel — including root — gets a `ddmm-panel-{uniqid()}` ID** (satisfies DRAW-11 fully and is multi-instance safe). The root panel ID is generated first and stored so level-1 back buttons can reference it.
- **D-10:** Back navigation uses **`data-back-target` = ancestor panel ID** on each back button — ID-based reverse navigation mirroring `data-target`. Reliable at any depth (consistent with the core value).
- **D-11:** Back-row layout: **← Back button on the left + parent-name title on the same row**, left-aligned.
- **D-12:** The "show parent name in back row" toggle (DRAW-08) defaults to **ON** — the parent name appears as the back-row title out of the box; users can toggle it off.
- **D-13:** *(Carried forward — locked)* Each child panel is rendered **immediately after its parent `</li>`** as a sibling in DOM order (from PROJECT.md / architecture). Not re-asked.

### JS Scope & PHP→JS Bridge
- **D-14:** Phase 4 ships the **JS bootstrap only**: IIFE + `DrillDownMenu` class skeleton, dual-path init (`elementor/frontend/init` event + `DOMContentLoaded` fallback), `data-ddmm-init` double-init guard, and container hookup. **No interactions** — open/close, panel slide, back nav, animation are all Phase 5. This satisfies JSCR-01..04 in Phase 4 as the roadmap maps them.
- **D-15:** PHP→JS config passes via **data-* attributes on each widget container + `--ddmm-*` CSS custom properties** — instance-safe (multiple widgets per page each carry their own settings). This **supersedes the literal JSCR-05 `wp_localize_script` wording**; `wp_localize_script` is reserved for any future truly-global values (e.g. i18n strings), of which none are needed yet.
- **D-16:** The **scope root is the `.ddmm-widget` wrapper** (containing trigger + overlay + drawer). It hosts the `data-ddmm-init` guard; all JS queries are scoped to it (per architecture Anti-Pattern 3 — no globals, instance-scoped).
- **D-17:** Phase 4 ships **HTML + base layout CSS**: drawer off-canvas via `translateX(-100%)`, overlay/panel stacking, `aria-hidden` defaults. Phase 6 adds Style-Tab customization on top.

### Editor Preview
- **D-18:** In Elementor **edit mode**, `render()` outputs the trigger **plus an editor-only preview block** rendering the root panel `<ul>` inline (static, not off-canvas) so the user sees the configured items with icons + chevrons. Sub-panels are omitted from the preview. This block is editor-only — never emitted on the published frontend.
- **D-19:** Empty/unconfigured in the editor **keeps the existing source-aware hint** (Phase 2/3 pattern: "Select a menu" for WP source / "Add menu items" for custom source).
- **D-20:** On the **published frontend**, the full drawer + panels + overlay HTML is **always present in the DOM**, positioned off-canvas (`translateX(-100%)`) with `aria-hidden="true"` — ready for Phase 5 to open. Phase 4 produces the visible drawer structure even though it won't open until Phase 5.

### ARIA & Semantics
- **D-21:** The drawer `<nav>` `aria-label` is a **configurable Content Tab text control**, defaulting to a translatable **"Mobile Menu"** (A11Y-01). **Never** `role="menu"`.
- **D-22:** Each **child panel carries `aria-labelledby`** pointing to its back-row title span, so screen readers announce the parent name when the panel opens. The root panel has no back row and relies on the nav label.
- **D-23:** The parent chevron `<button>` gets **`aria-label="Show [item] submenu"` + `aria-expanded="false"`** (A11Y-02). Phase 5 toggles the label to "Hide …" and `aria-expanded` to `true` when drilled in.
- **D-24:** Initial static state baked into the HTML: **drawer `aria-hidden="true"`**, trigger `aria-expanded="false"` (already from Phase 1). **All sub-panels ship `aria-hidden="true"`**; the root panel is the active one. Phase 5 toggles panel + drawer state as the user drills/opens.

### Data-Attribute Contract
- **D-25:** **Hook attributes are namespaced `data-ddmm-*`**: `data-ddmm-trigger`, `data-ddmm-overlay`, `data-ddmm-drawer`, `data-ddmm-init`. **Navigation attributes are short**: `data-target`, `data-panel-id`, `data-back-target`. Matches the architecture doc exactly.
- **D-26:** Dynamic state is represented via **BEM state classes**: the active root panel has `ddmm-panel--active`; the drawer gains `ddmm-is-open` when opened. JS toggles classes; CSS drives `transform`/`opacity`.
- **D-27:** The widget container has a **unique `id="ddmm-widget-{widget_id}"` + class `ddmm-widget`** (mirrors the existing `ddmm-drawer-{widget_id}` pattern). Enables direct ID lookup + scoped class queries.
- **D-28:** Panel menu structure is **semantic `<ul>/<li>`**. A parent `<li>` holds the split `<a>` + chevron `<button>`; the child panel `<div>` is a sibling inserted **right after the parent `</li>`** (per D-13).

### Icons & Renderer Architecture
- **D-29:** WP-menu item icons: the renderer **outputs the node `icon` field only when non-empty**. Custom-builder items show their picked icon; WP items are text-only by default (their `icon` field is empty). **No fragile WP icon parsing** (CSS classes / Pro meta) in Phase 4 — that can be revisited later.
- **D-30:** Present icons render via **`\Elementor\Icons_Manager::render_icon()` inside a `<span aria-hidden="true">`** before the label. Matches the Phase 1 trigger-icon pattern (with the same `phpcs:ignore … OutputNotEscaped` justification, since `Icons_Manager` output is pre-escaped). Decorative icons are skipped by screen readers.
- **D-31:** Rendering code lives in a **separate class `src/Rendering/DrawerRenderer.php`** (namespace `Devsroom_DDMM\Rendering`). Stateless render entry point taking `($tree, $settings, $widget_id)`. Keeps the widget class focused on Elementor controls; the renderer works with either menu source. Autoloader already maps `Devsroom_DDMM\Rendering\` → `src/Rendering/`.

### Claude's Discretion
- Exact BEM class names beyond the core contract above (element/modifier classes like `.ddmm-menu__item`, `.ddmm-back`, etc.)
- Exact `--ddmm-*` CSS custom property names exposed as Phase 6 theming hooks
- `DrawerRenderer` method signatures and internal helper decomposition (e.g. `render_header()`, `render_panel()`, `render_item()`, `render_back_row()`)
- Base CSS pixel values (drawer width default ~85vw/320px, overlay rgba, transition timing for Phase 5)
- Editor-only preview block styling (`.ddmm-editor-preview`)
- phpcs ignore-comment phrasing for `Icons_Manager` output (follow the existing Phase 1 pattern in `DrillDownMenu.php`)
- Whether to emit a visually-hidden heading inside `<nav>` for extra structure
- Exact label/description text for the new Content Tab controls (nav label, brand source, parent-name toggle)

### Folded Todos
None — no pending todos matched Phase 4 scope.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Architecture & Research
- `.planning/research/ARCHITECTURE.md` — **Primary ref.** Rendering data flow (PHP→HTML), `DrawerRenderer` component responsibility, recommended `src/Rendering/` structure, full target HTML output structure (lines ~398-449), data flow #2 (tree→HTML) and #3 (HTML→JS nav), Anti-Pattern 2 (positional nav) and Anti-Pattern 3 (global JS state), build order 4.1-4.5
- `.planning/research/FEATURES.md` — Feature landscape, drawer/panel requirements
- `.planning/research/STACK.md` — Stack decisions (CSS nesting, custom properties, IIFE JS)
- `.planning/research/PITFALLS.md` — Known prior-development issues (positional panel navigation, JS init crashes) to avoid regressing

### Requirements
- `.planning/REQUIREMENTS.md` — Phase 4 covers: **DRAW-01, DRAW-02, DRAW-03, DRAW-04, DRAW-05, DRAW-06, DRAW-07, DRAW-08, DRAW-09, DRAW-10, DRAW-11, A11Y-01, A11Y-02, A11Y-03, JSCR-01, JSCR-02, JSCR-03, JSCR-04, JSCR-05**

### Project Context
- `.planning/PROJECT.md` — Core value (flawless drill-down at any depth), locked architecture decisions (ID-based nav, uniqid panel IDs, child-panel-after-`</li>` placement, dual-path init, `data-ddmm-init` guard)
- `.planning/phases/01-plugin-foundation-widget-shell/01-CONTEXT.md` — Widget identity (`ddmm-drilldown-menu`), asset handles (`ddmm-frontend`), trigger rendering pattern, **D-15: JS ships as empty IIFE shell** that Phase 4 now fleshes out
- `.planning/phases/02-wordpress-menu-source/02-CONTEXT.md` — D-03 (renderer owns panel IDs), D-04 (node field set), D-05 (empty-state pattern)
- `.planning/phases/03-custom-menu-builder/03-CONTEXT.md` — **D-02: unified node contract → one Phase 4 render path**; the 8-field node incl. `icon`; both sources feed the same `$tree`
- `CLAUDE.md` — Stack, Elementor API usage, escaping conventions, BEM/CSS-custom-property guidance

### Source Contracts (read before implementing)
- `src/Elementor/Widget/DrillDownMenu.php` — **Integration point.** `render()` already outputs the trigger (`aria-controls="ddmm-drawer-{widget_id}"`) and the empty-state block; the `// Phase 4 will render…` comment marks where `DrawerRenderer` plugs in. Existing `_register_controls()` (`section_trigger`, `section_menu`) shows the control pattern to extend with header/brand controls.
- `src/MenuBuilder/WpNavTree.php` — 8-field node contract (`id`, `title`, `url`, `target`, `classes`, `has_children`, `children`, `icon`) that `DrawerRenderer` consumes
- `src/MenuBuilder/CustomTree.php` — Same 8-field contract; `icon` populated from the repeater Icons field
- `assets/css/ddmm-frontend.css` — Existing trigger base styles + `--ddmm-*` skeleton to extend with drawer/panel base layout
- `assets/js/ddmm-frontend.js` — Existing empty IIFE shell (Phase 1 D-15) to fill with the bootstrap (D-14)
- `src/Plugin.php` — PSR-4 autoloader; `Devsroom_DDMM\Rendering\DrawerRenderer` maps to `src/Rendering/DrawerRenderer.php` automatically (no manual require)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `src/Elementor/Widget/DrillDownMenu.php::render()` — Already builds `$tree` from either source and handles the empty state. Phase 4 replaces the `// Phase 4 will render…` stub with a `DrawerRenderer::render( $tree, $settings, $widget_id )` call. The trigger output and `aria-controls` contract are already in place.
- `\Elementor\Icons_Manager::render_icon()` — Used in Phase 1 for trigger icons (with `ob_start()` capture when concatenation is needed and a `phpcs:ignore` justification). Reuse the exact pattern for menu-item icons (D-30).
- `assets/css/ddmm-frontend.css` — Already has trigger base styles and the `--ddmm-*` custom-property skeleton. Drawer/panel/overlay base layout extends this file.
- `assets/js/ddmm-frontend.js` — Empty IIFE shell present; Phase 4 fills the bootstrap (class skeleton + dual-path init + guard), no interactions.

### Established Patterns
- **Escaping:** all dynamic output via `esc_attr()`, `esc_html()`, `esc_url()`. `Icons_Manager::render_icon()` output is pre-escaped → use the existing `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` comment pattern.
- **PSR-4 mapping:** `Devsroom_DDMM\Rendering\DrawerRenderer` → `src/Rendering/DrawerRenderer.php` (create `src/Rendering/` dir).
- **Elementor controls:** `SELECT`, `TEXT`, `URL`, `MEDIA`, `SWITCHER`, `ICONS` with `condition` arrays and `start_controls_section()` / `end_controls_section()` — extend `_register_controls()` with a new "Drawer Header" section.
- **Edit-mode branching:** `\Elementor\Plugin::$instance->editor->is_edit_mode()` already used for the empty-state hint — reuse for the editor preview block (D-18).
- **Stateless builder pattern:** `WpNavTree::build()` / `CustomTree::build()` are static, pure-PHP, zero-Elementor-dependency. `DrawerRenderer` follows the same separation (renderer depends only on the tree data + settings, not on Elementor internals beyond `Icons_Manager`).

### Integration Points
- `DrillDownMenu::render()` — Call `DrawerRenderer::render( $tree, $settings, $this->get_id() )` after the empty-state guard, outputting into the `.ddmm-widget` wrapper that also contains the trigger.
- `DrillDownMenu::_register_controls()` — Add "Drawer Header" section (brand source SELECT + conditional Custom Image MEDIA / Custom Text TEXT + nav-label TEXT) and the back-row parent-name SWITCHER. Possibly a new "Drawer Settings" section.
- `assets/css/ddmm-frontend.css` — Add `.ddmm-widget`, `.ddmm-overlay`, `.ddmm-drawer` (off-canvas), `.ddmm-panel`, `.ddmm-back`, `.ddmm-menu`, editor-preview, and `--ddmm-*` layout vars.
- `assets/js/ddmm-frontend.js` — Bootstrap: scope to `.ddmm-widget`, `data-ddmm-init` guard, dual-path init hooks (no behavior).
- Trigger `aria-controls="ddmm-drawer-{widget_id}"` already points at the drawer — the drawer must emit `id="ddmm-drawer-{widget_id}"`.

</code_context>

<specifics>
## Specific Ideas

- The split parent pattern (D-01) is chosen specifically because WordPress parent menu items frequently carry their own URL — splitting preserves both navigation and drill-down rather than forcing a choice.
- Panel IDs are `uniqid()` for *every* panel including root (D-09) — chosen over a literal `root` ID to stay multi-instance-safe even though full multi-instance (MULTI-01) is v2; the architecture already scopes queries per container.
- The data-attribute config bridge (D-15) deliberately supersedes the literal JSCR-05 `wp_localize_script` because a single localized global cannot hold per-instance settings safely; data-* on each container is instance-correct.
- The editor-only static root-panel preview (D-18) gives visual feedback now, since `content_template()` live preview (PRES-01) is deferred to v2.
- Drawer always in the DOM on the frontend (D-20) even though it can't open until Phase 5 — so Phase 5 only adds behavior, never markup.

</specifics>

<deferred>
## Deferred Ideas

- **Search box rendering/filtering** (EXTR-01, EXTR-02) — Phase 5
- **Animation system** (transition type/duration/easing controls + Slide/Fade/Scale/Slide+Fade) (ANIM-01..04) — Phase 5
- **Drawer open/close + panel slide + back nav behavior** (the JS interactions) — Phase 5 (Phase 4 ships only the contract + bootstrap)
- **Close-on-link-click, close-on-overlay-click, auto-open current path** (EXTR-03..05) — Phase 5
- **Full Style Tab customization** (STYL-01..06) — Phase 6
- **Keyboard nav + focus management + Tab trap** (A11Y-04..08) — Phase 7
- **WooCommerce URL correctness verification** (COMP-03) — Phase 7
- **`.pot` file / translation packaging** (COMP-04) — Phase 7
- **`content_template()` live editor preview** (PRES-01) — v2
- **Elementor Pro menu-item icon meta support** — v2 (WP items stay text-only in Phase 4, D-29)
- **RTL layout support** (RTL-01) — v2

</deferred>

---

*Phase: 04-rendering-pipeline-drawer-html*
*Context gathered: 2026-06-13*
