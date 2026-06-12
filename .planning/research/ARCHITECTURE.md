# Architecture Research

**Domain:** WordPress Elementor Widget Plugin (Drill-Down Mobile Menu)
**Researched:** 2026-06-12
**Confidence:** HIGH

## Standard Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                     WordPress Plugin Bootstrap                       │
│                     devsroom-drilldown-mobile-menu.php               │
├─────────────────────────────────────────────────────────────────────┤
│                        PSR-4 Autoloader                              │
│                  spl_autoload_register() → src/ mapping              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────┐   ┌──────────────┐   ┌──────────────────────┐     │
│  │   Plugin      │   │  Elementor   │   │  Admin Notice        │     │
│  │   Bootstrap   │──▶│  Widget      │   │  (Elementor Check)   │     │
│  │   (Init)      │   │  (DDMM)      │   └──────────────────────┘     │
│  └──────┬───────┘   └──────┬───────┘                                 │
│         │                  │                                         │
│         ▼                  ▼                                         │
│  ┌──────────────┐   ┌──────────────┐   ┌──────────────────────┐     │
│  │  Asset       │   │  Menu Tree   │   │  Custom Menu Tree    │     │
│  │  Registrar   │   │  Builder     │   │  Builder (Repeater)  │     │
│  │              │   │  (WP Nav)    │   │  (Stack-Based)       │     │
│  └──────────────┘   └──────┬───────┘   └──────────┬───────────┘     │
│                            │                       │                  │
│                            ▼                       ▼                  │
│                     ┌──────────────────────────────────┐             │
│                     │       Widget Renderer             │             │
│                     │  (HTML output with data attrs)    │             │
│                     └──────────────┬───────────────────┘             │
│                                    │                                  │
├────────────────────────────────────┼──────────────────────────────────┤
│           BROWSER / FRONTEND       │                                  │
│                                    ▼                                  │
│                     ┌──────────────────────────────────┐             │
│                     │     ddmm-frontend.js (ES6)       │             │
│                     │  ┌─────────┐  ┌──────────────┐   │             │
│                     │  │ Drawer  │  │  Panel Nav   │   │             │
│                     │  │ Manager │  │  (Drill-Down) │   │             │
│                     │  └─────────┘  └──────────────┘   │             │
│                     │  ┌─────────┐  ┌──────────────┐   │             │
│                     │  │ Keyboard│  │   Search     │   │             │
│                     │  │ Handler │  │   Handler    │   │             │
│                     │  └─────────┘  └──────────────┘   │             │
│                     └──────────────────────────────────┘             │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Implementation |
|-----------|----------------|----------------|
| **Plugin Bootstrap** | Entry point, Elementor dependency check, hook registration, autoloader init | Main PHP file with `plugins_loaded` action |
| **PSR-4 Autoloader** | Maps `Devsroom_DDMM\` namespace to `src/` directory | `spl_autoload_register()` callback, no Composer |
| **Admin Notice** | Warns when Elementor is inactive | Checks `did_action('elementor/loaded')`, shows admin notice |
| **Elementor Widget** | Registers widget with Elementor, defines controls, renders output | Extends `\Elementor\Widget_Base`, hooks `elementor/widgets/register` |
| **Asset Registrar** | Registers (not enqueues) JS/CSS with WordPress; Elementor loads conditionally | `wp_register_script/style` on `wp_enqueue_scripts`; widget declares via `get_script_depends()` / `get_style_depends()` |
| **WP Menu Tree Builder** | Converts flat `wp_get_nav_menu_items()` into nested tree structure | 3-pass ID-based algorithm (no PHP references) |
| **Custom Menu Tree Builder** | Converts flat repeater items with Depth field into nested tree | Stack-based depth-field algorithm |
| **Widget Renderer** | Outputs drawer HTML with panels, data attributes for JS targeting | PHP `render()` method on widget class |
| **Frontend JS (Drawer Manager)** | Handles trigger click, drawer open/close, overlay, focus trap | Pure ES6 class, initialized via `elementor/frontend/init` + `DOMContentLoaded` |
| **Frontend JS (Panel Nav)** | Handles drill-down navigation: panel sliding, back button, ID-based lookup | Pure ES6 class, uses `data-target` to `data-panel-id` mapping |
| **Frontend JS (Keyboard Handler)** | Escape to back/close, Tab trap, arrow key navigation, Enter/Space | Pure ES6, event delegation on drawer container |
| **Frontend JS (Search Handler)** | Optional search box filtering of current panel items | Pure ES6, live filter on input |

## Recommended Project Structure

```
devsroom-drilldown-mobile-menu/
├── devsroom-drilldown-mobile-menu.php    # Plugin entry point / bootstrap
├── src/                                  # PSR-4 root (namespace: Devsroom_DDMM\)
│   ├── Plugin.php                        # Main plugin class (init, hooks, checks)
│   ├── Admin/
│   │   └── ElementorNotice.php           # Admin notice when Elementor missing
│   ├── Assets/
│   │   └── Registrar.php                 # Script/style registration
│   ├── Elementor/
│   │   └── Widget/
│   │       └── DrillDownMenu.php         # Main widget class
│   ├── MenuBuilder/
│   │   ├── WpNavTree.php                 # WP nav_menu → nested tree
│   │   └── CustomRepeaterTree.php        # Repeater + Depth field → nested tree
│   └── Rendering/
│       └── DrawerRenderer.php            # HTML output builder
├── assets/
│   ├── css/
│   │   └── ddmm-frontend.css             # Drawer, panel, animation styles
│   └── js/
│       └── ddmm-frontend.js              # Pure ES6 frontend module
├── languages/                            # .pot file for translation
│   └── devsroom-drilldown-mobile-menu.pot
└── readme.txt                            # WordPress plugin readme
```

### Structure Rationale

- **`src/` as PSR-4 root:** Standard PHP convention. Namespace `Devsroom_DDMM\` maps directly to `src/`. Subdirectories match sub-namespaces (e.g., `Devsroom_DDMM\MenuBuilder\WpNavTree` at `src/MenuBuilder/WpNavTree.php`). Case-sensitive: directory names must match namespace casing exactly.

- **Separate `MenuBuilder/` from Widget:** The tree-building algorithms are pure logic with no Elementor dependency. Isolating them makes them testable independently and keeps the widget class focused on Elementor integration (controls + rendering).

- **Separate `Rendering/` from Widget:** The drawer HTML generation is complex (panels, data attributes, nesting). Extracting it from the widget class keeps `render()` readable and allows the renderer to work with either menu source (WP nav or custom repeater).

- **`Assets/Registrar.php` handles registration only:** Per Elementor's official recommendation, scripts are registered via `wp_register_script()` on `wp_enqueue_scripts`, then the widget declares them via `get_script_depends()` / `get_style_depends()`. Elementor only enqueues them when the widget is present on the page. No manual widget-detection logic needed.

## Architectural Patterns

### Pattern 1: Elementor Widget Registration via Hook

**What:** The widget class is instantiated and registered on the `elementor/widgets/register` action hook, not during plugin boot.
**When to use:** Always for Elementor widget plugins.
**Trade-offs:** Decouples widget from plugin lifecycle. Slight complexity: registration happens after `plugins_loaded`, so the widget class cannot be used before that hook fires.

**Example:**
```php
// src/Plugin.php
namespace Devsroom_DDMM;

