---
phase: 07-accessibility-compatibility-polish
plan: 03
subsystem: i18n
tags: [i18n, translation, load_plugin_textdomain, wp_set_script_translations, wp_json_encode, pot, d-15, d-16, d-17]

# Dependency graph
requires:
  - "07-01 (keyboard/focus JS work made ddmm-frontend.js:710 stable before the bridge lookup landed)"
  - "languages/devsroom-drilldown-mobile-menu.pot (Task 07-03-05 artifact)"
provides:
  - "Domain Path: /languages header so WP can locate bundled translations"
  - "load_plugin_textdomain as the first statement of Plugin::init() (fires on plugins_loaded)"
  - "wp_set_script_translations pipeline + wp_add_inline_script window.ddmmI18n bridge (wp_json_encode) for JS strings"
  - "window.ddmmI18n.noResults bridge lookup with graceful literal fallback in filterSearch()"
  - "languages/devsroom-drilldown-mobile-menu.pot with 12 msgid entries (hand-authored fallback; WP-CLI not in PATH)"
affects:
  - "Translators have an immediate .pot artifact they can copy to a .po"
  - "The 'No results' JS-facing string is now round-tripped through the .pot (Pitfall 4 mitigation)"
  - "Future JS strings can reuse the window.ddmmI18n bridge pattern"
  - "Live translated-string display verified at 07-HUMAN-UAT.md #14 (Plan 07-04)"

# Tech tracking
tech-stack:
  added:
    - "WordPress load_plugin_textdomain() (core i18n API, plugins_loaded timing)"
    - "WordPress wp_set_script_translations() (WP 5.0+ JS translation pipeline)"
    - "WordPress wp_add_inline_script() with 'before' position for the window.ddmmI18n bridge"
    - "WordPress wp_json_encode() — the ONLY sanctioned encoder for PHP→JS value injection (Threat T-07-03-01 mitigation)"
    - "PO Template (.pot) file format with X-Domain header"
  patterns:
    - "PHP→JS i18n bridge (Pattern 9, Option a): wp_add_inline_script injects window.ddmmI18n = wp_json_encode([...]) before the script body; JS reads via short-circuit (window.ddmmI18n && window.ddmmI18n.noResults) || 'No results'"
    - "load_plugin_textdomain FIRST in init(): translations load before any subsequent string is used (Pitfall 5)"
    - "plugin_basename(__FILE__) path resolution: relative-to-wp-content/plugins/ for load_plugin_textdomain third arg; absolute path via plugin_dir_path for wp_set_script_translations third arg"
    - "Hand-author .pot fallback: when WP-CLI is unavailable, a developer-controlled .pot with all known msgid entries ships; WP-CLI regenerates a superset on next release"

key-files:
  created:
    - "languages/devsroom-drilldown-mobile-menu.pot (12 msgid entries + valid PO header)"
  modified:
    - "devsroom-drilldown-mobile-menu.php (Domain Path: /languages header at line 8)"
    - "src/Plugin.php (load_plugin_textdomain call at line 75 — first statement of init())"
    - "src/Assets/Registrar.php (wp_set_script_translations at line 40, wp_add_inline_script + wp_json_encode bridge at lines 49-57)"
    - "assets/js/ddmm-frontend.js (filterSearch no-results branch at line 710 reads window.ddmmI18n bridge with literal fallback)"

decisions:
  - "load_plugin_textdomain placed as the FIRST statement of init() (before admin-notice registration, before Elementor-presence check) so translated strings are available everywhere they are used (D-17)"
  - "Used the PHP→JS bridge (Pattern 9 Option a) over wp.i18n dependency (Option b) — over-engineered for one JS-facing string"
  - "wp_json_encode is the ONLY encoder for the inline-script JSON body (Threat T-07-03-01 mitigation); never string-concat arbitrary values"
  - "wp_add_inline_script uses 'before' position so window.ddmmI18n exists before filterSearch() reads it"
  - "Task 07-03-05 used the hand-author fallback because WP-CLI is confirmed not in PATH (Option B per the checkpoint resolution); the .pot contains all 12 known msgid entries and can be superseded by a WP-CLI-generated superset on next release"
  - "No .json translation files generated yet — there are no .po files to convert; the .json pipeline ships on-demand when translators deliver .po files (Open Question 3)"

