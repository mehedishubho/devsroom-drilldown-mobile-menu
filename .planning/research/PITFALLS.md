# Pitfalls Research

**Domain:** WordPress Elementor widget plugin — mobile drill-down menu
**Researched:** 2026-06-12
**Confidence:** HIGH (corroborated by official Elementor docs, WordPress core docs, W3C/WCAG references, and project-specific known issues from PROJECT.md)

## Critical Pitfalls

### Pitfall 1: Elementor JS Init Timing — `elementorFrontend` Is `undefined`

**What goes wrong:**
Accessing `elementorFrontend.hooks` before Elementor has fully initialized causes a `TypeError: Cannot read properties of undefined` crash. The widget's JavaScript never runs, and the mobile menu is completely non-functional on the frontend.

**Why it happens:**
Elementor loads its frontend scripts asynchronously. The `elementorFrontend` global object does not exist until Elementor fires its internal `elementor/frontend/init` event. Scripts enqueued via `wp_enqueue_script` run at `DOMContentLoaded` by default, which often fires before Elementor's init event. This is the single most commonly reported JS issue for custom Elementor widgets.

**How to avoid:**
Use the dual-path init pattern documented in PROJECT.md:
1. Primary path: Listen for the `elementor/frontend/init` event, then call `elementorFrontend.hooks.addAction('frontend/element_ready/ddmm-drilldown-menu.default', callback)`.
2. Fallback path: On `DOMContentLoaded`, check if `elementorFrontend` already exists (covers dynamically loaded pages and PJAX scenarios).
3. Double-init guard: Set a `data-ddmm-init` attribute on the container after first init; skip re-initialization if already present.

```javascript
// Correct pattern
function initDDMM() { /* ... */ }
jQuery(window).on('elementor/frontend/init', initDDMM);
document.addEventListener('DOMContentLoaded', function() {
    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.config) {
        initDDMM();
    }
});
```

**Warning signs:**
- Browser console shows `TypeError` referencing `elementorFrontend` on page load.
- Menu works in Elementor editor preview but not on the live frontend.
- Menu works after a full page refresh but not on AJAX/PJAX navigation.

**Phase to address:**
Phase 1 (Widget Skeleton + JS Handler) — get the init pattern right from the start; retrofitting it is painful because every JS feature depends on correct initialization.

---

### Pitfall 2: PHP Menu Tree Building — Reference Corruption with `foreach &$ref`

**What goes wrong:**
Using PHP references (`&$item`) in a `foreach` loop to build the nested menu tree silently corrupts the menu data. Symptoms include duplicated items, missing children, wrong nesting depth, or items appearing under the wrong parent. The bug is intermittent and may not appear with small test menus.

**Why it happens:**
PHP's `foreach` with references leaves the reference variable bound after the loop ends. A subsequent `foreach` over the same array without `unset($ref)` modifies the last element. This is a well-documented PHP footgun that has bitten WordPress plugin developers repeatedly. The project already encountered this in v1.3.0 and resolved it.

**How to avoid:**
Use the 3-pass ID-based approach documented in PROJECT.md:
1. Pass 1: Index all menu items by their database ID.
2. Pass 2: Assign each item's children by matching `menu_item_parent` to the indexed ID map.
3. Pass 3: Extract only root-level items (parent = 0) as the tree.

Never use `foreach ($items as &$item)` for menu tree construction. If references are absolutely necessary elsewhere, always `unset()` the reference variable immediately after the loop.

**Warning signs:**
- Menu renders correctly with 3-4 items but breaks with 10+ items or deep nesting.
- Last menu item appears duplicated or its children are wrong.
- Bug disappears when you reorder menu items in WordPress admin.

**Phase to address:**
Phase 1 (WP Menu Source + Tree Builder) — the tree builder is foundational; test with large menus (15+ items, 4+ depth levels) immediately.

---

### Pitfall 3: CSS Transitions Not Using GPU Compositing — Janky Mobile Animations

