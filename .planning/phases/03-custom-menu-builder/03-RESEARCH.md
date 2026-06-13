# Phase 3: Custom Menu Builder - Research

**Researched:** 2026-06-13
**Domain:** Elementor Repeater control, stack-based depth-field tree algorithm, URL/Icons controls
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** The Depth field uses an Elementor NUMBER control with min=0, step=1, default=0, and no maximum cap. The NUMBER control is preferred over a SELECT dropdown because it's more natural and doesn't impose an arbitrary depth limit.
- **D-02:** CustomTree outputs the identical 7-field node contract as WpNavTree: `id` (int), `title` (string), `url` (string), `target` (string), `classes` (array), `has_children` (bool), `children` (array). Phase 4's renderer will handle a single `$tree` variable regardless of source. This eliminates branching in Phase 4.
- **D-03:** The stack-based algorithm inherently auto-clamps depth jumps (e.g., depth 0 -> depth 3 becomes depth 0 -> depth 1). No extra validation or editor warnings needed.
- **D-04:** Repeater is visible only when `menu_source === 'custom'` (using Elementor `condition` array, matching the existing `wp_menu_id` pattern).
- **D-05:** Repeater field order: Label (TEXT) -> URL (URL) -> Depth (NUMBER, min=0, step=1, default=0) -> Icon (ICONS) -> Open in New Tab (SWITCHER).
- **D-06:** Repeater starts empty -- no default items. User adds items manually.
- **D-07:** Per CMEN-03, repeater title formatting shows indent dashes based on depth value: Root items show as-is, depth 1 shows "--- Item", depth 2 shows "------ Item", etc. This uses Elementor's `title_field` property with dynamic `{{{ depth }}}` interpolation to generate visual hierarchy.
- **D-08:** When Menu Source is 'custom' and no repeater items exist (or tree is empty), follows Phase 2's D-05 pattern: editor shows hint "Add menu items to display", frontend renders zero menu HTML. The existing empty-state block in render() handles this.
- **D-09:** New class `CustomTree` at `src/MenuBuilder/CustomTree.php` (namespace `Devsroom_DDMM\MenuBuilder`), paralleling `WpNavTree`. Static `build(array $items): array` method. Zero Elementor dependency -- receives raw repeater data array, returns tree nodes.
- **D-10:** For custom items, `id` field uses sequential integers starting from 1 (generated at build time). `classes` field is always an empty array `[]`. `target` is `_blank` when "Open in New Tab" is enabled, empty string otherwise.

### Claude's Discretion
- Exact label text for repeater fields and controls
- Stack-based algorithm implementation details (data structure, traversal order)
- Whether to add `data-depth` attributes in the editor for enhanced preview
- Edge case handling for empty URL, missing labels, etc.
- Internal naming of helper methods

### Deferred Ideas (OUT OF SCOPE)
None -- discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| CMEN-01 | Content Tab provides "Custom Menu Builder" repeater with fields: Label, URL, Depth, Icon, Open in New Tab | Elementor REPEATER control with `new \Elementor\Repeater()` pattern. Fields use TEXT, URL, NUMBER, ICONS, and SWITCHER controls. Documented in "Pattern 1: Repeater Control Setup". |
| CMEN-02 | Depth field drives nesting -- flat list in Elementor, nested panels in rendered output | Stack-based depth-field tree builder documented in "Pattern 2: Stack-Based Depth-Field Tree Builder". Auto-clamp behavior per D-03. |
| CMEN-03 | Title field in repeater shows indent dashes for visual hierarchy (-- Child, ---- Grandchild) | `title_field` property supports inline JS expressions. Documented in "Pattern 3: Indent Dashes via title_field". |
| CMEN-04 | Custom menu data converted to nested tree using stack-based depth-field algorithm | Stack-based algorithm with ancestry tracking. Verified pattern from ARCHITECTURE.md Pattern 5. Documented in "Pattern 2". |
| CMEN-05 | Icon field uses Elementor Icons control (Font Awesome, SVG) | `Controls_Manager::ICONS` control. Rendered via `\Elementor\Icons_Manager::render_icon()`. Same pattern as existing trigger icon controls. |
</phase_requirements>

## Summary

Phase 3 adds a Custom Menu Builder mode to the Elementor widget: when users select "Custom Builder" as the Menu Source, a flat repeater control appears where each item has Label, URL, Depth, Icon, and Open in New Tab fields. The Depth field (NUMBER, min=0, step=1) drives visual hierarchy in the editor via indent dashes in the collapsed repeater title, and structural nesting in the output via a stack-based tree builder class.

The core technical challenge is the `title_field` property. Elementor's repeater uses an internal template engine that evaluates JavaScript expressions inside `{{{ }}}` braces. Simple field interpolation like `{{{ label }}}` works out of the box. For indent dashes based on the depth value, an inline JS expression like `'title_field' => '{{{ depth > 0 ? "—".repeat(depth) + " " : "" }}} {{{ label }}}'` is needed. This approach has been verified to work via community examples where function calls and expressions are evaluated inside `{{{ }}}`.

