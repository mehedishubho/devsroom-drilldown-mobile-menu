# Phase 7: Accessibility & Compatibility Polish - Research

**Researched:** 2026-06-14
**Domain:** Keyboard a11y / focus management / SR feedback / WC compatibility / i18n / motion & RTL
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Keyboard Interaction (A11Y-04, A11Y-05, A11Y-06, A11Y-07)**
- **D-01:** Dual-mode keyboard — **Tab wraps** every focusable control (close, back, each item's label + chevron, search) at the drawer boundary; **Arrow ↑/↓ roam** between sibling menu items in the current panel via roving tabindex. Two complementary modes. No horizontal-arrow (←/→) drilling.
- **D-02:** **Esc precedence = back one level first**; pressing Esc again at the root panel **closes the drawer**. Back step → existing `back()`; close step → existing `close()`.
- **D-03:** **Initial focus on open = the auto-opened current item** if auto-open-current-path matched a page (Phase 5 D-12/13), **else the first focusable item**.
- **D-04:** **Split-parent keyboard model** — label `<a>` and `›` chevron `<button>` are **separate Tab/arrow stops**. Enter/Space activates whichever has focus.
- **D-05:** After drilling **INTO** a sub-panel (chevron Enter/Space), focus moves to the **first menu item of the new panel**.
- **D-06:** Enter/Space on a leaf `<a>` = navigate (respects Phase 5 D-16 close-after-link); on the ← Back button = go back.

**Focus Management & Screen-Reader (A11Y-08)**
- **D-07:** Focus moves into the drawer on open (target = D-03) and is **restored to the trigger button on close**, regardless of which close method fired.
- **D-08:** Polite `aria-live` region announces **panel context on drill in/out** — the new panel's back-row parent name (or the nav label for root). Exact region markup + id wiring at Claude's discretion; scoped to the container.
- **D-09:** Polite `aria-live` announces the **search result count** after each filter, including the "No results" empty state. May reuse the D-08 region or be a separate status region.
- **D-10:** Ship a **default `:focus-visible` outline now** (Phase 6 added none) — visible focus ring on trigger, items, chevrons, close, back, and search input, driven by an existing `--ddmm-*` color var. Use `:focus-visible` (not `:focus`) so mouse clicks don't show a ring. Must be visible on light + dark drawer backgrounds.

**Roving Tabindex**
- **D-11:** Menu items in a panel carry `tabindex="-1"` except the "active" one (`tabindex="0"`); ↑/↓ move the `tabindex="0"` among siblings. Close button, back button, search input, and each item's label `<a>` + chevron remain in normal Tab order. Which element holds `tabindex="0"` on open = the D-03 focus target. Exact mechanics at Claude's discretion.

**WooCommerce Compatibility (COMP-03)**
- **D-12:** **100% WooCommerce-agnostic.** NEVER detect or require WooCommerce (no `class_exists`, no `wc_get_*`). Renders whatever URLs WP-assigned menu carries. Works identically whether WC is active or inactive.
- **D-13:** **Dead-link handling = render as-is.** If WC is inactive and a menu item points to a missing shop page, render it exactly as WP stored it. No hiding, no disabling, no `aria-disabled`.
- **D-14:** Cart-count badge / live cart content is OUT OF SCOPE — deferred.

**Translation & i18n (COMP-04)**
- **D-15:** `.pot` generation = **WP-CLI `wp i18n make-pot`** committed to `languages/devsroom-drilldown-mobile-menu.pot`. Also run `wp i18n make-json` for JS translation `.json` files.
- **D-16:** JS-string translation via **`wp_set_script_translations('ddmm-frontend', 'devsroom-drilldown-mobile-menu', $languages_path)`** alongside the `ddmm-frontend` script registration.
- **D-17:** `load_plugin_textdomain('devsroom-drilldown-mobile-menu', false, dirname(plugin_basename(__FILE__)) . '/languages')` wired in `Plugin.php`; main plugin file header declares `Text Domain:` and `Domain Path:`. Exact hook at Claude's discretion.

**Edge Cases & Motion**
- **D-18:** Honor **`@media (prefers-reduced-motion: reduce)`** — neutralize drawer slide/fade/scale transitions. Unconditional; no per-instance toggle. Exact neutralization at Claude's discretion.
- **D-19:** **RTL baseline correctness** — use CSS logical properties (`inset-inline-start`, `margin-inline`, `padding-inline`) and avoid hardcoded left/right assumptions where practical. Full RTL behavior is **v2**. No `dir`-aware JS in Phase 7.

### Claude's Discretion
- Exact roving-tabindex mechanics (D-11); `:focus-visible` var/width (D-10); aria-live region markup + id wiring (D-08/D-09); reduced-motion neutralization threshold (D-18); `load_plugin_textdomain` hook choice (D-17); internal method decomposition of the keyboard handlers within the `DrillDownMenu` IIFE.
- Whether keyboard handlers live as methods on the existing `DrillDownMenu` class vs a small focused sub-module — stay within the single IIFE.
- Verification strategy for manual-only behaviors — likely a `07-HUMAN-UAT.md`.

### Deferred Ideas (OUT OF SCOPE)
- Cart-count badge / live WC cart content
- Full RTL layout (RTL-01) — drawer slide-from-right, chevron mirroring
- Swipe gestures (GEST-01)
- `content_template()` live editor preview (PRES-01)
- Multiple widget instances beyond per-container scoping (MULTI-01)
- A per-instance "Focus" Style Tab section
- New static ARIA markup (already shipped in Phase 4 D-21..24)

</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| A11Y-04 | Escape closes drawer or goes back one level | D-02 + the existing `back()` (ddmm-frontend.js:230) and `close()` (ddmm-frontend.js:128) methods. New keydown handler routes Esc to `back()` when `history.length > 0`, else `close()`. Coordinate with the EXISTING search-input Esc listener at line 447 (search has priority — see "Esc Coordination" in Architecture Patterns). |
| A11Y-05 | Tab trap keeps focus inside open drawer | D-01 + WAI-ARIA APG focus-trap pattern. Document-level keydown listener attached on `open()`, detached on `close()`, scoped to the active container. Tab on last focusable → wrap to first; Shift+Tab on first → wrap to last. |
| A11Y-06 | Arrow keys navigate between menu items | D-11 + WAI-ARIA APG roving tabindex algorithm (verified against official source). ↑/↓ move `tabindex="0"` among siblings in the active panel's `.ddmm-menu__item` list. Per-panel state reset on drill/back. |
| A11Y-07 | Enter/Space activates parent items and back button | D-04/D-06. Enter/Space on chevron → existing `drill()` (line 191); on back button → existing `back()`; on leaf `<a>` → native navigation (no JS needed beyond D-16 close-after-link). |
| A11Y-08 | Focus moves to drawer on open, restored to trigger on close | D-03/D-07. `open()` calls `focus()` on D-03 target; `close()` calls `focus()` on the trigger. Hook into existing methods so all close paths restore focus. |
| COMP-03 | WooCommerce menu items render correctly (Cart/Account/Checkout/Shop) | D-12/D-13. Verified: `WpNavTree::build()` reads `$item->url` from `wp_get_nav_menu_items()` — WC endpoints are stored as full URLs in `wp_posts.post_excerpt`/the menu item object. No code change required. Verification = static read of the existing render path + UAT in both WC states. |
| COMP-04 | Translation-ready with text domain + `.pot` file | D-15/D-16/D-17. Net-new: create `languages/`, run WP-CLI, add `Domain Path:` to plugin header, wire `load_plugin_textdomain` + `wp_set_script_translations`, AND fix the one JS string literal (`'No results'`) so it becomes extractable/translatable. |

</phase_requirements>

## Summary

Phase 7 layers accessibility behavior, WooCommerce-compatibility verification, and translation packaging on top of the existing Phase 4 DOM + Phase 5 JS + Phase 6 CSS. The good news: most of the infrastructure already exists. The Phase 4 ARIA markup (D-21..24), the Phase 5 unified `open()`/`close()`/`drill()`/`back()` methods, the per-container `.ddmm-widget` scoping, the existing search-results `aria-live="polite"` region (DrawerRenderer.php:125), and a complete BEM class catalog are all in place. The Phase 7 work is **additive wiring**, not greenfield.

Three findings dominate the planning picture:

1. **The JS `'No results'` string is a raw literal that breaks the standard i18n pipeline.** `wp i18n make-pot` scans for `__()`/`_x()`/`_n()` function calls; a bare `li.textContent = 'No results'` at ddmm-frontend.js:507 will NOT be extracted. The plugin has zero `wp-i18n` script dependency. The planner must choose one of: (a) inject the string from PHP via `wp_add_inline_script` + a small `window.ddmmI18n = {...}` bridge (consistent with the Phase 4 D-15 data-* bridge pattern — RECOMMENDED, zero new dependency), or (b) add `wp-i18n` as a script dependency and rewrite the literal as `wp.i18n.__('No results', 'devsroom-drilldown-mobile-menu')`. Option (a) is cleaner for a one-string surface.

2. **The plugin header is missing `Domain Path:` and there is no `languages/` directory, no `load_plugin_textdomain` call, and no `wp_set_script_translations` anywhere.** All four are net-new. The `.pot` must be regenerated on release; committing it once now satisfies D-15 and gives translators an immediate artifact.

3. **`:focus-visible`, `prefers-reduced-motion`, and CSS logical properties are entirely absent** from ddmm-frontend.css (verified: only `.ddmm-trigger:focus-visible` exists at line 93; zero matches for `prefers-reduced-motion`, `inset-inline`, `margin-inline`, `padding-inline`). D-10, D-18, and D-19 are all net-new CSS blocks.

The dual-mode keyboard model (D-01) is unusual but well-supported by WAI-ARIA: Tab traversal of every control + roving-tabindex arrow movement between siblings are complementary, not conflicting. The key implementation discipline is keeping all handlers per-container (Anti-Pattern 3) and routing Esc/Enter/Space through the existing `back()`/`close()`/`drill()` methods rather than reimplementing navigation logic.

**Primary recommendation:** Implement in three coordinated waves — (1) keyboard + focus + aria-live wiring in `ddmm-frontend.js` + `DrawerRenderer.php`; (2) CSS a11y (`:focus-visible`, reduced-motion, logical properties) in `ddmm-frontend.css`; (3) i18n packaging (languages/ creation, header fix, `load_plugin_textdomain` + `wp_set_script_translations`, fix the JS literal, run WP-CLI). WC compatibility (COMP-03) is a verification-only task — no code change.

## Standard Stack

### Core (already locked — no new dependencies)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Pure ES6 IIFE | N/A | All keyboard handlers, focus trap, roving tabindex, aria-live updates | CLAUDE.md mandates no jQuery, no build step. The existing `DrillDownMenu` class (ddmm-frontend.js:21) is the single home for all new methods. `[VERIFIED: codebase, ddmm-frontend.js]` |
| WordPress i18n APIs | WP 6.5+ | `load_plugin_textdomain()`, `wp_set_script_translations()`, `__()`/`esc_html__()`/`esc_attr__()` | Native WP. No Composer dependency. `[VERIFIED: developer.wordpress.org/reference/functions/wp_set_script_translations]` |
| WP-CLI i18n-command | 2.6+ (current) | `wp i18n make-pot`, `wp i18n make-json` | Official WP tooling. Scans PHP, Blade-PHP, and JS for `__()` family calls. `[VERIFIED: developer.wordpress.org/cli/commands/i18n/make-pot]` |
| CSS native nesting + custom properties | All modern browsers | `:focus-visible`, `@media (prefers-reduced-motion)`, logical properties | CLAUDE.md mandates plain CSS. `[VERIFIED: caniuse.com/css-nesting; CLAUDE.md]` |

### Supporting (already in codebase — reuse, don't add)

| Asset | Location | Purpose |
|-------|----------|---------|
| `DrillDownMenu` class | ddmm-frontend.js:21 | Add `wireKeyboard()`, `moveFocus()`, `trapTab()`, `updateRoving()`, `announce()` methods here |
| `DrawerRenderer::render()` | src/Rendering/DrawerRenderer.php:42 | Emit aria-live region(s) + initial `tabindex` attrs |
| `Plugin::init()` | src/Plugin.php:68 | Add `load_plugin_textdomain` call |
| `Registrar::register()` | src/Assets/Registrar.php:27 | Add `wp_set_script_translations` after the `wp_register_script('ddmm-frontend', ...)` call |
| `.screen-reader-text` class | ddmm-frontend.css:415 | Already shipped (Phase 4 WR-01 fix) — reuse for any visually-hidden SR text |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| JS keyboard handlers as methods on `DrillDownMenu` | Separate `KeyboardHandler` class | D-16/D-78 (CONTEXT.md) allows either. Recommend staying on `DrillDownMenu` for direct method access to `open()`/`close()`/`drill()`/`back()` — fewer indirections, smaller surface. |
| `wp.i18n.__()` in JS for "No results" | PHP-injected i18n bridge via `wp_add_inline_script` | Option (a) avoids adding `wp-i18n` as a script dependency for a single string. Consistent with Phase 4 D-15 data-bridge pattern. RECOMMENDED. |
| Native Tab focus trap (intercept Tab keydown) | `<dialog>` element's built-in trap | `<dialog>` would require refactoring the drawer markup (currently `<div>`). Out of scope — drawer is `<div>` + ARIA. Use manual trap. |
| `aria-activedescendant` for arrow nav | Roving tabindex (D-11) | D-11 LOCKED roving tabindex. APG notes roving tabindex auto-scrolls the focused element into view — a benefit `aria-activedescendant` lacks. Stay with tabindex. |

**Installation:**
```bash
# No new npm/composer packages. WP-CLI is a dev-side tool:
# If WP-CLI is not installed globally, the i18n-command is bundled in the phar.
wp i18n make-pot . languages/devsroom-drilldown-mobile-menu.pot --domain=devsroom-drilldown-mobile-menu --slug=devsroom-drilldown-mobile-menu
```

## Architecture Patterns

### Recommended Project Structure (additions only)

```
assets/js/ddmm-frontend.js           # +wireKeyboard(), +moveFocus(), +trapTab(), +announce()
assets/css/ddmm-frontend.css         # +:focus-visible, +prefers-reduced-motion, +logical props
src/Rendering/DrawerRenderer.php     # +aria-live region(s), +initial tabindex attrs
src/Plugin.php                       # +load_plugin_textdomain()
src/Assets/Registrar.php             # +wp_set_script_translations()
devsroom-drilldown-mobile-menu.php   # +Domain Path: /languages (header)
languages/                           # NEW DIRECTORY
  devsroom-drilldown-mobile-menu.pot # NEW (WP-CLI generated, committed)
  devsroom-drilldown-mobile-menu-ddmm-frontend-{locale}.json  # NEW (make-json output)
```

### Pattern 1: Roving Tabindex (D-11, A11Y-06) — VERIFIED against WAI-ARIA APG

**What:** One element in a composite widget holds `tabindex="0"` (the "active" one — included in tab sequence); all siblings hold `tabindex="-1"` (focusable via `.focus()` but not in tab sequence). Arrow keys move the `tabindex="0"` value among siblings.

**When to use:** Composite widgets with multiple focusable children — menubars, tablists, radiogroups, trees. The drill-down menu's per-panel item list is exactly this case.

**Source:** WAI-ARIA Authoring Practices — Developing a Keyboard Interface, "Managing Focus Within Components Using a Roving tabindex" `[CITED: w3.org/WAI/ARIA/apg/practices/keyboard-interface]`

**Algorithm (verbatim from APG, adapted to DDMM):**
1. On panel activation (open / drill-in / back), set `tabindex="0"` on the D-03 focus target; set `tabindex="-1"` on all OTHER `.ddmm-menu__item` anchors in the active panel.
2. When user presses ↑/↓ within the active panel:
   - Set `tabindex="-1"` on the element that currently has `tabindex="0"`.
   - Set `tabindex="0"` on the next/prev sibling item (wrap-around at panel edges).
   - Call `element.focus()` on the newly-`tabindex="0"` element.
3. On panel exit (drill/back/close), the previously-active item retains `tabindex="0"` so re-entry resumes there.

**What holds `tabindex="0"` on open:** D-03 — the auto-opened current item (the `<a>` inside `.ddmm-current-item`), else the first item's `<a>` in the active panel.

**Critical divergence from APG menu/menubar pattern:** The DDMM drawer is NOT a true ARIA menu — it's navigation (Phase 4 D-21: `<nav aria-label>`, never `role="menu"`). So we deliberately diverge from APG's menubar expectations: (a) all leaf items remain native `<a href>` (not `role="menuitem"`); (b) the label `<a>` and chevron `<button>` are BOTH in the Tab sequence (D-04 split-parent model), not collapsed to one tab stop. This is intentional — Pitfall 4 in PITFALLS.md warns that `role="menu"` on site navigation breaks SR usability.

**Per-panel scope:** Roving tabindex is computed PER ACTIVE PANEL. When drilling into a child panel, the child's items get their own `tabindex="-1"`/`"0"` distribution (independent of the parent panel's state). The `render_panel()` output already emits each panel as an independent `.ddmm-menu` — query within `container.querySelector('.ddmm-panel--active .ddmm-menu')`.

