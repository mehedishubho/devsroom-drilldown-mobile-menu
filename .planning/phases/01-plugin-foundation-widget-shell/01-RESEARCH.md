# Phase 1: Plugin Foundation & Widget Shell - Research

**Researched:** 2026-06-12
**Domain:** WordPress Plugin Bootstrap + Elementor Widget Registration + Trigger Button
**Confidence:** HIGH

## Summary

Phase 1 establishes the entire plugin scaffolding: the main PHP entry point with PSR-4 autoloader, Elementor dependency check with admin notice, widget registration on the modern `elementor/widgets/register` hook, Content Tab controls for trigger button configuration, trigger button HTML rendering with four display modes, and conditional CSS/JS asset loading via `get_script_depends()`/`get_style_depends()`. This is a greenfield phase with zero existing source code.

The existing research in `.planning/research/ARCHITECTURE.md`, `STACK.md`, and `PITFALLS.md` provides comprehensive coverage of the patterns and pitfalls. This research document focuses on Phase 1-specific implementation details: exact Elementor API signatures verified against official docs, the precise control configuration for each trigger type, CSS span-based hamburger icon implementation, and the specific file structure to create.

**Primary recommendation:** Follow the file structure and patterns from ARCHITECTURE.md exactly. Create files in dependency order: autoloader first, then Plugin bootstrap, then Asset Registrar, then Widget class with controls and render. Use the verified Elementor API signatures documented below -- they are confirmed against official docs as of June 2026.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Widget internal name (`get_name()`) is `ddmm-drilldown-menu`
- **D-02:** Widget appears in a custom Elementor category named "Devsroom" (registered via `elementor/elements/categories_registered`)
- **D-03:** Widget icon is a custom inline SVG hamburger (three lines) -- no dashicon, no eicon dependency
- **D-04:** PSR-4 autoloader via `spl_autoload_register()` maps `Devsroom_DDMM\` to `src/` directory (no Composer)
- **D-05:** Plugin class uses singleton pattern, initializes on `plugins_loaded` hook
- **D-06:** Admin notice displays when Elementor is not active, with link to install/activate -- simple styled notice, no custom branding
- **D-07:** Plugin header declares: name, version 0.0.01, author MEHEDI HASSAN SHUBHO, text domain `devsroom-drilldown-mobile-menu`
- **D-08:** Trigger renders as `<button>` element with `aria-expanded="false"` and `aria-controls` pointing to drawer ID
- **D-09:** Four trigger types: Hamburger Lines, Custom Icon (Elementor Icons picker), Text Only, Icon + Text
- **D-10:** Hamburger Lines type uses CSS spans (3-span trick) -- three `<span>` elements inside the button, styled as horizontal lines via CSS. Enables Phase 5 hamburger-to-X animation via class toggle.
- **D-11:** Icon + Text type supports configurable position (icon Before Text or After Text)
- **D-12:** Trigger button ships with reasonable default CSS: dark lines on transparent background, ~32px hamburger size, padding, cursor pointer. Not bare/unstyled -- looks decent out of the box. Phase 6 Style Tab overrides everything.
- **D-13:** Assets registered via `wp_register_script()` / `wp_register_style()` on `wp_enqueue_scripts` (register only, not enqueue)
- **D-14:** Widget declares handles via `get_script_depends()` / `get_style_depends()` -- Elementor enqueues conditionally
- **D-15:** JS file (`ddmm-frontend.js`) ships as empty IIFE shell in Phase 1 -- populated in Phase 5
- **D-16:** CSS file (`ddmm-frontend.css`) contains trigger button base styles and CSS custom properties skeleton (`--ddmm-*`)

### Claude's Discretion
- Exact admin notice copy and styling details
- CSS custom property naming conventions (`--ddmm-*` prefix specifics)
- Default trigger button pixel values (padding, line thickness, gap)
- Widget description text shown in Elementor panel
- Inline SVG markup details for the widget panel icon

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| PLUG-01 | Plugin bootstraps via main PHP file with PSR-4 autoloader mapping `Devsroom_DDMM\` namespace | Pattern verified: `spl_autoload_register()` in main file, maps `Devsroom_DDMM\` to `src/`. See Code Examples below. |
| PLUG-02 | Plugin singleton initializes on `plugins_loaded` after confirming Elementor is active | Singleton pattern with `plugins_loaded` hook. Elementor check via `did_action('elementor/loaded')`. |
| PLUG-03 | Admin notice when Elementor is not active, with link to install/activate | `admin_notices` hook. Check `did_action('elementor/loaded')`. Show notice with activation link. |
| PLUG-04 | Plugin header declares correct identity | Standard WordPress plugin header with all required fields. |
| PLUG-05 | Assets only enqueued when widget is present on page | `wp_register_script()` + `get_script_depends()` pattern. Verified against Elementor official docs. |
| PLUG-06 | All output is escaped (`esc_attr`, `esc_url`, `esc_html`) | Every `echo` in `render()` must use escaping functions. |
| WIDG-01 | Widget registers on `elementor/widgets/register` hook | Verified: modern hook. Use `$widgets_manager->register(new Widget())`. |
| WIDG-02 | Widget class extends `\Elementor\Widget_Base` with correct identity methods | `get_name()`, `get_title()`, `get_icon()`, `get_categories()`. |
| WIDG-03 | Widget icon is a custom SVG or dashicon representing a mobile menu | Inline SVG data URI in `get_icon()` return value. |
| WIDG-04 | Script and style dependencies declared via `get_script_depends()` and `get_style_depends()` | Verified: return array of registered handles. Elementor enqueues conditionally. |
| TRIG-01 | Trigger renders as a `<button>` element for accessibility | `<button>` with type="button", class, aria attributes. |
| TRIG-02 | Hamburger Lines type renders animated three-line icon | Three `<span>` elements inside button, styled via CSS. Enables Phase 5 animation. |
| TRIG-03 | Custom Icon type uses Elementor Icons picker | `\Elementor\Controls_Manager::ICONS` control. Render via `\Elementor\Icons_Manager::render_icon()`. |
| TRIG-04 | Text Only type renders configurable text string | `\Elementor\Controls_Manager::TEXT` control with `label_block => true`. |
| TRIG-05 | Icon + Text type renders both with configurable position | `CHOOSE` control for position (before/after). Conditionally displayed. |
| TRIG-06 | Trigger has `aria-expanded="false"` toggled when drawer opens | Static `aria-expanded="false"` in PHP render. JS toggles in Phase 5. |
| COMP-01 | Compatible with WordPress 6.5+, PHP 8.1/8.2/8.3 | Plugin header `Requires at least: 6.5`, `Requires PHP: 8.1`. PHP 8.4 also compatible. |
| COMP-02 | Compatible with Elementor Free and Pro | No Pro-only APIs used. `elementor/widgets/register` works in both. |
| COMP-05 | Compatible with any WordPress theme -- no theme-specific CSS overrides | All CSS scoped under `.elementor-widget-ddmm-drilldown-menu` wrapper. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP | 8.1 minimum | Server-side logic, widget rendering, autoloader | Project requirement. 8.1+ for named arguments, readonly, enums. Local: PHP 8.4.15. |
| WordPress | 6.5+ | CMS platform, hook system, script registration | 6.5+ baseline for modern APIs. |
| Elementor | 3.29+ (Free or Pro) | Widget registration API, Controls API, conditional asset loading | Modern `elementor/widgets/register` hook. `ICONS` control. |

### Elementor APIs Used in Phase 1
| API | Purpose | Status |
|-----|---------|--------|
| `\Elementor\Widget_Base` | Base class for the widget | VERIFIED: official docs |
| `\Elementor\Controls_Manager::SELECT` | Trigger type dropdown | VERIFIED: official docs |
| `\Elementor\Controls_Manager::ICONS` | Custom icon picker | VERIFIED: official docs |
| `\Elementor\Controls_Manager::TEXT` | Trigger text input | VERIFIED: official docs |
| `\Elementor\Controls_Manager::CHOOSE` | Icon position (before/after text) | VERIFIED: official docs |
| `\Elementor\Controls_Manager::SWITCHER` | Not needed in Phase 1 | -- |
| `\Elementor\Icons_Manager::render_icon()` | Render selected icon in PHP `render()` | VERIFIED: Icons control docs |
| `elementor/elements/categories_registered` | Register custom "Devsroom" category | VERIFIED: official docs |
| `elementor/widgets/register` | Register widget with Widgets_Manager | VERIFIED: official docs |
| `get_script_depends()` / `get_style_depends()` | Declare asset handles for conditional loading | VERIFIED: official docs |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Custom SVG icon for widget panel | Dashicon (`dashicons-menu`) | Dashicons may be removed from WordPress in future. Custom SVG gives full control of appearance. Decision D-03 locks this. |
| IIFE shell JS | Empty file with comment | IIFE shell establishes the pattern Phase 5 fills. Prevents "empty file" confusion. |

## Architecture Patterns

### Recommended Project Structure (Phase 1 files only)

```
devsroom-drilldown-mobile-menu/
  devsroom-drilldown-mobile-menu.php    # Plugin entry point (header, autoloader, Plugin init)
  src/
    Plugin.php                          # Main plugin class (singleton, hooks, Elementor check)
    Admin/
      ElementorNotice.php               # Admin notice when Elementor inactive
    Assets/
      Registrar.php                     # Script/style registration
    Elementor/
      Widget/
        DrillDownMenu.php               # Widget class (controls, trigger render)
  assets/
    js/
      ddmm-frontend.js                  # Empty IIFE shell (populated Phase 5)
    css/
      ddmm-frontend.css                 # Trigger button base styles + CSS custom properties