**What goes wrong:**
Menu panel slide transitions stutter, tear, or drop frames on mobile devices. The animation looks smooth on desktop Chrome DevTools but terrible on actual mid-range Android phones or older iPhones.

**Why it happens:**
Animating `left`, `right`, `margin-left`, or `width` triggers layout recalculation on every frame. Mobile browsers paint these synchronously on the main thread. Only `transform` and `opacity` are GPU-composited and can animate at 60fps on mobile hardware.

**How to avoid:**
- Use `transform: translateX()` for all panel sliding, never `left`/`right`/`margin`.
- Use `opacity` for fade effects, never `visibility` with transition.
- Add `will-change: transform` to panel containers (but sparingly — only on the panels that will animate).
- Use `translate3d(0, 0, 0)` or `translateZ(0)` as an initial hint to promote elements to their own GPU layer.
- Set `backface-visibility: hidden` on animated panels to prevent flickering.

```css
/* Correct */
.ddmm-panel {
    transform: translateX(100%);
    transition: transform var(--ddmm-duration) var(--ddmm-easing);
    will-change: transform;
}
.ddmm-panel.is-active {
    transform: translateX(0);
}

/* Wrong — causes layout thrash */
.ddmm-panel {
    left: 100%;
    transition: left 300ms ease;
}
```

**Warning signs:**
- Chrome DevTools Performance tab shows layout thrashing during animation.
- Animation is smooth on iOS but janky on Android (or vice versa).
- User reports of "laggy" or "glitchy" menu on specific devices.

**Phase to address:**
Phase 2 (Panel Navigation + Transitions) — build the animation system correctly from day one; retrofitting layout-triggering animations to use transforms requires rewriting all panel CSS.

---

### Pitfall 4: Misusing ARIA `role="menu"` on Site Navigation

**What goes wrong:**
Adding `role="menu"` to the navigation container triggers screen reader "application menu" mode, which changes keyboard behavior expectations. Screen readers stop reading the menu items as navigation links and instead treat them as application menu items. This makes the menu unusable for screen reader users and fails WCAG compliance.

**Why it happens:**
Developers see "menu" in the ARIA specification and assume it applies to site navigation menus. The ARIA `menu` role is designed for application-style menus (like a File/Edit menu in a desktop app), not website navigation. This is the most common accessibility mistake in navigation components.

**How to avoid:**
- Use `<nav>` element with `aria-label="Mobile Navigation"` as the outer container.
- Use `role="navigation"` only if not using a `<nav>` element (rare).
- Do NOT use `role="menu"` or `role="menuitem"` on site navigation.
- Use `aria-expanded="true/false"` on parent items that have children.
- Use `aria-current="page"` on the current page link.
- Use `aria-hidden="true"` on off-screen panels, toggle to `false` when visible.
- Manage focus: move focus to the first item in a new panel when it opens; return focus to the parent item when going back.
- Trap Tab focus within the open drawer.

**Warning signs:**
- Screen reader (NVDA/JAWS/VoiceOver) announces the menu as "application" or "menu bar."
- Keyboard users cannot Tab through menu items predictably.
- Accessibility audit flags incorrect ARIA roles.

**Phase to address:**
Phase 3 (Accessibility) — but plan the HTML structure in Phase 1 so the markup supports ARIA attributes from the start. Retrofitting correct ARIA roles into a `role="menu"` structure requires changing the entire markup pattern.

---

### Pitfall 5: Loading Assets on Every Page (Conditional Loading Failure)

**What goes wrong:**
The plugin's CSS and JS are loaded on every page of the WordPress site, even pages that don't use the drill-down menu widget. This adds HTTP requests and parse time to every page, slowing down the entire site.

**Why it happens:**
Developers enqueue scripts/styles on `wp_enqueue_scripts` hook (which fires on every page load) instead of using Elementor's widget dependency system. Some tutorials for custom Elementor widgets demonstrate this incorrect approach.