use Devsroom_DDMM\Elementor\Widget\DrillDownMenu;

class Plugin {
    public function init(): void {
        add_action('elementor/widgets/register', [$this, 'registerWidgets']);
    }

    public function registerWidgets(\Elementor\Widgets_Manager $manager): void {
        $manager->register(new DrillDownMenu());
    }
}
```

### Pattern 2: PSR-4 Autoloader Without Composer

**What:** Custom `spl_autoload_register()` callback that maps namespace prefixes to file paths. No Composer dependency, so end users do not need to run `composer install`.
**When to use:** WordPress plugins distributed to non-developers who will install via ZIP upload.
**Trade-offs:** Slightly more manual setup than Composer, but zero dependency on build tools for end users. Must be case-sensitive (directory casing must match namespace casing).

**Example:**
```php
// In main plugin file or src/Plugin.php
spl_autoload_register(function (string $class): void {
    $prefix = 'Devsroom_DDMM\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
```

### Pattern 3: Conditional Asset Loading via get_script_depends / get_style_depends

**What:** Register scripts/styles globally (but do not enqueue). Declare them as dependencies in the widget class. Elementor automatically enqueues only when the widget is rendered on the page.
**When to use:** Always for Elementor widget plugins to avoid loading JS/CSS on pages that do not use the widget.
**Trade-offs:** Scripts must be registered before Elementor renders. The registration hook (`wp_enqueue_scripts`) fires early, but Elementor's conditional loading is handled internally -- you do not need to detect widget presence yourself.

**Example:**
```php
// src/Assets/Registrar.php
namespace Devsroom_DDMM\Assets;

class Registrar {
    public function register(): void {
        add_action('wp_enqueue_scripts', function () {
            wp_register_script(
                'ddmm-frontend',
                plugins_url('assets/js/ddmm-frontend.js', dirname(__DIR__, 2)),
                [],
                '0.0.01',
                true  // Load in footer
            );
            wp_register_style(
                'ddmm-frontend',
                plugins_url('assets/css/ddmm-frontend.css', dirname(__DIR__, 2)),
                [],
                '0.0.01'
            );
        });
    }
}

// In the widget class:
public function get_script_depends(): array {
    return ['ddmm-frontend'];
}

public function get_style_depends(): array {
    return ['ddmm-frontend'];
}
```

### Pattern 4: 3-Pass WP Menu Tree Builder (No PHP References)

**What:** Convert the flat array from `wp_get_nav_menu_items()` into a nested parent-child tree using three sequential passes: (1) index all items by ID, (2) link children to parents, (3) extract root items.
**When to use:** When building hierarchical structures from WordPress nav menus. Avoids PHP `foreach &$ref` which corrupts arrays under certain conditions.
**Trade-offs:** Three passes instead of one, but the data is small (menu items) so performance is negligible. Much safer than reference-based approaches.

**Example:**
```php
// Pass 1: Index by ID
$indexed = [];
foreach ($items as $item) {
    $indexed[$item->db_id] = (object) [
        'id'       => $item->db_id,
        'parent'   => (int) $item->menu_item_parent,
        'title'    => $item->title,
        'url'      => $item->url,
        'target'   => $item->target,
        'children' => [],
    ];
}

// Pass 2: Link children to parents
$rootIds = [];
foreach ($indexed as $id => $node) {
    if ($node->parent > 0 && isset($indexed[$node->parent])) {
        $indexed[$node->parent]->children[] = $node;
    } else {
        $rootIds[] = $id;
    }
}

// Pass 3: Extract roots
$tree = [];
foreach ($rootIds as $id) {
    $tree[] = $indexed[$id];
}
```

### Pattern 5: Stack-Based Depth-Field Tree for Custom Repeater

**What:** The Elementor repeater stores items as a flat list where each item has a Depth field (0 = root, 1 = child, 2 = grandchild). A stack-based algorithm converts this to a nested tree without nested repeaters.
**When to use:** When Elementor's native nested repeater is too complex or not available without Pro/Nested Elements module. Flat repeater with depth field gives simpler UX.
**Trade-offs:** The editor shows a flat list with indent dashes rather than true visual nesting. But it avoids the Pro dependency and is easier to manage.

**Example:**
```php
// Flat repeater items with 'depth' field → nested tree
$stack = [];  // Stack tracks current ancestry path
$tree = [];

foreach ($items as $item) {
    $depth = (int) ($item['depth'] ?? 0);
    $node = [
        'label'    => $item['label'],
        'url'      => $item['url'],
        'target'   => $item['target'] ?? '',
        'icon'     => $item['icon'] ?? '',
        'children' => [],
    ];

    // Trim stack to current depth
    while (count($stack) > $depth) {
        array_pop($stack);
    }

    if ($depth === 0) {
        $tree[] = $node;
        $stack = [&$tree[count($tree) - 1]];
    } else {
        $parent = &$stack[count($stack) - 1];
        $parent['children'][] = $node;
        $stack[] = &$parent['children'][count($parent['children']) - 1];
    }
    unset($node);
}
```

### Pattern 6: Dual-Path JS Initialization

**What:** Frontend JavaScript initializes via both `elementor/frontend/init` (for published Elementor pages) and `DOMContentLoaded` (fallback for edge cases, AJAX-loaded content, or Elementor not fully ready).
**When to use:** Always for Elementor widget frontend scripts to handle both normal rendering and Elementor preview/AJAX scenarios.
**Trade-offs:** Slightly more complex init code. Must use a double-init guard (e.g., `data-ddmm-init` attribute) to prevent duplicate initialization.

**Example:**
```javascript
// ddmm-frontend.js
class DrillDownMenu {
    init(container) {
        if (container.dataset.ddmmInit) return;
        container.dataset.ddmmInit = 'true';
        // ... initialize drawer, panels, keyboard, search
    }
}

const ddmm = new DrillDownMenu();

// Path 1: Elementor frontend init
if (typeof elementorFrontend !== 'undefined') {
    elementorFrontend.hooks.addAction(
        'frontend/element_ready/ddmm_drilldown_menu.default',
        ($scope) => {
            ddmm.init($scope[0] ?? $scope);
        }
    );
}

// Path 2: DOMContentLoaded fallback
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ddmm-widget').forEach((el) => {
        ddmm.init(el);
    });
});
```

## Data Flow

### Admin/Editor Data Flow (Menu Source Selection)

```
Elementor Editor
    │
    ├── User selects "WP Menu" source
    │       │
    │       ▼
    │   Widget control: dropdown of wp_get_nav_menus()
    │       │
    │       ▼ (on page render)
    │   WpNavTree::build($menu_slug)
    │       │
    │       ▼
    │   wp_get_nav_menu_items() → flat array
    │       │
    │       ▼
    │   3-pass algorithm → nested tree
    │       │
    │       ▼
    │   DrawerRenderer::render($tree)
    │
    └── User selects "Custom Menu" source
            │
            ▼
        Widget control: Repeater with Label, URL, Depth, Icon, Target
            │
            ▼ (on page render)
        CustomRepeaterTree::build($repeater_items)
            │
            ▼
        Stack-based depth algorithm → nested tree
            │
            ▼
        DrawerRenderer::render($tree)
