---
phase: 07-accessibility-compatibility-polish
plan: 01
subsystem: ui
tags: [a11y, keyboard-navigation, focus-management, aria-live, roving-tabindex, es6, wordpress]

# Dependency graph
requires:
  - phase: 04-rendering-pipeline-drawer-html
    provides: "DOM contract (data-ddmm-* hooks, BEM classes, panel markup, .screen-reader-text class)"
  - phase: 05-frontend-drill-down-javascript
    provides: "DrillDownMenu class with open()/close()/drill()/back()/autoOpenCurrentPath() + D-19 single close path"
provides:
  - "Empty aria-live panel-context region (data-ddmm-sr-status) emitted in DrawerRenderer::render()"
  - "wireKeyboard() family: per-container keyboard handlers (Esc, Tab trap, Arrow roving, focus move/restore, aria-live writes)"
  - "Focus move on open + restore on close routed through D-19 single close path"
  - "Per-container scope discipline (contains() guard) for all document-level handlers"
affects: [07-02, 07-04, 08-verification]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Roving tabindex (WAI-ARIA APG): one item per panel holds tabindex=0, others -1; ArrowUp/ArrowDown roam with wrap-around + full reset before set (Pitfall 3)"
    - "Tab trap lifecycle: document-level keydown attaches on open(), detaches on close(); same handler reference stored on this.docHandler for removal (Pitfall 2)"
    - "Esc coordination: global handler early-returns when search input has focus (document.activeElement === searchInput) so the existing Phase 5 listener clears+blurs first (Pitfall 1)"
    - "aria-live writes via textContent ONLY (ASVS V5, Threat T-07-01-01); region emitted EMPTY at PHP render time (Pitfall 7)"

key-files:
  created: []
  modified:
    - "src/Rendering/DrawerRenderer.php (render() emits aria-live region at line 73)"
    - "assets/js/ddmm-frontend.js (+10 new methods, +6 hook points; 640 -> 840 lines)"

key-decisions:
  - "Drawer-scoped keydown for ArrowUp/ArrowDown only; Tab trap + Esc are document-level (attach on open, detach on close)"
  - "Native <a>/<button> activation handles Enter/Space — no synthetic preventDefault (D-04, A11Y-07)"
  - "announcePanelContext() writes back-row title for child panels, nav aria-label for root"
  - "lastTrigger captured at top of close() (before cleanup) so focus restores even if querySelector would miss during teardown"

patterns-established:
  - "Phase 7 keyboard handler family: wireKeyboard / onDrawerKeydown / attachDocListeners / detachDocListeners / onDocKeydown / trapTab / getFocusables / moveRoving / focusInitialTarget / announcePanelContext"
  - "Per-container scope guard: every document-level handler first statement is if (!this.container.contains(e.target)) return; (Anti-Pattern 3)"
  - "tabindex reset pattern: items.forEach(el => el.tabIndex = -1) before setting target.tabIndex = 0 (Pitfall 3)"

requirements-completed: [A11Y-04, A11Y-05, A11Y-06, A11Y-07, A11Y-08]

# Metrics
duration: 3min
completed: 2026-06-14
---

# Phase 7 Plan 01: Keyboard Navigation + Focus Management + aria-live Summary

**Per-container keyboard handlers (Esc back-then-close, Tab trap, Arrow roving, native Enter/Space activation), focus move/restore through the Phase 5 single close() path, and a polite aria-live panel-context region — all wiring verified by node --check + 20 acceptance greps**

## Performance

- **Duration:** 3 min
- **Started:** 2026-06-14T16:21:04Z
- **Completed:** 2026-06-14T16:24:09Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- Empty `aria-live="polite" aria-atomic="true"` panel-context region (`data-ddmm-sr-status`) emitted in `DrawerRenderer::render()` between the search box and `<nav>` — ready for JS textContent writes (A11Y-08/D-08).
- Ten new methods on `DrillDownMenu`: `wireKeyboard`, `onDrawerKeydown`, `attachDocListeners`, `detachDocListeners`, `onDocKeydown`, `trapTab`, `getFocusables`, `moveRoving`, `focusInitialTarget`, `announcePanelContext`.
- Esc precedence (D-02): back one level first via existing `back()`, then close via existing `close()` — coordinates with the pre-existing search-input Esc listener via the `document.activeElement === searchInput` early-return (Pitfall 1).
- Tab trap (A11Y-05): document-level keydown attaches on `open()`, detaches on `close()`; same `this.docHandler` reference removed (Pitfall 2); `offsetParent !== null` filter excludes hidden search results (Pitfall 6).
- Roving tabindex (A11Y-06/D-11): ArrowUp/ArrowDown move `tabindex=0` among sibling items in the active panel with wrap-around; full reset to `-1` before each set (Pitfall 3).
- Focus move/restore (A11Y-08/D-03/D-07): on `open()` focus lands on the D-03 target (auto-opened current item else first focusable); on `close()` the trigger captured at the top of `close()` receives `.focus()`.
- aria-live panel-context writes (D-08) on open/drill/back/Esc-back — `textContent` only, never `innerHTML` (ASVS V5, Threat T-07-01-01 mitigation).

## Task Commits

Each task was committed atomically:

1. **Task 07-01-01: Emit empty aria-live panel-context region in DrawerRenderer** - `468c2e7` (feat)
2. **Task 07-01-02: Add keyboard handlers, focus management, roving tabindex, aria-live writes** - `83ced27` (feat)