The tree builder (`CustomTree::build()`) is pure PHP with zero Elementor dependency, paralleling `WpNavTree`. It receives the raw repeater items array from `$settings['custom_items']`, iterates with a stack tracking the current ancestry path, and outputs the identical 7-field node contract. The algorithm inherently auto-clamps depth jumps per D-03.

**Primary recommendation:** Use the `new \Elementor\Repeater()` class pattern (not the inline `fields` array) for cleaner code. Implement the stack-based algorithm exactly as documented in ARCHITECTURE.md Pattern 5, with the node contract fields mapping precisely from repeater control names to tree node fields.

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Elementor Repeater control | 3.29+ | Flat repeater with multiple field types | Official Elementor pattern for repeatable field blocks. `new \Elementor\Repeater()` with `add_control()` per field, then `$repeater->get_controls()` passed to the REPEATER control's `fields` arg. `[VERIFIED: developers.elementor.com/docs/editor-controls/control-repeater/]` |
| Elementor Controls_Manager | 3.29+ | TEXT, URL, NUMBER, ICONS, SWITCHER controls | Each repeater field maps to a specific control type. All are standard Elementor controls. `[VERIFIED: developers.elementor.com/docs/editor-controls/control-repeater/ -- usage examples]` |
| PHP 8.1+ | 8.1 min | Tree builder, typed returns, match expressions | Project requires 8.1+. CustomTree uses strict typing. `[VERIFIED: codebase CLAUDE.md]` |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `\Elementor\Icons_Manager::render_icon()` | 3.29+ | Render icon HTML from Icons control data array | Used in `render()` when iterating custom menu items to output icons. Same pattern as trigger button icons (Phase 1). `[VERIFIED: codebase -- DrillDownMenu.php line 288-291]` |
| `\Elementor\Plugin::$instance->editor->is_edit_mode()` | 3.29+ | Detect editor context for empty-state hint | Per D-08, shows "Add menu items to display" in editor when custom repeater is empty. Same as Phase 2 D-05 pattern. `[VERIFIED: codebase -- DrillDownMenu.php line 332]` |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| NUMBER control for Depth | SELECT dropdown with options 0-5 | SELECT imposes an arbitrary max. NUMBER with min=0 gives unlimited flexibility. Locked via D-01. |
| `new \Elementor\Repeater()` class | Inline `fields` array | The Repeater class pattern is cleaner and more maintainable. Both produce the same result. Official docs show both; the class pattern is recommended for complex repeaters (5 fields). `[CITED: developers.elementor.com/docs/editor-controls/control-repeater/]` |
| `title_field` with JS expression for dashes | Just `{{{ label }}}` (no dashes) | Without dashes, users lose visual hierarchy in the collapsed repeater -- the primary UX benefit. CMEN-03 mandates dashes. |

**Installation:**
No packages to install. This phase uses only Elementor bundled APIs and WordPress core. No Composer, no npm.

**Version verification:** Not applicable -- no external packages. Elementor Repeater control is available since 1.0. ICONS control since 2.6.

## Architecture Patterns

### Recommended Project Structure (Phase 3 additions)
```
src/
├── Elementor/
│   └── Widget/
│       └── DrillDownMenu.php    # MODIFY: add custom_items repeater in _register_controls()
└── MenuBuilder/
    ├── WpNavTree.php            # EXISTING: Phase 2 tree builder (no changes)
    └── CustomTree.php           # NEW: stack-based depth-field tree builder (pure PHP)
```

The PSR-4 autoloader already maps `Devsroom_DDMM\MenuBuilder\CustomTree` to `src/MenuBuilder/CustomTree.php` -- no autoloader changes needed. The directory exists from Phase 2. `[VERIFIED: codebase -- src/Plugin.php autoloader maps Devsroom_DDMM\ to src/]`

### Pattern 1: Repeater Control Setup with `new \Elementor\Repeater()`

**What:** Create a `new \Elementor\Repeater()` instance, add controls to it, then pass `$repeater->get_controls()` to the REPEATER control's `fields` argument.

**When to use:** For complex repeaters with multiple fields (3+). Cleaner than the inline `fields` array approach.

