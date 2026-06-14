# Phase 7: Accessibility & Compatibility Polish - Context

**Gathered:** 2026-06-14
**Status:** Ready for planning

<domain>
## Phase Boundary

Add the keyboard-interaction, focus-management, screen-reader, and compatibility layer on top of the Phase 4 ARIA markup + Phase 5 JS interactions — and harden WooCommerce URL rendering + package translations. The goal: **fully keyboard-navigable, screen-reader friendly, WooCommerce-compatible, translation-ready, edge-case-resilient.** Phase 7 adds behavior + verification on top of existing markup; the only new markup is the aria-live region(s) and focus infrastructure.

Covers requirements: **A11Y-04, A11Y-05, A11Y-06, A11Y-07, A11Y-08, COMP-03, COMP-04**.

**In scope:**
- Keyboard: Esc (back-one-level then close, A11Y-04), Tab trap (A11Y-05), Arrow ↑/↓ sibling nav via roving tabindex (A11Y-06), Enter/Space activation of parent chevrons / leaf links / back button (A11Y-07)
- Focus management: focus into drawer on open + restore to trigger on close (A11Y-08)
- Screen-reader feedback: polite aria-live for panel context on drill + search result count
- Default `:focus-visible` outline (themeable)
- WooCommerce URL correctness verification (COMP-03) — passive/agnostic
- `.pot` generation + text-domain loading + JS-string translation (COMP-04)
- `prefers-reduced-motion` handling; RTL baseline correctness (no hard-break)

**Out of scope:**
- Cart-count badge / live WC cart content — **deferred** (see Deferred Ideas)
- Full RTL layout (slide direction, chevron mirroring) (RTL-01) — **v2**
- Swipe gestures (GEST-01) — v2
- `content_template()` live editor preview (PRES-01) — v2
- Multiple widget instances beyond per-container scoping (MULTI-01) — v2
- A per-instance "Focus" Style Tab section (theme the focus ring beyond the default) — future polish phase
- New static ARIA markup (already shipped in Phase 4 D-21..24)

</domain>

<decisions>
## Implementation Decisions

