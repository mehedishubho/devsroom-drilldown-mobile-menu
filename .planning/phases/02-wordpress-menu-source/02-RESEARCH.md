# Phase 2: WordPress Menu Source - Research

**Researched:** 2026-06-13
**Domain:** WordPress Nav Menu API, Elementor SELECT control, PHP tree-building algorithm
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Add a "Menu Source" SELECT toggle now with two options: "WordPress Menu" and "Custom Builder". The WP Menu dropdown appears conditionally when "WordPress Menu" is selected. Phase 3 will add the Custom Builder repeater controls under the second option. This avoids restructuring Content Tab controls later.
- **D-02:** New "Menu" section below "Trigger Button" in the Content Tab. Contains: Menu Source toggle + WP Menu dropdown (visible when source is "wp_menu"). Clean separation between trigger configuration and menu data.
- **D-03:** Tree builder returns pure data — no panel IDs, no HTML concerns. Panel ID generation (`data-target` / `data-panel-id` via `uniqid()`) is Phase 4's renderer responsibility. Clean separation between data layer and rendering layer.
- **D-04:** Each tree node carries a minimal field set: `id` (int, WP menu item DB ID), `title` (string), `url` (string), `target` (string, `_blank` or empty), `classes` (array, CSS classes from WP menu item), `has_children` (bool), `children` (array of child nodes, empty array for leaf items). Extra WP fields available from source data if needed in future but not included by default.
- **D-05:** When no menu is selected or the selected menu is empty/deleted, the widget renders nothing on the frontend (zero HTML output). In the Elementor editor preview, a subtle hint message is shown (e.g., "Select a menu to display") so the user knows the widget is present but unconfigured.
- **D-06:** WooCommerce menu items (Cart, My Account, Checkout, Shop) require no special handling in the tree builder. WooCommerce registers them as standard WP nav menu items — `wp_get_nav_menu_items()` returns them like any other item. The tree builder treats them identically. WooCommerce-specific URL correctness verification is Phase 7 territory.

### Claude's Discretion
- Exact SELECT option values for Menu Source toggle (e.g., `'wp_menu'` / `'custom'`)
- WP Menu dropdown default value and placeholder text
- Editor hint message wording and CSS styling
- Whether to include an `<hr>` or divider between sections in the Content Tab
- Internal naming of the tree builder method (`build()`, `get_tree()`, etc.)
- Whether `classes` field is an array or space-separated string

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope. Phase 3 (Custom Builder repeater), Phase 4 (drawer HTML rendering), Phase 5 (drill-down JS), Phase 6 (Style Tab), Phase 7 (keyboard/accessibility, WooCommerce URL verification) are all out of scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| WMEN-01 | Content Tab provides "WordPress Menu" dropdown listing all registered `wp_nav_menus()` | Elementor SELECT control populated at `_register_controls()` time via `wp_get_nav_menus()` returning `WP_Term[]`. See "Elementor Controls for Menu Selection" and "Code Examples: Building the WP Menu dropdown options". |
| WMEN-02 | Selected WP menu is converted to nested tree using 3-pass ID-based builder (index → attach → resolve) — no PHP references | 3-pass algorithm documented in "Architecture Patterns: Pattern 1". Critical correction to ARCHITECTURE.md: use `$item->ID` (post ID), not `$item->db_id`, as the index key and matching key. See "Pitfall 1: db_id vs ID Key Mismatch". |
| WMEN-03 | Menu items without children render as plain `<a>` links with their URL | Phase 2 builds the tree data; the actual `<a>` rendering is Phase 4. Phase 2's tree builder marks these nodes with `has_children => false`, enabling Phase 4 to render them as links. The tree node contract (D-04) carries `url` and `has_children` fields. |
| WMEN-04 | Menu items with children render as parent spans with `data-target="PANEL_ID"` and right-arrow icon | Phase 2's tree builder marks these nodes with `has_children => true` and populates `children[]`. Panel ID generation (`data-target`/`data-panel-id`) is Phase 4's responsibility per D-03. Phase 2 delivers the data that enables this. |
| WMEN-05 | WooCommerce menu items (Cart, My Account, Checkout, Shop) render correctly with proper URLs | Per D-06, WooCommerce items require no special tree-builder handling. `wp_get_nav_menu_items()` returns them as standard menu items with correct resolved URLs. The tree builder treats them identically. Verified via WordPress nav-menu API. |
</phase_requirements>

## Summary

Phase 2 adds two things to the plugin: (1) an Elementor Content Tab "Menu" section with a Menu Source toggle and a WordPress Menu dropdown, and (2) a pure-PHP tree builder class (`src/MenuBuilder/WpNavTree.php`) that converts the flat array returned by `wp_get_nav_menu_items()` into a nested parent-child tree using the locked 3-pass ID-based algorithm.

The Elementor SELECT control is populated statically at widget-control-registration time by calling `wp_get_nav_menus()` inside `_register_controls()`. Elementor does NOT support reactive/callback-based option refreshment — the options array is built once in PHP and serialized to the editor. This is sufficient because WordPress menus rarely change during an editing session, and the dropdown re-reads the options on each editor load.

