# Roadmap: Devsroom DrillDown Mobile Menu

## Overview

This roadmap delivers a production-ready WordPress Elementor widget for mobile drill-down navigation. The journey starts with a functioning plugin scaffold and widget shell, builds the menu data layer (WordPress menus first, then custom builder), constructs the rendering pipeline that bridges PHP trees to browser HTML, adds the core drill-down JavaScript interaction, implements animation and extra features, layers on full Elementor Style Tab controls, and finishes with accessibility hardening and compatibility polish. Each phase delivers a coherent, verifiable capability that builds on the previous one.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Plugin Foundation & Widget Shell** - Plugin bootstrap, Elementor dependency check, widget registration, trigger button, and conditional asset loading (completed 2026-06-12)
- [ ] **Phase 2: WordPress Menu Source** - WP menu dropdown, 3-pass tree builder, and menu item rendering
- [ ] **Phase 3: Custom Menu Builder** - Repeater control with depth field, stack-based tree builder, and visual hierarchy
- [ ] **Phase 4: Rendering Pipeline & Drawer HTML** - Drawer HTML output, panel structure, ARIA markup, and PHP-to-browser DOM contract
- [ ] **Phase 5: Frontend Drill-Down JavaScript** - Drawer open/close, panel transitions, back navigation, animation system, search, and close behaviors
- [ ] **Phase 6: Style Tab Controls** - Full Elementor Style Tab for trigger, drawer, header, menu items, and search box
- [ ] **Phase 7: Accessibility & Compatibility Polish** - Keyboard navigation, focus management, WooCommerce compatibility, translation readiness, and edge cases

## Phase Details

### Phase 1: Plugin Foundation & Widget Shell
**Goal**: The plugin installs, activates, registers as an Elementor widget, and renders a configurable trigger button with conditional asset loading
**Depends on**: Nothing (first phase)
**Requirements**: PLUG-01, PLUG-02, PLUG-03, PLUG-04, PLUG-05, PLUG-06, WIDG-01, WIDG-02, WIDG-03, WIDG-04, TRIG-01, TRIG-02, TRIG-03, TRIG-04, TRIG-05, TRIG-06, COMP-01, COMP-02, COMP-05
**Success Criteria** (what must be TRUE):
  1. Plugin activates and shows an admin notice with activation link when Elementor is not active
  2. Widget appears in Elementor editor under its own category with a custom menu icon
  3. User can configure trigger button type (Hamburger, Custom Icon, Text Only, Icon + Text) and see it rendered on the page
  4. Trigger button has correct aria-expanded and aria-controls attributes for accessibility
  5. Plugin JS and CSS files are only loaded on pages where the widget is present
**Plans**: 2 plans

Plans:
- [x] 01-01-PLAN.md — Plugin bootstrap, autoloader, admin notice, widget registration, and asset loading pipeline
- [x] 01-02-PLAN.md — Content Tab trigger controls, four trigger type rendering, and base CSS styles

### Phase 2: WordPress Menu Source
**Goal**: Users can select any registered WordPress menu and the plugin converts it into a nested tree structure using the 3-pass ID-based algorithm
**Depends on**: Phase 1
**Requirements**: WMEN-01, WMEN-02, WMEN-03, WMEN-04, WMEN-05
**Success Criteria** (what must be TRUE):
  1. Content Tab shows a dropdown listing all registered WordPress navigation menus
  2. Selected WP menu is correctly converted into a nested tree structure with unlimited depth
  3. Menu items without children are represented as link nodes with their URLs
  4. Menu items with children are represented as parent nodes with data-target references and arrow indicators
  5. WooCommerce menu items (Cart, My Account, Checkout, Shop) appear with correct URLs when WooCommerce is active
**Plans**: 2 plans

Plans:
- [x] 02-01-PLAN.md — Content Tab Menu section (source toggle, WP menu dropdown) and render() empty-state hint integration
- [x] 02-02-PLAN.md — Pure-PHP 3-pass ID-based WpNavTree tree builder (src/MenuBuilder/WpNavTree.php)

### Phase 3: Custom Menu Builder
**Goal**: Users can build a complete menu from scratch using a flat repeater with a depth field that produces nested panel output
**Depends on**: Phase 1
**Requirements**: CMEN-01, CMEN-02, CMEN-03, CMEN-04, CMEN-05
**Success Criteria** (what must be TRUE):
  1. Content Tab provides a repeater control with Label, URL, Depth, Icon, and Open in New Tab fields
  2. Items appear with indent dashes in the Elementor editor showing visual hierarchy (root, -- Child, ---- Grandchild)
  3. Flat repeater data with depth values is correctly converted to a nested tree using the stack-based algorithm
  4. Icons selected via Elementor Icons picker render correctly in menu output
**Plans**: 2 plans

Plans:
- [ ] 03-01-PLAN.md — Stack-based CustomTree builder (src/MenuBuilder/CustomTree.php) with 7-field node contract
- [ ] 03-02-PLAN.md — Repeater controls in widget and render() integration with source-aware empty state

