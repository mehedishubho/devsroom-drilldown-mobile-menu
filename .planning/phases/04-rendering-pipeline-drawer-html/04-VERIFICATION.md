---
phase: 04-rendering-pipeline-drawer-html
verified: 2026-06-13T14:05:00Z
status: gaps_found
score: 8/9 must-haves verified
overrides_applied: 0
gaps:
  - truth: "Correct ARIA markup throughout (aria-hidden, aria-controls, aria-expanded, aria-labelledby)"
    status: partial
    reason: "Child panels ALWAYS emit aria-labelledby=\"$title_id\" in render_panel() (line 208), but the referenced <span id=\"$title_id\"> is only rendered in render_back_row() when show_back_title==='yes' (lines 322-329). When a user sets 'Show Parent Name in Back Row' to OFF, every child panel carries an aria-labelledby reference to a non-existent DOM node, breaking the ARIA label association and tripping axe/Lighthouse audits. Default is ON so it does not manifest by default, but it is a real latent defect. (Code review WR-01.)"
    artifacts:
      - path: "src/Rendering/DrawerRenderer.php"
        issue: "render_panel() line 208 emits aria-labelledby unconditionally for child panels; render_back_row() lines 322-329 only emit the target span when show_back_title==='yes'. The two conditions are not aligned."
    missing:
      - "Make aria-labelledby emission in render_panel() conditional on show_back_title==='yes' (gated the same way as the span), OR always emit the span and apply the WordPress 'screen-reader-text' class when the toggle is OFF (preserves the ARIA label while hiding visually). Either closes the dangling-reference defect."
deferred:
  - truth: "Drawer slides in from the left as an off-canvas panel when trigger is clicked (DRAW-01 interaction)"
    addressed_in: "Phase 5"
    evidence: "Phase 5 Goal: 'The drill-down menu works as an interactive experience -- drawer opens/closes, panels slide in and out...'. DRAW-01 interaction (the actual slide-in on click) is Phase 5. Phase 4 delivers the HTML + CSS + JS bootstrap skeleton that Phase 5 animates."
  - truth: "Closed-drawer contents removed from keyboard tab order (WR-03)"
    addressed_in: "Phase 5 / Phase 7"
    evidence: "Phase 7 Goal: 'fully keyboard-navigable, screen-reader friendly' with Success Criteria covering focus management (SC3: 'Focus moves to the drawer when opened and is restored to the trigger button when closed'). The CSS visibility:hidden guard on the closed drawer is a partial mitigation Phase 4 could add, but complete focus management is Phase 7's domain (A11Y-04..08)."
human_verification:
  - test: "Render a 3-level WordPress menu on a published page and inspect the DOM in DevTools"
    expected: "Every parent chevron data-target value appears EXACTLY once as a sibling child panel data-panel-id; every back button data-back-target equals its containing panel's data-panel-id; nested panels appear as siblings after their parent </li>, never inside the <li>."
    why_human: "Requires a running WordPress + Elementor environment with a configured menu. The ID-threading logic is verified statically (single-source-of-truth $child_panel_id, 6 occurrences) but the full recursive output across 3+ levels needs a live render to confirm."
  - test: "Open the Elementor editor and confirm the editor preview shows the root panel items inline"
    expected: "Items render as an inline <ul class='ddmm-menu'> inside <div class='ddmm-editor-preview'> with icons and visual-only chevrons. No off-canvas transform, no child panel siblings, no back rows."
    why_human: "Requires the Elementor editor runtime. The is_edit_mode() branch (DrillDownMenu.php lines 505-512) is verified statically, but the visual rendering inside the editor iframe needs human confirmation."
  - test: "Toggle 'Show Parent Name in Back Row' to OFF and run an axe/Lighthouse accessibility audit on the page"
    expected: "CONFIRM THE GAP: axe/Lighthouse flags 'aria-labelledby' references to non-existent IDs on child panels. After the gap is fixed, this audit should pass clean."
    why_human: "Requires a live page with the toggle OFF plus a browser accessibility audit tool. This is the observable manifestation of the WR-01 gap."
