---
phase: 7
slug: accessibility-compatibility-polish
status: approved
nyquist_compliant: true
wave_0_complete: true
created: 2026-06-14
---

# Phase 7 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | None — this zero-dependency plugin deliberately has no PHPUnit/Jest suite (CLAUDE.md stack: no build step, no Composer runtime). All validation splits into static grep + human UAT. |
| **Config file** | none |
| **Quick run command** | `php -l <file>` for PHP files; `node --check assets/js/ddmm-frontend.js` for JS; `grep -cE "<pattern>" <file>` for wiring checks |
| **Full suite command** | `find src -name '*.php' -exec php -l {} \; && node --check assets/js/ddmm-frontend.js` |
| **Estimated runtime** | ~5 seconds |

**Implication:** Wave 0 does NOT add a test framework (out of scope — would be its own phase). Validation splits:
- **Static/grep checks** (verifiable by the plan-checker / verifier) — wiring, attrs, file existence, text-domain loading.
- **Human UAT** (deferred to `07-HUMAN-UAT.md`) — keyboard flows, SR announcements, reduced-motion, RTL baseline, WC states, translated-string display.

---

## Sampling Rate

- **After every task commit:** `php -l <touched-php-file>` + `node --check assets/js/ddmm-frontend.js` (when touched) + grep spot-checks for the task's wiring patterns.
- **After every plan wave:** `find src -name '*.php' -exec php -l {} \;` full lint sweep + the cumulative grep checklist.
- **Before `/gsd-verify-work`:** Full lint sweep green + every grep-verifiable acceptance_criterion from all 4 plans checked.
- **Max feedback latency:** ~10 seconds (single PHP lint + JS syntax check).

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 07-01-01 | 01 | 1 | A11Y-08 (D-08) | T-07-01-01 | aria-live region emitted EMPTY; JS writes via textContent | grep | `php -l src/Rendering/DrawerRenderer.php` + `grep -cE "data-ddmm-sr-status" src/Rendering/DrawerRenderer.php` | ✅ | ⬜ pending |
| 07-01-02 | 01 | 1 | A11Y-04, A11Y-05, A11Y-06, A11Y-07, A11Y-08 | T-07-01-01..05 | per-container scoping via contains() check; textContent-only writes; docHandler detached in close() | grep + node-check | `node --check assets/js/ddmm-frontend.js` + 20 wiring greps (see 07-01 Task 2 acceptance_criteria) | ✅ | ⬜ pending |
| 07-02-01 | 02 | 1 | A11Y-08 (D-10) | T-07-02-01, T-07-02-02 | :focus-visible on 6 BEM surfaces; no `outline: none` | grep | `grep -cE "focus-visible" assets/css/ddmm-frontend.css` ≥ 7 | ✅ | ⬜ pending |
| 07-02-02 | 02 | 1 | (D-18) | T-07-02-04 | prefers-reduced-motion block; 0.01ms not 0ms (Pitfall 8) | grep | `grep -cE "prefers-reduced-motion: reduce" assets/css/ddmm-frontend.css` == 1 | ✅ | ⬜ pending |
| 07-02-03 | 02 | 1 | (D-19) | — | 3 logical-property refactors; transform: translateX untouched | grep | `grep -cE "inset-inline-start|margin-inline-end|margin-inline-start" assets/css/ddmm-frontend.css` ≥ 4 | ✅ | ⬜ pending |
| 07-03-01 | 03 | 2 | COMP-04 (D-17) | T-07-03-05 | Domain Path header present | grep | `php -l devsroom-drilldown-mobile-menu.php` + `grep -cE "Domain Path: /languages" devsroom-drilldown-mobile-menu.php` | ✅ | ⬜ pending |
| 07-03-02 | 03 | 2 | COMP-04 (D-17) | T-07-03-05 | load_plugin_textdomain first line of init() | grep | `php -l src/Plugin.php` + `grep -cE "load_plugin_textdomain" src/Plugin.php` | ✅ | ⬜ pending |
| 07-03-03 | 03 | 2 | COMP-04 (D-16) | T-07-03-01, T-07-03-02 | wp_set_script_translations + wp_add_inline_script via wp_json_encode | grep | `php -l src/Assets/Registrar.php` + `grep -cE "wp_json_encode" src/Assets/Registrar.php` | ✅ | ⬜ pending |
| 07-03-04 | 03 | 2 | COMP-04 (D-16) | T-07-03-03 | 'No results' literal replaced with window.ddmmI18n bridge lookup | grep + node-check | `node --check assets/js/ddmm-frontend.js` + `grep -cE "window\\.ddmmI18n && window\\.ddmmI18n\\.noResults" assets/js/ddmm-frontend.js` | ✅ | ⬜ pending |
| 07-03-05 | 03 | 2 | COMP-04 (D-15) | T-07-03-04 | languages/devsroom-drilldown-mobile-menu.pot exists with 'No results' msgid (Pitfall 4) | cli | `test -f languages/devsroom-drilldown-mobile-menu.pot && grep -cE 'msgid "No results"' languages/devsroom-drilldown-mobile-menu.pot` | ❌ W0 (human-action: WP-CLI) | ⬜ pending |
| 07-04-01 | 04 | 3 | COMP-03 (D-12, D-13) | T-07-04-01 | zero WC-detection calls + zero dead-link hiding | grep | `grep -rnE "class_exists\\(\\s*['\"](Woocommerce|WooCommerce|WC)\\b|wc_get_|aria-disabled" src/ assets/` returns 0 | ✅ | ⬜ pending |
| 07-04-02 | 04 | 3 | (D-09, D-14) | — | 07-HUMAN-UAT.md exists with 14 test cases covering all live-behavior truths | grep | `test -f 07-HUMAN-UAT.md && grep -cE "^### [0-9]+\\." 07-HUMAN-UAT.md` == 14 | ❌ W0 (file created in-task) | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [x] `languages/` directory — created by Task 07-03-05 (the WP-CLI checkpoint includes `mkdir -p languages`)
- [x] `devsroom-drilldown-mobile-menu.pot` — generated by Task 07-03-05
- [x] `Domain Path:` header — added by Task 07-03-01
- [x] `load_plugin_textdomain()` — wired by Task 07-03-02
- [x] `wp_set_script_translations()` — wired by Task 07-03-03
- [x] JS `'No results'` literal — converted by Task 07-03-04
- [x] WP-CLI availability — verified at planning time (`command -v wp` returned "not in PATH"); Task 07-03-05 is a `checkpoint:human-action` with a hand-author fallback