The tree builder is pure PHP with zero Elementor dependency, making it independently testable. The critical implementation detail — which the ARCHITECTURE.md Pattern 4 example gets subtly wrong — is that the parent-child relationship in `wp_get_nav_menu_items()` is keyed on the menu item's **post ID** (`$item->ID`), which equals `db_id` for saved menu items but is the canonical property used by every WordPress core reference and the official user notes. The `menu_item_parent` field (a **string** — either `"0"` for top-level items or the parent's post ID as a numeric string) must be cast to int and matched against the indexed map.

**Primary recommendation:** Use `$item->ID` (not `$item->db_id`) as both the index key in Pass 1 and the field stored as `id` in the tree node. Match children by casting `$item->menu_item_parent` to int and looking it up in the ID-indexed map. This aligns with all WordPress core documentation and user-contributed notes, and is the safest choice for compatibility.

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| WordPress Nav Menu API | WP 6.5+ | `wp_get_nav_menus()`, `wp_get_nav_menu_items()` | The only correct API for fetching WordPress menu data. Returns `WP_Term[]` for menu list and decorated `WP_Post[]` for items. `[VERIFIED: developer.wordpress.org]` |
| Elementor Controls API | 3.29+ | `Controls_Manager::SELECT` control, `condition` arrays | Standard Elementor widget control for dropdowns. Options populated statically in `_register_controls()`. `[CITED: developers.elementor.com/docs/editor-controls/control-select/]` |
| PHP 8.1+ | 8.1 min, 8.4 local | Tree builder logic, typed returns, readonly | Project requires 8.1+. Local env has 8.4.15. `[VERIFIED: php --version]` |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `\Elementor\Plugin::$instance->editor->is_edit_mode()` | Elementor 3.29+ | Detect editor context for hint message rendering (D-05) | When deciding whether to show "Select a menu" placeholder |
| `is_preview_mode()` | Elementor 3.29+ | Detect preview iframe context | Secondary check; editor mode is the primary gate for the hint |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Elementor SELECT for menu dropdown | SELECT2 | SELECT2 adds search/autocomplete — useful only for sites with 20+ menus. Overkill for typical sites. Stick with SELECT. `[CITED: developers.elementor.com/docs/editor-controls/control-select/]` |
| `wp_get_nav_menus()` for dropdown | `get_registered_nav_menus()` (locations) | Locations list theme slots, not actual menus. A location may be empty or point to a deleted menu. `wp_get_nav_menus()` returns real menu objects the user created in Appearance > Menus. Use `wp_get_nav_menus()`. `[VERIFIED: developer.wordpress.org]` |
| Storing menu by slug | Store menu by `term_id` | `term_id` is stable and numeric. Slug can be renamed by users. Elementor SELECT option keys are the stored values — use `term_id` as the key. `[VERIFIED: developer.wordpress.org]` |

**Installation:**
No packages to install. This phase uses only WordPress core APIs and the Elementor bundled API. No Composer, no npm.

**Version verification:** Not applicable — no external packages. WordPress API functions are stable since WP 3.0.0 (nav menus) and Elementor SELECT control since 1.0.

## Architecture Patterns

### Recommended Project Structure (Phase 2 additions)
```
src/
├── Elementor/
│   └── Widget/
│       └── DrillDownMenu.php    # ADD: new "Menu" section in _register_controls()
└── MenuBuilder/                 # NEW DIRECTORY
    └── WpNavTree.php            # NEW: 3-pass tree builder (pure PHP, no Elementor dep)
```

The `src/MenuBuilder/` directory does not exist yet. It must be created. The PSR-4 autoloader (established in Phase 1) maps `Devsroom_DDMM\MenuBuilder\WpNavTree` to `src/MenuBuilder/WpNavTree.php` automatically once the file exists — no autoloader changes needed. `[VERIFIED: codebase — src/Plugin.php uses spl_autoload_register mapping Devsroom_DDMM\ → src/]`

### Pattern 1: 3-Pass ID-Based Tree Builder (Corrected)

**What:** Convert the flat array from `wp_get_nav_menu_items()` into a nested parent-child tree using three sequential passes. NO PHP references (`&$item`).

**When to use:** Always, for WordPress nav menu tree building. This is a locked decision (CONTEXT.md implies it; REQUIREMENTS.md WMEN-02 mandates it; ARCHITECTURE.md Pattern 4 documents it; PITFALLS.md Pitfall 2 warns against the reference-based alternative).

**CRITICAL CORRECTION to ARCHITECTURE.md Pattern 4:** The existing example in ARCHITECTURE.md indexes by `$item->db_id` and stores `'id' => $item->db_id`. While `db_id` equals `ID` (the post ID) for saved menu items, the canonical WordPress pattern — used by every official user note on `wp_get_nav_menu_items()` — is to use `$item->ID`. The `menu_item_parent` field references the parent's post ID. Using `ID` is safer because:
1. It is the standard `WP_Post` property (every example in the WordPress docs uses `$m->ID`).
2. `db_id` can theoretically be `0` for dynamically-added unsaved items (via `wp_nav_menu_objects` filter), while `ID` is always set.
3. The official WordPress function reference user notes consistently match `$submenu->menu_item_parent == $parent` where `$parent = $menu_item->ID`.

