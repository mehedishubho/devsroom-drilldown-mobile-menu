---
phase: 6
slug: style-tab-controls
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-06-14
---

# Phase 6 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.
> Derived from `06-RESEARCH.md` § Validation Architecture.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None — no PHPUnit / wp-env / Jest / Playwright configured (matches Phase 4 + Phase 5 pattern; CLAUDE.md mandates no build tool). `php -l` is the PHP syntax verifier. |
| **Config file** | none |
| **Quick run command** | `php -l src/Elementor/Widget/DrillDownMenu.php && php -l src/Rendering/DrawerRenderer.php` |
| **Full suite command** | `find src -name '*.php' -exec php -l {} \;` + the grep checks below + manual browser verification in the Elementor editor |
| **Estimated runtime** | ~4 seconds (lint + greps; manual browser checks batched per wave) |

**Environment probe (Wave 0):** confirm `php` is on PATH — `php --version`. If absent, fall back to pasting the PHP file into a PHP linter web tool. No production dependency is added by Phase 6.

---

## Sampling Rate

- **After every task commit:** `php -l` on touched PHP files + the grep checks for that task's requirements.
- **After every plan wave:** full lint sweep + all greps + manual browser verification of one section per wave (rotating through the 6 sections).
- **Before `/gsd-verify-work`:** Full suite must be green — all 6 sections verified in browser (editor preview matches published page for each section's controls), all greps green, no `#fff`/`#eee` in `.ddmm-editor-preview`, no hardcoded literal colors in the Phase 5 active-marker rules.
- **Max feedback latency:** ~4 seconds

---

## Per-Requirement Verification Map

> Style Tab controls are PHP data structures — they cannot be unit-tested without a loaded Elementor environment. Validation is **structural grep** (each required control exists, has a `selectors`/`selector` entry, sits in the right tab) plus **manual browser verification** (editor renders controls; changing a control updates editor preview AND published page).
>
> Plan/Wave columns reflect the researcher's suggested decomposition (06-01: Trigger/Drawer/Header + D-01 baseline; 06-02: Panel-Back/Menu-Items/Search; 06-03: editor-preview parity). The planner may restructure — task IDs are filled during planning.

| Req ID | Plan | Wave | Behavior | Test Type | Automated Command | Status |
|--------|------|------|----------|-----------|-------------------|--------|
| STYL-01 | 06-01 | 1 | Trigger section: COLOR, SLIDER (hamburger), DIMENSIONS (padding), Group_Control_Border, Group_Control_Typography, Normal/Hover tabs | static grep | `grep -cE "section_style_trigger" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "trigger_(color\|bg\|hover_color\|hover_bg\|padding\|border\|text_typography\|hamburger_width)" src/Elementor/Widget/DrillDownMenu.php` ≥8 AND `grep -c "start_controls_tabs" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ⬜ pending |
| STYL-02 | 06-01 | 1 | Drawer section: SLIDER (width px/vw/%), COLOR (bg), Group_Control_Box_Shadow, COLOR (overlay alpha) | static grep | `grep -cE "section_style_drawer" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "drawer_(width\|bg\|box_shadow)" src/Elementor/Widget/DrillDownMenu.php` ≥3 AND `grep -cE "overlay_color\|overlay_bg" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "'px', *'vw', *'%'" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ⬜ pending |
| STYL-03 | 06-01 | 1 | Header section: COLOR (bg), Group_Control_Border, SLIDER (height), Group_Control_Typography (title), COLOR (title), COLOR (close) | static grep | `grep -cE "section_style_header" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "header_(bg\|border\|height\|title_typography\|title_color\|close_color)" src/Elementor/Widget/DrillDownMenu.php` ≥6 | ⬜ pending |
| STYL-04 | 06-02 | 2 | Panel+Back Row: COLOR (back text), COLOR (back bg normal), COLOR (back bg hover), Group_Control_Typography (title), COLOR (title), COLOR (divider) | static grep | `grep -cE "section_style_panel_back\|section_style_back" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "back_(color\|bg\|hover_bg\|title_typography\|title_color)" src/Elementor/Widget/DrillDownMenu.php` ≥5 AND `grep -cE "divider_color\|menu_border_color" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ⬜ pending |
| STYL-05 | 06-02 | 2 | Menu Items: Normal/Hover/**Active** tabs (text/bg color each), min-height SLIDER, DIMENSIONS padding, Group_Control_Typography, chevron color | static grep | `grep -cE "section_style_menu_items\|section_style_items" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "menu_item_(normal\|hover\|active)" src/Elementor/Widget/DrillDownMenu.php` ≥3 AND `grep -cE "ddmm-current-item\|ddmm-current-ancestor" src/Elementor/Widget/DrillDownMenu.php` ≥2 AND `grep -cE "menu_item_(min_height\|padding\|typography\|chevron_color\|arrow_color)" src/Elementor/Widget/DrillDownMenu.php` ≥4 | ⬜ pending |
| STYL-06 | 06-02 | 2 | Search section (conditional on `search_enabled === 'yes'`): COLOR (bg), COLOR (text), Group_Control_Border, SLIDER (radius), Group_Control_Typography (input + results per D-02) | static grep | `grep -cE "section_style_search" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "search_(input_bg\|input_color\|input_border\|input_typography)" src/Elementor/Widget/DrillDownMenu.php` ≥4 AND `grep -cE "'condition' *=> *\[[^]]*'search_enabled' *=> *'yes'" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ⬜ pending |
| SC#5 / D-07 | 06-03 | 3 | Editor preview reworked: emits trigger + header + back row + chevron + (optional) search using real BEM classes; `.ddmm-editor-preview` `#fff`/`#eee` removed; off-canvas transform neutralized | static grep | `grep -cE "ddmm-(trigger\|header\|back\|brand__text\|close\|chevron)" src/Rendering/DrawerRenderer.php` ≥6 AND `grep -cE "#fff\b\|#eee\b\|#ffffff\b\|#eeeeee\b" assets/css/ddmm-frontend.css` reviewed — zero matches INSIDE the `.ddmm-editor-preview` block | ⬜ pending |
| SC#5 / D-08 | 06-03 | 3 | All 6 Style Tab sections visible in the editor preview and matching the published page | manual | browser inspection of editor preview (see Manual-Only Verifications) | ⬜ pending |
| PLUG-06 (carry) | all | — | No unescaped output in new PHP | negative grep | `grep -nE "echo\s+\\\$\|printf\([^)]*%s" src/Rendering/DrawerRenderer.php` reviewed — every dynamic value wrapped in `esc_attr`/`esc_html`/`esc_url` or carries the `phpcs:ignore … OutputNotEscaped` comment | ✅ must hold |
| BEM stable (carry) | all | — | No new BEM class names introduced beyond the Phase 4 catalog (only new `--ddmm-*` vars allowed) | static grep | `grep -oE "\.ddmm-[a-z0-9_-]+" assets/css/ddmm-frontend.css` count compared before vs after — no new class names | ✅ must hold |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

No test framework to install — validation rides on `php -l` + structural grep + manual browser checks (existing infrastructure). The "gaps" below are the implementation targets the greps verify; they are created by the plans, not Wave 0:

- [ ] `src/Elementor/Widget/DrillDownMenu.php` — add the 6 `TAB_STYLE` sections to `_register_controls()` (all STYL-01..06 greps depend on this)
- [ ] `assets/css/ddmm-frontend.css` — (a) refine baseline defaults (D-01): softer border color, drawer shadow var, refined spacing; (b) remove `.ddmm-editor-preview .ddmm-menu__item { background:#fff; border-bottom:1px solid #eee; }` hardcoded colors (D-07); (c) add `.ddmm-editor-preview .ddmm-drawer { position:static; transform:none; height:auto; }` neutralizer (Pitfall 8); (d) audit Phase 5 `.ddmm-current-item` rule is `!important`-free + var-driven (Pitfall 2)
- [ ] `src/Rendering/DrawerRenderer.php` — rework `render_editor_preview()` to emit full representative markup (trigger + header + brand text + close + back row + sample current-item + chevron + optional search row)
- [ ] Environment probe: confirm `php` available; if absent, document linter-web-tool fallback

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Each Style Tab section renders in the Elementor editor with the expected controls | STYL-01..06 | Elementor editor requires a loaded WP+Elementor environment; controls are PHP data structures | Open the widget in Elementor editor → Style tab → confirm each of the 6 sections is present with the expected controls (per STYL-01..06 field lists) |
| Editor preview ≡ published page for each section (SC#5 / D-08) | SC#5 | Visual parity across two rendering contexts cannot be grep-verified | For each section: set a distinctive value (loud color / large padding) in the Style control → confirm it appears in BOTH the editor preview block AND the published frontend drawer |
| Active state styles current item + ancestor trail (D-04) | STYL-05 | Requires a navigated state with current-page match | Navigate to a page in the menu → open drawer (auto-open drills to current) → confirm the current item AND each ancestor show the Active styling |
| Responsive sizing per breakpoint (D-05) | STYL-02 / STYL-05 | Requires Elementor responsive UI interaction | Set drawer-width / item-padding / typography to different values at mobile vs tablet vs desktop → resize editor preview → confirm each breakpoint honors its value |

*All four behaviors require the browser; the structural greps above confirm the controls exist before manual verification.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies (php -l + grep)
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 4s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