```

**Note:** `src/MenuBuilder/`, `src/Rendering/`, and `languages/` are NOT created in Phase 1. They arrive in later phases.

### Pattern 1: Plugin Bootstrap with Elementor Check

**What:** Main plugin file declares header, registers autoloader, initializes Plugin singleton on `plugins_loaded`.
**When to use:** Always for Elementor widget plugins.

```php
// devsroom-drilldown-mobile-menu.php (entry point)
<?php
/**
 * Plugin Name: Devsroom DrillDown Mobile Menu
 * Description: A mobile drill-down menu widget for Elementor.
 * Version: 0.0.01
 * Author: MEHEDI HASSAN SHUBHO
 * Text Domain: devsroom-drilldown-mobile-menu
 * Requires at least: 6.5
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// PSR-4 Autoloader
spl_autoload_register( function ( string $class ): void {
    $prefix = 'Devsroom_DDMM\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen( $prefix );

    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
});

// Initialize plugin
add_action( 'plugins_loaded', function (): void {
    \Devsroom_DDMM\Plugin::get_instance()->init();
});
```

**Source:** [VERIFIED: developers.elementor.com/docs/managers/registering-widgets/] + [CITED: .planning/research/ARCHITECTURE.md Pattern 2]

### Pattern 2: Conditional Asset Loading (Register, Not Enqueue)

**What:** Register scripts/styles on `wp_enqueue_scripts` but do NOT enqueue. Widget declares handles via `get_script_depends()`/`get_style_depends()`. Elementor enqueues only when widget is present on the page.
**When to use:** Always for Elementor widget plugins.

```php
// src/Assets/Registrar.php
namespace Devsroom_DDMM\Assets;

class Registrar {
    public function register(): void {
        add_action( 'wp_enqueue_scripts', function (): void {
            wp_register_script(
                'ddmm-frontend',
                plugin_dir_url( dirname( __DIR__, 2 ) ) . 'assets/js/ddmm-frontend.js',
                [],
                '0.0.01',
                true  // Load in footer
            );
            wp_register_style(
                'ddmm-frontend',
                plugin_dir_url( dirname( __DIR__, 2 ) ) . 'assets/css/ddmm-frontend.css',
                [],
                '0.0.01'
            );
        });
    }
}

// In widget class:
public function get_script_depends(): array {
    return [ 'ddmm-frontend' ];
}

public function get_style_depends(): array {
    return [ 'ddmm-frontend' ];
}
```

**Source:** [VERIFIED: developers.elementor.com/docs/widgets/widget-dependencies/] -- Official docs confirm: "these dependencies should already be registered. The widget class only informs Elementor what dependencies it needs to enqueue."

### Pattern 3: Elementor Widget Category Registration

**What:** Register a custom "Devsroom" category via `elementor/elements/categories_registered`.
**When to use:** When you want widgets grouped under your own brand name.

```php
// In Plugin.php init():
add_action( 'elementor/elements/categories_registered', function ( \Elementor\Elements_Manager $elements_manager ): void {
    $elements_manager->add_category(
        'devsroom',
        [
            'title' => esc_html__( 'Devsroom', 'devsroom-drilldown-mobile-menu' ),
            'icon'  => 'fa fa-plug',  // Icon shown in category panel
        ]
    );
});
```

**Source:** [VERIFIED: developers.elementor.com/docs/hooks/widget-categories/]

### Pattern 4: Trigger Type Controls with Conditional Display

**What:** SELECT control for trigger type, conditionally showing ICONS, TEXT, and CHOOSE controls.
**When to use:** When multiple display modes share a parent selector.

```php
// In _register_controls():
// Trigger Type selector
$this->add_control(
    'trigger_type',
    [
        'label'   => esc_html__( 'Trigger Type', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'hamburger',
        'options' => [
            'hamburger'   => esc_html__( 'Hamburger Lines', 'devsroom-drilldown-mobile-menu' ),
            'custom_icon' => esc_html__( 'Custom Icon', 'devsroom-drilldown-mobile-menu' ),
            'text_only'   => esc_html__( 'Text Only', 'devsroom-drilldown-mobile-menu' ),
            'icon_text'   => esc_html__( 'Icon + Text', 'devsroom-drilldown-mobile-menu' ),
        ],
    ]
);

// Custom Icon - shown only when trigger_type is 'custom_icon'
$this->add_control(
    'trigger_icon',
    [
        'label'     => esc_html__( 'Choose Icon', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::ICONS,
        'default'   => [
            'value'   => 'fas fa-bars',
            'library' => 'fa-solid',
        ],
        'condition' => [
            'trigger_type' => 'custom_icon',
        ],
    ]
);

// Trigger Text - shown when trigger_type is 'text_only' or 'icon_text'
$this->add_control(
    'trigger_text',
    [
        'label'       => esc_html__( 'Button Text', 'devsroom-drilldown-mobile-menu' ),
        'type'        => \Elementor\Controls_Manager::TEXT,
        'default'     => esc_html__( 'Menu', 'devsroom-drilldown-mobile-menu' ),
        'placeholder' => esc_html__( 'Enter menu text', 'devsroom-drilldown-mobile-menu' ),
        'condition'   => [
            'trigger_type' => [ 'text_only', 'icon_text' ],
        ],
    ]
);

// Icon for Icon+Text mode
$this->add_control(
    'trigger_icon_text_icon',
    [
        'label'     => esc_html__( 'Choose Icon', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::ICONS,
        'default'   => [
            'value'   => 'fas fa-bars',
            'library' => 'fa-solid',
        ],
        'condition' => [
            'trigger_type' => 'icon_text',
        ],
    ]
);

// Icon Position - shown only for icon_text type
$this->add_control(
    'trigger_icon_position',
    [
        'label'     => esc_html__( 'Icon Position', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::CHOOSE,
        'default'   => 'before',
        'options'   => [
            'before' => [
                'title' => esc_html__( 'Before Text', 'devsroom-drilldown-mobile-menu' ),
                'icon'  => 'eicon-h-align-left',
            ],
            'after'  => [
                'title' => esc_html__( 'After Text', 'devsroom-drilldown-mobile-menu' ),
                'icon'  => 'eicon-h-align-right',
            ],
        ],
        'condition' => [
            'trigger_type' => 'icon_text',
        ],
    ]
);
```

**Source:** [VERIFIED: developers.elementor.com/docs/editor-controls/conditional-display/] -- Official docs confirm `condition` with single value (equality), array values (OR), and `name!` syntax (inequality).

### Pattern 5: Trigger Button Rendering

**What:** PHP `render()` method outputs different button HTML based on trigger_type setting. All output escaped.
**When to use:** Widget render method.

```php
protected function render(): void {
    $settings = $this->get_settings_for_display();
    $trigger_type = $settings['trigger_type'] ?? 'hamburger';
    $widget_id = $this->get_id(); // Elementor's unique widget instance ID

    ?>
    <div class="ddmm-trigger-wrapper">
        <button
            type="button"
            class="ddmm-trigger ddmm-trigger--<?php echo esc_attr( $trigger_type ); ?>"
            aria-expanded="false"
            aria-controls="ddmm-drawer-<?php echo esc_attr( $widget_id ); ?>"
        >
            <?php
            switch ( $trigger_type ) {
                case 'hamburger':
                    // Three-span hamburger for CSS styling + Phase 5 animation
                    ?>
                    <span class="ddmm-hamburger">
                        <span class="ddmm-hamburger__line"></span>
                        <span class="ddmm-hamburger__line"></span>
                        <span class="ddmm-hamburger__line"></span>
                    </span>
                    <?php
                    break;

                case 'custom_icon':
                    \Elementor\Icons_Manager::render_icon(
                        $settings['trigger_icon'],
                        [ 'aria-hidden' => 'true' ]
                    );
                    break;

                case 'text_only':
                    echo esc_html( $settings['trigger_text'] );
                    break;

                case 'icon_text':
                    $icon_html = \Elementor\Icons_Manager::render_icon(
                        $settings['trigger_icon_text_icon'],
                        [ 'aria-hidden' => 'true' ],
                        'i',
                        true  // Return, not echo
                    );
                    $text = esc_html( $settings['trigger_text'] );
                    $position = $settings['trigger_icon_position'] ?? 'before';

                    if ( 'before' === $position ) {
                        echo $icon_html . ' ' . $text; // phpcs:ignore -- $icon_html already escaped by Icons_Manager
                    } else {
                        echo $text . ' ' . $icon_html; // phpcs:ignore -- $icon_html already escaped by Icons_Manager
                    }
                    break;
            }
            ?>
        </button>
    </div>
    <?php
}
```

**Source:** [VERIFIED: developers.elementor.com/docs/editor-controls/control-icons/] -- Official docs confirm `\Elementor\Icons_Manager::render_icon()` signature and usage.

### Pattern 6: Admin Notice for Missing Elementor

**What:** Check Elementor presence and show admin notice if missing.
**When to use:** Every Elementor addon plugin.

```php
// src/Admin/ElementorNotice.php
namespace Devsroom_DDMM\Admin;

class ElementorNotice {
    public function register(): void {
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'renderNotice' ] );
        }
    }

    public function renderNotice(): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $plugin_file = 'elementor/elementor.php';
        $install_url = admin_url( 'plugin-install.php?s=elementor&tab=search&type=term' );

        if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
            // Elementor installed but not active
            $action_url = wp_nonce_url(
                admin_url( 'plugins.php?action=activate&plugin=' . $plugin_file ),
                'activate-plugin_' . $plugin_file
            );
            $message = sprintf(
                /* translators: %s: activate link */
                esc_html__( 'Devsroom DrillDown Menu requires Elementor to be active. %s', 'devsroom-drilldown-mobile-menu' ),
                '<a href="' . esc_url( $action_url ) . '">' . esc_html__( 'Activate Elementor', 'devsroom-drilldown-mobile-menu' ) . '</a>'
            );
        } else {
            // Elementor not installed
            $message = sprintf(
                /* translators: %s: install link */
                esc_html__( 'Devsroom DrillDown Menu requires Elementor. %s', 'devsroom-drilldown-mobile-menu' ),
                '<a href="' . esc_url( $install_url ) . '">' . esc_html__( 'Install Elementor', 'devsroom-drilldown-mobile-menu' ) . '</a>'
            );
        }

        printf(
            '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
            wp_kses_post( $message )
        );
    }
}
```

**Source:** [CITED: Standard WordPress plugin pattern. `did_action('elementor/loaded')` from .planning/research/ARCHITECTURE.md]

### Pattern 7: Empty JS IIFE Shell

**What:** Phase 1 ships an empty IIFE that Phase 5 fills with drill-down logic.
**When to use:** Establishing the JS file early so asset registration works.

```javascript
/**
 * Devsroom DrillDown Mobile Menu - Frontend
 *
 * Phase 1: Shell only. Populated in Phase 5 with drill-down logic.
 */
