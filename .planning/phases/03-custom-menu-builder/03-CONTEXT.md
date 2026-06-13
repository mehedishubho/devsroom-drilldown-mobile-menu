# Phase 3: Custom Menu Builder - Context

**Gathered:** 2026-06-13
**Status:** Ready for planning

<domain>
## Phase Boundary

Users can build a complete menu from scratch using a flat repeater control in the Elementor Content Tab. Each repeater item has Label, URL, Depth, Icon, and Open in New Tab fields. The Depth field drives nesting — a flat list in the Elementor editor becomes nested panels in the rendered output. A stack-based algorithm converts the flat repeater data into a nested tree matching the WpNavTree output contract.

**In scope:** Custom Menu Builder repeater controls (visible when Menu Source = 'custom'), stack-based depth-field tree builder class (`src/MenuBuilder/CustomTree.php`), visual hierarchy display in Elementor editor (indent dashes), render() integration calling CustomTree::build(), editor hint for empty custom menu.

**Out of scope:** Drawer HTML rendering (Phase 4), drill-down JS (Phase 5), Style Tab (Phase 6), keyboard/accessibility (Phase 7).
</domain>

<decisions>
## Implementation Decisions

### Depth Field Control
- **D-01:** The Depth field uses an Elementor NUMBER control with min=0, step=1, default=0, and no maximum cap. This gives users flexibility while keeping the UI standard for Elementor. The NUMBER control is preferred over a SELECT dropdown because it's more natural and doesn't impose an arbitrary depth limit.

### Tree Output Contract
- **D-02:** CustomTree outputs the identical 7-field node contract as WpNavTree: `id` (int), `title` (string), `url` (string), `target` (string), `classes` (array), `has_children` (bool), `children` (array). Phase 4's renderer will handle a single `$tree` variable regardless of whether the source is WP Menu or Custom Builder. This eliminates branching in Phase 4.

### Invalid Depth Handling
- **D-03:** The stack-based algorithm inherently auto-clamps depth jumps (e.g., depth 0 → depth 3 becomes depth 0 → depth 1). No extra validation or editor warnings needed. The algorithm's natural behavior produces clean output.

### Repeater Control Design
- **D-04:** Repeater is visible only when `menu_source === 'custom'` (using Elementor `condition` array, matching the existing `wp_menu_id` pattern).
- **D-05:** Repeater field order: Label (TEXT) → URL (URL) → Depth (NUMBER, min=0, step=1, default=0) → Icon (ICONS) → Open in New Tab (SWITCHER).
- **D-06:** Repeater starts empty — no default items. User adds items manually.
- **D-07:** Per CMEN-03, repeater title formatting shows indent dashes based on depth value: Root items show as-is, depth 1 shows "— Item", depth 2 shows "—— Item", etc. This uses Elementor's `title_field` property with dynamic `{{{ depth }}}` interpolation to generate visual hierarchy.

### Empty State
- **D-08:** When Menu Source is 'custom' and no repeater items exist (or tree is empty), follows Phase 2's D-05 pattern: editor shows hint "Add menu items to display", frontend renders zero menu HTML. The existing empty-state block in render() handles this — when `$tree` is empty, the same hint/return logic applies.

