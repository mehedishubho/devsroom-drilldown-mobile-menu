# Phase 2: WordPress Menu Source - Context

**Gathered:** 2026-06-13
**Status:** Ready for planning

<domain>
## Phase Boundary

Users can select any registered WordPress menu, and the plugin converts it into a nested tree structure using the 3-pass ID-based algorithm. This is the **menu data layer** — Elementor Content Tab controls for menu source selection and WP menu picking, plus the pure PHP tree builder class that converts flat `wp_get_nav_menu_items()` output into a nested tree.

**In scope:** Menu Source toggle control (WordPress Menu / Custom Builder), WP Menu dropdown listing registered menus, 3-pass ID-based tree builder class (`src/MenuBuilder/WpNavTree.php`), tree node data contract, empty/no-menu handling.

**Out of scope:** Custom Menu Builder repeater (Phase 3), drawer HTML rendering (Phase 4), drill-down JS (Phase 5), Style Tab (Phase 6), keyboard/accessibility (Phase 7).
</domain>

<decisions>
## Implementation Decisions

### Menu Source Control Strategy
- **D-01:** Add a "Menu Source" SELECT toggle now with two options: "WordPress Menu" and "Custom Builder". The WP Menu dropdown appears conditionally when "WordPress Menu" is selected. Phase 3 will add the Custom Builder repeater controls under the second option. This avoids restructuring Content Tab controls later.

### Content Tab Section Layout
- **D-02:** New "Menu" section below "Trigger Button" in the Content Tab. Contains: Menu Source toggle + WP Menu dropdown (visible when source is "wp_menu"). Clean separation between trigger configuration and menu data.

### Tree Node Data Contract
- **D-03:** Tree builder returns pure data — no panel IDs, no HTML concerns. Panel ID generation (`data-target` / `data-panel-id` via `uniqid()`) is Phase 4's renderer responsibility. Clean separation between data layer and rendering layer.
- **D-04:** Each tree node carries a minimal field set:
  - `id` (int) — WP menu item DB ID
  - `title` (string) — Menu item label
  - `url` (string) — Menu item URL
  - `target` (string) — Link target (`_blank` or empty)
  - `classes` (array) — CSS classes from WP menu item
  - `has_children` (bool) — Whether this node has child items
  - `children` (array) — Array of child nodes (empty array for leaf items)

  Extra WP fields (description, attr_title, xfn, object, object_id, type) are available from source data if needed in future phases but are not included in the tree output by default.

### Empty/No Menu Handling
- **D-05:** When no menu is selected or the selected menu is empty/deleted, the widget renders nothing on the frontend (zero HTML output). In the Elementor editor preview, a subtle hint message is shown (e.g., "Select a menu to display") so the user knows the widget is present but unconfigured.

### WooCommerce Items
- **D-06:** WooCommerce menu items (Cart, My Account, Checkout, Shop) require no special handling in the tree builder. WooCommerce registers them as standard WP nav menu items — `wp_get_nav_menu_items()` returns them like any other item. The tree builder treats them identically. WooCommerce-specific URL correctness verification is Phase 7 territory.

### Claude's Discretion
- Exact SELECT option values for Menu Source toggle (e.g., `'wp_menu'` / `'custom'`)
- WP Menu dropdown default value and placeholder text
- Editor hint message wording and CSS styling
- Whether to include an `<hr>` or divider between sections in the Content Tab
- Internal naming of the tree builder method (`build()`, `get_tree()`, etc.)
- Whether `classes` field is an array or space-separated string
</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Plugin Architecture
- `.planning/research/ARCHITECTURE.md` — Full system architecture, 3-pass tree builder pattern (Pattern 4), data flow diagrams, component responsibilities, anti-patterns
- `.planning/research/FEATURES.md` — Feature landscape, menu source requirements
- `.planning/research/STACK.md` — Technology stack decisions
- `.planning/research/PITFALLS.md` — Known issues from prior development (PHP reference bug, positional navigation)

### Requirements
- `.planning/REQUIREMENTS.md` — Phase 2 covers: WMEN-01, WMEN-02, WMEN-03, WMEN-04, WMEN-05

### Project Context
- `.planning/PROJECT.md` — Vision, core value, key decisions (3-pass algorithm, ID-based navigation)
- `.planning/phases/01-plugin-foundation-widget-shell/01-CONTEXT.md` — Phase 1 decisions: widget name, PSR-4 mapping, asset loading patterns
- `CLAUDE.md` — Technology stack, Elementor API usage, file structure conventions
</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `src/Elementor/Widget/DrillDownMenu.php` — Widget class with `_register_controls()` method where the new "Menu" section will be added. Currently has one section ("Trigger Button"). `render()` method handles trigger output.
- `src/Assets/Registrar.php` — Asset registration pattern. Not directly used in Phase 2 but establishes the register-only pattern.
- `src/Plugin.php` — Bootstrap class with `registerWidget()` method. No changes needed for Phase 2.

### Established Patterns
- **PSR-4 mapping:** `Devsroom_DDMM\MenuBuilder\WpNavTree` → `src/MenuBuilder/WpNavTree.php` (must create `src/MenuBuilder/` directory)
- **Elementor controls:** Widget uses `\Elementor\Controls_Manager::SELECT`, `TEXT`, `ICONS`, `CHOOSE` controls with `condition` arrays for visibility. Same pattern applies for Menu Source toggle + WP Menu dropdown.
- **Escaping:** All output uses `esc_attr()`, `esc_html()`, `esc_url()`. Tree builder should not escape — that's the renderer's job.
- **Section naming:** Existing section is `section_trigger`. New section should follow pattern (e.g., `section_menu`).

### Integration Points
- `wp_get_nav_menus()` — Returns all registered nav menu objects for the Elementor dropdown control
- `wp_get_nav_menu_items( $menu_slug )` — Returns flat array of menu item objects for the tree builder
- `get_registered_nav_menus()` — Alternative API for listing menu locations (may not be needed — `wp_get_nav_menus()` returns menu objects directly)
- Widget `_register_controls()` — Where the new "Menu" section and controls are added
- Widget `render()` — Currently only renders trigger. Phase 4 will integrate tree data for full drawer rendering. Phase 2 does NOT change render() output beyond possibly showing the editor hint.
</code_context>

<specifics>
## Specific Ideas

- 3-pass algorithm is decided and documented in ARCHITECTURE.md Pattern 4 — index by ID, link children to parents, extract roots. No PHP references.
- Menu Source toggle anticipates Phase 3 Custom Builder — adding the toggle now avoids control restructuring later.
- Tree builder is pure PHP with zero Elementor dependency — can be developed and tested in isolation.
</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.
</deferred>

---

*Phase: 02-wordpress-menu-source*
*Context gathered: 2026-06-13*