---

# Phase 4: Rendering Pipeline & Drawer HTML Verification Report

**Phase Goal:** The PHP rendering pipeline outputs complete drawer HTML with nested panels, data attributes for ID-based navigation, header area, and correct ARIA markup
**Verified:** 2026-06-13T14:05:00Z
**Status:** gaps_found
**Re-verification:** No — initial verification

## Goal Achievement

The phase goal is substantially achieved. The rendering pipeline exists, is fully wired into the widget, emits the complete DOM contract for Phase 5, and satisfies 8 of 9 must-have truths. One ARIA defect (dangling `aria-labelledby` when `show_back_title='no'`) is a latent gap that only manifests when a user disables the back-row title toggle — it does not affect the default configuration but breaks the ARIA contract in that mode.

### Observable Truths

| # | Truth | Status | Evidence |
| --- | --- | --- | --- |
| 1 | Drawer HTML renders as off-canvas panel with semi-transparent overlay, triggered by the trigger button | ✓ VERIFIED | `DrawerRenderer::render()` (lines 49-57) emits overlay `data-ddmm-overlay` + drawer `id="ddmm-drawer-{widget_id}" data-ddmm-drawer`. CSS (lines 99-126): overlay `opacity:0; visibility:hidden`, drawer `transform:translateX(-100%)`. Widget wraps trigger + drawer in `.ddmm-widget` (DrillDownMenu.php:425,518). Trigger `aria-controls="ddmm-drawer-{widget_id}"` (line 431) matches drawer id (DrawerRenderer:55) — A11Y-03 contract closed. |
| 2 | Root panel displays all top-level items; parent items show right-arrow indicator and have data-target pointing to child panel IDs | ✓ VERIFIED | `render_panel()` emits root with `ddmm-panel--active` (line 201). `render_item()` (lines 271-282) generates `$child_panel_id = uniqid('ddmm-panel-')` ONCE (line 274) and threads it into BOTH chevron `data-target`/`aria-controls` (lines 278-282) AND the recursive `render_panel()` call (line 295). 6 occurrences of `$child_panel_id` confirm single-source-of-truth (DRAW-06). CSS `::after content: '›'` (line 240) renders the right-arrow via the `.ddmm-chevron` class on the button (D-02). |
| 3 | Each child panel has a Back button row showing parent item name, with data-panel-id matching the parent's data-target | ✓ VERIFIED | `render_back_row()` (lines 311-332) emits `<button data-back-target="$ancestor_panel_id">` (line 316) where `$ancestor_panel_id` = the containing panel's `data-panel-id` (threaded via render_panel role 2). `<span class="ddmm-back__title" id="$title_id">$parent_title</span>` (lines 324-328) shows the parent name. Child panel `data-panel-id="$child_panel_id"` (line 211) matches the parent chevron's `data-target="$child_panel_id"` (line 279) — both use the SAME variable. |
| 4 | Drawer header displays configurable brand (Site Logo/Custom Image/Custom Text/None) with close button | ✓ VERIFIED | `render_header()` (lines 83-93) always renders with brand + `data-ddmm-close` button. `render_brand()` (lines 106-176) implements all 4 `brand_source` cases. Widget controls `section_drawer_header` (lines 306-356) provide `brand_source` SELECT (default site_logo), conditional `brand_image` MEDIA, conditional `brand_text` TEXT. `section_drawer_settings` (lines 358-388) provides `nav_label` + `show_back_title`. |
| 5 | Correct ARIA markup throughout (aria-hidden, aria-controls, aria-expanded, aria-labelledby) | ✗ PARTIAL | nav `aria-label` ✓ (line 64). Drawer `aria-hidden=true` ✓ (line 55). Overlay `aria-hidden=true` ✓ (line 50). Child panels `aria-hidden=true` ✓ (line 205). Chevron `aria-expanded=false` + `aria-controls` ✓ (line 278). **BUT:** child panels ALWAYS emit `aria-labelledby="$title_id"` (line 208) while the referenced span only renders when `show_back_title==='yes'` (lines 322-329). Toggling the setting OFF produces dangling ARIA references. See gap. |
| 6 | JS bootstrap skeleton: IIFE, pure ES6, dual-path init, double-init guard, no wp_localize_script | ✓ VERIFIED | IIFE `( function() { 'use strict'; ... } )();` (lines 12-120). `class DrillDownMenu` with `init(container)` guarded by `container.dataset.ddmmInit` (lines 26-31). Dual-path: `elementorFrontend.hooks.addAction('frontend/element_ready/ddmm-drilldown-menu.default', ...)` (lines 81-84) + `DOMContentLoaded` fallback querying `.ddmm-widget` (lines 92-97). Zero `wp_localize_script`, zero jQuery DOM manipulation, zero `var`. `node --check` passes. |
| 7 | Navigation uses direct data-target → data-panel-id ID lookup — no positional heuristics | ✓ VERIFIED | No `children[]`, `nextSibling`, or `.index()` in the JS bootstrap (grep returns empty). PHP emits `data-target`/`data-panel-id`/`data-back-target` attributes exclusively (DrawerRenderer lines 211, 278, 316). Phase 5 will use `querySelector('[data-panel-id="' + target + '"]')` per the code comments (line 40). |
| 8 | Child panels emitted as siblings after parent </li>, never inside the <li> | ✓ VERIFIED | `render_item()` closes `</li>` (line 285) BEFORE calling `render_panel()` for the child (line 295). The child `<div class="ddmm-panel">` is a sibling of the `<li>` in the `<ul class="ddmm-menu">`. D-13/Pitfall 3 contract satisfied. |
| 9 | Every dynamic output value escaped (esc_html / esc_url / esc_attr); only Icons_Manager uses phpcs:ignore | ✓ VERIFIED | `grep -nE "echo \$[a-z]" src/Rendering/DrawerRenderer.php | grep -v phpcs:ignore` returns 0 matches. All `printf` calls use `esc_attr`/`esc_url`/`esc_html`/`sanitize_html_class`. The only unescaped echoes are `$icon_html` (Icons_Manager output) on lines 248 and 404, both annotated with `phpcs:ignore`. |

