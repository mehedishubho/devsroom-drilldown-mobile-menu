---
phase: 5
slug: frontend-drill-down-javascript
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-06-14
---

# Phase 5 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.
> Source: `05-RESEARCH.md` § Validation Architecture. Pattern: lint + grep + manual browser (matches Phase 4 — no JS test harness, no build step per CLAUDE.md).

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None — no PHPUnit / wp-env / Jest / Playwright configured in the project. `node --check` (ships with Node.js) is the JS syntax verifier; `php -l` lints touched PHP files. |
| **Config file** | none |
| **Quick run command** | `php -l src/Rendering/DrawerRenderer.php` + `php -l src/Elementor/Widget/DrillDownMenu.php` + `node --check assets/js/ddmm-frontend.js` |
| **Full suite command** | `find src -name '*.php' -exec php -l {} \;` + `node --check assets/js/ddmm-frontend.js` + grep checks below + manual browser verification |
| **Estimated runtime** | ~3 seconds (lint + syntax check) + ~2 seconds (greps) |

The project has no JS test harness and no build step (CLAUDE.md mandates no build tool). `node --check` parses the file without executing it, catching syntax errors in ~0.5s. This matches the Phase 4 VALIDATION.md pattern.

**Environment probe required at Wave 0:** confirm `php` and `node` are on PATH. If Node is absent, JS syntax verification falls back to pasting the file into browser DevTools console. No production dependency is added by Phase 5.

---

## Sampling Rate

- **After every task commit:** `php -l` on touched PHP files + `node --check assets/js/ddmm-frontend.js` + the grep checks for that task's requirements.
- **After every plan wave:** full lint sweep + all greps + manual browser verification of one animation type per wave.
- **Before `/gsd-verify-work`:** Full suite must be green — all 4 animation types verified in browser, search + auto-open + close behaviors verified, all greps green.
- **Max feedback latency:** ~5 seconds (lint + greps; manual checks batched per wave).

---

## Per-Requirement Verification Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|--------------|
| ANIM-01 | Animation-type SELECT control + 4 container classes emitted | static grep | `grep -cE "ddmm-anim--(slide\|fade\|scale\|slidefade)" assets/css/ddmm-frontend.css` ≥4 AND `grep -c "animation_type" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| ANIM-02 | Duration slider 100–2000ms → `--ddmm-transition-duration` inline override | static grep | `grep -c "animation_duration" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -c "ddmm-transition-duration" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| ANIM-03 | Easing SELECT → `--ddmm-transition-easing` var consumed by transitions | static grep | `grep -c "animation_easing" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -c "ddmm-transition-easing" assets/css/ddmm-frontend.css` ≥2 | ❌ Wave 0 |
| ANIM-04 | Only `transform`/`opacity` animate — no `left`/`top`/`width`/`margin` transitions | negative grep | `grep -nE "transition:[^;]*(left\|top\|width\|margin\|padding)" assets/css/ddmm-frontend.css` = 0 | ✅ exists (must hold after edits) |
| EXTR-01 | Search box conditional render + placeholder TEXT control | static grep | `grep -c "search_enabled" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -c "data-ddmm-search" src/Rendering/DrawerRenderer.php` ≥1 | ❌ Wave 0 |
| EXTR-02 | Search filters all items across panels (flat index) | static grep + manual | `grep -cE "buildSearchIndex\|searchIndex\|filterSearch" assets/js/ddmm-frontend.js` ≥3 | ❌ Wave 0 |
| EXTR-03 | Auto-open URL match + drill + marker classes | static grep + manual | `grep -cE "autoOpenCurrentPath\|findCurrentPageItem\|normalizeUrl" assets/js/ddmm-frontend.js` ≥3 AND `grep -c "ddmm-auto-open" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| EXTR-04 | Close-after-link toggle, excludes new-tab + chevron | static grep + manual | `grep -c "closeLink" assets/js/ddmm-frontend.js` ≥1 AND `grep -c "ddmm-close-link" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| EXTR-05 | Close-on-overlay toggle | static grep + manual | `grep -c "closeOverlay" assets/js/ddmm-frontend.js` ≥1 AND `grep -c "ddmm-close-overlay" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| DRAW-10 (carry) | ID-based nav only, no positional logic | negative grep | `grep -nE "children\[|nextSibling\|\.index\(" assets/js/ddmm-frontend.js` = 0 | ✅ exists (must hold) |
| JSCR-02 (carry) | IIFE-wrapped, no globals | static grep | `grep -c "^( function() {" assets/js/ddmm-frontend.js` ≥1 AND `grep -nE "window\.ddmm\|var ddmm" assets/js/ddmm-frontend.js` = 0 | ✅ exists |

> **Per-Task Verification Map:** The planner maps each PLAN.md `<task>` to the requirement rows above via the task's `acceptance_criteria` (grep commands) and `<automated>` verify block. Task IDs (e.g. `5-01-01`) populate at planning time.

---

## Wave 0 Requirements

- [ ] `assets/css/ddmm-frontend.css` — add animation-type class hooks (`ddmm-anim--{type}`) + `ddmm-panel--exited-left` state + search-box styles + hamburger→X transforms + `--ddmm-transition-easing` consumption. All ANIM/EXTR CSS greps depend on these.
- [ ] `assets/js/ddmm-frontend.js` — fill `init()` body + add `DrillDownMenu` methods (open/close/drill/back/search/autopath). All JS greps depend on this.
- [ ] `src/Rendering/DrawerRenderer.php` — add `render_search_box()` method + call it conditionally in `render()`; add `data-ddmm-trigger` to trigger button (Pitfall 1 gap).
- [ ] `src/Elementor/Widget/DrillDownMenu.php` — add Animation, Search, and Drawer-Settings-toggles Content Tab sections; add `data-*` config attrs + inline `--ddmm-transition-*` style + `data-ddmm-auto-open`/`data-ddmm-close-link`/`data-ddmm-close-overlay` on `.ddmm-widget`.
- [ ] Environment probe: confirm `php` and `node` available; if Node absent, document DevTools fallback.

*No JS test framework install needed — `node --check` ships with Node.js.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Animation type → correct transform/opacity, compositor-only | ANIM-01..04 | No DOM/animation test harness; must observe computed style + Performance recording | See RESEARCH §Validation Architecture (a): for each of 4 types, click parent chevron, observe DevTools computed `transform`/`opacity` + Performance trace shows no "Recalculate Style" during transform frames |
| Search filtering across tree depth | EXTR-01/02 | Requires live menu tree + input events | See RESEARCH (b): type "shoes" → 1 result w/ breadcrumb `Shop › Categories › Shoes`; "s" → multiple; Esc clears; "zzz" → "No results"; parent result drills, leaf result navigates |
| Auto-open URL match + drill + highlight | EXTR-03 | Requires live WP page + URL matching | See RESEARCH (c): navigate to deep page, open drawer → already drilled + item + ancestors marked; non-menu page → root, no highlight |
| Close-behavior edge cases | EXTR-04/05 | Multiple interaction states (new-tab, chevron vs link, overlay toggle) | See RESEARCH (d): same-tab link closes; chevron keeps open; new-tab keeps open; overlay closes when ON, no-op when OFF; ✕ always closes |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