( function() {
    'use strict';

    // Drill-down menu logic will be added in Phase 5.
} )();
```

**Source:** [CITED: .planning/research/ARCHITECTURE.md Pattern 5 -- IIFE wrapping]

### Anti-Patterns to Avoid

- **Eager enqueuing:** Never call `wp_enqueue_script()`/`wp_enqueue_style()` directly. Always register, then let Elementor conditionally enqueue via `get_script_depends()`/`get_style_depends()`. [VERIFIED: developers.elementor.com/docs/widgets/widget-dependencies/]
- **Deprecated `widgets_registered` hook:** Use `elementor/widgets/register`, NOT the old `widgets_registered`. [VERIFIED: developers.elementor.com/docs/managers/registering-widgets/]
- **Using `$this->get_settings()` in `get_script_depends()`:** This does NOT work in Elementor editor mode. `get_script_depends()` fires before settings are available. [VERIFIED: github.com/elementor/elementor/issues/7623]
- **PSR-4 case mismatch:** Namespace `Devsroom_DDMM` MUST map to directory names matching casing. `src/Admin/ElementorNotice.php` (not `src/admin/elementornotice.php`). [CITED: .planning/research/PITFALLS.md]
- **Unescaped output in `render()`:** Every dynamic value must use `esc_html()`, `esc_attr()`, or `esc_url()`. [CITED: REQUIREMENTS.md PLUG-06]

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Icon rendering in widget | Manual `<i>` tag generation | `\Elementor\Icons_Manager::render_icon()` | Handles Font Awesome, SVG, and all icon libraries. Official Elementor API. |
| Icon picker control | Custom icon selector UI | `\Elementor\Controls_Manager::ICONS` | Full icon library + SVG upload. Built into Elementor. |
| Widget presence detection | Manual DB queries or option checks | `get_script_depends()` / `get_style_depends()` | Elementor handles detection internally. Official recommendation. |
| CSS preprocessor | SCSS build pipeline | Native CSS with nesting + custom properties | All target browsers support it. Zero build step. |
| Autoloader | Composer autoloader | Custom `spl_autoload_register()` | No runtime dependency. ~15 lines. End users install via ZIP. |

**Key insight:** Every Elementor API listed above has been verified against official documentation. There is no reason to build custom alternatives for any of these.

## Common Pitfalls

### Pitfall 1: PSR-4 Case Sensitivity on Linux Hosting

**What goes wrong:** Plugin works on Windows (case-insensitive filesystem) but throws "class not found" fatal errors on Linux hosting (case-sensitive). This is the #1 deployment issue for WordPress plugins using PSR-4.
**Why it happens:** `Devsroom_DDMM\Admin\ElementorNotice` requires file at `src/Admin/ElementorNotice.php`. On Windows, `src/admin/elementornotice.php` would also match. On Linux, it will not.
**How to avoid:** Ensure directory names match namespace casing EXACTLY: `src/Admin/` (capital A), `src/Assets/` (capital A), `src/Elementor/` (capital E). Filenames match class names exactly.
**Warning signs:** "Class not found" errors only on production Linux servers, not on local Windows development.

### Pitfall 2: `get_script_depends()` Cannot Access Widget Settings

**What goes wrong:** Trying to conditionally load scripts based on widget settings in `get_script_depends()` fails in editor mode. The method fires before settings are available.
**Why it happens:** Elementor's improved asset loading (v3.27+) calls dependency methods early in the lifecycle, before per-widget settings are resolved.
**How to avoid:** Always return ALL registered handles from `get_script_depends()`/`get_style_depends()`. Conditional loading is handled by Elementor based on widget presence, not settings values.
**Warning signs:** Scripts load on frontend but not in Elementor editor preview.

### Pitfall 3: Widget Not Appearing in Elementor Panel

**What goes wrong:** Plugin activates without errors but the widget does not appear in the Elementor editor widget panel.
**Why it happens:** Most commonly: (1) Widget category not registered, (2) `get_categories()` returns a category slug that does not exist, (3) Registration happens on wrong hook.
**How to avoid:** Register the "devsroom" category on `elementor/elements/categories_registered` BEFORE registering the widget. Ensure `get_categories()` returns `['devsroom']` matching the registered slug exactly. Register the widget on `elementor/widgets/register`.
**Warning signs:** No PHP errors, no debug output, widget simply absent from editor.

### Pitfall 4: CSS Conflicts with Themes

**What goes wrong:** Trigger button looks wrong on certain themes because theme CSS overrides the widget styles. Common with aggressive theme selectors like `.site-header button { ... }` or `.main-navigation a { ... }`.
**Why it happens:** WordPress themes apply broad CSS selectors that cascade into widget output.
**How to avoid:** Scope all CSS under `.elementor-widget-ddmm-drilldown-menu` (Elementor's automatic widget wrapper class). Use sufficient specificity. The BEM-prefixed `ddmm-` class namespace prevents collisions.
**Warning signs:** Widget looks correct in one theme but broken in another.

### Pitfall 5: Admin Notice Showing When Elementor IS Active

**What goes wrong:** The "Elementor not active" admin notice appears even when Elementor is running.
**Why it happens:** Checking Elementor presence before `plugins_loaded` fires. The `elementor/loaded` action fires during `plugins_loaded`, so checking in the main plugin file directly (before hooks) gives a false negative.
**How to avoid:** Hook the Elementor check on `plugins_loaded` (priority 10 or later), not in the main file's global scope. Use `did_action('elementor/loaded')` inside a hooked function.
**Warning signs:** Notice always shows, regardless of Elementor state.

### Pitfall 6: Icons_Manager::render_icon() Returning vs Echoing

**What goes wrong:** `render_icon()` outputs the icon HTML in the wrong place or not at all, depending on the `$return` parameter.
**Why it happens:** The method has a `$return` parameter (default `false`) that controls whether it echoes or returns the HTML.
**How to avoid:** For direct output inside `render()`, call without the return parameter (it echoes). For building strings (icon + text concatenation), pass `true` to get the HTML string back.
**Warning signs:** Icon appears outside the button, or duplicated, or missing entirely.

## Code Examples

### Complete Plugin Singleton Pattern

```php
// src/Plugin.php
namespace Devsroom_DDMM;

