---
phase: 06-style-tab-controls
plan: 02
subsystem: ui
tags: [elementor, style-tab, css-custom-properties, bem, wordpress, active-state]

# Dependency graph
requires:
  - phase: 06-style-tab-controls
    plan: 01
    provides: "The _register_controls() extension point after section_style_header, refined D-01 --ddmm-* baseline, control-key naming conventions, hybrid selector strategy"
  - phase: 05-frontend-drill-down-javascript
    provides: "ddmm-current-item / ddmm-current-ancestor marker classes (D-13) emitted by auto-open logic — Plan 06-02 styles them, emits no new markers"
provides:
  - "Three Style Tab sections (Panel & Back Row STYL-04, Menu Items Normal/Hover/Active STYL-05, Search Box STYL-06) in DrillDownMenu::_register_controls()"
  - "16 new --ddmm-* hooks: back-text-color, back-bg, back-hover-bg, back-title-color, divider-color, item-text-color, item-bg, item-hover-text-color, item-hover-bg, item-active-text-color, item-active-bg, chevron-color, search-bg, search-text-color, search-border-color, search-radius"
  - "Var-driven Active state rule: .ddmm-menu__item.ddmm-current-item, .ddmm-menu__item.ddmm-current-ancestor combined selector driving color + background via --ddmm-item-active-* (D-04)"
  - "Pitfall 2 closeout: Phase 5 hardcoded .ddmm-current-item > a { font-weight:600; color: var(--ddmm-trigger-color) } and .ddmm-current-ancestor > a { font-weight:500 } rules REMOVED — STYL-05 Active tab now owns active appearance"
affects: [06-style-tab-controls/06-03]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Active state as THIRD inner tab (Normal -> Hover -> Active) with marker-class selectors (NOT pseudo-states) — Pattern 3 from 06-RESEARCH.md"
    - "Combined selector for D-04 trail: .ddmm-menu__item.ddmm-current-item, .ddmm-menu__item.ddmm-current-ancestor receive identical active styling"
    - "Var-bridge for state colors: Active tab controls override --ddmm-item-active-text-color and --ddmm-item-active-bg on {{WRAPPER}}, consumed by the CSS marker-class rule — keeps editor preview parity automatically"
    - "Section-level condition (Pitfall 4): 'condition' => [ 'search_enabled' => 'yes' ] on section_style_search hides the whole section when search is off"

key-files:
  created: []
  modified:
    - "assets/css/ddmm-frontend.css (16 new --ddmm-* hooks, 5 new rules consuming them, Phase 5 active-marker rules removed)"
    - "src/Elementor/Widget/DrillDownMenu.php (three new TAB_STYLE sections appended after section_style_header)"

key-decisions:
  - "Used the --ddmm-* var bridge for Active state colors so the editor preview inherits the active appearance via cascade (Pattern 1) — avoids needing {{WRAPPER}} .ddmm-menu__item.ddmm-current-item selectors in PHP, and means D-04 trail styling is driven by a single CSS rule with a combined selector"
  - "Combined the current-item + ancestor selectors into ONE CSS rule (.ddmm-menu__item.ddmm-current-item, .ddmm-menu__item.ddmm-current-ancestor) so D-04 receives identical color + background from one declaration block — simpler than emitting two selector keys per Active control"
  - "menu_item_padding uses isLinked => false (per-side, D-06 unlinked by default) — distinguishes it from 06-01's trigger_padding which used isLinked => true"
  - "section_style_search carries the condition at the SECTION level (not per-control) so the entire section vanishes from the editor when search is disabled"

patterns-established:
  - "Three-tab state pattern (Normal/Hover/Active) via start_controls_tabs — Active tab selectors key off marker classes (.ddmm-current-item / .ddmm-current-ancestor), NOT pseudo-states"
  - "Combined-selector CSS rule for trail styling — a single rule with comma-separated marker classes receives the same var-driven values"
  - "Section-level 'condition' => [ 'search_enabled' => 'yes' ] for opt-in feature styling (Pitfall 4)"

requirements-completed: [STYL-04, STYL-05, STYL-06]

