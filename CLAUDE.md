<!-- GSD:project-start source:PROJECT.md -->
## Project

**Devsroom DrillDown Mobile Menu**

A production-ready WordPress plugin that provides a custom Elementor widget for mobile navigation using a drill-down (push) menu system — identical in behavior to the mobile menu on Packiro.com. The menu works as a slide-in off-canvas drawer from the left. When a user taps a parent menu item, the current panel slides out and the submenu panel slides in from the right, creating a native-app-like navigation experience. Built for WordPress site builders and Elementor users who want a polished mobile menu without touching code.

**Core Value:** The drill-down panel navigation must work flawlessly at any depth — parent items slide the current panel left and reveal the child panel from the right, with a ← Back button to reverse. Direct `data-target` → `data-panel-id` ID lookup for navigation reliability.

### Constraints

- **WordPress:** Requires 6.5+ — uses modern WP APIs
- **PHP:** Requires 8.1+ — uses modern PHP features
- **Elementor:** Requires Free or Pro — widget registration depends on Elementor being active
- **No jQuery:** All JavaScript must be pure ES6, zero jQuery dependency
- **PSR-4:** Class autoloading via `spl_autoload_register`, no Composer dependency for end users
- **Plugin identity:** Author MEHEDI HASSAN SHUBHO, text domain `devsroom-drilldown-mobile-menu`, version 0.0.01
<!-- GSD:project-end -->

<!-- GSD:stack-start source:research/STACK.md -->
## Technology Stack

