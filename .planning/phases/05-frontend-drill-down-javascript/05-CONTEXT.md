# Phase 5: Frontend Drill-Down JavaScript - Context

**Gathered:** 2026-06-14
**Status:** Ready for planning

<domain>
## Phase Boundary

Wire all interaction behavior onto the Phase 4 DOM contract. The drawer opens/closes, panels drill in and out with configurable animation, back-navigation works at any depth, and the extra features — optional search, auto-open current path, and close-behavior toggles — function correctly. Phase 5 adds **behavior to existing markup**; the only new markup is the optional search box (and its results container). No new menu-source, rendering, or Style-Tab work.

Covers requirements: **ANIM-01, ANIM-02, ANIM-03, ANIM-04, EXTR-01, EXTR-02, EXTR-03, EXTR-04, EXTR-05**.

**In scope:**
- Trigger click → drawer open; overlay click / ✕ close → drawer close (DRAW-01/02/04 behavior)
- Chevron (`data-target`) → drill into child panel via `[data-panel-id]` lookup; back button (`data-back-target`) → reverse, at any depth (DRAW-06/07/09/10)
- Animation system: animation-type selector + duration slider + easing selector wired to per-instance config; 4 types (Slide/Fade/Scale/Slide+Fade); GPU-only (transform/opacity) (ANIM-01..04)
- Hamburger→X trigger morph (Phase 1 D-10 anticipation) via class toggle
- ARIA state toggling during interaction (drawer/panel `aria-hidden`, chevron `aria-expanded`/`aria-label`, overlay `aria-hidden`) — required so the live drawer is correct for SR users before Phase 7 keyboard/focus work
- Optional search box: new markup + JS filtering (EXTR-01/02)
- Auto-open current page path: URL match → drill + highlight on open (EXTR-03)
- Close-after-link-click + close-on-overlay-click toggles (EXTR-04/05)
- New Content Tab controls: animation type/duration/easing, search toggle + placeholder, auto-open toggle, close-behavior toggles
- Per-instance config via `data-*` + `--ddmm-*` CSS vars (carried from Phase 4 D-15)

**Out of scope:**
- Full keyboard navigation, focus management, Tab trap (A11Y-04..08) — **Phase 7**
- Full Style Tab customization (STYL-01..06) — **Phase 6** (Phase 5 only adds the marker classes + animation-type class hooks Phase 6 will style)
- WooCommerce URL verification (COMP-03) — Phase 7
- `.pot`/translation packaging (COMP-04) — Phase 7
- `content_template()` live editor preview (PRES-01) — v2
- Multiple-widget-instance coordination beyond per-container scoping (MULTI-01) — v2
- Swipe gestures (GEST-01) — v2
- RTL (RTL-01) — v2

</domain>

<decisions>
## Implementation Decisions

### Animation System (ANIM-01..04)
- **D-01:** The chosen animation type governs **panel drill-in/back transitions only**. The **drawer open/close always slides** off-canvas from the left (its identity, matching Packiro) regardless of the selected type. Keeps the off-canvas feel predictable; the type does not apply to the whole-drawer entrance.
- **D-02:** Default animation type = **Slide**. Matches the Packiro reference and the base CSS already shipped in Phase 4 (panels use `translateX`).
- **D-03:** Per-type drill-transition visuals (overlap/cross-fade style, not sequential):
  - **Slide** — current panel translates out-left (`translateX(-100%)`), child translates in from right (`translateX(100%)` → `0`). Back reverses.
  - **Fade** — panels cross-fade in place (opacity only, no horizontal movement).
  - **Scale** — child panel zooms in from ~92% scale + fade (depth/zoom feel).
  - **Slide + Fade** — child slides in from right AND fades in; current slides out-left AND fades out.
- **D-04:** Animation type is selected per-instance via a Content Tab control and applied as a **state class on the `.ddmm-widget` container** (e.g. `ddmm-anim--slide|fade|scale|slidefade`) that switches which `transform`/`opacity` rules the panel state classes trigger. Duration/easing flow through the existing `--ddmm-transition-duration` custom property plus a new `--ddmm-transition-easing` property (Phase 4 D-15 data-*/CSS-var bridge). Exact class/var names at Claude's discretion.
- **D-05:** Panel transition state model: JS toggles panel classes to represent **active (in-place)**, **exited-left** (drilled past), and **off-stage-right** (pending). Exact BEM class names at Claude's discretion (Phase 4 D-26 established that JS toggles classes while CSS drives the transform). The outgoing panel MUST move to an "exited-left" state on drill-in — the Phase 4 base CSS only defined active + off-stage-right, so Phase 5 adds this third state.

