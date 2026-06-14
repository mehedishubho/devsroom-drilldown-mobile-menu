---
phase: 07-accessibility-compatibility-polish
reviewed: 2026-06-14T13:00:00Z
depth: standard
files_reviewed: 7
files_reviewed_list:
  - src/Rendering/DrawerRenderer.php
  - assets/js/ddmm-frontend.js
  - assets/css/ddmm-frontend.css
  - src/Plugin.php
  - src/Assets/Registrar.php
  - devsroom-drilldown-mobile-menu.php
  - languages/devsroom-drilldown-mobile-menu.pot
findings:
  critical: 0
  warning: 6
  info: 7
  total: 13
status: issues_found
---

# Phase 7: Code Review Report

**Reviewed:** 2026-06-14T13:00:00Z
**Depth:** standard
**Files Reviewed:** 7
**Status:** issues_found

## Summary

Phase 7 (Accessibility & Compatibility Polish) lands solid a11y foundations: drawer-scoped roving tabindex, document-level Tab trap + Esc back-then-close with per-container scoping, focus move/restore across `open()/drill()/back()`, `aria-live` panel-context announcements written via `textContent`, `:focus-visible` rings on all six BEM surfaces, `prefers-reduced-motion` neutralization (correctly using `0.01ms` so `transitionend` still fires), RTL logical-property baseline, and a `wp_json_encode`-only `window.ddmmI18n` bridge. Output injection hygiene (ASVS V5) is consistently respected: no `innerHTML` on user data, all PHP echo paths escape via `esc_attr/esc_html/esc_url`, and `wp_json_encode` is used for the inline script payload.

No Critical issues found. The Warnings cluster around three themes worth tightening before release:

