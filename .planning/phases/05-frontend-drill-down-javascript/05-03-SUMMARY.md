---
phase: 05-frontend-drill-down-javascript
plan: 03
subsystem: ui
tags: [css, animation, gpu-compositing, search-box, hamburger-morph, css-custom-properties, native-nesting]

# Dependency graph
requires:
  - phase: 05-frontend-drill-down-javascript
    plan: 01
    provides: ddmm-anim--{type} container class hook, --ddmm-transition-duration/easing inline CSS vars on .ddmm-widget
provides:
  - Four animation-type container-class blocks (ddmm-anim--slide/fade/scale/slidefade) with exact D-03 transform/opacity values for all three panel states
  - Third panel state (ddmm-panel--exited-left) for drill-past visual — Phase 4 shipped only active + off-stage-right
  - --ddmm-transition-easing consumption by all five base transition declarations (ANIM-03)
  - .ddmm-panel base opacity:0 / .ddmm-panel--active opacity:1 defaults so Fade/Scale types resolve correctly
  - Hamburger->X morph via three :nth-child rules toggled by .ddmm-trigger--active
  - Search box layout (.ddmm-search, __input, __results, __result-item, __result, __result-title, __result-breadcrumb, __no-results)
  - D-06 panels-hide-when-search-active rule (.ddmm-search-active .ddmm-panels { display: none })
  - EXTR-03 current-item / current-ancestor marker classes (visible hint before Phase 6 Style Tab)
affects: [05-04-frontend-js-interaction]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Container-class-switch animation: one class on .ddmm-widget redefines what the three stable panel-state classes resolve to (transform/opacity). JS never sets inline transform styles."
    - "GPU-compositing guardrail: every transition is on transform/opacity/visibility/background/color only — a negative grep for left|top|width|margin|padding in transition shorthand returns 0 (ANIM-04)"
    - "Three-state panel model: active (in-place) / exited-left (drilled past) / off-stage-right (default pending) — CSS-driven, JS only toggles state classes"
    - "calc()-based hamburger morph: translateY by (line-height + gap) then rotate, using existing --ddmm-hamburger-* vars — no magic numbers"

key-files:
  created: []
  modified:
    - assets/css/ddmm-frontend.css

key-decisions:
  - "Four animation types expressed via container-class-switch pattern (D-04): .ddmm-widget.ddmm-anim--{type} prefix redefines the transform/opacity values each panel state class resolves to. Type-prefixed rules win over base states by specificity (more class selectors)."
  - "Phase 5 adds the THIRD panel state (ddmm-panel--exited-left) — Phase 4 only shipped active + off-stage-right. Without this, drill-in would have nowhere to send the outgoing panel."
  - "Fade and Scale types set pointer-events:none on non-active panels so the invisible panel never intercepts taps during cross-fade."
  - "Scale off-stage uses scale(0.92) and exited-left uses scale(0.96) per D-03 — the incoming child zooms UP from 92%, the outgoing parent recedes to 96%."
  - "Search-active hides .ddmm-panels entirely (display:none) rather than overlaying results — D-06 flat-list replaces drill view, and the results ul lives inside .ddmm-search so it survives the panels hide."
  - "Hamburger->X morph uses calc( var(--ddmm-hamburger-line-height) + var(--ddmm-hamburger-line-gap) ) for translateY so the morph tracks the Phase 1 vars — no hardcoded pixel values."

patterns-established:
  - "CSS contract for JS: Plan 04 JS toggles only panel state classes (ddmm-panel--active, ddmm-panel--exited-left) + container classes (ddmm-is-open, ddmm-trigger--active, ddmm-search-active). CSS drives 100% of motion via GPU-composited transform/opacity."
  - "Verification guardrail: ANIM-04 negative grep (transition:[^;]*(left|top|width|margin|padding) = 0) is a hard invariant — any new transition that animates a layout property breaks GPU compositing and must be rejected."

requirements-completed: [ANIM-01, ANIM-02, ANIM-03, ANIM-04]

# Metrics
duration: 4min
completed: 2026-06-13
---

# Phase 5 Plan 3: Animation CSS Summary

**Four animation-type container-class blocks (slide/fade/scale/slidefade) with the new exited-left panel state, --ddmm-transition-easing consumed by every transition, hamburger->X morph, and search box layout — the full visual layer for Phase 5 interaction**

## Performance

- **Duration:** 4 min
- **Started:** 2026-06-13T19:55:51Z
- **Completed:** 2026-06-13T19:58:12Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments

- All five base transition declarations (trigger / hamburger / overlay / drawer / panel) now consume `var( --ddmm-transition-easing )` instead of hardcoded `ease`, so Plan 01's `--ddmm-transition-easing` inline override actually takes effect (ANIM-03). The `--ddmm-transition-easing: ease` custom property is declared in the `.elementor-widget-ddmm-drilldown-menu` var block as the fallback.
- `.ddmm-panel` now has `opacity: 0` on the base state and `opacity: 1` on `.ddmm-panel--active` — the Phase 4 base had no opacity default, so Fade and Scale types (which rely on opacity transitions) would not have animated correctly.
- The new `.ddmm-panel--exited-left` state class exists as both a default rule (Slide-equivalent: translateX -100%, opacity 1) and as per-type overrides inside each animation-type block. This is the THIRD panel state Phase 5 adds (Phase 4 only shipped active + off-stage-right).
- Four animation-type container-class blocks (`.ddmm-widget.ddmm-anim--slide/fade/scale/slidefade`) each define the three panel states (off-stage-right / active / exited-left) with the exact D-03 transform/opacity values. Plan 04 JS will only ever toggle panel state classes — CSS resolves them per the active animation type. Specificity guarantees the type-prefixed rules win over the base states.
- Hamburger->X morph: three `:nth-child` rules on `.ddmm-trigger--active .ddmm-hamburger__line` rotate the top line +45deg, fade the middle line to opacity 0, and rotate the bottom line -45deg. The translateY uses `calc( var(--ddmm-hamburger-line-height) + var(--ddmm-hamburger-line-gap) )` so the morph tracks the Phase 1 vars without magic numbers.
- Search box layout styles cover the sticky bar (`.ddmm-search`), input (`.ddmm-search__input` + focus state), results list (`.ddmm-search__results` with max-height capped to viewport minus header), result items with title + breadcrumb, and a no-results state. The D-06 panels-hide-when-search-active rule (`.ddmm-search-active .ddmm-panels { display: none }`) replaces the drill view with the results list.
- Current-item and current-ancestor marker classes provide a visible hint for EXTR-03 auto-open observability before Phase 6 Style Tab adds full Active styling.

## Task Commits

| Task | Name | Commit | Files modified |
|------|------|--------|----------------|
| 1 | Update base transitions to consume --ddmm-transition-easing + add panel opacity defaults | `720710a` | assets/css/ddmm-frontend.css |
| 2 | Add four animation-type class hooks + exited-left panel state | `60ddfba` | assets/css/ddmm-frontend.css |
| 3 | Add hamburger->X morph, search box styles, and search-active panel hide | `0f1fef5` | assets/css/ddmm-frontend.css |

## Files Created/Modified

- `assets/css/ddmm-frontend.css` — Three additive sections appended at the end of the file, plus a one-line `--ddmm-transition-easing: ease;` declaration in the var block and 5 in-place `ease` -> `var( --ddmm-transition-easing )` swaps in the existing transition declarations. The `.ddmm-panel` rule gained `opacity: 0` on base and `opacity: 1` on `.ddmm-panel--active`. Net: +201 lines, -9 lines (326 -> 518 lines). No existing rules were replaced; all edits are additive or in-place value swaps.

## Decisions Made

- **Container-class-switch is the only animation mechanism.** A single class on `.ddmm-widget` (e.g. `.ddmm-anim--fade`) redefines what all three panel state classes resolve to. JS never sets inline `style.transform` (which would fight the CSS transition per RESEARCH Anti-Pattern 1). Plan 04 JS will toggle only panel state classes + the one-time animation-type class on init.
- **Fade and Scale add `pointer-events: none` to non-active panels.** During a cross-fade, the outgoing panel is still in the DOM at `translateX(0)`. Without `pointer-events: none`, an invisible panel could intercept taps meant for the active panel. Slide/Slide+Fade don't need this because the outgoing panel is off-stage (`translateX(-100%)`).
- **Scale uses 0.92 off-stage and 0.96 exited-left per D-03.** The asymmetry (0.92 incoming vs 0.96 outgoing) gives the depth/zoom feel: the child zooms UP from a smaller scale, the parent recedes to a slightly smaller scale. Both values are locked by D-03.
- **Search-active hides `.ddmm-panels` via `display: none`, not `visibility: hidden`.** The D-06 flat-list model REPLACES the drill view entirely. The results ul lives inside `.ddmm-search` (between header and nav) so it survives the panels hide and expands via `flex: 1 1 auto` to fill the drawer body.
- **Hamburger morph uses calc() on existing vars, not fixed pixels.** `translateY( calc( var(--ddmm-hamburger-line-height) + var(--ddmm-hamburger-line-gap) ) )` tracks the Phase 1 vars. If Phase 6 changes the line height or gap via Style Tab, the morph still centers correctly.

## Deviations from Plan

None - plan executed exactly as written. All three tasks landed as three separate atomic commits, one per task, matching the plan's task structure.

## Issues Encountered

None.

## User Setup Required

None - this plan is pure CSS. No external service configuration, no PHP changes, no asset registration changes. Plan 01 already emits the `.ddmm-anim--{type}` container class and the `--ddmm-transition-duration` / `--ddmm-transition-easing` inline CSS vars on `.ddmm-widget` that this CSS consumes.

## Next Phase Readiness

- **Plan 04 (Frontend JS Interaction)** can proceed: every class this plan's CSS targets is now resolvable.
  - `ddmm-panel--active` and `ddmm-panel--exited-left` toggles produce visible motion per the active animation type.
  - `ddmm-is-open` on `.ddmm-widget` (Phase 4 rule) + `ddmm-trigger--active` on the button produce the drawer slide-in and hamburger->X morph.
  - `ddmm-search-active` on `.ddmm-widget` hides the drill view and shows the flat results list.
  - `ddmm-current-item` / `ddmm-current-ancestor` on `<li>` elements produce visible auto-open markers.
- **No blockers.** ANIM-04 negative grep holds at 0. Braces balanced (63/63). File parses as valid CSS.

## Self-Check: PASSED

- FOUND: assets/css/ddmm-frontend.css (modified, 518 lines)
- FOUND: commit 720710a
- FOUND: commit 60ddfba
- FOUND: commit 0f1fef5
- ANIM-04 negative grep: 0 matches (GPU-composited only)
- Brace balance: 63 open / 63 close

---
*Phase: 05-frontend-drill-down-javascript*
*Completed: 2026-06-13*