### Pattern 2: Tab Focus Trap (D-01, A11Y-05)

**What:** While the drawer is open, Tab and Shift+Tab cycle focus within the drawer boundary — never escaping to the page behind.

**When to use:** Modal-style overlays (the drawer behaves as a modal when open).

**Source:** Consensus pattern across WAI-ARIA APG (dialog pattern), W3C aria-practices issue #1772, multiple community guides. `[CITED: github.com/w3c/aria-practices/issues/1772]`

**Algorithm:**
1. On `open()`, attach a `keydown` listener on `document` (NOT the container — Tab can otherwise leak).
2. Capture the drawer's focusable elements in DOM order: `close`, `back` (if present), `search input` (if present), each `.ddmm-menu__item`'s `<a>` AND its chevron `<button>` (D-04: both are tab stops).
3. On Tab (no Shift) on the LAST focusable: `e.preventDefault()`, `.focus()` the FIRST.
4. On Shift+Tab on the FIRST focusable: `e.preventDefault()`, `.focus()` the LAST.
5. On `close()`, detach the listener.

**Per-container scope discipline (Anti-Pattern 3):** The document-level listener must check that the event's target is INSIDE the active container before acting. Pattern: store `this.container` on the instance, and in the handler do `if (!this.container.contains(e.target)) return;`. This guarantees the Tab trap on container A never interferes with container B.

