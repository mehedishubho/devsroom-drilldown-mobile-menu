# Phase 6: Style Tab Controls - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-14
**Phase:** 06-style-tab-controls
**Areas discussed:** Default + typography, Hover/Active depth, Responsive controls, Editor fidelity

---

## Default + typography

| Option | Description | Selected |
|--------|-------------|----------|
| Polish the baseline (Rec.) | Refine existing defaults for a designed, modern un-styled feel | ✓ |
| Keep current defaults | Leave the Phase 4 CSS defaults as-is; controls inherit them | |
| Match Packiro reference | Tune defaults specifically toward the Packiro.com look | |

**User's choice:** Polish the baseline

| Option | Description | Selected |
|--------|-------------|----------|
| Trigger text | Typography group control for the trigger label (text_only / icon_text) | ✓ |
| Search input + results | Typography group control for the search input + result items | ✓ |

**User's choice:** Trigger text AND Search input + results (both, on top of the required menu/header/back-row typography)

---

## Hover/Active depth

| Option | Description | Selected |
|--------|-------------|----------|
| Items + primary controls (Rec.) | Menu items (required) + trigger button + back button + close ✕ | ✓ |
| Menu items only | Only what STYL-05 mandates | |
| Every interactive element | Also chevron (›) + search input focus | |

**User's choice:** Items + primary controls

| Option | Description | Selected |
|--------|-------------|----------|
| Item + ancestor trail (Rec.) | Style ddmm-current-item AND ddmm-current-ancestor | ✓ |
| Current item only | Style only the exact current-page leaf | |
| Trail + opened-parent | Also highlight a parent while its child panel is open | |

**User's choice:** Item + ancestor trail

---

## Responsive controls

| Option | Description | Selected |
|--------|-------------|----------|
| Per-breakpoint for sizing (Rec.) | Drawer width, item padding, typography sizes get mobile/tablet/desktop values | ✓ |
| Mobile-first, single value | One value per control; existing 85vw cap as safety net | |
| Full responsive everywhere | Every sizing control (incl. radii, gaps, heights) per-breakpoint | |

**User's choice:** Per-breakpoint for sizing

| Option | Description | Selected |
|--------|-------------|----------|
| Per-side, linked default (Rec.) | Elementor Dimensions control; unlink for asymmetric padding | ✓ |
| Uniform single value | One padding value per element | |

**User's choice:** Per-side, linked default

---

## Editor fidelity

| Option | Description | Selected |
|--------|-------------|----------|
| Full representative preview (Rec.) | Rework `.ddmm-editor-preview` to show trigger + header + items + sample back row + chevron, using real BEM classes (no hardcoded #fff/#eee) | ✓ |
| Core elements only | Preview covers trigger + header + items; back row / search omitted | |
| Keep as-is, accept gap | Leave simplified static preview; SC#5 only partially met | |

**User's choice:** Full representative preview

| Option | Description | Selected |
|--------|-------------|----------|
| Strict parity everywhere (Rec.) | Every Style Tab control reflected in the editor preview; all 6 sections visible | ✓ |
| Parity for previewed sections only | Non-previewed sections (drawer width/shadow, overlay, search) marked "see published page" | |
| Best-effort, no hard gate | Reasonable effort; don't block the phase on perfect parity | |

**User's choice:** Strict parity everywhere

---

## Claude's Discretion

- Per-section control inventory (the STYL-01..06 lists are the floor, not the ceiling)
- Var-bridge vs direct `selectors` choice per control (hybrid expected)
- Exact new `--ddmm-*` custom-property names for properties not yet covered
- Whether to introduce a single global Accent/Active color or per-section active colors
- Editor-preview sample content and whether a search sample row always renders or is gated by the toggle
- Style Tab section ordering, labels, descriptions, separators
- Polished-baseline numeric defaults (border softness, drawer shadow, spacing)

## Deferred Ideas

- Opened-parent Active state (revisit if users want stronger "you are here" cues)
- `content_template()` true live editor preview (PRES-01) — v2
- RTL Style Tab variants (RTL-01) — v2
- Full preset / theme-color system — out of scope (single accent color permitted at Claude's discretion)