**How to avoid:**
Use Elementor's built-in conditional asset loading:
1. Register scripts/styles with `wp_register_script()` and `wp_register_style()` in the plugin bootstrap (not enqueue — just register).
2. In the widget class, override `get_script_depends()` to return the script handles.
3. Override `get_style_depends()` to return the style handles.
4. Elementor automatically enqueues them only on pages where the widget is present.

```php
public function get_script_depends() {
    return [ 'ddmm-frontend-script' ];
}
public function get_style_depends() {
    return [ 'ddmm-frontend-style' ];
}
```

**Warning signs:**
- Plugin CSS/JS appears in `<head>` on pages without the menu widget.
- Network waterfall shows plugin assets loading on blog posts, archives, etc.
- PageSpeed Insights flags unused JavaScript from the plugin.

**Phase to address:**
Phase 1 (Widget Skeleton) — set up `get_script_depends()` and `get_style_depends()` from the first widget commit. This is architecture, not a feature you add later.

---

### Pitfall 6: WooCommerce Endpoints Not Recognized as "Current Page"

**What goes wrong:**
The "auto-open current page path" feature works for regular WordPress pages but fails for WooCommerce endpoints like `/my-account/orders/`, `/checkout/order-received/`, or `/cart/`. The menu either highlights nothing or highlights the wrong item.

**Why it happens:**
WooCommerce uses custom rewrite endpoints and virtual pages. Functions like `is_page()` return false for WooCommerce endpoints. The URL matching logic that works for regular pages does not account for WooCommerce's URL structure. Additionally, WooCommerce cart/my-account/checkout URLs may have query parameters or fragments that break simple URL comparison.

**How to avoid:**
- Use `wc_get_page_id()` to get WooCommerce page IDs and compare against the current queried object.
- For endpoints, check against `get_query_var()` in addition to URL matching.
- Normalize URLs before comparison (strip trailing slashes, remove query strings).
- Test with WooCommerce active AND inactive — the plugin must not crash when WooCommerce is not installed.
- Wrap WooCommerce-specific checks in `class_exists('WooCommerce')` guards.

**Warning signs:**
- "Auto-open current page" works on blog pages but not on shop/cart/checkout.
- PHP fatal error or warning when WooCommerce is deactivated.
- Cart page shows no active menu item highlighting.

**Phase to address:**
Phase 2 (WooCommerce Compatibility) — after the core menu works, add WooCommerce-specific URL resolution before implementing auto-open-current-page.

---

### Pitfall 7: Editor vs. Frontend CSS Discrepancy

**What goes wrong:**
The menu looks correct in Elementor's editor preview but broken on the live frontend (or vice versa). Colors, spacing, transitions, or layout differ between the two contexts. Every time the user edits style settings, the result does not match what they configured.

**Why it happens:**
Elementor's editor preview iframe injects its own CSS context. The editor adds extra wrapper elements, applies default styles, and uses an isolated CSS scope. Additionally, Elementor's improved CSS loading system (introduced in 3.27+) requires widgets to explicitly declare style dependencies via `get_style_depends()`. Missing this declaration means styles may not load on the frontend even though they appear in the editor.

**How to avoid:**
- Always declare style dependencies via `get_style_depends()`.
- Use Elementor's responsive controls (`{{WRAPPER}}` selectors) for widget styling — do not hardcode selectors that assume a specific DOM structure.
- Test widget output in both contexts: editor preview AND live frontend.
- Use "Regenerate CSS & Data" in Elementor Tools during development to clear stale CSS.
- Scope all custom CSS under the widget's unique class to prevent conflicts with themes and other plugins.

**Warning signs:**
- Styles update live in editor but not on frontend after save.
- Frontend renders with default/unstyled appearance.
- "Regenerate CSS" fixes it temporarily but it breaks again on next edit.

