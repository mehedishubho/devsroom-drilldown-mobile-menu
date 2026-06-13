---
status: partial
phase: 02-wordpress-menu-source
source: [02-VERIFICATION.md]
started: 2026-06-13T06:00:00.000Z
updated: 2026-06-13T06:00:00.000Z
---

## Current Test

[awaiting human testing]

## Tests

### 1. Content Tab Menu Section UI
expected: Elementor editor shows a "Menu" section below "Trigger Button" with a divider. "Menu Source" dropdown shows "WordPress Menu" and "Custom Builder". "Select Menu" dropdown lists all registered WP nav menus by name.
result: [pending]

### 2. Multi-Level Menu Tree Structure
expected: Create a 3+ level menu in WP Admin. Select it in the widget. Temporarily add `error_log( print_r( $tree, true ) )` before the empty-state check. Confirm nested `children` arrays at all depths with correct `has_children` flags.
result: [pending]

### 3. WooCommerce Menu Item Compatibility
expected: Activate WooCommerce. Add Cart, My Account, Checkout, Shop to a menu. Verify they appear in the tree as standard nodes with correct resolved URLs (no special handling).
result: [pending]

### 4. Empty-State Editor Hint vs Frontend
expected: With no menu selected, Elementor editor preview shows "Select a menu to display" hint. Published page source shows the trigger button HTML but zero menu/drawer HTML.
result: [pending]

## Summary

total: 4
passed: 0
issues: 0
pending: 4
skipped: 0
blocked: 0

## Gaps
