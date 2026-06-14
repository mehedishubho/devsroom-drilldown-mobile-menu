---
phase: 07-accessibility-compatibility-polish
plan: 04
subsystem: verification
tags: [verification, uat, comp-03, wc-agnostic, manual-testing, documentation]

# Dependency graph
requires:
  - "07-01 (keyboard/focus/SR wiring — provides tests #1-#8 prerequisites)"
  - "07-02 (CSS a11y :focus-visible / reduced-motion / RTL — provides tests #10, #11, #12 prerequisites)"
  - "07-03 (i18n pipeline — provides test #14 prerequisite)"
provides:
  - "COMP-03 static proof: grep across src/ and assets/ returns 0 WC-detection calls"
  - "07-HUMAN-UAT.md — the authoritative 14-test manual verification matrix closing Phase 7"
affects:
  - "Phase 7 acceptance now has a clear human-executable verification path"
  - "The COMP-03 invariant grep is a CI-verifiable regression check for future commits"

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Static grep as architectural invariant: grep -rnE 'class_exists\\(\\s*[\\'\"]Woocommerce|wc_get_|aria-disabled' returning 0 is the proof that WC-agnostic construction (D-12) is preserved across future commits"

key-files:
  created:
    - ".planning/phases/07-accessibility-compatibility-polish/07-HUMAN-UAT.md (14 numbered manual UAT test cases)"
  modified: []

decisions:
  - "Task 07-04-01 is verification-only — NO code change, NO commit. The grep result is the deliverable; it is recorded in this SUMMARY as the COMP-03 proof."
  - "07-HUMAN-UAT.md uses the ### N. Title heading format so the verifier can grep '^### [0-9]+\\.' and expect exactly 14 matches"
  - "Each test carries an Evidence reference line citing the shipped line numbers from 07-01/02/03 summaries — so the human tester can cross-reference the static wiring while observing live behavior"
  - "07-HUMAN-UAT.md status is 'partial' (all 14 tests pending) — Phase 7 code is complete; live human execution is the only remaining gate"

patterns-established:
  - "Phase 7 verification split: static grep proves the WC-agnostic invariant; human UAT proves live keyboard/SR/CSS/WC/i18n behavior — the two are complementary, not redundant"

requirements-completed: [COMP-03]

# Metrics
duration: 2min
completed: 2026-06-14
---

# Phase 7 Plan 04: COMP-03 Verification + Human UAT Matrix Summary

COMP-03 statically proven (zero WooCommerce-detection calls across `src/` and `assets/` — WC-agnostic by construction per D-12/D-13), and the full 14-test manual verification matrix (`07-HUMAN-UAT.md`) authored covering every Phase 7 live-behavior truth Plans 07-01/02/03 deferred to manual execution.

## Performance

- **Duration:** 2 min
- **Started:** 2026-06-14T17:47:23Z
- **Completed:** 2026-06-14T17:49:40Z
- **Tasks:** 2 (1 verification-only, 1 doc-authoring)
- **Files created:** 1 (07-HUMAN-UAT.md)
- **Files modified:** 0

## Accomplishments

### Task 07-04-01: COMP-03 static verification (WC-agnostic by construction)

**Verification-only task — NO code change. NO commit.** The deliverable is the grep result recorded here.

**The COMP-03 invariant grep returned ZERO matches:**

```
grep -rnE "class_exists\(\s*['\"](Woocommerce|WooCommerce|WC)\b|wc_get_|aria-disabled" src/ assets/
```

- `src/` — 0 matches
- `assets/` — 0 matches
- **Total: 0** ✓

This proves the plugin contains:
- No `class_exists('Woocommerce')` / `class_exists('WooCommerce')` / `class_exists('WC')` detection calls
- No `wc_get_*` WooCommerce API calls
- No `aria-disabled` dead-link hiding (D-13 mandates render-as-is)

**Positive WC-agnostic evidence cited:**

| File | Line | Code | Significance |
|------|------|------|--------------|
| `src/MenuBuilder/WpNavTree.php` | 45 | `'url' => $item->url,` | Direct read from `wp_get_nav_menu_items()` return value. WC endpoints (Cart, My Account, Checkout, Shop) are stored as full URLs by WordPress; the plugin renders them as-is — no WC API call, no detection, no branching. |
| `src/MenuBuilder/CustomTree.php` | 53-57 | `$item['url']` / `$item['url']['url']` extraction | URL extraction from Elementor URL control format. Same pattern — direct URL read, no WC branching. |

**Additional acceptance greps (all PASS):**
- `grep -cE '\$item->url' src/MenuBuilder/WpNavTree.php` = 1 (>= 1) ✓
- `grep -cE "wc_get_|class_exists\(\s*['\"]WooCommerce|class_exists\(\s*['\"]WC" src/MenuBuilder/WpNavTree.php` = 0 ✓
- `grep -cE "wc_get_|class_exists\(\s*['\"]WooCommerce|class_exists\(\s*['\"]WC" src/MenuBuilder/CustomTree.php` = 0 ✓
- `git status --short` clean (no source file modified by this task) ✓