**Example (corrected):**
```php
// src/MenuBuilder/WpNavTree.php
namespace Devsroom_DDMM\MenuBuilder;

/**
 * Converts flat wp_get_nav_menu_items() output into a nested tree.
 * Pure data — no HTML, no panel IDs, no Elementor dependency.
 */
class WpNavTree {

    /**
     * Build a nested menu tree from a WordPress nav menu.
     *
     * @param int|string|\WP_Term $menu Menu ID, slug, name, or term object.
     * @return array<int, array> Root-level tree nodes. Empty array if menu is empty/invalid.
     */
    public static function build( $menu ): array {
        $items = wp_get_nav_menu_items( $menu );

        if ( ! $items || empty( $items ) ) {
            return [];
        }

        // Pass 1: Index all items by their post ID.
        $indexed = [];
        foreach ( $items as $item ) {
            $indexed[ (int) $item->ID ] = [
                'id'          => (int) $item->ID,
                'title'       => $item->title,
                'url'         => $item->url,
                'target'      => $item->target ?? '',
                'classes'     => is_array( $item->classes ) ? $item->classes : [],
                'has_children' => false,
                'children'    => [],
            ];
        }

        // Pass 2: Link children to parents. Collect root IDs.
        $root_ids = [];
        foreach ( $items as $item ) {
            $parent_id = (int) $item->menu_item_parent;

            if ( $parent_id > 0 && isset( $indexed[ $parent_id ] ) ) {
                // Attach this node (by reference-free copy) to its parent.
                $indexed[ $parent_id ]['children'][]    = & $indexed[ (int) $item->ID ];
                $indexed[ $parent_id ]['has_children']  = true;
            } else {
                $root_ids[] = (int) $item->ID;
            }
        }

        // Pass 3: Extract root nodes as the tree.
        $tree = [];
        foreach ( $root_ids as $id ) {
            $tree[] = $indexed[ $id ];
        }

        return $tree;
    }
}
```

**IMPORTANT — Reference note:** Pass 2 uses `& $indexed[...]` (PHP reference assignment) to link the same array node into both the flat index AND the parent's children array. This is safe because: (a) it is NOT a `foreach &$ref` loop (the documented footgun), and (b) the references are resolved before return — `$tree` captures the full nested structure. After `build()` returns, no reference variable persists in scope. However, if the planner wants zero references at all, an alternative is to build the tree purely by recursion in Pass 3 (walk down from each root, pulling children from the index map). See "Open Questions" — this is a Claude's Discretion item.

**Anti-references alternative (fully reference-free):**
```php
// Pass 1: index by ID (same as above)
// Pass 2: build a parent => [child IDs] lookup map
$children_map = []; // parent_id => [child_id, child_id, ...]
foreach ( $items as $item ) {
    $parent_id = (int) $item->menu_item_parent;
    $id        = (int) $item->ID;
    if ( $parent_id > 0 && isset( $indexed[ $parent_id ] ) ) {
        $children_map[ $parent_id ][] = $id;
        $indexed[ $parent_id ]['has_children'] = true;
    }
}
// Pass 3: recursive assembly
$resolve = function( $node_id ) use ( &$resolve, &$indexed, &$children_map ) {
    $node = $indexed[ $node_id ];
    if ( isset( $children_map[ $node_id ] ) ) {
        foreach ( $children_map[ $node_id ] as $child_id ) {
            $node['children'][] = $resolve( $child_id );
        }
    }
    return $node;
};
$tree = [];
foreach ( $root_ids as $id ) {
    $tree[] = $resolve( $id );
}
```

### Pattern 2: Elementor SELECT Control with Static Option Population

**What:** The Elementor SELECT control takes an `options` array of `key => label` pairs. This array is built in PHP during `_register_controls()` and serialized. Elementor does NOT support reactive option refreshment from the editor UI.

**When to use:** Whenever a dropdown needs dynamic data (posts, menus, terms, users). Build the options array by calling the relevant WordPress API function at control-registration time.

