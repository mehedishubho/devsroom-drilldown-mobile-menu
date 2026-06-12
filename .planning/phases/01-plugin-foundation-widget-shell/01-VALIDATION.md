---
phase: 1
slug: plugin-foundation-widget-shell
status: draft
nyquist_compliant: true
wave_0_complete: false
created: 2026-06-12
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None — WordPress plugin with no test framework |
| **Config file** | none |
| **Quick run command** | `php -l {file}` (syntax check per file) |
| **Full suite command** | Manual browser verification |
| **Estimated runtime** | ~5 seconds for syntax checks |

---

## Sampling Rate

- **After every task commit:** Run `php -l` on modified PHP files for syntax errors
- **After every plan wave:** Manual browser verification in WordPress + Elementor
- **Before `/gsd-verify-work`:** All success criteria manually verified
- **Max feedback latency:** ~30 seconds (syntax check) / ~2 minutes (browser check)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01 | 1 | PLUG-01, PLUG-04 | — | N/A | syntax | `php -l devsroom-drilldown-mobile-menu.php` | ❌ W0 | ⬜ pending |
| 01-01-02 | 01 | 1 | PLUG-02, PLUG-03 | — | N/A | syntax | `php -l src/Plugin.php` | ❌ W0 | ⬜ pending |
| 01-02-01 | 02 | 1 | WIDG-01, WIDG-02, WIDG-03, WIDG-04 | — | N/A | syntax | `php -l src/Elementor/Widget/DrillDownMenu.php` | ❌ W0 | ⬜ pending |
| 01-03-01 | 03 | 1 | TRIG-01 through TRIG-06 | — | N/A | syntax | `php -l src/Elementor/Widget/DrillDownMenu.php` | ❌ W0 | ⬜ pending |
| 01-04-01 | 04 | 2 | PLUG-05, PLUG-06 | — | Output escaping | syntax | `php -l assets/js/ddmm-frontend.js` | ❌ W0 | ⬜ pending |
| 01-04-02 | 04 | 2 | PLUG-05, PLUG-06 | — | Output escaping | syntax | `php -l assets/css/ddmm-frontend.css` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- WordPress 6.5+ test environment with Elementor active
- PHP 8.1+ runtime for syntax validation

*No automated test framework — validation is syntax checks + manual browser verification.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Plugin activates without errors | PLUG-01, PLUG-02 | Requires live WordPress | Activate plugin in WP Admin → Plugins, check no errors |
| Admin notice shows when Elementor inactive | PLUG-03 | Requires Elementor deactivated | Deactivate Elementor, check admin notice with link appears |
| Widget appears in Elementor editor under "Devsroom" category | WIDG-01, WIDG-02 | Requires Elementor editor | Open Elementor editor, search for widget in panel |
| Custom SVG icon displays | WIDG-03 | Visual verification | Check widget icon in Elementor panel |
| All 4 trigger types render correctly | TRIG-01 through TRIG-05 | Visual + DOM verification | Configure each type in Content Tab, verify rendering |
| aria-expanded and aria-controls present | TRIG-06, A11Y-03 | DOM attribute check | Inspect trigger button in browser DevTools |
| Assets only loaded on pages with widget | PLUG-05 | Network request check | Check page source on pages with/without widget |
| All output escaped | PLUG-06 | Code review | grep for unescaped echo/printf in PHP files |

---

## Validation Sign-Off

- [ ] All tasks have automated syntax check or manual verification steps
- [ ] Sampling continuity: no 3 consecutive tasks without verification
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s (syntax) / 2min (browser)
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