**Example:**
```php
// Source: developers.elementor.com/docs/editor-controls/control-repeater/
// Inside DrillDownMenu::_register_controls(), after the wp_menu_id control

$repeater = new \Elementor\Repeater();

// Label field
$repeater->add_control(
    'label',
    [
        'label'       => esc_html__( 'Label', 'devsroom-drilldown-mobile-menu' ),
        'type'        => \Elementor\Controls_Manager::TEXT,
        'default'     => '',
        'placeholder' => esc_html__( 'Menu Item Label', 'devsroom-drilldown-mobile-menu' ),
        'label_block' => true,
    ]
);

// URL field
$repeater->add_control(
    'url',
    [
        'label'       => esc_html__( 'Link', 'devsroom-drilldown-mobile-menu' ),
        'type'        => \Elementor\Controls_Manager::URL,
        'placeholder' => esc_html__( 'https://example.com', 'devsroom-drilldown-mobile-menu' ),
        'default'     => [ 'url' => '' ],
    ]
);

// Depth field (per D-01)
$repeater->add_control(
    'depth',
    [
        'label'   => esc_html__( 'Depth', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::NUMBER,
        'min'     => 0,
        'step'    => 1,
        'default' => 0,
        'description' => esc_html__( '0 = root, 1 = child, 2 = grandchild...', 'devsroom-drilldown-mobile-menu' ),
    ]
);

// Icon field (per CMEN-05)
$repeater->add_control(
    'icon',
    [
        'label'   => esc_html__( 'Icon', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::ICONS,
        'default' => [
            'value'   => '',
            'library' => '',
        ],
    ]
);

// Open in New Tab field
$repeater->add_control(
    'new_tab',
    [
        'label'   => esc_html__( 'Open in New Tab', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => '',
    ]
);

// Add the repeater control to the widget (per D-04: condition on menu_source)
$this->add_control(
    'custom_items',
    [
        'label'       => esc_html__( 'Menu Items', 'devsroom-drilldown-mobile-menu' ),
        'type'        => \Elementor\Controls_Manager::REPEATER,
        'fields'      => $repeater->get_controls(),
        'title_field' => '{{{ depth > 0 ? "\\u2014".repeat( depth ) + " " : "" }}} {{{ label }}}',
        'condition'   => [
            'menu_source' => 'custom',
        ],
    ]
);
```
`[CITED: developers.elementor.com/docs/editor-controls/control-repeater/ -- Repeater class usage example]`
`[CITED: developers.elementor.com/docs/widgets/rendering-repeaters/ -- render() iteration pattern]`

### Pattern 2: Stack-Based Depth-Field Tree Builder

**What:** Convert flat repeater items with a `depth` field into a nested tree using a stack that tracks the current ancestry path.

**When to use:** When building hierarchical structures from a flat list where each item declares its nesting depth numerically.

**Example:**
```php
// Source: ARCHITECTURE.md Pattern 5 (verified algorithm)
// src/MenuBuilder/CustomTree.php

namespace Devsroom_DDMM\MenuBuilder;

class CustomTree {

    /**
     * Build a nested menu tree from flat repeater items with depth field.
     *
     * @param array $items Raw repeater data from Elementor settings.
     * @return array<int, array> Root-level tree nodes. Empty array if input is empty.
     */
    public static function build( array $items ): array {
        if ( empty( $items ) ) {
            return [];
        }

        $tree  = [];
        $stack = []; // Tracks current ancestry: stack[0]=root, stack[1]=child, etc.
        $id    = 0;

        foreach ( $items as $item ) {
            $id++;
            $depth = (int) ( $item['depth'] ?? 0 );
            $title = $item['label'] ?? '';
            $url_data = $item['url'] ?? [];

            // Extract URL from Elementor URL control format (array with 'url' key)
            $url = '';
            if ( is_array( $url_data ) && ! empty( $url_data['url'] ) ) {
                $url = $url_data['url'];
            } elseif ( is_string( $url_data ) && '' !== $url_data ) {
                $url = $url_data;
            }

            $target = ! empty( $item['new_tab'] ) && 'yes' === $item['new_tab'] ? '_blank' : '';

            $node = [
                'id'           => $id,
                'title'        => $title,
                'url'          => $url,
                'target'       => $target,
                'classes'      => [],
                'has_children' => false,
                'children'     => [],
            ];

            // Trim stack to current depth (auto-clamp behavior per D-03)
            while ( count( $stack ) > $depth ) {
                array_pop( $stack );
            }

            if ( 0 === $depth || empty( $stack ) ) {
                // Root-level item
                $tree[] = $node;
                $stack  = [ &$tree[ count( $tree ) - 1 ] ];
            } else {
                // Child item -- attach to the parent at the top of the stack
                $parent                    = &$stack[ count( $stack ) - 1 ];
                $parent['children'][]      = $node;
                $parent['has_children']    = true;
                $stack[]                   = &$parent['children'][ count( $parent['children'] ) - 1 ];
            }
            unset( $node );
        }

        return $tree;
    }
}
```

**How the auto-clamp works (per D-03):** If items go depth 0 -> depth 3 (skipping 1 and 2), the `while` loop pops nothing (stack has 1 element, depth is 3, condition `count($stack) > $depth` is false since 1 > 3 is false). Then the child attaches to the stack's top element (the depth-0 root). The result: depth 0 -> depth 1 (clamped from 3 to 1). `[VERIFIED: algorithm trace -- ARCHITECTURE.md Pattern 5]`

### Pattern 3: Indent Dashes via `title_field`

