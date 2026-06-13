---
phase: 4
slug: rendering-pipeline-drawer-html
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-06-13
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.
> Adapted from `04-RESEARCH.md` § Validation Architecture. This is a PHP rendering phase with **no JS behavior** (JS is bootstrap-only) and **no WordPress/PHPUnit test harness** in the project. Validation = PHP lint + structural grep checks on emitted code + manual render-in-WP inspection.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None — no PHPUnit / wp-env / test bootstrap configured |
| **Config file** | none |
| **Quick run command** | `php -l src/Rendering/DrawerRenderer.php` (per-file lint of touched files) |
| **Full suite command** | `find src -name '*.php' -exec php -l {} \;` + the grep checks in the map below |
| **Estimated runtime** | ~3 seconds (lint) + ~2 seconds (greps) |

---

## Sampling Rate

- **After every task commit:** `php -l` on each touched PHP file + the grep check(s) for that task's requirement(s).
- **After every plan wave:** full `find src -name '*.php' -exec php -l {} \;` + all grep checks + manual WP render inspection.
- **Before `/gsd-verify-work`:** full lint green + all greps pass + manual render verified (editor preview shows root `<ul>`; frontend shows off-canvas drawer with correct `data-target`/`data-panel-id`/`data-back-target` contract).
- **Max feedback latency:** ~5 seconds (lint + greps are instant; manual render is the gating step).

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 04-01-01 | 01 | 1 | DRAW-02 | — | overlay element escaped | static grep | `grep -c "ddmm-overlay" src/Rendering/DrawerRenderer.php` ≥1 | ❌ W0 | ⬜ pending |
| 04-01-02 | 01 | 1 | DRAW-04 / D-25 | T-04-close | close button hook `data-ddmm-close` | static grep | `grep -c "ddmm-close" src/Rendering/DrawerRenderer.php` ≥1 | ❌ W0 | ⬜ pending |
| 04-01-03 | 01 | 1 | DRAW-05 | — | root panel active class | static grep | `grep -c "ddmm-panel--active" src/Rendering/DrawerRenderer.php` ≥1 | ❌ W0 | ⬜ pending |
| 04-01-04 | 01 | 1 | DRAW-07 / D-10 | — | back-target attr | static grep | `grep -c "data-back-target" src/Rendering/DrawerRenderer.php` ≥1 | ❌ W0 | ⬜ pending |
| 04-01-05 | 01 | 1 | DRAW-11 / D-09 | T-04-id | `uniqid()` panel IDs | static grep | `grep -c "uniqid" src/Rendering/DrawerRenderer.php` ≥1 | ❌ W0 | ⬜ pending |
| 04-01-06 | 01 | 1 | A11Y-01 / D-21 | — | nav aria-label, no role=menu | grep ± | `grep -c "aria-label" src/Rendering/DrawerRenderer.php` ≥1 AND `grep -rc 'role="menu"' src/ assets/` = 0 | ❌ W0 | ⬜ pending |
| 04-01-07 | 01 | 1 | A11Y-02 / D-23 | — | chevron aria-expanded+controls | static grep | `grep -cE "aria-expanded|aria-controls" src/Rendering/DrawerRenderer.php` ≥2 | ❌ W0 | ⬜ pending |
| 04-01-08 | 01 | 1 | A11Y-03 | — | drawer id matches trigger aria-controls | static grep | `grep -c "ddmm-drawer-" src/Rendering/DrawerRenderer.php` ≥1 | ❌ W0 | ⬜ pending |
| 04-01-09 | 01 | 2 | DRAW-06 / D-09 | T-04-id | single-source-of-truth ID threading: `$child_panel_id` declared once via `uniqid()` and reused for BOTH chevron `data-target`/`aria-controls` AND the recursive `render_panel()` call (prevents Pitfall 1 ID re-derivation) | static grep + manual | `grep -c "\$child_panel_id" src/Rendering/DrawerRenderer.php` ≥3 (1 declaration + 1 chevron printf + 1 recursive render_panel call) | ❌ W0 | ⬜ pending |
| 04-02-01 | 02 | 1 | DRAW-03 / D-05 | T-04-img | brand source SELECT (4 options) | static grep | `grep -c "brand_source" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ✅ exists | ⬜ pending |
| 04-02-02 | 02 | 1 | DRAW-08 / D-12 | — | back-title toggle default ON | static grep | `grep -c "show_back_title" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ✅ exists | ⬜ pending |
| 04-03-01 | 03 | 1 | DRAW-01 | — | off-canvas CSS | static grep | `grep -c "translateX(-100%)" assets/css/ddmm-frontend.css` ≥1 | ✅ exists | ⬜ pending |
| 04-04-01 | 04 | 1 | JSCR-01..04 / D-14 | — | element_ready hook + init guard | static grep | `grep -c "element_ready/ddmm-drilldown-menu.default" assets/js/ddmm-frontend.js` ≥1 AND `grep -c "ddmmInit" assets/js/ddmm-frontend.js` ≥1 | ✅ exists | ⬜ pending |
| 04-04-02 | 04 | 1 | JSCR-05 / D-15 | — | NO wp_localize_script | negative grep | `grep -rc "wp_localize_script" src/` = 0 | n/a | ⬜ pending |
| 04-04-03 | 04 | 1 | DRAW-10 | T-04-positional | no positional nav logic in JS | negative grep | `grep -nE "children\[|nextSibling|\.index\(" assets/js/ddmm-frontend.js` = 0 | ✅ exists | ⬜ pending |

