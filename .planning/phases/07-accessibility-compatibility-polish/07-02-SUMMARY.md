---
phase: 07-accessibility-compatibility-polish
plan: 02
subsystem: frontend-css-a11y
tags: [a11y, css, focus-visible, prefers-reduced-motion, rtl, d-10, d-18, d-19]
requires:
  - "06-CONTEXT.md (BEM class catalog, --ddmm-* var bridge)"
  - "assets/css/ddmm-frontend.css (the file modified — single surface)"
provides:
  - ":focus-visible ring on 6 BEM surfaces driven by --ddmm-focus-ring-* themeable vars"
  - "@media (prefers-reduced-motion: reduce) neutralization block (0.01ms not 0ms)"
  - "RTL baseline via 3 logical-property refactors (inset-inline-start, margin-inline-end, margin-inline-start)"
affects:
  - "Keyboard users get a visible focus ring on trigger/items/chevrons/close/back/search"
  - "Reduced-motion users see near-instant panel/drawer transitions without breaking transitionend cleanup"
  - "dir=\"rtl\" themes no longer visibly break on drawer position, menu-icon spacing, or chevron alignment"
tech-stack:
  added:
    - "CSS :focus-visible pseudo-class (MDN-verified modern a11y standard)"
    - "CSS @media (prefers-reduced-motion: reduce) (browser-native, no JS)"
    - "CSS logical properties: inset-inline-start, margin-inline-end, margin-inline-start"
  patterns:
    - "Themeable focus ring via custom-property indirection (--ddmm-focus-ring-* defaulting to --ddmm-trigger-color)"
    - "0.01ms (not 0ms) duration compromise per CSS Remedy issue #11 + MDN"
    - "Logical-property refactor for RTL baseline without touching transform: translateX() slide (deferred to RTL-01 v2)"
key-files:
  created: []
  modified:
    - "assets/css/ddmm-frontend.css"
decisions:
  - "Reuse --ddmm-trigger-color as the focus-ring default so the ring auto-flips light/dark with the user's theme (D-10)"
  - "Use :focus-visible (not :focus) to suppress the ring on mouse-click focus (D-10 mandate)"
  - "0.01ms duration for reduced-motion to preserve transitionend cleanup (Pitfall 8 / D-18)"
  - "Deliberately leave transform: translateX() untouched — full RTL slide-direction is v2 (RTL-01 / D-19)"
metrics:
  duration: "6min"
  completed: "2026-06-14"
  tasks: 3
  files: 1
---

# Phase 7 Plan 02: CSS Accessibility Polish Summary

Themeable `:focus-visible` ring on all 6 keyboard-focusable BEM surfaces, unconditional `prefers-reduced-motion` neutralization (0.01ms not 0ms — preserves `transitionend` cleanup), and an RTL baseline via three CSS logical-property refactors — all additive to a single CSS file, no JS/PHP touched.

## What Shipped

### Task 07-02-01: `:focus-visible` ring on 6 BEM surfaces (D-10)

**3 themeable custom properties declared** inside the `.elementor-widget-ddmm-drilldown-menu` var block (after `--ddmm-trigger-font-size`):

| Line | Declaration |
|------|-------------|
| 19 | `--ddmm-focus-ring-color: var( --ddmm-trigger-color );` |
| 20 | `--ddmm-focus-ring-width: 2px;` |
| 21 | `--ddmm-focus-ring-offset: 2px;` |

The color var inherits `--ddmm-trigger-color` so the ring is automatically visible on both light (dark trigger color → dark ring) and dark (light trigger color → light ring) drawer backgrounds — no new color literal introduced.

**Unified 6-surface `:focus-visible` rule** at line 102 (replaced the single-surface `.ddmm-trigger:focus-visible` at the old line 93):

