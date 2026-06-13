# Phase 5: Frontend Drill-Down JavaScript - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-14
**Phase:** 05-frontend-drill-down-javascript
**Areas discussed:** Animation behavior, Search UX, Auto-open current path, Close-behavior toggles

---

## Area Selection

| Option | Description | Selected |
|--------|-------------|----------|
| Animation behavior | Per-type visuals, default type, drawer-open scope | ✓ |
| Search UX | Filtering model, box placement, result content, default state | ✓ |
| Auto-open current path | Trigger timing, highlight scope, default | ✓ |
| Close-behavior toggles | EXTR-04/05 defaults, parent-label + new-tab edge cases | ✓ |

All four offered areas were selected for discussion.

---

## Animation Behavior

| Question | Option | Description | Selected |
|----------|--------|-------------|----------|
| Drawer-open scope | Drawer always slides (Recommended) | Type governs panel drills only; drawer entrance is always off-canvas slide | ✓ |
| Drawer-open scope | Type applies to drawer too | e.g. Fade = drawer fades in | |
| Default type | Slide (Recommended) | Out-left / in-right; matches Packiro + existing CSS | ✓ |
| Default type | Fade | Cross-fade in place | |
| Default type | Scale | Child zoom-in from 92% + fade | |
| Default type | Slide + Fade | Slide + fade combined | |
| Per-type visuals | Approve proposed defs (Recommended) | Slide/Fade/Scale/Slide+Fade as described; overlap (cross-fade) style | ✓ |
| Per-type visuals | Simpler mapping | Fade/Scale never slide horizontally | |
| Per-type visuals | Let me describe | User describes own behavior | |

**User's choices:** Drawer always slides · Default Slide · Approved proposed definitions
**Notes:** Keeps the off-canvas "slide-in from left" identity (Packiro) predictable regardless of chosen type.

---

## Search UX

| Question | Option | Description | Selected |
|----------|--------|-------------|----------|
| Filter model | Flat results list (Recommended) | Drill-view replaced by scrollable matching links while typing; clear → back to drill view | ✓ |
| Filter model | In-place hide in panels | Keep panels, hide non-matches | |
| Filter model | Let me describe | User describes own behavior | |
| Box placement | Sticky bar below header (Recommended) | Always visible at any drill depth | ✓ |
| Box placement | Inside the header | Alongside brand/close | |
| Box placement | Root panel only | Disappears once drilling in | |
| Result content | All items + breadcrumb (Recommended) | Parents + leaves match by title; leaf navigates, parent drills in | ✓ |
| Result content | Leaf links only | Only terminal links; click navigates | |
| Result content | All items, no path | Flat titles, no breadcrumb | |
| Default state | Off by default / opt-in (Recommended) | Per EXTR-01 "optional"; user enables search | ✓ |
| Default state | On by default | Search renders unless disabled | |

**User's choices:** Flat results list · Sticky bar below header · All items + breadcrumb · Off by default
**Notes:** Flat list chosen because panels are mutually exclusive views, making in-place hiding across panels confusing.

---

## Auto-Open Current Page Path

| Question | Option | Description | Selected |
|----------|--------|-------------|----------|
| Trigger | Auto-drill on manual open (Recommended) | On open, drill to current item's panel; never auto-open on page load | ✓ |
| Trigger | Highlight only, no auto-drill | Open at root; just mark current item wherever it sits | |
| Trigger | Auto-open drawer on load | Drawer pops open on page load (intrusive) | |
| Highlight | Item + ancestor trail (Recommended) | Mirror WP current-menu-item / current-menu-ancestor | ✓ |
| Highlight | Current item only | Marker only on matching item | |
| Default | On by default (Recommended) | Orients to current page out of the box | ✓ |
| Default | Off by default | Users opt in | |

**User's choices:** Auto-drill on manual open · Item + ancestor trail · On by default
**Notes:** Never auto-opens the drawer on page load (avoids intrusive "menu pops open"). URL-based match works for both WP and custom sources.

---

## Close-Behavior Toggles

| Question | Option | Description | Selected |
|----------|--------|-------------|----------|
| EXTR-04 link close | On by default (Recommended) | Closes on any `<a>` click (leaf OR split parent label); chevron drill does NOT close | ✓ |
| EXTR-04 link close | Off by default | Drawer persists after link click | |
| EXTR-05 overlay close | On by default (Recommended) | Tapping overlay dismisses drawer | ✓ |
| EXTR-05 overlay close | Off by default | Only ✕ (and Phase 7 Esc) closes | |
| New-tab links | Leave open for new-tab (Recommended) | target=_blank doesn't navigate the page, so drawer stays | ✓ |
| New-tab links | Always close | Any link click dismisses uniformly | |

**User's choices:** Link close ON · Overlay close ON · Leave open for new-tab links
**Notes:** Close-after-link-click includes the split parent's own label `<a>` (a real navigation per Phase 4 D-01) but excludes the chevron drill and new-tab links.

---

## Claude's Discretion

Areas deferred to Claude's judgment (recorded in CONTEXT.md):
- Exact BEM panel-state class names and animation-type container class names
- Exact `--ddmm-*` easing custom property name
- Panel overlap/cross-fade timing, scroll-reset-on-drill, search debounce + min-char threshold
- Search box markup, `data-ddmm-*` hook names, results-container structure
- "No results" copy and search placeholder default text
- Current-item / ancestor marker class names (reuse WP classes where they passthrough)
- URL-match normalization (trailing slash, query, hash)
- `DrillDownMenu` internal method decomposition
- Content Tab section grouping for the new controls

## Deferred Ideas

- Keyboard nav / focus management / Tab trap (A11Y-04..08) — Phase 7
- Full Style Tab incl. search box + Active/current states (STYL-01..06) — Phase 6
- WooCommerce URL verification (COMP-03) — Phase 7
- `.pot` translation packaging (COMP-04) — Phase 7
- `content_template()` live editor preview (PRES-01) — v2
- Multi-instance beyond per-container scoping (MULTI-01) — v2
- Swipe gestures (GEST-01) — v2
- RTL (RTL-01) — v2
