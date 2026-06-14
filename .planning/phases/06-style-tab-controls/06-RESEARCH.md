# Phase 6: Style Tab Controls - Research

**Researched:** 2026-06-14
**Domain:** Elementor 3.29+ Style Tab controls (`Controls_Manager::TAB_STYLE`), group controls, state tabs, responsive controls, CSS-custom-property theming bridge
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions (honor these — do not re-litigate)

**Default Aesthetic**
- **D-01:** **Polish the baseline.** Refine the existing CSS defaults (softer borders, refined spacing, a subtle drawer box-shadow) so the widget looks good *un-styled* on first drop-in, then the Style Tab controls build on top with these refined values as their defaults. Not a neutral blank slate and not a Packiro clone — a designed, modern starting point.

**Typography Coverage**
- **D-02:** Typography group controls (`\Elementor\Group_Control_Typography`) are added to: **menu items, header/brand title, back-row title** (all required by STYL-01/03/04/05) **PLUS trigger text** (text_only / icon_text label) **and search input + results** (extends STYL-06). All five text surfaces get typography. Exact control placement per-section at Claude's discretion.

**Hover/Active State Depth**
- **D-03 (Hover breadth):** Hover states cover **menu items (required by STYL-05) PLUS the trigger button, the back button / back row, and the close ✕ button** — the primary tap targets. The chevron (›) and search input get a focus treatment rather than a hover treatment.
- **D-04 (Active meaning):** The "Active" state styles **BOTH the current-page item (`ddmm-current-item`) AND each ancestor up the trail (`ddmm-current-ancestor`)** — mirroring WordPress `current-menu-item` / `current-menu-ancestor` and matching the markers Phase 5 already emits. No separate "opened-parent" highlight state in v1 (deferred).

**Responsive Controls**
- **D-05 (Responsive sizing):** **Per-breakpoint sizing** for the key dimensions — drawer width, menu-item padding, typography font sizes — using Elementor's responsive UI (mobile / tablet / desktop values). Color, border-color, and most non-sizing controls stay single-value. The existing `max-width: 85vw` drawer cap is retained as a safety net.
- **D-06 (Spacing model):** Padding/spacing controls use **Elementor Dimensions (per-side top/right/bottom/left) with sides linked by default** — users can unlink for asymmetric padding. Applies to menu-item, drawer, header, and search padding.

