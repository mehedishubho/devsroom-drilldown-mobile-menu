# Phase 7 — Human UAT Matrix

> Manual verification of every live-behavior truth that static greps cannot prove. Each test maps to acceptance_criteria "Live-behavior flag" lines in Plans 07-01, 07-02, and 07-03.

**Status:** partial
**Started:** 2026-06-14T17:47:23Z
**Updated:** 2026-06-14T17:47:23Z

**Test environment:** A WordPress 6.5+ site with the Devsroom DrillDown Mobile Menu plugin active, Elementor Free 3.29+ installed, and at least one page with the drill-down menu widget placed and configured with a multi-level WP menu (3+ levels of nesting recommended). A modern browser with DevTools open (Chrome/Firefox recommended for the accessibility inspector).

**Screen reader:** NVDA (Windows) or VoiceOver (macOS) for tests #8 and #9.

---

## Summary

| Metric | Count |
|--------|-------|
| Total tests | 14 |
| Passed | 0 |
| Failed | 0 |
| Issues | 0 |
| Pending | 14 |

---

### 1. Esc navigates back one panel level

**Verifies:** A11Y-04 (back), D-02. **Plan ref:** 07-01 Task 07-01-02.

**Prerequisite:** Drawer open, user has drilled at least one level below root (history.length > 0).

**Steps:**
1. Open the drawer (click the trigger).
2. Click a parent item's chevron (›) to drill into a sub-panel.
3. Press `Escape` once.

**Expected:** The drawer navigates BACK one level (returns to the parent panel). The drawer does NOT close. Focus moves to the first focusable item in the returned panel.

**Evidence reference:** Esc precedence wired at `ddmm-frontend.js:355` (`onDocKeydown`) — calls `this.back()` first when `history.length > 0` (07-01-SUMMARY.md, "Esc precedence (D-02)" decision).

**result:** [pending]

---

### 2. Esc at root closes the drawer

**Verifies:** A11Y-04 (close), D-02. **Plan ref:** 07-01 Task 07-01-02.

**Prerequisite:** Drawer open at the root panel (no drilling yet, OR user has pressed Esc enough times to return to root).

**Steps:**
1. Open the drawer.
2. Press `Escape` once (do NOT drill first).

**Expected:** The drawer closes. Focus is restored to the trigger button. The Tab trap is detached (Tab on the page behind now works normally).

**Evidence reference:** Esc fallthrough to `this.close()` at `ddmm-frontend.js:355` (`onDocKeydown`) when `history.length === 0`; `detachDocListeners()` at line 343 removes the Tab trap handler (07-01-SUMMARY.md).

**result:** [pending]

---

### 3. Tab trap wraps focus within the drawer

**Verifies:** A11Y-05, D-01. **Plan ref:** 07-01 Task 07-01-02.

**Prerequisite:** Drawer open.

**Steps:**
1. Open the drawer.
2. Press `Tab` repeatedly until you reach the last focusable element (close button or last menu item chevron).
3. Press `Tab` one more time.
4. Press `Shift+Tab` repeatedly until you reach the first focusable element (close button).
5. Press `Shift+Tab` one more time.

**Expected:** Step 3 wraps focus back to the FIRST focusable element (close button). Step 5 wraps focus back to the LAST focusable element. Focus never escapes to the page behind the drawer while it is open.

**Evidence reference:** `trapTab()` at `ddmm-frontend.js:388`; `getFocusables()` at line 407 filters via `offsetParent !== null` (Pitfall 6 — hidden search results excluded).

**result:** [pending]

---

### 4. ArrowUp/ArrowDown move roving tabindex among siblings

**Verifies:** A11Y-06, D-11. **Plan ref:** 07-01 Task 07-01-02.

**Prerequisite:** Drawer open, active panel has at least 2 menu items.

**Steps:**
1. Open the drawer.
2. Press `ArrowDown`.
3. Press `ArrowDown` again.
4. Press `ArrowUp`.

**Expected:** Each `ArrowDown` moves focus to the NEXT sibling menu item. `ArrowUp` moves to the PREVIOUS. Wrap-around: pressing `ArrowDown` on the last item moves to the first; pressing `ArrowUp` on the first moves to the last. In DevTools, inspect the focused item: it carries `tabindex="0"` while its siblings carry `tabindex="-1"`.

**Evidence reference:** `moveRoving()` at `ddmm-frontend.js:422` — full reset to `tabIndex = -1` before setting target `tabIndex = 0` (Pitfall 3 mitigation); `onDrawerKeydown()` at line 319 dispatches ArrowUp/ArrowDown.

**result:** [pending]

---