**Live-behavior flag (deferred to 07-HUMAN-UAT.md #13):** The static grep cannot verify that WC menu items render with correct URLs in both WC-active and WC-inactive states — that requires actually toggling the WooCommerce plugin in a browser. Deferred to 07-HUMAN-UAT.md test #13 (authored in Task 07-04-02).

### Task 07-04-02: 07-HUMAN-UAT.md authored with 14 numbered test cases

Created `.planning/phases/07-accessibility-compatibility-polish/07-HUMAN-UAT.md` with EXACTLY 14 numbered test cases using the `### N. Title` heading format.

**The 14 tests (per 07-VALIDATION.md Manual-Only Verifications table):**

| # | Title | Verifies | Plan ref |
|---|-------|----------|----------|
| 1 | Esc navigates back one panel level | A11Y-04 (back), D-02 | 07-01 T2 |
| 2 | Esc at root closes the drawer | A11Y-04 (close), D-02 | 07-01 T2 |
| 3 | Tab trap wraps focus within the drawer | A11Y-05, D-01 | 07-01 T2 |
| 4 | ArrowUp/ArrowDown move roving tabindex among siblings | A11Y-06, D-11 | 07-01 T2 |
| 5 | Enter/Space activates chevron, back button, and leaf link | A11Y-07, D-04/D-06 | 07-01 T2 |
| 6 | Focus moves to drawer on open, restored to trigger on close | A11Y-08, D-03/D-07 | 07-01 T2 |
| 7 | Esc coordination with the search-input listener (no double-fire) | Pitfall 1, D-06 | 07-01 T2 |
| 8 | Screen reader announces panel context on drill/back | D-08 | 07-01 T1+T2 |
| 9 | Screen reader announces search result count (incl. "No results") | D-09 | 07-03 T4 (bridge) |
| 10 | :focus-visible ring visible for keyboard, invisible for mouse | D-10, A11Y-08 | 07-02 T1 |
| 11 | prefers-reduced-motion neutralizes transitions | D-18 | 07-02 T2 |
| 12 | RTL baseline — no visible breakage under dir="rtl" | D-19 | 07-02 T3 |
| 13 | WooCommerce menu items render correctly in both WC states | COMP-03, D-12, D-13 | 07-04 T1 |
| 14 | Translated strings display in a non-English locale | COMP-04, D-15/D-16/D-17 | 07-03 (full) |

**Each test carries:**
- `**Verifies:**` line with requirement ID(s) and decision IDs
- `**Plan ref:**` line with source plan and task
- `**Prerequisite:**` section
- Numbered `**Steps:**`
- `**Expected:` outcome
- `**Evidence reference:**` line citing the shipped line numbers from 07-01/02/03 summaries
- `**result:** [pending]` placeholder for human tester

**Document metadata:**
- `status: partial` (all 14 tests pending human execution)
- `started` / `updated` ISO timestamps (2026-06-14T17:47:23Z)
- Summary table (total=14, passed=0, failed=0, issues=0, pending=14)
- `## Gaps` section documenting the pending execution requirements (WC install for #13, .po/.mo for #14, SR software for #8/#9)

## Task Commits

| Task | Name | Commit | Type |
|------|------|--------|------|
| 07-04-01 | COMP-03 static verification (WC-agnostic by construction) | (no commit — verification-only) | docs (recorded here) |
| 07-04-02 | Author 07-HUMAN-UAT.md with 14 numbered test cases | `92b85c4` | docs |

## Files Created/Modified

- **`.planning/phases/07-accessibility-compatibility-polish/07-HUMAN-UAT.md`** (CREATED, 334 lines) — the authoritative 14-test manual UAT matrix. Each test maps to a Phase 7 live-behavior truth that static greps cannot prove. Carries evidence-reference lines pointing at the shipped line numbers so the tester can cross-reference static wiring.

## Acceptance Criteria — All Passed at Commit Time

### Task 07-04-01 (verification-only)
- `grep -rnE "class_exists\(\s*['\"](Woocommerce|WooCommerce|WC)\b|wc_get_|aria-disabled" src/ assets/ | wc -l` returns 0 ✓
- `grep -cE '\$item->url' src/MenuBuilder/WpNavTree.php` returns 1 (>= 1) ✓
- `grep -cE "wc_get_|class_exists\(\s*['\"]WooCommerce|class_exists\(\s*['\"]WC" src/MenuBuilder/WpNavTree.php` returns 0 ✓
- `grep -cE "wc_get_|class_exists\(\s*['\"]WooCommerce|class_exists\(\s*['\"]WC" src/MenuBuilder/CustomTree.php` returns 0 ✓
- No source file modified (git status clean) ✓

### Task 07-04-02 (commit 92b85c4)
- `test -f .planning/phases/07-accessibility-compatibility-polish/07-HUMAN-UAT.md` succeeds ✓
- `grep -cE '^### [0-9]+\.' .planning/phases/07-accessibility-compatibility-polish/07-HUMAN-UAT.md` = 14 ✓
- `grep -cE '^### 1\. |^### 2\. |...|^### 14\. '` = 14 (every number 1-14 present, no gaps) ✓
- `grep -cE 'A11Y-04|A11Y-05|A11Y-06|A11Y-07|A11Y-08|COMP-03|COMP-04'` = 10 (>= 7) ✓
- `grep -cE 'D-0[1-9]|D-1[0-9]'` = 22 (>= 7) ✓
- `grep -cE '^\*\*Prerequisite:\*\*'` = 14 (>= 14) ✓

## Decisions Made

- **Task 07-04-01 produces no commit.** The plan explicitly specifies "This task makes NO code change" and "The verification report is recorded in the plan SUMMARY, not in a separate file." The grep result is the deliverable; it lives in this SUMMARY under Task 07-04-01. This is the correct behavior for a verification-only task — committing an empty change would be noise.
- **07-HUMAN-UAT.md test count verification uses the exact regex from the plan's acceptance_criteria.** `^### [0-9]+\.` — the `\.` is load-bearing; without it `### 10.` would also match `### 1.` via prefix. Both `grep -cE '^### [0-9]+\.'` (=14) and the explicit `^### 1\. |^### 2\. |...|^### 14\. ` check (=14) agree.
- **Each test carries an Evidence reference line.** This goes slightly beyond the plan's literal body (which specifies `**Verifies:**` / `**Plan ref:**` / prerequisite / steps / expected) — the Evidence reference line was added so the human tester can cross-reference the static line numbers cited in 07-01/02/03 summaries while observing live behavior. This is a documentation enhancement, not a deviation from the plan's intent; it does not change the test count or structure.

## Deviations from Plan

None — plan executed exactly as written. Task 07-04-01 ran the grep verification (0 matches, all acceptance greps green). Task 07-04-02 created 07-HUMAN-UAT.md with exactly 14 numbered test cases in the specified `### N. Title` format, each with prerequisite/steps/expected, referencing the required requirement IDs and decision IDs.

The Evidence reference lines added to each test are an additive documentation enhancement (see Decisions Made above); the test structure, count, numbering, and required cross-references all match the plan exactly.

## Issues Encountered

None. Both tasks passed all acceptance greps on the first attempt.

## Known Stubs

None. 07-HUMAN-UAT.md is a complete manual verification matrix — the `**result:** [pending]` placeholders are the correct state for a document awaiting human execution (not stubs in the data-flow sense). The document itself is wired to the actual shipped line numbers from 07-01/02/03 summaries; every Evidence reference points at real, verified code.

## Threat Flags

None new beyond the plan's `<threat_model>`. T-07-04-01 (Tampering: future commits adding WC-coupling) is mitigated by Task 07-04-01's static grep invariant — the verifier can re-run the exact grep on any future commit to detect regression. No new threat surface introduced by this plan (it is verification + documentation only).

## User Setup Required

None for the static verification (Task 07-04-01). For the human UAT (07-HUMAN-UAT.md):
- Tests #8, #9 require NVDA (Windows) or VoiceOver (macOS) running
- Test #13 requires WooCommerce installed and activatable on the test site
- Test #14 requires a hand-authored `.po`/`.mo` for a non-English locale (the shipped `.pot` is the template)

## Next Phase Readiness

- Phase 7 code is complete. All static acceptance criteria across Plans 07-01, 07-02, 07-03, and 07-04 are green.
- The ONLY remaining gate is human UAT execution of 07-HUMAN-UAT.md tests #1-#14.
- After the human tester signs off all 14 tests, Phase 7 is complete and the project is ready for the v1.0 release milestone.

---
*Phase: 07-accessibility-compatibility-polish*
*Plan: 04*
*Completed: 2026-06-14*

## Self-Check: PASSED

- .planning/phases/07-accessibility-compatibility-polish/07-HUMAN-UAT.md — FOUND
- .planning/phases/07-accessibility-compatibility-polish/07-04-SUMMARY.md — FOUND
- Commit 92b85c4 (Task 07-04-02) — FOUND
- COMP-03 grep across src/ and assets/ — 0 matches (WC-agnostic proven)
- HUMAN-UAT test count — 14 (exactly 14)
- Requirement IDs referenced in HUMAN-UAT — 10 matches (>= 7)
- Decision IDs referenced in HUMAN-UAT — 22 matches (>= 7)
- Prerequisite sections in HUMAN-UAT — 14 (= 14)