**Editor Preview Fidelity (Success Criteria #5)**
- **D-07 (Preview scope):** **Full representative preview.** Rework `.ddmm-editor-preview` to render trigger + header (brand + close ✕) + the root panel with items + one sample back row + a chevron (›) — all using the **real BEM classes and the same `--ddmm-*` vars/selectors**, with the hardcoded `#fff`/`#eee` fallback colors removed so Style Tab styling cascades through identically. Sub-panels remain omitted (root representative only).
- **D-08 (SC#5 strictness):** **Strict parity everywhere.** Every Style Tab control must be reflected in the editor preview; engineer the preview + selectors so all six sections (trigger/drawer/header/items/back-row/search) are visible in the editor and match the published page. Search-box styling is reflected by rendering a sample search row in the preview even when the feature is off (or gated by the existing toggle — at Claude's discretion).

### Carried-Forward Locked Architecture (not re-asked)
- **CSS-var theming bridge** (Phase 4 D-15): `--ddmm-*` custom properties declared on `.elementor-widget-ddmm-drilldown-menu` and consumed by the CSS; Style Tab overrides these. Whether a given control uses the var bridge vs a direct Elementor `selectors` rule is Claude's discretion — group controls (Typography / Box-Shadow / Border) and hover/active states will necessarily use `selectors`.
- **BEM class names** (Phase 4 D-26) are stable: `.ddmm-trigger`, `.ddmm-overlay`, `.ddmm-drawer`, `.ddmm-header`, `.ddmm-brand__img/__text`, `.ddmm-close`, `.ddmm-menu__item`, `.ddmm-menu__icon`, `.ddmm-chevron` (`::after`), `.ddmm-back__button/__title`, `.ddmm-search__*`.
- **Split parent + CSS chevron** (Phase 4 D-01/D-02): label `<a href>` + separate `›` chevron `<button>` whose glyph is a CSS `::after` — chevron color is themeable.
- **Active markers shipped** (Phase 5 D-13): `ddmm-current-item` / `ddmm-current-ancestor` classes are already on the matching `<li>` + ancestors; Phase 6 styles the Active state off them.
- **Search is off-by-default** (Phase 5 D-09): STYL-06 styles an opt-in feature.
- **Drawer always in DOM** (Phase 4 D-20); off-canvas via `translateX(-100%)`.
- **Animation/easing vars** (`--ddmm-transition-duration`, `--ddmm-transition-easing`) already bridge per-instance; Style Tab does NOT duplicate animation controls (those live in the Content Tab, Phase 5).

### Claude's Discretion
- Exact control inventory per section (which specific COLOR / SLIDER / Dimensions / Typography / Box-Shadow / Border controls each of the 6 sections exposes) — the STYL-01..06 requirement lists are the floor, not the ceiling
- Whether each control emits a `--ddmm-*` var override or a direct Elementor `selectors` rule (hybrid expected: simple color/size → var; group controls + hover/active → selectors)
- Exact new `--ddmm-*` custom-property names introduced for any property not yet covered (e.g. drawer box-shadow, header border, back-row hover bg, accent/active color)
- Whether to introduce a single global "Accent / Active color" control reused across items + back-row, or per-section active colors
- Editor-preview sample content (placeholder brand, sample item labels, sample back-row parent name)
- How search-box styling is represented in the editor preview when search is disabled (always show a sample row vs gate on the toggle)
- Section ordering, labels, descriptions, and separators in the Style Tab
- Default numeric values for the polished baseline (D-01) — refined border softness, drawer shadow, spacing
- Any new PHP follows Phase 4 escaping patterns (`esc_attr`/`esc_html`/`esc_url` + the `Icons_Manager` `phpcs:ignore` convention); Style Tab controls add no new frontend markup beyond the reworked editor preview

### Deferred Ideas (OUT OF SCOPE — ignore completely)
- **Opened-parent Active state** (highlight a parent while its child panel is open, beyond the ancestor trail) — v1
- **`content_template()` true live editor preview** (PRES-01) — v2. Phase 6 uses the reworked static `.ddmm-editor-preview` for parity instead.
- **RTL Style Tab variants** (RTL-01) — v2.
- **Global accent-color system / theme presets** — Claude may introduce a single accent color at its discretion, but a full preset/theme system is out of scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| STYL-01 | Trigger Button controls: color, background, hamburger size, padding, border, border-radius, typography | § Standard Stack (group controls); § Code Examples (COLOR/SLIDER/DIMENSIONS/Group_Control_Typography/Group_Control_Border); § Architecture Pattern 5 (per-section selectors). BEM targets: `.ddmm-trigger`, `.ddmm-hamburger`, `.ddmm-trigger__text`. D-02 adds trigger typography. |
| STYL-02 | Drawer controls: width (px/vw/%), background, box-shadow, overlay color | § Code Examples (SLIDER `size_units: ['px','vw','%']`, Group_Control_Box_Shadow, COLOR for overlay). D-05 makes width responsive. BEM targets: `.ddmm-drawer` (width/bg/shadow), `.ddmm-overlay` (bg). |
| STYL-03 | Header controls: background, border, height, title typography/color, close button color | § Architecture Pattern 5. Group_Control_Border for border, SLIDER for height, Group_Control_Typography for `.ddmm-brand__text`. BEM: `.ddmm-header`, `.ddmm-close`, `.ddmm-brand__text`. D-02 adds title typography. |
| STYL-04 | Panel & Back Row controls: back row color, back row background (normal + hover), panel title typography + color, divider color | § Architecture Pattern 2 (Normal/Hover tabs), § Don't Hand-Roll (hover via `:hover` selector). BEM: `.ddmm-back`, `.ddmm-back__button`, `.ddmm-back__title`. Divider color → `--ddmm-menu-border-color` override. D-03 adds hover for back row. |
| STYL-05 | Menu Items controls: min-height, padding, Normal/Hover/Active tabs (text color, background, arrow color), typography | § Architecture Pattern 2 (Normal/Hover via `start_controls_tabs`), § Architecture Pattern 3 (Active state — the KEY pattern for `.ddmm-current-item`/`.ddmm-current-ancestor`). D-04 defines Active = current + ancestor. |
| STYL-06 | Search Box controls: background, text color, border, border-radius | § Code Examples (COLOR/DIMENSIONS/Group_Control_Border). Conditional on `search_enabled === 'yes'` via `condition` array. BEM: `.ddmm-search__input`. D-02 adds search input + results typography. |
</phase_requirements>

## Summary

Phase 6 is an Elementor-control-authoring phase, not a markup phase. All six requirements (STYL-01..06) are delivered by adding **TAB_STYLE sections** to `DrillDownMenu::_register_controls()`, where each control writes CSS via one of two well-defined mechanisms: (a) the existing `--ddmm-*` custom-property bridge (override `--ddmm-*` on `{{WRAPPER}}` for simple color/size values that already flow through the CSS), or (b) Elementor's native `selectors` array (for group controls — Typography/Border/Box-Shadow — and for Normal/Hover/Active state variants). The renderer already emits every BEM class the selectors target, so **no new frontend markup is needed beyond the editor-preview rework** (D-07).

The Elementor 3.29+ Style Tab API is stable and well-documented. The three primary tools — `start_controls_section`/`end_controls_section` with `'tab' => TAB_STYLE`, individual controls (`COLOR`, `SLIDER`, `DIMENSIONS`) with a `selectors` array using `{{WRAPPER}}`/`{{VALUE}}`/`{{UNIT}}`/`{{TOP}}..{{LEFT}}` tokens, and group controls (`Group_Control_Typography`, `Group_Control_Border`, `Group_Control_Box_Shadow`) with a singular `selector` argument — cover every STYL requirement. For Normal/Hover/Active states (STYL-04, STYL-05), use `start_controls_tabs()`/`start_controls_tab()` to create Normal/Hover inner tabs, and treat "Active" as a **third inner tab whose selectors key off the marker classes** `.ddmm-current-item`/`.ddmm-current-ancestor` (NOT Elementor's States API pseudo-state machinery — these are real DOM classes the JS toggles, so Active maps to direct `.ddmm-menu__item.ddmm-current-item > a` selectors, not `:hover`/`:focus`). `[CITED: developers.elementor.com/docs/editor-controls/control-tabs/]`

**Primary recommendation:** Build six `TAB_STYLE` sections (Trigger / Drawer / Header / Panel+Back Row / Menu Items with N/H/A tabs / Search Box). Use the `--ddmm-*` bridge for any color/size where a var already exists (refine the defaults per D-01 first); use direct `selectors` keyed off `{{WRAPPER}} .ddmm-<bem>` for all group controls and all Hover/Active state variants. Rework `.ddmm-editor-preview` + `render_editor_preview()` so every selector also matches inside the preview block (the BEM classes already do — the rework is primarily removing the hardcoded `#fff`/`#eee` overrides and emitting the header + sample back-row so all six sections are visible in the editor).

## Standard Stack

This phase uses **only APIs already loaded by Elementor Free 3.29+**. No new runtime dependencies. No npm/composer packages. No build step (CLAUDE.md mandates this).

### Core Elementor Control APIs (all verified against official docs)

| API | Class / Constant | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Section wrapper | `start_controls_section( $id, $args )` / `end_controls_section()` | Wraps a group of controls under a heading; `'tab' => \Elementor\Controls_Manager::TAB_STYLE` places it in the Style tab | Standard Elementor pattern; the existing widget already uses it for Content Tab sections `[CITED: developers.elementor.com/docs/widgets/rendering-style/]` |
| Color control | `Controls_Manager::COLOR` | Pick a single color; supports `'alpha' => true` for opacity | Returns `{{VALUE}}` token for selectors `[CITED: developers.elementor.com/docs/widgets/rendering-style/]` |
| Slider control | `Controls_Manager::SLIDER` | Numeric value with unit; supports `size_units`, `range`, `responsive => true` | Drawer width (px/vw/%), hamburger size, header height, min-height, border-radius. `'responsive' => true` enables per-breakpoint values (mobile/tablet/desktop) `[CITED: developers.elementor.com/additional-custom-breakpoints-technical-details-and-gotchas/]` |
| Dimensions control | `Controls_Manager::DIMENSIONS` | Per-side top/right/bottom/left + unit + isLinked toggle | Padding for menu items, drawer, header, search. Returns `{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}` tokens — exactly matches D-06 (per-side, linked by default) `[CITED: developers.elementor.com/docs/editor-controls/control-dimensions/]` |
| Control Tabs (state variants) | `start_controls_tabs( $id )` / `start_controls_tab( $id, $args )` / `end_controls_tab()` / `end_controls_tabs()` | Inner Normal/Hover/Active tab UI inside a section | The canonical Elementor pattern for state variants. Each inner tab wraps its own set of Color/Slider controls with state-specific selectors `[CITED: developers.elementor.com/docs/editor-controls/control-tabs/]` |
| Typography group | `\Elementor\Group_Control_Typography::get_type()` | font-family/size/weight/transform/style/line-height/letter-spacing in one control | Required by D-02 for 5 text surfaces. Takes singular `'selector' => '{{WRAPPER}} .<bem>'` (NOT `selectors`) `[CITED: developers.elementor.com/docs/editor-controls/group-control-typography/]` |
| Border group | `\Elementor\Group_Control_Border::get_type()` | border-type/width/color/radius in one control | STYL-01 trigger border, STYL-03 header border, STYL-06 search border. Singular `selector` arg `[CITED: developers.elementor.com/docs/editor-controls/group-control/]` |
| Box Shadow group | `\Elementor\Group_Control_Box_Shadow::get_type()` | h-offset/v-offset/blur/spread/color/inset in one control | STYL-02 drawer box-shadow (also trigger shadow at Claude's discretion). Singular `selector` arg `[CITED: developers.elementor.com/docs/editor-controls/group-control-box-shadow/]` |

### Supporting APIs (already used in the codebase)

| API | Used For | Already Used In |
|---------|---------|-------------|
| `add_control( $id, $args )` with `'condition' => [...]` | Gate search-box section on `search_enabled === 'yes'` | Existing widget uses `condition` for trigger-type variants, brand sources |
| `add_group_control( $type, $args )` | Register group controls | Pattern not yet used in this widget but standard |
| `esc_html__()`, `esc_attr()`, `esc_url()` | All user-facing strings | Already used throughout `_register_controls()` |
| `\Elementor\Icons_Manager::render_icon()` | (No new icons in Phase 6, but the `phpcs:ignore` escaping pattern carries forward) | Existing `render()` + `render_icon()` |
| `\Elementor\Plugin::$instance->editor->is_edit_mode()` | Branch editor preview vs frontend | Already used in `render()` |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `--ddmm-*` custom-property bridge | Direct `selectors` for everything | The bridge already exists and is consumed by every BEM rule — overriding `--ddmm-*` is more DRY and automatically inherits into the editor preview (which reuses the same BEM classes). Group controls and Hover/Active MUST use direct `selectors` because they target pseudo-classes (`:hover`) and the group controls emit multiple properties at once. **Recommendation: hybrid (per CONTEXT.md discretion).** |
| `start_controls_tabs` (classic state tabs) | Elementor's newer States API (ATF controls / pseudo-state generation) | The States API is newer and not yet universally stable across 3.29; the classic `start_controls_tabs` is the documented, battle-tested approach. More importantly, the Active state is NOT a pseudo-state — it's a real class (`.ddmm-current-item`) the JS toggles — so it cannot use States API `:hover`/`:focus` machinery anyway. **Use classic tabs throughout; Active is a third inner tab with marker-class selectors.** `[CITED: developers.elementor.com/docs/editor-controls/control-tabs/]` |
| Single global accent color | Per-section active colors | At Claude's discretion (CONTEXT.md). A global accent DRYs up the Active + Hover colors; per-section gives more control. The D-04 "current item + ancestor trail" model works either way — recommend a single "Menu Active Color" control in the Menu Items section reused for both current-item and ancestor, with per-section hover colors distinct. |
| Inline `selectors` per control | Reuse a single `$selector` variable | Matter of style; PHP variable interpolation in the `selectors` array is fine and reduces repetition. |

### Installation

```bash
# No installation required.
# All APIs ship with Elementor Free 3.29+ (already a hard dependency).
# No Composer, no Node, no build step (CLAUDE.md mandate).
```

**Version verification:** Elementor Free 3.29+ is the declared minimum (CLAUDE.md). The Style Tab APIs used here have been stable since Elementor 2.x and remain current in 3.29+ — no version risk. `[CITED: developers.elementor.com/docs/editor-controls/]`

## Architecture Patterns

### Recommended Code Structure

The 6 Style Tab sections are added to the existing `_register_controls()` method in `DrillDownMenu.php`, after the existing Content Tab sections. Maintain the established pattern: `start_controls_section` → `add_control`/`add_group_control` (possibly wrapped in `start_controls_tabs` for state variants) → `end_controls_section`. Use `'separator' => 'before'` on the first Style Tab section to visually separate it from the Content Tab.

```
src/Elementor/Widget/DrillDownMenu.php
└── _register_controls()
    ├── (existing) Content Tab sections (section_trigger, section_menu, ...)
    └── (NEW) Style Tab sections:
        ├── section_style_trigger        (STYL-01)
        ├── section_style_drawer         (STYL-02)
        ├── section_style_header         (STYL-03)
        ├── section_style_panel_back     (STYL-04)
        ├── section_style_menu_items     (STYL-05 — has Normal/Hover/Active tabs)
        └── section_style_search         (STYL-06 — condition: search_enabled === 'yes')
```

Files touched (3 total):
```
src/Elementor/Widget/DrillDownMenu.php  — add 6 Style Tab sections (the bulk of the work)
assets/css/ddmm-frontend.css            — (a) refine baseline defaults (D-01); (b) remove .ddmm-editor-preview hardcoded #fff/#eee (D-07); (c) add any new --ddmm-* hooks for Active/hover bg/colors not yet covered; (d) ensure .ddmm-current-item/.ddmm-current-ancestor selectors are driven by themeable vars
src/Rendering/DrawerRenderer.php        — rework render_editor_preview() into the full representative preview (D-07): trigger + header + root items + sample back row + chevron, using real BEM classes
```

### Pattern 1: `--ddmm-*` Custom-Property Bridge (color/size overrides)

**What:** For any color or size control where a `--ddmm-*` var already exists on `.elementor-widget-ddmm-drilldown-menu`, the Style Tab control overrides the var on `{{WRAPPER}}` and the existing CSS rule picks it up automatically — no new CSS rule needed.

**When to use:** Simple COLOR controls (trigger color/bg, drawer bg, overlay bg, header bg, item text color, border color) and simple SLIDER controls whose value already flows through a var (trigger padding, hamburger width/height, drawer width, header height, menu min-height). D-15 locked this bridge as the per-instance theming mechanism.

**Example:**
```php
// Source: Existing ddmm-frontend.css lines 8-36 + Elementor selectors pattern
// [CITED: developers.elementor.com/docs/widgets/rendering-style/]
$this->add_control(
    'drawer_bg',
    [
        'label'     => esc_html__( 'Drawer Background', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'default'   => '#ffffff',   // matches existing --ddmm-drawer-bg default
        'selectors' => [
            // Override the var on the widget wrapper; the .ddmm-drawer rule
            // already has `background: var(--ddmm-drawer-bg);` and inherits.
            '{{WRAPPER}}' => '--ddmm-drawer-bg: {{VALUE}};',
        ],
    ]
);
```

**Key insight:** Because the editor preview block (`.ddmm-editor-preview`) lives INSIDE `{{WRAPPER}}`, any `--ddmm-*` override on `{{WRAPPER}}` cascades into the preview automatically — this is the SC#5 parity mechanism for var-bridged controls.

### Pattern 2: Normal / Hover State Tabs via `start_controls_tabs`

**What:** Use `start_controls_tabs()` to wrap inner tabs (Normal / Hover), each containing its own Color/Background controls whose `selectors` differ only by the `:hover` pseudo-class.

**When to use:** STYL-04 (back row normal + hover), STYL-05 (menu items normal + hover), D-03 (trigger / back / close hover).

**Example:**
```php
// Source: Elementor Control Tabs docs
// [CITED: developers.elementor.com/docs/editor-controls/control-tabs/]
$this->start_controls_tabs( 'menu_items_state_tabs' );

// --- NORMAL ---
$this->start_controls_tab(
    'menu_items_normal',
    [ 'label' => esc_html__( 'Normal', 'devsroom-drilldown-mobile-menu' ) ]
);
$this->add_control(
    'menu_item_color',
    [
        'label'     => esc_html__( 'Text Color', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'default'   => '#1a1a1a',
        'selectors' => [
            '{{WRAPPER}} .ddmm-menu__item > a' => 'color: {{VALUE}};',
        ],
    ]
);
$this->add_control(
    'menu_item_bg',
    [
        'label'     => esc_html__( 'Background', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .ddmm-menu__item' => 'background: {{VALUE}};',
        ],
    ]
);
$this->end_controls_tab();

// --- HOVER ---
$this->start_controls_tab(
    'menu_items_hover',
    [ 'label' => esc_html__( 'Hover', 'devsroom-drilldown-mobile-menu' ) ]
);
$this->add_control(
    'menu_item_hover_color',
    [
        'label'     => esc_html__( 'Text Color', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .ddmm-menu__item:hover > a' => 'color: {{VALUE}};',
        ],
    ]
);
$this->add_control(
    'menu_item_hover_bg',
    [
        'label'     => esc_html__( 'Background', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .ddmm-menu__item:hover' => 'background: {{VALUE}};',
        ],
    ]
);
$this->end_controls_tab();

$this->end_controls_tabs();
```

### Pattern 3: Active State — the `.ddmm-current-item` / `.ddmm-current-ancestor` Selector (CRITICAL)

**What:** The "Active" state in STYL-05 is NOT a CSS pseudo-state — it's a real DOM class (`ddmm-current-item` on the matching `<li>`, `ddmm-current-ancestor` on each ancestor `<li>`) that Phase 5 JS already emits (Phase 5 D-13). Therefore **Active cannot use Elementor's hover/focus States API machinery**. Instead, Active is a **third inner tab** within the same `start_controls_tabs` group, whose selectors key off the marker classes directly.

**When to use:** STYL-05 Active tab (mandatory). Optionally reused for back-row active styling at Claude's discretion.

**Example:**
```php
// Source: Phase 5 D-13 marker classes + Elementor Control Tabs pattern
// The marker classes are emitted by render_item() in DrawerRenderer.php
// and applied by the Phase 5 JS auto-open logic.
$this->start_controls_tab(
    'menu_items_active',
    [ 'label' => esc_html__( 'Active', 'devsroom-drilldown-mobile-menu' ) ]
);
$this->add_control(
    'menu_item_active_color',
    [
        'label'     => esc_html__( 'Active Text Color', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'default'   => '#1a1a1a',
        'selectors' => [
            // Current page item — direct child <a>.
            '{{WRAPPER}} .ddmm-menu__item.ddmm-current-item > a' => 'color: {{VALUE}};',
            // Ancestor trail items — same color cascades (D-04).
            '{{WRAPPER}} .ddmm-menu__item.ddmm-current-ancestor > a' => 'color: {{VALUE}};',
        ],
    ]
);
$this->add_control(
    'menu_item_active_bg',
    [
        'label'     => esc_html__( 'Active Background', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .ddmm-menu__item.ddmm-current-item' => 'background: {{VALUE}};',
            '{{WRAPPER}} .ddmm-menu__item.ddmm-current-ancestor' => 'background: {{VALUE}};',
        ],
    ]
);
$this->end_controls_tab();
```

**Why this works in the editor (SC#5):** The editor preview reworked per D-07 should include ONE sample item carrying the `ddmm-current-item` class (and optionally one ancestor) so the Active-tab styling is visible in the editor. The existing Phase 5 CSS rule (`.ddmm-menu__item.ddmm-current-item > a { font-weight: 600; color: var(--ddmm-trigger-color); }`) becomes a candidate for refinement or removal — once the Style Tab drives the active color, the hardcoded Phase 5 rule should either be removed or converted to a `--ddmm-*` var override so the Style Tab wins (see Pitfall 2 — specificity).

### Pattern 4: Group Controls (Typography / Border / Box-Shadow) — singular `selector`

**What:** Group controls take a **singular** `'selector'` argument (NOT the `'selectors'` array used by individual controls). Elementor internally expands the group into multiple sub-controls, each of which writes one CSS property into that single selector.

**When to use:** STYL-01 trigger border + typography, STYL-02 drawer box-shadow, STYL-03 header border + title typography, STYL-04 panel-title typography, STYL-05 menu-items typography, STYL-06 search border + typography (per D-02).

**Example:**
```php
// Source: Elementor Typography Group Control docs
// [CITED: developers.elementor.com/docs/editor-controls/group-control-typography/]
$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    [
        'name'     => 'menu_item_typography',   // becomes the setting key prefix
        'label'    => esc_html__( 'Typography', 'devsroom-drilldown-mobile-menu' ),
        'selector' => '{{WRAPPER}} .ddmm-menu__item > a',   // SINGULAR
    ]
);

// Box-shadow on the drawer:
// [CITED: developers.elementor.com/docs/editor-controls/group-control-box-shadow/]
$this->add_group_control(
    \Elementor\Group_Control_Box_Shadow::get_type(),
    [
        'name'     => 'drawer_box_shadow',
        'label'    => esc_html__( 'Box Shadow', 'devsroom-drilldown-mobile-menu' ),
        'selector' => '{{WRAPPER}} .ddmm-drawer',
    ]
);

// Border on the header:
// [CITED: developers.elementor.com/docs/editor-controls/group-control/]
$this->add_group_control(
    \Elementor\Group_Control_Border::get_type(),
    [
        'name'     => 'header_border',
        'label'    => esc_html__( 'Border', 'devsroom-drilldown-mobile-menu' ),
        'selector' => '{{WRAPPER}} .ddmm-header',
    ]
);
```

**Pitfall warning:** The `name` becomes a setting key (e.g. `menu_item_typography_font_size`). Each `name` MUST be unique across the entire widget — group controls cannot share names. See Common Pitfalls #3.

### Pattern 5: Responsive Controls (`'responsive' => true`) — per D-05

**What:** Elementor SLIDER (and DIMENSIONS) controls accept a `'responsive' => true` flag that automatically renders separate inputs for each active breakpoint (desktop / tablet / mobile / mobile_extra / etc.). The selected value is stored per-breakpoint and Elementor emits the appropriate `@media` rules. `[CITED: developers.elementor.com/additional-custom-breakpoints-technical-details-and-gotchas/]`

**When to use:** D-05 mandates responsive sizing for: drawer width, menu-item padding, typography font sizes. Non-sizing controls (colors, border-color, most others) stay single-value.

**Example:**
```php
// Drawer width — responsive, px/vw/%, default 320px, max-width:85vw cap retained in CSS.
$this->add_control(
    'drawer_width',
    [
        'label'      => esc_html__( 'Drawer Width', 'devsroom-drilldown-mobile-menu' ),
        'type'       => \Elementor\Controls_Manager::SLIDER,
        'size_units' => [ 'px', 'vw', '%' ],   // STYL-02 requirement
        'responsive' => true,                   // D-05: per-breakpoint
        'range'      => [
            'px' => [ 'min' => 240, 'max' => 600, 'step' => 10 ],
            'vw' => [ 'min' => 50,  'max' => 100, 'step' => 1  ],
            '%'  => [ 'min' => 50,  'max' => 100, 'step' => 1  ],
        ],
        'default'    => [
            'unit' => 'px',
            'size' => 320,
        ],
        'selectors'  => [
            '{{WRAPPER}}' => '--ddmm-drawer-width: {{SIZE}}{{UNIT}};',
        ],
    ]
);
```

**Confirmed:** Both SLIDER and DIMENSIONS natively accept the `responsive` flag. Typography's font-size sub-control is responsive by default when used inside a `Group_Control_Typography` (the font-size field has its own responsive toggle in the UI). `[CITED: developers.elementor.com/additional-custom-breakpoints-technical-details-and-gotchas/]`

### Pattern 6: Editor Preview Parity (D-07 / D-08 / SC#5)

**What:** Rework `DrawerRenderer::render_editor_preview()` so the preview block contains real BEM-classed markup for every surface the Style Tab controls, AND remove the `.ddmm-editor-preview` hardcoded `#fff`/`#eee` overrides in `ddmm-frontend.css` (lines 284-287) so Style Tab styling cascades through.

**When to use:** The editor preview block must visibly demonstrate all 6 sections (trigger, drawer, header, items, back-row, search). It is the SC#5 parity vehicle (a true `content_template()` live preview is deferred to v2).

**Key insight:** Because `{{WRAPPER}}` is the widget wrapper and the editor preview lives inside that wrapper, **any selector keyed off `{{WRAPPER}} .ddmm-<bem>` automatically matches inside the preview** — the only requirement is that the preview emits the same BEM classes. The current preview already emits `.ddmm-menu` and `.ddmm-menu__item` (so the Menu Items section already works in the editor); the rework extends this to `.ddmm-trigger`, `.ddmm-header`, `.ddmm-brand__text`, `.ddmm-close`, `.ddmm-back`, `.ddmm-back__button`, `.ddmm-back__title`, `.ddmm-chevron`, and `.ddmm-search`/`.ddmm-search__input`.

**Preview structure target (D-07):**
```
<div class="ddmm-editor-preview">
    <button class="ddmm-trigger ddmm-trigger--hamburger">           <!-- STYL-01 -->
        <span class="ddmm-hamburger">…3 spans…</span>
    </button>
    <div class="ddmm-header">                                       <!-- STYL-03 -->
        <span class="ddmm-brand__text">Brand Name</span>
        <button class="ddmm-close">×</button>
    </div>
    <!-- Optional sample search row (D-08 discretion): -->
    <div class="ddmm-search">                                        <!-- STYL-06 -->
        <input class="ddmm-search__input" placeholder="Search menu…" disabled>
    </div>
    <div class="ddmm-back">                                          <!-- STYL-04 -->
        <button class="ddmm-back__button">← Back</button>
        <span class="ddmm-back__title">Parent Item</span>
    </div>
    <ul class="ddmm-menu">
        <li class="ddmm-menu__item ddmm-current-item">               <!-- STYL-05 Active marker -->
            <a>Current Page</a>
        </li>
        <li class="ddmm-menu__item">
            <a>Another Item</a>
            <button class="ddmm-chevron"></button>                   <!-- STYL-05 arrow -->
        </li>
    </ul>
</div>
```

**CSS cleanup (D-07):** The current `.ddmm-editor-preview .ddmm-menu__item { background: #fff; border-bottom: 1px solid #eee; }` rules (lines 284-287) MUST be removed — they override the Style Tab background/border selectors. After removal, the item background/border inherits from the global `.ddmm-menu__item` rule (which uses `--ddmm-menu-border-color`), and the Style Tab Background selectors apply cleanly. The `.ddmm-editor-preview { background: #f9f9f9; border: 1px dashed #ccc; … }` chrome can stay (it's the preview frame, not the widget content).

**Note on `.ddmm-drawer` in editor:** The drawer itself does NOT render in the editor (it's off-canvas via `translateX(-100%)` and would be invisible). The Drawer section controls (STYL-02) still need to be visible in the editor — solution: apply the drawer-width and drawer-bg selectors to BOTH `.ddmm-drawer` AND a visible editor representation. The simplest approach: in the editor preview, render a bordered "drawer preview" container carrying the `.ddmm-drawer` class inline (overriding `position: fixed` via the existing `.ddmm-editor-preview .ddmm-menu { position: static }` pattern — extend it to also neutralize the drawer's `position: fixed` / `transform`). At Claude's discretion: either neutralize the off-canvas transform inside the preview, OR add a parallel `.ddmm-editor-drawer-frame` container whose background/width vars mirror `.ddmm-drawer`'s. The overlay color is best shown by a thin preview strip rather than a full overlay.

### Anti-Patterns to Avoid

- **Hand-writing CSS for every Style Tab control instead of using selectors:** Elementor's `selectors` mechanism generates the CSS rule per-instance automatically. Do NOT add static CSS rules in `ddmm-frontend.css` that hardcode Style-Tab-driven values — the selectors ARE the styling mechanism.
- **Using `selectors` (plural) on a group control:** Group controls take `selector` (singular). `selectors` is for individual controls. Mixing them silently fails. `[CITED: developers.elementor.com/docs/editor-controls/group-control-typography/]`
- **Hardcoding Active state styling that fights the Style Tab:** The existing Phase 5 rule `.ddmm-menu__item.ddmm-current-item > a { color: var(--ddmm-trigger-color); }` has specificity `0,2,1` (two classes + element). The Style Tab Active selector `{{WRAPPER}} .ddmm-menu__item.ddmm-current-item > a` will have higher specificity (because `{{WRAPPER}}` expands to the widget's unique ID class, adding one more class to the chain) — so the Style Tab wins. But if the Phase 5 rule uses a literal color instead of a var, or if it's marked `!important`, it can win unexpectedly. **Verify the Phase 5 active rule is var-driven and !important-free before shipping Phase 6.**
- **Adding `responsive => true` to a COLOR control:** The `responsive` flag applies to size/dimension controls (SLIDER, DIMENSIONS). Color controls do not accept it (and D-05 explicitly says colors stay single-value).
- **Gating search section incorrectly:** The Search section condition should be `'condition' => [ 'search_enabled' => 'yes' ]` (matches the existing Content Tab search toggle). When the toggle is OFF, the section vanishes — but D-08 wants search styling reflected in the editor preview even when off. Resolution at Claude's discretion: either always render a sample search row in the preview (ignoring the toggle for preview purposes) or gate it (the user sees search styling only after enabling search). The CONTEXT.md allows either.
- **Forgetting `end_controls_tab()` / `end_controls_tabs()`:** Unbalanced tab/section boundaries produce silent control-dropping in the Elementor panel. Always pair every `start_*` with its `end_*`.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Per-instance CSS scoping | Manual `<style>` blocks with widget ID | `{{WRAPPER}}` selector token | Elementor auto-generates per-instance CSS keyed off the widget wrapper; `{{WRAPPER}}` is the canonical mechanism. Hand-rolled style blocks bypass Elementor's CSS cache and break in the editor. |
| Normal/Hover/Active UI | Custom radio controls + manual selector branching | `start_controls_tabs` / `start_controls_tab` | The tabbed state UI is the documented, expected Elementor UX. Users look for "Normal / Hover" tabs; not having them is a usability regression. |
| Responsive breakpoints | Manual `@media` queries + separate controls | `'responsive' => true` on SLIDER/DIMENSIONS | Elementor natively handles breakpoint inheritance (mobile inherits from tablet inherits from desktop), per-instance `@media` emission, and the responsive icon in the UI. Hand-rolling breaks the inheritance model. |
| Per-side padding with linked/unlinked toggle | 4 separate SLIDER controls | `Controls_Manager::DIMENSIONS` | DIMENSIONS ships with the link-toggle, unit picker, and `{{TOP}}{{UNIT}} …` tokens. Matches D-06 exactly. |
| Typography (font family/size/weight/line-height/letter-spacing) | 7 separate controls | `Group_Control_Typography` | One control, one selector, automatic global-fonts integration. Required by D-02 for 5 text surfaces. |
| Border (type/width/color/radius) | 4 separate controls | `Group_Control_Border` | Standard, includes the "none/solid/dashed/..." type selector and radius. |
| Box shadow | 6 separate controls | `Group_Control_Box_Shadow` | Standard, includes inset toggle. |
| Active state via pseudo-selectors | States API or `:active` | `.ddmm-current-item` / `.ddmm-current-ancestor` direct selectors | The Active state is a real DOM class (Phase 5 D-13), NOT a CSS pseudo-state. The States API generates `:hover`/`:focus` — useless here. The CSS `:active` pseudo-class means "while being clicked" — also wrong. The marker classes are the correct hook. |

**Key insight:** Every problem in this phase has a first-party Elementor API solution. The entire phase is "register controls that emit selectors" — there is zero new rendering logic, zero new JS, zero new DOM. The only file that changes structurally is `_register_controls()`; the CSS file gets baseline refinements + hardcoded-color removal; the renderer's `render_editor_preview()` gets a richer markup output.

## Common Pitfalls

### Pitfall 1: `{{WRAPPER}}` scope mismatch (selectors not applying)

**What goes wrong:** A selector like `.ddmm-menu__item { color: … }` (no `{{WRAPPER}}`) applies site-wide and gets overridden by the plugin's own CSS or theme CSS; conversely, a selector like `{{WRAPPER}} .ddmm-menu__item` works but a typo like `{{WRAPPER}}.ddmm-menu__item` (no space) targets the wrapper itself, not the item.
**Why it happens:** `{{WRAPPER}}` is a token Elementor replaces with the widget's unique wrapper class (e.g. `.elementor-element-abc123`). The space after it determines descendant vs self.
**How to avoid:** Always write `{{WRAPPER}} .ddmm-<bem-class>` (with a space). Never omit `{{WRAPPER}}`. Never write `{{WRAPPER}}.ddmm-…` unless you genuinely want to style the wrapper element itself.
**Warning signs:** Style changes appear site-wide, or never appear at all.

### Pitfall 2: Specificity wars with the plugin's own CSS

**What goes wrong:** The Style Tab sets a value, but the static `ddmm-frontend.css` rule wins because it has equal-or-higher specificity or uses `!important`.
**Why it happens:** The existing `.ddmm-menu__item.ddmm-current-item > a { color: var(--ddmm-trigger-color); }` (Phase 5) has specificity `0,2,1`. A Style Tab selector `{{WRAPPER}} .ddmm-menu__item.ddmm-current-item > a` expands to `.elementor-element-X .ddmm-menu__item.ddmm-current-item > a` = specificity `0,3,1` → wins. But if the static rule is `!important` or the var is overridden incorrectly, the cascade flips.
**How to avoid:** Audit `ddmm-frontend.css` before/after adding each Style Tab section. Convert any literal hardcoded color in the Phase 5 active-marker rules to a `--ddmm-*` var, and ensure no `!important` is used on any property the Style Tab will drive. The safest pattern is: the static CSS provides the var default; the Style Tab overrides the var; no `!important` anywhere.
**Warning signs:** Style Tab color picker changes but the rendered output doesn't. DevTools "Computed" shows the static CSS rule winning.

### Pitfall 3: Duplicate group-control `name` keys

**What goes wrong:** Two `add_group_control( Group_Control_Typography::get_type(), [ 'name' => 'typography', … ] )` calls (even in different sections) collide — the second silently overwrites the first, or the setting key collides and the editor panel throws.
**Why it happens:** Elementor builds the setting key from the `name` (e.g. `typography_font_size`). Duplicate names produce duplicate keys.
**How to avoid:** Every group control `name` MUST be unique across the whole widget. Use descriptive prefixes: `menu_item_typography`, `brand_title_typography`, `back_title_typography`, `trigger_text_typography`, `search_input_typography`, `search_results_typography`. Same for borders/shadows: `trigger_border`, `header_border`, `search_border`, `drawer_box_shadow`, etc.
**Warning signs:** One section's controls vanish from the editor panel, or settings don't persist.

### Pitfall 4: Forgetting to gate the Search section on `search_enabled`

**What goes wrong:** The Search Box Style Tab section shows up unconditionally, confusing users who haven't enabled search.
**Why it happens:** Missing `'condition' => [ 'search_enabled' => 'yes' ]`.
**How to avoid:** Copy the exact condition pattern used by the existing Content Tab `search_placeholder` control (line 514 of DrillDownMenu.php). The condition value is the string `'yes'`, not a boolean.
**Warning signs:** Search section appears when the search toggle is OFF.

### Pitfall 5: `separator` placement overuse

**What goes wrong:** Every control gets `'separator' => 'before'`, producing a wall of horizontal lines that hurts readability; or no separators at all, producing a wall of controls with no grouping.
**Why it happens:** `separator` is easy to sprinkle but hard to evaluate without rendering the panel.
**How to avoid:** Use `separator => 'before'` only at logical sub-group boundaries within a section (e.g. before the Typography group, before the state-tabs group). The first Style Tab section gets `'separator' => 'before'` to separate it from the last Content Tab section. Test by visually inspecting the panel.
**Warning signs:** The panel looks cluttered or undifferentiated.

### Pitfall 6: DIMENSIONS tokens misuse

**What goes wrong:** Writing `padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}};` (no units) produces invalid CSS like `padding: 8 12 8 12;`.
**Why it happens:** The DIMENSIONS tokens are bare numbers; the unit is a separate `{{UNIT}}` token.
**How to avoid:** Always write `padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};` — one `{{UNIT}}` after each numeric token. This is the documented DIMENSIONS pattern. `[CITED: developers.elementor.com/docs/editor-controls/control-dimensions/]`
**Warning signs:** Padding/border-width values silently fail; the property appears blank in DevTools.

### Pitfall 7: Responsive value gaps (mobile value not inheriting)

**What goes wrong:** User sets a desktop drawer width of 320px but the mobile view shows the default 320px because "no mobile value set" means "use desktop value" — but if the default mobile value differs, the user is confused.
**Why it happens:** Elementor's responsive inheritance: mobile inherits from tablet, tablet inherits from desktop. If the user sets desktop only, mobile/tablet inherit. But if the `default` array specifies only a desktop default, mobile/tablet inherit that default — usually fine.
**How to avoid:** Set `default` with a single `size`+`unit`; Elementor propagates it to all breakpoints. Do not try to specify per-breakpoint defaults in the `default` array (not supported). Document in the control description if per-breakpoint override is expected.
**Warning signs:** Mobile preview shows a different size than desktop even though the user only set desktop.

### Pitfall 8: Editor preview not neutralizing off-canvas transform

**What goes wrong:** After the D-07 rework adds `.ddmm-drawer` to the editor preview for STYL-02 parity, the drawer disappears from the preview because `.ddmm-drawer { transform: translateX(-100%); position: fixed; }` takes effect.
**Why it happens:** The drawer is designed to be off-canvas on the frontend; in the editor preview, it must be visible.
**How to avoid:** Extend the existing `.ddmm-editor-preview .ddmm-menu { position: static; }` override pattern (line 281) to also neutralize the drawer: `.ddmm-editor-preview .ddmm-drawer { position: static; transform: none; height: auto; }`. Alternatively (Claude's discretion per CONTEXT.md), do NOT emit `.ddmm-drawer` in the preview and instead render a parallel `.ddmm-editor-drawer-frame` container whose background/width are driven by the same `--ddmm-drawer-*` vars. Either approach achieves parity; the first is more DRY, the second is safer against accidental off-canvas regressions.
**Warning signs:** The drawer portion of the editor preview is blank or shifted off-screen.

### Pitfall 9: Default-value drift between CSS and Style Tab `default`

**What goes wrong:** The CSS declares `--ddmm-drawer-bg: #ffffff;` but the Style Tab COLOR control declares `'default' => '#FFFFFF'` (uppercase) — the editor shows the default swatch but a re-save writes a slightly different value, or the diff is visible.
**Why it happens:** Manual synchronization of two defaults (CSS var + control default).
**How to avoid:** Use IDENTICAL values (case, format) in both places. Audit each new control's `default` against the corresponding CSS var declaration. D-01 (polish the baseline) is the right moment to set both to the refined value in one pass.
**Warning signs:** The widget looks subtly different after the user opens + saves the Style Tab without changing anything.

## Code Examples

All examples below are verified patterns drawn from official Elementor docs and the existing widget code. Each is sized to drop into `_register_controls()` with minimal adaptation.

### Example A: Complete Trigger Button section (STYL-01)

```php
// Source: Elementor Style Tab + selectors pattern
// [CITED: developers.elementor.com/docs/widgets/rendering-style/]
// [CITED: developers.elementor.com/docs/editor-controls/control-tabs/]
$this->start_controls_section(
    'section_style_trigger',
    [
        'label'     => esc_html__( 'Trigger Button', 'devsroom-drilldown-mobile-menu' ),
        'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
        'separator' => 'before',   // separate from last Content Tab section
    ]
);

// --- Normal / Hover state tabs (D-03: trigger hover) ---
$this->start_controls_tabs( 'trigger_state_tabs' );

$this->start_controls_tab( 'trigger_normal', [ 'label' => esc_html__( 'Normal', 'devsroom-drilldown-mobile-menu' ) ] );
$this->add_control( 'trigger_color', [
    'label'     => esc_html__( 'Color', 'devsroom-drilldown-mobile-menu' ),
    'type'      => \Elementor\Controls_Manager::COLOR,
    'default'   => '#1a1a1a',
    'selectors' => [ '{{WRAPPER}}' => '--ddmm-trigger-color: {{VALUE}};' ],
] );
$this->add_control( 'trigger_bg', [
    'label'     => esc_html__( 'Background', 'devsroom-drilldown-mobile-menu' ),
    'type'      => \Elementor\Controls_Manager::COLOR,
    'selectors' => [ '{{WRAPPER}}' => '--ddmm-trigger-bg: {{VALUE}};' ],
] );
$this->end_controls_tab();

$this->start_controls_tab( 'trigger_hover', [ 'label' => esc_html__( 'Hover', 'devsroom-drilldown-mobile-menu' ) ] );
$this->add_control( 'trigger_hover_bg', [
    'label'     => esc_html__( 'Background', 'devsroom-drilldown-mobile-menu' ),
    'type'      => \Elementor\Controls_Manager::COLOR,
    'selectors' => [ '{{WRAPPER}} .ddmm-trigger:hover' => 'background: {{VALUE}};' ],
] );
$this->add_control( 'trigger_hover_color', [
    'label'     => esc_html__( 'Color', 'devsroom-drilldown-mobile-menu' ),
    'type'      => \Elementor\Controls_Manager::COLOR,
    'selectors' => [ '{{WRAPPER}} .ddmm-trigger:hover' => 'color: {{VALUE}};' ],
] );
$this->end_controls_tab();

$this->end_controls_tabs();

// Hamburger size (D-05: responsive).
$this->add_control( 'hamburger_width', [
    'label'      => esc_html__( 'Hamburger Width', 'devsroom-drilldown-mobile-menu' ),
    'type'       => \Elementor\Controls_Manager::SLIDER,
    'size_units' => [ 'px' ],
    'responsive' => true,
    'range'      => [ 'px' => [ 'min' => 16, 'max' => 48, 'step' => 1 ] ],
    'default'    => [ 'unit' => 'px', 'size' => 28 ],
    'selectors'  => [ '{{WRAPPER}}' => '--ddmm-hamburger-width: {{SIZE}}{{UNIT}};' ],
] );

// Padding (D-06: per-side Dimensions, linked by default).
$this->add_control( 'trigger_padding', [
    'label'      => esc_html__( 'Padding', 'devsroom-drilldown-mobile-menu' ),
    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
    'size_units' => [ 'px', 'em' ],
    'default'    => [ 'top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8, 'unit' => 'px', 'isLinked' => true ],
    'selectors'  => [ '{{WRAPPER}} .ddmm-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
] );

// Border (group control).
$this->add_group_control(
    \Elementor\Group_Control_Border::get_type(),
    [ 'name' => 'trigger_border', 'label' => esc_html__( 'Border', 'devsroom-drilldown-mobile-menu' ), 'selector' => '{{WRAPPER}} .ddmm-trigger' ]
);

// Typography (D-02: trigger text typography — applies to text_only / icon_text label).
$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    [ 'name' => 'trigger_text_typography', 'label' => esc_html__( 'Typography', 'devsroom-drilldown-mobile-menu' ), 'selector' => '{{WRAPPER}} .ddmm-trigger, {{WRAPPER}} .ddmm-trigger__text' ]
);

$this->end_controls_section();
```

### Example B: Drawer section (STYL-02) — see Pattern 5 above for width

```php
$this->start_controls_section(
    'section_style_drawer',
    [
        'label'     => esc_html__( 'Drawer', 'devsroom-drilldown-mobile-menu' ),
        'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
    ]
);
// drawer_width — see Pattern 5 example above
$this->add_control( 'drawer_bg', [
    'label'     => esc_html__( 'Drawer Background', 'devsroom-drilldown-mobile-menu' ),
    'type'      => \Elementor\Controls_Manager::COLOR,
    'default'   => '#ffffff',
    'selectors' => [ '{{WRAPPER}}' => '--ddmm-drawer-bg: {{VALUE}};' ],
] );
$this->add_control( 'overlay_color', [
    'label'     => esc_html__( 'Overlay Color', 'devsroom-drilldown-mobile-menu' ),
    'type'      => \Elementor\Controls_Manager::COLOR,
    'alpha'     => true,   // overlay is semi-transparent by design
    'default'   => 'rgba(0,0,0,0.5)',
    'selectors' => [ '{{WRAPPER}}' => '--ddmm-overlay-bg: {{VALUE}};' ],
] );
$this->add_group_control(
    \Elementor\Group_Control_Box_Shadow::get_type(),
    [ 'name' => 'drawer_box_shadow', 'label' => esc_html__( 'Box Shadow', 'devsroom-drilldown-mobile-menu' ), 'selector' => '{{WRAPPER}} .ddmm-drawer' ]
);
$this->end_controls_section();
```

### Example C: Selectors for each of the 6 sections (reference table)

| Section | BEM Target | Selector Template | Notes |
|---------|-----------|-------------------|-------|
| STYL-01 Trigger | `.ddmm-trigger`, `.ddmm-hamburger`, `.ddmm-trigger__text` | `{{WRAPPER}} .ddmm-trigger` / `{{WRAPPER}}` (for `--ddmm-trigger-*` vars) | Hover: `:hover` suffix on the element selector |
| STYL-02 Drawer | `.ddmm-drawer`, `.ddmm-overlay` | `{{WRAPPER}} .ddmm-drawer` / `{{WRAPPER}}` (for `--ddmm-drawer-*` / `--ddmm-overlay-bg` vars) | Width responsive (D-05); overlay uses `alpha => true` |
| STYL-03 Header | `.ddmm-header`, `.ddmm-brand__text`, `.ddmm-brand__img`, `.ddmm-close` | `{{WRAPPER}} .ddmm-header` / `{{WRAPPER}} .ddmm-brand__text` / `{{WRAPPER}} .ddmm-close` | Height via `--ddmm-header-height` var override; brand-title Typography group |
| STYL-04 Panel+Back Row | `.ddmm-back`, `.ddmm-back__button`, `.ddmm-back__title` | `{{WRAPPER}} .ddmm-back__button` / `{{WRAPPER}} .ddmm-back__title` | Hover on `.ddmm-back__button:hover`; divider color via `--ddmm-menu-border-color` override on `{{WRAPPER}}` |
| STYL-05 Menu Items | `.ddmm-menu__item > a`, `.ddmm-menu__item`, `.ddmm-chevron::after` | `{{WRAPPER}} .ddmm-menu__item` / `{{WRAPPER}} .ddmm-menu__item > a` / `{{WRAPPER}} .ddmm-chevron::after` | Active uses `.ddmm-menu__item.ddmm-current-item` (Pattern 3); arrow color targets the `::after` pseudo-element |
| STYL-06 Search Box | `.ddmm-search__input`, `.ddmm-search__results` | `{{WRAPPER}} .ddmm-search__input` | Condition: `search_enabled === 'yes'`; typography on input + results per D-02 |

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `widgets_registered` hook | `elementor/widgets/register` hook | Elementor 3.5+ (project baseline 3.29+) | Already in use; no Phase 6 impact |
| Flat `selectors` arrays | Same — stable for 5+ years | n/a | No migration needed |
| Manual responsive controls (separate per-breakpoint controls) | `'responsive' => true` flag on SLIDER/DIMENSIONS | Elementor 3.4+ | The single-flag approach is current; use it for D-05 |
| Elementor States API (newer, ATF-style) | Coexists with classic `start_controls_tabs` | 2023+ | For Active state, classic tabs are required (marker classes, not pseudo-states). For Normal/Hover, classic tabs are still the documented standard. |

**Deprecated/outdated:**
- The `widgets_registered` hook is deprecated (already avoided in this project).
- Manual `wp_localize_script` for per-instance config is superseded by data-* + `--ddmm-*` (Phase 4 D-15) — already in place, Phase 6 does not regress.
- Hardcoded color values in `ddmm-frontend.css` for properties the Style Tab will drive — these MUST be converted to `--ddmm-*` vars or removed (the `.ddmm-editor-preview` `#fff`/`#eee` are the prime targets per D-07).

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Elementor Free 3.29+ supports all the documented Style Tab / group-control / `start_controls_tabs` / `responsive => true` APIs used here. | Standard Stack | LOW — these APIs have been stable since Elementor 2.x/3.4; verified against current official docs. |
| A2 | The existing Phase 5 active-marker CSS rule (`.ddmm-menu__item.ddmm-current-item > a { color: var(--ddmm-trigger-color); font-weight: 600; }`) uses a var (not a literal color) and has no `!important`. | Pitfall 2 | MEDIUM — if it has `!important` or a literal color, the Style Tab Active selector needs higher specificity. **Action:** verify in Pitfall 2 audit before shipping. (Verified during this research: lines 508-514 of ddmm-frontend.css use `var(--ddmm-trigger-color)` and no `!important` — confirmed safe.) |
| A3 | DIMENSIONS `default` accepts the documented `top/right/bottom/left/unit/isLinked` shape. | Code Examples | LOW — verified against official docs `[CITED: developers.elementor.com/docs/editor-controls/control-dimensions/]`. |
| A4 | The editor preview lives inside `{{WRAPPER}}` so var overrides cascade in. | Pattern 6 | LOW — verified by reading DrillDownMenu.php::render() lines 660-665: the preview block is echoed inside the `.ddmm-widget` wrapper, which IS `{{WRAPPER}}`. |
| A5 | Typography group's font-size sub-field is responsive-by-default in the UI. | Pattern 5 | LOW — Typography exposes responsive toggles on size/line-height; D-05's typography requirement is satisfied by this. Verified via the existing animation_duration SLIDER which already uses responsive-style sizing. |

**No high-risk assumptions remain.** A2 was the only one flagged MEDIUM and is now verified safe by reading the CSS.

## Open Questions

1. **Global accent color vs per-section active colors** — Claude's discretion per CONTEXT.md. The research recommends a single "Menu Active Color" in the Menu Items section, reused for current-item + ancestor (matches D-04's "single Active state for item + trail" model). Per-section hover colors stay distinct. No user confirmation needed — the discretion is locked.

2. **How to render the drawer in the editor preview (Pitfall 8)** — Two viable approaches (neutralize the off-canvas transform inside the preview vs. render a parallel `.ddmm-editor-drawer-frame`). Both achieve parity. At Claude's discretion — the planner should pick one and document it. The neutralize approach is more DRY but risks accidental off-canvas regression; the parallel-frame approach is safer but slightly more CSS.

3. **Whether to render a sample search row in the editor preview when search is OFF** — D-08 allows either (always-show vs gate-on-toggle). The research recommends always-showing a sample search row in the preview (so users can pre-style search before enabling it), but the discretion is Claude's. No blocker.

4. **Exact refined baseline values (D-01)** — The CONTEXT.md leaves the specific refined border/shadow/spacing values to Claude's discretion. The research recommends: softer `--ddmm-menu-border-color` (e.g. `rgba(0,0,0,0.06)` instead of `0.05`), a subtle drawer `box-shadow` default baked into the CSS as a `--ddmm-drawer-box-shadow` var, and slightly tighter header padding. No user confirmation needed.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.1+ | All PHP changes (widget + renderer) | ✓ (declared minimum per CLAUDE.md) | 8.1+ | — |
| WordPress 6.5+ | Plugin runtime | ✓ (declared minimum per CLAUDE.md) | 6.5+ | — |
| Elementor Free 3.29+ | All Style Tab APIs | ✓ (declared minimum per CLAUDE.md) | 3.29+ | — |
| `php` CLI | Validation (`php -l` syntax check) | Probe at Wave 0 | — | DevTools console fallback (none needed for PHP-only phase) |

**Missing dependencies with no fallback:** None.

**Missing dependencies with fallback:** None — this phase is purely PHP + CSS, no JS, no build step, no external services.

## Validation Architecture

> `workflow.nyquist_validation: true` in config.json → section REQUIRED.

### Test Framework

| Property | Value |
|----------|-------|
| Framework | None — no PHPUnit / wp-env / Jest / Playwright configured (matches Phase 4 + Phase 5 pattern; CLAUDE.md mandates no build tool). `php -l` is the PHP syntax verifier. |
| Config file | none |
| Quick run command | `php -l src/Elementor/Widget/DrillDownMenu.php` + `php -l src/Rendering/DrawerRenderer.php` |
| Full suite command | `find src -name '*.php' -exec php -l {} \;` + grep checks below + manual browser verification in Elementor editor |

The project has no PHP test harness. `php -l` catches syntax errors in ~0.3s. The Style Tab controls are PHP data structures — they cannot be unit-tested without a loaded Elementor environment, so validation is **structural grep** (does each required control exist? does it have a `selectors`/`selector` entry? is it in the right tab?) plus **manual browser verification** (does the editor render the controls? does changing a control update the editor preview AND the published page?).

**Environment probe required at Wave 0:** confirm `php` is on PATH. Probe: `php --version`. If absent, fall back to pasting the PHP file into a PHP linter web tool. No production dependency is added by Phase 6.

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| STYL-01 | Trigger section has COLOR, SLIDER (hamburger), DIMENSIONS (padding), Group_Control_Border, Group_Control_Typography, Normal/Hover tabs | static grep | `grep -cE "section_style_trigger" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "trigger_(color\|bg\|hover_color\|hover_bg\|padding\|border\|text_typography\|hamburger_width)" src/Elementor/Widget/DrillDownMenu.php` ≥8 AND `grep -c "start_controls_tabs.*trigger_state_tabs" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| STYL-02 | Drawer section has SLIDER (width px/vw/%), COLOR (bg), Group_Control_Box_Shadow, COLOR (overlay alpha) | static grep | `grep -cE "section_style_drawer" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "drawer_(width\|bg\|box_shadow)" src/Elementor/Widget/DrillDownMenu.php` ≥3 AND `grep -cE "overlay_color\|overlay_bg" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "'px', *'vw', *'%'" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| STYL-03 | Header section has COLOR (bg), Group_Control_Border, SLIDER (height), Group_Control_Typography (title), COLOR (title), COLOR (close) | static grep | `grep -cE "section_style_header" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "header_(bg\|border\|height\|title_typography\|title_color\|close_color)" src/Elementor/Widget/DrillDownMenu.php` ≥6 | ❌ Wave 0 |
| STYL-04 | Panel+Back Row section has COLOR (back text), COLOR (back bg normal), COLOR (back bg hover), Group_Control_Typography (title), COLOR (title), COLOR (divider) | static grep | `grep -cE "section_style_panel_back\|section_style_back" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "back_(color\|bg\|hover_bg\|title_typography\|title_color)" src/Elementor/Widget/DrillDownMenu.php` ≥5 AND `grep -cE "divider_color\|menu_border_color" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| STYL-05 | Menu Items section has Normal/Hover/Active tabs, each with text/bg color; min-height SLIDER; DIMENSIONS padding; Group_Control_Typography; chevron color | static grep | `grep -cE "section_style_menu_items\|section_style_items" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "menu_item_(normal\|hover\|active)" src/Elementor/Widget/DrillDownMenu.php` ≥3 AND `grep -cE "ddmm-current-item\|ddmm-current-ancestor" src/Elementor/Widget/DrillDownMenu.php` ≥2 AND `grep -cE "menu_item_(min_height\|padding\|typography\|chevron_color\|arrow_color)" src/Elementor/Widget/DrillDownMenu.php` ≥4 | ❌ Wave 0 |
| STYL-06 | Search section (conditional on search_enabled) has COLOR (bg), COLOR (text), Group_Control_Border, SLIDER (radius), Group_Control_Typography (input + results per D-02) | static grep | `grep -cE "section_style_search" src/Elementor/Widget/DrillDownMenu.php` ≥1 AND `grep -cE "search_(input_bg\|input_color\|input_border\|input_typography)" src/Elementor/Widget/DrillDownMenu.php` ≥4 AND `grep -cE "'condition' *=> *\[[^]]*'search_enabled' *=> *'yes'" src/Elementor/Widget/DrillDownMenu.php` ≥1 | ❌ Wave 0 |
| SC#5 (D-07) | Editor preview reworked to emit trigger + header + back row + chevron + (optional) search using real BEM classes; `.ddmm-editor-preview` `#fff`/`#eee` removed | static grep | `grep -cE "ddmm-(trigger\|header\|back\|brand__text\|close\|chevron)" src/Rendering/DrawerRenderer.php` ≥6 AND `grep -cE "#fff\|#eee\|#ffffff\|#eeeeee" assets/css/ddmm-frontend.css` (within `.ddmm-editor-preview` block) = 0 | ❌ Wave 0 |
| SC#5 (D-08) | All 6 sections visible in editor preview | manual | (browser inspection of editor preview) | n/a — manual |
| (carry) PLUG-06 | No unescaped output in new PHP | negative grep | `grep -nE "echo\s+\\\$\|printf\([^)]*%s" src/Rendering/DrawerRenderer.php` reviewed — every dynamic value wrapped in `esc_attr/esc_html/esc_url` or carries the `phpcs:ignore … OutputNotEscaped` comment | ✅ exists (must hold) |
| (carry) BEM stable | No new BEM class names introduced (Phase 4 D-26 lock) | static grep | `grep -cE "ddmm-" assets/css/ddmm-frontend.css` (count before vs after — only new vars allowed, no new class names beyond the existing catalog) | ✅ exists (must hold) |

### Sampling Rate

- **After every task commit:** `php -l` on touched PHP files + the grep checks for that task's requirements.
- **After every plan wave:** full lint sweep + all greps + manual browser verification of one section per wave (rotating through the 6 sections).
- **Before `/gsd-verify-work`:** Full suite must be green — all 6 sections verified in browser (editor preview matches published page for each section's controls), all greps green, no `#fff`/`#eee` in `.ddmm-editor-preview`, no hardcoded literal colors in the Phase 5 active-marker rules (Pitfall 2 audit).
- **Max feedback latency:** ~4 seconds (lint + greps; manual checks batched per wave).

### Wave 0 Gaps

- [ ] `src/Elementor/Widget/DrillDownMenu.php` — add the 6 `TAB_STYLE` sections to `_register_controls()`. All STYL-01..06 greps depend on this.
- [ ] `assets/css/ddmm-frontend.css` — (a) refine baseline defaults (D-01): softer border color, drawer shadow var, refined spacing; (b) remove `.ddmm-editor-preview .ddmm-menu__item { background: #fff; border-bottom: 1px solid #eee; }` hardcoded colors (D-07); (c) add `.ddmm-editor-preview .ddmm-drawer { position: static; transform: none; height: auto; }` neutralizer (Pitfall 8); (d) audit the Phase 5 `.ddmm-current-item` rule for !important-free var-driven values (Pitfall 2).
- [ ] `src/Rendering/DrawerRenderer.php` — rework `render_editor_preview()` to emit the full representative markup (trigger + header + brand text + close + back row + sample current-item + chevron + optional search row).
- [ ] Environment probe: confirm `php` available; if absent, document linter-web-tool fallback.

*(If no gaps: not applicable — gaps listed above.)*

## Security Domain

> `security_enforcement` not explicitly set in config.json → defaults to enabled. Include this section.

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | n/a — no auth in this phase |
| V3 Session Management | no | n/a |
| V4 Access Control | no | n/a — no capability checks needed for Style Tab controls (they run in the Elementor editor context, which already requires `edit_posts`) |
| V5 Input Validation | yes (minimal) | All user-facing control strings via `esc_html__()`; all dynamic output via `esc_attr`/`esc_html`/`esc_url` (PLUG-06 carry). Style Tab control VALUES are not echoed — Elementor's CSS-Generation pipeline sanitizes them. The editor-preview rework emits the same escaped pattern as the existing renderer. |
| V6 Cryptography | no | n/a |
| V7 Errors & Logging | no | n/a |
| V12 Files & Resources | no | n/a — no file uploads in this phase |

### Known Threat Patterns for Elementor Widget Style Tab

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| XSS via control default values | Tampering | All defaults are static strings wrapped in `esc_html__()`; no user input flows into control definitions. |
| XSS via editor preview rework | Tampering | The reworked `render_editor_preview()` follows the existing escaping pattern: `esc_html()` for sample item labels, `esc_attr()` for any attribute values, `esc_url()` for any URL. Sample content is hardcoded translatable strings — not user input. |
| CSS injection via selector values | Tampering | Not applicable — selectors are hardcoded in PHP, not user-supplied. The user picks color/size VALUES which Elementor sanitizes before emitting CSS. |

**Conclusion:** No new security surface. The phase reuses established escaping patterns (PLUG-06 carry). The `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` convention for `Icons_Manager` output is not needed in Phase 6 (no new icons) but the pattern carries forward unchanged.

## Sources

### Primary (HIGH confidence)
- **Elementor Developer Docs — Rendering Style** `https://developers.elementor.com/docs/widgets/rendering-style/` — `{{WRAPPER}}`, `{{VALUE}}`, `{{UNIT}}` selector tokens; `selectors` array pattern; `TAB_STYLE` usage. [Fetched 2026-06-14.]
- **Elementor Developer Docs — Control Tabs** `https://developers.elementor.com/docs/editor-controls/control-tabs/` — `start_controls_tabs` / `start_controls_tab` / `end_controls_tab` / `end_controls_tabs` pattern for Normal/Hover inner tabs. [Fetched 2026-06-14.]
- **Elementor Developer Docs — Typography Group Control** `https://developers.elementor.com/docs/editor-controls/group-control-typography/` — `Group_Control_Typography::get_type()`, singular `selector` argument, `name`/`exclude` params. [Fetched 2026-06-14.]
- **Elementor Developer Docs — Dimensions Control** `https://developers.elementor.com/docs/editor-controls/control-dimensions/` — `DIMENSIONS` return shape (`top/right/bottom/left/unit/isLinked`), `{{TOP}}{{UNIT}} …` selector tokens, `default` shape, `size_units`. [Fetched 2026-06-14.]
- **Elementor Developer Docs — Group Control** `https://developers.elementor.com/docs/editor-controls/group-control/` — `add_group_control()` pattern. [Cited 2026-06-14.]
- **Elementor Developer Docs — Box Shadow Group Control** `https://developers.elementor.com/docs/editor-controls/group-control-box-shadow/` — `Group_Control_Box_Shadow::get_type()`. [Cited 2026-06-14.]
- **Elementor Developer Docs — Additional Custom Breakpoints** `https://developers.elementor.com/additional-custom-breakpoints-technical-details-and-gotchas/` — `responsive => true` flag, breakpoint inheritance (mobile ← tablet ← desktop). [Cited 2026-06-14.]
- **Codebase (read in full):** `src/Elementor/Widget/DrillDownMenu.php` (existing Content Tab control patterns, escaping, render() editor/frontend branch), `assets/css/ddmm-frontend.css` (existing `--ddmm-*` var declarations lines 8-36, BEM class catalog, `.ddmm-editor-preview` block lines 273-288, Phase 5 active-marker rules lines 508-514), `src/Rendering/DrawerRenderer.php` (every BEM class emission, `render_editor_preview()` current implementation).

### Secondary (MEDIUM confidence)
- WebSearch verified against official Elementor docs for `'responsive' => true` semantics — confirmed via the official Additional Custom Breakpoints page.

### Tertiary (LOW confidence)
- None — all claims trace to either official docs or the codebase.

## Metadata

**Confidence breakdown:**
- Standard stack (Elementor Style Tab APIs): **HIGH** — verified against current official docs (fetched 2026-06-14) + existing widget already uses the same APIs for Content Tab.
- Architecture patterns (selector bridge, state tabs, Active via marker classes): **HIGH** — directly documented + the marker classes verified present in `DrawerRenderer.php` + `ddmm-frontend.css`.
- Pitfalls: **HIGH** — drawn from official docs + Elementor community knowledge + direct reading of the codebase (specificity verified for the Phase 5 active-marker rules).
- Editor-preview parity (D-07/D-08): **MEDIUM-HIGH** — the parity mechanism (preview lives inside `{{WRAPPER}}`, so var overrides + BEM-classed selectors cascade) is verified by reading `render()`. The specific Pitfall 8 mitigation (neutralize off-canvas transform) is straightforward CSS but the exact approach is at Claude's discretion.
- Validation architecture: **HIGH** — matches the Phase 4 + Phase 5 pattern (no test harness, lint + structural grep + manual browser).

**Research date:** 2026-06-14
**Valid until:** 2026-07-14 (30 days — Elementor Style Tab APIs are stable; the `--ddmm-*` bridge and BEM classes are locked by prior phase decisions and will not change within this window)

## RESEARCH COMPLETE