### Search (EXTR-01, EXTR-02)
- **D-06:** **Flat results list** filtering model. While the user types, the drill-view is replaced by a single scrollable list of matching links; clearing the query (or Esc) returns to the normal drill view. Chosen because panels are mutually exclusive views, so in-place hiding across panels would be confusing.
- **D-07:** Search box sits in a **sticky bar directly below the header, above the panels area** — always visible while the drawer is open at any depth.
- **D-08:** Results match **all items (parents + leaves)** by title (case-insensitive substring). Each result shows a **breadcrumb trail** (e.g. `Shop › Categories › Shoes`). Clicking a leaf navigates; clicking a parent result **drills into its panel**.
- **D-09:** Search is **off by default (opt-in)** per EXTR-01 ("optional"). A toggle controls whether the search box renders at all; placeholder text is configurable.
- **D-10:** Search box markup is **new in Phase 5** (Phase 4 emitted none). Rendered by the PHP renderer/widget (sticky bar + `<input>` + results container); JS owns the live filtering. Exact markup at Claude's discretion, but it must carry `data-ddmm-*` hook attributes for JS to bind (consistent with the Phase 4 hook convention).
- **D-11:** No-results state shows a translatable "No results" message inside the results container. Exact copy at Claude's discretion.

### Auto-Open Current Page Path (EXTR-03)
- **D-12:** On **manual open** of the drawer, JS matches the current page URL against menu items and **auto-drills down to the panel containing the current item** (root → L1 → L2…) so it is visible and highlighted. The drawer is **never auto-opened on page load**. Users can still navigate back up normally.
- **D-13:** Highlight scope = **current item + ancestor trail**. A marker class is added to the matching item AND each ancestor (mirrors WP `current-menu-item` / `current-menu-ancestor`). Phase 6 Style Tab styles the "Active" state. Exact class names at Claude's discretion, but reuse the WP classes that already passthrough for the WP source (render_item classes passthrough, Phase 4 D-04).
- **D-14:** Matching is **URL-based** so it works for BOTH sources (WP items and custom-builder items); WP's `current-menu-item` class may be used as a hint when present, with URL match as the authoritative fallback. If the current page isn't in the menu, do nothing (open at root).
- **D-15:** Auto-open-current-path is **on by default** (configurable toggle per EXTR-03).

### Close Behaviors (EXTR-04, EXTR-05)
- **D-16:** Close-after-link-click is **on by default**. It closes the drawer on any **actual `<a>` link click** — a leaf OR the split parent's own label `<a>` (Phase 4 D-01 split pattern, which navigates to the parent's URL). The **chevron drill-in does NOT close** (it is not a navigation). Configurable toggle.
- **D-17:** Close-on-overlay-click is **on by default**. Tapping the semi-transparent overlay dismisses the drawer. Configurable toggle. Pairs with the ✕ close button (always available per Phase 4 D-07).
- **D-18:** **New-tab links (`target="_blank"`) leave the drawer open** — the original page didn't navigate, so closing would leave the user staring at a closed menu. Close-after-link-click fires only for same-tab navigations.
- **D-19:** All close actions (link click, overlay, ✕, and later Esc in Phase 7) route through a single internal `close()` path so cleanup (class/aria reset, scroll reset) is consistent.

### Carried Forward — Locked (not re-asked)
- ID-based nav: `data-target` → `[data-panel-id="X"]`; `data-back-target` → ancestor panel (Phase 4 D-25/D-26, DRAW-10).
- CSS drives motion, JS toggles classes: `ddmm-is-open` on `.ddmm-widget`, panel state classes; only `transform`/`opacity` animate (Phase 4 D-26, ANIM-04).
- Per-container scoping to `.ddmm-widget` — no globals (Phase 4 D-16, Anti-Pattern 3).
- Per-instance config via `data-*` + `--ddmm-*` CSS vars (Phase 4 D-15).
- Drawer always in DOM off-canvas; JS toggles `aria-hidden` (Phase 4 D-20/D-24).
- Split parent row: label `<a href>` navigates, `›` chevron `<button data-target>` drills (Phase 4 D-01).
- Hamburger→X via `.ddmm-trigger--active` class toggle on the 3-span CSS trick (Phase 1 D-10).
- Animation control *values* are locked by requirements: type selector (Slide/Fade/Scale/Slide+Fade), duration 100–2000ms default 300ms, easing `ease|ease-in|ease-out|ease-in-out|linear` (ANIM-01/02/03).

