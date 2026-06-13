# Phase 5: Frontend Drill-Down JavaScript - Research

**Researched:** 2026-06-14
**Domain:** Browser interaction mechanics — animation state machine, search filtering, URL matching, GPU compositing, Elementor frontend lifecycle
**Confidence:** HIGH

## Summary

Phase 5 wires behavior onto the Phase 4 DOM contract. All of the markup (drawer, panels, overlay, header, back rows, chevrons, ARIA) already exists and is off-canvas in the DOM; Phase 5 adds the JavaScript that toggles classes and ARIA state, plus the one new markup piece (the search box). The dominant complexity is the **three-state panel model** (active / exited-left / off-stage-right) combined with **four animation types** driven by a container state class, and the coordination rules that prevent flicker when outgoing and incoming panels transition simultaneously.

The existing JS bootstrap (`assets/js/ddmm-frontend.js`) already handles dual-path init (`elementor/frontend/init` + `DOMContentLoaded`), the `data-ddmm-init` double-init guard, and per-container scoping. Phase 5 fills the `init(container)` body with scoped listeners. The CSS already defines the active + off-stage-right panel states, the `ddmm-is-open` drawer state, and `--ddmm-transition-duration`; Phase 5 adds the exited-left state, the four animation-type class hooks, hamburger→X transforms, the easing custom property, and search-box styles.

**Primary recommendation:** Implement the animation system as a **container-class switch** (`ddmm-anim--slide|fade|scale|slidefade`) that selects which transform/opacity values the three panel-state classes (`ddmm-panel--active`, `ddmm-panel--exited-left`, default off-stage-right) resolve to. This keeps ALL motion in CSS (GPU-composited per ANIM-04), JS only toggles classes. Coordinate outgoing+incoming via a shared transition timing — no per-panel JS timers — and reset scroll on the outgoing panel after transitionend.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Animation System (ANIM-01..04)**
- **D-01:** The chosen animation type governs **panel drill-in/back transitions only**. The **drawer open/close always slides** off-canvas from the left (its identity, matching Packiro) regardless of the selected type.
- **D-02:** Default animation type = **Slide**. Matches the Packiro reference and the base CSS already shipped in Phase 4.
- **D-03:** Per-type drill-transition visuals (overlap/cross-fade style, not sequential):
  - **Slide** — current panel translates out-left (`translateX(-100%)`), child translates in from right (`translateX(100%)` → `0`). Back reverses.
  - **Fade** — panels cross-fade in place (opacity only, no horizontal movement).
  - **Scale** — child panel zooms in from ~92% scale + fade (depth/zoom feel).
  - **Slide + Fade** — child slides in from right AND fades in; current slides out-left AND fades out.
- **D-04:** Animation type is applied as a **state class on the `.ddmm-widget` container** (e.g. `ddmm-anim--slide|fade|scale|slidefade`); duration/easing flow through `--ddmm-transition-duration` + a new `--ddmm-transition-easing` custom property.
- **D-05:** Panel transition state model: JS toggles panel classes for **active**, **exited-left** (drilled past), and **off-stage-right** (pending). The outgoing panel MUST move to "exited-left" on drill-in — Phase 4 base CSS only defined active + off-stage-right, so Phase 5 adds this third state.

**Search (EXTR-01, EXTR-02)**
- **D-06:** **Flat results list** filtering model. While the user types, the drill-view is replaced by a single scrollable list of matching links; clearing the query (or Esc) returns to the normal drill view.
- **D-07:** Search box sits in a **sticky bar directly below the header, above the panels area** — always visible while the drawer is open at any depth.
- **D-08:** Results match **all items (parents + leaves)** by title (case-insensitive substring). Each result shows a **breadcrumb trail** (e.g. `Shop › Categories › Shoes`). Clicking a leaf navigates; clicking a parent result **drills into its panel**.
- **D-09:** Search is **off by default (opt-in)** per EXTR-01.
- **D-10:** Search box markup is **new in Phase 5** (Phase 4 emitted none). Rendered by the PHP renderer (sticky bar + `<input>` + results container); JS owns live filtering. Must carry `data-ddmm-*` hook attributes.
- **D-11:** No-results state shows a translatable "No results" message.

**Auto-Open Current Page Path (EXTR-03)**
- **D-12:** On **manual open** of the drawer, JS matches the current page URL against menu items and **auto-drills down to the panel containing the current item**. The drawer is **never auto-opened on page load**.
- **D-13:** Highlight scope = **current item + ancestor trail**. A marker class is added to the matching item AND each ancestor (mirrors WP `current-menu-item` / `current-menu-ancestor`).
- **D-14:** Matching is **URL-based**; WP's `current-menu-item` class may be used as a hint when present, with URL match as the authoritative fallback. If the current page isn't in the menu, do nothing (open at root).
- **D-15:** Auto-open-current-path is **on by default**.

**Close Behaviors (EXTR-04, EXTR-05)**
- **D-16:** Close-after-link-click is **on by default**. It closes the drawer on any **actual `<a>` link click** — a leaf OR the split parent's own label `<a>`. The **chevron drill-in does NOT close**. Configurable toggle.
- **D-17:** Close-on-overlay-click is **on by default**. Pairs with the ✕ close button (always available).
- **D-18:** **New-tab links (`target="_blank"`) leave the drawer open** — close-after-link-click fires only for same-tab navigations.
- **D-19:** All close actions (link click, overlay, ✕, and later Esc in Phase 7) route through a single internal `close()` path for consistent cleanup.

**Carried Forward — Locked**
- ID-based nav: `data-target` → `[data-panel-id="X"]`; `data-back-target` → ancestor panel.
- CSS drives motion, JS toggles classes: `ddmm-is-open` on `.ddmm-widget`, panel state classes; only `transform`/`opacity` animate.
- Per-container scoping to `.ddmm-widget` — no globals.
- Per-instance config via `data-*` + `--ddmm-*` CSS vars.
- Drawer always in DOM off-canvas; JS toggles `aria-hidden`.
- Split parent row: label `<a href>` navigates, `›` chevron `<button data-target>` drills.
- Hamburger→X via `.ddmm-trigger--active` class toggle on the 3-span CSS trick.
- Animation control values locked: type (Slide/Fade/Scale/Slide+Fade), duration 100–2000ms default 300ms, easing `ease|ease-in|ease-out|ease-in-out|linear`.

### Claude's Discretion
- Exact BEM panel state class names (active / exited-left / off-stage-right) and animation-type container class names
- Exact `--ddmm-*` custom property names for easing (duration already exists)
- Panel transition timing coordination, scroll-to-top-on-drill behavior, search debounce/throttle details and min-char threshold
- Search box exact markup, `data-ddmm-*` hook attribute names, results-container structure
- Exact "No results" copy and search placeholder default text
- Exact current-item / ancestor marker class names (reusing WP classes where they passthrough)
- URL-match normalization (trailing slashes, query strings, hash handling)
- Internal method decomposition of the `DrillDownMenu` class
- Whether animation-type controls live in a new "Animation" Content Tab section vs. the existing structure

### Deferred Ideas (OUT OF SCOPE)
- Full keyboard nav + focus management + Tab trap (A11Y-04..08) — Phase 7
- Full Style Tab customization incl. search box + Active/current states (STYL-01..06) — Phase 6
- WooCommerce URL correctness verification (COMP-03) — Phase 7
- `.pot` translation packaging (COMP-04) — Phase 7
- `content_template()` live editor preview (PRES-01) — v2
- Multiple widget instances beyond per-container scoping (MULTI-01) — v2
- Swipe gestures (GEST-01) — v2
- RTL (RTL-01) — v2
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| ANIM-01 | Transition Type selector: Slide \| Fade \| Scale \| Slide + Fade | §Animation State Machine — four container classes `ddmm-anim--{type}` each redefine the transform/opacity values of the three panel states; PHP renders the active class from the Elementor SELECT control. |
| ANIM-02 | Duration slider 100ms–2000ms, default 300ms | §Config Bridge — `--ddmm-transition-duration` already shipped at 300ms; PHP overrides it from the SLIDER control via inline `style` on `.ddmm-widget`. |
| ANIM-03 | Easing selector: ease \| ease-in \| ease-out \| ease-in-out \| linear | §Config Bridge — new `--ddmm-transition-easing` custom property; consumed by every panel/drawer transition declaration. |
| ANIM-04 | All animations use CSS `transform` + `opacity` only — GPU-composited | §GPU-Compositing Pitfalls — the three-state model uses ONLY `translateX`/`scale` + `opacity`; no layout properties animate. `will-change` applied to panels. |
| EXTR-01 | Optional search box in drawer with configurable placeholder text | §Search Box Markup — `DrawerRenderer` emits the sticky bar + input conditionally when the search toggle is on; placeholder is a TEXT control. |
| EXTR-02 | Search filters menu items across all panels | §Search Filtering — flat-results-list model; JS builds a flat index of all items with breadcrumb trails on init, filters by case-insensitive substring on input. |
| EXTR-03 | Auto-open current page path — menu drills down to highlight current page item | §URL-Match Normalization — on `open()`, run URL normalization + match; if hit, walk ancestor panel IDs and apply active classes top-down without animation, then mark item + ancestors. |
| EXTR-04 | Close menu after link click (configurable toggle) | §Close Behaviors — single delegated click listener on the panels container checks `event.target.closest('a')`, excludes new-tab links, routes through `close()`. |
| EXTR-05 | Close on overlay click (configurable toggle) | §Close Behaviors — overlay click listener gated by the toggle data-attribute, routes through `close()`. |
</phase_requirements>