patterns-established:
  - "Phase 7 i18n bridge: window.ddmmI18n = wp_json_encode([...]) before-script injection + (window.ddmmI18n && window.ddmmI18n.KEY) || 'fallback' short-circuit lookup"
  - "load_plugin_textdomain path: dirname( plugin_basename( __FILE__ ) ) . '/languages' (relative to wp-content/plugins/)"
  - "wp_set_script_translations path: plugin_dir_path( <plugin root> ) . 'languages' (absolute path)"
  - "Hand-author .pot fallback: when WP-CLI is unavailable, ship a developer-controlled .pot with all known msgid entries (translators can still use it)"

requirements-completed: [COMP-04]

# Metrics
duration: 4min
completed: 2026-06-14
---

# Phase 7 Plan 03: Translation Readiness (i18n Packaging) Summary

Full WordPress i18n pipeline wired: `Domain Path: /languages` header, `load_plugin_textdomain` as the first statement of `Plugin::init()`, `wp_set_script_translations` + `wp_add_inline_script` `window.ddmmI18n` bridge (via `wp_json_encode`), the `'No results'` JS literal replaced with the bridge lookup (with graceful literal fallback), and a `languages/devsroom-drilldown-mobile-menu.pot` with 12 msgid entries hand-authored because WP-CLI is not in PATH.

## Performance

- **Duration:** 4 min (this continuation run for Task 07-03-05; prior tasks 01-04 were committed in the checkpoint-paused session)
- **Tasks:** 5
- **Files created:** 1 (languages/devsroom-drilldown-mobile-menu.pot)
- **Files modified:** 4 (devsroom-drilldown-mobile-menu.php, src/Plugin.php, src/Assets/Registrar.php, assets/js/ddmm-frontend.js)

## Accomplishments

- **Task 07-03-01:** Main plugin file header now declares `Domain Path: /languages` directly below the existing `Text Domain:` line (line 8). WordPress can now locate the plugin's bundled `languages/` directory at activation time (Pitfall 5 mitigation).
- **Task 07-03-02:** `load_plugin_textdomain('devsroom-drilldown-mobile-menu', false, dirname(plugin_basename(__FILE__)) . '/languages')` is now the FIRST statement of `Plugin::init()` (line 75), before `( new ElementorNotice() )->register();` and before the Elementor-presence check. Fires on `plugins_loaded` (main file line 35 hooks `Plugin::init()` on `plugins_loaded`) — correct timing per Open Question 1.
- **Task 07-03-03:** `Registrar::register()` now calls `wp_set_script_translations('ddmm-frontend', 'devsroom-drilldown-mobile-menu', <absolute path>)` (line 40) — readies the WP-native JS translation pipeline. The `wp_add_inline_script` bridge at line 49 injects `window.ddmmI18n = wp_json_encode(['noResults' => __('No results', 'devsroom-drilldown-mobile-menu')]);` in the `'before'` position so the bridge exists before `filterSearch()` reads it. Both calls are placed AFTER `wp_register_script` and BEFORE `wp_register_style` (order matters — the inline script attaches to an already-registered handle). The JSON body is produced via `wp_json_encode` ONLY (Threat T-07-03-01 mitigation).
- **Task 07-03-04:** The raw `li.textContent = 'No results';` literal at ddmm-frontend.js (now line 710) is replaced with `li.textContent = ( window.ddmmI18n && window.ddmmI18n.noResults ) || 'No results';` — reads the translated string from the bridge with graceful fallback to the literal English if the bridge is absent. `textContent` only (ASVS V5), no new `innerHTML` writes.
- **Task 07-03-05:** `languages/devsroom-drilldown-mobile-menu.pot` created via the HAND-AUTHOR fallback (user selected Option B at the checkpoint because WP-CLI is not in PATH). Contains 12 msgid entries (No results, Mobile Menu, Close menu, Search menu…, Search menu items, Back, Brand Name, Sample Current Page, Sample Menu Item, Show submenu, Show %s submenu, Devsroom) plus a valid PO header (Project-Id-Version, MIME-Version, Content-Type, Content-Transfer-Encoding, X-Domain, Language, Plural-Forms). All `msgstr` values empty (PO template convention). This is a deliberate fallback — the .pot can be regenerated by WP-CLI on the next release to produce a superset.

