# Requirements: Devsroom DrillDown Mobile Menu

**Defined:** 2026-06-12
**Core Value:** The drill-down panel navigation must work flawlessly at any depth — parent items slide the current panel left and reveal the child panel from the right, with a ← Back button to reverse. Direct `data-target` → `data-panel-id` ID lookup for navigation reliability.

## v1 Requirements

### Plugin Foundation

- [ ] **PLUG-01**: Plugin bootstraps via main PHP file with PSR-4 autoloader (`spl_autoload_register`) mapping `Devsroom_DDMM\` namespace to class files
- [ ] **PLUG-02**: Plugin singleton initializes on `plugins_loaded` after confirming Elementor is active
- [ ] **PLUG-03**: Admin notice displays when Elementor is not active, with link to install/activate
- [ ] **PLUG-04**: Plugin header declares correct identity: name, version 0.0.01, author MEHEDI HASSAN SHUBHO, text domain `devsroom-drilldown-mobile-menu`
- [ ] **PLUG-05**: Assets only enqueued when widget is present on page (conditional loading via `get_script_depends()` / `get_style_depends()`)
- [x] **PLUG-06**: All output is escaped (`esc_attr`, `esc_url`, `esc_html`) — no unescaped echoes

### Elementor Widget Registration

- [ ] **WIDG-01**: Widget registers on `elementor/widgets/register` hook (not deprecated `widgets_registered`)
- [ ] **WIDG-02**: Widget class extends `\Elementor\Widget_Base` with correct name, title, icon, and categories
- [ ] **WIDG-03**: Widget icon is a custom SVG or dashicon representing a mobile menu
- [ ] **WIDG-04**: Script and style dependencies declared via `get_script_depends()` and `get_style_depends()`

### Menu Sources — WordPress Menu

- [ ] **WMEN-01**: Content Tab provides "WordPress Menu" dropdown listing all registered `wp_nav_menus()`
- [ ] **WMEN-02**: Selected WP menu is converted to nested tree using 3-pass ID-based builder (index → attach → resolve) — no PHP references
- [ ] **WMEN-03**: Menu items without children render as plain `<a>` links with their URL
- [ ] **WMEN-04**: Menu items with children render as parent spans with `data-target="PANEL_ID"` and right-arrow icon
- [ ] **WMEN-05**: WooCommerce menu items (Cart, My Account, Checkout, Shop) render correctly with proper URLs

### Menu Sources — Custom Menu Builder

- [ ] **CMEN-01**: Content Tab provides "Custom Menu Builder" repeater with fields: Label, URL, Depth, Icon, Open in New Tab
- [ ] **CMEN-02**: Depth field drives nesting — flat list in Elementor, nested panels in rendered output
- [ ] **CMEN-03**: Title field in repeater shows indent dashes for visual hierarchy (— Child, —— Grandchild)
- [ ] **CMEN-04**: Custom menu data converted to nested tree using stack-based depth-field algorithm
- [ ] **CMEN-05**: Icon field uses Elementor Icons control (Font Awesome, SVG)

### Trigger Button

- [x] **TRIG-01**: Trigger renders as a `<button>` element (not `<div>`) for accessibility
- [x] **TRIG-02**: Hamburger Lines type renders animated three-line icon
- [x] **TRIG-03**: Custom Icon type uses Elementor Icons picker
- [x] **TRIG-04**: Text Only type renders configurable text string
- [x] **TRIG-05**: Icon + Text type renders both with configurable position (Before Text | After Text)
- [x] **TRIG-06**: Trigger has `aria-expanded="false"` toggled to `true` when drawer opens

### Drawer & Panel Navigation

- [ ] **DRAW-01**: Drawer slides in from the left as an off-canvas panel when trigger is clicked
- [ ] **DRAW-02**: Semi-transparent overlay covers page content behind drawer
- [ ] **DRAW-03**: Drawer header displays configurable brand: Site Logo (WP Custom Logo), Custom Image, Custom Text, or None
- [ ] **DRAW-04**: Close (✕) button in drawer header dismisses the menu
- [ ] **DRAW-05**: Root panel shows all top-level menu items; parent items display right-arrow (›)
- [ ] **DRAW-06**: Tapping a parent item slides current panel left and brings child panel in from right
- [ ] **DRAW-07**: Each submenu panel has ← Back button at top that slides back to previous panel
- [ ] **DRAW-08**: Back button row shows parent item name as title (configurable via toggle)
- [ ] **DRAW-09**: Drill-down works for unlimited nesting levels (root → level 1 → level 2 → level 3…)
- [ ] **DRAW-10**: Navigation uses direct `data-target` → `data-panel-id` ID lookup — no positional heuristics
- [ ] **DRAW-11**: Unique panel IDs generated with `uniqid()` at render time

### Animation

- [ ] **ANIM-01**: Content Tab provides Transition Type selector: Slide | Fade | Scale | Slide + Fade
- [ ] **ANIM-02**: Duration slider: 100ms–2000ms with default 300ms
- [ ] **ANIM-03**: Easing selector: ease | ease-in | ease-out | ease-in-out | linear
- [ ] **ANIM-04**: All animations use CSS `transform: translateX()` and `opacity` only — GPU-composited, no layout thrashing

### Extra Features

- [ ] **EXTR-01**: Optional search box in drawer with configurable placeholder text
- [ ] **EXTR-02**: Search filters menu items across all panels
- [ ] **EXTR-03**: Auto-open current page path — menu drills down to highlight current page item
- [ ] **EXTR-04**: Close menu after link click (configurable toggle)
- [ ] **EXTR-05**: Close on overlay click (configurable toggle)

### Style Tab

- [ ] **STYL-01**: Trigger Button controls: color, background, hamburger size, padding, border, border-radius, typography
- [ ] **STYL-02**: Drawer controls: width (px/vw/%), background, box-shadow, overlay color
- [ ] **STYL-03**: Header controls: background, border color, height, title typography, title color, close button color
- [ ] **STYL-04**: Panel & Back Row controls: back row color, back row background (normal + hover), panel title typography + color, divider color
- [ ] **STYL-05**: Menu Items controls: min-height, padding, Normal/Hover/Active tabs (text color, background, arrow color), typography
- [ ] **STYL-06**: Search Box controls: background, text color, border, border-radius

### Accessibility

- [ ] **A11Y-01**: Drawer uses `<nav aria-label>` (never `role="menu"`)
- [ ] **A11Y-02**: Parent items use `<button>` or `<span role="button" tabindex="0">` with `aria-expanded`
- [ ] **A11Y-03**: Trigger button has `aria-expanded` and `aria-controls` pointing to drawer ID
- [ ] **A11Y-04**: Keyboard: Escape closes drawer or goes back one level
- [ ] **A11Y-05**: Keyboard: Tab trap keeps focus inside open drawer
- [ ] **A11Y-06**: Keyboard: Arrow keys navigate between menu items
- [ ] **A11Y-07**: Keyboard: Enter/Space activates parent items (drill in) and back button (go back)
- [ ] **A11Y-08**: Focus moves to drawer when opened, restored to trigger when closed

### JavaScript

- [ ] **JSCR-01**: Pure ES6 JavaScript, zero jQuery dependency
- [ ] **JSCR-02**: IIFE-wrapped — no global namespace pollution
- [ ] **JSCR-03**: Dual-path init: `elementor/frontend/init` event for Elementor pages + `DOMContentLoaded` for published mode
- [ ] **JSCR-04**: Double-init guard via `data-ddmm-init` attribute on container
- [ ] **JSCR-05**: PHP settings (transition type, duration, easing, feature toggles) passed to JS via `wp_localize_script()`

### Compatibility & i18n

- [ ] **COMP-01**: Compatible with WordPress 6.5+, PHP 8.1/8.2/8.3
- [ ] **COMP-02**: Compatible with Elementor Free and Pro
- [ ] **COMP-03**: WooCommerce menu items render correctly (Cart, My Account, Checkout, Shop)
- [ ] **COMP-04**: Translation-ready with text domain `devsroom-drilldown-mobile-menu` and `.pot` file
- [x] **COMP-05**: Compatible with any WordPress theme — no theme-specific CSS overrides

## v2 Requirements

### Future Enhancements

- **RTL-01**: Full RTL layout support for Arabic, Hebrew, and other RTL languages
- **GEST-01**: Swipe gesture support (swipe left to drill in, swipe right to go back)
- **MULTI-01**: Multiple widget instances on same page with independent state
- **PRES-01**: Elementor editor live preview via `content_template()` method
- **MINF-01**: Minified CSS/JS production files shipped alongside source
- **DEEP-01**: Elementor dynamic tags support in Custom Menu Builder URLs

## Out of Scope

| Feature | Reason |
|---------|--------|
| Mega menu (desktop dropdown columns) | Fundamentally different UX pattern — mobile-only drill-down is the product |
| Accordion menu (inline expand) | Panels replace, not expand — this is a different interaction model |
| Desktop menu replacement | Designed specifically for mobile navigation breakpoints |
| Third-party menu plugin dependency | Self-contained Elementor widget — no CFM, Max Mega Menu, etc. |
| Theme modification | Purely additive plugin — does not touch theme files |
| WordPress admin menu page | Uses existing WP menus or built-in custom builder |
| Page builder other than Elementor | Elementor-only widget — not Beaver, Brizy, Divi, etc. |
| REST API / AJAX menu loading | Menu data rendered server-side in PHP for simplicity and SEO |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| PLUG-01 | Phase 1 | Pending |
| PLUG-02 | Phase 1 | Pending |
| PLUG-03 | Phase 1 | Pending |
| PLUG-04 | Phase 1 | Pending |
| PLUG-05 | Phase 1 | Pending |
| PLUG-06 | Phase 1 | Complete |
| WIDG-01 | Phase 1 | Pending |
| WIDG-02 | Phase 1 | Pending |
| WIDG-03 | Phase 1 | Pending |
| WIDG-04 | Phase 1 | Pending |
| TRIG-01 | Phase 1 | Complete |
| TRIG-02 | Phase 1 | Complete |
| TRIG-03 | Phase 1 | Complete |
| TRIG-04 | Phase 1 | Complete |
| TRIG-05 | Phase 1 | Complete |
| TRIG-06 | Phase 1 | Complete |
| COMP-01 | Phase 1 | Pending |
| COMP-02 | Phase 1 | Pending |
| COMP-05 | Phase 1 | Complete |
| WMEN-01 | Phase 2 | Pending |
| WMEN-02 | Phase 2 | Pending |
| WMEN-03 | Phase 2 | Pending |
| WMEN-04 | Phase 2 | Pending |
| WMEN-05 | Phase 2 | Pending |
| CMEN-01 | Phase 3 | Pending |
| CMEN-02 | Phase 3 | Pending |
| CMEN-03 | Phase 3 | Pending |
| CMEN-04 | Phase 3 | Pending |
| CMEN-05 | Phase 3 | Pending |
| DRAW-01 | Phase 4 | Pending |
| DRAW-02 | Phase 4 | Pending |
| DRAW-03 | Phase 4 | Pending |
| DRAW-04 | Phase 4 | Pending |
| DRAW-05 | Phase 4 | Pending |
| DRAW-06 | Phase 4 | Pending |
| DRAW-07 | Phase 4 | Pending |
| DRAW-08 | Phase 4 | Pending |
| DRAW-09 | Phase 4 | Pending |
| DRAW-10 | Phase 4 | Pending |
| DRAW-11 | Phase 4 | Pending |
| A11Y-01 | Phase 4 | Pending |
| A11Y-02 | Phase 4 | Pending |
| A11Y-03 | Phase 4 | Pending |
| JSCR-01 | Phase 4 | Pending |
| JSCR-02 | Phase 4 | Pending |
| JSCR-03 | Phase 4 | Pending |
| JSCR-04 | Phase 4 | Pending |
| JSCR-05 | Phase 4 | Pending |
| ANIM-01 | Phase 5 | Pending |
| ANIM-02 | Phase 5 | Pending |
| ANIM-03 | Phase 5 | Pending |
| ANIM-04 | Phase 5 | Pending |
| EXTR-01 | Phase 5 | Pending |
| EXTR-02 | Phase 5 | Pending |
| EXTR-03 | Phase 5 | Pending |
| EXTR-04 | Phase 5 | Pending |
| EXTR-05 | Phase 5 | Pending |
| STYL-01 | Phase 6 | Pending |
| STYL-02 | Phase 6 | Pending |
| STYL-03 | Phase 6 | Pending |
| STYL-04 | Phase 6 | Pending |
| STYL-05 | Phase 6 | Pending |
| STYL-06 | Phase 6 | Pending |
| A11Y-04 | Phase 7 | Pending |
| A11Y-05 | Phase 7 | Pending |
| A11Y-06 | Phase 7 | Pending |
| A11Y-07 | Phase 7 | Pending |
| A11Y-08 | Phase 7 | Pending |
| COMP-03 | Phase 7 | Pending |
| COMP-04 | Phase 7 | Pending |

**Coverage:**
- v1 requirements: 70 total
- Mapped to phases: 70
- Unmapped: 0

---
*Requirements defined: 2026-06-12*
*Last updated: 2026-06-12 after roadmap creation*