**Score:** 8/9 truths verified

### Deferred Items

Items not yet met but explicitly addressed in later milestone phases.

| # | Item | Addressed In | Evidence |
| --- | --- | --- | --- |
| 1 | Drawer slides in from the left as an off-canvas panel when trigger is clicked (DRAW-01 interaction) | Phase 5 | Phase 5 Goal: "The drill-down menu works as an interactive experience -- drawer opens/closes, panels slide in and out with configurable animations, back navigation works at any depth." DRAW-01 click-to-open interaction is Phase 5 work. Phase 4 delivers the HTML/CSS/JS-bootstrap substrate. |
| 2 | Closed-drawer contents removed from keyboard tab order (WR-03 focus leak) | Phase 5 / Phase 7 | Phase 7 Goal: "fully keyboard-navigable, screen-reader friendly" with Success Criteria covering focus management (SC3: "Focus moves to the drawer when opened and is restored to the trigger button when closed"). A11Y-04..08 are Phase 7 requirements. The CSS `visibility:hidden` guard on the closed drawer is a partial mitigation that Phase 4 could add, but complete focus management is Phase 7's domain. |

### Required Artifacts

| Artifact | Expected | Status | Details |
| --- | --- | --- | --- |
| `src/Rendering/DrawerRenderer.php` | Stateless recursive tree-to-HTML drawer renderer + editor preview | ✓ VERIFIED | 437 lines. Namespace `Devsroom_DDMM\Rendering`, `final class DrawerRenderer`. Public: `render()`, `render_editor_preview()`. Private: `render_header`, `render_brand`, `render_panel`, `render_item`, `render_back_row`, `render_icon`, `render_editor_item`. `php -l` passes. |
| `src/Elementor/Widget/DrillDownMenu.php` | Drawer Header + Drawer Settings controls and render() integration | ✓ VERIFIED | `section_drawer_header` (lines 306-356) + `section_drawer_settings` (lines 358-388) added. `render()` wraps trigger+drawer in `.ddmm-widget` (lines 425,518), branches on `is_edit_mode()` (lines 505-516), calls `DrawerRenderer::render()` and `render_editor_preview()`. `php -l` passes. |
| `assets/css/ddmm-frontend.css` | Base layout CSS for off-canvas drawer, overlay, panel stacking | ✓ VERIFIED | 301 lines. Off-canvas drawer `translateX(-100%)` (line 119), overlay hidden (lines 103-104), panels stacked `translateX(100%)` default + `translateX(0)` active (lines 181,189), chevron `::after content:'›'` (line 240), `.ddmm-is-open` Phase 5 anticipation (lines 292-301). Zero layout-property transitions. |
| `assets/js/ddmm-frontend.js` | JS bootstrap skeleton (IIFE + DrillDownMenu class + dual-path init + guard) | ✓ VERIFIED | 120 lines. IIFE-wrapped, `class DrillDownMenu`, dual-path init (element_ready hook + DOMContentLoaded), `data-ddmm-init` guard, zero `wp_localize_script`, zero jQuery DOM manipulation, zero `var`. `node --check` passes. |