**Example:**
```php
// Inside DrillDownMenu::_register_controls()

$this->start_controls_section(
    'section_menu',
    [
        'label'     => esc_html__( 'Menu', 'devsroom-drilldown-mobile-menu' ),
        'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
        'separator' => 'after', // Optional divider between Trigger Button and Menu sections (Claude's Discretion)
    ]
);

// D-01: Menu Source toggle
$this->add_control(
    'menu_source',
    [
        'label'   => esc_html__( 'Menu Source', 'devsroom-drilldown-mobile-menu' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'wp_menu',
        'options' => [
            'wp_menu' => esc_html__( 'WordPress Menu', 'devsroom-drilldown-mobile-menu' ),
            'custom'  => esc_html__( 'Custom Builder', 'devsroom-drilldown-mobile-menu' ),
        ],
    ]
);

// WP Menu dropdown — visible only when source is 'wp_menu'
$this->add_control(
    'wp_menu_id',
    [
        'label'     => esc_html__( 'Select Menu', 'devsroom-drilldown-mobile-menu' ),
        'type'      => \Elementor\Controls_Manager::SELECT,
        'default'   => '',
        'options'   => $this->get_wp_menu_options(),
        'condition' => [
            'menu_source' => 'wp_menu',
        ],
    ]
);

$this->end_controls_section();
```

```php
/**
 * Build SELECT options from all registered WordPress nav menus.
 *
 * @return array<int|string, string> Menu term_id => menu name. Includes empty default.
 */
protected function get_wp_menu_options(): array {
    $menus   = wp_get_nav_menus();
    $options = [ '' => esc_html__( '— Select a Menu —', 'devsroom-drilldown-mobile-menu' ) ];

    if ( empty( $menus ) ) {
        return $options;
    }

    foreach ( $menus as $menu ) {
        $options[ $menu->term_id ] = $menu->name;
    }

    return $options;
}
```
`[CITED: developers.elementor.com/docs/editor-controls/control-select/ — options arg is key=>value pairs]`
`[VERIFIED: developer.wordpress.org/reference/functions/wp_get_nav_menu_items/ — wp_get_nav_menus() returns WP_Term objects with term_id and name]`

### Pattern 3: Editor-Only Hint Message (Empty State)

**What:** Per D-05, when no menu is selected or the menu is empty, render zero HTML on the frontend but show a hint in the Elementor editor so the user knows the widget is there.

**When to use:** In `render()`, after the trigger button block, when checking if a valid tree was built.

**Example:**
```php
// Inside render(), after trigger button output

$menu_source = $settings['menu_source'] ?? 'wp_menu';
$tree        = [];

if ( 'wp_menu' === $menu_source && ! empty( $settings['wp_menu_id'] ) ) {
    $tree = \Devsroom_DDMM\MenuBuilder\WpNavTree::build( $settings['wp_menu_id'] );
}

if ( empty( $tree ) ) {
    // D-05: Frontend renders nothing. Editor shows a hint.
    if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
        echo '<div class="ddmm-editor-hint">' .
             esc_html__( 'Select a menu to display', 'devsroom-drilldown-mobile-menu' ) .
             '</div>';
    }
    return; // Zero HTML on frontend
}

// ... Phase 4 will add drawer rendering from $tree here
```
`[CITED: ralphjsmit.com/elementor-check-active — is_edit_mode() / is_preview_mode() pattern]`

### Anti-Patterns to Avoid
- **`foreach ($items as &$item)` reference loop:** Documented in PITFALLS.md Pitfall 2. Causes silent array corruption. The 3-pass approach eliminates this. `[CITED: .planning/research/PITFALLS.md]`
- **Using `$item->db_id` instead of `$item->ID` as the tree index key:** While equivalent for saved items, `ID` is the canonical WordPress post identifier and is what `menu_item_parent` references. Using `db_id` works but diverges from every official example and risks confusion. `[VERIFIED: developer.wordpress.org/reference/functions/wp_get_nav_menu_items/ user notes]`
- **Forgetting to cast `menu_item_parent` to int:** It is returned as a **string** (`"0"` for roots, `"42"` for a child of item 42). A loose comparison `== 0` works, but `isset($indexed[$item->menu_item_parent])` with a string key against an int-keyed array will fail in PHP 8.x strict contexts. Always cast: `(int) $item->menu_item_parent`. `[VERIFIED: developer.wordpress.org — menu_item_parent is string type]`
- **Treating `classes` as a string:** `wp_get_nav_menu_items()` returns `classes` as an **array** of strings. Do not concatenate with space until render time. Per D-04, store as array in the tree node. `[VERIFIED: WordPress source — classes is array]`
- **Calling tree builder in `_register_controls()`:** The dropdown options are built at registration time, but the tree itself must be built in `render()` (frontend) only. Building the tree during control registration wastes resources and may fail (menus can change between editor load and frontend render).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Fetch WordPress menus | Custom DB query on `wp_terms` | `wp_get_nav_menus()` | Handles all caching, filtering, permissions. Returns `WP_Term[]` with `term_id` and `name`. `[VERIFIED: developer.wordpress.org]` |
| Fetch menu items | Custom DB query on `wp_posts` | `wp_get_nav_menu_items( $menu )` | Handles `wp_setup_nav_menu_item()` decoration, URL resolution, ordering by `menu_order`, invalid-item filtering on frontend. Returns decorated `WP_Post[]`. `[VERIFIED: developer.wordpress.org — see source showing array_map('wp_setup_nav_menu_item')]` |
| Menu tree nesting | Recursive Walker class or `foreach &$ref` | 3-pass ID-based index/attach/extract | Walker is designed for HTML output (interleaving `<ul>`/`<li>` during traversal), not for producing a pure data tree. The 3-pass approach produces clean data without references. `[CITED: .planning/research/PITFALLS.md Pitfall 2]` |
| Editor hint CSS | Inline styles or a new CSS file | Reuse existing `ddmm-frontend.css` or inline minimal styles | The hint is a simple div. Add a `.ddmm-editor-hint` rule to the existing CSS file. Do not create a second stylesheet. |