1. **Focus-management robustness** — focus trap/restore can silently no-op if the trigger loses visibility (`offsetParent === null`) mid-session, and `focusInitialTarget()` can throw if the active panel somehow contains no focusable items but still has a `.ddmm-current-item` element whose `> a` query returns `null`.
2. **Esc coordination** — the search-input Esc listener at `wireSearch()` line 647 handles `Escape` before `onDocKeydown`, but it neither calls `e.stopPropagation()` nor `e.preventDefault()`, so on a system where the drawer's document listener fires first (or in capture phase elsewhere), the first Esc from a search input could be observed by both handlers. Defensive coordination is worth adding.
3. **i18n consistency** — the `'No results'` literal has a hardcoded English fallback on the JS side that bypasses the translation pipeline, and the `.pot` is missing entries for two editor-only strings (`'Parent Item'` and `'Show submenu'` appears once but `'Show %s submenu'` should also surface the editor's standalone `Show submenu` usage — it is present, OK).

The Info findings are conventional polish: dead `const self = this`, single-use `lastTrigger` redundancy with a fresh query, missing `<button>` type annotations on the editor-preview chevron (already has it — re-checked, OK), and a few BEM/consistency nits.

All recommendations below include concrete fixes.

## Critical Issues

_(None.)_

## Warnings

### WR-01: `focusInitialTarget()` can throw if `.ddmm-current-item > a` returns null in a degenerate panel

**File:** `assets/js/ddmm-frontend.js:454-459`
**Issue:** `panel.querySelector( '.ddmm-current-item > a' )` may return `null` when a panel contains a `<li class="ddmm-current-item">` whose child anchor is absent (e.g., a future "divider" or non-link current marker). The line `target = current || items[0]` correctly falls back to `items[0]` — but then `target.tabIndex = 0; target.focus();` runs unconditionally. The guard at line 452 (`if ( ! items.length ) return;`) protects against an empty list, so this is currently safe. **However**, the same method is invoked from three call sites (`open()`, `drill()`, `back()`, and the Esc-back branch) and the contract `items.length > 0` is the only thing preventing a crash. If `items[0]` is later refactored or a panel emits items without anchors, the silent assumption becomes a TypeError. More importantly, the method does not guard against the panel being detached (`.ddmm-panel--active` removed by a race) between the `panel` query at line 447 and the `items` query at line 450 — `panel.querySelectorAll` on a detached node returns `[]`, which the existing guard handles, so this is defensively OK but fragile.
**Fix:** Make the no-op path explicit and document the precondition so future refactorers don't strip the guard:
```javascript
focusInitialTarget() {
    const panel = this.container.querySelector( '.ddmm-panel--active' );
    if ( ! panel ) return;
    const items = Array.from(
        panel.querySelectorAll( '.ddmm-menu > li > a, .ddmm-menu .ddmm-chevron' )
    );
    // Defensive: callers (open/drill/back/Esc) all assume focus moved into the
    // drawer. If the active panel has zero focusable items, leave focus where it
    // is rather than crashing — better a stale focus than a TypeError.
    if ( ! items.length ) return;
    const current = panel.querySelector( '.ddmm-current-item > a' );
    const target = current || items[ 0 ];
    items.forEach( ( el ) => { el.tabIndex = -1; } );
    target.tabIndex = 0;
    target.focus();
}
```

### WR-02: Tab-trap `offsetParent` filter is unreliable for fixed/zero-size elements and for `position: fixed` drawer contents

**File:** `assets/js/ddmm-frontend.js:413`
**Issue:** `Array.from( all ).filter( ( el ) => el.offsetParent !== null )` is documented as filtering out `display:none` (Pitfall 6). This works for the common case, but `offsetParent` returns `null` for elements with `position: fixed` (and their descendants in some browsers), as well as for elements with `display: contents`. The `.ddmm-drawer` is `position: fixed`, so any fixed-position child (or a button inside one) could be wrongly excluded from the focusable list even when it is visible and interactive. This is a latent correctness risk if a future Style Tab adds a fixed footer/close button inside the drawer. The safer filter is `getClientRects().length > 0` or `checkVisibility()` (Chrome 105+, Safari 16.4+, Firefox 119+ — all within the plugin's documented browser baseline of Chrome 90+/iOS 16.5+/FF 117+; `checkVisibility` is borderline on FF 117 but `getClientRects()` works everywhere).
**Fix:** Use `getClientRects().length` for the visibility filter, which is reliable for fixed elements:
```javascript
getFocusables() {
    const all = this.container.querySelectorAll(
        '[data-ddmm-close], [data-back-target], [data-ddmm-search-input], ' +
        '.ddmm-panel--active .ddmm-menu > li > a, ' +
        '.ddmm-panel--active .ddmm-menu .ddmm-chevron'
    );
    // Pitfall 6: filter out display:none (search results hidden unless
    // .ddmm-search-active). getClientRects() is reliable for position:fixed
    // descendants where offsetParent would wrongly return null.
    return Array.from( all ).filter( ( el ) => el.getClientRects().length > 0 );
}
```

### WR-03: Search-input Esc listener does not stop propagation, risking double-handling with the document-level Esc handler

**File:** `assets/js/ddmm-frontend.js:647-653` and `assets/js/ddmm-frontend.js:359-365`
**Issue:** The search input's Esc listener (line 647) clears the query and blurs the input. The document-level Esc handler (`onDocKeydown`, line 359) defensively checks `document.activeElement === searchInput` and returns early if the search input still has focus — but only on the SAME keypress. The two listeners are both registered on different targets (`input` vs `document`), and without `e.stopPropagation()` the keydown event bubbles from the input to the document. In the current ordering the input handler fires first (capture vs target/bubble phase) and the document handler's guard at line 363 catches the same press — so today this works. The risk is fragility: any future capture-phase listener added higher in the tree would reverse the ordering and cause the document handler to run first, bypassing the search-input guard. Making the contract explicit removes the implicit ordering dependency.
**Fix:** Add `e.stopPropagation()` after clearing so the document handler never sees this Esc press:
```javascript
input.addEventListener( 'keydown', ( e ) => {
    if ( e.key === 'Escape' ) {
        e.stopPropagation();          // own this Esc; don't bubble to onDocKeydown
        input.value = '';
        this.clearSearch();
        input.blur();
    }
} );
```

### WR-04: Focus restore calls `.focus()` on the trigger without checking visibility — can throw or silently no-op when the trigger is hidden

**File:** `assets/js/ddmm-frontend.js:157-159`
**Issue:** `this.lastTrigger.focus()` runs unconditionally after `detachDocListeners()`. If the trigger was hidden via CSS (e.g., the widget is in a hero section that the user scrolled past on desktop where `display:none` kicks in at a breakpoint, or the trigger is inside an Elementor hidden-device toggle), `.focus()` on a hidden element either throws (Firefox, for some cases) or silently no-ops, and either way leaves focus stranded on `document.body`. The `lastTrigger` null-check exists but the visibility check does not. The phase-context explicitly calls out "lastTrigger captured before teardown" as a focus-area; the capture is correct, the restore is unguarded.
**Fix:** Guard the restore against a hidden trigger; fall back to `document.body` so focus is never stranded:
```javascript
this.detachDocListeners();
if ( this.lastTrigger && this.lastTrigger.getClientRects().length > 0 ) {
    this.lastTrigger.focus();
} else {
    document.body.focus(); // graceful degradation: don't strand focus on nothing
}
```

### WR-05: `moveRoving` early-returns when `items.length < 2`, leaving single-item panels without a focused element after arrow-key press

**File:** `assets/js/ddmm-frontend.js:428`
**Issue:** `if ( items.length < 2 ) return;` means a panel with exactly one menu item (e.g., a leaf submenu with one child link) does not get its roving tabindex set when the user presses ArrowDown/ArrowUp. `focusInitialTarget()` already handles setting `tabIndex=0` on open/drill/back, so the single-item case is covered on entry — but if the user arrow-keys away from a multi-item panel into a single-item panel, the arrow handler silently no-ops and the user's arrow keypress appears "dead." Screen-reader users relying on arrow navigation in this case get no feedback. The `< 2` guard is also a latent off-by-one: with `items.length === 1`, the modulo arithmetic in line 431 already handles it correctly (`nextIdx` resolves to 0), so the early return is unnecessary.
**Fix:** Lower the guard to `< 1` (i.e., only skip when the panel is truly empty) and let the modulo handle the single-item case:
```javascript
if ( items.length < 1 ) return;
```

### WR-06: `wp_set_script_translations` path computation may fail when the plugin is symlinked (e.g., `bedrock`/`wp-content/plugins/<plugin>` → composer-installed source)

**File:** `src/Assets/Registrar.php:43`
**Issue:** `plugin_dir_path( dirname( __DIR__, 2 ) . '/devsroom-drilldown-mobile-menu.php' ) . 'languages'` assumes the main plugin file is exactly two directories up from `src/Assets/`. This holds for the standard layout, but `plugin_dir_path()` on a symlinked plugin (common in Bedrock/Composer deployments) returns the resolved path which may not be where `languages/` physically lives. The conventional and robust pattern is `dirname( plugin_dir_path( __FILE__ ), 3 ) . '/languages'` or, better, use `plugin_dir_path( dirname( __FILE__, 3 ) )` directly anchored on `__FILE__`. This matches the `Plugin.php` approach (which uses `plugin_basename( __FILE__ )` correctly).
**Fix:** Anchor the path on the Registrar's own `__FILE__` and walk up three levels:
```php
wp_set_script_translations(
    'ddmm-frontend',
    'devsroom-drilldown-mobile-menu',
    dirname( plugin_dir_path( __FILE__ ), 2 ) . '/languages'
);
```
`__FILE__` is `src/Assets/Registrar.php`; `plugin_dir_path(__FILE__)` = `src/Assets/`; `dirname(..., 2)` = plugin root; append `/languages`. This is symlink-safe because `__FILE__` resolves to the actual file location.

## Info

### INFO-01: Dead `const self = this;` assignment left over from a pre-arrow refactor

**File:** `assets/js/ddmm-frontend.js:236`
**Issue:** `const self = this; // eslint-disable-line no-unused-vars` is never used — the `transitionend` callback below it is a `function` expression but reads only `ev` and `outgoing`, never `self`. The eslint-disable comment is papering over a dead binding. This is dead code (out of v1 scope for performance, but it is a quality issue the review scope explicitly covers).
**Fix:** Remove line 236 entirely:
```javascript
// Scroll incoming to top; reset outgoing after transitionend (Pitfall 4).
incoming.scrollTop = 0;
outgoing.addEventListener( 'transitionend', function onEnd( ev ) {
    if ( ev.propertyName !== 'transform' ) return;
    outgoing.removeEventListener( 'transitionend', onEnd );
    outgoing.scrollTop = 0;
} );
```

### INFO-02: `close()` queries the trigger twice (once into `this.lastTrigger`, once into a local `trigger`)

**File:** `assets/js/ddmm-frontend.js:139-146`
**Issue:** Line 140 sets `this.lastTrigger = this.container.querySelector(...)`, then line 142 re-queries the same selector into a local `trigger` and uses it for `classList`/`aria` manipulation. The two queries return the same element. The redundancy is harmless but adds DOM cost on every close and obscures intent.
**Fix:** Reuse the captured reference:
```javascript
close() {
    this.lastTrigger = this.container.querySelector( '[data-ddmm-trigger], .ddmm-trigger' );
    this.container.classList.remove( 'ddmm-is-open' );
    if ( this.lastTrigger ) {
        this.lastTrigger.classList.remove( 'ddmm-trigger--active' );
        this.lastTrigger.setAttribute( 'aria-expanded', 'false' );
    }
    // ...rest unchanged
}
```

### INFO-03: `.pot` is missing the `'Parent Item'` string emitted in `render_editor_preview()`

**File:** `languages/devsroom-drilldown-mobile-menu.pot:1-62` vs `src/Rendering/DrawerRenderer.php:510-513`
**Issue:** `render_editor_preview()` emits `esc_html__( 'Parent Item', 'devsroom-drilldown-mobile-menu' )` at line 512, but the `.pot` has no entry for `'Parent Item'`. The only sample-item entry is `'Sample Menu Item'`. Translators cannot localize this string because it is not catalogued. The other editor string `'Show submenu'` is present (line 53), but `'Parent Item'` is absent.
**Fix:** Regenerate the `.pot` (or hand-add the missing entry):
```
#: src/Rendering/DrawerRenderer.php
msgid "Parent Item"
msgstr ""
```

### INFO-04: `'No results'` fallback string bypasses the i18n pipeline

**File:** `assets/js/ddmm-frontend.js:710`
**Issue:** `( window.ddmmI18n && window.ddmmI18n.noResults ) || 'No results'` falls back to a hardcoded English literal. This is defensible (the comment says "defensive — never crash"), but it does mean a site with a broken/missing inline-script injection shows English "No results" even after a translator provided a translation. The translator cannot override the JS fallback. This is acceptable for the defensive case but worth documenting as a known limitation; if a fully-translated fallback is desired, the fallback should be removed entirely (let `undefined` render nothing or a non-text icon) so the failure mode is visible rather than silently English.
**Fix:** (Optional) Either drop the fallback to surface the bug, or leave as-is and add a code comment noting the trade-off. No code change required if the trade-off is intentional.

### INFO-05: `printf` with `aria-hidden` boolean attribute emits `aria-hidden=""` on the overlay (technically valid but inconsistent)

**File:** `src/Rendering/DrawerRenderer.php:50`
**Issue:** `printf( '<div class="ddmm-overlay" data-ddmm-overlay aria-hidden="true"></div>' );` — `aria-hidden="true"` is correctly quoted here (good). But the same line uses `printf` with zero format placeholders; a plain `echo` is clearer and matches the pattern used elsewhere in the file (e.g., line 86 `echo '</div></nav></div>';`). Trivial consistency nit.
**Fix:** Use `echo` for zero-arg output:
```php
echo '<div class="ddmm-overlay" data-ddmm-overlay aria-hidden="true"></div>';
```

### INFO-06: `getFocusables()` selector omits the trigger button, which is correct, but the comment doesn't explain why

**File:** `assets/js/ddmm-frontend.js:407-414`
**Issue:** The focusable selector correctly excludes `[data-ddmm-trigger]` because the trigger sits OUTSIDE the drawer (in `.ddmm-widget` but not inside `[data-ddmm-drawer]`), and the Tab trap should keep focus inside the drawer. The comment says "query inside this.container only" but doesn't explicitly call out the deliberate trigger exclusion. Future readers might "fix" this by adding the trigger and break the trap.
**Fix:** Add a one-line comment documenting the exclusion:
```javascript
getFocusables() {
    // NOTE: trigger ([data-ddmm-trigger]) is deliberately excluded — it lives
    // outside the drawer; including it would let Tab leak past the trap boundary.
    const all = this.container.querySelectorAll( ... );
    ...
}
```

### INFO-07: `load_plugin_textdomain()` is called inside `init()` which fires on `plugins_loaded` — this is correct, but the hook timing means the registrar's inline-script string is localized on `wp_enqueue_scripts` (later) which is fine — no bug, but the cross-hook flow is non-obvious

**File:** `src/Plugin.php:75-79` and `src/Assets/Registrar.php:53`
**Issue:** `load_plugin_textdomain` runs on `plugins_loaded` (via `init()`), and the `wp_add_inline_script` in `Registrar::register()` runs on `wp_enqueue_scripts`. The latter fires AFTER the former, so `__( 'No results', ... )` in the registrar correctly sees the loaded text domain. This works, but the ordering dependency is implicit across two files. No fix required — documenting for the next maintainer.

---

_Reviewed: 2026-06-14T13:00:00Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_
