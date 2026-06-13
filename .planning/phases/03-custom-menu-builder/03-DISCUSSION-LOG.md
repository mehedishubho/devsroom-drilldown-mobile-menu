# Phase 3: Custom Menu Builder - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-13
**Phase:** 03-custom-menu-builder
**Areas discussed:** Depth Field UX, Tree Output Parity, Invalid Depth Handling

---

## Depth Field UX

| Option | Description | Selected |
|--------|-------------|----------|
| NUMBER input | Elementor NUMBER control with min=0, step=1, no max cap. Flexible, standard Elementor pattern. | ✓ |
| SELECT dropdown | SELECT with options 0-5 (Root, Child, Grandchild, etc.). Controlled but capped. | |

**User's choice:** NUMBER input (Recommended)
**Notes:** User selected all gray areas plus "Skip, Claude decides" — indicating they wanted to review the key decisions but are comfortable with Claude handling details.

---

## Tree Output Parity

| Option | Description | Selected |
|--------|-------------|----------|
| Same format | CustomTree outputs the same 7-field nodes as WpNavTree. Phase 4 has one rendering path regardless of menu source. | ✓ |
| Different format | CustomTree can differ. Phase 4 needs separate handling per source. | |

**User's choice:** Same format (Recommended)
**Notes:** Clear alignment with Phase 4's single-rendering-path goal.

---

## Invalid Depth Handling

| Option | Description | Selected |
|--------|-------------|----------|
| Auto-clamp | Stack-based algorithm inherently clamps depth jumps. Clean output, no extra code. | ✓ |
| Auto-clamp + editor warning | Add validation to detect depth jumps and show a warning in the editor. | |

**User's choice:** Auto-clamp (Recommended)
**Notes:** Simpler implementation, algorithm handles edge cases naturally.

---

## Claude's Discretion

- Max depth: No cap (NUMBER input has no max)
- Default empty state: Follows Phase 2's D-05 pattern
- Custom tree builder class name: CustomTree at src/MenuBuilder/CustomTree.php
- Repeater field order: Label → URL → Depth → Icon → Open in New Tab
- Repeater default items: None — starts empty
- Title formatting with indent dashes
- `classes` field: Empty array in custom nodes
- `id` field: Sequential integers from 1
- Exact label text for repeater fields
- Stack-based algorithm implementation details

## Deferred Ideas

None — discussion stayed within phase scope.
