# Feature Landscape

**Domain:** WordPress Elementor mobile menu plugin (drill-down / off-canvas drawer)
**Researched:** 2026-06-12

---

## Table Stakes

Features users expect from any mobile menu plugin. Missing = product feels incomplete or broken.

### Menu Source

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| WordPress Menu (wp_nav_menu) selection | Every competing plugin supports this. Users manage menus in WP Admin > Appearance > Menus and expect to point the widget at one. | Low | Dropdown of all registered nav menus. PROJECT.md already lists this. |
| Custom Menu Builder (repeater) | Not all users want to use WP menus -- some want to build a quick inline menu directly in Elementor. The Plus Addons, ShiftNav, and WP Mobile Menu all offer this. | High | PROJECT.md specifies a flat repeater with Depth field for visual hierarchy. This is the harder path but better UX than nested repeaters. |
| Menu depth: at least 3 levels | WP Mobile Menu free supports 3 levels. ShiftNav supports unlimited. Elementor's own Nav Menu widget handles arbitrary depth. Anything less than 3 feels broken. | Medium | PROJECT.md specifies unlimited nesting, which is a differentiator. |

### Trigger / Toggle

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Hamburger icon trigger | Universal convention. Every single competitor uses a hamburger/toggle button. | Low | PROJECT.md: Hamburger Lines type. |
| Custom icon support | The Plus Addons, ShiftNav, WP Mobile Menu all allow custom trigger icons (SVG, icon library). | Low | PROJECT.md: Custom Icon type. |
| Trigger responsive visibility | Menu should only show on mobile/tablet. Elementor's Nav Menu widget has breakpoint control (Mobile, Tablet, None). | Low | Use Elementor's built-in responsive visibility, not custom logic. |
| Toggle alignment (left/center/right) | Elementor's own Nav Menu widget offers toggle alignment. Users expect to position the hamburger. | Low | |

### Drawer / Off-Canvas

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Slide-in drawer from left (or right) | The defining feature of the off-canvas pattern. Every competitor does this. ShiftNav, WP Mobile Menu, The Plus Addons, Offcanvas Menu plugin. | Medium | PROJECT.md: slides in from left. Configurable side is a bonus. |
| Overlay / backdrop when drawer is open | Universal pattern. ShiftNav, WP Mobile Menu, The Plus Addons all use overlay masks. | Low | Dark semi-transparent overlay. Click-to-close is standard. |
| Close (X) button in drawer | Every competitor has this. Users expect an explicit close control. | Low | |
| Close on overlay click | ShiftNav has this as a configurable "touch-off close" setting. WP Mobile Menu has it. Standard expectation. | Low | PROJECT.md: configurable toggle. |
| Close on Escape key | W3C mobile menu accessibility guide requires Escape to close. ShiftNav added this as an accessibility feature in v1.7. | Low | Also required for WCAG compliance. |
| Back button for submenu navigation | The core UX of drill-down navigation. Without it, users cannot return to parent levels. | Low | PROJECT.md: Back button at top of each submenu panel. |

### Animation

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Smooth slide transition | CSS3 transitions are standard. ShiftNav uses CSS3 transforms. The Plus Addons uses CSS transitions. No competitor uses janky JS animation. | Low | Use CSS transform/translate, not JS-driven animation. |
| Configurable transition speed | ShiftNav, WP Mobile Menu, The Plus Addons all allow speed configuration. | Low | PROJECT.md: 100ms-2000ms range. |
| Transition easing | Basic expectation. Linear feels wrong; ease-out is the default for slide panels. | Low | |