### Key Link Verification

| From | To | Via | Status | Details |
| --- | --- | --- | --- | --- |
| DrillDownMenu::render() | DrawerRenderer::render() | static call inside .ddmm-widget wrapper | ✓ WIRED | `DrawerRenderer::render( $tree, $settings, $widget_id )` at line 515, inside the frontend branch of `is_edit_mode()`. |
| DrillDownMenu::render() editor branch | DrawerRenderer::render_editor_preview() | static call inside .ddmm-editor-preview wrapper | ✓ WIRED | `DrawerRenderer::render_editor_preview( $tree, $settings )` at line 511, wrapped in `<div class="ddmm-editor-preview">` (line 510). |
| DrawerRenderer::render() | render_panel/render_item/render_back_row/render_header | private static method calls | ✓ WIRED | `render_panel` called at line 69 (root) and 295 (child). `render_item` called at line 227. `render_back_row` at line 220. `render_header` at line 60. All defined and called. |
| render_item() | child panel sibling emission | `$child_panel_id` threaded into chevron + recursive render_panel | ✓ WIRED | `$child_panel_id` declared once (line 274), used in chevron `data-target`/`aria-controls` (lines 279-280), used in recursive `render_panel()` call (line 295). 6 occurrences total — single source of truth. |
| assets/js/ddmm-frontend.js DOMContentLoaded | .ddmm-widget containers | `document.querySelectorAll('.ddmm-widget')` | ✓ WIRED | Line 93: `document.querySelectorAll( '.ddmm-widget' )`. Phase 5 will scope all queries to these containers. |
| Autoloader (devsroom-drilldown-mobile-menu.php) | src/Rendering/DrawerRenderer.php | PSR-4 `str_replace('\\','/')` | ✓ WIRED | Autoloader (lines 17-32) maps `Devsroom_DDMM\Rendering\DrawerRenderer` → `src/Rendering/DrawerRenderer.php` generically. No `use` statement needed in the widget (fully-qualified call at line 515). |

### Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
| --- | --- | --- | --- | --- |
| DrawerRenderer::render() | `$tree` (8-field nodes) | `WpNavTree::build()` / `CustomTree::build()` called in DrillDownMenu::render() lines 485/487 | Yes — both builders produce real nested trees from WP menu / repeater data | ✓ FLOWING |
| DrawerRenderer::render() | `$widget_id` | `$this->get_id()` (Elementor widget ID) | Yes — server-generated unique ID per widget instance | ✓ FLOWING |
| DrawerRenderer::render() | `$settings` | `$this->get_settings_for_display()` (line 421) | Yes — Elementor settings array with null-safe defaults (`?? 'site_logo'`, `?? 'yes'`, `?? 'Mobile Menu'`) | ✓ FLOWING |
| render_brand() | `$source`, `$settings['brand_image']`, `$settings['brand_text']` | Elementor controls section_drawer_header | Yes — real controls registered; defaults applied when unset | ✓ FLOWING |

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
| --- | --- | --- | --- |
| PHP syntax valid (DrawerRenderer) | `php -l src/Rendering/DrawerRenderer.php` | "No syntax errors detected" | ✓ PASS |
| PHP syntax valid (Widget) | `php -l src/Elementor/Widget/DrillDownMenu.php` | "No syntax errors detected" | ✓ PASS |
| JS syntax valid | `node --check assets/js/ddmm-frontend.js` | exit 0 | ✓ PASS |
| No unescaped variable echoes | `grep -nE "echo \$[a-z]" DrawerRenderer.php \| grep -v phpcs:ignore` | 0 matches | ✓ PASS |
| No role="menu" (A11Y-01) | `grep -c 'role="menu"' DrawerRenderer.php` | 0 | ✓ PASS |
| ID threading single-source-of-truth | `grep -c 'child_panel_id' DrawerRenderer.php` | 6 (>=3 required) | ✓ PASS |
| No wp_localize_script (JSCR-05) | `grep -c "wp_localize_script" ddmm-frontend.js` | 0 | ✓ PASS |
| No layout-property CSS transitions (ANIM-04) | `grep -nE "transition:.*(left\|right\|margin\|width\|top\|height)" ddmm-frontend.css` | 0 matches | ✓ PASS |
| No jQuery DOM manipulation (JSCR-01) | `grep -nE "\$\(.*\)\.(click\|css\|html\|...)" JS \| grep -v "jQuery( window ).on"` | 0 matches | ✓ PASS |
| Stub comment removed | `grep -c "// Phase 4 will render" DrillDownMenu.php` | 0 | ✓ PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
| --- | --- | --- | --- | --- |
| DRAW-01 | 04-03 | Drawer slides in from left as off-canvas panel | ✓ SATISFIED (HTML/CSS substrate) | CSS `.ddmm-drawer transform: translateX(-100%)` (line 119). Click-to-open interaction is Phase 5 (deferred). |
| DRAW-02 | 04-01, 04-03 | Semi-transparent overlay | ✓ SATISFIED | PHP emits `data-ddmm-overlay` (DrawerRenderer:50); CSS `.ddmm-overlay` opacity:0/visibility:hidden + `--ddmm-overlay-bg: rgba(0,0,0,0.5)` (lines 99-108, 27). |
| DRAW-03 | 04-02 | Configurable brand: Site Logo/Custom Image/Custom Text/None | ✓ SATISFIED | `render_brand()` 4 cases (lines 111-173); `section_drawer_header` controls (lines 306-356). |
| DRAW-04 | 04-01 | Close (✕) button in header | ✓ SATISFIED | `render_header()` close button with `data-ddmm-close` (lines 88-91). CSS `.ddmm-close` (lines 150-159). |
| DRAW-05 | 04-01 | Root panel shows top-level items; parents show › | ✓ SATISFIED | `render_panel` root `ddmm-panel--active` (line 201); `render_item` chevron (lines 277-282); CSS `::after content:'›'` (line 240). |
| DRAW-06 | 04-01 | Parent tap slides current left, child in from right | ✓ SATISFIED (DOM contract) | Single-source `$child_panel_id` (6 occurrences); `data-target`↔`data-panel-id` match. Slide animation is Phase 5. |
| DRAW-07 | 04-01 | Each submenu has ← Back button | ✓ SATISFIED | `render_back_row()` emits back button with `data-back-target` (lines 315-318). |
| DRAW-08 | 04-01, 04-02 | Back row shows parent name (toggleable) | ✓ SATISFIED | `render_back_row()` span with `$parent_title` gated by `show_back_title` (lines 322-329); `show_back_title` SWITCHER control (lines 379-386). |
| DRAW-09 | 04-01 | Drill-down works for unlimited nesting | ✓ SATISFIED | `render_panel`→`render_item`→`render_panel` recursion (lines 226-228, 295). No depth cap. |
| DRAW-10 | 04-01, 04-04 | Direct data-target → data-panel-id lookup, no positional | ✓ SATISFIED | No `children[]`/`nextSibling`/`.index()` in JS; PHP emits ID-based attrs exclusively. Phase 5 will use `querySelector('[data-panel-id=...]')`. |
| DRAW-11 | 04-01 | Unique panel IDs via uniqid() | ✓ SATISFIED | `uniqid('ddmm-panel-', false)` at lines 44 (root) and 274 (child). `uniqid('ddmm-back-title-', false)` at line 288. |
| A11Y-01 | 04-01 | nav aria-label, never role=menu | ✓ SATISFIED | `<nav aria-label="%s">` (line 64); `grep role="menu"` = 0. |
| A11Y-02 | 04-01 | Parent items use `<button>` with aria-expanded | ✓ SATISFIED | Chevron `<button ... aria-expanded="false" aria-controls="...">` (line 278). |
| A11Y-03 | 04-01, 04-02 | Trigger aria-expanded + aria-controls → drawer ID | ✓ SATISFIED | Trigger `aria-controls="ddmm-drawer-{widget_id}"` (DrillDownMenu:431) matches drawer `id="ddmm-drawer-{widget_id}"` (DrawerRenderer:55). |
| JSCR-01 | 04-04 | Pure ES6, zero jQuery dependency | ✓ SATISFIED | No jQuery DOM manipulation (only `elementor/frontend/init` event-bus subscription); pure ES6 (`const`/`let`, arrow functions, `class`). |
| JSCR-02 | 04-04 | IIFE-wrapped | ✓ SATISFIED | `( function() { 'use strict'; ... } )();` (lines 12-120). |
| JSCR-03 | 04-04 | Dual-path init | ✓ SATISFIED | `elementorFrontend.hooks.addAction('frontend/element_ready/...')` (line 81) + `DOMContentLoaded` fallback (line 115). |
| JSCR-04 | 04-04 | Double-init guard via data-ddmm-init | ✓ SATISFIED | `if ( ! container \|\| container.dataset.ddmmInit ) return;` (lines 28-30); `container.dataset.ddmmInit = 'true'` (line 31). |
| JSCR-05 | 04-04 | PHP→JS via wp_localize_script | ✓ SATISFIED (as scoped) | REQUIREMENTS.md says "via wp_localize_script()" but CONTEXT decision D-15 deliberately uses data-* + CSS vars instead. The 04-04 PLAN and SUMMARY explicitly document this as the chosen approach: NO wp_localize_script. `grep -c wp_localize_script` = 0. This is an intentional, documented deviation from the requirement's literal wording — the INTENT (config bridge from PHP to JS) is satisfied via a different, simpler mechanism. **Note:** This deviation is accepted as the planned implementation; if strict REQUIREMENTS.md conformance is required, an override should be recorded. |

