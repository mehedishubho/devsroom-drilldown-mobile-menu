---
phase: 03-custom-menu-builder
verified: 2026-06-13T02:05:00Z
reverified: 2026-06-13T02:35:00Z
status: passed
score: 6/6
overrides_applied: 0
gaps_resolved:
  - truth: "Icons selected via Elementor Icons picker are available in the data for Phase 4 (CMEN-05)"
    resolution: "Fixed in commit 76589c8. CustomTree::build() now reads $item['icon'] from repeater data and includes 'icon' key in node output. WpNavTree::build() includes 'icon' => [] for contract symmetry. Both builders now produce an 8-field node contract with icon data flowing to Phase 4."
    fix_commit: "76589c8"
    artifacts:
      - path: "src/MenuBuilder/CustomTree.php"
      - path: "src/MenuBuilder/WpNavTree.php"
    missing:
      - "Add 'icon' => $item['icon'] ?? [] (or equivalent extraction) to the node array in CustomTree::build() after URL/target handling"
      - "Add 'icon' => [] to the WpNavTree node contract so both builders produce an 8-field schema (or document an alternative Phase 4 data path if icons are to be read directly from $settings['custom_items'])"
      - "Update 03-REVIEW.md WR-01 status or close it explicitly once resolved"
---

# Phase 3: Custom Menu Builder Verification Report

**Phase Goal:** Users can build a complete menu from scratch using a flat repeater with a depth field that produces nested panel output
**Verified:** 2026-06-13T02:05:00Z
**Status:** gaps_found
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| #   | Truth | Status | Evidence |
| --- | ----- | ------ | -------- |
| 1 | Content Tab provides a repeater control with Label, URL, Depth, Icon, and Open in New Tab fields (CMEN-01) | VERIFIED | DrillDownMenu.php lines 232-302: `$custom_repeater = new \Elementor\Repeater()` with 5 add_control calls — TEXT (label, line 239), URL (line 251), NUMBER min=0 step=1 (depth, line 261), ICONS (icon, line 274), SWITCHER (new_tab, line 284). Repeater registered as `custom_items` with `condition => ['menu_source' => 'custom']` (line 298). |
| 2 | Items appear with indent dashes in the Elementor editor showing visual hierarchy (CMEN-03) | VERIFIED | DrillDownMenu.php line 296: `'title_field' => '{{{ depth > 0 ? "—".repeat( depth ) + " " : "" }}} {{{ label }}}'`. Uses literal em-dash with `.repeat(depth)` for depth-driven indent. |
| 3 | Flat repeater data with depth values is correctly converted to a nested tree using the stack-based algorithm (CMEN-02, CMEN-04) | VERIFIED | Behavioral spot-check on CustomTree::build() with 7 items (root, child, grandchild, empty-label, depth-jump-to-9, root2) produced correct tree: 3 roots, Products→Shirts→CottonTees→DeepJump (depth 9 auto-clamped to 3), empty-label phantom item skipped, empty input returns []. Stack algorithm at CustomTree.php lines 76-91 with `while (count($stack) > $depth)` auto-clamp and PHP `&` references for ancestry. |
| 4 | Icons selected via Elementor Icons picker are available in the data for Phase 4 (CMEN-05) | FAILED | Repeater defines `icon` ICONS control (DrillDownMenu.php line 270-277) but CustomTree::build() never reads `$item['icon']` and the 7-field node contract has no `icon` key (CustomTree.php lines 65-73). Icon data is silently discarded. Phase 4 consumes unified `$tree` per Plan 03-02 summary, so icons will not reach the renderer. |
| 5 | CustomTree outputs identical 7-field node contract as WpNavTree (D-02) | VERIFIED | CustomTree.php lines 66-72 and WpNavTree.php lines 43-49 both produce nodes with exactly the same 7 keys in the same order: `id, title, url, target, classes, has_children, children`. Confirmed via grep and behavioral spot-check (`FIELDS MATCH CONTRACT: YES`). |
| 6 | Empty custom menu shows editor hint, zero frontend HTML (D-08) | VERIFIED | DrillDownMenu.php lines 401-414: `elseif ('custom' === $menu_source && !empty($settings['custom_items']))` guards build() call. Empty tree triggers source-aware hint at line 408-410 (`'custom' === $menu_source ? 'Add menu items...' : 'Select a menu...'`) shown only when `is_edit_mode()` is true, then `return` emits zero frontend HTML for the menu portion. |