# Metrics
duration: 3min
completed: 2026-06-14
---

# Phase 6 Plan 02: Panel / Back Row / Menu Items / Search Style Tab Summary

**Three TAB_STYLE sections (Panel & Back Row STYL-04, Menu Items with Normal/Hover/Active marker-class tabs STYL-05, Search Box STYL-06) wired through 16 new --ddmm-* custom-property hooks — with the Phase 5 hardcoded active-marker rule removed (Pitfall 2 closeout) so the Active tab fully owns the active appearance for both current-item and ancestor trail (D-04)**

## Performance

- **Duration:** ~3 min
- **Started:** 2026-06-14T00:58:03Z
- **Completed:** 2026-06-14T01:01:00Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments

- Added 16 new `--ddmm-*` custom-property hooks to the widget var block (back-row normal/hover bg + text + title, divider color aliased to menu-border by default, item text/bg/hover-text/hover-bg/active-text/active-bg, chevron color, search bg/text/border/radius) — all consumed in-situ by existing BEM rules so the baseline ships styled
- Wired the Active state as a CSS rule with a **combined selector** (`.ddmm-menu__item.ddmm-current-item, .ddmm-menu__item.ddmm-current-ancestor`) driving `color` + `background` off `--ddmm-item-active-text-color` / `--ddmm-item-active-bg` — satisfies D-04 (current item + ancestor trail receive identical active styling) from a single declaration block
- **Pitfall 2 finalization:** removed the Phase 5 hardcoded `.ddmm-menu__item.ddmm-current-item > a { font-weight: 600; color: var(--ddmm-trigger-color) }` and `.ddmm-menu__item.ddmm-current-ancestor > a { font-weight: 500 }` rules — the new var-driven `.ddmm-menu__item.ddmm-current-item` / `.ddmm-menu__item.ddmm-current-ancestor` rule replaces them, so the STYL-05 Active tab wins specificity cleanly (no `!important`, no hardcoded literals)
- `section_style_panel_back` (STYL-04): back text COLOR, Normal/Hover bg state tabs (D-03), `back_title_typography` Group_Control_Typography on `.ddmm-back__title` (D-02), back title COLOR, divider COLOR (alpha-enabled)
- `section_style_menu_items` (STYL-05): min-height SLIDER (non-responsive), `menu_item_padding` DIMENSIONS with `responsive => true` (D-05) + `isLinked => false` (D-06 per-side), Normal/Hover/**Active** state tabs each with text+bg color, chevron COLOR, `menu_item_typography` Group_Control_Typography on `.ddmm-menu__item > a` (D-02)
- `section_style_search` (STYL-06): `'condition' => [ 'search_enabled' => 'yes' ]` on the SECTION (Pitfall 4), bg COLOR, text COLOR, `search_input_border` Group_Control_Border on `.ddmm-search__input`, border-radius SLIDER (px/%), `search_input_typography` + `search_results_typography` Group_Control_Typography (D-02)
- All Pitfall checks green: 10 unique group-control names across the whole widget (Pitfall 3); DIMENSIONS uses one `{{UNIT}}` per token (Pitfall 6); `responsive => true` count is now 3 (drawer_width + hamburger_width from 06-01 + menu_item_padding from 06-02, satisfying D-05); every COLOR/SLIDER `default` matches its `--ddmm-*` var default (Pitfall 9); all selectors `{{WRAPPER}} .ddmm-…` with a space (Pitfall 1)

## Task Commits

Each task was committed atomically:

1. **Task 1: Add --ddmm-* CSS hooks for back-row/item states/chevron/search; finalize Phase 5 active-marker rule (Pitfall 2)** - `662e8f8` (feat)
2. **Task 2: Add three Style Tab sections — Panel/Back Row (STYL-04), Menu Items Normal/Hover/Active (STYL-05), Search (STYL-06)** - `45c4247` (feat)

## Files Created/Modified

- `assets/css/ddmm-frontend.css` - Added 16 new `--ddmm-*` custom-property hooks to the widget var block; consumed them in `.ddmm-menu__item` (color/bg/divider), `.ddmm-chevron` (chevron-color), `.ddmm-back` (color/bg/divider), `.ddmm-back__title` (title-color), `.ddmm-search__input` (bg/text/border/radius); added 3 new sibling rules (`.ddmm-menu__item:hover`, combined `.ddmm-menu__item.ddmm-current-item, .ddmm-menu__item.ddmm-current-ancestor`, `.ddmm-back:hover`); removed the Phase 5 hardcoded active-marker rules (Pitfall 2 closeout)
- `src/Elementor/Widget/DrillDownMenu.php` - Appended three `TAB_STYLE` sections (`section_style_panel_back`, `section_style_menu_items` with Normal/Hover/Active inner tabs, `section_style_search` conditional on `search_enabled === 'yes'`) to `_register_controls()` after the 06-01 `section_style_header` section

## Complete STYL-04/05/06 Control Key Inventory (for Plan 06-03 to verify editor-preview parity)

Plan 06-03 MUST verify every selector below cascades into the editor preview (the preview lives inside `{{WRAPPER}}`, so var-bridged controls inherit automatically via cascade; group-control selectors require the matching BEM class to be emitted by `render_editor_preview()`).

**Section: section_style_panel_back (STYL-04)**
- Individual controls: `back_color`, `back_bg`, `back_hover_bg`, `back_title_color`, `divider_color`
- Group control names: `back_title_typography` (selector: `{{WRAPPER}} .ddmm-back__title`)
- Tab IDs: `back_state_tabs`, `back_normal`, `back_hover`
- Var targets: `--ddmm-back-text-color`, `--ddmm-back-bg`, `--ddmm-back-hover-bg`, `--ddmm-back-title-color`, `--ddmm-divider-color`

**Section: section_style_menu_items (STYL-05)**
- Individual controls: `menu_item_min_height`, `menu_item_padding`, `menu_item_color`, `menu_item_bg`, `menu_item_hover_color`, `menu_item_hover_bg`, `menu_item_active_color`, `menu_item_active_bg`, `menu_item_chevron_color`
- Group control names: `menu_item_typography` (selector: `{{WRAPPER}} .ddmm-menu__item > a`)
- Tab IDs: `menu_item_state_tabs`, `menu_item_normal`, `menu_item_hover`, `menu_item_active`
- Var targets: `--ddmm-menu-min-height`, `--ddmm-item-text-color`, `--ddmm-item-bg`, `--ddmm-item-hover-text-color`, `--ddmm-item-hover-bg`, `--ddmm-item-active-text-color`, `--ddmm-item-active-bg`, `--ddmm-chevron-color`

**Section: section_style_search (STYL-06 — conditional: `search_enabled === 'yes'`)**
- Individual controls: `search_input_bg`, `search_input_color`, `search_input_radius`
- Group control names: `search_input_border` (selector: `{{WRAPPER}} .ddmm-search__input`), `search_input_typography` (selector: `{{WRAPPER}} .ddmm-search__input`), `search_results_typography` (selector: `{{WRAPPER}} .ddmm-search__results, {{WRAPPER}} .ddmm-search__result-title`)
- Var targets: `--ddmm-search-bg`, `--ddmm-search-text-color`, `--ddmm-search-radius` (the `search_input_border` group control writes border CSS directly to the selector; `--ddmm-search-border-color` remains a baseline only)

## Exact Active-Tab Selector Strings Committed

The Active state is driven ENTIRELY through the var bridge (no direct PHP selectors on marker classes) — the PHP controls override `--ddmm-item-active-text-color` and `--ddmm-item-active-bg` on `{{WRAPPER}}`, and the CSS rule with the combined marker-class selector consumes them:

```css
/* assets/css/ddmm-frontend.css (Task 1) */
.ddmm-menu__item.ddmm-current-item,
.ddmm-menu__item.ddmm-current-ancestor {
    color: var( --ddmm-item-active-text-color );
    background: var( --ddmm-item-active-bg );
}
.ddmm-menu__item.ddmm-current-item > a,
.ddmm-menu__item.ddmm-current-ancestor > a {
    color: inherit;   /* inherits the active color set on the <li> */
}
```

```php
// src/Elementor/Widget/DrillDownMenu.php (Task 2 — Active tab)
$this->add_control( 'menu_item_active_color', [
    'selectors' => [ '{{WRAPPER}}' => '--ddmm-item-active-text-color: {{VALUE}};' ],
] );
$this->add_control( 'menu_item_active_bg', [
    'selectors' => [ '{{WRAPPER}}' => '--ddmm-item-active-bg: {{VALUE}};' ],
] );
```

**Why this satisfies D-04:** Both `.ddmm-current-item` (current page) and `.ddmm-current-ancestor` (each ancestor up the trail) are in the SAME CSS rule, so they receive IDENTICAL color + background from one declaration block. The marker classes are emitted by Phase 5 JS auto-open logic (D-13) — Plan 06-02 only styles them.

**For Plan 06-03 editor-preview parity:** the preview should emit at least one `<li class="ddmm-menu__item ddmm-current-item">` (and optionally one `ddmm-current-ancestor`) so the Active-tab styling is visible in the editor. Since the active rule keys off marker classes (not pseudo-states), it WILL apply inside `.ddmm-editor-preview` as long as the class is present in the preview markup.

## Pitfall 2 Closeout Confirmation

The Phase 5 hardcoded active-marker rules have been REMOVED from `assets/css/ddmm-frontend.css`:

**Removed (was lines 528-534 of the pre-06-02 file):**
```css
.ddmm-menu__item.ddmm-current-item > a {
    font-weight: 600;
    color: var( --ddmm-trigger-color );
}
.ddmm-menu__item.ddmm-current-ancestor > a {
    font-weight: 500;
}
```

**Replaced by:** the new var-driven `.ddmm-menu__item.ddmm-current-item, .ddmm-menu__item.ddmm-current-ancestor` rule (color + background via `--ddmm-item-active-*` vars) + the `.ddmm-menu__item.ddmm-current-item > a, .ddmm-menu__item.ddmm-current-ancestor > a { color: inherit }` rule.

**Verification greps (all pass):**
- `grep -cE "\.ddmm-menu__item\.ddmm-current-item > a" assets/css/ddmm-frontend.css` = 1 (the new `color: inherit` rule — was 2 before, now reduced to 1)
- `grep -cPzo "font-weight: 600;[[:space:]]*\n[[:space:]]*color: var\( --ddmm-trigger-color \)" assets/css/ddmm-frontend.css` = 0 (the deleted Phase 5 pattern is GONE)

## Pitfall 4 Condition Confirmation

`section_style_search` carries the condition at the SECTION level (not per-control):

```php
$this->start_controls_section(
    'section_style_search',
    [
        'label'     => esc_html__( 'Search Box', 'devsroom-drilldown-mobile-menu' ),
        'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
        'condition' => [
            'search_enabled' => 'yes',   // string 'yes', not boolean
        ],
    ]
);
```

This matches the existing Content Tab `search_placeholder` condition pattern (line 513-516 of DrillDownMenu.php). When the "Enable Search" toggle is OFF, the entire Search Box Style section vanishes from the editor.

## Refined --ddmm-* Default Values Added by Task 1 (for Plan 06-03 to verify against)

These are the exact defaults Task 1 committed (appended after the 06-01 hooks in the widget var block):

```css
/* Phase 6 STYL-04/05/06 hooks (added by Plan 06-02) */
--ddmm-back-text-color: inherit;
--ddmm-back-bg: transparent;
--ddmm-back-hover-bg: rgba(0, 0, 0, 0.04);
--ddmm-back-title-color: #1a1a1a;
--ddmm-divider-color: var(--ddmm-menu-border-color);
--ddmm-item-text-color: #1a1a1a;
--ddmm-item-bg: transparent;
--ddmm-item-hover-text-color: var(--ddmm-item-text-color);
--ddmm-item-hover-bg: rgba(0, 0, 0, 0.04);
--ddmm-item-active-text-color: #1a1a1a;
--ddmm-item-active-bg: rgba(0, 0, 0, 0.06);
--ddmm-chevron-color: inherit;
--ddmm-search-bg: #ffffff;
--ddmm-search-text-color: inherit;
--ddmm-search-border-color: var(--ddmm-menu-border-color);
--ddmm-search-radius: 4px;
```

## Decisions Made

- **Var bridge for Active state colors (per Plan discretion):** The Active tab controls override `--ddmm-item-active-text-color` and `--ddmm-item-active-bg` on `{{WRAPPER}}` (Pattern 1) rather than emitting direct `{{WRAPPER}} .ddmm-menu__item.ddmm-current-item > a` selectors (Pattern 3 literal). This keeps the editor-preview parity automatic (the preview inherits via cascade) and means D-04 trail styling is driven by ONE CSS rule with a combined selector instead of two selector keys per Active control. The 06-RESEARCH.md § Pattern 3 example showed the direct-selector approach, but the plan's `<action>` block specified the var-bridge approach — the plan wins.
- **Combined marker-class selector:** Wrote `.ddmm-menu__item.ddmm-current-item, .ddmm-menu__item.ddmm-current-ancestor` as ONE CSS rule rather than two separate rules. This guarantees D-04 (identical active styling for current item + ancestor trail) from a single declaration block and is more DRY.
- **`menu_item_padding` unlinked by default:** D-06 mandates per-side Dimensions. The plan specified `isLinked => false` (unlinked) for menu-item padding, distinguishing it from 06-01's `trigger_padding` which used `isLinked => true` (linked). This reflects the different padding semantics: trigger padding is typically uniform, menu-item padding typically wants horizontal-only (top/bottom = 0, left/right = 16).
- **Section-level condition for Search:** Put `'condition' => [ 'search_enabled' => 'yes' ]` on the SECTION, not on each control — cleaner than gating each control individually and matches the existing Content Tab `search_placeholder` pattern.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- **Acceptance grep #11 (condition check) returned 0 on Windows:** The plan's acceptance grep `grep -cE "'condition' *=> *\[[^]]*'search_enabled' *=> *'yes'"` uses a single-line regex, but the PHP file uses CRLF line endings and the `condition` array spans multiple lines. Verified via `grep -cE "'search_enabled' *=> *'yes'"` that BOTH expected matches exist (line 515 from existing Content Tab `search_placeholder`, line 1134 from new `section_style_search`). This is a grep pattern limitation on Windows CRLF files, not a real failure — the semantic criterion (≥2 matches) is satisfied.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Plan 06-03 can verify editor-preview parity for ALL six Style Tab sections — the control key inventory above (plus the 06-01 inventory) is the complete selector surface to check
- Plan 06-03 owns the `.ddmm-editor-preview .ddmm-menu__item { background: #fff; border-bottom: 1px solid #eee; }` block removal (lines ~350-353 of the post-06-02 CSS file) — Plan 06-02 left it UNTOUCHED as instructed
- Plan 06-03 should rework `render_editor_preview()` to emit at least one `<li class="ddmm-menu__item ddmm-current-item">` so the STYL-05 Active tab styling is visible in the editor (the active rule keys off marker classes, so it WILL apply inside the preview once the class is present)
- Plan 06-03 should also emit `.ddmm-back`, `.ddmm-back__title`, `.ddmm-chevron`, and (optionally, gated by the toggle) `.ddmm-search__input` in the preview markup so STYL-04/05/06 group controls (Typography/Border) have their selector targets present

## Self-Check: PASSED

- FOUND: .planning/phases/06-style-tab-controls/06-02-SUMMARY.md
- FOUND: commit 662e8f8 (Task 1)
- FOUND: commit 45c4247 (Task 2)
- FOUND: src/Elementor/Widget/DrillDownMenu.php
- FOUND: assets/css/ddmm-frontend.css
- php -l: clean on all 7 src/*.php files
- All STYL-04/05/06 acceptance greps: green (except #11 which is a Windows CRLF grep limitation — semantic criterion satisfied, see Issues Encountered)

---
*Phase: 06-style-tab-controls*
*Completed: 2026-06-14*