### Custom Tree Builder Class
- **D-09:** New class `CustomTree` at `src/MenuBuilder/CustomTree.php` (namespace `Devsroom_DDMM\MenuBuilder`), paralleling `WpNavTree`. Static `build(array $items): array` method. Zero Elementor dependency — receives raw repeater data array, returns tree nodes.
- **D-10:** For custom items, `id` field uses sequential integers starting from 1 (generated at build time). `classes` field is always an empty array `[]` (custom items don't have WP menu CSS classes). `target` is `_blank` when "Open in New Tab" is enabled, empty string otherwise.

### Claude's Discretion
- Exact label text for repeater fields and controls
- Stack-based algorithm implementation details (data structure, traversal order)
- Whether to add `data-depth` attributes in the editor for enhanced preview
- Edge case handling for empty URL, missing labels, etc.
- Internal naming of helper methods
</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Plugin Architecture
- `.planning/research/ARCHITECTURE.md` — Full system architecture, stack-based tree builder pattern (Pattern 5), data flow diagrams, component responsibilities
- `.planning/research/FEATURES.md` — Feature landscape, custom menu builder requirements
- `.planning/research/STACK.md` — Technology stack decisions
- `.planning/research/PITFALLS.md` — Known issues from prior development

### Requirements
- `.planning/REQUIREMENTS.md` — Phase 3 covers: CMEN-01, CMEN-02, CMEN-03, CMEN-04, CMEN-05

### Project Context
- `.planning/PROJECT.md` — Vision, core value, key decisions (stack-based algorithm, depth-field nesting)
- `.planning/phases/02-wordpress-menu-source/02-CONTEXT.md` — Phase 2 decisions: D-01 through D-06 (menu source toggle, tree node contract, empty state pattern)
- `CLAUDE.md` — Technology stack, Elementor API usage, file structure conventions

### Source Files (contracts to match)
- `src/MenuBuilder/WpNavTree.php` — Tree node output contract (7 fields) that CustomTree MUST match
- `src/Elementor/Widget/DrillDownMenu.php` — Widget with existing section_menu, menu_source control, render() empty-state block
</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `src/Elementor/Widget/DrillDownMenu.php` — Widget class with `_register_controls()` where custom builder repeater will be added (inside existing `section_menu`, after `wp_menu_id`, with `condition` on `menu_source === 'custom'`). `render()` already has the empty-state block and `$tree` variable — CustomTree call integrates alongside WpNavTree call.
- `src/MenuBuilder/WpNavTree.php` — Tree builder class establishing the output contract pattern. CustomTree parallels this structure.
- `src/Plugin.php` — PSR-4 autoloader maps `Devsroom_DDMM\MenuBuilder\CustomTree` to `src/MenuBuilder/CustomTree.php` automatically.

### Established Patterns
- **Elementor REPEATER control:** Standard pattern — create `new \Elementor\Repeater()`, add controls to it, then `$this->add_control('items', ['type' => Repeater, 'fields' => $repeater->get_controls()])`. Used across Elementor widgets.
- **Condition arrays:** Both `wp_menu_id` and custom repeater use `'condition' => ['menu_source' => 'wp_menu']` / `'condition' => ['menu_source' => 'custom']` for mutual exclusivity.
- **Tree builder pattern:** Static `build()` method accepting raw data, returning `array<int, array>` of tree nodes. Pure PHP, zero Elementor dependency.
- **Node contract:** 7 fields per node — `id`, `title`, `url`, `target`, `classes`, `has_children`, `children`.

### Integration Points
- `DrillDownMenu::_register_controls()` — Add repeater control inside existing `section_menu`, after `wp_menu_id`, with `condition => ['menu_source' => 'custom']`
- `DrillDownMenu::render()` — Add `'custom' === $menu_source && ! empty($settings['custom_items'])` branch calling `CustomTree::build($settings['custom_items'])`, setting `$tree` the same way as WpNavTree path
- `src/MenuBuilder/CustomTree.php` — New file, autoloaded, parallels WpNavTree
</code_context>

<specifics>
## Specific Ideas

- Stack-based algorithm is decided and documented in ARCHITECTURE.md Pattern 5 — uses a stack to track current ancestry, pushes/pops based on depth value changes
- Repeater `title_field` uses Elementor's dynamic placeholder syntax (e.g., `{{{ label }}}` or `{{{ depth }}} — {{{ label }}}`) to show indent dashes in the editor
- The custom tree builder is pure PHP with zero Elementor dependency — same design as WpNavTree
- Both menu sources (WP and Custom) feed into the same `$tree` variable in render(), so Phase 4 has one rendering path
</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.
</deferred>

---

*Phase: 03-custom-menu-builder*
*Context gathered: 2026-06-13*