### Elementor Integration

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Elementor Content Tab with menu source, trigger options | Standard Elementor widget convention. Content Tab = what the widget shows. | Low | |
| Elementor Style Tab with full styling controls | Every Elementor widget has a Style Tab. Users expect to control typography, colors, spacing, backgrounds for trigger, drawer, items, etc. | Medium | PROJECT.md: Normal/Hover/Active states for menu items. |
| Conditional asset loading | Only enqueue CSS/JS when the widget is on the page. Elementor handles this natively if scripts are registered in the widget constructor. | Low | PROJECT.md: assets only enqueued when widget is present. |
| Elementor editor live preview | Users expect to see the widget in the Elementor editor. The `_content_template()` method handles this. | Medium | Not in PROJECT.md but is standard Elementor practice. |
| Admin notice when Elementor is inactive | Standard for Elementor addon plugins. Prevents fatal errors. | Low | PROJECT.md: plugin admin notice. |

### Accessibility

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Keyboard navigation (Tab, Enter, Escape) | WCAG requirement. WordPress core requires WCAG 2.0 AA since 2016. ShiftNav had a major accessibility upgrade in v1.7. | Medium | PROJECT.md: Escape, Tab trap, Arrow keys, Enter/Space. |
| ARIA attributes (aria-expanded, aria-controls) | W3C mobile menu guide explicitly requires `aria-expanded` on toggle, `aria-controls` linking toggle to panel, and `<nav aria-label>`. | Medium | W3C guide at https://w3c.github.io/wai-mobile-intro/mobile/mobile-menus/ |
| Focus trap when drawer is open | Prevents keyboard users from tabbing into page content behind the drawer. ShiftNav added "close panel when focus leaves" in v1.7. | Medium | |
| Focus return to trigger on close | W3C accessibility pattern: focus should return to the button that opened the menu when it closes. | Low | |
| Toggle button is a `<button>` element | W3C requires this. Using `<div>` or `<span>` for the hamburger is an accessibility violation. | Low | |
| Screen reader text for icon-only triggers | Icons alone do not convey purpose to screen readers. Must have accessible name. | Low | |

### Reliability

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| No jQuery dependency | WordPress is deprecating jQuery usage. Modern plugins use vanilla JS. This is a performance expectation now. | Medium | PROJECT.md: Pure ES6, no jQuery. |
| Translation-ready (text domain) | WordPress plugin repository requirement. All competitors support this. | Low | PROJECT.md: text domain `devsroom-drilldown-mobile-menu`. |
| Works with both Elementor Free and Pro | Elementor Pro costs money. Locking to Pro limits audience. ShiftNav and WP Mobile Menu are theme-agnostic. | Low | PROJECT.md: Free or Pro. |

---

## Differentiators

Features that set this plugin apart from competitors. Not expected by default, but valued when present.

### Drill-Down Panel Navigation (Core Differentiator)

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Panel-replacing drill-down (not accordion, not cascade) | Most competitors use accordion (expand inline) or cascade (dropdown columns). The drill-down pattern where tapping a parent item slides the current panel out and slides the child panel in is the primary differentiator. The Plus Addons offers some slide-panel modes but not true drill-down. ShiftNav has accordion mode but not panel-replacing drill-down. | High | This IS the product. PROJECT.md defines it precisely: "parent items slide the current panel left and reveal the child panel from the right." |
| Unlimited nesting depth | WP Mobile Menu free caps at 3 levels. Even Pro caps at 5. ShiftNav supports arbitrary depth but uses accordion, not panels. Unlimited drill-down panels is rare. | Medium | PROJECT.md: unlimited nesting levels. |
| ID-based panel navigation (data-target/data-panel-id) | This is an architectural differentiator that guarantees reliability. Most competitors use positional DOM traversal which breaks at depth. | Medium | PROJECT.md: direct ID lookup, not positional heuristics. |

### Custom Menu Builder UX

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Flat repeater with Depth field (not nested repeaters) | Elementor's nested repeater UI is notoriously bad. A flat list with a Depth field and visual indent dashes is much cleaner. No competitor does this specifically for Elementor. | Medium | PROJECT.md: "flat list in Elementor, nested panels in rendered output." |
| Visual indent dashes (-- Child, ---- Grandchild) | Makes the flat repeater usable. Without visual hierarchy, users lose track of nesting. | Low | PROJECT.md specifies this. |