*Task IDs are provisional — the planner assigns final IDs. Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `src/Rendering/DrawerRenderer.php` — does not exist yet; **create as the first Wave 1 task** (the directory `src/Rendering/` must be created). All DRAW/A11Y greps depend on this file existing.
- [ ] No PHPUnit harness — validation relies on `php -l` + grep + manual inspection. Automated HTML-structure tests are **out of Phase 4 scope** (would require a WP test bootstrap).
- [ ] No rendered-HTML fixture — manual render-in-WP is the gap-closer for runtime correctness.

*Existing infrastructure (lint, grep) covers all phase requirements at the code-emission level.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| `data-target` ↔ `data-panel-id` match at runtime | DRAW-06 | Runtime confirmation of the static threading grep (04-01-09). Requires rendered DOM (WP + Elementor + configured widget) | **Static gate (04-01-09):** `grep -c "\$child_panel_id" src/Rendering/DrawerRenderer.php` ≥ 3 — proves one variable drives both the chevron `data-target`/`aria-controls` and the recursive `render_panel()` call. **Manual runtime confirmation:** Configure a 3-level menu (WP or custom), render on frontend, open DevTools: every parent chevron's `data-target` value appears exactly once as a child panel's `data-panel-id`. |
| Unlimited-depth recursion | DRAW-09 | Requires multi-level configured menu | Configure a 4-level custom menu, render, verify 4 nested panels each with a working back button (back-target points to correct ancestor). |
| Editor preview block (static root `<ul>`) | D-18 | Elementor editor iframe | In Elementor editor, confirm trigger + inline root panel items render (icons + chevrons); sub-panels absent; block never appears on published frontend. |
| Drawer off-canvas + overlay on frontend | DRAW-01/02 | Visual | Published page: drawer is off-screen left, overlay hidden, until Phase 5 opens it. `aria-hidden="true"` on drawer. |
| Escaping end-to-end (no unescaped echo) | PLUG-06 / V5 | Runtime + phpcs | Run `phpcs` if available; otherwise confirm every dynamic echo in `DrawerRenderer.php` is wrapped in `esc_html`/`esc_url`/`esc_attr` or uses the `Icons_Manager` pre-escaped path (phpcs:ignore). |

---

## Validation Sign-Off

- [ ] All tasks have an automated verify (`php -l` + grep) or Wave 0 dependency
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references (DrawerRenderer.php created first)
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s (lint + greps)
- [ ] `nyquist_compliant: true` set in frontmatter once manual render passes

**Approval:** pending