```

### Frontend Interaction Data Flow

```
User clicks trigger (hamburger button)
    │
    ▼
DrawerManager.open()
    ├── Adds 'ddmm-open' class to drawer wrapper
    ├── Shows overlay
    ├── Sets focus to first menu item
    └── Enables keyboard trap
    │
User taps parent menu item (has children)
    │
    ▼ (reads data-target="panel-{id}" from clicked element)
PanelNav.drillDown(targetId)
    ├── Slides current panel left (CSS transition)
    ├── Slides target panel in from right (via data-panel-id match)
    ├── Updates back button target (data-back-target)
    └── Pushes current panel ID to history stack
    │
User taps ← Back button
    │
    ▼
PanelNav.goBack()
    ├── Pops from history stack
    ├── Slides current panel right
    ├── Slides previous panel in from left
    └── Restores focus to triggering parent item
    │
User clicks link (leaf item, no children)
    │
    ▼
DrawerManager.close()  (if "close on link click" enabled)
    ├── Navigates to URL
    └── OR closes drawer and navigates
```

### Rendering Data Flow (PHP to HTML)

```
Widget::render()
    │
    ├── Reads settings (menu_source, animation_type, etc.)
    │
    ├── Branches on menu_source:
    │   ├── 'wp_menu'  → WpNavTree::build($settings['menu_slug'])
    │   └── 'custom'   → CustomRepeaterTree::build($settings['menu_items'])
    │
    ├── Passes tree to DrawerRenderer::render($tree, $settings)
    │
    ▼