**Phase to address:**
Phase 1 (Widget Skeleton + Controls) — set up the style dependency declaration from the start. Phase 4 (Style Tab) — verify editor/frontend parity for every style control.

---

### Pitfall 8: Double-Nested ZIP Structure on Distribution

**What goes wrong:**
When users install the plugin ZIP file via WordPress Plugins > Add New > Upload Plugin, WordPress extracts it and creates a directory like `wp-content/plugins/devsroom-drilldown-mobile-menu/devsroom-drilldown-mobile-menu/`. WordPress cannot find the main plugin file in the root of the first directory, so the plugin does not appear in the installed plugins list.

**Why it happens:**
Most build scripts and shell `zip` commands include the parent directory name in the archive paths. For example, `zip -r devsroom-drilldown-mobile-menu.zip devsroom-drilldown-mobile-menu/` creates entries like `devsroom-drilldown-mobile-menu/devsroom-drilldown-mobile-menu.php`. WordPress expects the ZIP to extract to a single directory containing the plugin files directly. The project already experienced "shell brace expansion in ZIP" as a known issue in v1.3.0.

**How to avoid:**
- Build the ZIP from inside the plugin directory, not from the parent:
  ```bash
  cd devsroom-drilldown-mobile-menu && zip -r ../devsroom-drilldown-mobile-menu.zip . -x ".git/*" ".planning/*"
  ```
- Verify the ZIP structure before release: `unzip -l plugin.zip | head -20` should show `devsroom-drilldown-mobile-menu.php` as a top-level entry inside a single root directory.
- Test the ZIP by uploading it to a fresh WordPress installation.
- If distributing via WordPress.org SVN: trunk/ contains the plugin files directly; tags/{version}/ mirrors trunk/ structure.

**Warning signs:**
- "The package could not be installed. No valid plugins were found." error on upload.
- ZIP extracts to nested directories.
- Plugin appears in filesystem but not in WordPress admin plugin list.

