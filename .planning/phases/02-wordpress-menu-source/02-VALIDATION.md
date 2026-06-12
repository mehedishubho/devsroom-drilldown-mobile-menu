---
phase: 2
slug: wordpress-menu-source
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-06-13
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None configured — manual validation only (consistent with Phase 1) |
| **Config file** | none — no `phpunit.xml`, no `composer.json`, no `tests/` directory |
| **Quick run command** | `php -l src/MenuBuilder/WpNavTree.php` (PHP syntax lint — the only automated check available) |
| **Full suite command** | N/A — no automated suite exists |
| **Estimated runtime** | ~2 seconds (lint) |

**Note:** No automated test infrastructure exists in this project. Phase 1 was validated manually in a WP environment. Phase 2 follows the same approach: PHP lint for syntax + manual verification in a WP+Elementor instance. Adding PHPUnit/WP test bootstrap is explicitly out of scope (no `composer.json`; would be a Wave 0 infra task deferred to a future dedicated effort).

---

## Sampling Rate

- **After every task commit:** Run `php -l <changed-file>` — syntax validation only (fast, no WP needed)
- **After every plan wave:** Manual verification in WP+Elementor instance with a 3+ level test menu
- **Before `/gsd-verify-work`:** Full manual checklist green (dropdown populated, tree correct for flat/nested/WooCommerce menus, empty state shows hint in editor and nothing on frontend)
- **Max feedback latency:** ~2 seconds (lint); manual checks gated to wave boundaries

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 02-01-01 | 01 | 1 | WMEN-01 | — | N/A — SELECT control restricts options to real `term_id`s (Elementor-enforced) | manual (WP+Elementor) | `php -l <widget.php>` | ✅ exists | ⬜ pending |
| 02-02-01 | 02 | 1 | WMEN-02 | — | Tree builder outputs pure data (arrays/strings), no HTML output | manual + PHP lint | `php -l src/MenuBuilder/WpNavTree.php` | ❌ W0 (file created this phase) | ⬜ pending |
| 02-02-02 | 02 | 1 | WMEN-03 | — | Leaf nodes carry `url` unescaped (renderer escapes in Phase 4) | manual (inspect tree output via `error_log(print_r())`) | — | N/A | ⬜ pending |
| 02-02-03 | 02 | 1 | WMEN-04 | — | Parent nodes carry `children[]` and `has_children=true` | manual (inspect tree output) | — | N/A | ⬜ pending |
| 02-02-04 | 02 | 1 | WMEN-05 | — | WooCommerce items flow through tree builder unchanged (trusted admin data) | manual (WC active, inspect tree) | — | N/A | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [x] No framework to install — manual validation is the established project pattern (Phase 1 precedent)
- [x] Developer's local WP+Elementor instance serves as the test environment

*Existing infrastructure (PHP lint + local WP instance) covers all phase requirements. No Wave 0 setup work required — the tree builder file is created during the phase, not as Wave 0.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Dropdown lists all registered WP menus | WMEN-01 | Requires live WP instance with registered nav menus + Elementor editor | In WP Admin create 2+ menus; open Elementor editor; add widget; verify Content Tab "Menu" section dropdown lists every menu by name |
| 3-pass tree produces correct nested tree | WMEN-02 | `wp_get_nav_menu_items()` requires WP bootstrap; no PHPUnit/WP test suite configured | Build a 3+ level menu; select it; temporarily `error_log(print_r($tree,true))` during render; verify index-by-ID → link-children → extract-roots structure with zero PHP references |
| Leaf items carry url, has_children=false | WMEN-03 | Inspect runtime tree output | From the logged tree, confirm leaf nodes have `has_children=false` and a populated `url` |
| Parent items carry children[], has_children=true | WMEN-04 | Inspect runtime tree output | From the logged tree, confirm parent nodes have `has_children=true` and non-empty `children[]` |
| WooCommerce items appear with correct URLs | WMEN-05 | Requires WooCommerce active + WC menu items | Activate WooCommerce; add Cart/My Account/Checkout/Shop to a menu; verify they appear in tree with correct URLs |
| Empty/no-menu renders nothing on frontend, hint in editor | WMEN-01 (D-05) | Editor vs frontend mode detection | Leave widget unconfigured; in editor see "Select a menu" hint; on frontend view page source confirms zero widget HTML |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies (lint for code files; manual map for runtime behavior)
- [x] Sampling continuity: no 3 consecutive tasks without automated verify (lint runs on every PHP file commit)
- [x] Wave 0 covers all MISSING references (no MISSING — manual validation is the project pattern)
- [x] No watch-mode flags
- [x] Feedback latency < 2s (lint); manual checks gated to wave boundaries by design
- [x] `nyquist_compliant: true` set in frontmatter (manual-only validation is justified: tree builder depends on `wp_get_nav_menu_items()`, which cannot be exercised without a WP bootstrap the project does not have)

**Approval:** pending