HTML Output Structure:
    <div class="ddmm-widget" data-ddmm-id="...">
      <!-- Trigger Button -->
      <button class="ddmm-trigger" data-ddmm-trigger>...</button>
      
      <!-- Drawer Overlay -->
      <div class="ddmm-overlay" data-ddmm-overlay></div>
      
      <!-- Off-Canvas Drawer -->
      <div class="ddmm-drawer" data-ddmm-drawer>
        <!-- Drawer Header (logo/text/close) -->
        <div class="ddmm-header">...</div>
        
        <!-- Optional Search -->
        <div class="ddmm-search">...</div>
        
        <!-- Panel Container -->
        <div class="ddmm-panels">
          <!-- Root Panel -->
          <div class="ddmm-panel ddmm-panel--active" data-panel-id="root">
            <ul class="ddmm-menu">
              <li>
                <a href="..." data-target="panel-abc">Parent Item ›</a>
              </li>
              <!-- Child panel rendered immediately after parent </li> -->
              <div class="ddmm-panel" data-panel-id="panel-abc">
                <div class="ddmm-back-row">
                  <button data-back-target="root">← Back</button>
                  <span>Parent Item</span>
                </div>
                <ul class="ddmm-menu">...</ul>
              </div>
            </ul>
          </div>
        </div>
      </div>
    </div>
