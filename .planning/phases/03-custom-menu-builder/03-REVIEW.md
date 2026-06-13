---
phase: 03-custom-menu-builder
reviewed: 2026-06-13T07:45:00Z
depth: standard
files_reviewed: 3
files_reviewed_list:
  - src/MenuBuilder/CustomTree.php
  - src/Elementor/Widget/DrillDownMenu.php
  - src/MenuBuilder/WpNavTree.php
findings:
  critical: 0
  warning: 3
  info: 3
  total: 6
status: issues_found
---

# Phase 03: Code Review Report

**Reviewed:** 2026-06-13T07:45:00Z
**Depth:** standard
**Files Reviewed:** 3
**Status:** issues_found

## Summary

Reviewed the three files involved in Phase 3 (Custom Menu Builder): the new `CustomTree.php` class, the modified `DrillDownMenu.php` widget, and the reference `WpNavTree.php` for contract comparison.

The implementation is well-structured with thoughtful handling of documented pitfalls (Elementor URL array format, SWITCHER 'yes'/'' values, stack-based auto-clamping). The 7-field node contract matches `WpNavTree` exactly in field names and types. Escaping is properly applied throughout the widget's `render()` method.

However, there are two categories of issues:

1. **Data loss**: The `icon` field from the repeater is not included in the `CustomTree` node output. Since the repeater defines an `icon` control (ICONS type) but `CustomTree::build()` never reads or passes it through, custom menu items will lose their icons when the tree is consumed by the renderer in Phase 4.
2. **Defensive gaps**: Negative depth values are not clamped, and the `title_field` JS expression could produce extremely long strings with large depth values.

No security vulnerabilities, no hardcoded secrets, and no injection risks were found.

## Warnings

### WR-01: Icon data is silently discarded by CustomTree

**File:** `src/MenuBuilder/CustomTree.php:64-73`
**Issue:** The repeater defines an `icon` control (ICONS type, line 270-277 of DrillDownMenu.php), but `CustomTree::build()` never reads `$item['icon']` from the repeater data and does not include it in the returned node array. The 7-field contract (`id`, `title`, `url`, `target`, `classes`, `has_children`, `children`) has no `icon` field. When Phase 4's renderer consumes the tree, it will have no icon data for custom menu items, meaning icons set by the user will be silently ignored.

**Fix:** Add an `icon` field to the node contract in both `CustomTree.php` and `WpNavTree.php` (for consistency):

```php
// CustomTree.php — add icon extraction after URL handling (around line 59)
$icon = $item['icon'] ?? [];

// Then in the node array (line 65):
$node = [
    'id'           => $id,
    'title'        => $title,
    'url'          => $url,
    'target'       => $target,
    'icon'         => $icon,
    'classes'      => [],
    'has_children' => false,
    'children'     => [],
];
```

Also update `WpNavTree.php` to include `'icon' => []` in its node contract so both builders produce identical schemas.

### WR-02: Negative depth values not clamped

**File:** `src/MenuBuilder/CustomTree.php:43`
**Issue:** The `depth` field is cast to `(int)` but not clamped to a minimum of 0. The Elementor NUMBER control has `min => 0` set (line 262 of DrillDownMenu.php), but Elementor does not always enforce `min` on the server side -- a saved value or programmatic input could contain a negative depth. With `$depth = -1`, the condition `0 === $depth` is false, `empty($stack)` depends on state, and the item could be treated as a child of whatever is on the stack, producing an unexpected tree structure.

**Fix:**
```php
// Line 43 — clamp negative depths to 0
$depth = max( 0, (int) ( $item['depth'] ?? 0 ) );
```

### WR-03: Editor hint div lacks esc_attr on class but more importantly uses unescaped $menu_source in string comparison without validation

**File:** `src/Elementor/Widget/DrillDownMenu.php:408`
**Issue:** The `$menu_source` value comes from `$settings['menu_source']` which is a SELECT control with only two options (`wp_menu`, `custom`). While the strict string comparison (`===`) is safe here, if someone adds a third source type later, neither branch would match and no hint would show. This is a minor maintainability concern, not a runtime bug.

Additionally, the editor hint `<div class="ddmm-editor-hint">` on line 411 is not wrapped in an `esc_attr()` call for the class attribute. While the string is hardcoded (so no XSS risk), using `esc_attr()` is the WordPress coding standard for all attribute values.

**Fix:**
```php
echo '<div class="' . esc_attr( 'ddmm-editor-hint' ) . '">' . $hint . '</div>';
```

## Info

### IN-01: title_field JS expression does not limit depth for display

**File:** `src/Elementor/Widget/DrillDownMenu.php:296`
**Issue:** The `title_field` expression `{{{ depth > 0 ? "—".repeat( depth ) + " " : "" }}} {{{ label }}}` uses `String.repeat()` with the raw depth value. If a user enters a very large depth (e.g., 100), this would generate an em-dash string 100 characters long. While Elementor's NUMBER control UI makes this unlikely, a `Math.min(depth, 5)` guard would be defensive.

**Fix:**
```
'title_field' => '{{{ depth > 0 ? "—".repeat( Math.min( depth, 5 ) ) + " " : "" }}} {{{ label }}}',
```

### IN-02: Node `id` field type differs between CustomTree and WpNavTree

**File:** `src/MenuBuilder/CustomTree.php:66` vs `src/MenuBuilder/WpNavTree.php:43`
**Issue:** `WpNavTree` casts the ID to `(int)` on line 43, producing an integer. `CustomTree` uses a sequential `$id` counter (line 39) which starts at 0 and increments as a plain integer via `$id++`. Both are integers, so this is consistent. However, `CustomTree` IDs are sequential (1, 2, 3...) and have no relationship to the actual menu item, while `WpNavTree` IDs are WordPress post IDs. Any downstream consumer that assumes IDs are unique across both sources could conflict. This is acceptable for now since Phase 4 rendering uses IDs only for `data-panel-id` attributes within a single widget instance, but worth documenting.

**Fix:** No code change needed. Consider adding a `@note` in the PHPDoc of `CustomTree::build()` explaining that IDs are sequential, not database identifiers.

### IN-03: Repeater `title_field` uses triple mustache `{{{ }}}` (raw output)

**File:** `src/Elementor/Widget/DrillDownMenu.php:296`
**Issue:** Elementor's `title_field` supports both `{{ }}` (escaped) and `{{{ }}}` (raw) mustache syntax. Using `{{{ }}}` for the `label` field means HTML in label values would render in the Elementor panel repeater title. Since labels are user-entered text, this is unlikely to cause issues (Elementor's panel is not susceptible to XSS from its own controls), but `{{ label }}` would be more conservative. This matches Elementor's own documentation examples, so it is standard practice.

**Fix:** No change needed. This is the standard Elementor pattern.

---

_Reviewed: 2026-06-13T07:45:00Z_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_