use Devsroom_DDMM\Admin\ElementorNotice;
use Devsroom_DDMM\Assets\Registrar;
use Devsroom_DDMM\Elementor\Widget\DrillDownMenu;

final class Plugin {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Singleton -- prevent direct construction
    }

    public function init(): void {
        // Check if Elementor is active
        if ( ! did_action( 'elementor/loaded' ) ) {
            ( new ElementorNotice() )->register();
            return;  // Do not register widget or assets without Elementor
        }

        // Register custom widget category
        add_action( 'elementor/elements/categories_registered', [ $this, 'registerCategory' ] );

        // Register widget
        add_action( 'elementor/widgets/register', [ $this, 'registerWidget' ] );

        // Register assets (register only, Elementor enqueues conditionally)
        ( new Registrar() )->register();
    }

    public function registerCategory( \Elementor\Elements_Manager $elements_manager ): void {
        $elements_manager->add_category(
            'devsroom',
            [
                'title' => esc_html__( 'Devsroom', 'devsroom-drilldown-mobile-menu' ),
            ]
        );
    }

    public function registerWidget( \Elementor\Widgets_Manager $widgets_manager ): void {
        $widgets_manager->register( new DrillDownMenu() );
    }
}
```

**Source:** [VERIFIED: developers.elementor.com/docs/managers/registering-widgets/] -- Official docs show `$widgets_manager->register( new Widget() )` pattern.

### Widget Icon as Inline SVG Data URI

```php
public function get_icon(): string {
    // Inline SVG hamburger icon (three horizontal lines)
    return 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>'
    );
}
```

**Source:** [ASSUMED] -- Elementor's `get_icon()` accepts icon class strings (e.g., `eicon-menu-bar`) OR SVG data URIs. The data URI approach avoids dependency on Elementor's icon font.

### CSS Hamburger Base Styles (Phase 1 Default)

```css
/* assets/css/ddmm-frontend.css */