### Keyboard Interaction (A11Y-04, A11Y-05, A11Y-06, A11Y-07)
- **D-01:** Dual-mode keyboard — **Tab wraps** every focusable control (close, back, each item's label + chevron, search) at the drawer boundary (the trap, A11Y-05); **Arrow ↑/↓ roam** between sibling menu items in the current panel via roving tabindex (A11Y-06). Two complementary modes. No horizontal-arrow (←/→) drilling.
- **D-02:** **Esc precedence = back one level first**; pressing Esc again at the root panel **closes the drawer** (A11Y-04). The back step routes through the existing `back()` path; the close step routes through the Phase 5 single `close()` path (D-19) so aria/class/scroll cleanup stays consistent.
- **D-03:** **Initial focus on open = the auto-opened current item** if auto-open-current-path matched a page (Phase 5 D-12/13), **else the first focusable item** in the visible panel.
- **D-04:** **Split-parent keyboard model** (Phase 4 D-01) — the label `<a>` and the `›` chevron `<button>` are **separate Tab/arrow stops**. Enter/Space activates whichever has focus: navigate (label `<a>`) or drill (chevron). Mirrors the visual split; no ambiguity.
- **D-05:** After drilling **INTO** a sub-panel (chevron Enter/Space), focus moves to the **first menu item of the new panel**.
- **D-06:** Enter/Space on a leaf `<a>` = navigate (follow link, respects Phase 5 close-after-link-click D-16); on the ← Back button = go back (A11Y-07).

### Focus Management & Screen-Reader (A11Y-08)
- **D-07:** Focus moves into the drawer on open (target = D-03) and is **restored to the trigger button on close** (A11Y-08 / SC#3), regardless of which close method fired (Esc, overlay, ✕, link-click).
- **D-08:** Polite `aria-live` region announces **panel context on drill in/out** — the new panel's back-row parent name (or the nav label for root). Exact region markup + id wiring at Claude's discretion; scoped to the container.
- **D-09:** Polite `aria-live` announces the **search result count** after each filter, including the "No results" empty state (Phase 5 D-11 string). May reuse the D-08 region or be a separate status region.
- **D-10:** Ship a **default `:focus-visible` outline now** (Phase 6 added none) — a visible focus ring on trigger, items, chevrons, close, back, and search input, driven by an existing `--ddmm-*` color var so it's themeable. Use `:focus-visible` (not `:focus`) so mouse clicks don't show a ring. Exact var/width at Claude's discretion; must be visible on light + dark drawer backgrounds.

### Roving Tabindex
- **D-11:** Roving tabindex strategy — menu items in a panel carry `tabindex="-1"` except the "active" one (`tabindex="0"`); ↑/↓ move the `tabindex="0"` among siblings. Close button, back button, search input, and each item's label `<a>` + chevron remain in normal Tab order. Which element holds `tabindex="0"` on open = the D-03 focus target. Exact mechanics at Claude's discretion.

### WooCommerce Compatibility (COMP-03)
- **D-12:** **100% WooCommerce-agnostic.** The plugin NEVER detects or requires WooCommerce (no `class_exists` checks, no `wc_get_*` calls). It renders whatever URLs the WP-assigned menu carries — Cart / My Account / Checkout / Shop links work because WP stored correct permalinks when the menu was built. Works identically whether WC is active or inactive.
- **D-13:** **Dead-link handling = render as-is.** If WC is inactive and a menu item points to a missing shop page, render it exactly as WP stored it. A broken link is a content/site-owner issue, not a plugin issue (consistent with D-12). No hiding, no disabling, no `aria-disabled`.
- **D-14:** **Cart-count badge / live cart content is OUT OF SCOPE** — deferred. COMP-03 is strictly about correct URL rendering + verification.

### Translation & i18n (COMP-04)
- **D-15:** `.pot` generation = **WP-CLI `wp i18n make-pot`** committed to `languages/devsroom-drilldown-mobile-menu.pot`. The `.pot` ships with the plugin so translators have it immediately; the developer regenerates it on release. End users never need WP-CLI. Also run `wp i18n make-json` to produce the JS translation `.json` files for D-16.
- **D-16:** JS-string translation via **`wp_set_script_translations('ddmm-frontend', 'devsroom-drilldown-mobile-menu', $languages_path)`** — the modern WP-native path. Loaded alongside the `ddmm-frontend` script registration. Covers any JS-facing strings (e.g. the "No results" message).
- **D-17:** Text-domain loading — `load_plugin_textdomain('devsroom-drilldown-mobile-menu', false, dirname(plugin_basename(__FILE__)) . '/languages')` wired in `Plugin.php` init; main plugin file header declares `Text Domain: devsroom-drilldown-mobile-menu` and `Domain Path: /languages/`. Exact hook (plugins_loaded/init) at Claude's discretion.

### Edge Cases & Motion
- **D-18:** Honor **`@media (prefers-reduced-motion: reduce)`** — neutralize the drawer slide/fade/scale transitions (instant or near-instant panel swap + drawer entrance) for users who request reduced motion. Unconditional; no per-instance toggle. Exact neutralization (duration → ~0.01ms vs instant) at Claude's discretion.
- **D-19:** **RTL baseline correctness** — use CSS logical properties (e.g. `inset-inline-start`, `margin-inline`, `padding-inline`) and avoid hardcoded left/right assumptions where practical so the layout doesn't visibly BREAK under `dir="rtl"` themes. Full RTL behavior (drawer slides from the right, chevron mirroring, back-arrow direction) remains **v2 (RTL-01)**. No `dir`-aware JS behavior in Phase 7.

### Carried Forward — Locked (not re-asked)
- ARIA markup already shipped (Phase 4 D-21..24): `<nav aria-label>`, chevron `aria-expanded`/`aria-label` (Phase 5 toggles "Show"/"Hide"), panel `aria-labelledby`, drawer + sub-panel `aria-hidden`. Phase 7 toggles these correctly during keyboard interaction — no new static ARIA.
- Single internal `close()` path (Phase 5 D-19) — Esc's close step + focus-restore (D-07) route through it.
- Split parent (Phase 4 D-01) — label `<a href>` navigates, `›` chevron `<button data-target>` drills.
- Auto-open-current-path (Phase 5 D-12/13) — drills + marks current item + ancestors on manual open; Phase 7 focus (D-03) lands on that current item.
- Per-container scoping to `.ddmm-widget` (Phase 4 D-16) — keyboard handlers + aria-live regions are per-container; Esc/Tab/arrows on container A never affect container B.
- ID-based nav (Phase 4 D-25): `data-target` / `data-panel-id` / `data-back-target`.
- All user-facing PHP strings already use the text domain (Phase 4/5 groundwork) — COMP-04 is `.pot` generation + JS-string wiring + verification, **not** a retroactive escaping pass.
- No jQuery, pure ES6 IIFE; CSS nesting + custom properties (CLAUDE.md).

### Claude's Discretion
- Exact roving-tabindex mechanics (D-11); the `:focus-visible` var/width (D-10); the aria-live region markup + id wiring (D-08/D-09); the reduced-motion neutralization threshold (D-18); the `load_plugin_textdomain` hook choice (D-17); internal method decomposition of the keyboard handlers within the `DrillDownMenu` class (keep inside the IIFE per JSCR-02).
- Whether keyboard handlers live as methods on the existing `DrillDownMenu` class (init-scoped listeners) vs a small focused sub-module — stay within the single IIFE.
- Verification strategy for the manual-only behaviors (keyboard flows, SR announcements, WC states, reduced-motion) — likely a `07-HUMAN-UAT.md` like Phase 6's SC#5, since there's no browser automation in this zero-dependency plugin. Grep-based checks can verify wiring (listener presence, tabindex attrs, .pot existence, text-domain loading) but not live behavior.

### Folded Todos
None — no pending todos matched Phase 7 scope.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase 7 Requirements & Goal
- `.planning/ROADMAP.md` §Phase 7 — goal ("fully keyboard-navigable, screen-reader friendly, WooCommerce-compatible, translation-ready, edge-case-resilient") + 5 success criteria (Esc/Tab-trap; Arrow/Enter-Space; focus move/restore; WC URLs both states; text-domain + `.pot`)
- `.planning/REQUIREMENTS.md` — **A11Y-04, A11Y-05, A11Y-06, A11Y-07, A11Y-08** (§Accessibility), **COMP-03, COMP-04** (§Compatibility & i18n)

### Prior Phase Context (locked dependencies — read first)
- `.planning/phases/04-rendering-pipeline-drawer-html/04-CONTEXT.md` — **PRIMARY.** The ARIA/DOM contract Phase 7 keyboard operates on: D-01 split parent (label `<a>` + chevron `<button>`), D-21..24 ARIA markup, D-25 data-attribute nav contract, D-26 BEM state classes (JS toggles classes, CSS drives motion), D-27 container `id="ddmm-widget-{widget_id}"`
- `.planning/phases/05-frontend-drill-down-javascript/05-CONTEXT.md` — the JS behavior layer: **D-19 single `close()` path** (Esc + focus-restore hook in here), D-12/13 auto-open-current-path (D-03 focus target reuses its result), D-06..11 search (D-09 result-count announcement + D-11 "No results" string), D-04 animation-type class hooks. **The existing `keydown` listener at `ddmm-frontend.js:447` (search-input Esc to clear) must coordinate with the new global Esc handler.**
- `.planning/phases/06-style-tab-controls/06-01-SUMMARY.md` + `06-CONTEXT.md` — the `--ddmm-*` theming vars `:focus-visible` (D-10) should reuse; the BEM classes (`ddmm-trigger`, `ddmm-menu__item`, `ddmm-chevron`, `ddmm-close`, `ddmm-back`, `ddmm-search__input`) `:focus-visible` targets

### Source Contracts (files Phase 7 modifies)
- `assets/js/ddmm-frontend.js` — add keyboard handlers (Tab trap, ↑/↓ roving, Esc back-then-close, Enter/Space activation, focus move/restore, aria-live updates) scoped per `.ddmm-widget`; coordinate with the existing search-input Esc listener (line 447)
- `src/Rendering/DrawerRenderer.php` — emit the aria-live region(s) (D-08/D-09), set initial `tabindex` on items for roving (D-11); `render_editor_preview()` may need tabindex neutralization in the editor
- `assets/css/ddmm-frontend.css` — `:focus-visible` outline (D-10), `prefers-reduced-motion` block (D-18), logical-property refactor for RTL baseline (D-19)
- `src/Plugin.php` — `load_plugin_textdomain` (D-17)
- `src/Assets/Registrar.php` (or wherever `ddmm-frontend` is registered/enqueued) — `wp_set_script_translations` (D-16)
- `languages/devsroom-drilldown-mobile-menu.pot` — **NEW**, WP-CLI generated + committed (D-15); plus the JS translation `.json` files (D-15/D-16)
- `devsroom-drilldown-mobile-menu.php` (main plugin file header) — confirm `Text Domain:` + `Domain Path:` declarations

### Architecture & Research
- `.planning/research/ARCHITECTURE.md` — Anti-Pattern 3 (global JS state — keyboard handlers MUST stay per-container, never `document`-wide keydown that crosses instances), data flow #3 (HTML→JS nav)
- `.planning/research/PITFALLS.md` — prior JS init crashes (`elementorFrontend.hooks` not ready) — keyboard listeners attach in the same `init()` flow, don't regress
- `.planning/research/STACK.md` — pure ES6 / no-jQuery / CSS-custom-property rationale
- `CLAUDE.md` — stack, escaping conventions (`esc_attr`/`esc_html`/`esc_url` + `Icons_Manager` phpcs:ignore pattern), BEM, CSS nesting, text domain `devsroom-drilldown-mobile-menu`

No external specs/ADRs — all requirements are captured in the decisions above.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `DrillDownMenu` class (`ddmm-frontend.js`) — the `open()` / `close()` / `drill()` / `back()` methods Phase 5 built. Keyboard handlers call into them: Esc → `back()` then `close()`; Enter on chevron → `drill()`; Enter on back → `back()`. No duplication of nav logic.
- Phase 5 unified `close()` path (D-19) — Esc's close step + focus-restore (D-07) hook into it so aria/class/scroll cleanup stays consistent.
- Phase 5 auto-open-current-path (D-12/13) — already identifies the current item on open; D-03's focus target reuses that result rather than recomputing.
- Phase 4 ARIA markup (D-21..24) — already in the HTML; Phase 7 only toggles `aria-hidden` / `aria-expanded` correctly during keyboard nav.
- The `--ddmm-*` theming vars (Phase 6) — `:focus-visible` (D-10) reuses a color var; reduced-motion + logical-property edits live alongside the existing CSS.

### Established Patterns
- **Per-container scoping** (Phase 4 D-16) — keyboard handlers bind within `init(container)`; a keydown listener for the Tab trap attaches to `document` only while the drawer is open and is scoped to the active container (never affects sibling instances).
- **JS toggles classes/aria, CSS drives motion** (Phase 4 D-26) — focus state via `tabindex`/class, not JS-injected inline styles.
- **Data-attr config bridge** (Phase 4 D-15) — instance settings via `data-*` + `--ddmm-*` vars (no global `wp_localize_script`).
- **Escaping** — any new echo (aria-live default text, visually-hidden helper) via `esc_html__()` / `esc_attr__()`.

### Integration Points
- `ddmm-frontend.js` `init()` — wire the keydown listener (container-scoped + document-level Tab trap while open), focus management on open/close, aria-live updates on drill/search.
- `DrawerRenderer.php` — emit aria-live region(s) + initial `tabindex`; possibly a visually-hidden helper class for SR-only text.
- `ddmm-frontend.css` — `:focus-visible`, `prefers-reduced-motion`, logical properties.
- `Plugin.php` / `Registrar.php` — text-domain + script-translations loading.
- `languages/` — new `.pot` + `.json`.

</code_context>

<specifics>
## Specific Ideas

- The **dual Tab-trap + Arrow-roaming model** is deliberate: Tab gives full manual access to every control (close, back, items, chevrons, search) for users who expect standard tab order, while ↑/↓ give quick item-to-item movement. Most menu libraries pick one mode; the requirements mandate both.
- **Esc-back-then-close** mirrors the ← Back mental model — a keyboard user drills back out the way they came, then dismisses, rather than the drawer vanishing from depth in one keystroke.
- The **WC-agnostic stance** is a deliberate non-feature: the plugin stays out of WooCommerce's business entirely, rendering WP-assigned URLs. This keeps the plugin dependency-free and correct in both WC states *by construction* — no detection logic to maintain or break.
- **`:focus-visible` (not `:focus`)** so mouse-click users don't see a ring but keyboard users always do — standard modern a11y practice.
- **RTL baseline-only** chosen because full RTL (slide direction, chevron mirroring) is a cohesive feature worth its own v2 pass; Phase 7 just ensures the CSS doesn't *break* under `dir="rtl"` via logical properties, so RTL-theme users get a functional (if not mirrored) menu today.

</specifics>

<deferred>
## Deferred Ideas

- **Cart-count badge / live WooCommerce cart content** — would need WC detection + fragments; own future phase or v2. COMP-03 stays URL-only.
- **Full RTL layout (RTL-01)** — drawer slide-from-right under `dir="rtl"`, chevron mirroring, back-arrow direction; v2.
- **Swipe gestures** (swipe-left drill-in, swipe-right back) (GEST-01) — v2.
- **`content_template()` live editor preview** (PRES-01) — v2.
- **Multiple widget instances beyond per-container scoping** (MULTI-01) — v2 (per-container scoping already keeps keyboard handlers isolated).
- **A per-instance "Focus" Style Tab section** — let authors theme the focus ring beyond the D-10 default; a future polish phase if requested.
- **Elementor Pro menu-item icon meta** — v2 (WP items stay text-only per Phase 4 D-29).

### Reviewed Todos (not folded)
None — no todos matched Phase 7.

</deferred>

---

*Phase: 07-accessibility-compatibility-polish*
*Context gathered: 2026-06-14*