### Claude's Discretion
- Exact BEM panel state class names (active / exited-left / off-stage-right) and the animation-type container class names
- Exact `--ddmm-*` custom property names for easing (duration already exists)
- Panel transition timing coordination (overlap/cross-fade mechanics), scroll-to-top-on-drill behavior, search debounce/throttle details and min-char threshold
- Search box exact markup, `data-ddmm-*` hook attribute names, and results-container structure
- Exact "No results" copy and search placeholder default text
- Exact current-item / ancestor marker class names (reusing WP classes where they passthrough)
- URL-match normalization (trailing slashes, query strings, hash handling)
- Internal method decomposition of the `DrillDownMenu` class (open/close/drill/back/search/autopath helpers)
- Whether animation-type controls live in a new "Animation" Content Tab section vs. the existing structure
- Any new PHP markup follows Phase 4 escaping patterns (esc_attr/esc_html/esc_url + the Icons_Manager phpcs:ignore convention)

### Folded Todos
None — no pending todos matched Phase 5 scope.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase 5 DOM Contract (PRIMARY — read first)
- `assets/js/ddmm-frontend.js` — **The file Phase 5 fills.** IIFE `DrillDownMenu` class with `init(container)`, dual-path init (Elementor `frontend/element_ready/ddmm-drilldown-menu.default` + `DOMContentLoaded`), `data-ddmm-init` double-init guard. The `init()` body documents exactly which listeners Phase 5 must wire (trigger / overlay / close / `[data-target]` / `[data-back-target]`) and which classes/arias to toggle.
- `assets/css/ddmm-frontend.css` — Base transitions already in place: `.ddmm-widget.ddmm-is-open` slides the drawer + fades the overlay; `.ddmm-panel` transitions `transform`+`opacity` with `.ddmm-panel--active { translateX(0) }` and default `translateX(100%)`. `--ddmm-transition-duration` (300ms) and layout vars defined. **Phase 5 adds: animation-type class hooks, the "exited-left" panel state, hamburger→X transforms (`.ddmm-trigger--active`), search-box styles.**
- `src/Rendering/DrawerRenderer.php` — **The exact data-attribute + ARIA contract Phase 5 JS consumes:** `data-ddmm-overlay`, `data-ddmm-drawer`, `data-ddmm-close`, `data-target`/`data-panel-id`/`data-back-target`, panel `aria-hidden`/`aria-labelledby`, chevron `aria-expanded`/`aria-controls`/`aria-label`. Also where the new search-box markup plugs in (sticky bar below header, above `.ddmm-panels`).

### Requirements & Project Context
- `.planning/REQUIREMENTS.md` — Phase 5 covers **ANIM-01, ANIM-02, ANIM-03, ANIM-04, EXTR-01, EXTR-02, EXTR-03, EXTR-04, EXTR-05** (see §Animation, §Extra Features)
- `.planning/PROJECT.md` — Core value (flawless drill-down at any depth), locked architecture decisions (ID-based nav, dual-path init, per-container scoping)
- `.planning/ROADMAP.md` — Phase 5 goal + 5 success criteria
- `CLAUDE.md` — Stack (pure ES6 IIFE, no jQuery; CSS nesting + custom properties; classic `wp_enqueue_script`), Elementor control patterns, escaping conventions

### Prior Phase Context (locked decisions carried forward)
- `.planning/phases/04-rendering-pipeline-drawer-html/04-CONTEXT.md` — **Primary dependency.** D-01 split parent, D-15 data-*/CSS-var config bridge (supersedes `wp_localize_script`), D-16 `.ddmm-widget` scope root, D-20/D-24 drawer-always-in-DOM + aria-hidden, D-25/D-26 hook-attribute naming + BEM state classes, D-22/D-23 aria-labelledby + chevron aria-label contract
- `.planning/phases/01-plugin-foundation-widget-shell/01-CONTEXT.md` — D-10 hamburger 3-span trick (Phase 5 animates to X), D-15 JS IIFE shell, trigger `aria-expanded`/`aria-controls`

### Architecture & Research
- `.planning/research/ARCHITECTURE.md` — Data flow #3 (HTML→JS nav), Anti-Pattern 2 (positional nav — avoid) and Anti-Pattern 3 (global JS state — avoid), the `data-target`→`data-panel-id` lookup pattern
- `.planning/research/PITFALLS.md` — Prior JS init crashes (`elementorFrontend.hooks` not ready), positional panel navigation — do not regress
- `.planning/research/FEATURES.md` — Animation + extra-feature requirements landscape
- `.planning/research/STACK.md` — Pure ES6 / CSS-custom-property / no-jQuery rationale

