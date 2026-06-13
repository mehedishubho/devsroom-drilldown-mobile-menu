---
status: partial
phase: 05-frontend-drill-down-javascript
source: [05-VERIFICATION.md]
started: 2026-06-14T00:00:00Z
updated: 2026-06-14T00:00:00Z
---

## Current Test

[awaiting human testing]

## Tests

### 1. Drawer open/close animation + hamburger morph
expected: Click the trigger button in a published Elementor page — the drawer slides in from the left, the overlay fades in, and the hamburger icon morphs to an X. Click the overlay / close button — the drawer slides out and the X morphs back. `aria-expanded` toggles true/false; `aria-hidden` on drawer+overlay toggles in lockstep.
result: [pending]

### 2. Drill-down at depth + Back navigation
expected: With a multi-level menu (root > Shop > Categories > Shoes), clicking each parent chevron drills in: outgoing panel gets `ddmm-panel--exited-left`, incoming gets `ddmm-panel--active`, per the active animation type. ← Back plays the reverse. History stack survives multiple drills so Back from depth 3 returns to depth 2, then 1, then root.
result: [pending]

### 3. Four animation types + duration/easing
expected: For each Animation Type (Slide, Fade, Scale, Slide+Fade) set in the Elementor editor, drilling into a submenu produces the correct visual — Slide = horizontal translateX; Fade = in-place opacity; Scale = scale 0.92→1 + opacity; Slide+Fade = translateX + opacity. Duration (e.g. 1000ms) and Easing (e.g. ease-in-out) take effect on every panel transition and on drawer open/close.
result: [pending]

### 4. Live search filtering + Escape clear
expected: With Search enabled (`search_enabled = yes`), the search input shows below header / above panels. Typing (e.g. "shoes", "s", "zzz") renders matches with title + breadcrumb (200ms debounce); "zzz" shows the No-results item. Escape clears the input AND removes `ddmm-search-active` (panels reappear). Clicking a parent result drills into the target panel; clicking a leaf result navigates (and closes per EXTR-04).
result: [pending]

### 5. Auto-open URL match + item/ancestor markers
expected: Navigate to a deep page (e.g. /shop/categories/shoes/) matching a menu item, reload, and open the drawer. With `auto_open` enabled (default), the drawer is already drilled to the matching item's panel (instant, no animation flash); the matching `<li>` has `ddmm-current-item` and all ancestor `<li>`s have `ddmm-current-ancestor`. Back walks the auto-drilled chain correctly.
result: [pending]

### 6. Close behaviors (EXTR-04 / EXTR-05)
expected: Same-tab link closes (when `close_after_link=on); new-tab (target=_blank) link stays open; chevron stays open; overlay closes when `close_on_overlay=on` and no-ops when off; ✕ button always closes.
result: [pending]

## Summary

total: 6
passed: 0
issues: 0
pending: 6
skipped: 0
blocked: 0

## Gaps
