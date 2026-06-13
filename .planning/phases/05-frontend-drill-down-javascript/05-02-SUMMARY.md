---
phase: 05-frontend-drill-down-javascript
plan: 02
subsystem: rendering
tags: [search, php, drawer, elementor, a11y]
requires:
  - "04-rendering-pipeline-drawer-html (DrawerRenderer base, escaping patterns)"
provides:
  - "DrawerRenderer::render_search_box() — sticky search bar markup with data-ddmm-search-input / data-ddmm-search-results hook attributes"
  - "Conditional search-box render call in DrawerRenderer::render() gated by search_enabled === 'yes'"
affects:
  - "src/Rendering/DrawerRenderer.php (render() flow gains one conditional call; one new private static method)"
  - "Plan 05-04 JS (consumes [data-ddmm-search-input] + [data-ddmm-search-results] hooks for live filtering)"
tech-stack:
  added: []
  patterns:
    - "printf with positional %1$s reuse of $widget_id for multi-instance ID uniqueness (label for / input id / aria-controls / results id)"
    - "Opt-in conditional render guard: ! empty( $settings['search_enabled'] ) && 'yes' === $settings['search_enabled'] (D-09 default-off)"
    - "Empty-container-on-render contract — PHP emits the <ul> shell, JS (Plan 04) populates results via document.createElement + textContent (ASVS V5)"
key-files:
  created: []
  modified:
    - src/Rendering/DrawerRenderer.php
decisions:
  - "Search box placed between render_header() and nav opening (D-07 sticky bar directly below header, above panels)"
  - "Input and results IDs suffixed with $widget_id so multiple widget instances on the same page never collide (label for / aria-controls / aria-labelledby all resolve per-instance)"
  - "Placeholder defaults to 'Search menu…' (translatable) when search_placeholder setting is empty"
  - "Only one free-text value (search_placeholder) crosses the settings→HTML boundary; escaped via esc_attr (ASVS V5, threat T-05-05 mitigated)"
metrics:
  duration: 136s
  completed: 2026-06-14
  tasks: 1
  files: 1
---

# Phase 5 Plan 02: Search Box Markup Summary

Added the opt-in search-box markup to `DrawerRenderer` — the only new PHP markup in Phase 5 (per CONTEXT.md domain boundary). A new `render_search_box()` static method emits a sticky bar with `<input>` + empty results `<ul>`, called conditionally inside `render()` when `search_enabled === 'yes'`. Plan 04's JS owns the live filtering; this plan just emits the container with the hook attributes JS binds to.

## What Was Built

**Single-file PHP modification to `src/Rendering/DrawerRenderer.php`:**

1. **New `render_search_box( array $settings, string $widget_id ): void` private static method** — emits:
   - Outer `<div class="ddmm-search" data-ddmm-search role="search">` wrapper
   - `<label class="screen-reader-text">` with translatable "Search menu items"
   - `<input type="search" data-ddmm-search-input>` with `autocomplete="off"`, `aria-controls` pointing at the results `<ul>`
   - Empty `<ul class="ddmm-search__results" data-ddmm-search-results aria-live="polite" aria-relevant="additions">`
   - `%1$s` positional placeholder reuses `$widget_id` four times (label `for`, input `id`, `aria-controls`, results `id`) so each widget instance has matching, unique IDs

2. **Conditional call in `render()`** — inserted between step 3 (`render_header`) and step 4 (nav opening), gated by `! empty( $settings['search_enabled'] ) && 'yes' === $settings['search_enabled']` (D-09 opt-in, default off). `$widget_id` was already a `render()` parameter, so no signature change was needed.

3. **Placeholder handling** — `$settings['search_placeholder']` if non-empty, else translatable `__( 'Search menu…', 'devsroom-drilldown-mobile-menu' )` default.

**Threat T-05-05 mitigated:** the only user-controlled free-text value (`search_placeholder`) passes through `esc_attr()` in the printf, preventing attribute breakout. Threat T-05-06 (user-typed query XSS) is explicitly a Plan 04 JS-side concern — this plan emits an empty `<ul>` on render, so no user query flows through PHP.

## Commits

| Task | Hash | Message |
|------|------|---------|
| 1 | `1fe48e9` | `feat(05-02): add render_search_box() to DrawerRenderer` |

## Acceptance Criteria — All Pass

| # | Check | Target | Actual |
|---|-------|--------|--------|
| 1 | `php -l` exit code | 0 | 0 |
| 2 | `render_search_box` count | ≥2 | 2 (method def + call) |
| 3 | `data-ddmm-search` count | ≥3 | 5 (3 hook attrs + 2 ID substrings) |
| 4 | `data-ddmm-search-input` count | ≥1 | 2 |
| 5 | `data-ddmm-search-results` count | ≥1 | 2 |
| 6 | `role="search"` count | ≥1 | 1 |
| 7 | `search_placeholder` count | ≥1 | 3 |
| 8 | text domain count | ≥5 (unchanged/increased) | 7 (was 5) |
| 9 | `search_enabled` count | ≥1 | 1 |
| 10 | `esc_attr`/`esc_html` count | ≥25 (unchanged/increased) | 28 (was 25) |
| 11 | method signature | ≥1 | 1 |

## EXTR-01 Validation

VALIDATION.md EXTR-01 target: `grep -c "data-ddmm-search" src/Rendering/DrawerRenderer.php` ≥ 1 → **actual 5** (PASS).

The `search_enabled` setting control itself lives in `DrillDownMenu.php` (`_register_controls()`), which is a separate plan's scope (per VALIDATION.md Wave 0 requirements, the PHP control is added in the plan that owns `_register_controls()` edits). This plan delivers the renderer half of EXTR-01.

## Deviations from Plan

None — plan executed exactly as written. Both edits landed verbatim from the PLAN.md `<action>` block, and all 11 acceptance grep checks passed on first verification.

## Worktree Path Note

During execution the Edit tool wrote changes to the main repo checkout (`D:/Devsroom-Work/Plugins/devsroom-drilldown-mobile-menu/src/...`) rather than this agent worktree's checkout (`.claude/worktrees/agent-a94dba46808fa1357/src/...`) — a known Windows worktree path resolution behavior. Detected via diffing the two file sizes (17436 vs 19348 bytes), corrected by copying the edited file into the worktree and restoring the main repo file to clean. Final state: worktree branch `worktree-agent-a94dba46808fa1357` has exactly one modified file (`src/Rendering/DrawerRenderer.php`) committed as `1fe48e9`; main repo's `DrawerRenderer.php` is clean (its other modifications belong to parallel agents and were untouched).

## Known Stubs

None. The `<ul class="ddmm-search__results">` is intentionally empty on render — this is the contract surface, not a stub. Plan 04's JS (`buildSearchIndex()` + `filterSearch()`) populates it via safe DOM APIs at runtime. The empty container is the designed handoff, documented in the method's PHPDoc.

## Threat Flags

None. The only new trust-boundary crossing (`search_placeholder` settings value → `placeholder` attribute) was already identified in the plan's `<threat_model>` as T-05-05 and mitigated via `esc_attr()`. No additional security-relevant surface was introduced.

## Self-Check: PASSED

- `src/Rendering/DrawerRenderer.php` — FOUND (worktree checkout, committed)
- Commit `1fe48e9` — FOUND in `git log --oneline` on branch `worktree-agent-a94dba46808fa1357`