```

### Key Data Flows

1. **Menu Source to Tree:** User selects menu source in Elementor editor. On render, the appropriate tree builder converts the flat data into a nested tree structure. The tree is the canonical internal format that both sources produce.

2. **Tree to HTML:** The renderer walks the tree recursively, outputting each node as a menu item. Items with children get a `data-target` attribute pointing to a child panel ID. Child panels are rendered as sibling `<div>` elements with matching `data-panel-id` attributes, placed immediately after their parent `</li>` in DOM order.

3. **HTML to JS Navigation:** The frontend JS reads `data-target` on click, finds the matching `data-panel-id` panel via direct ID lookup (`querySelector`), and applies CSS transform classes for the slide animation. No positional heuristics -- direct ID-based matching ensures reliability at any nesting depth.

4. **Settings to CSS Custom Properties:** Animation type, duration, and easing are passed from Elementor settings to CSS custom properties on the drawer wrapper (e.g., `--ddmm-duration: 300ms; --ddmm-easing: ease-out;`). The CSS uses these variables for transitions. This avoids inline styles on every animated element.

## Component Communication Boundaries

```
┌─────────────────────────────────────────────────────────────────┐
│                         PHP Backend                             │
│                                                                  │
│  Plugin.php ──registers──▶ Elementor\Widget\DrillDownMenu.php   │
│      │                        │                                  │
│      │                        ├── uses ──▶ MenuBuilder\*        │
│      │                        ├── uses ──▶ Rendering\*          │
│      │                        └── depends ─▶ Assets\Registrar   │
│      │                                                          │
│      └──calls──▶ Assets\Registrar.php (register scripts/styles)│
│                                                                  │
│  Communication: Direct method calls, constructor injection.     │
│  No cross-widget messaging. Single widget plugin.               │
├─────────────────────────────────────────────────────────────────┤
│                      PHP → Browser Bridge                       │
│                                                                  │
│  DrawerRenderer outputs HTML with data-* attributes             │
│  Elementor settings → CSS custom properties on wrapper          │
│  wp_localize_script or data-* for any PHP→JS config             │
├─────────────────────────────────────────────────────────────────┤
│                     Browser / Frontend JS                        │
│                                                                  │
│  DrillDownMenu (main class)                                     │
│      ├── creates DrawerManager (open/close/overlay)             │
│      ├── creates PanelNav (drill-down/back)                     │
│      ├── creates KeyboardHandler (a11y)                         │
│      └── creates SearchHandler (optional filter)                │
│                                                                  │
│  Communication: Shared DOM container reference.                 │
│  Components communicate via DOM attributes and custom events.   │
│  No global state; each widget instance is independent.          │
└─────────────────────────────────────────────────────────────────┘
```

### Internal Boundaries

| Boundary | Communication | Direction | Notes |
|----------|---------------|-----------|-------|
| Plugin Bootstrap → Widget | Instantiation + registration | One-way (down) | Bootstrap creates widget instance, passes to Elementor manager |
| Widget → Tree Builders | Method call with data, returns tree | Request/response | Widget calls `build()`, builder returns array tree. No side effects. |
| Widget → Renderer | Method call with tree + settings, returns HTML | Request/response | Renderer is stateless. Pure function: (tree, settings) -> HTML string |
| Widget → Asset Registrar | Via `get_script_depends()` / `get_style_depends()` | Declarative | Widget declares handle names; Elementor resolves and enqueues |
| DrawerManager ↔ PanelNav | Custom DOM events or direct method calls | Bidirectional | Drawer open enables PanelNav; PanelNav can request drawer close |
| PanelNav → DrawerManager | Custom event `ddmm:close-request` | One-way (up) | When user clicks leaf link and "close on click" is enabled |

## Build Order (Dependencies Between Components)

This is the order in which components should be built, based on dependency chains:

```
Phase 1: Foundation (no dependencies)
    1.1  Plugin Bootstrap + PSR-4 Autoloader
    1.2  Admin Notice (Elementor dependency check)