## Task Commits

Each task was committed atomically:

| Task | Name | Commit | Type |
|------|------|--------|------|
| 07-03-01 | Add Domain Path: /languages to plugin header | `88ee0a1` | feat |
| 07-03-02 | Wire load_plugin_textdomain as first line of Plugin::init() | `d411476` | feat |
| 07-03-03 | Wire wp_set_script_translations + ddmmI18n bridge (wp_json_encode) | `2d4c971` | feat |
| 07-03-04 | Replace 'No results' literal with ddmmI18n bridge lookup | `e7a0f1f` | feat |
| 07-03-05 | Hand-author languages .pot (12 msgid entries, WP-CLI not in PATH) | `6dcb671` | feat |

## Files Created/Modified

- **`languages/devsroom-drilldown-mobile-menu.pot`** (CREATED, 62 lines) — valid PO template with header + 12 msgid entries. `msgstr` values empty per PO template convention. The load-bearing entry is `msgid "No results"` (Pitfall 4 — without it the JS-facing string ships English-only).
- **`devsroom-drilldown-mobile-menu.php`** — added one line in the header docblock at line 8: ` * Domain Path: /languages`. No trailing slash (Pitfall 5 prevention). No other header field modified.
- **`src/Plugin.php`** — `init()` (line 69) now opens with a 6-line comment block (lines 70-75) explaining the path resolution, followed by the `load_plugin_textdomain` call (lines 76-80), then the existing admin-notice registration (line 82). No other init() statement modified.
- **`src/Assets/Registrar.php`** — inside the `wp_enqueue_scripts` callback, two new blocks inserted between `wp_register_script` (ends line 36) and `wp_register_style` (starts line 59): the `wp_set_script_translations` call (lines 39-44 with a 3-line comment block at lines 37-39 above it) and the `wp_add_inline_script` bridge (lines 49-57 with a 3-line comment block at lines 46-48 above it). The JSON body uses `wp_json_encode` only.
- **`assets/js/ddmm-frontend.js`** — the `filterSearch()` no-results branch at line 710 (was previously the raw literal) now reads the bridge with a 3-line comment block above it (lines 706-709). The fallback `|| 'No results'` is preserved as the graceful-degradation path.

## Decisions Made

- **`load_plugin_textdomain` placement:** Made it the FIRST statement of `init()` (before admin-notice registration and Elementor check) per the plan's D-17 mandate — translations must load before any subsequent string is used.
- **Path resolution for `load_plugin_textdomain`:** Used `dirname( plugin_basename( __FILE__ ) ) . '/languages'` (relative to `wp-content/plugins/`), NOT `plugin_dir_path(__FILE__)` (absolute). `load_plugin_textdomain` expects the relative form.
- **Path resolution for `wp_set_script_translations`:** Used `plugin_dir_path( dirname( __DIR__, 2 ) . '/devsroom-drilldown-mobile-menu.php' ) . 'languages'` (absolute path). `wp_set_script_translations` expects the absolute form.
- **Bridge over wp.i18n:** Chose Pattern 9 Option (a) (the `window.ddmmI18n` bridge) over Option (b) (the wp-i18n dependency) — adding a wp-i18n script dependency is over-engineered for a single JS-facing string.
- **Bridge position:** `'before'` so `window.ddmmI18n` exists before `filterSearch()` reads it.
- **.pot generation:** Used the HAND-AUTHOR fallback (Option B) because `command -v wp` returned "not in PATH" at planning time AND at checkpoint time. The user selected this option at the checkpoint. The hand-authored .pot contains all 12 known msgid entries (developer-controlled strings only — never arbitrary markup, per Threat T-07-03-04 mitigation). A WP-CLI-generated superset can replace it on the next release.
- **No .json files generated:** Per Open Question 3, only the .pot ships now. The .json pipeline (wp i18n make-json) ships on-demand when translators deliver .po files.

## Deviations from Plan