**Score:** 5/6 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
| -------- | -------- | ------ | ------- |
| `src/MenuBuilder/CustomTree.php` | Stack-based depth-field tree builder, 7-field node contract, pure PHP | VERIFIED | 99 lines. Contains `namespace Devsroom_DDMM\MenuBuilder`, `class CustomTree`, `public static function build(array $items): array`. Stack algorithm with auto-clamp, empty-label skip, URL array extraction, SWITCHER target check. PHP lint clean. No `use Elementor` imports (pure PHP). |
| `src/Elementor/Widget/DrillDownMenu.php` | Custom repeater (5 fields) + CustomTree::build() integration in render() | VERIFIED | Lines 231-302 add custom_items repeater with all 5 fields, indent title_field, prevent_empty=false, menu_source condition. Lines 399-403 integrate CustomTree::build() alongside WpNavTree. Lines 405-413 source-aware empty state. PHP lint clean. Existing wp_menu controls and trigger rendering untouched. |

### Key Link Verification

| From | To | Via | Status | Details |
| ---- | -- | --- | ------ | ------- |
| `src/Elementor/Widget/DrillDownMenu.php` | `src/MenuBuilder/CustomTree.php` | `CustomTree::build( $settings['custom_items'] )` | WIRED | DrillDownMenu.php line 402: `$tree = \Devsroom_DDMM\MenuBuilder\CustomTree::build( $settings['custom_items'] );`. Called inside `elseif ('custom' === $menu_source && !empty($settings['custom_items']))` guard. Result assigned to `$tree` which Phase 4 will consume. |
| `src/MenuBuilder/CustomTree.php` | 7-field node contract | node array with id, title, url, target, classes, has_children, children | WIRED | CustomTree.php lines 65-73 build the node array with all 7 keys. Behavioral spot-check confirmed output: `FIELD KEYS (root[0]): id,title,url,target,classes,has_children,children`. |
| Repeater `icon` field | CustomTree node output | `$item['icon']` → node `icon` key | NOT_WIRED | Repeater registers `icon` ICONS control (DrillDownMenu.php line 270-277) but CustomTree::build() never reads `$item['icon']` and the node contract has no `icon` key. Data link is broken — icons collected in editor but discarded before reaching `$tree`. |

### Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
| -------- | ------------- | ------ | ------------------ | ------ |
| `CustomTree::build()` | `$tree` | Repeater `$items` via stack algorithm | Yes — behavioral spot-check produced nested tree with real titles, URLs, targets, depth-clamped children | FLOWING |
| `DrillDownMenu::render()` `$tree` | `CustomTree::build()` / `WpNavTree::build()` | Branches on `menu_source` | Yes — both branches populate `$tree` with real node arrays | FLOWING |
| `icon` field in repeater | `$item['icon']` | Elementor ICONS control | Collected in settings but NOT propagated to `$tree` | DISCONNECTED |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| -------- | ------- | ------ | ------ |
| PHP syntax valid (CustomTree) | `php -l src/MenuBuilder/CustomTree.php` | No syntax errors detected | PASS |
| PHP syntax valid (DrillDownMenu) | `php -l src/Elementor/Widget/DrillDownMenu.php` | No syntax errors detected | PASS |
| Stack algorithm correctness | `php -r '...CustomTree::build() with 7-item fixture...'` | 3 roots, depth-jump 9→3 clamped, phantom skipped, empty input → [] | PASS |
| 7-field contract match | Behavioral spot-check field comparison | `FIELDS MATCH CONTRACT: YES` (id,title,url,target,classes,has_children,children) | PASS |
| SWITCHER target mapping | Spot-check `new_tab='yes'` → `target='_blank'` | `Products: target=_blank? YES` | PASS |
| Commit hashes valid | `git cat-file -t 16d1b32 129f771 8a8aed3` | All three are commits | PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
| ----------- | ----------- | ----------- | ------ | -------- |
| CMEN-01 | 03-02 | Repeater with Label, URL, Depth, Icon, Open in New Tab fields | SATISFIED | 5 repeater add_control calls with correct control types (DrillDownMenu.php lines 235-287) |
| CMEN-02 | 03-01 | Depth field drives nesting — flat list in editor, nested panels in output | SATISFIED | Stack-based depth-field algorithm verified via behavioral spot-check (CustomTree.php lines 41-95) |
| CMEN-03 | 03-02 | Title field shows indent dashes for visual hierarchy | SATISFIED | `title_field` with `"—".repeat(depth)` (DrillDownMenu.php line 296) — requires live Elementor for final visual confirmation but expression is correct |
| CMEN-04 | 03-01 | Custom menu data converted to nested tree using stack-based depth-field algorithm | SATISFIED | Stack algorithm with auto-clamp (CustomTree.php lines 76-91), behavioral spot-check confirmed correct nesting |
| CMEN-05 | 03-02 | Icon field uses Elementor Icons control (Font Awesome, SVG) | PARTIAL | ICONS control registered in repeater (line 274), BUT icon data is NOT propagated into `$tree` nodes — see Gap #4. Control type is correct; data availability for rendering is broken. |

