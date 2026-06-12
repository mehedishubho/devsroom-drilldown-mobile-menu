# Phase 1: Plugin Foundation & Widget Shell - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-12
**Phase:** 01-plugin-foundation-widget-shell
**Areas discussed:** Widget Identity, Trigger Button Base Styling, Hamburger Icon Approach

---

## Widget Identity

### Widget Internal Name (get_name())

| Option | Description | Selected |
|--------|-------------|----------|
| ddmm-drilldown-menu | Follows WP/Elementor convention: prefix + descriptive name. Clean, predictable. | ✓ |
| ddmm-menu | Shorter internal name, developer-facing only. | |
| devsroom-drilldown-menu | Full brand name in internal ID, more verbose. | |

**User's choice:** ddmm-drilldown-menu
**Notes:** Recommended option — follows WordPress/Elementor naming conventions and produces clean JS hook names.

### Elementor Category

| Option | Description | Selected |
|--------|-------------|----------|
| Own category 'Devsroom' | Custom category via `elementor/elements/categories_registered`. Professional, brandable. | ✓ |
| Elementor 'general' category | Use built-in general category. Simpler but widget gets lost among others. | |

**User's choice:** Own category 'Devsroom'
**Notes:** Creates a branded section in the Elementor widget panel. Requires registering the category in Plugin.php init.

### Widget Icon

| Option | Description | Selected |
|--------|-------------|----------|
| Custom SVG hamburger | Inline SVG of three lines. No external dependency, crisp at any size. | ✓ |
| WordPress dashicon | Use `dashicons-menu`. Simple but generic. | |
| Elementor icon (eicon) | Use `eicon-menu-bar`. Native look but depends on Elementor icon font. | |

**User's choice:** Custom SVG hamburger
**Notes:** Inline SVG in `get_icon()` method. Self-contained, no external dependency, matches widget purpose.

---

## Trigger Button Base Styling

| Option | Description | Selected |
|--------|-------------|----------|
| Reasonable defaults | Dark lines on transparent bg, ~32px size, padding, cursor. Looks decent out of the box. Phase 6 overrides everything. | ✓ |
| Bare minimum functional | Zero visual styling, just a bare `<button>`. Developers rely entirely on Phase 6 or custom CSS. | |

**User's choice:** Reasonable defaults
**Notes:** Avoids the widget looking "broken" between Phase 1 and Phase 6. Default CSS provides a functional, presentable trigger button.

---

## Hamburger Icon Approach

| Option | Description | Selected |
|--------|-------------|----------|
| CSS spans (3-line trick) | Three `<span>` elements inside button, styled as horizontal lines. Standard pattern for animated hamburger-to-X transitions. | ✓ |
| Inline SVG | SVG with three `<line>` elements. Crisp but harder to animate X transition. | |
| Elementor Icons default | Use Elementor's icon library. Ties appearance to icon set, harder to animate. | |

**User's choice:** CSS spans (3-line trick)
**Notes:** Chosen specifically to enable smooth Phase 5 hamburger-to-X animation via CSS class toggle. Phase 5 JS toggles a class (e.g., `ddmm-active`) and CSS transforms the three spans into an X shape.

---

## Claude's Discretion

- Exact admin notice copy and styling details
- CSS custom property naming conventions (`--ddmm-*` prefix specifics)
- Default trigger button pixel values (padding, line thickness, gap)
- Widget description text shown in Elementor panel
- Inline SVG markup details for the widget panel icon

## Deferred Ideas

None — discussion stayed within phase scope.