### 5. Enter/Space activates chevron, back button, and leaf link

**Verifies:** A11Y-07, D-04/D-06. **Plan ref:** 07-01 Task 07-01-02.

**Prerequisite:** Drawer open.

**Steps:**
1. Open the drawer.
2. Tab/arrow to a parent item's chevron (›). Press `Enter`. Then press `Space` on another chevron.
3. Tab to the back button (← Back). Press `Enter`.
4. Tab to a leaf link (`<a href>`). Press `Enter`.

**Expected:** Step 2 drills into the sub-panel on both `Enter` and `Space`. Step 3 navigates back one level. Step 4 follows the link (native navigation; if close-after-link-click is on, the drawer closes too).

**Evidence reference:** No synthetic `preventDefault()` for Enter/Space (D-04 decision in 07-01-SUMMARY.md); native `<a>`/`<button>` activation handles both keys (A11Y-07).

**result:** [pending]

---

### 6. Focus moves to drawer on open, restored to trigger on close

**Verifies:** A11Y-08, D-03/D-07. **Plan ref:** 07-01 Task 07-01-02.

**Prerequisite:** Drawer closed initially.

**Steps:**
1. Focus the trigger button (Tab to it or click it).
2. Press `Enter` to open the drawer.
3. Inspect `document.activeElement` in DevTools.
4. Close the drawer via EACH close method, one at a time:
   a. Click the ✕ close button.
   b. Click the overlay.
   c. Press `Escape` (at root).
   d. Click a leaf link (if close-after-link-click is on).
5. After EACH close, inspect `document.activeElement`.

**Expected:** Step 3: focus is inside the drawer (on the D-03 target — the auto-opened current item if the current page is in the menu, else the first focusable). Step 5: after EVERY close method, focus is restored to the trigger button. No close path leaves focus stranded.

**Evidence reference:** `focusInitialTarget()` at `ddmm-frontend.js:446` (called on open, drill, back); `lastTrigger` captured at top of `close()` (line 140) before `classList.remove('ddmm-is-open')` — guarantees the trigger reference exists even during teardown; `lastTrigger.focus()` at lines 156-158 (D-07).

**result:** [pending]

---

### 7. Esc coordination with the search-input listener (no double-fire)

**Verifies:** Pitfall 1 (research), D-06. **Plan ref:** 07-01 Task 07-01-02.

**Prerequisite:** Drawer open, search enabled (`search_enabled = yes` in the widget settings).

**Steps:**
1. Open the drawer.
2. Tab to the search input and type a query (e.g. "shop").
3. With focus STILL in the search input, press `Escape` once.
4. Press `Escape` again.

**Expected:** Step 3: the search query is cleared, the input is blurred, and the drawer STAYS OPEN (the existing search-input listener handled this Esc; the new global listener early-returned). Step 4: now that focus has left the search input, the global listener fires — the drawer navigates back (or closes if at root). No double-action on step 3.

**Evidence reference:** `onDocKeydown()` at `ddmm-frontend.js:355` — early-returns when `document.activeElement === searchInput` (Pitfall 1 mitigation); pre-existing search-input Esc listener unchanged (now at line 648, originally 447).

**result:** [pending]

---

### 8. Screen reader announces panel context on drill/back

**Verifies:** D-08. **Plan ref:** 07-01 Task 07-01-01 (region emission) + 07-01 Task 07-01-02 (`announcePanelContext()`).

**Prerequisite:** NVDA or VoiceOver running. Drawer open.

**Steps:**
1. Open the drawer (root panel visible).
2. Listen for the SR announcement.
3. Drill into a sub-panel (click a chevron or press Enter on it).
4. Listen for the announcement.
5. Press `Escape` to go back.

**Expected:** Step 2: SR announces the root panel's context — the `<nav>` `aria-label` (default "Mobile Menu", or whatever the user configured). Step 4: SR announces the sub-panel's back-row parent name (e.g. "Shop"). Step 5: SR announces the returned panel's context. The `[data-ddmm-sr-status]` region is the source — confirmed via DevTools: its `textContent` matches the announcement.

**Evidence reference:** Empty `aria-live="polite" aria-atomic="true"` region emitted at `DrawerRenderer.php:73` (frontend only); `announcePanelContext()` at `ddmm-frontend.js:468` writes via `textContent` ONLY (ASVS V5, Threat T-07-01-01 mitigation).

**result:** [pending]

---

### 9. Screen reader announces search result count (including "No results")

**Verifies:** D-09. **Plan ref:** Already wired (DrawerRenderer.php emits the `<ul aria-live="polite" aria-relevant="additions">`; Phase 5 `filterSearch()` populates it; Plan 07-03 Task 07-03-04 wired the `window.ddmmI18n.noResults` bridge for the translated "No results" string).