**Orphaned requirements check:** REQUIREMENTS.md maps CMEN-01 through CMEN-05 to Phase 3. All 5 are claimed by Phase 3 plans (CMEN-01/03/05 in 03-02, CMEN-02/04 in 03-01). No orphans.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
| ---- | ---- | ------- | -------- | ------ |
| `src/MenuBuilder/CustomTree.php` | 34 | `return [];` (empty array early return) | Info | Legitimate — empty-input guard, not a stub. Returns empty tree when `$items` is empty. |
| `src/Elementor/Widget/DrillDownMenu.php` | 145, 241 | `placeholder` attribute on TEXT controls | Info | Standard Elementor control usage — placeholder text shown in editor input field, not a stub. |

No TODO/FIXME/HACK/PLACEHOLDER markers found in either artifact. No `return null`, no `=> {}` empty implementations, no console.log stubs.

### Human Verification Required

### 1. Repeater controls render in Elementor editor

**Test:** Open Elementor editor, add the DrillDown Mobile Menu widget, set Menu Source to "Custom Builder". Verify the repeater appears with Label, URL, Depth, Icon, Open in New Tab fields.
**Expected:** All 5 fields visible and usable; repeater hidden when Menu Source is "WordPress Menu".
**Why human:** Requires a live WordPress + Elementor instance. Code-level inspection confirms control registration is correct, but only the editor can confirm the UI renders as intended.

### 2. Indent dashes display in collapsed repeater titles

**Test:** Add repeater items with depth 0, 1, 2. Collapse each item and read the title.
**Expected:** Titles render as "Root", "— Child", "—— Grandchild" (em-dash indentation proportional to depth).
**Why human:** The `title_field` JS expression (`{{{ depth > 0 ? "—".repeat( depth ) + " " : "" }}} {{{ label }}}`) is present in code but its evaluation happens in Elementor's Backbone/Marionette frontend — only the live editor can confirm the rendered output.

### 3. Icons render in tree data (CMEN-05 — currently blocked by gap)

**Test:** Add repeater items with icons via the Elementor Icons picker. Inspect the `$tree` produced by CustomTree::build().
**Expected:** Tree nodes should carry icon data for Phase 4 to render.
**Why human:** Requires live Elementor Icons picker. Note: this verification is currently BLOCKED by Gap #4 — icon data is not propagated to nodes, so this test will fail until the gap is closed.

### Gaps Summary

**One gap blocks full goal achievement:**

The icon field gap (CMEN-05) is the only failing item. The repeater correctly collects icon selections from the Elementor Icons picker, but `CustomTree::build()` discards the `icon` key when constructing nodes. The 7-field contract (shared with WpNavTree) has no slot for icons. This was identified as warning WR-01 during plan review (03-REVIEW.md lines 40-64) with a concrete fix proposal, but the fix was not applied during execution.

**Impact on Phase 4:** The rendering pipeline will consume the unified `$tree` variable. Without icon data in the nodes, the renderer cannot call `\Elementor\Icons_Manager::render_icon()` for custom menu items — icons set by users will silently disappear from the rendered menu.

**Recommended fix (per 03-REVIEW.md WR-01):** Add `'icon' => $item['icon'] ?? []` to the CustomTree node array (around line 73) and `'icon' => []` to the WpNavTree node contract (around line 49) so both builders produce an identical 8-field schema. This preserves the "unified `$tree`" invariant while making icon data available downstream.

All other truths verified: the stack algorithm is correct (behavioral spot-check passed), the 7-field contract matches between builders, the repeater has all 5 fields with correct control types, indent dashes are configured, and the source-aware empty state works as specified.

---

_Verified: 2026-06-13T02:05:00Z_
_Verifier: Claude (gsd-verifier)_