**Orphaned requirements:** None. All 19 requirement IDs in the phase scope appear in at least one plan's `requirements` frontmatter and have implementation evidence.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
| --- | --- | --- | --- | --- |
| `src/Rendering/DrawerRenderer.php` | 208 | Dangling `aria-labelledby` when `show_back_title='no'` (WR-01) | ⚠️ Warning → escalated to GAP | Breaks ARIA label association when toggle is OFF; axe/Lighthouse will flag. Default ON masks the issue. |
| `assets/css/ddmm-frontend.css` | 111-126 | Closed drawer lacks `visibility:hidden` (WR-03) | ⚠️ Warning | Off-canvas drawer contents remain keyboard-focusable when closed (tabindex leak). Deferred to Phase 5/7 (focus management domain). |
| `assets/css/ddmm-frontend.css` | (missing) | `.ddmm-editor-hint` rendered but unstyled (WR-02) | ℹ️ Info | Empty-state editor hint appears unstyled. Cosmetic only; does not affect functionality. |
| `src/Rendering/DrawerRenderer.php` | 121,153 | Brand `<img>` lacks width/height/loading attrs (WR-04) | ℹ️ Info | CLS/LCP impact for brand logo. D-08 rationale (no inline dims) is partially misapplied — intrinsic dims differ from display dims. |
| `src/Rendering/DrawerRenderer.php` | 123,155 | Brand alt always uses site name (WR-05) | ℹ️ Info | Ignores attachment alt text and MEDIA control alt field. WCAG 1.1.1 concern but admin-configurable. |