### Animation Types

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Multiple animation types (Slide, Fade, Scale, Slide+Fade) | Most competitors offer one animation type (slide). Offering 4 gives design flexibility. WP Mobile Menu offers "slide over" and "slide push" but not fade/scale. | Medium | PROJECT.md: 4 animation types. |
| Configurable easing curves | Beyond basic ease. Power users and designers care about animation feel. | Low | |

### Drawer Features

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Drawer header with configurable content (logo, custom image, custom text, or none) | The Plus Addons and WP Mobile Menu have branding areas. Making it configurable (logo vs text vs image vs none) is more flexible than competitors. | Medium | PROJECT.md: configurable header content. |
| Optional search box in drawer | WP Mobile Menu Pro has this as a premium feature. ShiftNav Pro has it. Offering it as a toggle in the free plugin is a differentiator. | Medium | PROJECT.md: optional search box with configurable placeholder. |
| Auto-open current page path | When the menu opens, automatically expand the drill-down path to the current page. ShiftNav Pro has "scrollTo" features. This is a nice UX touch. | Medium | PROJECT.md: configurable toggle. |
| Close menu after link click | Small UX detail that competitors handle inconsistently. Making it configurable is smart. | Low | PROJECT.md: configurable toggle. |

### Technical Differentiators

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| OOP PHP with PSR-4 autoloading | Most competitors (WP Mobile Menu, ShiftNav) use older procedural patterns. Modern PHP architecture means better maintainability. | Medium | PROJECT.md: namespace `Devsroom_DDMM\`, PSR-4. |
| Dual JS init path (elementor/frontend/init + DOMContentLoaded) | Handles both Elementor editor and published mode. Solves the common issue where `elementorFrontend.hooks` is not ready on load. | Low | PROJECT.md: dual-path init. |
| Double-init guard (data-ddmm-init attribute) | Prevents the widget from initializing twice (a common Elementor issue with dynamic loading). | Low | PROJECT.md: data-ddmm-init guard. |
| 3-pass ID-based WP menu tree builder | Eliminates the PHP reference corruption bug that plagues naive `foreach &$ref` implementations. | Low | PROJECT.md: no PHP references. |
| WooCommerce menu item compatibility | WP Mobile Menu charges for WooCommerce features. Including this in the base plugin is competitive. | Low | PROJECT.md: Cart, My Account, Checkout, Shop compatible. |

---

## Anti-Features

Features to explicitly NOT build. These are scope-destroyers or misaligned with the product's core value.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| Mega menu (multi-column desktop dropdowns) | Completely different UX pattern. Requires hover detection, column layout, positioning logic. Would double the codebase. This is a MOBILE-ONLY drill-down. | Stay focused on mobile drill-down. Let Elementor Pro's Nav Menu widget or dedicated mega menu plugins handle desktop. |
| Accordion menu (submenus expanding inline) | The entire product identity is panel-replacing drill-down. Accordion is a fundamentally different interaction pattern that conflicts with the slide-panel animation system. | The drill-down panels already handle depth. Accordion is explicitly out of scope in PROJECT.md. |
| Desktop menu replacement | The plugin is designed for mobile navigation. Desktop menus have different requirements (horizontal layout, hover dropdowns, multi-column). | Use Elementor's responsive visibility to show the widget only on mobile breakpoints. |
| Theme modification / global menu replacement | Modifying theme templates is invasive. It breaks with theme updates and causes support burden. ShiftNav and WP Mobile Menu both struggle with theme compatibility issues. | Be purely additive -- an Elementor widget that users place where they want. No theme hooks, no global overrides. |
| Third-party page builder support (Beaver Builder, Divi, Gutenberg) | Each builder has its own widget API, rendering model, and editor integration. Supporting multiple builders multiplies maintenance. | Elementor-only. Clean scope. |
| Built-in icon library / font loading | Loading icon fonts adds weight. WP Mobile Menu had performance issues with icon font sizes (they had to "massively reduce file size of icon fonts" in a later update). | Use SVG icons or the icon library already available in Elementor. Do not bundle Font Awesome or similar. |
| Popup builder / content drawer for non-menu items | The Plus Addons and Unlimited Elements use their off-canvas widgets for forms, CTAs, and arbitrary content. This scope creep would destroy focus. | The drawer is for navigation only. Let Elementor's own Off-Canvas widget handle arbitrary content. |
| Analytics / event tracking | The Plus Addons bundles GA4 and Facebook Pixel event tracking. This belongs in dedicated analytics plugins, not a menu widget. | Keep the plugin focused. Users can add tracking via GTM or dedicated plugins. |
| Import/export settings | WP Mobile Menu added this. It adds complexity for a niche use case. Elementor already handles template import/export. | Rely on Elementor's template system for portability. |
| Role-based menu visibility | The Menu plugin offers this. It is a nice feature but adds query complexity and user management coupling. | Defer. Can be added later as a premium feature if needed. |

---

## Feature Dependencies

```
WordPress Menu Source OR Custom Menu Builder (pick one to start; both in MVP)
  |
  +---> Menu tree builder (3-pass for WP, stack-based for Custom)
         |
         +---> Panel renderer (creates DOM panels from tree)
                |
                +---> Drill-down JS controller (handles panel transitions)
                |       |
                |       +---> Animation system (slide/fade/scale)
                |       |
                |       +---> Back button navigation
                |       |
                |       +---> Keyboard navigation (Escape, Tab trap, Arrow keys)
                |       |
                |       +---> Auto-open current page path
                |
                +---> Trigger button (hamburger/custom icon/text)
                |       |
                |       +---> Drawer open/close toggle
                |
                +---> Drawer (off-canvas panel)
                        |
                        +---> Overlay backdrop
                        +---> Close (X) button
                        +---> Header (logo/text/image/none)
                        +---> Optional search box
                        +---> Close on overlay click
                        +---> Close after link click