**Prerequisite:** NVDA or VoiceOver running. Drawer open, search enabled.

**Steps:**
1. Open the drawer.
2. Tab to the search input.
3. Type a query that matches some items (e.g. "shop").
4. Listen.
5. Clear the query and type a query that matches nothing (e.g. "zzzzz").
6. Listen.

**Expected:** Step 4: SR announces the matching result items as they are added to the results list. Step 6: SR announces "No results" (the translated string from the `window.ddmmI18n` bridge — e.g. "Keine Ergebnisse" if the site is in German). The `[data-ddmm-search-results]` `<ul>` is the source.

**Evidence reference:** `<ul aria-live="polite" aria-relevant="additions">` emitted in DrawerRenderer (D-09 region — exactly 1 match per 07-01-SUMMARY); bridge lookup at `ddmm-frontend.js:710` — `( window.ddmmI18n && window.ddmmI18n.noResults ) || 'No results'` (Plan 07-03 Task 07-03-04).

**result:** [pending]

---

### 10. :focus-visible ring visible for keyboard, invisible for mouse

**Verifies:** D-10, A11Y-08. **Plan ref:** 07-02 Task 07-02-01.

**Prerequisite:** Drawer open.

**Steps:**
1. Open the drawer.
2. Press `Tab` repeatedly to move keyboard focus through close, back, menu items, chevrons, search input.
3. Observe the outline on each focused element.
4. Click (mouse) on a menu item, the close button, and the search input.
5. Observe whether an outline appears.

**Expected:** Step 3: each keyboard-focused element shows a visible `outline` (color from `--ddmm-focus-ring-color`, width from `--ddmm-focus-ring-width`, offset from `--ddmm-focus-ring-offset`). Step 5: mouse-clicked elements do NOT show an outline (because the rule uses `:focus-visible`, not `:focus`).

**Evidence reference:** Unified 6-surface `:focus-visible` rule at `ddmm-frontend.css:102`; 3 themeable vars at lines 19-21 (`--ddmm-focus-ring-color` inherits `--ddmm-trigger-color` so the ring auto-flips light/dark); legacy `.ddmm-search__input:focus` rule removed (07-02-SUMMARY.md).

**result:** [pending]

---

### 11. prefers-reduced-motion neutralizes transitions

**Verifies:** D-18. **Plan ref:** 07-02 Task 07-02-02.

**Prerequisite:** OS "reduce motion" preference enabled (Windows: Settings → Accessibility → Visual effects → Animation effects OFF; macOS: System Settings → Accessibility → Display → Reduce motion ON).

**Steps:**
1. Enable the OS reduce-motion preference.
2. Reload the page.
3. Open the drawer.
4. Drill into a sub-panel.
5. Press Escape to go back.

**Expected:** Steps 3-5: transitions complete near-instantly (~0.01ms — effectively instant to the user). The drawer does NOT visibly slide. Panel swap is immediate. Importantly, the outgoing panel's scroll position is correctly reset (transitionend cleanup at `ddmm-frontend.js` drill() — Pitfall 8 mitigation: 0.01ms duration guarantees the event still fires). Disable the OS preference and reload to confirm normal animation resumes.

**Evidence reference:** `@media (prefers-reduced-motion: reduce)` block at `ddmm-frontend.css:635` — duration is `0.01ms` NOT `0ms` (Pitfall 8 per 07-02-SUMMARY.md); covers `.ddmm-widget` descendants with `!important`.

**result:** [pending]

---

### 12. RTL baseline — no visible breakage under dir="rtl"

**Verifies:** D-19. **Plan ref:** 07-02 Task 07-02-03.

**Prerequisite:** Ability to set `dir="rtl"` on the page (e.g. via the browser DevTools Elements panel — edit the `<html>` tag to `<html dir="rtl">`, OR use a RTL-language WP installation).

**Steps:**
1. Set `dir="rtl"` on the page.
2. Reload.
3. Open the drawer.
4. Inspect the drawer position, the menu icon spacing, and the chevron position.

**Expected:** The drawer container anchors to the inline-start edge (right edge under RTL). The menu icon sits on the inline-start side of its label with `margin-inline-end` spacing. The chevron pushes to the inline-end side via `margin-inline-start: auto`. The layout does NOT visibly break (no overlapping elements, no off-screen overflow). NOTE: the drawer still slides from the LEFT (transform: translateX is untouched per D-19 — full RTL slide-direction is v2 RTL-01). The test verifies NO BREAKAGE, not full mirroring.

