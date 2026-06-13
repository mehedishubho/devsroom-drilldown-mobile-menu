---
phase: 04-rendering-pipeline-drawer-html
plan: 05
subsystem: a11y
tags: [aria, aria-labelledby, screen-reader-text, accessibility, wordpress-core-pattern, elementor]

# Dependency graph
requires:
  - phase: 04-rendering-pipeline-drawer-html
    provides: "DrawerRenderer::render_back_row() and render_panel() with the conditional span emit and unconditional aria-labelledby emission (the defect surface)"
provides:
  - "render_back_row() always emits the back-row title span; aria-labelledby reference resolves in every show_back_title configuration (WR-01 closed)"
  - "Class switches between visible ddmm-back__title (toggle ON) and ddmm-back__title screen-reader-text (toggle OFF) — screen readers still announce parent name when visible title is hidden"
  - "Self-contained .screen-reader-text CSS rule matching WordPress-core pattern — works regardless of whether the active theme defines its own"
affects: [04-verification, phase-05-frontend-js-interaction, phase-07-accessibility-polish]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "WordPress-core .screen-reader-text class as the accessibility-hide primitive (border:0; clip:rect(1px,1px,1px,1px); clip-path:inset(50%); 1px sizing; position:absolute; word-wrap:normal !important) — self-contained so the plugin works in any theme"
    - "Always-emit + visually-hide pattern preserves ARIA label associations across all toggle states — strictly better than gating the attribute off"

key-files:
  created: []
  modified:
    - "src/Rendering/DrawerRenderer.php"
    - "assets/css/ddmm-frontend.css"

key-decisions:
  - "Chose the always-emit + screen-reader-text approach over gating aria-labelledby off — preserves the screen-reader announcement of the parent name when the toggle is OFF (strictly better accessibility)"
  - "Made the .screen-reader-text rule self-contained in the plugin CSS rather than relying on the theme's definition — keeps the plugin theme-independent per CLAUDE.md"
  - "Left render_panel() aria-labelledby emission UNCHANGED — it is now always valid because the span always exists in the DOM"

patterns-established:
  - "Always-emit + visually-hide: when a user-toggle controls visibility of an element that an ARIA attribute references, emit the element unconditionally and toggle a visually-hidden class instead of conditionally rendering it"
  - "Self-contained a11y primitives: ship a11y CSS utility classes (.screen-reader-text) in the plugin rather than depending on theme/WordPress-core to define them"

requirements-completed: [A11Y-02, DRAW-08]

# Metrics
duration: 4min
completed: 2026-06-13
---

# Phase 4 Plan 05: Aria-labelledby Dangling Reference Gap Closure Summary

**Closed the WR-01 ARIA defect by making render_back_row() always emit the title span (switching class between visible ddmm-back__title and visually-hidden ddmm-back__title screen-reader-text) and shipping a self-contained WordPress-core .screen-reader-text CSS rule so the class works in any theme**

## Performance

- **Duration:** ~4 min
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- Closed the WR-01 dangling aria-labelledby defect: child panels no longer reference a non-existent DOM id when "Show Parent Name in Back Row" is OFF
- Preserved the ARIA label association in ALL configurations — screen readers still announce the parent name when the visible title is hidden (strictly better than gating the attribute off, which the gap definition offered as an alternative)
- Made the plugin self-contained: the `.screen-reader-text` rule ships in the plugin CSS so the class works whether or not the active theme defines its own
- No escaping regressions — `$parent_title` still flows through `esc_html()`, `$title_id` and the new `$title_class` (a hardcoded string literal) both flow through `esc_attr()`
- No GPU-hostile layout transitions introduced (ANIM-04 preserved)

## Task Commits

Each task was committed atomically:

1. **Task 1: Always emit the back-row title span; branch the class on show_back_title** - `9fdb24e` (fix)
2. **Task 2: Add self-contained .screen-reader-text CSS rule to ddmm-frontend.css** - `8facd8e` (fix)

## Files Created/Modified
- `src/Rendering/DrawerRenderer.php` - render_back_row() now unconditionally emits `<span class="ddmm-back__title[ screen-reader-text]" id="$title_id">$parent_title</span>`; class branches on show_back_title; docblock updated to reflect always-present title; render_panel() aria-labelledby emission left UNCHANGED (now always valid)
- `assets/css/ddmm-frontend.css` - APPEND-only addition of the WordPress-core `.screen-reader-text` rule (border:0; clip:rect(1px,1px,1px,1px); clip-path:inset(50%); 1px sizing; position:absolute; word-wrap:normal !important); no existing rules modified

## Decisions Made
- **Always-emit + screen-reader-text over gating aria-labelledby:** The VERIFICATION.md gap offered two fixes. We chose the screen-reader-text approach because gating `aria-labelledby` off when the toggle is OFF would strip the screen-reader announcement of the parent name entirely. The always-emit approach keeps the ARIA association intact in every configuration while honoring the visible-hide choice.
- **Self-contained .screen-reader-text rule:** Shipped in the plugin CSS instead of relying on the theme. Some custom themes omit `.screen-reader-text`, which would break the visually-hidden behavior. Shipping it keeps the plugin theme-independent per CLAUDE.md's "compatible with any WordPress theme" mandate.
- **Left render_panel() aria-labelledby emission UNCHANGED:** Per the plan's explicit instruction, the `aria-labelledby` emission at render_panel() is now always valid because the span always exists in the DOM. Leaving it untouched is the point of the screen-reader-text approach.

## Deviations from Plan

None - plan executed exactly as written. Both CURRENT→FIXED code blocks were applied verbatim. All acceptance_criteria grep checks pass.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- The ARIA contract is now closed for all values of `show_back_title` (both 'yes' and 'no'). Phase 4 Truth 5 ("Correct ARIA markup throughout") is fully satisfied.
- Human verification recommended post-fix: set "Show Parent Name in Back Row" to OFF, render on a published page, run axe/Lighthouse accessibility audit — expected: no `aria-labelledby` references to non-existent IDs on child panels (documented in 04-VERIFICATION.md human_verification test #3).
- No blockers for Phase 5 (frontend JS interaction) or Phase 7 (accessibility polish). The always-present span gives Phase 5's JS a stable DOM target; the screen-reader-text pattern is reusable for any future visually-hide requirement in Phase 7.

---
*Phase: 04-rendering-pipeline-drawer-html*
*Completed: 2026-06-13*

## Self-Check: PASSED

- FOUND: src/Rendering/DrawerRenderer.php
- FOUND: assets/css/ddmm-frontend.css
- FOUND: .planning/phases/04-rendering-pipeline-drawer-html/04-05-SUMMARY.md
- FOUND: commit 9fdb24e (Task 1)
- FOUND: commit 8facd8e (Task 2)