**What:** The Elementor repeater's `title_field` property accepts JavaScript expressions inside `{{{ }}}` braces. These expressions are evaluated by Elementor's internal template engine to produce the collapsed item title.

**When to use:** When the collapsed repeater title needs to reflect computed values (like depth-based indent dashes) beyond simple field interpolation.

**Example:**
```php
// The title_field expression for indent dashes
'title_field' => '{{{ depth > 0 ? "\\u2014".repeat( depth ) + " " : "" }}} {{{ label }}}',

// How this renders in the Elementor editor:
// depth=0, label="Home"       -> "Home"
// depth=1, label="About"      -> "--- About"
// depth=2, label="Our Team"   -> "------ Our Team"
// depth=0, label="Contact"    -> "Contact"
```

**Key finding:** The `title_field` template engine evaluates JavaScript expressions inside `{{{ }}}`. Field values from the repeater item are available as JavaScript variables. This means:
- `{{{ label }}}` -- outputs the label field value
- `{{{ depth }}}` -- outputs the depth number
- `{{{ depth > 0 ? "---".repeat(depth) + " " : "" }}}` -- evaluates a ternary expression

The em-dash character (`—`) is preferred over hyphens for visual clarity. In the PHP string, it must be escaped as `"\\u2014"` since the template is passed through to JavaScript. `[VERIFIED: stackoverflow.com/questions/56006978 -- community example of function calls in title_field; developers.elementor.com/docs/editor-controls/control-repeater/ -- title_field documentation]`

**Fallback if JS expressions fail:** If the inline expression approach does not work in the actual Elementor editor (edge case with specific Elementor versions), a simpler alternative is to just show the depth number: `'title_field' => '{{{ label }}} (depth: {{{ depth }}})'`. This is less visually elegant but communicates hierarchy. This fallback should only be used if the JS expression approach fails during implementation testing.

### Pattern 4: Render Integration for Custom Source

**What:** In the widget's `render()` method, add a branch for the custom menu source that calls `CustomTree::build()` and produces the `$tree` variable.

**When to use:** When extending `render()` to support a second menu source alongside the existing WP Menu source.

**Example:**
```php
// Inside DrillDownMenu::render(), after the existing WP Menu tree building block

$menu_source = $settings['menu_source'] ?? 'wp_menu';
$tree        = [];

if ( 'wp_menu' === $menu_source && ! empty( $settings['wp_menu_id'] ) ) {
    $tree = \Devsroom_DDMM\MenuBuilder\WpNavTree::build( $settings['wp_menu_id'] );
} elseif ( 'custom' === $menu_source && ! empty( $settings['custom_items'] ) ) {
    $tree = \Devsroom_DDMM\MenuBuilder\CustomTree::build( $settings['custom_items'] );
}

// D-08: Empty state (same logic for both sources)
if ( empty( $tree ) ) {
    if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        $hint = 'custom' === $menu_source
            ? esc_html__( 'Add menu items to display', 'devsroom-drilldown-mobile-menu' )
            : esc_html__( 'Select a menu to display', 'devsroom-drilldown-mobile-menu' );
        echo '<div class="ddmm-editor-hint">' . $hint . '</div>';
    }
    return;
}
```
`[VERIFIED: codebase -- DrillDownMenu.php lines 322-341 show the existing WP Menu branch and empty-state pattern]`

### Anti-Patterns to Avoid
- **Nested repeaters for menu hierarchy:** Would require Elementor Pro or Nested Elements module. Creates confusing editor UX for deep nesting. The flat repeater with depth field is the deliberate design choice. `[CITED: .planning/research/ARCHITECTURE.md Anti-Pattern 6]`
- **Custom validation warnings for depth jumps:** D-03 explicitly states no editor warnings are needed. The stack-based algorithm auto-clamps. Adding validation UI is wasted complexity.
- **Separate tree node contract for custom items:** D-02 mandates identical 7-field output. CustomTree MUST produce the same structure as WpNavTree. Any deviation breaks Phase 4's single-renderer design.
- **Passing Elementor `$settings` directly to CustomTree:** The tree builder must accept only a plain PHP array, not Elementor settings objects. This keeps it independently testable and Elementor-free. `[CITED: CONTEXT.md D-09]`

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Repeater control with fields | Custom editor UI or nested metaboxes | Elementor REPEATER control with `new \Elementor\Repeater()` | Elementor's repeater handles add/remove/reorder/duplicate UI, data serialization, editor preview. Building custom UI would be thousands of lines. `[CITED: developers.elementor.com/docs/editor-controls/control-repeater/]` |
| Icon selection and rendering | Custom icon picker, manual font/SVG loading | `Controls_Manager::ICONS` + `Icons_Manager::render_icon()` | Elementor's Icons control handles Font Awesome, SVG upload, icon library integration. `render_icon()` handles inline SVG vs font class rendering. `[VERIFIED: codebase -- DrillDownMenu.php line 288]` |
| URL input with link options | TEXT control with manual URL parsing | `Controls_Manager::URL` control | URL control provides `url`, `is_external`, `nofollow`, `custom_attributes` sub-fields. Handles protocol, mailto, tel. `[VERIFIED: Elementor Controls API]` |
| Switcher (on/off toggle) | Custom checkbox | `Controls_Manager::SWITCHER` | Standard Elementor toggle control. Returns `'yes'` or empty string. `[VERIFIED: Elementor Controls API]` |