**Selector for focusable elements (use exactly this):**
```js
const focusables = this.container.querySelectorAll(
    '[data-ddmm-close], [data-back-target], [data-ddmm-search-input], ' +
    '.ddmm-panel--active .ddmm-menu > li > a, ' +
    '.ddmm-panel--active .ddmm-menu .ddmm-chevron'
);
// Filter to visible (not display:none) — needed because search results live inside container but are hidden until search-active.
```

**Caveat:** `display:none` elements must be filtered out — the search results container (`.ddmm-search__results`) is hidden unless `.ddmm-search-active` is on the container. Use `offsetParent !== null` or `getComputedStyle().visibility !== 'hidden'`.

### Pattern 3: Esc Coordination with Existing Search Listener (D-02 + D-06 search)

**What:** Phase 5 already has a search-input Esc listener at ddmm-frontend.js:447 that clears the search query and blurs the input. The new global Esc handler (back-then-close) must NOT double-fire when the search input has focus.

**Why it matters:** Without coordination, pressing Esc while typing in search would (a) clear the search (existing) AND (b) attempt to navigate back/close (new) — a confusing double action.

**Coordination strategy (RECOMMENDED):**
1. The new global Esc handler is a `document`-level `keydown` listener attached on `open()`.
2. Its FIRST check: `if (e.target.isSameNode(this.container.querySelector('[data-ddmm-search-input]'))) return;` — let the existing listener handle Esc-while-searching.
3. The existing search Esc listener already calls `input.blur()` after clearing. Once blurred, subsequent Esc presses hit the global handler → back-then-close per D-02.

**Source:** Direct read of ddmm-frontend.js:447-453 — the listener calls `input.value = ''`, `clearSearch()`, `input.blur()`. `[VERIFIED: codebase]`

**Alternative (more complex):** Use `e.stopImmediatePropagation()` in the search listener. Not recommended — adds coupling, and the listener order is fragile.

### Pattern 4: Focus Move/Restore (D-03, D-07, A11Y-08)

**What:** On `open()`, move focus to the drawer (target = D-03). On `close()`, restore focus to the trigger button regardless of which close method fired.

**Why regardless of close method:** D-07 + Phase 5 D-19 single-close-path mandate that overlay-click, ✕-click, link-click, and Esc all route through `close()`. Hooking focus-restore into `close()` itself guarantees D-07.

**Implementation:**
- `open()`: after the existing `autoOpenCurrentPath()` call (ddmm-frontend.js:121), compute the D-03 target and call `.focus()`. Reuse the existing `findCurrentPageItem()` (line 308) result.
- `close()`: after the existing cleanup, find the trigger (`container.querySelector('[data-ddmm-trigger], .ddmm-trigger')`) and call `.focus()`.

**Source:** Verified the existing `open()`/`close()` method bodies at ddmm-frontend.js:106 and 128. `[VERIFIED: codebase]`

### Pattern 5: aria-live Regions (D-08, D-09)

**What:** A polite `aria-live` region announces dynamic state changes to screen readers without moving focus.

**D-09 (search result count) is ALREADY wired.** Verified at DrawerRenderer.php:125:
```php
'<ul class="ddmm-search__results" data-ddmm-search-results id="ddmm-search-results-%1$s"
    aria-live="polite" aria-relevant="additions"></ul>'
```
When Phase 5's `filterSearch()` populates/clears this `<ul>`, SR users automatically hear the additions. The "No results" `<li>` is announced too. **No new region needed for D-09.**

**D-08 (panel context on drill) is NEW.** Recommended pattern — emit a dedicated visually-hidden status region inside the drawer, scoped per container:

```php
// In DrawerRenderer::render(), before the <nav> opening:
printf(
    '<div class="ddmm-sr-status screen-reader-text" data-ddmm-sr-status ' .
    'aria-live="polite" aria-atomic="true"></div>',
);
```

Then in JS, on `drill()` and `back()`, set:
```js
const status = this.container.querySelector('[data-ddmm-sr-status]');
if (status) {
    const panel = this.container.querySelector('.ddmm-panel--active');
    const title = panel ? panel.querySelector('.ddmm-back__title') : null;
    const navLabel = this.container.querySelector('.ddmm-nav').getAttribute('aria-label');
    status.textContent = title ? title.textContent : navLabel;
}
```

**Why a separate region (not reusing search `<ul>`):** The search results region announces ADDITIONS (`aria-relevant="additions"`), which is correct for list mutations but wrong for atomic context updates. The panel-context region needs `aria-atomic="true"` so each drill fully replaces the announcement. Two regions, two responsibilities. `[CITED: developer.mozilla.org on aria-live + aria-atomic]`

**Visually-hidden helper:** Already shipped at ddmm-frontend.css:415 (`.screen-reader-text` per WP-core pattern). REUSE it — do NOT add a duplicate.

### Pattern 6: `:focus-visible` Ring (D-10)

**What:** A visible focus outline that appears ONLY for keyboard navigation, never for mouse clicks. Driven by an existing `--ddmm-*` var so it's themeable.