```css
.ddmm-trigger:focus-visible,
.ddmm-menu__item > a:focus-visible,
.ddmm-chevron:focus-visible,
.ddmm-close:focus-visible,
.ddmm-back__button:focus-visible,
.ddmm-search__input:focus-visible {
    outline: var( --ddmm-focus-ring-width ) solid var( --ddmm-focus-ring-color );
    outline-offset: var( --ddmm-focus-ring-offset );
}
```

**Legacy `.ddmm-search__input:focus` rule REMOVED** (was at old line 555). It used `:focus` (always-visible) which contradicts D-10's mouse-click suppression mandate. The unified `:focus-visible` rule above now covers the search input — keeping the legacy rule would have produced a duplicate always-visible ring defeating D-10.

### Task 07-02-02: `prefers-reduced-motion` neutralization (D-18, Pitfall 8)

**Single `@media (prefers-reduced-motion: reduce)` block appended at line 635** (file end, after the Phase 6 active-state comment block):

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

**Duration is `0.01ms` NOT `0ms`** — Pitfall 8: zero-duration transitions do not reliably fire `transitionend` across browsers, which would break Phase 5's scroll-reset cleanup in `drill()` at `ddmm-frontend.js:237` (the `transitionend` listener that resets `outgoing.scrollTop = 0`). The `0.01ms` value is short enough to feel instant to the user yet guarantees the event fires. Consensus per MDN + CSS Remedy issue #11.

`!important` is required because the per-animation-type rules at lines 467-532 (slide/fade/scale/slidefade) have higher specificity than this global rule. Scope is unconditional (`.ddmm-widget` descendants) per D-18 — no per-instance toggle.

### Task 07-02-03: RTL baseline via 3 logical-property refactors (D-19)

| Line | Was | Now | Rule |
|------|-----|-----|------|
| 161 | `left: 0;` | `inset-inline-start: 0;` | `.ddmm-drawer` |
| 295 | `margin-right: 8px;` | `margin-inline-end: 8px;` | `.ddmm-menu__icon` |
| 305 | `margin-left: auto;` | `margin-inline-start: auto;` | `.ddmm-chevron` |

**`transform: translateX()` deliberately UNTOUCHED** (17 occurrences, baseline verified unchanged) — there is no logical-property equivalent for transform-based slide, and D-19 explicitly defers full RTL slide-direction to v2 (RTL-01). Symmetric paddings (`padding: 0 16px`, `padding: 12px 16px`) also untouched — they are already direction-agnostic.

Browser support for CSS logical properties: Chrome 87+, Safari 15+, Firefox 66+ — matches the CLAUDE.md browser-support matrix.

## Acceptance Criteria Results

All `<verify>` and `<acceptance_criteria>` grep thresholds at commit time:

| # | Criterion | Threshold | Actual | Status |
|---|-----------|-----------|--------|--------|
| 1 | `focus-visible` count | >= 7 | 7 | PASS |
| 2 | 6-surface selector list count | >= 6 | 6 | PASS |
| 3 | `outline: none\|outline: 0` | 0 | 0 | PASS |
| 4 | shared `var( --ddmm-focus-ring-width ) solid var( --ddmm-focus-ring-color )` | >= 1 | 1 | PASS |
| 5 | `--ddmm-focus-ring-color: var( --ddmm-trigger-color )` | >= 1 | 1 | PASS |
| 6 | Legacy `.ddmm-search__input:focus` (non `-visible`) rule | 0 | 0 | PASS (strict check) |
| 7 | `prefers-reduced-motion: reduce` count | == 1 | 1 | PASS |
| 8 | `animation-duration: 0.01ms !important` | >= 1 | 1 | PASS |
| 9 | `transition-duration: 0.01ms !important` | >= 1 | 1 | PASS |
| 10 | `scroll-behavior: auto !important` | >= 1 | 1 | PASS |
| 11 | `animation-iteration-count: 1 !important` | >= 1 | 1 | PASS |
| 12 | `transition-duration: 0ms\|0s` (Pitfall 8) | 0 | 0 | PASS |
| 13 | `inset-inline-start: 0` | >= 1 | 1 | PASS |
| 14 | `margin-inline-end: 8px` | >= 1 | 1 | PASS |
| 15 | `margin-inline-start: auto` | >= 1 | 1 | PASS |
| 16 | `inset-inline-start\|margin-inline-end\|margin-inline-start` (combined) | >= 4 | 3 | **DEVIATION** (see below) |
| 17 | `.ddmm-drawer left: 0` gone (outside editor-preview) | gone | gone | PASS |
| 18 | `margin-right: 8px` as actual declaration | 0 | 0 | PASS (only in comments) |
| 19 | `margin-left: auto` as actual declaration | 0 | 0 | PASS (only in comments) |
| 20 | `transform: translateX` count unchanged | 17 | 17 | PASS |