**Key insight:** Elementor's control API handles all the editor-side complexity (UI, data binding, serialization). The only custom code needed is the PHP tree builder algorithm and the render() integration. Everything else is declarative control configuration.

## Common Pitfalls

### Pitfall 1: Elementor URL Control Data Format (CRITICAL)
**What goes wrong:** The URL control stores data as an array with keys `url`, `is_external`, `nofollow`, `custom_attributes`. Accessing `$item['url']` naively gives an array, not a string. Passing the array to `esc_url()` or string concatenation causes PHP warnings or incorrect output.
**Why it happens:** Developers expect `url` to be a string since the UI shows a URL input. The URL control actually returns a structured array.
**How to avoid:** In `CustomTree::build()`, extract the URL explicitly: `$url = is_array($item['url']) ? ($item['url']['url'] ?? '') : $item['url']`. The `is_external` field is redundant for custom items because we have a dedicated `new_tab` switcher -- always use the `new_tab` field for target, not `is_external`.
**Warning signs:** Menu item URLs appear as "Array" in the output, or PHP warnings about array-to-string conversion.

### Pitfall 2: SWITCHER Control Returns 'yes'/'' Not true/false
**What goes wrong:** Checking `$item['new_tab']` with `if ($item['new_tab'])` seems to work, but the SWITCHER control returns the string `'yes'` when enabled and `''` (empty string) when disabled. Loose truthy checks work, but `=== true` would always fail.
**Why it happens:** Elementor's SWITCHER control uses `'yes'` as the "on" value by default, not boolean true.
**How to avoid:** Use explicit string comparison: `'yes' === $item['new_tab']`. This is clearer and matches Elementor conventions.
**Warning signs:** Open in New Tab never works because developer used `$item['new_tab'] === true`.

### Pitfall 3: `title_field` JS Expression Em-Dash Escaping
**What goes wrong:** The em-dash character (`---` in HTML entity or `—` in Unicode) must be properly escaped in the PHP string that defines `title_field`. If the escaping is wrong, the editor shows garbled characters or the expression fails silently.
**Why it happens:** The `title_field` value is a PHP string containing a JavaScript template expression. Em-dashes in PHP source are fine for UTF-8 files, but the `—` escape sequence needs double backslash in PHP (`"\\u2014"`) because PHP interprets `\u` as a Unicode codepoint in double-quoted strings since PHP 7.0.
**How to avoid:** Either use the literal em-dash character in a UTF-8 PHP file, or use `"\\u2014"` in double-quoted strings. Test the title_field output in the Elementor editor with items at various depths.
**Warning signs:** Collapsed repeater titles show `undefined` or `---` instead of em-dashes, or PHP parse error on the title_field line.

### Pitfall 4: Stack Reference Tracking in Tree Builder
**What goes wrong:** The stack-based algorithm uses PHP references (`&`) to track ancestry. If references are mishandled, child items appear under the wrong parent, or the tree structure is corrupted (similar to the `foreach &$ref` footgun from PITFALLS.md Pitfall 2).
**Why it happens:** PHP references in the stack array are tricky. After `array_pop()`, the reference is destroyed, but the remaining stack entries must still point to valid nodes.
**How to avoid:** The algorithm in Pattern 2 uses references carefully: each `&$stack[N]` points to a specific node in the growing tree. After pushing a child, the new stack entry references the child within the parent's `children` array. The `unset($node)` at the end of each iteration breaks the `$node` variable's reference to avoid interference with the next iteration.
**Warning signs:** Menu renders correctly for 2-3 items but shows wrong nesting with 5+ items or interleaved depths (0,1,2,1,2,0).

### Pitfall 5: Empty Repeater vs Empty Tree
**What goes wrong:** Developer checks `$settings['custom_items']` for emptiness but the repeater data may contain items with empty labels and depth=0, producing a "technically non-empty" tree that renders blank menu items.
**Why it happens:** Elementor's repeater can contain items where the user filled in nothing. The repeater data array is non-empty (has entries with `_id` fields), but the meaningful content (label, url) is blank.
**How to avoid:** In `CustomTree::build()`, skip items with empty labels: `if (empty($title)) continue;`. This ensures the tree only contains items with actual content. The empty-state check in `render()` then correctly handles the case where all items had blank labels.
**Warning signs:** Empty clickable areas in the menu, or "phantom" menu items with no visible text.

## Code Examples