/* ========================================
   Devsroom DrillDown Mobile Menu
   Phase 1: Trigger Button Base Styles
   ======================================== */

/* CSS Custom Properties - Phase 6 Style Tab overrides these */
.elementor-widget-ddmm-drilldown-menu {
    --ddmm-trigger-color: #1a1a1a;
    --ddmm-trigger-bg: transparent;
    --ddmm-trigger-padding: 8px;
    --ddmm-trigger-border: none;
    --ddmm-trigger-border-radius: 0;
    --ddmm-trigger-cursor: pointer;
    --ddmm-trigger-font-size: 16px;
    --ddmm-hamburger-width: 28px;
    --ddmm-hamburger-height: 20px;
    --ddmm-hamburger-line-height: 2px;
    --ddmm-hamburger-line-gap: 6px;
    --ddmm-hamburger-line-color: var(--ddmm-trigger-color);
    --ddmm-hamburger-line-radius: 1px;
    --ddmm-transition-duration: 300ms;
}

/* Trigger Button */
.ddmm-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: var(--ddmm-trigger-padding);
    background: var(--ddmm-trigger-bg);
    border: var(--ddmm-trigger-border);
    border-radius: var(--ddmm-trigger-border-radius);
    color: var(--ddmm-trigger-color);
    font-size: var(--ddmm-trigger-font-size);
    cursor: var(--ddmm-trigger-cursor);
    line-height: 1;
    -webkit-tap-highlight-color: transparent;
}