*No test framework gaps — the plugin deliberately has no automated test suite per CLAUDE.md (zero-dependency stack).*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Esc routes through back() then close() | A11Y-04 | Runtime keydown event handling + history state | 07-HUMAN-UAT.md tests #1, #2 |
| Tab trap wraps focus within drawer | A11Y-05 | Live focus cycling + Shift+Tab direction | 07-HUMAN-UAT.md test #3 |
| Arrow ↑/↓ move roving tabindex among siblings | A11Y-06 | Runtime tabindex mutation + DevTools inspection | 07-HUMAN-UAT.md test #4 |
| Enter/Space activates chevron/back/leaf | A11Y-07 | Native activation + drill/back nav | 07-HUMAN-UAT.md test #5 |
| Focus moves to drawer on open, restored on close | A11Y-08 | Runtime focus() calls across close paths | 07-HUMAN-UAT.md test #6 |
| Esc coordination with search-input listener | Pitfall 1 | Two-listener event-bubble coordination | 07-HUMAN-UAT.md test #7 |
| SR announces panel context on drill/back | D-08 | Screen-reader output (NVDA/VoiceOver) | 07-HUMAN-UAT.md test #8 |
| SR announces search result count | D-09 | Screen-reader output on aria-live additions | 07-HUMAN-UAT.md test #9 |
| :focus-visible ring visible for keyboard, invisible for mouse | D-10 | Visual browser inspection + mouse vs keyboard | 07-HUMAN-UAT.md test #10 |
| prefers-reduced-motion neutralizes transitions | D-18 | OS preference + visual transition observation | 07-HUMAN-UAT.md test #11 |
| RTL baseline — no breakage under dir="rtl" | D-19 | dir attribute + visual layout inspection | 07-HUMAN-UAT.md test #12 |
| WooCommerce menu items render correctly both states | COMP-03 | Plugin activation toggle + URL inspection | 07-HUMAN-UAT.md test #13 |
| Translated strings display in non-English locale | COMP-04 | Site language switch + .po delivery | 07-HUMAN-UAT.md test #14 |

*The full manual matrix lives in `.planning/phases/07-accessibility-compatibility-polish/07-HUMAN-UAT.md` (produced by Task 07-04-02).*

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: every task has a lint or grep automated check; no 3 consecutive tasks without one
- [x] Wave 0 covers all MISSING references (languages/ dir, .pot, header, text-domain loading, JS literal — all created/wired in Phase 7 plans)
- [x] No watch-mode flags
- [x] Feedback latency < 10s (single PHP lint + JS syntax check)
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** approved 2026-06-14 (planner)