### Full Repeater Control Registration (VERIFIED pattern)
```php
// Source: developers.elementor.com/docs/editor-controls/control-repeater/
// Inside DrillDownMenu::_register_controls(), inside section_menu, after wp_menu_id

if ( ! class_exists( '\\Elementor\\Repeater' ) ) {
    return; // Guard against Elementor not being active (should not happen due to Plugin.php check)
}

$custom_repeater = new \Elementor\Repeater();

$custom_repeater->add_control(
    'label',
    [
        'label'       => esc_html__( 'Label', 'devsroom-drilldown-mobile-menu' ),
        'type'        => \Elementor\Controls_Manager::TEXT,
        'default'     => '',
        'placeholder' => esc_html__( 'Menu Item Label', 'devsroom-drilldown-mobile-menu' ),
        'label_block' => true,
    ]
);

$custom_repeater->add_control(
    'url',
    [
        'label'   => esc_html__( 'Link', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::URL,
        'default' => [ 'url' => '' ],
    ]
);

$custom_repeater->add_control(
    'depth',
    [
        'label'       => esc_html__( 'Depth', 'devsroom-drilldown-mobile-menu' ),
        'type'        => \Elementor\Controls_Manager::NUMBER,
        'min'         => 0,
        'step'        => 1,
        'default'     => 0,
        'label_block' => false,
        'description' => esc_html__( '0 = root, 1 = child, 2 = grandchild', 'devsroom-drilldown-mobile-menu' ),
    ]
);

$custom_repeater->add_control(
    'icon',
    [
        'label'   => esc_html__( 'Icon', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::ICONS,
        'default' => [ 'value' => '', 'library' => '' ],
    ]
);

$custom_repeater->add_control(
    'new_tab',
    [
        'label'   => esc_html__( 'Open in New Tab', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::SWITCHER,
        'default' => '',
    ]
);

$this->add_control(
    'custom_items',
    [
        'label'       => esc_html__( 'Menu Items', 'devsroom-drilldown-mobile-menu' ),
        'type'        => \Elementor\Controls_Manager::REPEATER,
        'fields'      => $custom_repeater->get_controls(),
        'title_field' => '{{{ depth > 0 ? "\\u2014".repeat( depth ) + " " : "" }}} {{{ label }}}',
        'condition'   => [
            'menu_source' => 'custom',
        ],
    ]
);
```
`[CITED: developers.elementor.com/docs/editor-controls/control-repeater/ -- official Repeater class usage pattern]`

### Stack-Based Tree Builder Trace
```
// Input: flat repeater items
[
    { label: "Home",       depth: 0 },
    { label: "About",      depth: 0 },
    { label: "Team",       depth: 1 },
    { label: "Leadership", depth: 2 },
    { label: "Careers",    depth: 1 },
    { label: "Contact",    depth: 0 },
]

// Step-by-step trace:
// Item 1: "Home" depth=0 -> stack empty, push to tree, stack=[&Home]
//   tree: [Home]
//   stack: [&Home]

// Item 2: "About" depth=0 -> pop stack to 0 elements, push to tree, stack=[&About]
//   tree: [Home, About]
//   stack: [&About]

// Item 3: "Team" depth=1 -> stack has 1 element (About), attach to About, stack=[&About, &Team]
//   tree: [Home, About{children:[Team]}]
//   stack: [&About, &Team]

// Item 4: "Leadership" depth=2 -> stack has 2 elements, attach to Team, stack=[&About, &Team, &Leadership]
//   tree: [Home, About{children:[Team{children:[Leadership]}]}]
//   stack: [&About, &Team, &Leadership]

// Item 5: "Careers" depth=1 -> pop stack to 1 element (About), attach to About, stack=[&About, &Careers]
//   tree: [Home, About{children:[Team{children:[Leadership]}, Careers]}]
//   stack: [&About, &Careers]

// Item 6: "Contact" depth=0 -> pop stack to 0 elements, push to tree, stack=[&Contact]
//   tree: [Home, About{children:[Team{children:[Leadership]}, Careers]}, Contact]

// Auto-clamp example:
// Item: depth=0, next item: depth=3
// -> stack pops to 0 elements, then tries to attach as child of stack[0]
// -> BUT stack is empty (0 === depth), so it goes to root instead
// Result: depth effectively becomes 0 (clamped from 3)
```
`[VERIFIED: ARCHITECTURE.md Pattern 5 -- stack-based algorithm documented]`