**Phase to address:**
Phase 5 (Distribution/Packaging) — create a build script and verify ZIP structure. Include ZIP verification as a pre-release checklist item.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Loading assets globally on every page | Faster to implement, no dependency registration | Slows every page on the site, hurts PageSpeed scores, users uninstall plugin | Never — use `get_script_depends()`/`get_style_depends()` |
| Using jQuery for DOM manipulation | Easier DOM queries, wider WP compatibility | Conflicts with "no jQuery" requirement, larger JS bundle, slower on mobile | Never — project requires pure ES6 |
| Skipping ARIA attributes in initial markup | Fewer attributes to manage, faster initial render | Full accessibility retrofit requires markup changes, breaks screen reader users | Never — build ARIA into Phase 1 markup |
| Hardcoding panel IDs instead of `uniqid()` | Simpler, predictable IDs | Breaks when multiple instances of the widget are on the same page | Never — always use unique IDs per instance |
| Flat CSS file without Elementor responsive controls | Quick styling | Cannot use Elementor's built-in responsive editing, hard to maintain | MVP prototype only — must migrate to Elementor controls |
| Single instance testing only | Faster development | Multiple widget instances on one page collide (ID conflicts, event handlers fire on wrong element) | Only during initial prototyping |

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Elementor Editor | Enqueueing JS via `wp_enqueue_scripts` only — script never loads in editor preview | Register handler via `elementor/frontend/init` event; Elementor manages loading in both contexts |
| Elementor Style System (3.27+) | Not declaring `get_style_depends()` — styles vanish on frontend with improved CSS loading | Always return style handles from `get_style_depends()`, even for a single CSS file |
| WordPress Nav Menu API | Calling `wp_nav_menu()` directly and parsing HTML output | Use `wp_get_nav_menu_items()` to get structured data, build tree in PHP |
| WooCommerce | Assuming WooCommerce is always active; calling `wc_*` functions unconditionally | Guard all WooCommerce calls with `class_exists('WooCommerce')` or `in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))` |
| WooCommerce Endpoints | Using `is_page()` to detect current page — returns false for WC endpoints | Use `wc_get_page_id()` for WC pages, `get_query_var()` for endpoints |
| WordPress Customizer | Widget settings not previewed in Customizer because render method uses static values | Ensure `render()` uses `$this->get_settings_for_display()` which is Customizer-aware |
| PSR-4 Autoloader | Case mismatch between namespace and directory (e.g., namespace `Devsroom_DDMM` but directory `devsroom_ddmm`) | PSR-4 mapping is case-sensitive — namespace `Devsroom_DDMM` must map to directory matching the exact casing, or normalize in the autoloader function |
| Theme CSS Conflicts | Widget styles overridden by aggressive theme selectors (e.g., `.main-navigation ul li a`) | Scope all widget CSS under `.elementor-widget-ddmm-drilldown-menu` and use sufficient specificity |

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Animating layout properties (`left`, `top`, `width`) instead of transforms | Stuttering on mobile, dropped frames during panel transitions | Use only `transform: translateX()` and `opacity` for all animations | Noticeable on any mobile device, severe on mid-range Android |
| Not using `will-change` for animated panels | Browser re-composites layers during animation, causing jank | Add `will-change: transform` to panel containers that will animate | Breaks with 3+ simultaneous transitions (e.g., parent panel sliding out while child slides in) |
| Loading plugin CSS/JS on all pages | Every page on the site loads menu assets unnecessarily | Use `get_script_depends()`/`get_style_depends()` for conditional loading | Noticeable on any site with 10+ pages; severe on large sites |
| No CSS containment on drawer/panels | Browser recalculates layout for entire page when drawer opens | Use `contain: layout style` on the drawer container | Breaks on pages with many DOM elements (long posts, product listings) |
| Multiple widget instances without ID namespacing | All instances share same panel IDs, events fire on wrong widget | Generate unique IDs per widget instance using `uniqid()` or Elementor's widget ID | Breaks as soon as a second instance is placed on the page |
| Unbounded `querySelectorAll` during navigation | Scanning entire DOM for panel elements on every tap | Scope queries to the widget container element (`$scope` in Elementor handler) | Breaks on pages with multiple widgets or large DOM trees |

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| Not escaping widget settings in `render()` | Stored XSS — admin user sets malicious title/URL, executes scripts for visitors | Always use `esc_html()`, `esc_url()`, `esc_attr()` when outputting `$settings` values in `render()` |
| Trusting custom menu builder URLs without sanitization | Stored XSS or open redirect via crafted URL values | Use `esc_url()` on output and `sanitize_url()` on input; never `echo $url` raw |
| Not checking `current_user_can()` before AJAX handlers (if any) | Unauthorized actions by non-admin users | Add capability checks to all admin-facing handlers |
| Using `$_POST`/`$_GET` directly instead of Elementor's settings API | Bypassing Elementor's built-in sanitization | Always read widget settings via `$this->get_settings_for_display()` |
| Missing nonce verification on any custom form submissions | CSRF attacks | Use `wp_nonce_field()`/`wp_verify_nonce()` for any non-Elementor form handling |

## UX Pitfalls

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| No close button or only close-on-overlay-click | Users with motor impairments cannot dismiss the menu | Always provide visible close (X) button in drawer header |
| Touch target too small on back button | Users frequently miss the back button, especially on small phones | Minimum 44x44px touch target (WCAG guideline); make the entire back row tappable, not just the arrow |
| No visual indication of which items have children | Users tap expecting a page load but get a panel transition — disorienting | Always show a right-arrow indicator (chevron) on parent items |
| Menu closes on every link click with no option to change | Users cannot open links in new tabs; menu closes before they can long-press | Make "close on link click" configurable (already in requirements) |
| No transition duration control | Menu feels either too snappy (confusing) or too slow (annoying) | Expose duration (100ms-2000ms) and easing as Elementor controls |
| Drawer covers entire viewport with no way to see underlying page | Users lose context of where they are on the site | Use semi-transparent overlay behind drawer, not full-screen coverage |
| Search box not focusing automatically when drawer opens | Users must tap the search box manually — adds friction | Auto-focus search input on drawer open (but respect `prefers-reduced-motion` and screen readers) |

