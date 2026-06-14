# Phase 7: Accessibility & Compatibility Polish - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-14
**Phase:** 07-accessibility-compatibility-polish
**Areas discussed:** Keyboard model, Focus & SR feedback, WooCommerce, i18n & motion

**Gray areas offered (multiSelect):** Keyboard model · Focus & SR feedback · WooCommerce · i18n & motion
**User selected:** All four.

---

## Keyboard model

### Q1 — How should Tab and Arrow keys divide navigation inside the open drawer?

| Option | Description | Selected |
|--------|-------------|----------|
| Tab wraps + ↑/↓ roam items | Tab cycles every focusable control & wraps at the drawer boundary (the trap); ↑/↓ move between menu items via roving tabindex. Two complementary modes. | ✓ |
| Full treeview (→ drills, ← back) | Tab jumps between control groups; ↑/↓ siblings, → drills into parent, ← goes back (WAI-ARIA treeview). | |
| Tab wraps + ↑/↓ only (no ←/→) | Tab cycles controls & wraps; ↑/↓ roam items. Drilling via Enter on chevron only. | |

**User's choice:** Tab wraps + ↑/↓ roam items → **D-01**.
**Notes:** The requirements mandate both a Tab trap (A11Y-05) AND Arrow nav (A11Y-06); this satisfies both with two complementary modes. No horizontal-arrow drilling.

### Q2 — At a sub-panel, what should Escape do first?

| Option | Description | Selected |
|--------|-------------|----------|
| Back one level, close at root | Esc steps back one panel; Esc at root closes the drawer. Matches the ← Back mental model. | ✓ |
| Always close immediately | Esc dismisses from any depth in one press. Faster escape. | |
| Esc = back, Shift+Esc = close | Two distinct keys. | |

**User's choice:** Back one level, close at root → **D-02**.

### Q3 — When the drawer opens, where should keyboard focus land first?

