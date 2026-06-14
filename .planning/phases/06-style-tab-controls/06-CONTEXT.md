# Phase 6: Style Tab Controls - Context

**Gathered:** 2026-06-14
**Status:** Ready for planning

<domain>
## Phase Boundary

A full Elementor **Style Tab** so users can customize every visual element of the drill-down menu — trigger button, drawer, header, panel/back-row, menu items (with Normal/Hover/Active states), and search box — entirely through Elementor's Style tab. The underlying BEM markup and the `--ddmm-*` CSS-custom-property theming bridge already exist from Phases 4–5; this phase adds Style Tab sections + selectors on top of them. No new menu-source, rendering, interaction, or accessibility work.

Covers requirements: **STYL-01, STYL-02, STYL-03, STYL-04, STYL-05, STYL-06**.

**In scope:**
- New Style Tab sections in `DrillDownMenu::_register_controls()` for: Trigger Button (STYL-01), Drawer (STYL-02), Header (STYL-03), Panel & Back Row (STYL-04), Menu Items with Normal/Hover/Active tabs (STYL-05), Search Box (STYL-06)
- Wiring each control to its element via the established `--ddmm-*` custom-property bridge and/or Elementor `selectors` targeting the existing BEM classes
- Reworking `.ddmm-editor-preview` into a full representative preview so Style Tab changes render identically in the editor and on the published page (Success Criteria #5)
- Refining the baseline CSS defaults for a polished un-styled appearance (D-01)
- Hover states for items + trigger + back button + close ✕ (D-03); Active styling for current item + ancestor trail (D-04)
- Responsive (per-breakpoint) sizing for width/padding/typography (D-05); per-side Dimensions spacing (D-06)
- Typography group controls on menu items, header/brand title, back-row title (required) + trigger text + search input/results (D-02)

**Out of scope:**
- Keyboard navigation, focus management, Tab trap (A11Y-04..08) — Phase 7
- WooCommerce URL verification (COMP-03), `.pot`/translation packaging (COMP-04) — Phase 7
- New menu sources, new rendering markup, new JS interactions — already delivered in Phases 2–5
- `content_template()` live editor preview (PRES-01) — v2 (the reworked static `.ddmm-editor-preview` is the SC#5 parity vehicle, not a true live preview)
- RTL layout (RTL-01) — v2
- Elementor Pro menu-item icon meta — v2

</domain>

<decisions>
## Implementation Decisions

### Default Aesthetic
- **D-01:** **Polish the baseline.** Refine the existing CSS defaults (softer borders, refined spacing, a subtle drawer box-shadow) so the widget looks good *un-styled* on first drop-in, then the Style Tab controls build on top with these refined values as their defaults. Not a neutral blank slate and not a Packiro clone — a designed, modern starting point. (Chosen over "keep current defaults as-is" and "match Packiro reference".)

### Typography Coverage
- **D-02:** Typography group controls (`\Elementor\Group_Control_Typography`) are added to: **menu items, header/brand title, back-row title** (all required by STYL-01/03/04/05) **PLUS trigger text** (text_only / icon_text label) **and search input + results** (extends STYL-06). All five text surfaces get typography. Exact control placement per-section at Claude's discretion.

### Hover/Active State Depth
- **D-03 (Hover breadth):** Hover states cover **menu items (required by STYL-05) PLUS the trigger button, the back button / back row, and the close ✕ button** — the primary tap targets. The chevron (›) and search input get a focus treatment rather than a hover treatment. (Chosen over "menu items only" and "every interactive element".)
- **D-04 (Active meaning):** The "Active" state styles **BOTH the current-page item (`ddmm-current-item`) AND each ancestor up the trail (`ddmm-current-ancestor`)** — mirroring WordPress `current-menu-item` / `current-menu-ancestor` and matching the markers Phase 5 already emits (Phase 5 D-13). No separate "opened-parent" highlight state in v1 (deferred).

### Responsive Controls
- **D-05 (Responsive sizing):** **Per-breakpoint sizing** for the key dimensions — drawer width, menu-item padding, typography font sizes — using Elementor's responsive UI (mobile / tablet / desktop values). Color, border-color, and most non-sizing controls stay single-value. The existing `max-width: 85vw` drawer cap is retained as a safety net. (Chosen over "single value everywhere" and "full responsive on everything".)
- **D-06 (Spacing model):** Padding/spacing controls use **Elementor Dimensions (per-side top/right/bottom/left) with sides linked by default** — users can unlink for asymmetric padding. Applies to menu-item, drawer, header, and search padding. (Chosen over "uniform single value".)

### Editor Preview Fidelity (Success Criteria #5)
- **D-07 (Preview scope):** **Full representative preview.** Rework `.ddmm-editor-preview` to render trigger + header (brand + close ✕) + the root panel with items + one sample back row + a chevron (›) — all using the **real BEM classes and the same `--ddmm-*` vars/selectors**, with the hardcoded `#fff`/`#eee` fallback colors removed so Style Tab styling cascades through identically. Sub-panels remain omitted (root representative only). (Chosen over "core elements only" and "keep as-is".)
- **D-08 (SC#5 strictness):** **Strict parity everywhere.** Every Style Tab control must be reflected in the editor preview; engineer the preview + selectors so all six sections (trigger/drawer/header/items/back-row/search) are visible in the editor and match the published page. Search-box styling is reflected by rendering a sample search row in the preview even when the feature is off (or gated by the existing toggle — at Claude's discretion). (Chosen over "parity for previewed sections only" and "best-effort".)

### Carried Forward — Locked (not re-asked)
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

### Folded Todos
None — no pending todos matched Phase 6 scope (`todo match-phase` returned 0 matches).

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase 6 Source Files (PRIMARY — the files this phase modifies)
- `src/Elementor/Widget/DrillDownMenu.php` — **Integration point.** `_register_controls()` currently has Content Tab sections only (`section_trigger`, `section_menu`, `section_drawer_header`, `section_drawer_settings`, `section_animation`, `section_search`); Phase 6 adds Style Tab sections (`\Elementor\Controls_Manager::TAB_STYLE`) here. `render()` already emits the `.ddmm-widget` wrapper, the trigger, the editor-preview block, and the `DrawerRenderer` call.
- `assets/css/ddmm-frontend.css` — **The theming layer.** All `--ddmm-*` vars are declared on `.elementor-widget-ddmm-drilldown-menu` (lines 8–36) with the header comment *"CSS custom properties overridden by Phase 6 Style Tab controls."* BEM classes for every element are defined here. The `.ddmm-editor-preview` block (lines 273–288) holds the hardcoded `#fff`/`#eee` fallbacks that D-07 removes.

### Phase 6 DOM Contract (the classes selectors will target)
- `src/Rendering/DrawerRenderer.php` — Emits every BEM class the Style Tab targets (`.ddmm-header`, `.ddmm-brand__img/__text`, `.ddmm-close`, `.ddmm-menu__item`, `.ddmm-menu__icon`, `.ddmm-chevron`, `.ddmm-back__button/__title`, `.ddmm-search__*`), the auto-open marker classes (`ddmm-current-item` / `ddmm-current-ancestor`), and `render_editor_preview()` (the rework target for D-07).

### Requirements & Project Context
- `.planning/REQUIREMENTS.md` — Phase 6 covers **STYL-01, STYL-02, STYL-03, STYL-04, STYL-05, STYL-06** (see §Style Tab)
- `.planning/PROJECT.md` — Core value (flawless drill-down at any depth), locked architecture decisions
- `.planning/ROADMAP.md` — Phase 6 goal + 5 success criteria (esp. SC#5 editor≡published parity). `UI hint: yes`.
- `CLAUDE.md` — Stack (CSS nesting + custom properties; no jQuery), Elementor control + group-control patterns, escaping conventions

### Prior Phase Context (locked decisions carried forward)
- `.planning/phases/04-rendering-pipeline-drawer-html/04-CONTEXT.md` — **Primary dependency.** D-01 split parent, D-02 CSS `::after` chevron (themeable), D-15 `--ddmm-*` + data-* config bridge (the theming mechanism Phase 6 rides on), D-26 BEM state-class convention.
- `.planning/phases/05-frontend-drill-down-javascript/05-CONTEXT.md` — D-07 search sticky-bar markup, D-13 current-item + ancestor marker classes (the Active hooks), D-09 search off-by-default.

### Architecture & Research
- `.planning/research/ARCHITECTURE.md` — Rendering data flow, BEM/class structure, anti-patterns (no globals, instance-scoped)
- `.planning/research/STACK.md` — CSS-custom-property theming rationale

No external specs/ADRs — requirements are fully captured in the decisions above.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `--ddmm-*` custom properties on `.elementor-widget-ddmm-drilldown-menu` — already consumed by every BEM rule; most Style Tab color/size controls can override these directly (minimal new CSS).
- Complete BEM class catalog (see CSS + DrawerRenderer) — every selector target already exists; no new frontend markup is needed for STYL-01..06 (only the editor-preview rework).
- `\Elementor\Icons_Manager`, `\Elementor\Group_Control_Typography / _Border / _Box_Shadow`, `Controls_Manager::DIMENSIONS / SLIDER / COLOR / TABS` — standard Elementor control API already used throughout `_register_controls()`.
- Auto-open marker classes (`ddmm-current-item`, `ddmm-current-ancestor`) — the Active-state hooks are already emitted by the renderer/JS; Phase 6 only styles them.

### Established Patterns
- **CSS-var config bridge** (Phase 4 D-15): per-instance settings as `--ddmm-*` on the widget wrapper. Style Tab controls should override these for instance-correctness; group controls (Typography / Box-Shadow / Border) and hover/active states use Elementor `selectors` keyed off `{{WRAPPER}} .<bem-class>`.
- **Editor vs frontend branching** (Phase 4 D-18/D-20): `is_edit_mode()` already selects the preview block vs the real drawer — the reworked preview (D-07) lives behind the same branch in `render()`.
- **Escaping**: all dynamic output via `esc_attr`/`esc_html`/`esc_url`; `Icons_Manager` output via the existing `phpcs:ignore … OutputNotEscaped` pattern. Style Tab controls themselves add no frontend markup beyond the editor-preview rework.
- **Per-section control structure**: `start_controls_section` / `add_control` / `add_group_control` / `end_controls_section` with `condition` arrays — extend with new `TAB_STYLE` sections.

### Integration Points
- `DrillDownMenu::_register_controls()` — add 6 Style Tab sections (Trigger / Drawer / Header / Panel+Back Row / Menu Items w. Normal-Hover-Active tabs / Search Box).
- `assets/css/ddmm-frontend.css` — refine baseline defaults (D-01); remove `.ddmm-editor-preview` hardcoded colors (D-07); add any new `--ddmm-*` hooks for properties not yet covered.
- `DrawerRenderer::render_editor_preview()` — rework into the full representative preview (D-07).

</code_context>

<specifics>
## Specific Ideas

- The Style Tab should feel "complete" the way first-class Elementor widgets do — every visible surface styleable, sensible defaults so it looks good immediately, and strict editor≡published parity so what you configure is exactly what ships.
- Active state deliberately mirrors WordPress `current-menu-item` / `current-menu-ancestor` (item + ancestor trail) rather than inventing a novel highlight model — familiar to anyone who has themed a WP menu.
- The editor preview is the parity vehicle for SC#5 (not a true `content_template()` live preview, which is deferred to v2) — so it must use the real BEM classes and real `--ddmm-*` vars, not painted-on placeholders.
- Polish-the-baseline (D-01) was chosen so the widget "sells itself" on first drop-in rather than presenting a blank unstyled box that demands configuration before it looks acceptable.

</specifics>

<deferred>
## Deferred Ideas

- **Opened-parent Active state** (highlight a parent while its child panel is open, beyond the ancestor trail) — not in v1; revisit if users want stronger "you are here" cues.
- **`content_template()` true live editor preview** (PRES-01) — v2. Phase 6 uses the reworked static `.ddmm-editor-preview` for parity instead.
- **RTL Style Tab variants** (RTL-01) — v2.
- **Global accent-color system / theme presets** — Claude may introduce a single accent color at its discretion, but a full preset/theme system is out of scope.

No scope-creep ideas were raised — discussion stayed within the Style Tab boundary.

</deferred>

---

*Phase: 06-style-tab-controls*
*Context gathered: 2026-06-14*