## Deviations from Plan

### Plan-Criteria Miscalculation (Rule 3 — Documented, not code-defective)

**Found during:** Task 07-02-03 verification

**Issue:** Acceptance criterion #16 specifies `grep -cE "inset-inline-start|margin-inline-end|margin-inline-start" assets/css/ddmm-frontend.css` must return `>= 4`. The plan's `<action>` prescribes exactly 3 refactors (one logical property each), producing exactly 3 matches. The threshold rationale states: *"accounts for at least one logical property appearing more than once or being joined by the existing `inset: 0` shorthand."* However, the existing `inset: 0` shorthand (`.ddmm-overlay` line 148, `.ddmm-panel` line 233) does NOT match the literal pattern `inset-inline-start` — `inset:` and `inset-inline-start` are distinct tokens. `grep -cE` counts matching lines, not pattern alternatives, so 3 distinct rules → 3 matches.

**Why this is a plan-criteria issue, not a code issue:** The implementation exactly follows the plan's `<action>` block (3 refactors, 1 logical property each). Inflating the count to 4 would require either (a) adding a 4th logical-property refactor the plan does not prescribe (scope creep), or (b) duplicating one of the existing 3 (artificial padding). Neither is defensible.

**Resolution:** Followed the plan's `<action>` (source of truth) over the miscalculated `<acceptance_criteria>` threshold. All 3 refactors are correctly applied per D-19. Documented here for transparency and for the verifier's awareness.

**Files modified:** assets/css/ddmm-frontend.css (the 3 refactors themselves — no extra changes were added to pad the count)

**Commit:** c3a2f8d

### Note on grep word-boundary artifact (criterion 6)

The original acceptance criterion 6 uses `grep -cE "\.ddmm-search__input:focus\b"`. In ripgrep, `\b` treats `:` as a non-word-character boundary, so `.ddmm-search__input:focus-visible` matches `.ddmm-search__input:focus\b` (the `\b` sits between `focus` and `-visible`). A strict check (`grep -nE '\.ddmm-search__input:focus[^-]'`) confirms the legacy `:focus {` rule (without `-visible`) is genuinely gone. The intent of the criterion is satisfied.

## Commits

| Hash | Message |
|------|---------|
| 74cb158 | feat(07-02): themeable :focus-visible ring on 6 BEM surfaces |
| 55bc92b | feat(07-02): prefers-reduced-motion neutralization (0.01ms) |
| c3a2f8d | feat(07-02): RTL baseline via 3 logical-property refactors |

## Known Stubs

None. All CSS is production-ready and wires into the existing `--ddmm-*` custom-property bridge. No placeholder values, no TODOs, no mock data.

## Threat Flags

None. The `<threat_model>` in the plan identified T-07-02-01 (focus indicator missing → mitigated by Task 1), T-07-02-02 (CSS-injection via Elementor inline styles → accepted, inherits existing trust model), and T-07-02-04 (stuck panel under reduced motion → mitigated by Task 2's 0.01ms duration). No new threat surface introduced beyond what the plan anticipated.

## Self-Check: PASSED