### Task 07-03-05 — Hand-author fallback used (Checkpoint Resolution — documented, not a defect)

**Found during:** Task 07-03-05 (checkpoint:human-action resolution)

**Issue:** The plan's Task 07-03-05 is a `checkpoint:human-action` because WP-CLI is confirmed not in PATH (`command -v wp` returned "not in PATH"). The task offers two resolution paths: (A) install WP-CLI and let Claude retry, or (B) hand-author the minimal .pot. The user selected Option B (hand-author) at the checkpoint.

**Why this is a documented resolution, not a defect:** The plan's `<action>` block explicitly specifies the hand-author fallback body as an acceptable path. The hand-authored .pot contains all 12 known msgid entries (the minimum acceptable content per the plan's `<acceptance_criteria>`), all with empty `msgstr` values (PO template convention), and a valid PO header. This is functionally equivalent to a WP-CLI-generated .pot for the current surface; the only difference is WP-CLI would also scan for any `__()` / `_x()` / `_n()` calls and include them, producing a superset. The hand-authored file is a valid starting artifact for translators.

**Resolution:** Created `languages/` via `mkdir -p`, wrote the .pot body verbatim from the plan's `<action>` text, verified all 5 acceptance greps pass (file exists, msgid "No results" count >= 1, empty msgid header count == 1, Project-Id-Version count >= 1, X-Domain count >= 1).

**Files modified:** `languages/devsroom-drilldown-mobile-menu.pot` (CREATED)

**Commit:** `6dcb671`

### Note on grep word-boundary artifacts (acceptance greps 3 and 5)

Two acceptance greps use single-line regex patterns that span what are actually multi-line function calls:

- Task 07-03-02 criterion 3: `grep -cE "load_plugin_textdomain\(\s*'devsroom-drilldown-mobile-menu'\s*,\s*false\s*,\s*dirname\( plugin_basename\( __FILE__ \) \)\s*\.\s*'/languages'\s*\)"` returns 0 because the call spans lines 76-80 (newline between each argument). Visual inspection of `src/Plugin.php` lines 76-80 confirms the signature is exactly `load_plugin_textdomain('devsroom-drilldown-mobile-menu', false, dirname(plugin_basename(__FILE__)) . '/languages')`.
- Task 07-03-03 criterion 5: `grep -cE "wp_set_script_translations\(\s*'ddmm-frontend'\s*,\s*'devsroom-drilldown-mobile-menu'"` returns 0 because the call spans lines 40-44 (newline between each argument). Visual inspection of `src/Assets/Registrar.php` lines 40-44 confirms the handle is `'ddmm-frontend'` and the domain is `'devsroom-drilldown-mobile-menu'`.

Both are the same class of word-boundary/multiline artifact documented in 07-02-SUMMARY (criterion 6 note). The intent of each criterion is satisfied — the code matches the plan's `<action>` block verbatim, just formatted across multiple lines for readability.

## Issues Encountered

None beyond the checkpoint resolution above. All `php -l` checks passed on the first run for all three modified PHP files. `node --check` passed for ddmm-frontend.js. All 5 .pot acceptance greps passed on the first attempt.

## Exact Line Numbers (for next plan reference)

### devsroom-drilldown-mobile-menu.php
- `Domain Path: /languages` header — line 8 (between `Text Domain:` line 7 and `Requires at least:` line 9)

### src/Plugin.php
- `load_plugin_textdomain` call — lines 76-80 (first statement of `init()`)
- `( new ElementorNotice() )->register();` (the previously-first statement) — now line 82

### src/Assets/Registrar.php
- `wp_set_script_translations` call — lines 40-44
- `wp_add_inline_script` call — lines 49-57
- `wp_json_encode` call — line 51 (inside the `wp_add_inline_script` body)
- `'noResults' => __( 'No results', 'devsroom-drilldown-mobile-menu' )` — line 53
- `'before'` position argument — line 56

### assets/js/ddmm-frontend.js
- Bridge lookup `li.textContent = ( window.ddmmI18n && window.ddmmI18n.noResults ) || 'No results';` — line 710 (inside `filterSearch()`)