Phase 2: Widget Shell (depends on 1.1)
    2.1  Asset Registrar (register scripts/styles)
    2.2  Widget class skeleton (extends Widget_Base, registers, has empty render)
    2.3  Basic controls: trigger button, drawer display toggle
    2.4  Trigger button rendering + basic drawer container rendering

Phase 3: Menu Data Layer (depends on 1.1, no Elementor dependency)
    3.1  WpNavTree builder (3-pass algorithm)
    3.2  CustomRepeaterTree builder (stack-based depth algorithm)
         Can be developed/tested with unit-style PHP without WordPress

Phase 4: Full Rendering (depends on 2.2, 3.1, 3.2)
    4.1  DrawerRenderer: root panel from tree
    4.2  DrawerRenderer: child panels with data-target / data-panel-id
    4.3  DrawerRenderer: header (logo/text/close)
    4.4  DrawerRenderer: back button rows
    4.5  All Content Tab controls (menu source, repeater fields, search toggle)

Phase 5: Frontend Core (depends on 2.1, 4.1-4.4)
    5.1  DrawerManager: open/close/overlay (CSS classes + transitions)
    5.2  PanelNav: drill-down navigation via data-target → data-panel-id
    5.3  PanelNav: back button navigation with history stack
    5.4  Double-init guard (data-ddmm-init attribute)
    5.5  Dual-path init (elementor/frontend/init + DOMContentLoaded)

Phase 6: Frontend Enhancements (depends on 5.x)
    6.1  KeyboardHandler: Escape, Tab trap, Arrow keys, Enter/Space
    6.2  SearchHandler: optional search box filtering
    6.3  Close-on-link-click behavior
    6.4  Auto-open current page path
    6.5  Animation variants (Slide, Fade, Scale, Slide+Fade)

Phase 7: Style Tab Controls (depends on 2.2)
    7.1  Trigger button styling (Normal/Hover/Active)
    7.2  Drawer styling (width, background, padding)
    7.3  Header styling
    7.4  Menu item styling (Normal/Hover/Active)
    7.5  Back row styling
    7.6  Search box styling
    7.7  CSS custom properties for animation config

Phase 8: Polish (depends on all above)
    8.1  WooCommerce menu item compatibility
    8.2  Translation readiness (text domain, esc_html__)
    8.3  Plugin admin notice styling
    8.4  Conditional asset loading verification
