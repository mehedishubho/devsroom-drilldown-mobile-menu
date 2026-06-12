# Phase 1: Plugin Foundation & Widget Shell - Context

**Gathered:** 2026-06-12
**Status:** Ready for planning

<domain>
## Phase Boundary

The plugin installs, activates, registers as an Elementor widget, and renders a configurable trigger button with conditional asset loading. This is the foundation — no menu data, no drawer, no drill-down JS. Only the plugin bootstrap, Elementor integration, trigger button rendering, and asset pipeline.

**In scope:** Plugin entry point, PSR-4 autoloader, Elementor dependency check + admin notice, widget registration, Content Tab controls for trigger type selection, trigger button HTML rendering, CSS/JS asset registration, conditional loading.

**Out of scope:** Menu data sources (Phase 2/3), drawer HTML (Phase 4), drill-down JS (Phase 5), full Style Tab (Phase 6), accessibility beyond basic ARIA on trigger (Phase 7).
</domain>

<decisions>
## Implementation Decisions

### Widget Identity
- **D-01:** Widget internal name (`get_name()`) is `ddmm-drilldown-menu`
- **D-02:** Widget appears in a custom Elementor category named "Devsroom" (registered via `elementor/elements/categories_registered`)
- **D-03:** Widget icon is a custom inline SVG hamburger (three lines) — no dashicon, no eicon dependency

### Plugin Bootstrap
- **D-04:** PSR-4 autoloader via `spl_autoload_register()` maps `Devsroom_DDMM\` → `src/` directory (no Composer)
- **D-05:** Plugin class uses singleton pattern, initializes on `plugins_loaded` hook
- **D-06:** Admin notice displays when Elementor is not active, with link to install/activate — simple styled notice, no custom branding
- **D-07:** Plugin header declares: name, version 0.0.01, author MEHEDI HASSAN SHUBHO, text domain `devsroom-drilldown-mobile-menu`

### Trigger Button
- **D-08:** Trigger renders as `<button>` element with `aria-expanded="false"` and `aria-controls` pointing to drawer ID
- **D-09:** Four trigger types: Hamburger Lines, Custom Icon (Elementor Icons picker), Text Only, Icon + Text
- **D-10:** Hamburger Lines type uses CSS spans (3-span trick) — three `<span>` elements inside the button, styled as horizontal lines via CSS. This enables Phase 5 hamburger-to-X animation via class toggle.
- **D-11:** Icon + Text type supports configurable position (icon Before Text or After Text)

### Base Styling
- **D-12:** Trigger button ships with reasonable default CSS: dark lines on transparent background, ~32px hamburger size, padding, cursor pointer. Not bare/unstyled — looks decent out of the box. Phase 6 Style Tab overrides everything.

### Asset Loading
- **D-13:** Assets registered via `wp_register_script()` / `wp_register_style()` on `wp_enqueue_scripts` (register only, not enqueue)
- **D-14:** Widget declares handles via `get_script_depends()` / `get_style_depends()` — Elementor enqueues conditionally
- **D-15:** JS file (`ddmm-frontend.js`) ships as empty IIFE shell in Phase 1 — populated in Phase 5
- **D-16:** CSS file (`ddmm-frontend.css`) contains trigger button base styles and CSS custom properties skeleton (`--ddmm-*`)

### Claude's Discretion
- Exact admin notice copy and styling details
- CSS custom property naming conventions (`--ddmm-*` prefix specifics)
- Default trigger button pixel values (padding, line thickness, gap)
- Widget description text shown in Elementor panel
- Inline SVG markup details for the widget panel icon
</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Plugin Architecture
- `.planning/research/ARCHITECTURE.md` — Full system architecture, component responsibilities, data flows, recommended file structure, build order, and anti-patterns
- `.planning/research/FEATURES.md` — Feature landscape, table stakes, differentiators, anti-features, MVP phasing
- `.planning/research/STACK.md` — Technology stack decisions with rationale and confidence levels
- `.planning/research/PITFALLS.md` — Known issues from prior development and how to avoid them

### Requirements
- `.planning/REQUIREMENTS.md` — Full requirements list; Phase 1 covers: PLUG-01 through PLUG-06, WIDG-01 through WIDG-04, TRIG-01 through TRIG-06, COMP-01, COMP-02, COMP-05

### Project Context
- `.planning/PROJECT.md` — Vision, core value, constraints, key decisions, architecture decisions already made
- `CLAUDE.md` — Technology stack details, Elementor API usage, file structure conventions, plugin identity
</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- None — greenfield project with no source code yet

### Established Patterns
- Architecture research defines the full file structure: `devsroom-drilldown-mobile-menu.php` (entry), `src/` (PSR-4 root), `assets/css/` and `assets/js/` (frontend files)
- PSR-4 namespace mapping: `Devsroom_DDMM\` → `src/`, case-sensitive directory names
- Elementor widget pattern: extend `\Elementor\Widget_Base`, register on `elementor/widgets/register`, controls in `_register_controls()`, output in `render()`
- Conditional asset loading pattern: `wp_register_*()` + `get_*_depends()` delegates enqueue to Elementor

### Integration Points
- WordPress `plugins_loaded` hook — plugin init timing
- `did_action('elementor/loaded')` — Elementor presence check
- `elementor/elements/categories_registered` — register custom "Devsroom" category
- `elementor/widgets/register` — widget registration (modern hook, not deprecated `widgets_registered`)
- `wp_enqueue_scripts` — asset registration (not enqueue)
- Elementor Controls API — REPEATER, SELECT, SWITCHER, TEXT, URL, MEDIA controls for Content Tab
</code_context>

<specifics>
## Specific Ideas

- Inspired by Packiro.com mobile menu behavior for the overall product vision
- Hamburger CSS span pattern chosen specifically to enable smooth Phase 5 hamburger-to-X animation via CSS class toggle (no SVG animation complexity)
- Reasonable default styling chosen so the widget looks functional between Phase 1 and Phase 6 — not "broken"
</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope
</deferred>

---

*Phase: 01-plugin-foundation-widget-shell*
*Context gathered: 2026-06-12*