.ddmm-trigger:focus-visible {
    outline: 2px solid var(--ddmm-trigger-color);
    outline-offset: 2px;
}

/* Hamburger Lines (3-span trick) */
.ddmm-hamburger {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: var(--ddmm-hamburger-width);
    height: var(--ddmm-hamburger-height);
}

.ddmm-hamburger__line {
    display: block;
    width: 100%;
    height: var(--ddmm-hamburger-line-height);
    background-color: var(--ddmm-hamburger-line-color);
    border-radius: var(--ddmm-hamburger-line-radius);
    transition: transform var(--ddmm-transition-duration) ease,
                opacity var(--ddmm-transition-duration) ease;
}

/* Phase 5 will add .ddmm-trigger--active .ddmm-hamburger__line
   transforms for hamburger-to-X animation */
```

**Source:** [CITED: Decision D-10, D-12] -- CSS spans chosen for Phase 5 animation capability. Default values are reasonable defaults (not bare/unstyled).

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `widgets_registered` hook | `elementor/widgets/register` hook | Elementor 3.5+ | Old hook is deprecated. New hook passes `Widgets_Manager` directly. |
| `$manager->register_widget_type()` | `$manager->register()` | Elementor 3.5+ | Cleaner API. Single argument: widget instance. |
| Global script enqueuing | Conditional via `get_script_depends()` | Elementor 3.1+ (experiment), 3.27+ (default) | Scripts only loaded when widget present on page. |
| Manual `require_once` per class | PSR-4 autoloader | PHP 5.3+ (namespaces), standard since PHP 7 | Modern standard. ~15 lines for custom implementation. |

**Deprecated/outdated:**
- `widgets_registered` hook: Deprecated in favor of `elementor/widgets/register`. [VERIFIED: developers.elementor.com]
- `register_widget_type()` method: Deprecated in favor of `register()`. [VERIFIED: developers.elementor.com]
- jQuery for widget JS: WordPress is actively deprecating jQuery. Elementor's own frontend still uses jQuery internally, but custom widgets should use vanilla ES6. [CITED: CLAUDE.md project instructions]

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `get_icon()` accepts SVG data URI strings | Widget Icon section | LOW -- fallback to dashicon string `dashicons-menu` |
| A2 | `\Elementor\Icons_Manager::render_icon()` has a `$return` parameter for string return instead of echo | Pattern 5 | LOW -- official docs show echo usage; `$return` param can be verified at implementation time |
| A3 | `plugin_dir_url( dirname( __DIR__, 2 ) )` from `src/Assets/Registrar.php` resolves to plugin root URL | Pattern 2 | LOW -- can be verified immediately at implementation. Alternative: pass plugin file path from bootstrap. |
| A4 | Elementor wraps widget output in `.elementor-widget-{widget_name}` class automatically | Pitfall 4 | LOW -- well-documented Elementor behavior |

**If this table is empty:** All claims in this research were verified or cited -- no user confirmation needed.

Since A1-A4 are all LOW risk and have simple fallbacks, no user confirmation is strictly required.

## Open Questions (RESOLVED)

1. **Widget icon approach verification** — RESOLVED
   - What we know: Elementor's `get_icon()` accepts icon class strings (e.g., `eicon-menu-bar`). Data URI SVGs work in practice based on community examples.
   - What was unclear: Whether data URI is the officially recommended approach for custom widget icons.
   - **Resolution:** Use data URI SVG as the primary approach (confirmed in CONTEXT.md D-03). Fallback to `eicon-menu-bar` if data URI fails during testing. Risk: LOW.

2. **Icon rendering return value for concatenation** — RESOLVED
   - What we know: `\Elementor\Icons_Manager::render_icon()` defaults to echo. The 4th parameter `$return` exists.
   - What was unclear: Exact signature in current Elementor version.
   - **Resolution:** Use `ob_start()` / `ob_get_clean()` as the primary approach for capturing icon output as a string. This is a well-known PHP pattern that works regardless of the exact `render_icon()` signature. Risk: LOW.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.1+ | Plugin runtime | Yes | 8.4.15 (cli) | -- |
| WordPress | Plugin platform | N/A (runtime) | -- | Requires active WP instance for testing |
| Elementor | Widget registration | N/A (runtime) | -- | Requires active Elementor for widget testing |
| Node.js | Not used | Yes | 24.15.0 | N/A |
| WP-CLI | Optional testing | No | -- | Manual WordPress testing |

**Missing dependencies with no fallback:**
- WordPress runtime environment: Required for testing widget registration, controls rendering, and asset loading. Plugin code can be written without it, but end-to-end testing requires an active WordPress + Elementor installation.

**Missing dependencies with fallback:**
- WP-CLI: Not essential. Manual WordPress admin testing is the fallback for verifying widget appearance and asset loading.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (WordPress standard) -- NOT setup in Phase 1 |
| Config file | None -- Phase 1 creates production code only |
| Quick run command | N/A -- no test framework in Phase 1 |
| Full suite command | N/A |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PLUG-01 | Autoloader maps namespace to files | Manual | N/A | No -- Wave 0 |
| PLUG-02 | Plugin singleton initializes on plugins_loaded | Manual | N/A | No -- Wave 0 |
| PLUG-03 | Admin notice when Elementor inactive | Manual | N/A | No -- Wave 0 |
| PLUG-04 | Plugin header correct | Manual | N/A | No -- Wave 0 |
| PLUG-05 | Conditional asset loading | Manual | N/A | No -- Wave 0 |
| PLUG-06 | Output escaping | Code review | N/A | No -- Wave 0 |
| WIDG-01 | Widget registers on correct hook | Manual | N/A | No -- Wave 0 |
| WIDG-02 | Widget extends Widget_Base correctly | Manual | N/A | No -- Wave 0 |
| WIDG-03 | Custom SVG widget icon | Manual | N/A | No -- Wave 0 |
| WIDG-04 | Script/style dependency declaration | Manual | N/A | No -- Wave 0 |
| TRIG-01 | Trigger is button element | Manual | N/A | No -- Wave 0 |
| TRIG-02 | Hamburger spans render | Manual | N/A | No -- Wave 0 |
| TRIG-03 | Custom Icon control works | Manual | N/A | No -- Wave 0 |
| TRIG-04 | Text Only renders | Manual | N/A | No -- Wave 0 |
| TRIG-05 | Icon + Text with position | Manual | N/A | No -- Wave 0 |
| TRIG-06 | aria-expanded and aria-controls | Manual | N/A | No -- Wave 0 |
| COMP-01 | WP 6.5+, PHP 8.1+ compatible | Manual | N/A | No -- Wave 0 |
| COMP-02 | Works with Elementor Free and Pro | Manual | N/A | No -- Wave 0 |
| COMP-05 | No theme-specific CSS | Code review | N/A | No -- Wave 0 |

### Sampling Rate
- **Per task commit:** Manual verification in WordPress admin + Elementor editor
- **Per wave merge:** Full manual walkthrough of all success criteria
- **Phase gate:** All 5 success criteria verified before proceeding to Phase 2

### Wave 0 Gaps
- [ ] PHPUnit setup and configuration for WordPress plugin testing
- [ ] Test suite for autoloader namespace mapping
- [ ] Test suite for admin notice conditions
- [ ] Test suite for widget output HTML structure
- [ ] Test suite for output escaping verification

**Note:** Phase 1 is a greenfield foundation phase. All requirements are best verified through manual testing in an active WordPress + Elementor environment. Automated tests can be retrofitted in a later phase or as a separate quality assurance pass. The primary validation is the 5 success criteria listed in the phase description.

## Sources

### Primary (HIGH confidence)
- [Elementor Developer Docs - Registering Widgets](https://developers.elementor.com/docs/managers/registering-widgets/) -- Widget registration API, `elementor/widgets/register` hook
- [Elementor Developer Docs - Widget Dependencies](https://developers.elementor.com/docs/widgets/widget-dependencies/) -- `get_script_depends()`, `get_style_depends()`, conditional loading
- [Elementor Developer Docs - Icons Control](https://developers.elementor.com/docs/editor-controls/control-icons/) -- ICONS control, `Icons_Manager::render_icon()`, return value format
- [Elementor Developer Docs - Conditional Display](https://developers.elementor.com/docs/editor-controls/conditional-display/) -- `condition` argument, `conditions` argument, operators
- [Elementor Developer Docs - Widget Categories](https://developers.elementor.com/docs/hooks/widget-categories/) -- `elementor/elements/categories_registered` hook
- [Elementor Developer Docs - Select Control](https://developers.elementor.com/docs/editor-controls/control-select/) -- SELECT control API

### Secondary (MEDIUM confidence)
- [Codeable - Elementor Widget Development Best Practices](https://www.codeable.io/blog/elementor-widget-development/) -- Widget structure patterns, security practices
- [.planning/research/ARCHITECTURE.md](file:///.planning/research/ARCHITECTURE.md) -- Full system architecture, component design, data flows
- [.planning/research/STACK.md](file:///.planning/research/STACK.md) -- Technology stack decisions
- [.planning/research/PITFALLS.md](file:///.planning/research/PITFALLS.md) -- Known pitfalls and recovery strategies

### Tertiary (LOW confidence)
- [GitHub Issue #7623](https://github.com/elementor/elementor/issues/7623) -- `get_script_depends()` cannot access settings in editor mode

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all APIs verified against official Elementor docs
- Architecture: HIGH -- patterns from ARCHITECTURE.md, confirmed by official docs
- Pitfalls: HIGH -- corroborated by official docs and project-specific research
- Code examples: HIGH -- based on verified Elementor API signatures

**Research date:** 2026-06-12
**Valid until:** 2026-07-12 (Elementor API is stable across minor versions)
