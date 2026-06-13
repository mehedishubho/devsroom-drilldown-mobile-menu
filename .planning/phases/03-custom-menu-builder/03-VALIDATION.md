---
phase: 03
slug: custom-menu-builder
status: draft
nyquist_compliant: true
wave_0_complete: false
created: 2026-06-13
---

# Phase 03 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None configured (PHP lint only) |
| **Config file** | none — no phpunit.xml, no composer.json |
| **Quick run command** | `php -l <changed-file>` |
| **Full suite command** | N/A — no automated test suite |
| **Estimated runtime** | ~1 second per file (lint) |

---

## Sampling Rate

- **After every task commit:** Run `php -l <changed-file>` — syntax validation only
- **After every plan wave:** Manual verification in WP+Elementor instance
- **Before `/gsd-verify-work`:** Full manual checklist
- **Max feedback latency:** 1 second (lint)

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 03-01-01 | 01 | 1 | CMEN-01 | — | Repeater controls validate inputs via Elementor | lint | `php -l src/Elementor/Widget/DrillDownMenu.php` | ❌ W0 | ⬜ pending |
| 03-01-02 | 01 | 1 | CMEN-03 | — | title_field expression escapes output | lint | `php -l src/Elementor/Widget/DrillDownMenu.php` | ❌ W0 | ⬜ pending |
| 03-01-03 | 01 | 1 | CMEN-01, CMEN-05 | — | render() integrates CustomTree call | lint | `php -l src/Elementor/Widget/DrillDownMenu.php` | ❌ W0 | ⬜ pending |
| 03-02-01 | 02 | 1 | CMEN-02, CMEN-04 | — | Tree builder returns pure data, no escaping | lint | `php -l src/MenuBuilder/CustomTree.php` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

No Wave 0 needed — existing infrastructure covers Phase 3 requirements (PHP lint for syntax, manual testing in WP+Elementor instance).

*Existing infrastructure covers all phase requirements.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Repeater controls render in Elementor editor | CMEN-01 | Requires live Elementor instance | Open Elementor editor, add widget, select "Custom Builder" menu source. Verify repeater appears with Label, URL, Depth, Icon, Open in New Tab fields. |
| Indent dashes display in repeater items | CMEN-03 | Requires live Elementor editor | Add items with depth 0, 1, 2. Verify titles show as "Item", "— Item", "—— Item". |
| Stack-based tree produces correct nesting | CMEN-02, CMEN-04 | Requires WP environment with widget | Add items: Root(0), Child(1), Grandchild(2), Root2(0). Temporarily error_log the tree. Verify nested children with correct has_children flags. |
| Icons render in tree data | CMEN-05 | Requires Elementor Icons picker | Add items with icons. Verify tree nodes carry icon data for Phase 4. |
| Empty custom menu state | D-08 | Requires editor + published page comparison | Select Custom Builder, add no items. Editor shows hint. Published page shows zero menu HTML. |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 1s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