### Human Verification Required

### 1. Live 3-Level Menu DOM Inspection

**Test:** Configure a 3-level WordPress menu in the widget, render on a published page, open DevTools Elements panel.
**Expected:** Every parent chevron `data-target` value appears EXACTLY once as a sibling child panel `data-panel-id`; every back button `data-back-target` equals its containing panel's `data-panel-id`; child panels are siblings after their parent `</li>`, never nested inside `<li>`.
**Why human:** Requires a running WordPress + Elementor environment with a configured menu. The ID-threading logic is verified statically (single-source-of-truth `$child_panel_id`, 6 occurrences, role 2 ancestor threading) but the full recursive output across 3+ nesting levels needs a live render to confirm no edge-case ID collisions or structural errors.

### 2. Elementor Editor Preview Visual Confirmation

**Test:** Open the Elementor editor with the widget placed and a menu configured.
**Expected:** The editor preview shows the root panel items inline (not off-canvas) inside `<div class="ddmm-editor-preview">` with icons and visual-only chevrons (no data-target wiring). No sub-panels visible. The off-canvas transform is NOT applied in the editor.
**Why human:** Requires the Elementor editor runtime (iframe rendering). The `is_edit_mode()` branch (DrillDownMenu.php lines 505-512) and `render_editor_preview()` (DrawerRenderer lines 375-385) are verified statically, but the visual rendering inside the editor iframe needs human confirmation.

### 3. Accessibility Audit with Toggle OFF (Gap Confirmation)

**Test:** Set "Show Parent Name in Back Row" to OFF, run axe DevTools or Lighthouse accessibility audit on the published page.
**Expected:** CONFIRMS THE GAP — audit flags `aria-labelledby` references to non-existent IDs on child panels. After the gap fix (aligning the `aria-labelledby` emission condition with the span emission condition), this audit should pass clean.
**Why human:** Requires a live page with the toggle OFF plus a browser accessibility audit tool. This is the observable manifestation of the WR-01 gap documented above.

### Gaps Summary

**One gap** blocks full goal achievement:

The `aria-labelledby` attribute on child panels (`render_panel` line 208) is emitted unconditionally, but its target span (`render_back_row` lines 322-329) is only rendered when `show_back_title === 'yes'`. When a user disables the back-row title toggle, every child panel carries a dangling ARIA reference. This breaks Success Criterion 5 ("Correct ARIA markup throughout") in that configuration and will trip automated accessibility audits. The fix is small: gate the `aria-labelledby` emission on the same `show_back_title` setting, OR always render the span with a `screen-reader-text` class when visually hidden.

**Items NOT counted as gaps (deferred):**
- DRAW-01 click-to-open interaction and slide animations are explicitly Phase 5 work (ROADMAP Phase 5 Goal).
- WR-03 closed-drawer focus leak is a focus-management concern primarily owned by Phase 7 (A11Y-04..08). A CSS `visibility:hidden` mitigation could be added in Phase 4 but does not block the Phase 4 rendering-pipeline goal.

**Items NOT counted as gaps (info/minor):**
- WR-02 (unstyled `.ddmm-editor-hint`), WR-04 (brand img lacks intrinsic dims), WR-05 (brand alt ignores attachment alt) are cosmetic/polish items noted in the code review but not goal-blocking.

---

_Verified: 2026-06-13T14:05:00Z_
_Verifier: Claude (gsd-verifier)_