### Integration Points (source files to modify)
- `src/Elementor/Widget/DrillDownMenu.php` — `_register_controls()` gets new Content Tab sections (Animation: type/duration/easing; Search: toggle + placeholder; Drawer Settings: auto-open + close-behavior toggles). `render()` already outputs the `.ddmm-widget` wrapper feeding `DrawerRenderer::render()`.
- `src/Assets/Registrar.php` — Confirm `ddmm-frontend` script/style handles (already registered Phase 1); Phase 5 changes file contents, not registration.

No external specs/ADRs — all requirements are captured in the decisions above.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `DrillDownMenu.init(container)` (ddmm-frontend.js) — the single hook point. Phase 5 adds scoped listeners inside it. The `data-ddmm-init` guard, dual-path init, and per-container scope are all done.
- `onElementReady($scope)` + `onDomReady()` — init plumbing already handles both Elementor-rendered and plain-DOM pages; no new init code needed.
- Phase 4 CSS transition scaffolding (`.ddmm-is-open`, `.ddmm-panel--active`, overlay fade) — Phase 5 extends rather than rewrites.
- `DrawerRenderer::render()` / `render_item()` / `render_back_row()` — emit every hook Phase 5 needs; the search box is the only new emit.

### Established Patterns
- **Hook attributes vs nav attributes** (Phase 4 D-25): `data-ddmm-*` for JS hooks, short `data-target`/`data-panel-id`/`data-back-target` for navigation. New search hooks should follow `data-ddmm-*` (e.g. `data-ddmm-search`, `data-ddmm-search-input`, `data-ddmm-search-results`).
- **State via BEM classes** (Phase 4 D-26): JS toggles classes, CSS drives `transform`/`opacity`. New states (exited-left panel, active animation type, search-active) follow this.
- **Instance scoping** (Phase 4 D-16): every `querySelector` is scoped to the `.ddmm-widget` container; no `document`-wide queries.
- **Config bridge** (Phase 4 D-15): per-instance settings as `data-*` on the container + `--ddmm-*` CSS vars; NOT a global `wp_localize_script`.

### Integration Points
- `ddmm-frontend.js` `init()` — wire all interaction listeners + animation-type class application + search + auto-path + close behaviors, scoped to `container`.
- `ddmm-frontend.css` — add animation-type hooks, exited-left panel state, search-box + results styles, hamburger→X transforms.
- `DrawerRenderer.php` — emit the sticky search bar + results container (conditionally, when the search toggle is on).
- `DrillDownMenu.php` `_register_controls()` — add Animation, Search, and Drawer-Settings (auto-open / close toggles) Content Tab sections.

</code_context>

<specifics>
## Specific Ideas

- The animation type intentionally governs only panel drills (not the drawer entrance) so the off-canvas "slide-in from left" identity — the Packiro reference behavior — stays consistent no matter the chosen type.
- Flat-results-list search was chosen over in-place filtering because the drill-down panel model is mutually exclusive (one panel visible at a time); hiding items across hidden panels would be unintuitive.
- Auto-drill positions to the current page *only when the user opens the drawer* — never auto-opening on page load avoids the intrusive "menu pops open" anti-pattern while still orienting the user once they engage.
- Close-after-link-click deliberately includes the split parent's own label `<a>` (Phase 4 D-01) since that click is a genuine navigation, but excludes the chevron drill (not a navigation) and new-tab links (page didn't change).
- New-tab links leave the drawer open so a user opening several links into new tabs isn't left with a closed menu after the first.

</specifics>

<deferred>
## Deferred Ideas

- **Full keyboard nav + focus management + Tab trap** (A11Y-04..08) — Phase 7. Phase 5 only toggles aria states so the live drawer isn't broken for SR users in the interim.
- **Full Style Tab customization incl. search box + Active/current states** (STYL-01..06) — Phase 6. Phase 5 ships marker classes + animation hooks Phase 6 will theme.
- **WooCommerce URL correctness verification** (COMP-03) — Phase 7
- **`.pot` file / translation packaging** (COMP-04) — Phase 7 (Phase 5 user-facing strings — "No results", search placeholder, "Back"/"Show X submenu" — already use the text domain so they're translation-ready)
- **`content_template()` live editor preview** (PRES-01) — v2
- **Multiple widget instances beyond per-container scoping** (MULTI-01) — v2
- **Swipe gestures** (swipe-left drill-in, swipe-right back) (GEST-01) — v2
- **RTL layout** (RTL-01) — v2

</deferred>

---

*Phase: 05-frontend-drill-down-javascript*
*Context gathered: 2026-06-14*