## Files Created/Modified
- `src/Rendering/DrawerRenderer.php` — added one `printf()` inside `render()` (line 73) emitting `<div class="screen-reader-text" data-ddmm-sr-status aria-live="polite" aria-atomic="true"></div>`. Frontend path only; `render_editor_preview()` untouched. D-09 search results `<ul aria-live="polite" aria-relevant="additions">` unchanged (still exactly 1 match).
- `assets/js/ddmm-frontend.js` — added 10 new methods to the `DrillDownMenu` class + 6 hook points in `init()`/`open()`/`close()`/`drill()`/`back()`. File grew from 640 to 840 lines (+200).

## Decisions Made
- Followed the plan's recommendation to place all keyboard methods after `wireCloseBehaviors()` and before `normalizeUrl()` — keeps the related methods visually grouped.
- `lastTrigger` is captured at the very top of `close()` (before `classList.remove('ddmm-is-open')`) so the querySelector runs against the still-open DOM; this guarantees the trigger reference exists even if any future cleanup step mutates the container.
- `announcePanelContext()` falls back to `navLabel || ''` (empty string) when neither back-row title nor nav label is available — never writes `null`/`undefined` to the live region.
- Left the existing search-input Esc listener (now at line 648, originally line 447) byte-for-byte unchanged — the new `onDocKeydown` coordinates with it via the `document.activeElement === searchInput` check rather than modifying it.

## Deviations from Plan

None - plan executed exactly as written. Both tasks followed the `<action>` steps verbatim. All code landed at the recommended insertion points (init fields after `searchTimer`, `wireKeyboard()` call after the searchOn block, methods after `wireCloseBehaviors()`, hooks at the end of `open()`/`drill()`/`back()` and top+bottom of `close()`).

## Issues Encountered
None. Both `php -l` and `node --check` passed on the first run. All 20 acceptance wiring greps passed on the first attempt with counts meeting or exceeding the thresholds.

## Exact Line Numbers (for next plan reference)

### New methods in DrillDownMenu class (assets/js/ddmm-frontend.js)
- `wireKeyboard()` — line 307
- `onDrawerKeydown( e )` — line 319
- `attachDocListeners()` — line 333
- `detachDocListeners()` — line 343
- `onDocKeydown( e )` — line 355
- `trapTab( e )` — line 388
- `getFocusables()` — line 407
- `moveRoving( direction )` — line 422
- `focusInitialTarget()` — line 446
- `announcePanelContext()` — line 468

### Instance fields (init())
- `this.lastTrigger = null;` — line 46
- `this.docHandler = null;` — line 48
- `this.wireKeyboard();` call — line 70

### Hook points
- `open()`: `attachDocListeners + focusInitialTarget + announcePanelContext` at lines 130-132
- `close()`: `lastTrigger` capture at line 140 (top); `detachDocListeners + lastTrigger.focus()` at lines 156-158 (bottom)
- `drill()`: `focusInitialTarget + announcePanelContext` at lines 243-244 (end)
- `back()`: `focusInitialTarget + announcePanelContext` at lines 277-278 (end)

### DrawerRenderer.php
- aria-live region printf — line 73 (inside `render()`, frontend only)

## Acceptance Criteria — All Passed at Commit Time

### Task 07-01-01
- `php -l src/Rendering/DrawerRenderer.php` exits 0 ✓
- `grep -cE "data-ddmm-sr-status"` = 1 ✓
- Exact empty-region markup present ✓
- Region is empty at emission (`></div>` immediately after attributes) ✓
- D-09 search region untouched (exactly 1 match) ✓
- Region appears only in `render()` at line 73 (inside method body 42-78+), NOT in `render_editor_preview()` ✓

### Task 07-01-02
- `node --check assets/js/ddmm-frontend.js` exits 0 ✓
- All 20 wiring greps pass (wireKeyboard=2, onDrawerKeydown=2, ArrowDown|ArrowUp=5, attachDocListeners=3, detachDocListeners=4, this.docHandler=6, removeEventListener 'keydown'=1, trapTab=2, getFocusables=2, offsetParent=1, moveRoving=2, focusInitialTarget=5, announcePanelContext=5, data-ddmm-sr-status=1, contains(e.target)=1, activeElement===searchInput=1, lastTrigger=4, tabIndex=-1=2, tabIndex=0=2, status.textContent=1) ✓
- No new `innerHTML` writes (1 grep match is a comment in the docblock, not a write) ✓
- No `preventDefault()` added for Enter or Space ✓
- Existing search-input Esc listener byte-for-byte unchanged ✓

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Keyboard + focus + aria-live wiring complete and verified statically.
- Live keyboard behavior (Esc flows, Tab wrap, Arrow roving, focus move/restore, SR announcements) is deferred to 07-HUMAN-UAT.md tests #1-#8, authored in Plan 07-04 Task 07-04-02.
- Plan 07-02 can proceed: adds the `:focus-visible` ring (D-10), `prefers-reduced-motion` block (D-18), and RTL logical-property refactor (D-19) to `ddmm-frontend.css`.
- Plan 07-03 can proceed: i18n packaging (`.pot` generation, `load_plugin_textdomain`, `wp_set_script_translations`, fix the `'No results'` JS literal).

---
*Phase: 07-accessibility-compatibility-polish*
*Plan: 01*
*Completed: 2026-06-14*

## Self-Check: PASSED

- src/Rendering/DrawerRenderer.php — FOUND
- assets/js/ddmm-frontend.js — FOUND
- .planning/phases/07-accessibility-compatibility-polish/07-01-SUMMARY.md — FOUND
- Commit 468c2e7 (Task 07-01-01) — FOUND
- Commit 83ced27 (Task 07-01-02) — FOUND
- data-ddmm-sr-status in PHP — 1 match
- wireKeyboard in JS — 2 matches
- php -l — no syntax errors
- node --check — JS OK