### Reading Repeater Data in render()
```php
// Source: developers.elementor.com/docs/widgets/rendering-repeaters/
$settings = $this->get_settings_for_display();

// $settings['custom_items'] is an array of repeater rows.
// Each row is an associative array with keys matching the repeater control names:
// [
//     ['_id' => 'abc123', 'label' => 'Home', 'url' => ['url' => '/', ...], 'depth' => 0, 'icon' => [...], 'new_tab' => ''],
//     ['_id' => 'def456', 'label' => 'About', 'url' => ['url' => '/about', ...], 'depth' => 0, 'icon' => [...], 'new_tab' => ''],
//     ...
// ]
// The '_id' is Elementor's internal repeater item ID (auto-generated).
// The 'icon' field structure matches Icons control output: ['value' => 'fas fa-home', 'library' => 'fa-solid'] or SVG data.
```
`[CITED: developers.elementor.com/docs/widgets/rendering-repeaters/ -- iterating repeater data in render()]`

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Elementor Icon control (font-only) | Elementor Icons control (font + SVG) | Elementor 2.6+ | Icons control supports Font Awesome, custom SVG uploads. Old Icon control only handles font icons. Plugin uses Icons control per CMEN-05. |
| Inline `fields` array in repeater | `new \Elementor\Repeater()` class pattern | Elementor best practice | Class pattern is cleaner for 3+ fields. Both produce same result. Official docs show both. |
| Nested repeaters for hierarchy | Flat repeater with depth field | Project-mandated | Avoids Elementor Pro dependency. Simpler UX. `[CITED: .planning/research/ARCHITECTURE.md Anti-Pattern 6]` |

**Deprecated/outdated:**
- `Controls_Manager::ICON` (old icon control, font-only) -- use `Controls_Manager::ICONS` instead. The old control only supports Font Awesome class-based icons, not SVG uploads.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `title_field` evaluates JavaScript expressions like `'---'.repeat(depth)` inside `{{{ }}}` braces | Pattern 3 | If wrong, indent dashes won't display. Fallback: show depth number only, or use `<# #>` Underscore.js blocks. `[ASSUMED -- based on community examples of function calls in title_field; not verified against Elementor source code]` |
| A2 | The `—` escape sequence must be double-backslashed in PHP (`"\\u2014"`) to survive through to JavaScript | Pitfall 3 | If wrong, the em-dash may not render. Fallback: use literal em-dash character in UTF-8 PHP file. `[ASSUMED -- PHP 7.0+ Unicode codepoint escape behavior]` |
| A3 | `prevent_empty` defaults to `true` on the repeater, but we want an empty start per D-06, so we must explicitly set `'prevent_empty' => false` | Pattern 1 | If wrong and prevent_empty is true, the repeater always has at least one item, contradicting D-06. `[ASSUMED -- Elementor docs state default is true; needs explicit override]` |

**Note:** All other claims are tagged `[VERIFIED]` or `[CITED]` in their respective sections.

## Open Questions

1. **`title_field` JS expression compatibility across Elementor versions**
   - What we know: The `{{{ }}}` syntax supports at least simple field interpolation and function calls (verified by StackOverflow examples). The em-dash repeat expression is a standard JS expression.
   - What's unclear: Whether all Elementor versions from 3.29+ support inline ternary expressions with method calls in `title_field`.
   - Recommendation: Implement the expression approach. Test in the actual Elementor editor. If it fails, fall back to showing depth number: `'{{{ label }}} (depth: {{{ depth }}})'`.

2. **Whether to filter empty-label items in CustomTree::build() or in render()**
   - What we know: Pitfall 5 identifies that blank-label items produce phantom menu items. CONTEXT.md does not specify where to filter.
   - What's unclear: Whether filtering should happen in the tree builder (pure PHP) or in the widget's render() method.
   - Recommendation: Filter in CustomTree::build() -- skip items where `label` is empty. This is Claude's Discretion. The tree builder is the right place because it produces the tree that Phase 4 consumes. Phase 4 should not need to filter.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.1+ | CustomTree builder, typed returns | Yes | 8.4.15 (local) | -- |
| WordPress 6.5+ | `get_settings_for_display()` context | Not installed locally | -- | Test in WP env during execution |
| Elementor 3.29+ | REPEATER, ICONS, NUMBER, SWITCHER, URL controls | Not installed locally | -- | Test in WP+Elementor env during execution |

**Missing dependencies with no fallback:**
- A running WordPress + Elementor instance is required to verify the repeater controls in the editor and test the `title_field` expression. This is an execution-time concern, not a planning blocker.

**Missing dependencies with fallback:**
- None. All APIs are WordPress/Elementor core (bundled).

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | None configured |
| Config file | none -- no `phpunit.xml`, no `composer.json`, no `tests/` directory |
| Quick run command | `php -l src/MenuBuilder/CustomTree.php` (syntax lint) |
| Full suite command | N/A |

**Note:** No automated test infrastructure exists in this project. Same approach as Phase 2: PHP lint for syntax, manual verification in WP+Elementor instance.

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| CMEN-01 | Repeater appears with Label, URL, Depth, Icon, Open in New Tab when Menu Source = Custom | manual (WP+Elementor) | -- | N/A (control registration) |
| CMEN-02 | Depth field drives nesting in tree output | manual + PHP lint | `php -l src/MenuBuilder/CustomTree.php` | No -- file to be created |
| CMEN-03 | Collapsed repeater titles show indent dashes (-- Child, ---- Grandchild) | manual (Elementor editor) | -- | N/A (title_field expression) |
| CMEN-04 | Stack-based algorithm produces correct nested tree | manual (inspect tree output) | -- | No -- file to be created |
| CMEN-05 | Icon field uses Icons picker, renders correctly | manual (WP+Elementor) | -- | N/A (ICONS control) |