## "Looks Done But Isn't" Checklist

- [ ] **Panel Navigation:** Often works for 2-3 levels but breaks at level 4+ — test with 5+ nesting levels
- [ ] **Multiple Instances:** Widget works with one instance but breaks when two are placed on the same page — test with 2+ instances
- [ ] **Editor Preview:** Widget renders on live frontend but not in Elementor editor preview — test in both contexts
- [ ] **No Menu Selected:** Widget crashes when no WordPress menu is selected in the dropdown — handle empty state gracefully
- [ ] **Empty Menu:** Widget crashes or renders broken HTML when the selected menu has zero items — show fallback UI
- [ ] **WooCommerce Deactivated:** Plugin crashes or throws warnings when WooCommerce is not installed — guard all WC calls
- [ ] **Accessibility:** Visual testing looks fine but screen readers cannot navigate — test with at least one screen reader (VoiceOver on macOS is most accessible for dev testing)
- [ ] **RTL Languages:** Panel slide direction is reversed for RTL locales — use CSS logical properties or `dir` attribute checks
- [ ] **Customizer Preview:** Widget settings update in Elementor but not in WordPress Customizer — use `get_settings_for_display()`
- [ ] **Asset Loading on Archive Pages:** Widget placed in a header template loads assets on archive/single pages — verify conditional loading works for template-placed widgets
- [ ] **Plugin Deactivation Reactivation:** Settings lost or corrupted after deactivating and reactivating the plugin — verify settings persist
- [ ] **Elementor Not Active:** Plugin throws fatal errors or admin notice loops when Elementor is deactivated — show clean admin notice only

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Wrong JS init timing (no `elementor/frontend/init`) | MEDIUM | Refactor all JS init to use Elementor handler pattern; add double-init guard; test editor + frontend |
| PHP reference corruption in menu tree | LOW | Replace `foreach &$ref` with 3-pass ID-based approach; test with large menus |
| Layout-triggering CSS animations | MEDIUM | Rewrite all panel animations from `left`/`right` to `transform: translateX()`; test on real mobile devices |
| Global asset loading | LOW | Add `get_script_depends()`/`get_style_depends()` to widget class; move `wp_enqueue` to `wp_register`; verify per-page loading |
| Wrong ARIA roles (`role="menu"`) | LOW | Remove `role="menu"`/`role="menuitem"`, add correct `aria-expanded`/`aria-hidden` attributes |
| Double-nested ZIP | LOW | Fix build script to zip from inside plugin directory; verify with `unzip -l` |
| WooCommerce crash on deactivation | LOW | Wrap all WC calls in `class_exists('WooCommerce')` guards |
| Missing output escaping | HIGH | Audit every `echo` in `render()` method; add `esc_html()`/`esc_url()`/`esc_attr()` to all dynamic output |

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Elementor JS init timing | Phase 1: Widget Skeleton | Menu works in both editor preview and live frontend; no console errors |
| PHP reference corruption | Phase 1: WP Menu Source | Test with 15+ item menu at 4+ depth; no duplicate or missing items |
| CSS GPU compositing | Phase 2: Panel Transitions | Chrome DevTools shows no layout thrashing; 60fps on real mobile device |
| ARIA role misuse | Phase 3: Accessibility | Screen reader (VoiceOver) can navigate all levels; axe/WAVE audit passes |
| Global asset loading | Phase 1: Widget Skeleton | Network tab shows plugin assets only on pages containing the widget |
| WooCommerce endpoint detection | Phase 2: WooCommerce Compat | Current page highlighting works on cart/checkout/my-account endpoints |
| Editor vs. frontend CSS | Phase 4: Style Tab | Visual comparison: editor preview matches live frontend pixel-for-pixel |
| Double-nested ZIP | Phase 5: Distribution | Upload ZIP to fresh WP install; plugin appears and activates correctly |
| Missing output escaping | Phase 1: Widget Skeleton | Audit `render()` method — no raw `echo $variable` without escaping |
| Multiple instance collision | Phase 1: Widget Skeleton | Place 2+ widget instances on same page; both work independently |
| PSR-4 case mismatch | Phase 1: Widget Skeleton | Autoloader loads all classes; no "class not found" errors on case-sensitive systems (Linux) |