## Executive Summary
## Recommended Stack
### Core Runtime
| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| PHP | 8.1 minimum, 8.3 recommended | Server-side widget logic, menu tree building, rendering | PHP 8.1 is EOL as of Dec 2025 (security only) but remains widely deployed on WordPress hosting. PHP 8.3 is the 2026 recommended version. Requiring 8.1+ gives access to enums, readonly properties, fibers, named arguments, union types, and match expressions. The PROJECT.md already mandates 8.1+. | HIGH |
| WordPress | 6.5+ | CMS platform, Script Modules API, wp_nav_menu() | WordPress 6.5 introduced `wp_register_script_module()` / `wp_enqueue_script_module()` for native ES module support. Current stable is 6.8.1. Requiring 6.5+ ensures Script Modules API availability while maintaining broad host compatibility. | HIGH |
| Elementor Free | 3.29+ | Widget registration API, editor integration | Current latest free is 3.29 (May 2025). Uses modern `elementor/widgets/register` hook (not deprecated `widgets_registered`). Widget extends `\Elementor\Widget_Base`. Works with both Free and Pro. | HIGH |
### Frontend JavaScript
| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| Vanilla ES6+ | N/A (no build step) | Drill-down menu logic, panel transitions, keyboard navigation, overlay management | Modern browsers handle ES6 natively. No transpilation needed for mobile safari, Chrome mobile, Firefox mobile -- all support `const`, `let`, arrow functions, template literals, class syntax, `dataset`, `classList`, `addEventListener`, `closest()`, `matches()`, and `CustomEvent`. No jQuery dependency as mandated by PROJECT.md. | HIGH |
| IIFE pattern | N/A | Scope isolation for plugin JS | Wrap all JS in an IIFE to avoid global namespace pollution. Pattern: `(function() { 'use strict'; ... })();`. This is the standard approach for WordPress plugins without a build step. | HIGH |
### Frontend CSS
| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| Plain CSS with native nesting | CSS Nesting spec (2023+) | Widget styles, drawer animations, panel transitions, responsive rules | CSS native nesting has 93%+ browser support (Chrome 112+, Safari 16.5+, Firefox 117+). Mobile browsers this plugin targets all support it. No SCSS build step keeps the plugin zero-dependency. Use CSS custom properties for theming variables. BEM naming convention for class structure. | HIGH |
| CSS Custom Properties | All modern browsers | Theme customization via Elementor Style tab | `--ddmm-*` prefixed custom properties for colors, spacing, transitions. Set via Elementor's inline styles, consumed by the CSS. Standard WordPress/Elementor pattern. | HIGH |
| CSS Transitions + Transforms | All modern browsers | Panel slide animations, overlay fade, drawer entrance | `transform: translateX()` for panel sliding. `transition` property for smooth animation. `will-change` hint for GPU acceleration. No JS animation library needed -- CSS handles all four animation types (Slide, Fade, Scale, Slide+Fade). | HIGH |
### PHP Architecture
| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| Custom PSR-4 Autoloader | N/A (via `spl_autoload_register`) | Class loading without Composer runtime dependency | PROJECT.md mandates no Composer dependency for end users. A custom `spl_autoload_register` callback maps `Devsroom_DDMM\` namespace to the plugin's `src/` directory. ~15 lines of code. No `vendor/` directory shipped. | HIGH |
| OOP PHP with namespaces | PHP 8.1+ | Widget class, menu builder classes, asset management | Namespace `Devsroom_DDMM\` as specified in PROJECT.md. Classes: Plugin (bootstrap), Widget_DrillDown_Menu (Elementor widget), Menu_Tree_Builder (WP menu parser), Custom_Menu_Builder (repeater processor), Asset_Loader (conditional enqueue). | HIGH |
| Elementor Widget API | 3.29+ | Widget registration, controls, rendering | Extend `\Elementor\Widget_Base`. Implement `get_name()`, `get_title()`, `get_icon()`, `get_categories()`, `_register_controls()`, `render()`. Register via `elementor/widgets/register` hook using `$widgets_manager->register()`. | HIGH |
| Elementor Controls API | 3.29+ | Content/Style tab configuration | Use `REPEATER`, `SELECT`, `SWITCHER`, `SLIDER`, `COLOR`, `TYPHOGRAPHY`, `URL`, `MEDIA`, `TEXT` controls. Group controls: `\Elementor\Group_Control_Typography`, `\Elementor\Group_Control_Border`, `\Elementor\Group_Control_Box_Shadow`. Standard Elementor widget API. | HIGH |
### Development Tooling
| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| None (no build tool) | N/A | Zero build pipeline | This plugin ships plain .js and .css files. No webpack, Vite, Rollup, or @wordpress/scripts needed. The JS is small enough (~300-500 lines) that bundling/transpilation provides no benefit. CSS uses native nesting. This is a deliberate simplicity choice: zero dev dependencies means zero CI/CD complexity. | HIGH |
| Composer (dev only, optional) | 2.x | Local development: PHP_CodeSniffer, PHPStan | Use Composer only in development for code quality tools. The `vendor/` directory is gitignored and NOT shipped with the plugin. Production has zero Composer dependency. | MEDIUM |
| Node.js (dev only, optional) | 20 LTS | Local development: ESLint, Prettier, Stylelint | Use Node.js only in development for linting/formatting. No build output. The plugin ships raw .js files. `node_modules/` is gitignored. | MEDIUM |
### WordPress APIs Used
| API | Purpose | Why |
|-----|---------|-----|
| `wp_nav_menu()` / `wp_get_nav_menu_items()` | Fetch WordPress menu items for rendering | Standard WP API for retrieving menu structures. The plugin builds its own tree from the flat items array using the 3-pass ID-based approach documented in PROJECT.md. |
| `wp_enqueue_script()` | Frontend script loading | Enqueue the drill-down JS file. Use `get_script_depends()` on the widget to declare the handle. Elementor handles conditional loading (only when widget present). |
| `wp_enqueue_style()` | Frontend CSS loading | Enqueue the widget CSS file. Use `get_style_depends()` on the widget. |
| `register_nav_menus()` | Ensure a menu location exists | Optional: the plugin reads existing WP menus, does not need to register its own location. |
| `plugin_dir_url()` / `plugin_dir_path()` | URL and path resolution | Standard WordPress plugin path functions. Use `__FILE__` as the anchor. |
| `get_registered_nav_menus()` | List available menus for dropdown control | Returns all registered menu locations for the Elementor "WordPress Menu" dropdown control. |
| `wp_get_nav_menus()` | List all nav menu objects | For the Elementor control that lets users pick which menu to display. |
| `add_action()` / `add_filter()` | WordPress hook system | Standard WP hook API for all integration points. |
### Elementor APIs Used
| API | Purpose |
|-----|---------|
| `elementor/widgets/register` | Modern widget registration hook (NOT the deprecated `widgets_registered`) |
| `\Elementor\Widget_Base` | Base class for the drill-down menu widget |
| `\Elementor\Controls_Manager` | Adding widget controls (content, style sections) |
| `\Elementor\Repeater` | Custom Menu Builder repeater control |
| `\Elementor\Group_Control_Typography` | Typography styling control |
| `\Elementor\Group_Control_Border` | Border styling control |
| `\Elementor\Group_Control_Box_Shadow` | Box shadow styling control |
| `\Elementor\Utils` | Utility methods |
| `elementor/frontend/init` | JS initialization hook for Elementor frontend |
| `get_script_depends()` / `get_style_depends()` | Conditional asset loading based on widget presence |
| `should_enqueue_assets()` | Elementor's internal check for whether to load widget assets |
## Alternatives Considered
| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| JS Build Tool | None (plain JS) | Webpack / @wordpress/scripts | Overkill for ~400 lines of JS. Adds build complexity, CI steps, and ship complications for zero benefit. No ES modules to bundle, no JSX to transpile. |
| JS Build Tool | None | Vite | Same reasoning as Webpack. Vite is excellent for SPAs and React apps, but this is a self-contained widget JS file. |
| JS Framework | Vanilla ES6 | React / Preact / Alpine.js | PROJECT.md mandates pure ES6. React is unnecessary for a menu widget. Alpine.js adds a dependency for marginal benefit. Vanilla JS handles this DOM manipulation trivially. |
| CSS Preprocessor | Plain CSS | SCSS / Sass | Native CSS nesting covers the primary SCSS benefit. No mixins, @extend, or functions needed. Skipping SCSS eliminates a build tool dependency entirely. |
| CSS Approach | Plain CSS | Tailwind CSS | Utility-first CSS is inappropriate for a WordPress plugin that must work inside any theme. Elementor widgets need scoped, predictable styles, not utility classes that may conflict. |
| JS Library | Vanilla ES6 | jQuery | PROJECT.md explicitly mandates "no jQuery dependency." WordPress is deprecating jQuery usage. Modern DOM APIs (`querySelector`, `classList`, `dataset`, `closest`, `matches`) cover everything jQuery would provide here. |
| Autoloading | Custom PSR-4 | Composer autoloader | PROJECT.md mandates no Composer runtime dependency for end users. A custom `spl_autoload_register` callback is ~15 lines and ships zero extra files. |
| Autoloading | Custom PSR-4 | Manual `require_once` | PSR-4 is cleaner, scalable, and follows modern PHP standards. Manual requires become unmaintainable as class count grows. |
| Animation | CSS Transitions | GSAP / anime.js / Motion One | CSS transitions handle slide/fade/scale/slide+fade natively. No JS animation library needed. CSS is hardware-accelerated and performs better on mobile for these transform types. |
| PHP Version | 8.1 minimum | 7.4 | PHP 7.4 is EOL. PHP 8.1+ provides named arguments, enums, readonly properties, fiber support, and union types. The plugin targets modern hosting. |
| WP Script Loading | `wp_enqueue_script()` | `wp_enqueue_script_module()` | Script Modules API is newer and cleaner, but has a critical limitation: no `wp_localize_script()` equivalent for passing PHP data to JS. The drill-down menu needs to pass configuration from PHP (menu data, settings) to JS. Using `wp_enqueue_script()` with a `wp_add_inline_script()` data injection is the pragmatic choice. Additionally, Elementor's own frontend init system (`elementor/frontend/init`) was designed for classic scripts, not modules. Stick with classic `wp_enqueue_script()`. |
## Plugin File Structure
## Key Architecture Decisions
### 1. No Build Tool
### 2. Custom PSR-4 Autoloader (No Composer Runtime)
### 3. Classic `wp_enqueue_script()` over Script Modules
### 4. CSS Native Nesting (No SCSS)
### 5. IIFE-Wrapped JS (No ES Modules)
## Installation
### Production (end users)
# No installation required. Plugin is activated via WordPress admin.
# No Composer. No Node.js. No build step.
### Development (contributors, optional)
# Optional: PHP linting and static analysis
# Optional: JS/CSS linting
## Version Compatibility Matrix
| Dependency | Minimum | Recommended | Latest Stable | Notes |
|------------|---------|-------------|---------------|-------|
| WordPress | 6.5 | 6.8 | 6.8.1 | 6.5 for Script Modules API (not used, but ensures modern baseline) |
| PHP | 8.1 | 8.3 | 8.4 | 8.1 is the declared minimum; 8.3 recommended for production |
| Elementor Free | 3.29 | 3.29+ | 3.29 | Modern widget registration API |
| Elementor Pro | Any | Latest | 3.33 | Plugin works with both Free and Pro |
| MySQL | 5.7 | 8.0 | 8.x | Follows WordPress requirements |
| Node.js | N/A | N/A | N/A | Not used in production or build |
| Composer | N/A | N/A | N/A | Not used in production |
## Browser Support
| Browser | Minimum Version | Why |
|---------|----------------|-----|
| Chrome Mobile | 90+ | ES6, CSS nesting, classList, dataset, Transitions |
| Safari iOS | 16.5+ | CSS native nesting support |
| Firefox Mobile | 117+ | CSS native nesting support |
| Samsung Internet | 20+ | Chromium-based, inherits Chrome support |
## Sources
- [Elementor Developer Docs - Requirements](https://developers.elementor.com/docs/getting-started/requirements/) -- Official requirements
- [Elementor Developer Docs - Registering Widgets](https://developers.elementor.com/docs/managers/registering-widgets/) -- Modern widget registration API
- [Elementor Developer Docs - Scripts & Styles](https://developers.elementor.com/docs/scripts-styles/) -- Conditional asset loading
- [Elementor Pro Changelog](https://elementor.com/pro/changelog/) -- Latest version 3.33.0
- [WordPress Requirements](https://wordpress.org/about/requirements/) -- Official WP server requirements
- [WordPress 6.5 Script Modules API](https://make.wordpress.org/core/2024/03/04/script-modules-in-6-5/) -- Script Modules documentation
- [PHP Supported Versions](https://www.php.net/supported-versions.php) -- PHP lifecycle and EOL dates
- [Can I Use - CSS Nesting](https://caniuse.com/css-nesting) -- Browser support for native CSS nesting
- [WooCommerce CSS/Sass Naming Conventions](https://developer.woocommerce.com/docs/best-practices/coding-standards/css-sass-naming-conventions/) -- BEM for WordPress plugins
- [DLX Plugins - PSR-4 WordPress Plugin](https://dlxplugins.com/tutorials/creating-a-psr-4-autoloading-wordpress-plugin/) -- Custom autoloader without Composer
- [Codeable - Elementor Widget Development](https://www.codeable.io/blog/elementor-widget-development/) -- Best practices reference
- [WordPress.org Releases](https://wordpress.org/news/category/releases/) -- WordPress 6.8 release info
- [Make WordPress Core - PHP 8 Support](https://make.wordpress.org/core/2025/04/09/php-8-support-clarification/) -- WordPress PHP version support status
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

Conventions not yet established. Will populate as patterns emerge during development.
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

Architecture not yet mapped. Follow existing patterns found in the codebase.
<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->
## Project Skills

No project skills found. Add skills to any of: `.claude/skills/`, `.agents/skills/`, `.cursor/skills/`, or `.github/skills/` with a `SKILL.md` index file.
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->



<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
