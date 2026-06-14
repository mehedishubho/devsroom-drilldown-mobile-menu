---
phase: quick
plan: 260615-0ue
subsystem: plugin-lifecycle
tags: [uninstall, cleanup, multisite, wordpress]
requires:
  - "WordPress 6.5+ (uses get_sites() modern API, guaranteed by ABSPATH baseline)"
provides:
  - "uninstall.php — canonical WordPress uninstaller entry point"
  - "devsroom_drilldown_mobile_menu_uninstall action hook for third-party cleanup"
affects:
  - "Plugin deletion via WP admin Plugins screen"
tech-stack:
  added: []
  patterns:
    - "WP_UNINSTALL_PLUGIN direct-access guard (NOT ABSPATH)"
    - "Forward-compatible empty options array (single source of truth for future option cleanup)"
    - "Balanced switch_to_blog()/restore_current_blog() multisite iteration"
key-files:
  created:
    - uninstall.php
  modified: []
decisions:
  - "Used WP_UNINSTALL_PLUGIN guard instead of ABSPATH — ABSPATH is defined on every request and would not block direct browser access"
  - "Kept empty options array with commented future keys rather than omitting the deletion logic — makes extension a one-line change"
  - "Declared global $wpdb in multisite branch even though get_sites() doesn't use it directly — documents global-state context and avoids future maintainer surprise"
  - "No declare(strict_types=1) — non-namespaced procedural file calling WP API functions with mixed-type returns; matches main plugin file and src/ house style"
metrics:
  duration: 1min
  completed: 2026-06-14T18:41:03Z
  tasks: 1
  files: 1
---

# Quick Task 260615-0ue: Add uninstall.php Cleanup File at Plugin Root Summary

Added WordPress-canonical `uninstall.php` at plugin root — a forward-compatible cleanup stub guarded by `WP_UNINSTALL_PLUGIN` that handles single-site + multisite option deletion and fires a `devsroom_drilldown_mobile_menu_uninstall` action hook for third-party extensibility.

## What Was Built

A single file, `uninstall.php` (76 lines), at the plugin root (sibling of `devsroom-drilldown-mobile-menu.php`). WordPress core loads this file directly — with NO plugin autoloader, NO namespace context, NO bootstrap — when an admin deletes the plugin via the "Plugins" screen. The file is plain procedural PHP, structured in seven layers:

1. **Opening `<?php`** with no closing `?>` (WordPress standard for pure-PHP files).
2. **File header DocBlock** mirroring the main plugin file's identity (Plugin Name, Version 0.0.01, Author MEHEDI HASSAN SHUBHO, Text Domain, `@package Devsroom_DDMM`).
3. **Direct-access guard** — `if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }` as the first executable line. This is stronger than the `ABSPATH` guard used in normal plugin files: `ABSPATH` is defined on every front-end and admin request, so it would NOT prevent direct browser access to `uninstall.php`. `WP_UNINSTALL_PLUGIN` is set by WP core ONLY on a legitimate delete action.
4. **Structured options list** — `$devsroom_drilldown_options = array(...)`, currently empty with two commented future keys (`_settings`, `_version`). Verified empty: grep for `get_option|update_option|add_option|delete_option|set_transient|set_site_transient|add_post_meta|update_post_meta|wp_schedule_event` across `src/` returned zero matches.
5. **Single-site deletion** — `foreach` loop calling `delete_option()` for each key.
6. **Multisite deletion** — `if ( is_multisite() )` branch: `get_sites(array('fields'=>'ids','number'=>0,...))` to fetch all site IDs efficiently, `switch_to_blog()` per site, delete options, `restore_current_blog()` once. Plus `delete_site_option()` for network-wide options in `wp_sitemeta`.
7. **Extensibility hook** — `do_action( 'devsroom_drilldown_mobile_menu_uninstall' )` as the final line, so third-party add-ons can run their own cleanup.

## Verification Results

| Check | Method | Result |
|-------|--------|--------|
| PHP syntax | `php -l uninstall.php` | "No syntax errors detected", exit 0 |
| Guard uses WP_UNINSTALL_PLUGIN | grep `WP_UNINSTALL_PLUGIN` | Found at lines 6, 17 |
| ABSPATH NOT present | grep `ABSPATH` | No matches (correct — ABSPATH would be wrong guard) |
| No namespace/use/Devsroom_DDMM reference | grep | Only `@package Devsroom_DDMM` DocBlock tag (annotation, not code reference) |
| No require/include | grep | No matches |
| No closing `?>` | grep `\?>` | No matches |
| Required functions present | grep | `is_multisite()`, `switch_to_blog`, `restore_current_blog`, `delete_option`, `delete_site_option`, `do_action` all present |
| Identity fields in DocBlock | grep | Plugin Name, Version 0.0.01, Author, Text Domain, `@package Devsroom_DDMM` all present |
| Minimum line count | `wc -l` | 76 lines (≥45 required) |

All verification criteria from PLAN.md met.

## How It Works

When an admin clicks "Delete" on the plugin in WP admin → Plugins screen:

1. WordPress core defines `WP_UNINSTALL_PLUGIN` constant.
2. WordPress includes `uninstall.php` directly (the plugin's main file is NOT loaded — no autoloader, no `Plugin::get_instance()`, no hooks).
3. The guard passes (constant is defined), execution continues.
4. The empty options loop runs (currently a no-op, ready for future options).
5. On multisite, the network-wide iteration runs across all blogs.
6. The `devsroom_drilldown_mobile_menu_uninstall` action fires so extensions can clean up their own data.
7. WordPress then removes the plugin's files from disk.

When a browser hits `uninstall.php` directly: `WP_UNINSTALL_PLUGIN` is undefined → `exit` immediately. No cleanup runs, no data exposed.

## Deviations from Plan

None - plan executed exactly as written.

## Known Stubs

The `$devsroom_drilldown_options` array is intentionally empty. This is NOT a stub that prevents the plan's goal — it is the correct state of a forward-compatible cleanup stub for a plugin that (verified) stores zero persistent data. Inline comments document how to extend it (uncomment / append option keys). The deletion loop, multisite branch, and action hook are all fully wired and production-ready; they simply have nothing to delete today.

## Threat Flags

None. The file introduces no new network endpoints, no auth paths, no file access patterns beyond WordPress's own `delete_option()` / `delete_site_option()` / `do_action()` APIs, which are the designated cleanup primitives. The `WP_UNINSTALL_PLUGIN` guard is the canonical WordPress security boundary for uninstaller files.

## Self-Check: PASSED

- [x] `uninstall.php` exists at plugin root — FOUND
- [x] Commit `d195b07` exists in git log — FOUND