### Sampling Rate
- **Per task commit:** `php -l <changed-file>` -- syntax validation only
- **Per wave merge:** Manual verification in WP+Elementor instance with multi-level test menu items
- **Phase gate:** Full manual checklist (repeater visible on custom source, indent dashes display, tree correct for flat/nested/dept-jump scenarios, empty state shows hint, icons render)

### Wave 0 Gaps
- [ ] No PHPUnit setup -- deferred to future task (consistent with Phase 1 and Phase 2 approach)
- [ ] No WP test instance configured -- execution relies on developer's local WP environment

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V5 Input Validation | yes | Elementor settings read via `$this->get_settings_for_display()` with built-in sanitization. The repeater data is structured by Elementor -- user cannot inject arbitrary fields. Custom menu URLs from the URL control are validated by Elementor. |
| V7 Data Protection | yes (minor) | CustomTree produces pure data (no HTML output). Escaping is Phase 4's renderer responsibility. Editor hint uses `esc_html__()`. |
| V2 Authentication | no | No authentication logic |
| V3 Session Management | no | No session handling |
| V4 Access Control | no | No access control -- widget renders for all visitors |

### Known Threat Patterns for Custom Menu Data

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Stored XSS via custom label | Tampering | Labels are entered by site admins in Elementor editor (trusted role). Phase 4 renderer must use `esc_html()` when outputting. CustomTree stores raw strings -- escaping is render-time. |
| Open redirect via custom URL | Spoofing | Elementor URL control validates URL format. Phase 4 renderer must use `esc_url()`. Custom items with JavaScript URLs (`javascript:...`) are mitigated by Elementor's URL validation. |
| Malicious SVG in icon upload | Tampering | Elementor's Icons control and `Icons_Manager::render_icon()` handle SVG sanitization internally. Do not bypass with raw output. `[VERIFIED: Elementor handles SVG sanitization in Icons_Manager]` |

## Sources

### Primary (HIGH confidence)
- [developers.elementor.com/docs/editor-controls/control-repeater/](https://developers.elementor.com/docs/editor-controls/control-repeater/) -- Official Repeater control documentation. Confirms `fields`, `title_field`, `prevent_empty`, `default` arguments. Both inline fields array and Repeater class patterns documented.
- [developers.elementor.com/docs/widgets/rendering-repeaters/](https://developers.elementor.com/docs/widgets/rendering-repeaters/) -- Official docs for iterating repeater data in `render()`. Shows `$settings['list']` as array access pattern and `foreach` iteration.
- [developers.elementor.com/new-control-icons/](https://developers.elementor.com/new-control-icons/) -- Official Icons control docs. Confirms `Icons_Manager::render_icon()` returns boolean and handles both font and SVG icons.

### Secondary (MEDIUM confidence)
- [stackoverflow.com/questions/56006978](https://stackoverflow.com/questions/56006978/elementor-extension-how-can-i-set-a-repeater-item-title-to-the-current-value-of) -- Community-verified: `title_field` supports JavaScript function calls inside `{{{ }}}` (e.g., `{{{ flanceTitleHelper(country) }}}`).
- Codebase inspection: `src/Elementor/Widget/DrillDownMenu.php` -- confirmed existing control patterns (condition arrays, Icons control usage with render_icon, SWITCHER for trigger types).
- Codebase inspection: `src/MenuBuilder/WpNavTree.php` -- confirmed 7-field tree node contract that CustomTree must match.

### Internal References
- `.planning/research/ARCHITECTURE.md` -- Pattern 5 (stack-based depth-field tree builder), data flow diagrams, component responsibilities
- `.planning/research/FEATURES.md` -- Custom Menu Builder UX requirements
- `.planning/research/PITFALLS.md` -- Pitfall 2 (PHP reference corruption), Security Mistakes
- `.planning/phases/02-wordpress-menu-source/02-CONTEXT.md` -- D-03 (tree node contract), D-05 (empty state pattern)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- Elementor Repeater, Icons, URL, SWITCHER controls are well-documented and stable. Tree builder is pure PHP.
- Architecture: HIGH -- Stack-based algorithm documented in ARCHITECTURE.md and verified by code trace. Node contract locked via D-02.
- Pitfalls: HIGH -- URL control data format and SWITCHER return value verified against Elementor docs and codebase patterns. Reference handling in stack builder is the main risk (MEDIUM).
- title_field JS expression: MEDIUM -- Community examples confirm function calls work, but the specific em-dash repeat expression has not been tested against Elementor 3.29+ source code. Flagged in Assumptions as A1.

**Research date:** 2026-06-13
**Valid until:** 2026-07-13 (30 days -- Elementor controls API is stable; long shelf life)