**Evidence reference:** 3 logical-property refactors at `ddmm-frontend.css` — line 161 `inset-inline-start: 0` (`.ddmm-drawer`), line 295 `margin-inline-end: 8px` (`.ddmm-menu__icon`), line 305 `margin-inline-start: auto` (`.ddmm-chevron`); `transform: translateX()` deliberately untouched (17 occurrences, D-19 deferral to v2).

**result:** [pending]

---

### 13. WooCommerce menu items render correctly in both WC states

**Verifies:** COMP-03, D-12, D-13. **Plan ref:** 07-04 Task 07-04-01 (static verification) + this test (live verification).

**Prerequisite:** A WP menu containing WooCommerce items (Cart, My Account, Checkout, Shop). Ability to activate/deactivate the WooCommerce plugin.

**Steps:**
1. Activate WooCommerce. Build (or confirm) a WP menu with Cart, My Account, Checkout, Shop items. Assign it to the drill-down widget.
2. Open the drawer. Inspect each WC menu item's href.
3. Deactivate WooCommerce. Reload the page.
4. Open the drawer. Inspect each WC menu item's href again.

**Expected:** Step 2: each WC menu item renders with its correct permalink (e.g. `/cart/`, `/my-account/`, `/checkout/`, `/shop/`). The links are clickable and navigate to the correct page. Step 4: the SAME URLs are rendered as-is (D-13 — dead links are NOT hidden, NOT disabled, NOT given `aria-disabled`; they render exactly as WP stored them). The plugin behaves identically in both states because it never detects WC (D-12 — agnostic by construction).

**Evidence reference:** Static proof from Task 07-04-01 — `grep -rnE "class_exists\(\s*['\"](Woocommerce|WooCommerce|WC)\b|wc_get_|aria-disabled" src/ assets/` returns 0 matches. `WpNavTree.php:45` reads `$item->url` directly (no WC branching); `CustomTree.php:53-57` extracts URL from Elementor control format (no WC branching).

**result:** [pending]

---

### 14. Translated strings display in a non-English locale

**Verifies:** COMP-04, D-15/D-16/D-17. **Plan ref:** 07-03 (full pipeline).

**Prerequisite:** A `.po`/`.mo` translation file for a non-English locale (e.g. `languages/devsroom-drilldown-mobile-menu-de_DE.po` + `.mo`) with at least the "No results", "Mobile Menu", "Close menu", "Back", "Search menu…" strings translated. Site language set to that locale (e.g. Settings → General → Site Language → Deutsch).

**Steps:**
1. Place the translated `.po`/`.mo` files in `languages/` (or `wp-content/languages/plugins/`).
2. Switch the site language to the target locale.
3. Reload the page.
4. Open the drawer. Inspect: the nav aria-label, the close button aria-label, the back button text, the search placeholder.
5. Tab to the search input and type a query that matches nothing.

**Expected:** Step 4: all PHP-emitted strings appear in the target locale (e.g. "Mobile Menu" → "Mobiles Menü", "Close menu" → "Menü schließen", "Back" → "Zurück", "Search menu…" → "Menü durchsuchen…"). Step 5: the "No results" message appears in the target locale (e.g. "Keine Ergebnisse") — confirming the `window.ddmmI18n` bridge is working end-to-end (PHP → wp_json_encode → JS → textContent).

**Evidence reference:** `Domain Path: /languages` at main plugin file line 8; `load_plugin_textdomain` first statement of `Plugin::init()` (lines 76-80); `wp_set_script_translations` at `Registrar.php` lines 40-44; `window.ddmmI18n` bridge at `Registrar.php` lines 49-57 (via `wp_json_encode`); JS bridge lookup at `ddmm-frontend.js:710`. `.pot` ships 12 msgid entries (Plan 07-03 Task 07-03-05).

**result:** [pending]

---

## Sign-Off

After all 14 tests pass, mark Phase 7 complete:

- [ ] Tests #1-#9 (keyboard + SR) pass.
- [ ] Tests #10-#12 (CSS a11y) pass.
- [ ] Test #13 (WC compatibility) passes in both states.
- [ ] Test #14 (i18n) passes in at least one non-English locale.

Any failure → file an issue against the plan referenced in the test's "Plan ref" line.

---

## Gaps

- Tests #1-#14 are all PENDING — no human execution has occurred yet. This document is the authoritative manual verification matrix; execution happens after Phase 7 code lands.
- Test #13 requires WooCommerce to be installed and activatable on the test site.
- Test #14 requires a hand-authored `.po`/`.mo` for a non-English locale (the shipped `.pot` is the template).
- Test #8 and #9 require NVDA (Windows) or VoiceOver (macOS) running.