```

### Build Order Rationale

1. **Foundation first** because everything depends on the autoloader working and Elementor being present.
2. **Widget shell before menu logic** because the widget is the integration point. An empty widget that shows up in Elementor validates the registration pipeline.
3. **Menu builders are independent** of Elementor -- they are pure PHP logic that takes data in and returns a tree. This means they can be developed and tested in isolation.
4. **Rendering depends on both widget and menu builders** -- it needs the Elementor settings API and the tree structure.
5. **Frontend JS depends on rendered HTML** -- the JS reads data attributes from the DOM, so the PHP rendering must be stable before JS development.
6. **Style Tab is last** because it is purely cosmetic configuration. The widget must function before it can be styled.
7. **Polish items are additive** and do not block core functionality.

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| Single site, < 50 menu items | No adjustments needed. This is the target. All rendering is server-side on page load. |
| Single site, 200+ menu items | Consider caching the rendered drawer HTML in WordPress transients. Rebuild on menu save. The tree builders are O(n) so performance is fine, but HTML string generation for 200+ nested items could be cached. |
| Multi-site / multisite | Plugin works per-site. No cross-site considerations. Menu data is site-scoped in WordPress. |
| Page caching (WP Rocket, etc.) | Rendered HTML is static (no dynamic data per request), so page caching works natively. JS initializes on DOMContentLoaded regardless of cache. No AJAX needed for core functionality. |

### Scaling Priorities

1. **First concern: Menu item count.** With very deep nesting (5+ levels) and many items (100+), the HTML output grows. The 3-pass tree builder handles this efficiently. If rendering becomes slow, cache the output with a transient keyed on menu slug + settings hash.

2. **Second concern: Asset size.** The single JS file should remain small (under 15KB minified). No framework dependency. CSS custom properties for animation config avoid JS-driven style manipulation.

## Anti-Patterns

### Anti-Pattern 1: PHP References for Tree Building

**What people do:** Use `foreach ($items as &$item)` with PHP references to build parent-child links in a single pass.
**Why it's wrong:** PHP references in foreach loops can silently corrupt the array. If the same variable name `$item` is reused later in the same scope, the reference persists and overwrites data. This was a documented bug in prior development (v1.3.0) causing menu corruption.
**Do this instead:** Use the 3-pass ID-based approach: index by ID, link children by ID lookup, extract roots. No references needed.

### Anti-Pattern 2: Positional Panel Navigation

**What people do:** Use array indices or DOM child position to determine which panel to show next when a parent item is clicked.
**Why it's wrong:** Positional heuristics break at depth. If items are reordered, or if child panels are inserted between siblings, the index mapping becomes wrong. This was a documented bug in prior development.
**Do this instead:** Direct ID-based lookup. Each parent item has `data-target="panel-{uniqid}"`, and the corresponding child panel has `data-panel-id="panel-{uniqid}"`. Navigation is always `querySelector('[data-panel-id="' + targetId + '"]')`. This works at any nesting depth regardless of DOM ordering.

### Anti-Pattern 3: Global JS State for Widget Instances

**What people do:** Use global variables or `window.ddmm` to store widget state, assuming only one instance per page.
**Why it's wrong:** Elementor allows multiple widget instances on one page. Global state would cause conflicts between instances.
**Do this instead:** Each `DrillDownMenu` instance scopes to its container element. All DOM queries are scoped: `container.querySelector(...)` not `document.querySelector(...)`. State is stored as instance properties, not globals.

### Anti-Pattern 4: jQuery Dependency

**What people do:** Use `jQuery()` or `$()` for DOM manipulation, event binding, and animations.
**Why it's wrong:** Project constraint requires pure ES6 with zero jQuery dependency. WordPress is moving away from jQuery. jQuery adds unnecessary load (~30KB) for functionality achievable with native DOM APIs.
**Do this instead:** Use `document.querySelectorAll()`, `addEventListener()`, `classList.toggle()`, `element.style.transform = ...` for all DOM interaction. CSS transitions handle animations.

### Anti-Pattern 5: Eager Asset Enqueuing

**What people do:** Call `wp_enqueue_script()` / `wp_enqueue_style()` globally, loading CSS/JS on every page regardless of widget presence.
**Why it's wrong:** Wastes bandwidth and slows page load on pages that do not use the mobile menu widget.
**Do this instead:** Use `wp_register_script()` (register only), then declare handles in `get_script_depends()` / `get_style_depends()`. Elementor enqueues conditionally based on widget presence. This is the official Elementor recommendation.

### Anti-Pattern 6: Nested Repeaters for Custom Menu

**What people do:** Use Elementor's nested repeater (repeater inside repeater) to represent menu hierarchy in the editor.
**Why it's wrong:** Nested repeaters require Elementor Pro or the Nested Elements module. They create a confusing UX for deep nesting (3+ levels). The editor becomes slow and confusing with deeply nested UI.
**Do this instead:** Flat repeater with a Depth field. Items are listed sequentially with indent dashes (—, ——, ———) showing hierarchy visually. The stack-based algorithm converts this to a tree at render time. Simpler UX, no Pro dependency.

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Elementor (Free or Pro) | `elementor/widgets/register` hook, `Widget_Base` extension | Must check `did_action('elementor/loaded')` before registering. Widget works with both Free and Pro. |
| WordPress Nav Menus | `wp_get_nav_menu_items()`, `wp_get_nav_menus()` | Returns flat array of menu item objects. Each has `db_id`, `menu_item_parent`, `title`, `url`, `target`. Must convert to tree. |
| WooCommerce (optional) | Menu items for Cart, My Account, Checkout, Shop | No direct API integration. WooCommerce adds its own menu items that appear in `wp_get_nav_menu_items()`. The widget renders them like any other item. |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| PHP Backend → Frontend JS | HTML data-* attributes + CSS custom properties | No `wp_localize_script` needed if all config is in data attributes and CSS variables. Cleaner separation. |
| Asset Registrar → Elementor | `get_script_depends()` / `get_style_depends()` | Registrar registers handles; widget declares them; Elementor enqueues when needed. |
| Tree Builders → Renderer | PHP array (tree structure) | Pure data contract. Renderer does not know or care which builder produced the tree. |
| Frontend JS components | Shared container element reference + custom events | Components are classes instantiated with the container DOM element. They communicate by reading DOM state and dispatching custom events. |

## Sources

- [Elementor Widget Dependencies (Official Docs)](https://developers.elementor.com/docs/widgets/widget-dependencies/) -- HIGH confidence. Official documentation on `get_script_depends()` and `get_style_depends()` for conditional asset loading.
- [Elementor Scripts & Styles (Official Docs)](https://developers.elementor.com/docs/scripts-styles/) -- HIGH confidence. Explains static vs dynamic loading, correct hooks to use.
- [Elementor Rendering Repeaters (Official Docs)](https://developers.elementor.com/docs/widgets/rendering-repeaters/) -- HIGH confidence. Official pattern for iterating repeater items in `render()`.
- [Elementor Widget Development Best Practices (Codeable)](https://www.codeable.io/blog/elementor-widget-development/) -- HIGH confidence. Published June 2025. Covers widget structure, registration, controls, rendering.
- [Using PHP Namespaces And Autoloaders In WordPress Plugins (JoshPress)](https://joshpress.net/blog/php-namespaces) -- HIGH confidence. Direct reference for PSR-4 autoloading without Composer in WordPress plugins.
- [Implementing Namespaces and Coding Standards (WordPress Developer Blog)](https://developer.wordpress.org/news/2025/09/implementing-namespaces-and-coding-standards-in-wordpress-plugin-development/) -- HIGH confidence. Official WordPress developer blog, September 2025.
- [Build a Multilevel Animated Mobile Menu (TutsPlus)](https://webdesign.tutsplus.com/build-a-multilevel-animated-mobile-menu-with-javascript--cms-38817t) -- MEDIUM confidence. Tutorial for drill-down panel navigation architecture with pure JavaScript.
- [PSR-4 Autoload in WordPress Plugin (Stack Overflow)](https://stackoverflow.com/questions/52633505/php-psr-4-autoload-in-wordpress-plugin-namespace) -- HIGH confidence. Community-verified patterns for PSR-4 case sensitivity and path mapping.
- [How Elementor Improved Asset Loading (Elementor Developers)](https://developers.elementor.com/how-elementor-improved-asset-loading-and-made-your-website-run-faster/) -- HIGH confidence. Official blog on conditional asset registration mechanism.

---
*Architecture research for: WordPress Elementor Drill-Down Mobile Menu Plugin*
*Researched: 2026-06-12*