## Sources

- [Elementor Widget Dependencies (Official Docs)](https://developers.elementor.com/docs/widgets/widget-dependencies/) — conditional asset loading via `get_script_depends()`/`get_style_depends()`
- [Add JavaScript to Widgets (Elementor Official)](https://developers.elementor.com/add-javascript-to-elementor-widgets/) — JS handler registration pattern
- [Elementor JS Hooks (Official Docs)](https://developers.elementor.com/docs/hooks/js/) — `elementor/frontend/init` event lifecycle
- [Elementor Frontend Available Controls (Official Docs)](https://developers.elementor.com/docs/editor-controls/frontend-available/) — editor vs. frontend control availability
- [Ultimate Guide for JS in Elementor Widgets (Igor Benic)](https://www.ibenic.com/ultimate-guide-for-javascript-in-elementor-widgets/) — comprehensive JS handler patterns
- [Vanilla JS for Custom Widgets — GitHub Issue #11435](https://github.com/elementor/elementor/issues/11435) — community discussion on init patterns
- [WordPress Walker Class (Official Docs)](https://developer.wordpress.org/reference/classes/walker/) — tree traversal mechanics
- [Understanding the Walker Class (Pressidium)](https://pressidium.com/blog/understanding-the-walker-class-in-wordpress/) — Walker customization guide
- [W3C Mobile Menu Accessibility](https://w3c.github.io/wai-mobile-intro/mobile/mobile-menus/) — `aria-expanded` for mobile menus
- [ARIA menu role (MDN)](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Roles/menu_role) — why NOT to use `role="menu"` for site navigation
- [Accessible Navigation Pitfalls (Level Access)](https://www.levelaccess.com/blog/accessible-navigation-menus-pitfalls-and-best-practices/) — navigation ARIA patterns
- [aria-expanded Good and Bad (BarrierBreak)](https://www.barrierbreak.com/aria-expanded-the-good-and-the-bad/) — common aria-expanded mistakes
- [Elementor 3.27 Developers Update](https://developers.elementor.com/elementor-3-27-developers-update/) — mandatory `get_style_depends()` for improved CSS loading
- [Codeable: Elementor Widget Development Best Practices](https://www.codeable.io/blog/elementor-widget-development/) — security and coding pitfalls
- [WordPress SVN Plugin Distribution (Official)](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/) — SVN directory structure
- [PSR-4 Case Sensitivity (Stack Overflow)](https://stackoverflow.com/questions/52633505/php-psr-4-autoload-in-wordpress-plugin-namespace) — case-sensitive namespace mapping
- [Simple PSR-4 Autoloader Without Composer (doeken.org)](https://doeken.org/tip/simple-psr4-autoloader) — `spl_autoload_register` pattern for WP plugins
- [WooCommerce Menu Cart Widget (Elementor Pro Docs)](https://elementor.com/help/menu-cart-widget-pro/) — WC integration patterns
- PROJECT.md known issues (v1.3.0) — PHP reference bug, positional panel navigation, JS crash on hooks, hamburger click cascade, mixed PHP syntax, shell brace expansion in ZIP

---
*Pitfalls research for: WordPress Elementor mobile drill-down menu plugin*
*Researched: 2026-06-12*
