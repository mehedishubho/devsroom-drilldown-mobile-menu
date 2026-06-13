<?php
/**
 * Custom menu tree builder (flat repeater with depth field).
 *
 * Converts flat repeater items with depth values into a nested
 * parent-child tree using a stack-based algorithm.
 *
 * @package Devsroom_DDMM\MenuBuilder
 */

namespace Devsroom_DDMM\MenuBuilder;

/**
 * Converts flat repeater items with depth field into nested tree
 * using stack-based algorithm.
 *
 * Pure data — no HTML, no Elementor dependency.
 * Escaping is the renderer's responsibility (Phase 4).
 */
class CustomTree {

	/**
	 * Build a nested menu tree from flat repeater items with depth field.
	 *
	 * Uses a stack-based algorithm that tracks the current ancestry path.
	 * Depth jumps are auto-clamped (e.g., depth 0 -> 3 becomes depth 0 -> 1).
	 * Items with empty labels are skipped to prevent phantom menu items.
	 *
	 * @param array $items Raw repeater data from Elementor settings.
	 * @return array<int, array> Root-level tree nodes. Empty array if input is empty.
	 */
	public static function build( array $items ): array {
		if ( empty( $items ) ) {
			return [];
		}

		$tree  = [];
		$stack = [];
		$id    = 0;

		foreach ( $items as $item ) {
			$id++;
			$depth = (int) ( $item['depth'] ?? 0 );
			$title = $item['label'] ?? '';

			// Skip items with empty labels (Pitfall 5: prevents phantom menu items).
			if ( '' === $title ) {
				continue;
			}

			// Extract URL from Elementor URL control format (Pitfall 1: URL control returns array).
			$url = '';
			if ( isset( $item['url'] ) ) {
				if ( is_array( $item['url'] ) && ! empty( $item['url']['url'] ) ) {
					$url = $item['url']['url'];
				} elseif ( is_string( $item['url'] ) && '' !== $item['url'] ) {
					$url = $item['url'];
				}
			}

			// Target: SWITCHER returns 'yes' / '' (Pitfall 2: not boolean).
			$target = ( ! empty( $item['new_tab'] ) && 'yes' === $item['new_tab'] ) ? '_blank' : '';

			// Icon: Elementor ICONS control data (CMEN-05). Passed through to Phase 4 renderer.
			$icon = $item['icon'] ?? [];

			// Build the node array — 8-field contract matching WpNavTree (D-02, CMEN-05).
			$node = [
				'id'           => $id,
				'title'        => $title,
				'url'          => $url,
				'target'       => $target,
				'classes'      => [],
				'icon'         => $icon,
				'has_children' => false,
				'children'     => [],
			];

			// Auto-clamp stack to current depth (D-03).
			while ( count( $stack ) > $depth ) {
				array_pop( $stack );
			}

			if ( 0 === $depth || empty( $stack ) ) {
				// Root-level item.
				$tree[] = $node;
				// Reset stack to reference the new root node.
				$stack = [ &$tree[ count( $tree ) - 1 ] ];
			} else {
				// Child item — attach to parent at top of stack.
				$parent                       = &$stack[ count( $stack ) - 1 ];
				$parent['children'][]         = $node;
				$parent['has_children']       = true;
				$stack[]                      = &$parent['children'][ count( $parent['children'] ) - 1 ];
			}

			// Break reference to $node to avoid next-iteration interference (Pitfall 4).
			unset( $node );
		}

		return $tree;
	}
}