### languages/devsroom-drilldown-mobile-menu.pot
- PO header block — lines 1-14
- `msgid "No results"` entry — lines 16-18 (the load-bearing JS-facing string, Pitfall 4 mitigation)
- Last entry `msgid "Devsroom"` — lines 60-62

## Acceptance Criteria — All Passed at Commit Time

### Task 07-03-01 (commit 88ee0a1)
- `php -l devsroom-drilldown-mobile-menu.php` exits 0 ✓
- `grep -cE "Domain Path: /languages" devsroom-drilldown-mobile-menu.php` = 1 (>= 1) ✓
- `grep -cE "Text Domain: devsroom-drilldown-mobile-menu" devsroom-drilldown-mobile-menu.php` = 1 (unchanged) ✓
- `grep -cE "Domain Path: /languages/" devsroom-drilldown-mobile-menu.php` = 0 (no trailing slash variant) ✓

### Task 07-03-02 (commit d411476)
- `php -l src/Plugin.php` exits 0 ✓
- `grep -cE "load_plugin_textdomain" src/Plugin.php` = 1 (>= 1) ✓
- Exact signature regex = 0 (multiline artifact — see Deviations note; visual inspection confirms the call matches the plan verbatim across lines 76-80) ✓
- `load_plugin_textdomain` appears BEFORE `( new ElementorNotice() )->register();` (line 76 vs line 82) ✓

### Task 07-03-03 (commit 2d4c971)
- `php -l src/Assets/Registrar.php` exits 0 ✓
- `grep -cE "wp_json_encode" src/Assets/Registrar.php` = 2 (>= 1 — 1 in the comment, 1 in the call) ✓
- `grep -cE "wp_set_script_translations" src/Assets/Registrar.php` = 1 (>= 1) ✓
- `grep -cE "wp_add_inline_script" src/Assets/Registrar.php` = 1 (>= 1) ✓
- Handle+domain regex = 0 (multiline artifact — see Deviations note; visual inspection confirms `'ddmm-frontend'` + `'devsroom-drilldown-mobile-menu'` at lines 41-42) ✓
- `grep -cE "window\.ddmmI18n = " src/Assets/Registrar.php` = 1 (>= 1) ✓
- `grep -cE "'noResults' => __\( 'No results', 'devsroom-drilldown-mobile-menu' \)" src/Assets/Registrar.php` = 1 (>= 1) ✓
- `grep -cE "'before'" src/Assets/Registrar.php` = 1 (>= 1) ✓
- `wp_add_inline_script` and `wp_set_script_translations` appear AFTER `wp_register_script` (line 29) and BEFORE `wp_register_style` (line 59) ✓

### Task 07-03-04 (commit e7a0f1f)
- `node --check assets/js/ddmm-frontend.js` exits 0 ✓
- `grep -cE "window\.ddmmI18n && window\.ddmmI18n\.noResults" assets/js/ddmm-frontend.js` = 1 (>= 1) ✓
- `grep -cE "\|\| 'No results'" assets/js/ddmm-frontend.js` = 1 (>= 1 — graceful fallback present) ✓
- `grep -cE "li\.textContent = 'No results';" assets/js/ddmm-frontend.js` = 0 (raw literal gone) ✓
- No new `innerHTML` writes ✓