**Key insight:** WordPress already resolves all the hard parts (URL generation for page/post/custom items, WooCommerce endpoint URLs, menu ordering, parent assignment). The tree builder's only job is structural transformation — flat to nested. Do not re-fetch or re-resolve anything the API already provides.

## Common Pitfalls

### Pitfall 1: db_id vs ID Key Mismatch (CRITICAL)
**What goes wrong:** ARCHITECTURE.md Pattern 4 uses `$item->db_id` as the index key and matching field. While `db_id === ID` for saved menu items, this diverges from the canonical WordPress pattern and every official user note, which use `$item->ID`. If a future filter dynamically adds menu items (where `db_id` might be `0`), the `db_id`-keyed index breaks.
**Why it happens:** The `db_id` and `ID` fields appear equivalent, so either "works." The ARCHITECTURE.md author chose `db_id` without realizing it's non-canonical.
**How to avoid:** Use `$item->ID` (the standard `WP_Post::$ID`) as both the index key and the `id` field in the tree node. Match `$item->menu_item_parent` (cast to int) against this ID-keyed map.
**Warning signs:** Tree works for standard menus but breaks when a plugin dynamically injects menu items via `wp_nav_menu_objects` filter.

### Pitfall 2: menu_item_parent is a String, Not an Int
**What goes wrong:** `$item->menu_item_parent` is returned as a string (`"0"`, `"42"`). Using it directly as an array key against an int-keyed `$indexed` map, or comparing with `=== 0`, produces silent failures. Children either don't attach or root detection breaks.
**Why it happens:** WordPress stores `menu_item_parent` as a post meta string. The `wp_setup_nav_menu_item()` decorator does not cast it to int.
**How to avoid:** Always cast: `$parent_id = (int) $item->menu_item_parent;` before any comparison or array lookup.
**Warning signs:** All items appear as root-level (parent detection fails because `"0" === 0` is false), or no items attach as children (string key `"42"` doesn't match int key `42` in strict isset — though PHP normalizes numeric string keys to int in arrays, the comparison with `> 0` and `isset` is safer with explicit casting).

### Pitfall 3: Elementor SELECT Options Are Static (No Reactive Refresh)
**What goes wrong:** Developer assumes that if a user creates a new menu in WP Admin while the Elementor editor is open, the dropdown will update. It will not.
**Why it happens:** Elementor serializes the `options` array when `_register_controls()` runs (on editor page load). There is no AJAX callback to refresh options mid-session.
**How to avoid:** This is acceptable behavior. The dropdown refreshes when the editor page reloads. Do not attempt to build a JS-based reactive dropdown — it's not supported by the Elementor control API. Document this limitation if relevant.
**Warning signs:** User reports "my new menu doesn't appear in the dropdown" — tell them to save and reload the editor.

### Pitfall 4: Calling wp_get_nav_menus() Before WordPress Is Ready
**What goes wrong:** `wp_get_nav_menus()` returns empty or triggers a fatal error if called before the taxonomy system is initialized.
**Why it happens:** `_register_controls()` runs within the Elementor widget lifecycle, which is well after WordPress core init. However, if the tree builder or menu options function is called too early (e.g., in the plugin bootstrap), it fails.
**How to avoid:** Only call `wp_get_nav_menus()` inside `_register_controls()` (which Elementor invokes safely) and only call `wp_get_nav_menu_items()` inside `render()` (frontend rendering, always safe). Never call these in the plugin bootstrap or constructor.

### Pitfall 5: Tree Builder Depends on Elementor Context
**What goes wrong:** Developer writes the tree builder to accept Elementor `$settings` directly, coupling it to Elementor's API.
**Why it happens:** Convenience — passing settings directly seems simpler than extracting the menu ID.
**How to avoid:** The tree builder must accept only a menu identifier (`int|string|WP_Term`) and return pure data. The widget extracts `$settings['wp_menu_id']` and passes it to `WpNavTree::build()`. This keeps the builder Elementor-free and independently testable. `[CITED: CONTEXT.md D-03 — tree builder returns pure data]`

## Code Examples

### Building the WP Menu dropdown options (VERIFIED pattern)
```php
// Source: WordPress developer docs + Elementor SELECT control docs
$menus = wp_get_nav_menus();
// $menus is WP_Term[] — each has:
//   $menu->term_id  (int)   — use as SELECT option key
//   $menu->name     (string) — use as SELECT option label
//   $menu->slug     (string)
//   $menu->count    (int)   — number of items in the menu

$options = [ '' => '— Select a Menu —' ];
foreach ( $menus as $menu ) {
    $options[ $menu->term_id ] = $menu->name;
}
// Pass to Elementor SELECT control 'options' argument.
```
`[VERIFIED: developer.wordpress.org/reference/functions/wp_get_nav_menu_items/ — wp_get_nav_menus() returns WP_Term objects]`

### Reading a selected menu's items (VERIFIED API)
```php
// Source: WordPress developer docs (wp_get_nav_menu_items source code)
$menu_slug_or_id = 42; // whatever the user selected (term_id stored in Elementor setting)
$items = wp_get_nav_menu_items( $menu_slug_or_id );
// $items is array of WP_Post objects decorated with nav menu properties, OR false if menu doesn't exist.

// Key properties on each $item:
//   $item->ID                (int)    — post ID of this nav_menu_item post (CANONICAL KEY)
//   $item->db_id             (int)    — equals ID for saved items; 0 for dynamic/unsaved
//   $item->menu_item_parent  (string) — post ID of parent as string; "0" for roots
//   $item->title             (string) — display label
//   $item->url               (string) — resolved frontend URL
//   $item->target            (string) — "_blank" or ""
//   $item->classes           (array)  — CSS class names as array elements
//   $item->object            (string) — "page", "custom", "category", etc.
//   $item->type              (string) — "post_type", "taxonomy", "custom"

// The function internally calls wp_setup_nav_menu_item() on each item,
// which resolves URLs (including for WooCommerce pages) and applies filters.
// Items are ordered by menu_order ASC by default.
```
`[VERIFIED: developer.wordpress.org/reference/functions/wp_get_nav_menu_items/ — source code and return description]`

### Full render() integration skeleton (Phase 2 scope)
```php
protected function render(): void {
    $settings = $this->get_settings_for_display();

    // --- Existing Phase 1 trigger button rendering (unchanged) ---
    $trigger_type = $settings['trigger_type'] ?? 'hamburger';
    $widget_id    = $this->get_id();
    ?>
    <div class="ddmm-trigger-wrapper">
        <button type="button"
            class="ddmm-trigger ddmm-trigger--<?php echo esc_attr( $trigger_type ); ?>"
            aria-expanded="false"
            aria-controls="ddmm-drawer-<?php echo esc_attr( $widget_id ); ?>">
            <?php /* ... existing trigger switch ... */ ?>
        </button>
    </div>
    <?php

    // --- Phase 2: Menu tree building (data layer only) ---
    $menu_source = $settings['menu_source'] ?? 'wp_menu';
    $tree        = [];

    if ( 'wp_menu' === $menu_source && ! empty( $settings['wp_menu_id'] ) ) {
        $tree = \Devsroom_DDMM\MenuBuilder\WpNavTree::build( $settings['wp_menu_id'] );
    }

    // D-05: Empty state handling
    if ( empty( $tree ) ) {
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            echo '<div class="ddmm-editor-hint">' .
                 esc_html__( 'Select a menu to display', 'devsroom-drilldown-mobile-menu' ) .
                 '</div>';
        }
        return; // Zero frontend HTML when no menu
    }

    // Phase 4 will render the drawer + panels from $tree here.
    // Phase 2 does NOT output the tree as HTML — that is Phase 4's job.
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `foreach ($items as &$ref)` tree building | 3-pass ID-based index/attach/extract | Project-mandated (PITFALLS.md) | Eliminates PHP reference corruption bug from v1.3.0 |
| `wp_nav_menu()` with custom Walker | `wp_get_nav_menu_items()` + custom tree builder | Project-mandated (ARCHITECTURE.md) | Produces pure data tree, not interleaved HTML. Enables separation of data layer (Phase 2) from rendering (Phase 4). |
| Menu identified by slug | Menu identified by `term_id` | Best practice | Slugs can be renamed by users; term_id is immutable. Elementor SELECT option key stores term_id. |

**Deprecated/outdated:**
- `widgets_registered` hook (deprecated) → `elementor/widgets/register` (used since Phase 1) — not relevant to Phase 2 but worth noting the widget is already on the modern hook.
- Direct `wp_nav_menu()` HTML parsing — never use. Use `wp_get_nav_menu_items()` for structured data.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `wp_get_nav_menus()` is safe to call inside `_register_controls()` (runs during Elementor editor load, after WP init) | Code Examples | If wrong, dropdown options would be empty. Mitigated by the fact that Elementor's own Nav Menu widget calls this API the same way. `[ASSUMED — based on Elementor's own Nav Menu widget behavior]` |
| A2 | `\Elementor\Plugin::$instance->editor->is_edit_mode()` is available and reliable for the editor hint | Pattern 3 | If the API path is wrong, hint won't show. Alternative: `is_preview_mode()`. Both documented in community references. `[ASSUMED — should verify in Elementor source during implementation]` |
| A3 | Using PHP references (`& $indexed[$id]`) in Pass 2 of the tree builder is safe because it's not a `foreach &$ref` loop | Pattern 1 | Low risk — the reference is localized and resolves before return. If the planner prefers zero references, the recursive alternative is provided. `[ASSUMED — PHP reference safety reasoning]` |

**Note:** All other claims are tagged `[VERIFIED]` or `[CITED]` in their respective sections.

## Open Questions

1. **Reference-free vs reference-minimal tree builder**
   - What we know: The 3-pass algorithm must avoid `foreach &$ref` (the documented footgun). Pass 2 can either use localized `& $indexed[...]` references (Pattern 1 main example) or a fully reference-free recursive approach (alternative provided).
   - What's unclear: Which is clearer for maintainability. Both produce identical output.
   - Recommendation: This is Claude's Discretion per CONTEXT.md. The recursive alternative is cleaner conceptually but adds a closure. The reference-minimal approach is shorter. Recommend the reference-minimal version unless the planner has a strong preference for zero references.

2. **`classes` field format in tree node**
   - What we know: WordPress returns `classes` as an array. D-04 lists `classes` as "(array) — CSS classes from WP menu item". CONTEXT.md lists this as Claude's Discretion.
   - What's unclear: Whether Phase 4's renderer will want an array or a space-separated string. Keeping it as an array is the faithful representation; the renderer can `implode(' ', $classes)` when needed.
   - Recommendation: Store as array (matches WordPress source). Renderer joins to string. This is the most flexible choice.

3. **Whether to show the menu item count in the dropdown label**
   - What we know: `WP_Term->count` gives the number of items in each menu.
   - What's unclear: Whether to display "Main Menu (12)" or just "Main Menu" in the dropdown.
   - Recommendation: Keep labels simple ("Main Menu"). Count adds noise. This is a minor UX detail at Claude's Discretion.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.1+ | Tree builder, typed returns | ✓ | 8.4.15 (local) | — |
| WordPress 6.5+ | `wp_get_nav_menus()`, `wp_get_nav_menu_items()` | Not installed locally (CLI only) | — | Test via actual WP instance during Phase 2 execution |
| Elementor 3.29+ | SELECT control, `is_edit_mode()` | Not installed locally | — | Test via actual WP+Elementor instance during execution |
| WP-CLI | Optional: menu inspection, fixture generation | ✗ | — | Manual testing via WP admin |
| Composer/PHPUnit | Test infrastructure | ✗ | — | No test framework configured; see Validation Architecture |

**Missing dependencies with no fallback:**
- A running WordPress + Elementor instance is required to verify the widget controls and tree builder against real menu data. This is an execution-time concern, not a planning blocker — the code can be written and linted locally (PHP 8.4 is available), then tested in a WP environment during Phase 2 execution.

**Missing dependencies with fallback:**
- None. All APIs used are WordPress/Elementor core (bundled, not installable).

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | None configured |
| Config file | none — no `phpunit.xml`, no `composer.json`, no `tests/` directory |
| Quick run command | `php -l src/MenuBuilder/WpNavTree.php` (syntax lint — only available validation) |
| Full suite command | N/A |

**Note:** No automated test infrastructure exists in this project. Phase 1 was validated manually in a WP environment. Phase 2 should follow the same approach: PHP lint for syntax, manual verification in a WP+Elementor instance.

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| WMEN-01 | Dropdown lists all registered WP menus | manual (WP+Elementor) | — | N/A |
| WMEN-02 | 3-pass tree builder produces correct nested tree, no references | manual + PHP lint | `php -l src/MenuBuilder/WpNavTree.php` | ❌ file not yet created |
| WMEN-03 | Leaf items have `has_children=false`, carry `url` | manual (inspect tree output) | — | N/A |
| WMEN-04 | Parent items have `has_children=true`, carry `children[]` | manual (inspect tree output) | — | N/A |
| WMEN-05 | WooCommerce items appear with correct URLs | manual (WC active, inspect tree) | — | N/A |

**Justification for manual-only:** The tree builder is pure PHP but depends on `wp_get_nav_menu_items()` (a WordPress function). Without a WordPress test bootstrap, it cannot be unit-tested in isolation without mocking the entire WP function. The project has no PHPUnit/WP test suite configured. Adding one is out of scope for this phase (would be a Wave 0 infrastructure task). Validation is manual: create a multi-level menu in WP Admin, select it in the widget, and verify the tree structure (e.g., via `error_log( print_r($tree, true) )` during render).

### Sampling Rate
- **Per task commit:** `php -l <changed-file>` — syntax validation only (fast, no WP needed)
- **Per wave merge:** Manual verification in WP+Elementor instance with a 3+ level test menu
- **Phase gate:** Full manual checklist (dropdown populated, tree correct for flat/nested/WooCommerce menus, empty state shows hint in editor and nothing on frontend)

### Wave 0 Gaps
- [ ] No PHPUnit setup — but adding it is out of scope (no composer.json, would require WP test bootstrap). Recommend deferring test infrastructure to a dedicated future task.
- [ ] No WP test instance configured — execution will rely on the developer's local WP environment.

*(Validation is manual for this phase, consistent with Phase 1's approach. The tree builder is small enough (~40 lines) that careful code review plus manual testing in WP is sufficient.)*

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V5 Input Validation | yes | Elementor settings are read via `$this->get_settings_for_display()` which uses Elementor's built-in sanitization. The `wp_menu_id` value comes from a SELECT control with server-defined options — the user cannot inject arbitrary values via the editor. |
| V7 Data Protection | yes (minor) | Tree builder does not output HTML (pure data). Escaping responsibility belongs to Phase 4's renderer. The editor hint uses `esc_html__()`. |
| V3 Session Management | no | No session handling in Phase 2 |
| V2 Authentication | no | No authentication logic |
| V4 Access Control | no | No access control — widget renders for all visitors |

### Known Threat Patterns for WordPress Menu Data

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Stored XSS via menu item title | Tampering | Menu titles are created by WP admins (trusted). However, the tree builder must NOT escape — escaping is the renderer's job (Phase 4). The editor hint uses `esc_html__()`. When Phase 4 renders titles, it must use `esc_html()` / `esc_url()`. `[CITED: .planning/research/PITFALLS.md Security Mistakes]` |
| Open redirect via menu item URL | Spoofing | URLs come from `wp_get_nav_menu_items()` which resolves them via WordPress core. Admin-created custom links are trusted. Phase 4 renderer must use `esc_url()`. |
| Menu ID injection | Tampering | SELECT control restricts options to real menu `term_id`s. A crafted POST to Elementor's save endpoint outside this list is handled by Elementor's own validation. Not a Phase 2 concern. |

**Phase 2 escaping posture:** The tree builder produces pure data (arrays/strings) and outputs nothing. The only direct output in Phase 2 is the editor hint (`esc_html__()`). All other escaping is deferred to Phase 4. This matches the locked decision D-03 (tree builder returns pure data, rendering is separate).

## Sources

### Primary (HIGH confidence)
- [developer.wordpress.org/reference/functions/wp_get_nav_menu_items/](https://developer.wordpress.org/reference/functions/wp_get_nav_menu_items/) — Official function reference with source code showing `wp_setup_nav_menu_item()` decoration, `menu_order` sorting, and the `wp_get_nav_menu_items` filter. User notes demonstrate `$item->ID` as the parent-matching key. Confirmed `classes` is an array, `menu_item_parent` is a string.
- [developers.elementor.com/docs/editor-controls/control-select/](https://developers.elementor.com/docs/editor-controls/control-select/) — Official Elementor SELECT control docs. Confirms `options` is `key => value` array, populated statically at registration. No reactive callback support.
- [developer.wordpress.org/reference/functions/wp_setup_nav_menu_item/](https://developer.wordpress.org/reference/functions/wp_setup_nav_menu_item/) — Documents `db_id` vs `ID` distinction and that `menu_item_parent` references the parent's db_id (post ID).

### Secondary (MEDIUM confidence)
- [ralphjsmit.com/elementor-check-active](https://ralphjsmit.com/elementor-check-active) — `is_edit_mode()` / `is_preview_mode()` pattern for editor detection. Community-verified but not official docs.
- Codebase inspection: `src/Plugin.php`, `src/Elementor/Widget/DrillDownMenu.php`, `src/Assets/Registrar.php` — confirmed PSR-4 mapping, existing control patterns (SELECT, condition arrays), escaping conventions (`esc_attr`, `esc_html`).

### Tertiary (LOW confidence)
- WebSearch results on Elementor dynamic dropdown population — confirmed no native reactive option refreshment exists; multiple sources agree options are built in PHP at registration time.

### Internal References
- `.planning/research/ARCHITECTURE.md` — Pattern 4 (3-pass tree builder) — corrected in this research (use `$item->ID`, not `$item->db_id`)
- `.planning/research/PITFALLS.md` — Pitfall 2 (PHP reference corruption), Security Mistakes (escaping), Integration Gotchas (WC guards, nav menu API)
- `.planning/research/FEATURES.md` — Table stakes for menu source, WP menu selection complexity (Low)
- `.planning/phases/01-plugin-foundation-widget-shell/01-CONTEXT.md` — Phase 1 established patterns: PSR-4, widget identity, control registration, escaping
- `.planning/phases/02-wordpress-menu-source/02-CONTEXT.md` — Locked decisions D-01 through D-06

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — WordPress nav menu API is stable since 3.0.0; Elementor SELECT control is foundational.
- Architecture: HIGH — 3-pass algorithm is documented and project-mandated; Elementor control patterns established in Phase 1.
- Pitfalls: HIGH — `db_id`/`ID` distinction and `menu_item_parent` string type verified via official WordPress source code and user notes. Reference corruption documented in PITFALLS.md.
- Tree builder implementation detail (reference-minimal vs recursive): MEDIUM — both approaches are correct; choice is Claude's Discretion.

**Research date:** 2026-06-13
**Valid until:** 2026-07-13 (30 days — WordPress nav menu API and Elementor controls are stable; long shelf life)