## Project Constraints (from CLAUDE.md)

These directives are binding on Phase 5 implementation:

| Directive | Source | Phase 5 Implication |
|-----------|--------|---------------------|
| **No jQuery** — all JS pure ES6 | CLAUDE.md Constraints | All listeners use `addEventListener`, `querySelector`, `classList`, `dataset`, `closest`. The existing bootstrap already subscribes to Elementor's jQuery event bus for `elementor/frontend/init` — that is Elementor's OWN event system, not jQuery DOM manipulation, and is the standard pattern. |
| **IIFE-wrapped** — no global namespace pollution | CLAUDE.md / JSCR-02 | The existing `( function() { 'use strict'; ... } )();` shell is preserved. Phase 5 adds methods to the `DrillDownMenu` class inside it. |
| **CSS native nesting + custom properties** | CLAUDE.md Stack | New CSS (animation-type hooks, exited-left state, search box, hamburger→X) uses native nesting + `--ddmm-*` vars. No SCSS. |
| **Classic `wp_enqueue_script`** | CLAUDE.md (Key Decision #3) | No change to asset registration. The `ddmm-frontend` handle stays; only file contents change. |
| **Per-instance config via `data-*` + `--ddmm-*`** | Phase 4 D-15 | All Phase 5 toggles (animation type, close-after-link, close-on-overlay, auto-open, search on/off, placeholder) flow from Elementor settings → `data-*` on `.ddmm-widget` + CSS vars. NO `wp_localize_script`. |
| **PSR-4 autoloading** | CLAUDE.md Constraints | The new search-box render method goes in the existing `DrawerRenderer` class (`Devsroom_DDMM\Rendering\`). No new class directory needed. |
| **Escaping** — `esc_attr`/`esc_url`/`esc_html` + Icons_Manager phpcs:ignore | CLAUDE.md / PLUG-06 | New PHP markup (search box, animation `style` attr, `data-*` config attrs) follows the existing escaping patterns verbatim. |
| **WordPress 6.5+, PHP 8.1+, Elementor 3.29+** | CLAUDE.md Constraints | No version-specific APIs used. All features work on the baseline. |

## Standard Stack

### Core (already in place — Phase 5 adds behavior, not dependencies)

| Technology | Version | Purpose | Why Standard |
|------------|---------|---------|--------------|
| Vanilla ES6+ | N/A (no build) | All interaction logic | CLAUDE.md mandates. All target mobile browsers support every API used: `classList`, `dataset`, `closest`, `matches`, `addEventListener`, `URL`, `URLSearchParams`, `CustomEvent`, `setTimeout`, `requestAnimationFrame`. [VERIFIED: MDN browser compat] |
| IIFE pattern | N/A | Scope isolation | Existing shell preserved. [VERIFIED: codebase — `assets/js/ddmm-frontend.js` lines 12-120] |
| CSS Transitions + Transforms | All modern browsers | All animation | `transform: translateX()/scale()` + `opacity` only — GPU-composited per ANIM-04. [CITED: developers.google.com/web/fundamentals/performance/rendering] |
| CSS Custom Properties | All modern browsers | Config bridge + theming hooks | `--ddmm-*` consumed by transitions. [VERIFIED: codebase — `ddmm-frontend.css` lines 8-34] |

### Native browser APIs Phase 5 relies on (no library needed)

| API | Purpose | Why Native |
|-----|---------|------------|
| `URL` (WHATWG) | URL normalization for auto-open match | Built into all target browsers. Handles parsing, pathname, host, query, hash without a library. [CITED: url.spec.whatwg.org, MDN URL] |
| `URLSearchParams` | Query-string access during normalization | Pairs with `URL`. [CITED: MDN URLSearchParams] |
| `classList.add/remove/toggle/contains` | All state-class manipulation | Direct, no jQuery. [VERIFIED: caniuse classList] |
| `element.closest()` | Delegated click handling — find ancestor `<a>` / `[data-target]` | Handles event delegation cleanly. [VERIFIED: caniuse closest] |
| `transitionend` event | Post-transition cleanup (scroll reset, `will-change` removal) | Fires once per property; filter on `propertyName === 'transform'`. [CITED: MDN transitionend] |
| `requestAnimationFrame` | Batching class changes that must paint | Ensures the browser commits the "before" state before the "after" class is applied (prevents skipped-transition flicker). [CITED: MDN requestAnimationFrame] |
| `setTimeout` | Search debounce | Standard debounce mechanism. [VERIFIED: codebase pattern] |

### Alternatives Considered (and rejected per locked decisions)

| Instead of | Could Use | Tradeoff | Decision |
|------------|-----------|----------|----------|
| CSS class-toggle animation | GSAP / Web Animations API / Motion One | Library adds weight; WAAPI is heavier than needed; CSS transitions handle all 4 types at 60fps | CSS only (ANIM-04 mandate) |
| Debounced `input` filtering | throttle / rAF-loop | Throttle fires at fixed rate regardless of typing; debounce fires after pause — better UX for search | Debounce (D-06 flat-list model) |
| `wp_localize_script` config | `data-*` + CSS vars | Per-instance correctness (D-15) | `data-*` + CSS vars |

## Architecture Patterns

### Recommended JS Structure (inside the existing IIFE)

The `DrillDownMenu` class gains private state + handler methods. No new files, no globals, no subclasses — a single class per container, methods scoped to `this.container`.

```
DrillDownMenu class
├── init(container)              // existing — now wires all listeners
├── this.container               // .ddmm-widget root (scoped queries)
├── this.config                  // parsed data-* attrs (anim type, toggles)
├── this.history                 // array of panel IDs for back-nav
├── this.searchIndex             // flat array of {title, url, target, breadcrumb, isParent}
├── open()                       // D-12: auto-drill on open
├── close()                      // D-19: single cleanup path
├── drill(targetPanelId)         // D-05: active→exited-left, child→active
├── back()                       // reverse of drill
├── applyAnimationType(type)     // sets ddmm-anim--{type} on container
├── buildSearchIndex()           // walks DOM once on init
├── handleSearchInput(query)     // debounced filter → render results
├── findCurrentPageItem()        // URL normalization + match
├── autoOpenCurrentPath()        // walks ancestors, applies marker classes
├── closeOnLinkClick(event)      // delegated; checks target=_blank
└── toggleAria*(el, state)       // aria-hidden / aria-expanded helpers
```

### Pattern 1: Container-Class Animation Switch (THE core pattern)

**What:** A single class on `.ddmm-widget` selects which transform/opacity values the three panel states resolve to. JS never sets inline transform styles — it only toggles panel state classes and (once, on init) the animation-type class.

**When to use:** Always — this is the only animation mechanism in Phase 5.

**Why this beats inline-style animation:** Inline `style.transform` set from JS forces a style recalc on the main thread and fights the CSS transition. Class-toggle lets the browser's style system resolve the final value and run the transition on the compositor thread. [CITED: developers.google.com — "Use CSS animations / transitions; avoid animating with JavaScript"]

**Example (the full CSS contract):**

```css
/* Source: synthesized from Phase 4 D-26 + Phase 5 D-03/D-04/D-05.
   Container class selects transform values; panel state classes are stable. */

.ddmm-widget {
    /* Phase 5 adds the easing var; duration already exists from Phase 4. */
    --ddmm-transition-easing: ease;
}

.ddmm-panel {
    position: absolute;
    inset: 0;
    transition: transform var(--ddmm-transition-duration) var(--ddmm-transition-easing),
                opacity   var(--ddmm-transition-duration) var(--ddmm-transition-easing);
    will-change: transform, opacity;
    /* DEFAULT state = off-stage-right (Phase 4 baseline) */
    transform: translateX(100%);
    opacity: 0;
}

/* Active panel — in place, visible */
.ddmm-panel.ddmm-panel--active {
    transform: translateX(0);
    opacity: 1;
}

/* Exited-left — drilled past (Phase 5 NEW third state) */
.ddmm-panel.ddmm-panel--exited-left {
    transform: translateX(-100%);
    opacity: 1;  /* Slide: still visible while sliding out */
}

/* ============================================================
   Animation Type: SLIDE (default)
   Outgoing → exited-left (translateX(-100%), opacity 1)
   Incoming ← off-stage-right → active (translateX(0))
   ============================================================ */
.ddmm-widget.ddmm-anim--slide .ddmm-panel.ddmm-panel--exited-left {
    transform: translateX(-100%);
    opacity: 1;
}

/* ============================================================
   Animation Type: FADE — cross-fade in place, no horizontal move
   Outgoing → opacity 0 (stays at translateX(0))
   Incoming ← opacity 1 (from opacity 0 at translateX(0))
   ============================================================ */
.ddmm-widget.ddmm-anim--fade .ddmm-panel {
    transform: translateX(0);       /* override off-stage-right default */
}
.ddmm-widget.ddmm-anim--fade .ddmm-panel:not(.ddmm-panel--active) {
    opacity: 0;                      /* hidden when not active */
    transform: translateX(0);
    pointer-events: none;
}
.ddmm-widget.ddmm-anim--fade .ddmm-panel.ddmm-panel--exited-left {
    transform: translateX(0);
    opacity: 0;
}

/* ============================================================
   Animation Type: SCALE — child zooms in from 92%
   Outgoing → opacity 0, scale 0.96 (recedes)
   Incoming ← scale 0.92 + opacity 0 → scale 1 + opacity 1
   ============================================================ */
.ddmm-widget.ddmm-anim--scale .ddmm-panel {
    transform: translateX(0) scale(0.92);  /* off-stage = scaled down, in place */
    opacity: 0;
}
.ddmm-widget.ddmm-anim--scale .ddmm-panel.ddmm-panel--active {
    transform: translateX(0) scale(1);
    opacity: 1;
}
.ddmm-widget.ddmm-anim--scale .ddmm-panel.ddmm-panel--exited-left {
    transform: translateX(0) scale(0.96);
    opacity: 0;
}

/* ============================================================
   Animation Type: SLIDE + FADE — slide AND fade together
   Outgoing → translateX(-100%) + opacity 0
   Incoming ← translateX(100%) + opacity 0 → translateX(0) + opacity 1
   ============================================================ */
.ddmm-widget.ddmm-anim--slidefade .ddmm-panel.ddmm-panel--exited-left {
    transform: translateX(-100%);
    opacity: 0;                      /* fades out as it exits */
}
.ddmm-widget.ddmm-anim--slidefade .ddmm-panel:not(.ddmm-panel--active):not(.ddmm-panel--exited-left) {
    opacity: 0;                      /* incoming starts invisible */
}
```

### Pattern 2: Drill/Back Class Coordination

**What:** On drill, JS removes `ddmm-panel--active` from the outgoing panel and adds `ddmm-panel--exited-left`; simultaneously adds `ddmm-panel--active` to the incoming panel (which transitions from its off-stage-right default). Both transitions run in parallel — no JS timer, no sequencing.

**When to use:** Every drill-in and every back-nav.

**Critical detail — why no flicker:** Both panels share the SAME `--ddmm-transition-duration` and `--ddmm-transition-easing`. The browser runs both compositor-thread transitions concurrently. Because both are GPU-composited (`transform`/`opacity` only), neither blocks the main thread. The panels are `position: absolute; inset: 0` stacked — they overlap during the transition (which IS the desired cross-fade/overlap effect per D-03).

**Example:**

```javascript
// Source: synthesized from D-05 + D-19 + GPU-compositing best practice.
// Scoped to this.container (Phase 4 D-16, Anti-Pattern 3).

drill( targetPanelId ) {
    const incoming = this.container.querySelector(
        '[data-panel-id="' + targetPanelId + '"]'
    );
    if ( !incoming ) return;

    // Find the currently-active panel (there is exactly one).
    const outgoing = this.container.querySelector( '.ddmm-panel--active' );
    if ( !outgoing ) return;

    // 1. Outgoing: active → exited-left.
    outgoing.classList.remove( 'ddmm-panel--active' );
    outgoing.classList.add( 'ddmm-panel--exited-left' );
    outgoing.setAttribute( 'aria-hidden', 'true' );

    // 2. Incoming: off-stage-right default → active.
    incoming.classList.remove( 'ddmm-panel--exited-left' ); // safety: clear stale state
    incoming.classList.add( 'ddmm-panel--active' );
    incoming.setAttribute( 'aria-hidden', 'false' );

    // 3. Push to history for back-nav.
    this.history.push( outgoing.dataset.panelId );

    // 4. Update chevron aria-expanded/aria-label (D-23).
    const chevron = this.container.querySelector(
        '[data-target="' + targetPanelId + '"]'
    );
    if ( chevron ) {
        chevron.setAttribute( 'aria-expanded', 'true' );
        // Label flip "Show" → "Hide" per D-23 — implemented via data attrs or string replace.
    }

    // 5. Post-transition cleanup: reset outgoing scroll, done after transitionend.
    incoming.scrollTop = 0;  // start the incoming panel at top
    outgoing.addEventListener( 'transitionend', function onEnd( e ) {
        if ( e.propertyName !== 'transform' ) return;
        outgoing.removeEventListener( 'transitionend', onEnd );
        outgoing.scrollTop = 0;  // reset for next visit
    } );
}

back() {
    if ( !this.history.length ) return;
    const previousId = this.history.pop();
    const incoming = this.container.querySelector(
        '[data-panel-id="' + previousId + '"]'
    );
    const outgoing = this.container.querySelector( '.ddmm-panel--active' );
    if ( !incoming || !outgoing ) return;

    // Reverse of drill: outgoing active → off-stage-right (remove active + exited-left).
    outgoing.classList.remove( 'ddmm-panel--active' );
    outgoing.classList.remove( 'ddmm-panel--exited-left' ); // back to default off-stage-right
    outgoing.setAttribute( 'aria-hidden', 'true' );

    // Incoming: exited-left → active.
    incoming.classList.remove( 'ddmm-panel--exited-left' );
    incoming.classList.add( 'ddmm-panel--active' );
    incoming.setAttribute( 'aria-hidden', 'false' );
}
```

### Pattern 3: Single `close()` Path (D-19)

**What:** Every dismiss trigger (✕ button, overlay click, link click, future Esc) calls the same `close()` method. Cleanup is centralized: class removal, aria reset, scroll reset, search-clear, history reset.

```javascript
close() {
    this.container.classList.remove( 'ddmm-is-open' );

    // Reset drawer + overlay aria.
    const drawer = this.container.querySelector( '[data-ddmm-drawer]' );
    const overlay = this.container.querySelector( '[data-ddmm-overlay]' );
    if ( drawer ) drawer.setAttribute( 'aria-hidden', 'true' );
    if ( overlay ) overlay.setAttribute( 'aria-hidden', 'true' );

    // Reset trigger aria-expanded + hamburger morph.
    const trigger = this.container.querySelector( '[data-ddmm-trigger]' );
    if ( trigger ) {
        trigger.setAttribute( 'aria-expanded', 'false' );
        trigger.classList.remove( 'ddmm-trigger--active' );
    }

    // Reset panels to initial state (root active, all others off-stage).
    this.resetPanels();

    // Clear search if active.
    this.clearSearch();

    this.history = [];
}
```

### Pattern 4: Config Bridge — `data-*` on container, parsed once on init

**What:** PHP renders all Phase 5 toggles as `data-*` attributes on `.ddmm-widget` and a `style` attribute setting the `--ddmm-transition-*` vars. JS reads them once in `init()` and stores in `this.config`.

```php
// Source: Phase 4 D-15 pattern. In DrillDownMenu::render() — the .ddmm-widget wrapper.
$anim_type   = $settings['animation_type'] ?? 'slide';
$duration    = $settings['animation_duration']['size'] ?? 300;
$easing      = $settings['animation_easing'] ?? 'ease';
$search_on   = ( $settings['search_enabled'] ?? '' ) === 'yes';
$auto_open   = ( $settings['auto_open_current'] ?? 'yes' ) === 'yes';
$close_link  = ( $settings['close_after_link'] ?? 'yes' ) === 'yes';
$close_overlay = ( $settings['close_on_overlay'] ?? 'yes' ) === 'yes';

printf(
    '<div class="ddmm-widget ddmm-anim--%s" id="ddmm-widget-%s" data-ddmm-init
        data-ddmm-anim="%s"
        data-ddmm-auto-open="%s"
        data-ddmm-close-link="%s"
        data-ddmm-close-overlay="%s"
        style="--ddmm-transition-duration:%sms;--ddmm-transition-easing:%s">',
    esc_attr( $anim_type ),
    esc_attr( $widget_id ),
    esc_attr( $anim_type ),
    $auto_open ? 'true' : 'false',
    $close_link ? 'true' : 'false',
    $close_overlay ? 'true' : 'false',
    (int) $duration,
    esc_attr( $easing )
);
```

```javascript
// JS parses once.
this.config = {
    anim:        container.dataset.ddmmAnim || 'slide',
    autoOpen:    container.dataset.ddmmAutoOpen !== 'false',
    closeLink:   container.dataset.ddmmCloseLink !== 'false',
    closeOverlay: container.dataset.ddmmCloseOverlay !== 'false',
};
```

### Recommended Project Structure (Phase 5 touches these files only)

```
assets/js/ddmm-frontend.js   # Fill init() body + add DrillDownMenu methods
assets/css/ddmm-frontend.css # Add: anim-type hooks, exited-left state,
                             # hamburger→X, search box + results + sticky bar
src/Rendering/DrawerRenderer.php  # Add: render_search_box() method,
                                  # called conditionally in render()
src/Elementor/Widget/DrillDownMenu.php
    # _register_controls(): add Animation + Search + Drawer-Settings-toggles
    # render(): add data-* config attrs + inline --ddmm-transition-* style
                             #     on .ddmm-widget; ADD data-ddmm-trigger
                             #     to the trigger button (see Gap A1 below)
```

### Anti-Patterns to Avoid

- **Inline-style animation from JS:** Setting `el.style.transform = 'translateX(-100%)'` fights the CSS transition and forces main-thread recalc. Use class toggles only. [CITED: developers.google.com rendering perf]
- **Permanent `will-change`:** Leaving `will-change: transform, opacity` on every panel forever wastes GPU memory (each panel gets its own compositor layer). Phase 4 already sets it on `.ddmm-panel` — acceptable here because panel count is small (a menu rarely exceeds ~20 panels). For larger menus, toggle `will-change` off after `transitionend`. [CITED: MDN will-change — "do not overuse"]
- **Animating layout properties:** `left`, `top`, `width`, `margin`, `padding` trigger layout on every frame. Phase 5 uses `transform` + `opacity` exclusively. [VERIFIED: codebase — Phase 4 CSS already follows this; PITFALLS.md Pitfall 3]
- **Global JS state:** Storing panel state in a module-level variable breaks multi-instance. Every query is `this.container.querySelector(...)`. [VERIFIED: ARCHITECTURE.md Anti-Pattern 3]
- **Positional panel navigation:** Using sibling index / DOM position to find the next panel. Always use `[data-panel-id="X"]` ID lookup. [VERIFIED: ARCHITECTURE.md Anti-Pattern 2; Phase 4 D-25]
- **Debouncing the input value display:** The debounce must wrap the FILTER computation, not the input field update — the user must see their keystrokes immediately. [CITED: reddit.com/r/reactjs TanStack debounce caution]

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| URL parsing/normalization | Regex string splitting on `?`, `#`, `/` | Native `URL` + `URLSearchParams` APIs | Handles all edge cases (ports, encoded chars, relative vs absolute) per WHATWG spec. Regex-based parsing breaks on `?a=b&c=d#frag` and URL-encoded paths. [CITED: url.spec.whatwg.org] |
| Event delegation | Per-element `addEventListener` on every chevron/back/link | Single delegated listener on the panels container using `event.target.closest()` | Handles dynamically-rendered panels uniformly; fewer listeners; matches the per-container scope mandate. |
| Animation timing | JS `requestAnimationFrame` loop driving transforms | CSS `transition` + class toggle | CSS transitions run on the compositor thread; JS rAF runs on main thread and blocks. ANIM-04 mandates GPU-only. |
| Search debounce | Custom throttle with timestamp math | `setTimeout` + `clearTimeout` debounce | The simplest correct implementation; ~5 lines; no library. |
| Debounce/throttle library | Lodash/Just `debounce` import | Inline `setTimeout` debounce | No build step (CLAUDE.md); the implementation is trivial and self-contained. |

**Key insight:** Every problem in Phase 5 has a native browser solution. The only dependencies are the existing `ddmm-frontend.js`/`.css` files. No new library, no new package, no new file.

## Common Pitfalls

### Pitfall 1: Missing `data-ddmm-trigger` attribute on the trigger button (GAP FOUND)

**What goes wrong:** The Phase 4 D-25 contract specifies `data-ddmm-trigger` as the hook attribute on the trigger button. The existing JS bootstrap comment (`ddmm-frontend.js` line 36) assumes it: `container.querySelector( '[data-ddmm-trigger]' )`. But the ACTUAL rendered button in `DrillDownMenu.php` (lines 425-477) has classes `ddmm-trigger ddmm-trigger--{type}` and NO `data-ddmm-trigger` attribute. Phase 5's open-listener would find nothing.

**Why it happens:** Phase 4's plan deferred the attribute but the renderer never added it. The contract drifted from the implementation.

**How to avoid:** Phase 5 MUST add `data-ddmm-trigger` to the trigger `<button>` in `DrillDownMenu::render()`. Alternatively, JS can find the trigger via `.ddmm-trigger` class (the button always carries it). Recommendation: add the attribute to match the contract AND keep the class fallback in the querySelector: `container.querySelector( '[data-ddmm-trigger], .ddmm-trigger' )`.

**Warning signs:** Clicking the trigger does nothing; console shows `Cannot read properties of null` on the trigger listener.

[VERIFIED: codebase — `src/Elementor/Widget/DrillDownMenu.php` lines 425-477 vs Phase 4 04-CONTEXT.md D-25]

### Pitfall 2: Elementor `elementorFrontend.hooks` not ready on page load

**What goes wrong:** Accessing `elementorFrontend.hooks.addAction` before Elementor's frontend init fires throws `TypeError: Cannot read properties of undefined`. The menu never initializes.

**Why it happens:** Elementor loads its frontend scripts asynchronously. The `elementorFrontend` global exists but its `hooks` property is populated only after the `elementor/frontend/init` event.

**How to avoid:** The existing bootstrap (lines 99-119) already handles this correctly via a three-path guard:
1. If `elementorFrontend` AND `elementorFrontend.hooks` exist → register immediately.
2. Else if `jQuery` exists → wait for `elementor/frontend/init` event, then register.
3. DOMContentLoaded always fires as the fallback (queries all `.ddmm-widget` directly).

**Phase 5 action:** Do NOT touch the bootstrap. The `init(container)` body is where all Phase 5 listeners attach — by the time `init()` runs, the container is in the DOM and queryable. The init-time crash risk was solved in Phase 4.

**Warning signs:** Menu works on hard refresh but not on PJAX navigation; console TypeError on `elementorFrontend`.

[VERIFIED: codebase — `ddmm-frontend.js` lines 99-119; PITFALLS.md Pitfall 1]

### Pitfall 3: Editor mode — interaction listeners firing in Elementor editor preview

**What goes wrong:** Phase 5's drill/close listeners attach in the Elementor editor preview iframe, where the drawer is NOT meant to be interactive (Phase 4 D-18 ships an editor-only static root panel, no off-canvas drawer).

**Why it happens:** The `frontend/element_ready` hook fires in the editor preview iframe too. If `init()` attaches listeners unconditionally, clicking in the editor could trigger drawer open/close on a non-existent drawer.

**How to avoid:** Guard `init()` against editor mode. Elementor exposes `elementorFrontend.isEditMode()` which returns `true` inside the editor preview iframe.

```javascript
init( container ) {
    if ( ! container || container.dataset.ddmmInit ) return;
    // Editor mode guard: the editor preview (Phase 4 D-18) is static; skip listeners.
    if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
        return;
    }
    container.dataset.ddmmInit = 'true';
    // ... wire listeners
}
```

Note: the DOMContentLoaded path also fires in the editor, so the guard inside `init()` covers both paths. The editor preview markup (`render_editor_preview`) emits no `[data-ddmm-drawer]`, so even without the guard, queries would return null — but the explicit guard is clearer and prevents wasted listener attachment.

**Warning signs:** Drawer opens unexpectedly while editing in Elementor; console errors about missing `[data-ddmm-drawer]`.

[CITED: developers.elementor.com/docs/hooks/js/ — `elementorFrontend.isEditMode()`]

### Pitfall 4: `transitionend` firing multiple times (once per animated property)

**What goes wrong:** A `transitionend` listener used for post-transition cleanup fires TWICE when both `transform` and `opacity` transition (Slide+Fade, Scale types). The cleanup runs twice, potentially causing flicker or double-scroll-reset.

**Why it happens:** `transitionend` fires once per property that transitioned. Two properties = two events.

**How to avoid:** Filter on `event.propertyName`:

```javascript
panel.addEventListener( 'transitionend', function onEnd( e ) {
    if ( e.propertyName !== 'transform' ) return;  // only handle one
    panel.removeEventListener( 'transitionend', onEnd );
    // ... cleanup
} );
```

**Warning signs:** Cleanup runs inconsistently; console shows double-execution logs.

[CITED: MDN transitionend — "fired for each property"]

### Pitfall 5: Box-shadow / border-radius repaint during transform (mobile Safari)

**What goes wrong:** Panels with large `box-shadow` or `border-radius` repaint during `transform: translateX()` on mobile Safari, causing visible jank even though transform is GPU-composited.

**Why it happens:** Safari historically had bugs where composited layers with box-shadow forced repaints during transform. The compositor handles the transform, but the shadow must be re-rasterized.

**How to avoid:** Phase 5 does not add box-shadow to panels (Phase 4 base CSS has none on `.ddmm-panel`). The drawer has `overflow: hidden` which clips panels cleanly. If Phase 6 Style Tab adds shadows, they should go on the DRAWER, not the panels. Document this as a Phase 6 constraint.

**Warning signs:** Smooth on Chrome, janky on iOS Safari, especially on older iPhones.

[VERIFIED: PITFALLS.md Pitfall 3; CITED: developers.google.com rendering perf]

### Pitfall 6: Search index stale after Elementor editor save (cached frontend)

**What goes wrong:** The search index is built once in `init()` from the rendered DOM. If the user edits the menu in Elementor and the page is served from a cache (WP Rocket, etc.), the DOM may be stale relative to the new settings. Search returns old items.

**Why it happens:** Page caching serves the old HTML. The JS reads the old DOM. This is a WordPress-page-cache concern, not a Phase 5 bug — but worth documenting.

**How to avoid:** Phase 5 cannot solve this (it's a cache invalidation concern). The search index is rebuilt on every page load because `init()` runs fresh each time the script loads. The staleness is purely a page-cache issue — Elementor's own CSS regen + cache plugins handle this. No action needed in Phase 5.

**Warning signs:** Search results don't match the configured menu after a save + cache serve.

### Pitfall 7: Auto-open highlight surviving after manual navigation

**What goes wrong:** Auto-open applies `current-menu-item` / `current-menu-ancestor` marker classes to the matched item + ancestors. If the user then navigates the drill-down manually, those classes persist on now-irrelevant items, confusing the highlight.

**Why it happens:** The marker classes are applied once on open and never cleared.

**How to avoid:** The marker classes should be applied to the ITEMS (the `<a>` / `<li>`), not the panels. They indicate "this item is the current page" — a fact that does not change as the user drills around. This mirrors WordPress core behavior: `current-menu-item` is set server-side and never removed by JS interaction. So no cleanup is needed — the highlight is page-relative, not panel-relative.

**Warning signs:** None — if implemented correctly (item-scoped, not panel-scoped), this pitfall does not occur.

### Pitfall 8: Auto-open URL match fails on home page (edge case)

**What goes wrong:** When the current page is the site home (`/`), URL normalization may produce an empty pathname or the menu item URL may be the full site URL while `window.location.href` is a relative path. Match fails; no auto-drill.

**Why it happens:** Home page URLs have multiple valid forms: `https://example.com/`, `https://example.com`, `/`, `https://example.com/index.php`. Strict string comparison fails.

**How to avoid:** Normalize BOTH the current URL and each item URL through the same `normalizeUrl()` function (see §URL-Match Normalization). For home page specifically, treat empty pathname + root pathname as equivalent. See the code example in §URL-Match Normalization.

**Warning signs:** Auto-open works on inner pages but not on the home page.

## Code Examples

### URL-Match Normalization (the authoritative pattern)

```javascript
// Source: synthesized from WHATWG URL spec + WordPress menu-item URL patterns.
// Uses native URL API. No regex, no library.

/**
 * Normalize a URL for comparison: strip hash, strip trailing slash (except root),
 * lowercase host, ignore protocol http vs https (treat as same site).
 *
 * @param {string} raw - The URL to normalize (item href or window.location.href).
 * @returns {string|null} Normalized "pathname?query" string, or null if unparseable.
 */
function normalizeUrl( raw ) {
    if ( ! raw || raw === '#' ) return null;
    try {
        const u = new URL( raw, window.location.origin );
        // Strip hash. Sort query params for order-insensitive comparison.
        let path = u.pathname.replace( /\/+$/, '' ) || '/';   // trim trailing slash, keep root
        if ( u.search ) {
            const params = new URLSearchParams( u.search );
            const sorted = Array.from( params.keys() ).sort().map(
                k => k + '=' + params.get( k )
            ).join( '&' );
            if ( sorted ) path += '?' + sorted;
        }
        return u.host.toLowerCase() + path;
    } catch ( e ) {
        return null;  // malformed URL — no match
    }
}

/**
 * Find the menu item matching the current page.
 * @returns {HTMLAnchorElement|null}
 */
findCurrentPageItem() {
    const current = normalizeUrl( window.location.href );
    if ( !current ) return null;
    const links = this.container.querySelectorAll( '.ddmm-menu a[href]' );
    for ( const link of links ) {
        // D-14: WP 'current-menu-item' class is a hint (WP source only).
        if ( link.classList.contains( 'current-menu-item' ) ||
             link.closest( '.current-menu-item' ) ) {
            return link;
        }
        // URL match is the authoritative fallback (works for both sources).
        if ( normalizeUrl( link.href ) === current ) {
            return link;
        }
    }
    return null;
}
```

### Auto-Open Current Path (the full flow)

```javascript
// Source: D-12 + D-13 + D-14. Called from open() if this.config.autoOpen.

autoOpenCurrentPath() {
    const item = this.findCurrentPageItem();
    if ( !item ) return;  // D-14: do nothing if not in menu

    // Mark the current item + all ancestor <li>s (D-13).
    item.closest( 'li' )?.classList.add( 'ddmm-current-item' );
    let cursor = item.closest( '.ddmm-panel' );
    const ancestorPanels = [];
    while ( cursor ) {
        // Mark every item that is an ancestor of the current item.
        cursor.querySelectorAll( '.ddmm-menu__item' ).forEach( li => {
            if ( li.contains( item ) && li !== item.closest( 'li' ) ) {
                li.classList.add( 'ddmm-current-ancestor' );
            }
        } );
        ancestorPanels.unshift( cursor );  // root-first order
        // Walk up: find the parent panel via the back-target of this panel's back button.
        const backBtn = cursor.querySelector( '[data-back-target]' );
        const parentId = backBtn?.dataset.backTarget;
        cursor = parentId
            ? this.container.querySelector( '[data-panel-id="' + parentId + '"]' )
            : null;
    }

    // Drill down to the deepest ancestor WITHOUT animation (instant).
    // Suppress transition by temporarily setting duration to 0.
    this.container.style.setProperty( '--ddmm-transition-duration', '0ms' );
    ancestorPanels.forEach( ( panel, idx ) => {
        if ( idx === 0 ) return;  // root already active
        const prev = ancestorPanels[ idx - 1 ];
        prev.classList.remove( 'ddmm-panel--active' );
        prev.classList.add( 'ddmm-panel--exited-left' );
        prev.setAttribute( 'aria-hidden', 'true' );
        panel.classList.add( 'ddmm-panel--active' );
        panel.setAttribute( 'aria-hidden', 'false' );
        this.history.push( prev.dataset.panelId );
    } );
    // Restore the configured duration on next frame.
    requestAnimationFrame( () => {
        requestAnimationFrame( () => {
            this.container.style.removeProperty( '--ddmm-transition-duration' );
        } );
    } );
}
```

### Search Box Markup (PHP, in DrawerRenderer)

```php
// Source: D-06, D-07, D-10, D-11. Rendered conditionally when search_enabled === 'yes'.
// Inserted in render() AFTER render_header(), BEFORE the <nav>.

if ( ! empty( $settings['search_enabled'] ) && 'yes' === $settings['search_enabled'] ) {
    self::render_search_box( $settings );
}

private static function render_search_box( array $settings ): void {
    $placeholder = $settings['search_placeholder'] ?? __( 'Search menu…', 'devsroom-drilldown-mobile-menu' );
    printf(
        '<div class="ddmm-search" data-ddmm-search role="search">'
        . '<label class="screen-reader-text" for="ddmm-search-input">%s</label>'
        . '<input type="search" id="ddmm-search-input" class="ddmm-search__input" data-ddmm-search-input placeholder="%s" autocomplete="off" aria-controls="ddmm-search-results">'
        . '<ul class="ddmm-search__results" data-ddmm-search-results id="ddmm-search-results" aria-live="polite" aria-relevant="additions"></ul>'
        . '</div>',
        esc_attr__( 'Search menu items', 'devsroom-drilldown-mobile-menu' ),
        esc_attr( $placeholder )
    );
}
```

### Search Index Build + Debounced Filter (JS)

```javascript
// Source: D-06, D-08. Built once in init(); filter runs on debounced input.

buildSearchIndex() {
    this.searchIndex = [];
    const seen = new Set();
    const links = this.container.querySelectorAll( '.ddmm-menu a[href]' );
    links.forEach( link => {
        const href = link.getAttribute( 'href' );
        if ( !href || href === '#' || seen.has( href ) ) return;
        seen.add( href );
        const title = ( link.textContent || '' ).trim();
        if ( !title ) return;

        // Compute breadcrumb: walk up through ancestor panels, collect parent titles.
        const breadcrumb = [];
        let panel = link.closest( '.ddmm-panel' );
        while ( panel ) {
            const titleEl = panel.querySelector( '.ddmm-back__title' );
            if ( titleEl ) breadcrumb.unshift( titleEl.textContent.trim() );
            const backBtn = panel.querySelector( '[data-back-target]' );
            const parentId = backBtn?.dataset.backTarget;
            panel = parentId
                ? this.container.querySelector( '[data-panel-id="' + parentId + '"]' )
                : null;
        }
        breadcrumb.push( title );  // the item itself last

        // Find the drill target: if this item is a parent (has a sibling chevron), the
        // chevron's data-target is the drill target; clicking the result should drill.
        const li = link.closest( '.ddmm-menu__item' );
        const chevron = li ? li.querySelector( '[data-target]' ) : null;

        this.searchIndex.push( {
            title: title,
            breadcrumb: breadcrumb.join( ' › ' ),
            href: href,
            target: link.target,
            drillPanelId: chevron ? chevron.dataset.target : null,
            element: link,
        } );
    } );
}

handleSearchInput() {
    const input = this.container.querySelector( '[data-ddmm-search-input]' );
    if ( !input ) return;
    let timer = null;
    input.addEventListener( 'input', ( e ) => {
        clearTimeout( timer );
        timer = setTimeout( () => {
            this.filterSearch( e.target.value );
        }, 200 );  // 200ms — see §Search Filtering for rationale
    } );
    // Esc clears search (returns to drill view).
    input.addEventListener( 'keydown', ( e ) => {
        if ( e.key === 'Escape' ) {
            input.value = '';
            this.clearSearch();
            input.blur();
        }
    } );
}

filterSearch( rawQuery ) {
    const results = this.container.querySelector( '[data-ddmm-search-results]' );
    if ( !results ) return;
    const query = rawQuery.trim().toLowerCase();

    if ( !query ) {
        this.clearSearch();
        return;
    }

    // Match by title substring (case-insensitive).
    const matches = this.searchIndex.filter(
        item => item.title.toLowerCase().includes( query )
    );

    // Toggle the drawer into "search mode" — hide drill view, show results.
    this.container.classList.add( 'ddmm-search-active' );

    if ( !matches.length ) {
        results.innerHTML = '<li class="ddmm-search__no-results">' +
            escHtml( this.config.i18n.noResults ) + '</li>';
        return;
    }

    results.innerHTML = matches.map( item => {
        const target = item.drillPanelId
            ? 'data-ddmm-search-drill="' + escAttr( item.drillPanelId ) + '"'
            : 'href="' + escAttr( item.href ) + '"' + ( item.target ? ' target="_blank"' : '' );
        return '<li class="ddmm-search__result-item">' +
            '<a class="ddmm-search__result" ' + target + '>' +
            '<span class="ddmm-search__result-title">' + escHtml( item.title ) + '</span>' +
            '<span class="ddmm-search__result-breadcrumb">' + escHtml( item.breadcrumb ) + '</span>' +
            '</a></li>';
    } ).join( '' );
}

clearSearch() {
    this.container.classList.remove( 'ddmm-search-active' );
    const results = this.container.querySelector( '[data-ddmm-search-results]' );
    if ( results ) results.innerHTML = '';
    const input = this.container.querySelector( '[data-ddmm-search-input]' );
    if ( input ) input.value = '';
}
```

> **Escaping note:** The JS-side `escHtml` / `escAttr` above are local helpers that set `textContent` / use `setAttribute` rather than string concatenation to avoid XSS. The cleaner implementation builds DOM nodes via `document.createElement` and sets `.textContent`. The string-concat form above is illustrative of the structure; the actual implementation should use DOM APIs for safety.

### Close Behaviors (delegated listeners)

```javascript
// Source: D-16, D-17, D-18, D-19. All route through close().

wireCloseListeners() {
    // ✕ close button — always available (Phase 4 D-07).
    const closeBtn = this.container.querySelector( '[data-ddmm-close]' );
    closeBtn?.addEventListener( 'click', () => this.close() );

    // Overlay — gated by toggle (D-17).
    const overlay = this.container.querySelector( '[data-ddmm-overlay]' );
    if ( overlay && this.config.closeOverlay ) {
        overlay.addEventListener( 'click', () => this.close() );
    }

    // Link clicks — delegated on the panels container (D-16, D-18).
    const panels = this.container.querySelector( '.ddmm-panels' );
    panels?.addEventListener( 'click', ( e ) => {
        if ( !this.config.closeLink ) return;
        const link = e.target.closest( 'a[href]' );
        if ( !link ) return;
        // D-18: new-tab links leave the drawer open.
        if ( link.target === '_blank' || link.hasAttribute( 'target' ) &&
             link.getAttribute( 'target' ) === '_blank' ) {
            return;
        }
        // Same-tab navigation → close. The browser will navigate after the handler.
        this.close();
    } );

    // Search result link clicks — same close-after-link rule applies.
    const searchResults = this.container.querySelector( '[data-ddmm-search-results]' );
    searchResults?.addEventListener( 'click', ( e ) => {
        const drill = e.target.closest( '[data-ddmm-search-drill]' );
        if ( drill ) {
            // Parent result → drill into its panel (D-08). Do NOT close.
            e.preventDefault();
            // Exit search mode first.
            this.clearSearch();
            this.drill( drill.dataset.ddmmSearchDrill );
            return;
        }
        const link = e.target.closest( 'a[href]' );
        if ( link && this.config.closeLink && link.target !== '_blank' ) {
            this.close();
        }
    } );
}
```

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | None — no PHPUnit / wp-env / Jest / Playwright configured in the project |
| Config file | none |
| Quick run command | `php -l src/Rendering/DrawerRenderer.php` + `php -l src/Elementor/Widget/DrillDownMenu.php` + `node --check assets/js/ddmm-frontend.js` |
| Full suite command | `find src -name '*.php' -exec php -l {} \;` + `node --check assets/js/ddmm-frontend.js` + the grep checks below + manual browser verification |
| Estimated runtime | ~3 seconds (lint + syntax check) + ~2 seconds (greps) |

The project has no JS test harness and no build step (CLAUDE.md mandates no build tool). `node --check` is the available JS syntax verifier — it parses the file without executing it, catching syntax errors in ~0.5s. This matches the Phase 4 VALIDATION.md pattern (lint + grep + manual).

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| ANIM-01 | Animation-type SELECT control + 4 container classes emitted | static grep | `grep -cE "ddmm-anim--(slide\|fade\|scale\|slidefade)" assets/css/ddmm-frontend.css` ≥4 AND `grep -c "animation_type" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 (CSS classes not yet added) |
| ANIM-02 | Duration slider 100-2000ms → `--ddmm-transition-duration` inline override | static grep | `grep -c "animation_duration" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -c "ddmm-transition-duration" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| ANIM-03 | Easing SELECT → `--ddmm-transition-easing` var consumed by transitions | static grep | `grep -c "animation_easing" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -c "ddmm-transition-easing" assets/css/ddmm-frontend.css` ≥2 | ❌ Wave 0 |
| ANIM-04 | Only `transform`/`opacity` animate — no `left`/`top`/`width`/`margin` transitions | negative grep | `grep -nE "transition:[^;]*(left\|top\|width\|margin\|padding)" assets/css/ddmm-frontend.css` = 0 (lines matching the transition shorthand with layout properties) | ✅ exists (verify holds after edits) |
| EXTR-01 | Search box conditional render + placeholder TEXT control | static grep | `grep -c "search_enabled" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -c "data-ddmm-search" src/Rendering/DrawerRenderer.php` ≥1 | ❌ Wave 0 |
| EXTR-02 | Search filters all items across panels (flat index) | static grep + manual | `grep -cE "buildSearchIndex\|searchIndex\|filterSearch" assets/js/ddmm-frontend.js` ≥3 | ❌ Wave 0 |
| EXTR-03 | Auto-open URL match + drill + marker classes | static grep + manual | `grep -cE "autoOpenCurrentPath\|findCurrentPageItem\|normalizeUrl" assets/js/ddmm-frontend.js` ≥3 AND `grep -c "ddmm-auto-open" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| EXTR-04 | Close-after-link toggle, excludes new-tab + chevron | static grep + manual | `grep -c "closeLink" assets/js/ddmm-frontend.js` ≥1 AND `grep -c "ddmm-close-link" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| EXTR-05 | Close-on-overlay toggle | static grep + manual | `grep -c "closeOverlay" assets/js/ddmm-frontend.js` ≥1 AND `grep -c "ddmm-close-overlay" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| DRAW-10 (carry) | ID-based nav only, no positional logic | negative grep | `grep -nE "children\[|nextSibling\|\.index\(" assets/js/ddmm-frontend.js` = 0 | ✅ exists (verify holds) |
| JSCR-02 (carry) | IIFE-wrapped, no globals | static grep | `grep -c "^( function() {" assets/js/ddmm-frontend.js` ≥1 AND `grep -nE "window\.ddmm\|var ddmm" assets/js/ddmm-frontend.js` = 0 (window.ddmm excepted if scoped) | ✅ exists |

### Sampling Rate
- **Per task commit:** `php -l` on touched PHP files + `node --check assets/js/ddmm-frontend.js` + grep checks for that task's requirements.
- **Per wave merge:** full lint sweep + all greps + manual browser verification of one animation type per wave.
- **Phase gate:** all 4 animation types verified in browser, search + auto-open + close behaviors verified, all greps green before `/gsd-verify-work`.

### Concrete Manual Verification Approaches

These are the browser-based checks that gate Phase 5 sign-off (no automated harness exists):

**a) Animation type produces correct transform/opacity (ANIM-01..04):**
1. Configure a 3-level WP menu in Elementor.
2. For each of the 4 types (Slide, Fade, Scale, Slide+Fade):
   - Set the type in the Content Tab, save, view frontend.
   - Open DevTools → Elements, select a child panel.
   - Click a parent chevron; in DevTools, observe the inline-style/computed-style transition.
   - **Slide:** outgoing panel computed `transform: matrix(-1, 0, 0, 1, -W, 0)` (translateX(-100%)); incoming animates from `translateX(100%)` to `translateX(0)`.
   - **Fade:** both panels stay at `translateX(0)`; opacity transitions 0↔1.
   - **Scale:** incoming starts at `scale(0.92)` + `opacity: 0`; outgoing → `scale(0.96)` + `opacity: 0`.
   - **Slide+Fade:** outgoing → `translateX(-100%)` + `opacity: 0`; incoming ← `translateX(100%)` + `opacity: 0` → `translateX(0)` + `opacity: 1`.
   - Run DevTools Performance recording during the transition; confirm NO purple "Recalculate Style" bars during the transform frames (compositor-only).
3. Back button reverses each type correctly.

**b) Search filtering correctness across tree depth (EXTR-01/02):**
1. Configure a menu: Root → "Shop" → "Categories" → "Shoes" (3 levels) + a root-level "Contact".
2. Enable search in Content Tab.
3. On frontend, open drawer, type "shoes":
   - Exactly one result appears.
   - Result breadcrumb reads `Shop › Categories › Shoes`.
4. Type "s": results include "Shoes", "Shop", "Categories" (all containing "s").
5. Clear query (or press Esc): drawer returns to drill view at root.
6. Type "zzz": "No results" message appears.
7. Click a parent result ("Shop"): drawer drills into the Shop panel (does NOT navigate).
8. Click a leaf result ("Shoes"): navigates to the Shoes URL.

**c) Auto-open URL match accuracy (EXTR-03):**
1. Configure a menu with "About" → "Team" → "Leadership" hierarchy.
2. Navigate to the Leadership page on the frontend.
3. Click the trigger to open the drawer.
4. **Verify:** the drawer opens already drilled to the Leadership panel (no animation — instant), the Leadership item has the marker class, and the About + Team items have ancestor marker classes.
5. Navigate to a page NOT in the menu (e.g., a blog post).
6. Open the drawer: it opens at root, no items highlighted.
7. Navigate to the home page.
8. Open the drawer: if a home item exists in the menu, it highlights; otherwise root, no highlight.

**d) Close-behavior edge cases (EXTR-04/05):**
1. **Same-tab leaf link:** click a leaf `<a>` → drawer closes, page navigates.
2. **Same-tab split-parent label `<a>`:** click "Shop" label → drawer closes, navigates to Shop URL.
3. **Chevron drill-in:** click `›` → drawer STAYS OPEN, panel drills (chevron is not a navigation).
4. **New-tab link (`target="_blank"`):** click → opens new tab, drawer STAYS OPEN on original page.
5. **Overlay click:** click the semi-transparent overlay → drawer closes (when toggle ON).
6. **Overlay click with toggle OFF:** click overlay → nothing happens.
7. **✕ close:** always available, always closes.

### Wave 0 Gaps
- [ ] `assets/css/ddmm-frontend.css` — add animation-type class hooks (`ddmm-anim--{type}`) + `ddmm-panel--exited-left` state + search-box styles + hamburger→X transforms + `--ddmm-transition-easing` consumption. All ANIM/EXTR greps depend on these.
- [ ] `assets/js/ddmm-frontend.js` — fill `init()` body + add `DrillDownMenu` methods (open/close/drill/back/search/autopath). All JS greps depend on this.
- [ ] `src/Rendering/DrawerRenderer.php` — add `render_search_box()` method + call it conditionally in `render()`.
- [ ] `src/Elementor/Widget/DrillDownMenu.php` — add Animation, Search, and Drawer-Settings-toggles Content Tab sections; add `data-*` config attrs + inline `--ddmm-transition-*` style + `data-ddmm-trigger` on `.ddmm-widget` and trigger button (Pitfall 1).
- [ ] No JS test framework install needed — `node --check` ships with Node.js and is available wherever Node is installed. If Node is absent, fall back to `js -c` (SpiderMonkey) or browser DevTools console. **Environment probe required** (see below).

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.1+ | `php -l` lint of touched files | Probe required | — | None — PHP is the production runtime |
| Node.js (any) | `node --check assets/js/ddmm-frontend.js` | Probe required | — | Browser DevTools console (paste the file, check for syntax errors); or skip JS syntax check and rely on manual browser verification |
| WordPress 6.5+ | Runtime — manual verification only | Probe required (local dev site) | — | None — manual browser verification is the gating step |
| Elementor 3.29+ | Runtime — manual verification only | Probe required | — | None |

**Missing dependencies with no fallback:** None for code authoring. Manual browser verification requires a WP + Elementor dev site (the project's existing dev environment — not a Phase 5 addition).

**Missing dependencies with fallback:** If Node is unavailable, JS syntax verification falls back to browser DevTools. No production dependency is added by Phase 5.

## Security Domain

`security_enforcement` is not explicitly set in `.planning/config.json`, so per the defaults it is treated as enabled. Phase 5 is primarily a JS/CSS behavior phase with minimal new security surface, but the search box introduces user-controlled input that must be handled safely.

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | N/A — no auth in Phase 5 |
| V3 Session Management | no | N/A |
| V4 Access Control | no | N/A |
| V5 Input Validation | yes | Search input is user-controlled. MUST be rendered into the results list via `textContent` / DOM APIs, NEVER via `innerHTML` with unescaped input. The `escHtml`/`escAttr` helpers in the Code Examples must use `element.textContent = value` not string concat. |
| V6 Cryptography | no | N/A |
| V12 Files & Resources | no | N/A |

### Known Threat Patterns for this stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| XSS via search query reflected into results DOM | Tampering / Spoofing | Build result DOM nodes via `document.createElement` + `.textContent`. Never `innerHTML` the raw query. The menu item titles come from server-rendered escaped HTML (already safe via `esc_html` in PHP), but JS-rebuilt strings must use safe DOM APIs. |
| XSS via URL `href` in search results | Tampering | Item hrefs come from the server-rendered `<a href>` (escaped via `esc_url` in PHP). JS reads `link.href` (the browser's parsed, absolute URL) — safe. Do NOT read `link.getAttribute('href')` and re-inject it raw. |
| Stored XSS via custom menu builder label | Tampering | Phase 3/4 already escapes labels via `esc_html`. Phase 5 reads `link.textContent` (safe — text, not HTML). No new surface. |

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| jQuery `$.on()` for all events | Native `addEventListener` + `closest()` delegation | WP 5.6+ (jQuery deprecation push) | CLAUDE.md mandates pure ES6; the existing bootstrap uses Elementor's jQuery event bus ONLY for `elementor/frontend/init` subscription (Elementor's own API), not for DOM manipulation. |
| `wp_localize_script` for PHP→JS config | `data-*` attrs + CSS custom properties | Phase 4 D-15 (2026-06-13) | Per-instance config correctness for multi-instance; supersedes the literal JSCR-05 wording. |
| CSS `left`/`margin` for slide animations | CSS `transform: translateX()` + `opacity` | ~2015 baseline; standardized in CLAUDE.md | GPU-composited, 60fps on mobile (ANIM-04). |
| `transitionend` single-fire assumption | Filter on `propertyName` | Always true per spec | Required for correctness when 2 properties transition (Scale, Slide+Fade types). |

**Deprecated/outdated:**
- `role="menu"` on site navigation — replaced by `<nav aria-label>` (Phase 4 D-21 already correct).
- jQuery `.slideToggle()` etc. — replaced by CSS transitions (ANIM-04).
- `document.all` / `navigator.userAgent` browser detection — never used; feature detection only.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `elementorFrontend.isEditMode()` returns `true` inside the editor preview iframe and is the correct guard to skip interaction listeners. | Pitfall 3 | If the API name is wrong or behavior differs, listeners attach in the editor. Mitigation: also guard by checking `container.querySelector('[data-ddmm-drawer]')` exists (the editor preview emits no drawer). The fallback guard makes the assumption low-risk. |
| A2 | The four animation-type container classes (`ddmm-anim--slide/fade/scale/slidefade`) and the three panel states (`--active`, `--exited-left`, default off-stage-right) are sufficient to express all four animation types with cross-fade/overlap as specified in D-03. | Architecture Patterns | If the CSS matrix does not cleanly express one type (e.g., Scale needs a different default transform), the planner may need a per-type override of the DEFAULT (off-stage-right) state, not just the `--exited-left` state. The CSS example shows this pattern for Fade/Scale. |
| A3 | 200ms is the correct debounce interval for search. | Search Filtering | If users perceive lag, the interval can be lowered to 120-150ms. If the filter computation is heavy on very large menus, raise to 300ms. The value is a Claude's Discretion item (D-06) and easily tuned. |
| A4 | The WP `current-menu-item` class is applied server-side by WordPress core Walker and appears on the rendered `<li>` for the current page when the menu source is a WP menu. | URL-Match Normalization | If the class is absent (e.g., custom-builder source has no Walker), the URL match fallback handles it (D-14). Low risk. |
| A5 | The trigger button in `DrillDownMenu.php` does NOT currently carry `data-ddmm-trigger` (verified by reading lines 425-477). | Pitfall 1 | If I misread and the attribute IS present, the recommendation to add it is redundant (harmless). The safer querySelector `[data-ddmm-trigger], .ddmm-trigger` covers both cases. |

## Open Questions

1. **Should `will-change` be toggled off after `transitionend`?**
   - What we know: MDN warns against permanent `will-change`. Phase 4 sets it permanently on `.ddmm-panel`.
   - What's unclear: For a menu with ~5-20 panels, is the GPU memory cost meaningful?
   - Recommendation: Leave permanent for Phase 5 (small panel count; simpler code). If Phase 7 or later adds very large menus, revisit. Document in the Style Tab (Phase 6) constraint that shadows should avoid panels.

2. **Should auto-open animation be fully suppressed, or animate quickly?**
   - What we know: D-12 says "auto-drills down" — does not specify animated vs instant.
   - What's unclear: Instant (0ms) feels jarring; animated feels slow on open.
   - Recommendation: Instant (0ms override during auto-open, restored after) — matches the "orient the user" intent without delaying the open. The example code shows this pattern.

3. **Does `ddmm-search-active` class on the container hide the panels cleanly?**
   - What we know: D-06 says "drill-view is replaced by the results list".
   - What's unclear: Should the active panel remain visible (with results overlaid) or be hidden?
   - Recommendation: Hide the panels container (`.ddmm-panels { display: none }` when `.ddmm-search-active`) and show the results list. The search results list lives OUTSIDE `.ddmm-panels` (in the sticky search bar area or a sibling) so it is not affected by the panel hide.

## Sources

### Primary (HIGH confidence)
- Codebase: `assets/js/ddmm-frontend.js` (existing Phase 4 bootstrap, lines 1-120) — verified the init guard, dual-path wiring, and the Phase 5 listener stubs documented in comments.
- Codebase: `assets/css/ddmm-frontend.css` (Phase 4 base styles) — verified `--ddmm-transition-duration` exists at 300ms, `.ddmm-panel--active` and default `translateX(100%)` states, `.ddmm-widget.ddmm-is-open` drawer-open rule.
- Codebase: `src/Rendering/DrawerRenderer.php` — verified the full DOM contract: `data-ddmm-overlay`, `data-ddmm-drawer`, `data-ddmm-close`, `data-target`/`data-panel-id`/`data-back-target`, panel `aria-hidden`/`aria-labelledby`, chevron `aria-expanded`/`aria-controls`/`aria-label`, back-row title span.
- Codebase: `src/Elementor/Widget/DrillDownMenu.php` — verified the existing control sections (trigger, menu, drawer header, drawer settings) and the `render()` method structure. **Found Gap A1/Pitfall 1: trigger button has no `data-ddmm-trigger` attribute despite D-25 contract.**
- `.planning/phases/04-rendering-pipeline-drawer-html/04-CONTEXT.md` — D-01 split parent, D-15 data-*/CSS-var bridge, D-16 `.ddmm-widget` scope, D-25/D-26 hook-attribute + BEM state conventions, D-22/D-23 ARIA contract.
- `.planning/phases/05-frontend-drill-down-javascript/05-CONTEXT.md` — all 19 locked decisions D-01..D-19, Claude's Discretion items, deferred ideas.

### Secondary (MEDIUM confidence)
- [MDN — will-change CSS property](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/will-change) — `will-change` best practices (specific properties, not permanent, don't over-promote). [VERIFIED via WebSearch 2026-06-14]
- [MDN — transitionend event](https://developer.mozilla.org/en-US/docs/Web/API/Element/transitionend_event) — fires once per property; filter on `propertyName`. [VERIFIED via WebSearch]
- [WHATWG URL Standard](https://url.spec.whatwg.org/) — the `URL` and `URLSearchParams` API spec used for normalization. [VERIFIED via WebSearch 2026-06-14]
- [Stack Overflow — How long should you debounce text input](https://stackoverflow.com/questions/42361485/how-long-should-you-debounce-text-input) — 250ms upper bound, human reaction time benchmark. [VERIFIED via WebSearch]
- [Elementor JS Hooks — developers.elementor.com](https://developers.elementor.com/docs/hooks/js/) — `elementorFrontend.isEditMode()` and the `frontend/element_ready/{widget}.default` hook. [VERIFIED via WebSearch]

### Tertiary (LOW confidence)
- [Medium — When to Use will-change and When to Step Away](https://dvmhn07.medium.com/when-to-use-will-change-and-when-to-step-away-from-it-780753e6e796) — practical add/remove timing guidance. Single source; flagged for validation but consistent with MDN.
- The specific debounce value of 200ms (between the 250ms SO benchmark and the 300ms Atom fuzzy-finder benchmark) is an interpolation, not a single cited source. Flagged as Assumption A3.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all native browser APIs, verified in MDN/caniuse; no external libraries.
- Architecture (state machine + animation types): HIGH — derived from the locked decisions D-01..D-05 plus the Phase 4 base CSS; the container-class-switch pattern is the canonical GPU-compositing approach.
- Close behaviors + URL matching: HIGH — follows directly from D-14..D-19; URL normalization uses the native WHATWG `URL` API.
- Search filtering: HIGH for the index/filter mechanics; MEDIUM for the specific debounce interval (Claude's Discretion; 200ms is a reasonable interpolation).
- Elementor editor-mode guard: MEDIUM — `elementorFrontend.isEditMode()` verified via WebSearch but not tested in this codebase. Mitigated by the null-query fallback guard (Pitfall 3).
- Validation approach: MEDIUM — relies on `node --check` + grep + manual browser verification; no automated JS test harness exists in the project (consistent with Phase 4's VALIDATION.md approach).

**Research date:** 2026-06-14
**Valid until:** 2026-07-14 (30 days — stable domain; browser APIs and CSS patterns do not change fast)