### Task 07-03-05 (commit 6dcb671)
- `test -f languages/devsroom-drilldown-mobile-menu.pot` succeeds ✓
- `grep -cE 'msgid "No results"' languages/devsroom-drilldown-mobile-menu.pot` = 1 (>= 1, Pitfall 4 mitigation) ✓
- `grep -cE '^msgid ""$' languages/devsroom-drilldown-mobile-menu.pot` = 1 (PO header entry present) ✓
- `grep -cE 'Project-Id-Version:' languages/devsroom-drilldown-mobile-menu.pot` = 1 (>= 1, valid PO header) ✓
- `grep -cE 'X-Domain: devsroom-drilldown-mobile-menu' languages/devsroom-drilldown-mobile-menu.pot` = 1 (>= 1, domain declared) ✓
- Total msgid entries = 13 (1 empty header + 12 string entries — matches the plan's hand-author body exactly) ✓
- Live translated-string display deferred to 07-HUMAN-UAT.md #14 (Plan 07-04) — grep CANNOT verify

## Complete msgid Entry List in the .pot

| # | msgid | Source reference |
|---|-------|------------------|
| 1 | (empty header) | PO header (Project-Id-Version, MIME, Content-Type, X-Domain, Language, Plural-Forms) |
| 2 | No results | src/Assets/Registrar.php (the load-bearing JS-facing string — Pitfall 4) |
| 3 | Mobile Menu | src/Rendering/DrawerRenderer.php |
| 4 | Close menu | src/Rendering/DrawerRenderer.php |
| 5 | Search menu… | src/Rendering/DrawerRenderer.php |
| 6 | Search menu items | src/Rendering/DrawerRenderer.php |
| 7 | Back | src/Rendering/DrawerRenderer.php |
| 8 | Brand Name | src/Rendering/DrawerRenderer.php |
| 9 | Sample Current Page | src/Rendering/DrawerRenderer.php |
| 10 | Sample Menu Item | src/Rendering/DrawerRenderer.php |
| 11 | Show submenu | src/Rendering/DrawerRenderer.php |
| 12 | Show %s submenu | src/Rendering/DrawerRenderer.php |
| 13 | Devsroom | src/Plugin.php (the widget category label) |

## Known Stubs

None. All i18n wiring is production-ready. The `window.ddmmI18n` bridge is wired end-to-end (PHP injection → JS consumption with fallback). The .pot ships all known msgid entries. No placeholder values, no TODOs, no mock data.

## Threat Flags

None new beyond the plan's `<threat_model>`. All 5 threats (T-07-03-01 through T-07-03-05) are mitigated as specified:
- T-07-03-01 (output injection via inline script) — mitigated by `wp_json_encode` only (verified grep count = 2)
- T-07-03-02 (translation-file path traversal) — mitigated by hardcoded absolute path derived from `plugin_dir_path` (no user input)
- T-07-03-03 (XSS via translated "No results") — mitigated by `textContent` assignment (not `innerHTML`) + literal fallback
- T-07-03-04 (.pot file integrity) — mitigated by hand-author body using developer-controlled strings only; no `msgstr` values ship
- T-07-03-05 (translations never load) — mitigated by Task 01 (Domain Path header) + Task 02 (load_plugin_textdomain as first statement)

## User Setup Required

None — no external service configuration required. Translators can copy the .pot to a `.po` and fill in `msgstr` values immediately.

## Next Phase Readiness

- The i18n pipeline is wired end-to-end (PHP server-side + JS bridge + .pot artifact).
- Plan 07-04 can proceed: COMP-03 static verification (WC-agnostic by construction) + 07-HUMAN-UAT.md manual verification matrix, including test #14 (live translated-string display in a non-English locale).
- Live translated-string display is deferred to 07-HUMAN-UAT.md #14 — grep CANNOT verify it.

---
*Phase: 07-accessibility-compatibility-polish*
*Plan: 03*
*Completed: 2026-06-14*

## Self-Check: PASSED

- languages/devsroom-drilldown-mobile-menu.pot — FOUND
- devsroom-drilldown-mobile-menu.php — FOUND
- src/Plugin.php — FOUND
- src/Assets/Registrar.php — FOUND
- assets/js/ddmm-frontend.js — FOUND
- .planning/phases/07-accessibility-compatibility-polish/07-03-SUMMARY.md — FOUND
- Commit 88ee0a1 (Task 07-03-01) — FOUND
- Commit d411476 (Task 07-03-02) — FOUND
- Commit 2d4c971 (Task 07-03-03) — FOUND
- Commit e7a0f1f (Task 07-03-04) — FOUND
- Commit 6dcb671 (Task 07-03-05) — FOUND
- `msgid "No results"` in .pot — 1 match (Pitfall 4 mitigation verified)
- `Domain Path: /languages` in main plugin file — 1 match
- `load_plugin_textdomain` in Plugin.php — 1 match
- `wp_json_encode` in Registrar.php — 2 matches
- `window.ddmmI18n && window.ddmmI18n.noResults` in ddmm-frontend.js — 1 match
- php -l (3 files) — no syntax errors
- node --check (ddmm-frontend.js) — JS OK