**Why `:focus-visible` not `:focus`:** Modern browsers apply `:focus-visible` only when the user is keyboard-navigating. Mouse-click focus does not match. This is the standard modern a11y pattern. `[VERIFIED: developer.mozilla.org :focus-visible]`

**Source for the var reuse:** Verified the existing CSS at ddmm-frontend.css:93:
```css
.ddmm-trigger:focus-visible {
    outline: 2px solid var(--ddmm-trigger-color);
    outline-offset: 2px;
}
```
This is the EXACT pattern to extend. The `--ddmm-trigger-color` is the existing var. RECOMMENDATION for D-10: introduce a single dedicated `--ddmm-focus-ring-color` (defaulting to `var(--ddmm-trigger-color)` so it's dark-on-light or light-on-dark automatically based on the configured trigger color) + `--ddmm-focus-ring-width` (default `2px`) + `--ddmm-focus-ring-offset` (default `2px`). Apply uniformly:

```css
.ddmm-trigger:focus-visible,
.ddmm-close:focus-visible,
.ddmm-back__button:focus-visible,
.ddmm-chevron:focus-visible,
.ddmm-menu__item > a:focus-visible,
.ddmm-search__input:focus-visible {
    outline: var(--ddmm-focus-ring-width, 2px) solid var(--ddmm-focus-ring-color, var(--ddmm-trigger-color));
    outline-offset: var(--ddmm-focus-ring-offset, 2px);
}
```

**Note on the existing `.ddmm-search__input:focus` (line 555):** Phase 5 used `:focus` (always-visible). Recommend changing to `:focus-visible` for consistency with D-10.

**Visibility on light + dark backgrounds (D-10 requirement):** `--ddmm-trigger-color` defaults to `#1a1a1a` (dark) — visible on the default white drawer. If a user styles a dark drawer with a light trigger color, the focus ring inherits that light color → visible on dark. Automatic by virtue of reusing the existing var.

### Pattern 7: prefers-reduced-motion (D-18)

**What:** A CSS media query that detects the OS-level "reduce motion" preference and neutralizes transitions.

**Source:** MDN `[CITED: developer.mozilla.org :prefers-reduced-motion]` + W3C CSS Remedy issue #11 community consensus `[CITED: github.com/jensimmons/cssremedy/issues/11]`.

**Key insight (from CSS Remedy #11):** Do NOT set `transition-duration: 0` — elements transitioning to `opacity: 0` or off-screen positions would become invisible/unreachable instantly, breaking the drawer. Use a tiny non-zero duration (~1ms) OR keep opacity transitions while removing transforms. The DDMM drawer needs both: drawer slides in (transform) AND panels cross-fade (transform + opacity).

**Recommended neutralization (Claude's Discretion per D-18):**
```css
@media (prefers-reduced-motion: reduce) {
    .ddmm-widget,
    .ddmm-widget *,
    .ddmm-widget *::before,
    .ddmm-widget *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
```
The `0.01ms` value is the MDN-recommended compromise: short enough to feel instant, long enough that `transitionend` events still fire (so Phase 5's `transitionend` cleanup at ddmm-frontend.js:220 still works) and elements don't get stuck invisible. `[VERIFIED: web.dev/learn/css/transitions; developer.mozilla.org prefers-reduced-motion]`

**Scope:** Apply ONCE on `.ddmm-widget` descendants — no per-instance toggle (D-18 unconditional). Use `!important` because the per-animation-type CSS at lines 447-512 has higher specificity than the global rule.

### Pattern 8: RTL Baseline via CSS Logical Properties (D-19)

**What:** Replace physical `left`/`right`/`margin-left`/`padding-left` declarations with logical equivalents so the layout doesn't visibly break under `dir="rtl"`.

**Why baseline-only:** D-19 explicitly excludes full RTL (slide direction, chevron mirroring) — that's RTL-01 (v2). Phase 7 just ensures no breakage.

**Audit of the existing CSS for hardcoded left/right (must be refactored):**
- ddmm-frontend.css:146 — `.ddmm-drawer { left: 0; }` → `inset-inline-start: 0;`
- ddmm-frontend.css:153 — `transform: translateX(-100%);` — KEEP (transform has no logical equivalent; under RTL the drawer would still slide from the left, which D-19 explicitly allows as "v2" territory)
- ddmm-frontend.css:282 — `.ddmm-menu__icon { margin-right: 8px; }` → `margin-inline-end: 8px;`
- ddmm-frontend.css:291 — `.ddmm-chevron { margin-left: auto; }` → `margin-inline-start: auto;`
- ddmm-frontend.css:168 — `.ddmm-header { padding: 0 16px; }` — already symmetric, no change needed
- ddmm-frontend.css:313 — `.ddmm-back { padding: 12px 16px; }` — already symmetric, no change needed

**What NOT to touch:** `transform: translateX(±100%)` for panel sliding and drawer off-canvas positioning. There is no logical-property equivalent, and D-19 explicitly defers RTL slide-direction to v2.

**Browser support for CSS logical properties:** Chrome 87+, Safari 15+, Firefox 66+, all modern mobile browsers. Matches the CLAUDE.md browser-support matrix. `[VERIFIED: caniuse.com/css-logical-props]`

### Anti-Patterns to Avoid

- **Document-wide keydown that crosses instances:** Per Anti-Pattern 3 (ARCHITECTURE.md), the Tab trap listener attaches to `document` BUT must check `this.container.contains(e.target)` before acting. Failing this check = container A's Tab trap interferes with container B.
- **Reimplementing navigation logic in keyboard handlers:** Esc → call existing `back()`/`close()`; Enter on chevron → call existing `drill()`. Never duplicate the panel-state logic.
- **`role="menu"` / `role="menuitem"`:** Pitfall 4 (PITFALLS.md). DDMM is `<nav>` navigation, NOT an ARIA application menu. The roving-tabindex pattern is borrowed from menubar APG, but the actual ARIA roles remain `<nav aria-label>` + `<a>`/`<button>` — no role injection.
- **Setting `tabindex="0"` on every item:** That defeats roving tabindex (D-11). Only ONE item per panel holds `tabindex="0"`; the rest are `tabindex="-1"`.
- **Adding `wp-i18n` as a script dependency for a single string:** Over-engineered. Use the PHP-injected bridge (Pattern 9 below).
- **Forgetting to detach the Tab trap on `close()`:** Memory leak + the trap fires forever after one open. Always detach in `close()`.
- **`outline: none` anywhere:** Removes the only focus indicator some users have. The D-10 work ADDS outlines; never remove.

### Pattern 9: PHP→JS i18n Bridge for the "No results" string (D-16)

**What:** Pass the translated "No results" string from PHP to JS without adding `wp-i18n` as a script dependency.

**Why needed:** `wp i18n make-pot` extracts JS strings by recognizing `__()`/`_x()`/`_n()` function-call patterns. The existing ddmm-frontend.js:507 has `li.textContent = 'No results';` — a raw literal the extractor will NOT find. Two options exist:

**Option (a) — PHP-injected bridge (RECOMMENDED):** In `Registrar::register()`, after `wp_register_script('ddmm-frontend', ...)`:
```php
wp_register_script('ddmm-frontend', ...);
wp_add_inline_script(
    'ddmm-frontend',
    'window.ddmmI18n = ' . wp_json_encode([
        'noResults' => __('No results', 'devsroom-drilldown-mobile-menu'),
    ]) . ';',
    'before'
);
wp_set_script_translations('ddmm-frontend', 'devsroom-drilldown-mobile-menu', $languages_path);
```
Then in JS: `li.textContent = (window.ddmmI18n && window.ddmmI18n.noResults) || 'No results';`. The `__('No results', ...)` in PHP IS picked up by `wp i18n make-pot` because it's a PHP file.

**Option (b) — `wp.i18n` dependency:** Add `'wp-i18n'` to the deps array of `wp_register_script` (currently `[]`), then in JS:
```js
li.textContent = wp.i18n.__('No results', 'devsroom-drilldown-mobile-menu');
```
`wp i18n make-pot` recognizes `wp.i18n.__()` in JS. BUT this adds a script dependency that loads WP's i18n bundle on every page where the widget appears — overkill for ONE string.

**Recommendation:** Option (a). Consistent with Phase 4 D-15 data-bridge philosophy. Zero new runtime dependency. `[CITED: developer.wordpress.org/cli/commands/i18n/make-pot (extraction patterns)]`

**Note on `wp_set_script_translations` + Option (a):** D-16 still wires `wp_set_script_translations` so the standard WP translation pipeline works for FUTURE JS strings. Option (a) and D-16 are complementary, not exclusive — the bridge handles the one current string; D-16 readies the pipeline for future additions. If Option (a) is chosen, the `.json` from `make-json` is technically unused NOW but ships ready for future use.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Focus trap | Manual "track last focused element + intercept all Tab events from scratch" | The 4-step document-level Tab listener pattern (Pattern 2) | Well-documented consensus pattern; manual reimplementations miss edge cases (shadow DOM, iframe escapes, hidden-element filtering). |
| i18n string extraction | Custom regex scanner | WP-CLI `wp i18n make-pot` | Officially maintained, handles PHP + JS, edge cases (comments, domains, plurals). |
| JS string translation | Custom JSON file + fetch + lookup | `wp_set_script_translations` + `wp i18n make-json` | Native WP 5.0+ pipeline; auto-loaded by WP; no manual JSON management. |
| Focus ring styling | JS-driven "add class on focus" | CSS `:focus-visible` pseudo-class | Browser-native, auto-detects keyboard vs mouse. `[VERIFIED: developer.mozilla.org :focus-visible]` |
| Screen-reader-only text | New `.sr-only` class | Existing `.screen-reader-text` (ddmm-frontend.css:415) | Already shipped in Phase 4 WR-01 fix. Matches WP-core pattern. |
| Reduced-motion detection | JS `matchMedia('(prefers-reduced-motion: reduce)')` | CSS `@media (prefers-reduced-motion: reduce)` | Browser-native, no JS, applies before first paint. (JS matchMedia is acceptable ONLY if JS-driven animation needs neutralizing — DDMM uses pure CSS transitions, so CSS-only is sufficient.) |

**Key insight:** Every accessibility/i18n concern in Phase 7 has a mature native or WP-standard solution. The work is wiring, not invention.

## Common Pitfalls

### Pitfall 1: Esc Double-Fire with Search Listener

**What goes wrong:** The existing search-input Esc listener (ddmm-frontend.js:447) clears the search. The new global Esc handler also fires. Result: pressing Esc in search both clears AND navigates back — disorienting.

**Why it happens:** Both listeners attach to different targets (search input vs document). Event bubbles from input → document; both fire.

**How to avoid:** Pattern 3 above — the global Esc handler's first check is "is the search input currently focused?" If yes, return early and let the existing listener handle it. The existing listener calls `input.blur()` after clearing, so the NEXT Esc press correctly routes to back-then-close.

**Warning signs:** During UAT, pressing Esc in search unexpectedly dismisses the drawer.

### Pitfall 2: Tab Trap Leaks After Close

**What goes wrong:** The `document.addEventListener('keydown', trapHandler)` is added on `open()` but never removed on `close()`. After the first open/close cycle, every Tab press on the page is intercepted forever.

**Why it happens:** Forgetting the symmetric `removeEventListener`. The handler reference must be stored on the instance (e.g. `this.tabHandler`) so the exact same function reference can be removed.

**How to avoid:** Store the handler as an instance property; attach in `open()`, detach in `close()`. Use a named function expression, not an anonymous arrow inside `addEventListener`.

**Warning signs:** After opening and closing the drawer once, the rest of the page becomes keyboard-un navigable.

### Pitfall 3: `tabindex` Pollution Across Panels

**What goes wrong:** After drilling from panel A → panel B → back to panel A, items in panel A have stale `tabindex` values from the previous session, leaving zero or two `tabindex="0"` items in the same panel.

**Why it happens:** Roving tabindex state is per-panel; failing to RESET all items in a panel to `tabindex="-1"` before setting the new `tabindex="0"` accumulates drift.

**How to avoid:** On every panel activation (open / drill / back), the FIRST step in `updateRoving(panel)` is: `panel.querySelectorAll('.ddmm-menu__item > a, .ddmm-menu__item .ddmm-chevron').forEach(el => el.tabIndex = -1);`. THEN set the target's `tabIndex = 0`.

**Warning signs:** After several drill/back cycles, Tab stops skip items or land on multiple items simultaneously.

### Pitfall 4: `wp i18n make-pot` Misses the JS "No results" String

**What goes wrong:** The `.pot` file is generated, but `'No results'` (ddmm-frontend.js:507) is absent. Translators never see it; the string ships English-only.

**Why it happens:** `wp i18n make-pot` extracts by pattern-matching `__()`/`_x()`/`_n()` calls in JS. A bare string literal is invisible to the extractor.

**How to avoid:** Pattern 9 — move the string to PHP via `wp_add_inline_script` + `window.ddmmI18n`, OR add `wp-i18n` dep and use `wp.i18n.__()`. Do NOT leave a bare literal in JS.

**Warning signs:** `grep "No results" languages/devsroom-drilldown-mobile-menu.pot` returns nothing.

### Pitfall 5: Missing `Domain Path:` in Plugin Header

**What goes wrong:** WordPress cannot find the `languages/` directory; `load_plugin_textdomain` falls back to `wp-content/languages/plugins/` (the WP.org translation system), where this plugin has no translations. Result: translations in `languages/devsroom-drilldown-mobile-menu.pot` are never loaded.

**Why it happens:** The current plugin header (devsroom-drilldown-mobile-menu.php:3-10) has `Text Domain:` but NOT `Domain Path:`.

**How to avoid:** Add `Domain Path: /languages` to the plugin header alongside `Text Domain:`. WP parses this on activation.

**Warning signs:** Translations are loaded in `wp-content/languages/plugins/` but not from the plugin's own `languages/` directory.

### Pitfall 6: `:focus-visible` on `display:none` Elements

**What goes wrong:** The Tab trap's `querySelectorAll` returns the hidden search results (`.ddmm-search__results`) as a focusable target, then `.focus()` on it silently fails (or worse, the browser scrolls to it).

**Why it happens:** `.ddmm-search__results` is always in the DOM but only visible when `.ddmm-search-active` is on the container. The Tab trap selector must exclude hidden elements.

**How to avoid:** Filter the focusable list: `Array.from(focusables).filter(el => el.offsetParent !== null)`. `offsetParent` is null for `display:none` elements.

**Warning signs:** Pressing Tab through the drawer occasionally lands on an invisible element.

### Pitfall 7: `aria-live` Region Loaded After the Announcement

**What goes wrong:** The D-08 panel-context `<div>` is emitted in `DrawerRenderer::render()` but JS writes to it BEFORE the browser registers it as a live region. Some SRs miss the first announcement.

**Why it happens:** SRs need the live region to be present in the DOM and have `aria-live="polite"` set BEFORE the content changes. If JS writes the content immediately on init, the SR may not announce.

**How to avoid:** Emit the region empty (no initial text). Only write to it on `drill()`/`back()` events (after open). The region is present from page load; announcements fire on user-initiated panel changes — correct timing.

**Warning signs:** NVDA/VoiceOver does not announce the parent name when drilling in.

### Pitfall 8: `prefers-reduced-motion` Breaks `transitionend` Cleanup

**What goes wrong:** Setting `transition-duration: 0ms` for reduced motion causes Phase 5's `transitionend`-based cleanup (ddmm-frontend.js:220) to never fire — the panel scroll reset is skipped.

**Why it happens:** Zero-duration transitions sometimes don't fire `transitionend` reliably across browsers.

**How to avoid:** Use `0.01ms` (Pattern 7), not `0ms`. The tiny duration guarantees `transitionend` still fires. Verified best practice per CSS Remedy issue #11 + MDN.

**Warning signs:** Under reduced motion, outgoing panels retain stale scroll positions after several drill cycles.

## Code Examples

### Example 1: Keyboard Wiring Skeleton (ddmm-frontend.js additions)

```js
// Source: synthesized from WAI-ARIA APG roving tabindex + focus trap patterns
// [CITED: w3.org/WAI/ARIA/apg/practices/keyboard-interface]

// Inside DrillDownMenu.init(), after the existing wireSearch() block:
this.wireKeyboard();
// Store the last-focused trigger so close() can restore focus.
this.lastTrigger = null;

wireKeyboard() {
    // Drawer-scoped keydown for arrows + Enter/Space (per-container, never document-wide).
    const drawer = this.container.querySelector('[data-ddmm-drawer]');
    if (!drawer) return;
    drawer.addEventListener('keydown', (e) => this.onDrawerKeydown(e));
}

onDrawerKeydown(e) {
    // Esc is handled at document level (onDocKeydown) to ensure precedence rules.
    switch (e.key) {
        case 'ArrowDown':
        case 'ArrowUp':
            e.preventDefault();
            this.moveRoving(e.key === 'ArrowDown' ? 1 : -1);
            break;
        case 'Enter':
        case ' ':
            // Native activation handles <a> and <button> already.
            // This branch is only needed if the active element is NOT natively activatable.
            // For DDMM's split-parent model (label <a> + chevron <button>), native handles it.
            break;
    }
}

// Tab trap + Esc — attached to document ONLY while drawer is open.
attachDocListeners() {
    this.docHandler = (e) => this.onDocKeydown(e);
    document.addEventListener('keydown', this.docHandler);
}
detachDocListeners() {
    if (this.docHandler) {
        document.removeEventListener('keydown', this.docHandler);
        this.docHandler = null;
    }
}

onDocKeydown(e) {
    // Scope: only act if the event target is inside THIS container.
    if (!this.container.contains(e.target)) return;

    if (e.key === 'Escape') {
        // Pitfall 1: if search input has focus, let the existing listener handle Esc.
        const searchInput = this.container.querySelector('[data-ddmm-search-input]');
        if (searchInput && document.activeElement === searchInput) return;

        e.preventDefault();
        // D-02: back first, then close.
        if (this.history.length > 0) {
            this.back();
            this.announcePanelContext(); // D-08
        } else {
            this.close(); // routes through the single close path (D-19, D-07)
        }
        return;
    }

    if (e.key === 'Tab') {
        this.trapTab(e);
    }
}

trapTab(e) {
    const focusables = this.getFocusables();
    if (!focusables.length) return;
    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
    }
}

getFocusables() {
    const all = this.container.querySelectorAll(
        '[data-ddmm-close], [data-back-target], [data-ddmm-search-input], ' +
        '.ddmm-panel--active .ddmm-menu > li > a, ' +
        '.ddmm-panel--active .ddmm-menu .ddmm-chevron'
    );
    // Pitfall 6: filter out display:none elements (e.g. hidden search results).
    return Array.from(all).filter((el) => el.offsetParent !== null);
}

moveRoving(direction) {
    const panel = this.container.querySelector('.ddmm-panel--active');
    if (!panel) return;
    const items = Array.from(panel.querySelectorAll('.ddmm-menu > li > a, .ddmm-menu .ddmm-chevron'));
    if (items.length < 2) return;
    const currentIdx = items.findIndex((el) => el.tabIndex === 0);
    const nextIdx = (currentIdx + direction + items.length) % items.length;

    // Pitfall 3: reset ALL items first, then set the new tabindex=0.
    items.forEach((el) => (el.tabIndex = -1));
    items[nextIdx].tabIndex = 0;
    items[nextIdx].focus();
}

// Call from open() after autoOpenCurrentPath():
focusInitialTarget() {
    const panel = this.container.querySelector('.ddmm-panel--active');
    if (!panel) return;
    const items = Array.from(panel.querySelectorAll('.ddmm-menu > li > a, .ddmm-menu .ddmm-chevron'));
    // D-03: prefer the auto-opened current item.
    const current = panel.querySelector('.ddmm-current-item > a');
    const target = current || items[0];
    if (!target) return;
    // Set up roving for the active panel.
    items.forEach((el) => (el.tabIndex = -1));
    target.tabIndex = 0;
    target.focus();
}

// Call from drill() and back() after panel state changes:
announcePanelContext() {
    const status = this.container.querySelector('[data-ddmm-sr-status]');
    if (!status) return;
    const panel = this.container.querySelector('.ddmm-panel--active');
    const title = panel ? panel.querySelector('.ddmm-back__title') : null;
    const nav = this.container.querySelector('.ddmm-nav');
    const navLabel = nav ? nav.getAttribute('aria-label') : '';
    // D-08: child panel announces its back-row parent name; root announces the nav label.
    status.textContent = (title && title.textContent) || navLabel;
}

// Hook into existing methods:
// In open(): after autoOpenCurrentPath() call, add:
//   this.attachDocListeners();
//   this.focusInitialTarget();
//   this.announcePanelContext();
// In close(): before the existing cleanup, add:
//   this.lastTrigger = this.container.querySelector('[data-ddmm-trigger], .ddmm-trigger');
//   ... existing cleanup ...
//   this.detachDocListeners();
//   if (this.lastTrigger) this.lastTrigger.focus();
// In drill(): after the existing panel state changes, add:
//   this.focusInitialTarget(); // moves focus to first item of new panel (D-05)
//   this.announcePanelContext();
// In back(): after the existing panel state changes, add:
//   this.focusInitialTarget();
//   this.announcePanelContext();
```

### Example 2: aria-live Region Emission (DrawerRenderer.php)

```php
// Source: synthesized from WAI-ARIA aria-live patterns + existing .screen-reader-text class
// [CITED: developer.mozilla.org on aria-live]

// In DrawerRenderer::render(), after the search box conditional and BEFORE the <nav> opening:
printf(
    '<div class="screen-reader-text" data-ddmm-sr-status aria-live="polite" aria-atomic="true"></div>'
);
```

### Example 3: Initial tabindex on Item Anchors (DrawerRenderer.php)

The renderer should NOT emit `tabindex` on items at render time (the JS `focusInitialTarget()` sets it on open). Items default to native tab order (`<a href>` and `<button>` are natively focusable). The JS `focusInitialTarget()` is responsible for converting them to the roving `-1`/`0` distribution on open.

**Why not emit `tabindex` in PHP:** The initial state (before JS runs) should remain accessible — if JS fails, items are still in normal tab order. JS converts to roving on init. This is the progressive-enhancement pattern WAI-ARIA APG recommends.

### Example 4: CSS a11y Additions (ddmm-frontend.css)

```css
/* Source: MDN :focus-visible + prefers-reduced-motion + CSS logical properties
   [CITED: developer.mozilla.org :focus-visible; :prefers-reduced-motion; CSS logical properties] */

/* D-10: Default focus ring, themeable via existing --ddmm-* vars.
   Visible on light + dark backgrounds because --ddmm-trigger-color
   defaults to #1a1a1a but inherits whatever the user configured. */
.elementor-widget-ddmm-drilldown-menu {
    --ddmm-focus-ring-color: var(--ddmm-trigger-color);
    --ddmm-focus-ring-width: 2px;
    --ddmm-focus-ring-offset: 2px;
}

.ddmm-trigger:focus-visible,
.ddmm-close:focus-visible,
.ddmm-back__button:focus-visible,
.ddmm-chevron:focus-visible,
.ddmm-menu__item > a:focus-visible,
.ddmm-search__input:focus-visible {
    outline: var(--ddmm-focus-ring-width) solid var(--ddmm-focus-ring-color);
    outline-offset: var(--ddmm-focus-ring-offset);
}

/* D-18: prefers-reduced-motion neutralization.
   0.01ms (not 0ms) so transitionend still fires — Pitfall 8. */
@media (prefers-reduced-motion: reduce) {
    .ddmm-widget,
    .ddmm-widget *,
    .ddmm-widget *::before,
    .ddmm-widget *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

/* D-19: RTL baseline via logical properties.
   Physical left/right → logical so dir="rtl" doesn't visibly break.
   Full RTL (slide direction) remains v2 (RTL-01). */
.ddmm-drawer {
    inset-inline-start: 0;  /* was: left: 0 */
}
.ddmm-menu__icon {
    margin-inline-end: 8px;  /* was: margin-right: 8px */
}
.ddmm-chevron {
    margin-inline-start: auto;  /* was: margin-left: auto */
}
```

### Example 5: i18n Wiring (Plugin.php + Registrar.php + header)

```php
// Source: developer.wordpress.org/reference/functions/load_plugin_textdomain
//        developer.wordpress.org/reference/functions/wp_set_script_translations
// [CITED: developer.wordpress.org]

// devsroom-drilldown-mobile-menu.php header — add Domain Path:
/*
 * Plugin Name: Devsroom DrillDown Mobile Menu
 * ...
 * Text Domain: devsroom-drilldown-mobile-menu
 * Domain Path: /languages          <-- NEW
 * ...
 */

// src/Plugin.php — in init(), add:
public function init(): void {
    // D-17: load translations from the plugin's own languages/ dir.
    load_plugin_textdomain(
        'devsroom-drilldown-mobile-menu',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
        // NOTE: __FILE__ here is Plugin.php (in src/), so dirname gives src/.
        // plugin_basename resolves to 'devsroom-drilldown-mobile-menu/src/Plugin.php'.
        // dirname(...) gives 'devsroom-drilldown-mobile-menu' — append '/languages'.
        // This works because the path is RELATIVE TO wp-content/plugins/.
    );
    // ... existing init code ...
}

// src/Assets/Registrar.php — in register(), after wp_register_script:
public function register(): void {
    add_action('wp_enqueue_scripts', function (): void {
        wp_register_script(/* ... existing ... */);

        // D-16: WP-native JS translation pipeline.
        wp_set_script_translations(
            'ddmm-frontend',
            'devsroom-drilldown-mobile-menu',
            plugin_dir_path(dirname(__DIR__, 2) . '/devsroom-drilldown-mobile-menu.php') . 'languages'
        );

        // Pattern 9 (Option a): PHP-injected bridge for the "No results" string.
        wp_add_inline_script(
            'ddmm-frontend',
            'window.ddmmI18n = ' . wp_json_encode([
                'noResults' => __('No results', 'devsroom-drilldown-mobile-menu'),
            ]) . ';',
            'before'
        );

        wp_register_style(/* ... existing ... */);
    });
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `:focus` for focus rings | `:focus-visible` | ~2018 (Firefox), 2020+ (all major browsers) | Mouse-click users no longer see a focus ring; keyboard users always do. Standard modern a11y. |
| `role="menu"` for site nav | `<nav aria-label>` + native elements | Long-standing WAI-ARIA guidance, reinforced by APG | Site nav is NEVER an ARIA menu. Pitfall 4 in PITFALLS.md. |
| `wp_localize_script` for JS strings | `wp_set_script_translations` + `wp.i18n` | WP 5.0 (2018) | Native pipeline; but for ONE string, `wp_add_inline_script` is the lightweight alternative. |
| Manual `.sr-only` class definitions | WP-core `.screen-reader-text` | Long-standing | Already shipped in DDMM (ddmm-frontend.css:415). |
| `transition-duration: 0` for reduced motion | `transition-duration: 0.01ms` | Community consensus (CSS Remedy #11) | Preserves `transitionend` events. |

**Deprecated/outdated:**
- `wp.i18n.__()` without `wp_set_script_translations`: works only if translation `.json` is in `wp-content/languages/plugins/` (WP.org system). For bundled `.json`, the third param to `wp_set_script_translations` is required.
- `tabindex="1"` (positive values): NEVER use per APG. Causes tab-order chaos. Only `0` and `-1` are correct.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | The drawer's panel list (`.ddmm-menu`) is the correct composite widget scope for roving tabindex (not the entire drawer). | Pattern 1 | If wrong, arrow keys would roam across close/back/search too — undesirable. But D-11 explicitly says "between sibling menu items," so the panel-scope reading is correct. |
| A2 | `transitionend` fires reliably with `0.01ms` duration across all target browsers (Chrome/Safari/Firefox mobile). | Pitfall 8, Pattern 7 | If wrong, scroll-reset cleanup in Phase 5's drill() would be skipped under reduced motion. MDN + community consensus support this. |
| A3 | The search input Esc listener (ddmm-frontend.js:447) and the new global Esc handler can coexist by checking `document.activeElement === searchInput`. | Pattern 3 | If wrong, Esc double-fire. The check is standard JS; should be robust. |

**If this table is otherwise empty:** All other claims in this research were verified or cited against a primary source.

## Open Questions

1. **Exact hook for `load_plugin_textdomain`**
   - What we know: D-17 allows Claude's discretion. WordPress core calls `load_plugin_textdomain` on `init` or `plugins_loaded` interchangeably.
   - What's unclear: The existing `Plugin::init()` is itself wired on `plugins_loaded` (devsroom-drilldown-mobile-menu.php:35). Calling `load_plugin_textdomain` inside `init()` (which fires at `plugins_loaded`) is correct timing-wise.
   - Recommendation: Call it as the first line of `Plugin::init()` — no separate hook needed. `[VERIFIED: developer.wordpress.org/reference/functions/load_plugin_textdomain — "should be called from within the 'init' action"]`

2. **Whether to emit `tabindex` in PHP (DrawerRenderer) or set it in JS only**
   - What we know: Progressive enhancement says leave the initial DOM accessible (native tab order), let JS convert to roving on open.
   - What's unclear: None — APG endorses the JS-driven approach. Recommendation: emit NO `tabindex` in PHP; JS owns roving state.

3. **Whether to commit the generated `.json` (from `make-json`) given there are no current `.po` files**
   - What we know: `make-json` converts `.po` → `.json` per-locale. With no translations yet, `make-json` produces nothing.
   - Recommendation: Commit only the `.pot` for now. The `.json` files are generated on-demand when translators deliver `.po` files. Document the make-json command in the README/CONTRIBUTING so it's reproducible.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| WP-CLI (with i18n-command) | COMP-04 (.pot generation, D-15) | Unknown (not checked; dev-side tool) | 2.6+ (current) | Manual `.pot` authoring (tedious; not recommended) OR run via the bundled phar |
| PHP 8.1+ | All PHP changes | ✓ (CLAUDE.md) | 8.1+ | — |
| WordPress 6.5+ | All WP APIs | ✓ (CLAUDE.md) | 6.5+ | — |
| Modern browser (CSS nesting, `:focus-visible`, `prefers-reduced-motion`, logical properties) | All CSS a11y | ✓ (CLAUDE.md browser matrix) | Chrome 90+/Safari 16.5+/FF 117+ | — |

**Missing dependencies with no fallback:** None blocking. If WP-CLI is not installed, the `.pot` can be hand-authored (the surface is small — ~100 strings) but this is not recommended.

**Missing dependencies with fallback:** WP-CLI — if absent, the plan should include a WP-CLI install step OR a documented manual `.pot` creation step. Verify availability during Wave 0.

## Validation Architecture

> `workflow.nyquist_validation: true` in config.json. Section included.

### Test Framework

| Property | Value |
|----------|-------|
| Framework | None — this plugin has no test framework (no PHPUnit, no Jest). All validation is via static grep + human UAT. |
| Config file | none |
| Quick run command | `grep -rn "<pattern>" assets/ src/` (manual) |
| Full suite command | n/a |

**Implication for the planner:** There is NO automated test infrastructure. Wave 0 does NOT add one (out of scope — that would be its own phase). Instead, validation splits into:
- **Static/grep checks** (verifiable by the plan-checker / verifier) — wiring, attrs, file existence, text-domain loading.
- **Human UAT** (deferred to `07-HUMAN-UAT.md`) — keyboard flows, SR announcements, reduced-motion, WC states.

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| A11Y-04 | Esc routes through back() then close() | grep + human | `grep -n "Escape" assets/js/ddmm-frontend.js` (verify Esc handler present + coordinates with search listener) | ✅ Wave 1 (grep), ❌ Wave 3 (human UAT) |
| A11Y-05 | Tab trap wraps focus within drawer | grep + human | `grep -n "trapTab\|getFocusables\|attachDocListeners" assets/js/ddmm-frontend.js` | ✅ Wave 1, ❌ Wave 3 |
| A11Y-06 | Arrow keys move roving tabindex among siblings | grep + human | `grep -n "moveRoving\|tabIndex" assets/js/ddmm-frontend.js` | ✅ Wave 1, ❌ Wave 3 |
| A11Y-07 | Enter/Space activates chevron/back/leaf | grep + human | `grep -n "Enter\|' '" assets/js/ddmm-frontend.js` (verify keydown handler) — Native `<a>`/`<button>` activation is browser-default, minimal JS needed | ✅ Wave 1, ❌ Wave 3 |
| A11Y-08 | Focus moves to drawer on open, restored on close | grep + human | `grep -n "focusInitialTarget\|lastTrigger\|\.focus()" assets/js/ddmm-frontend.js` | ✅ Wave 1, ❌ Wave 3 |
| COMP-03 | WC menu items render with correct URLs both states | human only | (Static: verify no `class_exists('WooCommerce')` / `wc_get_*` in source) `grep -rn "class_exists\|wc_get\|woocommerce" src/ assets/` must return ZERO matches | ✅ Wave 1 (grep proves WC-agnostic), ❌ Wave 3 (human verifies in both states) |
| COMP-04 | .pot exists, text domain loaded, JS strings translated | grep + cli | `test -f languages/devsroom-drilldown-mobile-menu.pot && grep -q "Domain Path" devsroom-drilldown-mobile-menu.php && grep -q "load_plugin_textdomain" src/Plugin.php && grep -q "wp_set_script_translations" src/Assets/Registrar.php` | ✅ Wave 1 |

### Sampling Rate

- **Per task commit:** `php -l <file>` for PHP files; `grep` spot-checks for wiring
- **Per wave merge:** Full static checklist (see Wave 0 Gaps)
- **Phase gate:** Human UAT (`07-HUMAN-UAT.md`) — full keyboard flow in a browser

### Wave 0 Gaps

- [ ] `languages/` directory — does not exist (must create before `make-pot`)
- [ ] `devsroom-drilldown-mobile-menu.pot` — does not exist (must generate via WP-CLI)
- [ ] `Domain Path:` header line — missing from devsroom-drilldown-mobile-menu.php:3-10
- [ ] `load_plugin_textdomain()` call — missing from src/Plugin.php
- [ ] `wp_set_script_translations()` call — missing from src/Assets/Registrar.php
- [ ] The JS `'No results'` literal at ddmm-frontend.js:507 — must be converted to a translated string (Pattern 9)
- [ ] WP-CLI availability — unverified (check `command -v wp`)

*(No test framework gaps — the plugin deliberately has no automated test suite per project conventions.)*

## Security Domain

> `security_enforcement` is not set in config.json — treat as enabled. Section included.

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | Plugin has no auth surface |
| V3 Session Management | no | Plugin has no session surface |
| V4 Access Control | no | Plugin has no privileged operations |
| V5 Input Validation | yes | Existing: `esc_attr`/`esc_html`/`esc_url` on all PHP output (PLUG-06 complete). Phase 7 additions: any new aria-live default text or SR-only labels via `esc_html__()`/`esc_attr__()`. JS-side: existing DOM-API result rendering (`document.createElement` + `textContent`) — already ASVS V5 compliant. No change needed for keyboard handlers. |
| V6 Cryptography | no | No crypto operations |
| V7 Data Protection | no | No stored user data |
| V12 Files & Resources | no | No file upload |

### Known Threat Patterns for this stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| XSS via aria-live content | Tampering | If the D-08 announcement pulls from `.ddmm-back__title.textContent`, it's safe (textContent is auto-escaped). Never use `innerHTML` for announcements. The existing pattern is correct. |
| XSS via translated strings | Tampering | Translations come from `.po`/`.json` files trusted by the site admin. Standard WP trust model. No additional mitigation needed beyond `esc_html__()` on output. |
| Focus hijacking | Spoofing/Tampering | The Tab trap confines focus to the drawer — a deliberate, user-initiated confinement. Restore-on-close (D-07) ensures the user is never left without focus. APG-compliant. |
| Translation file path traversal | Tampering | `wp_set_script_translations` third param is a hardcoded absolute path derived from `plugin_dir_path(__FILE__)`. No user input reaches it. |

## Sources

### Primary (HIGH confidence)
- Codebase inspection — `assets/js/ddmm-frontend.js`, `src/Rendering/DrawerRenderer.php`, `src/Plugin.php`, `src/Assets/Registrar.php`, `assets/css/ddmm-frontend.css`, `devsroom-drilldown-mobile-menu.php`, `src/MenuBuilder/WpNavTree.php` — direct read of all source files Phase 7 modifies
- `.planning/phases/04|05|06-*-CONTEXT.md` — locked prior-phase contracts
- WAI-ARIA Authoring Practices — Developing a Keyboard Interface — https://www.w3.org/WAI/ARIA/apg/practices/keyboard-interface/ (roving tabindex algorithm verbatim)
- WAI-ARIA Authoring Practices — Menu and Menubar Pattern — https://www.w3.org/WAI/ARIA/apg/patterns/menubar/
- MDN — `prefers-reduced-motion` — https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/At-rules/@media/prefers-reduced-motion
- MDN — `:focus-visible` — https://developer.mozilla.org/en-US/docs/Web/CSS/:focus-visible
- WP-CLI — `wp i18n make-pot` — https://developer.wordpress.org/cli/commands/i18n/make-pot/
- WordPress — `wp_set_script_translations()` — https://developer.wordpress.org/reference/functions/wp_set_script_translations/
- WordPress — `load_plugin_textdomain()` — https://developer.wordpress.org/reference/functions/load_plugin_textdomain/

### Secondary (MEDIUM confidence)
- CSS Remedy Issue #11 — prefers-reduced-motion best practices — https://github.com/jensimmons/cssremedy/issues/11 (0.01ms vs 0ms consensus)
- W3C aria-practices Issue #1772 — Tab/Shift+Tab trap behavior — https://github.com/w3c/aria-practices/issues/1772
- web.dev — Learn CSS: Transitions — https://web.dev/learn/css/transitions
- Can I Use — CSS Nesting — https://caniuse.com/css-nesting
- Can I Use — CSS Logical Properties — https://caniuse.com/css-logical-props

### Tertiary (LOW confidence — marked for validation)
- None. All claims cross-verified against at least one primary source.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all locked by CLAUDE.md / prior phases; no new dependencies
- Architecture (keyboard patterns): HIGH — verified against WAI-ARIA APG primary source
- i18n pipeline: HIGH — verified against developer.wordpress.org + codebase inspection
- Pitfalls: HIGH — each grounded in codebase inspection (line numbers cited) or community consensus (multiple sources)
- WC compatibility (COMP-03): HIGH — verified by reading WpNavTree.php (reads `$item->url` directly; no WC API calls anywhere in source)

**Research date:** 2026-06-14
**Valid until:** 2026-07-14 (30 days — i18n tooling and WAI-ARIA APG are stable; no fast-moving dependencies)