### Phase 4: Rendering Pipeline & Drawer HTML
**Goal**: The PHP rendering pipeline outputs complete drawer HTML with nested panels, data attributes for ID-based navigation, header area, and correct ARIA markup
**Depends on**: Phase 2, Phase 3
**Requirements**: DRAW-01, DRAW-02, DRAW-03, DRAW-04, DRAW-05, DRAW-06, DRAW-07, DRAW-08, DRAW-09, DRAW-10, DRAW-11, A11Y-01, A11Y-02, A11Y-03, JSCR-01, JSCR-02, JSCR-03, JSCR-04, JSCR-05
**Success Criteria** (what must be TRUE):
  1. Drawer HTML renders as an off-canvas panel with a semi-transparent overlay, triggered by the trigger button
  2. Root panel displays all top-level items; parent items show a right-arrow indicator and have data-target attributes pointing to child panel IDs
  3. Each child panel has a Back button row showing the parent item name, with data-panel-id matching the parent's data-target
  4. Drawer header displays configurable brand content (Site Logo, Custom Image, Custom Text, or None) with a close button
  5. All output uses correct semantic HTML (nav aria-label, button/span with aria-expanded, aria-controls) and all dynamic output is escaped
**Plans**: TBD

### Phase 5: Frontend Drill-Down JavaScript
**Goal**: The drill-down menu works as an interactive experience -- drawer opens/closes, panels slide in and out with configurable animations, back navigation works at any depth, and extra features (search, close behaviors, auto-open) function correctly
**Depends on**: Phase 4
**Requirements**: ANIM-01, ANIM-02, ANIM-03, ANIM-04, EXTR-01, EXTR-02, EXTR-03, EXTR-04, EXTR-05
**Success Criteria** (what must be TRUE):
  1. Clicking the trigger opens the drawer; clicking overlay or close button dismisses it
  2. Tapping a parent menu item slides the current panel left and reveals the child panel from the right; back button reverses this at any nesting depth
  3. Animation type (Slide, Fade, Scale, Slide+Fade), duration (100ms-2000ms), and easing are configurable and all use GPU-composited CSS only (transform, opacity)
  4. Optional search box filters menu items across all panels in real time
  5. Auto-open current page path drills down to and highlights the current page item; close-after-link-click and close-on-overlay-click toggles work as configured
**Plans**: TBD

### Phase 6: Style Tab Controls
**Goal**: Users can fully customize the appearance of every visual element -- trigger button, drawer, header, menu items (with state variants), and search box -- through Elementor's Style Tab
**Depends on**: Phase 5
**Requirements**: STYL-01, STYL-02, STYL-03, STYL-04, STYL-05, STYL-06
**Success Criteria** (what must be TRUE):
  1. Trigger Button section controls color, background, hamburger size, padding, border, border-radius, and typography
  2. Drawer section controls width (px/vw/%), background, box-shadow, and overlay color
  3. Header section controls background, border, height, title typography/color, and close button color
  4. Menu Items section provides Normal/Hover/Active tabs controlling text color, background, arrow color, min-height, padding, and typography
  5. All Style Tab changes render identically in Elementor editor preview and on the published page
**Plans**: TBD
**UI hint**: yes

### Phase 7: Accessibility & Compatibility Polish
**Goal**: The menu is fully keyboard-navigable, screen-reader friendly, WooCommerce-compatible, translation-ready, and handles edge cases gracefully
**Depends on**: Phase 6
**Requirements**: A11Y-04, A11Y-05, A11Y-06, A11Y-07, A11Y-08, COMP-03, COMP-04
**Success Criteria** (what must be TRUE):
  1. Escape key closes the drawer or navigates back one panel level; Tab key trap keeps focus inside the open drawer
  2. Arrow keys navigate between sibling menu items; Enter/Space activates parent items (drill in) and back buttons (go back)
  3. Focus moves to the drawer when opened and is restored to the trigger button when closed
  4. WooCommerce menu items (Cart, My Account, Checkout, Shop) render with correct URLs whether WooCommerce is active or inactive
  5. All user-facing strings use the correct text domain and a .pot file exists for translation
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 -> 2 -> 3 -> 4 -> 5 -> 6 -> 7
(Phases 2 and 3 are independent and could execute in either order, but Phase 2 is listed first as it is the simpler menu source.)

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Plugin Foundation & Widget Shell | 2/2 | Complete    | 2026-06-12 |
| 2. WordPress Menu Source | 0/2 | Not started | - |
| 3. Custom Menu Builder | 0/2 | Not started | - |
| 4. Rendering Pipeline & Drawer HTML | 0/? | Not started | - |
| 5. Frontend Drill-Down JavaScript | 0/? | Not started | - |
| 6. Style Tab Controls | 0/? | Not started | - |
| 7. Accessibility & Compatibility Polish | 0/? | Not started | - |