Elementor Widget Registration
  |
  +---> Content Tab controls (menu source, trigger, animation, drawer options)
  +---> Style Tab controls (trigger, drawer, header, panels, items, search)
  +---> render() method (PHP output)
  +---> _content_template() method (editor preview)
  +---> Asset registration (conditional CSS/JS loading)

Accessibility layer (cross-cutting)
  |
  +---> ARIA attributes on trigger, panels, back buttons
  +---> Focus management (trap, return)
  +---> Screen reader text
  +---> Semantic HTML (<nav>, <button>, <ul>, <li>)
```

### Critical dependency chain for MVP:
1. Menu source (WP Menu is simpler; start there)
2. Panel renderer (builds DOM from menu tree)
3. Drill-down JS controller (panel transitions)
4. Drawer + Trigger (off-canvas wrapper)
5. Elementor widget controls (expose settings)
6. Style Tab (styling)
7. Accessibility layer (ARIA + keyboard)

---

## MVP Recommendation

### Phase 1 -- Core Drill-Down (Ship This First)
1. WP Menu source (dropdown of registered menus)
2. Slide-in drawer from left with overlay
3. Drill-down panel navigation (parent slides left, child slides in from right)
4. Back button at top of each submenu
5. Close (X) button in drawer header
6. Hamburger trigger button
7. Basic animation (Slide) with configurable duration/easing
8. Elementor Content Tab: menu selection, trigger type, basic drawer settings
9. Elementor Style Tab: trigger colors, drawer background, item typography/colors
10. Close on overlay click
11. Escape key to close

### Phase 2 -- Polish and Completion
3. Custom Menu Builder (flat repeater with Depth field)
4. Multiple animation types (Fade, Scale, Slide+Fade)
5. Drawer header options (logo, custom image, custom text, none)
6. Optional search box
7. Full keyboard accessibility (Tab trap, Arrow keys, Enter/Space)
8. Full ARIA attributes
9. Auto-open current page path
10. Close after link click toggle
11. Trigger types: Custom Icon, Text Only, Icon + Text

### Phase 3 -- Refinement
12. WooCommerce menu item compatibility testing
13. Translation-ready validation
14. Editor live preview polish
15. Performance optimization
16. Edge case handling (empty menus, single-level menus, very deep nesting)

### Defer
- Role-based visibility: niche use case, adds complexity
- Swipe gesture support: nice-to-have but not table stakes for Elementor widget
- RTL support: important for global distribution but can be added post-launch
- Custom CSS per menu item: power-user feature, defer

---

## Sources

- **The Plus Addons Mobile Menu Widget:** https://theplusaddons.com/elementor-builder/header-builder/mobile-menu/ -- Feature list, layout modes, demo types
- **ShiftNav WordPress Plugin (WordPress.org):** https://wordpress.org/plugins/shiftnav-responsive-mobile-menu/ -- Full changelog with accessibility upgrades, features, and community feedback
- **WP Mobile Menu (WordPress.org):** https://wordpress.org/plugins/mobile-menu/ -- Feature list, free vs premium tiers, user reviews
- **Offcanvas Mobile Menu (WordPress.org):** https://wordpress.org/plugins/offcanvas-menu/ -- Simple off-canvas implementation
- **Elementor Nav Menu Widget (Pro) Docs:** https://elementor.com/help/nav-menu-widget-pro/ -- Native Elementor menu widget capabilities and settings
- **Elementor Nav Menu Blog Post:** https://elementor.com/blog/introducing-nav-menu/ -- Elementor's own positioning on menu building
- **W3C Mobile Menu Accessibility Guide:** https://w3c.github.io/wai-mobile-intro/mobile/mobile-menus/ -- Official W3C guidance on mobile menu accessibility (aria-expanded, aria-controls, nav landmarks)
- **WordPress Menu Accessibility Comparison:** https://peakperformancedigital.com/accessible-menu-testing-part-1/ -- Practical accessibility testing of 10 WordPress menus
- **Level Access Navigation Best Practices:** https://www.levelaccess.com/blog/accessible-navigation-menus-pitfalls-and-best-practices/ -- Professional accessibility guidelines
- **Elementor Developer Docs (Widget JS):** https://developers.elementor.com/building-a-simple-custom-widget-with-javascript/ -- Best practices for JS in custom Elementor widgets
- **Elementor Developer Docs (Widget Styles):** https://developers.elementor.com/docs/scripts-styles/widget-styles/ -- CSS enqueuing handled by Elementor natively
- **Smashing Magazine Mobile Navigation:** https://www.smashingmagazine.com/2022/11/navigation-design-mobile-ux/ -- Mobile navigation UX patterns
- **NN/g Menu Design Checklist:** https://www.nngroup.com/articles/menu-design/ -- UX guidelines for menu design
- **Baymard Mobile Navigation Benchmark:** https://baymard.com/mcommerce-usability/benchmark/mobile-page-types/navigation-menu -- 896 mobile navigation examples with UX insights
- **Reddit r/Elementor Drill-Down Discussion:** https://www.reddit.com/r/elementor/comments/1ffqaq5/drill_down_mobile_menu/ -- Community demand for drill-down menus in Elementor

---

## Confidence Assessment

| Area | Confidence | Reason |
|------|------------|--------|
| Table stakes features | HIGH | Verified across 5+ competitor plugins, official Elementor docs, and W3C standards |
| Differentiators | HIGH | PROJECT.md already defines them clearly; competitive gap confirmed by research |
| Anti-features | HIGH | Aligned with PROJECT.md Out of Scope section and confirmed by competitor analysis |
| Accessibility requirements | HIGH | W3C official guide + WordPress accessibility policy + ShiftNav changelog all agree |
| Feature dependencies | MEDIUM | Based on PROJECT.md architecture decisions and standard Elementor widget patterns |
| MVP phasing | MEDIUM | Opinionated recommendation based on research; real-world validation needed |

---

*Last updated: 2026-06-12*