| Option | Description | Selected |
|--------|-------------|----------|
| Auto-opened current item, else first | Focus the auto-open-current-path match; otherwise the first item. Orients the user. | ✓ |
| Close (✕) button | Predictable, always present, top-right. | |
| First menu item always | Simplest rule. | |
| Drawer / nav landmark | Focus the `<nav>` itself (literal SC#3 wording). | |

**User's choice:** Auto-opened current item, else first → **D-03**.

### Q4 — A parent item is split (label `<a>` navigates + `›` chevron drills). How should Enter/Space behave on a parent row?

| Option | Description | Selected |
|--------|-------------|----------|
| Label + chevron are separate stops | Tab/arrows land on the label `<a>` (Enter = navigate) OR the chevron (Enter = drill). User explicitly picks. | ✓ |
| Enter always drills; link is secondary | One focus stop per row; Enter drills. Navigating to the parent URL needs an extra step. | |
| Enter navigates; → drills | Treeview convention: Enter follows the link, → drills. | |

**User's choice:** Label + chevron are separate stops → **D-04**.

---

## Focus & SR feedback

### Q1 — When the user drills between panels, how should screen readers be notified?

| Option | Description | Selected |
|--------|-------------|----------|
| Announce panel context | Polite aria-live announces the new panel's context (back-row parent name / nav label). | ✓ |
| Silent; rely on focus move | No live region; rely on aria-hidden/aria-labelledby + focus move. | |
| Announce forward only | Announce on drill-in, not on back. | |

**User's choice:** Announce panel context → **D-08**.

### Q2 — When search filters the menu, how should screen-reader users get feedback?

| Option | Description | Selected |
|--------|-------------|----------|
| Announce result count | Polite aria-live announces the count after each filter, incl. "No results". | ✓ |
| Announce empty state only | Stay silent when there are matches. | |
| No search live region | Visible list + role=status is enough. | |

**User's choice:** Announce result count → **D-09**.

### Q3 — How should keyboard focus be visually indicated?

| Option | Description | Selected |
|--------|-------------|----------|
| Default :focus-visible now | Ship a :focus-visible outline (themeable via --ddmm-*). | ✓ |
| Browser default only | Rely on each browser's default ring. | |
| Defer to a future Style section | Wire behavior now, ship no focus CSS. | |

**User's choice:** Default :focus-visible now → **D-10**.

### Q4 — After drilling INTO a sub-panel, where should focus land?

| Option | Description | Selected |
|--------|-------------|----------|
| First item of new panel | Forward momentum — keep descending into content. | ✓ |
| Back button first | Lets the user immediately back out. | |
| New panel container | SR announces it; user arrows in. | |

**User's choice:** First item of new panel → **D-05**.

---

## WooCommerce

### Q1 — How involved should the plugin be with WooCommerce?

| Option | Description | Selected |
|--------|-------------|----------|
| 100% WC-agnostic | Never detect/require WC. Render WP-assigned URLs. Works whether WC active or not. | ✓ |
| Soft-detect + dynamic URLs | class_exists check; resolve Cart/Account/Checkout/Shop via wc_get_* when active. | |
| Full WC integration | Detect + dynamic URLs + WC fragments for live cart state. | |

**User's choice:** 100% WC-agnostic → **D-12**.
**Notes:** Keeps the plugin dependency-free and correct in both WC states by construction.

### Q2 — When WC is inactive and a menu item points to a missing shop page, what should the plugin do?

| Option | Description | Selected |
|--------|-------------|----------|
| Render as-is | Render exactly as WP stored it. Broken link = content issue, not plugin issue. | ✓ |
| Hide unresolvable WC items | Detect 404 and hide. Plugin makes content decisions. | |
| Render disabled | Add aria-disabled / dimming. | |

**User's choice:** Render as-is → **D-13**.

### Q3 — Is a cart-item-count badge (or any live cart content) in Phase 7 scope?

| Option | Description | Selected |
|--------|-------------|----------|
| Out of scope — defer | Dynamic WC content = new capability. COMP-03 is URL-only. | ✓ |
| Static cart-count badge | Updates on page load, no fragments. | |

**User's choice:** Out of scope — defer → **D-14**.

---

## i18n & motion

### Q1 — How should the .pot translation template be generated and shipped?

| Option | Description | Selected |
|--------|-------------|----------|
| WP-CLI generated + committed | `wp i18n make-pot` → languages/*.pot committed; ships with plugin. | ✓ |
| Document command, don't commit | Regenerate at release only. | |
| Hand-maintained .pot | No WP-CLI dependency; drifts easily. | |

**User's choice:** WP-CLI generated + committed → **D-15**.

### Q2 — How should user-facing strings that live in the JS be made translatable?

| Option | Description | Selected |
|--------|-------------|----------|
| wp_set_script_translations | Modern WP-native JS translation path (loads .json via make-json). | ✓ |
| Data-attr strings (no JS i18n file) | Pass every JS string from PHP via data-* (esc_html__). | |
| wp_localize_script strings | Older pattern; Phase 4 D-15 reserved it for future i18n. | |

**User's choice:** wp_set_script_translations → **D-16**.

### Q3 — How should the drawer animations treat users with prefers-reduced-motion?

| Option | Description | Selected |
|--------|-------------|----------|
| Honor the media query | Neutralize transitions under prefers-reduced-motion: reduce. Unconditional. | ✓ |
| Per-instance toggle (default on) | Add a Content-Tab toggle. More control. | |
| Ignore | Configured animation always runs. | |

**User's choice:** Honor the media query → **D-18**.

### Q4 — How much RTL handling should Phase 7 include?

| Option | Description | Selected |
|--------|-------------|----------|
| Baseline correctness, full RTL = v2 | Logical properties; don't break under dir=rtl. Full RTL (RTL-01) stays v2. | ✓ |
| Ignore RTL until v2 | LTR-only; accept breakage under RTL themes. | |
| Full RTL now | Slide direction + mirroring. Pulls RTL-01 forward (scope expansion). | |

**User's choice:** Baseline correctness, full RTL = v2 → **D-19**.

---

## Claude's Discretion

- Exact roving-tabindex mechanics (D-11); `:focus-visible` var/width (D-10); aria-live region markup + id wiring (D-08/D-09); reduced-motion neutralization threshold (D-18); `load_plugin_textdomain` hook (D-17); keyboard-handler method decomposition within the `DrillDownMenu` IIFE.
- Verification strategy for manual-only behaviors — likely a `07-HUMAN-UAT.md` (grep checks verify wiring; browser verifies live behavior).

## Deferred Ideas

- Cart-count badge / live WC cart content (own future phase or v2).
- Full RTL layout (RTL-01) — v2.
- Swipe gestures (GEST-01) — v2.
- `content_template()` live editor preview (PRES-01) — v2.
- Multiple widget instances beyond per-container scoping (MULTI-01) — v2.
- Per-instance "Focus" Style Tab section — future polish phase.
