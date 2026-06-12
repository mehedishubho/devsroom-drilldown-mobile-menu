<?php
/**
 * WordPress nav menu tree builder.
 *
 * Converts the flat array from wp_get_nav_menu_items() into a nested
 * parent-child tree using a 3-pass ID-based algorithm.
 *
 * @package Devsroom_DDMM\MenuBuilder
 */

namespace Devsroom_DDMM\MenuBuilder;

/**
 * Converts flat wp_get_nav_menu_items() output into a nested tree.
 *
 * Pure data — no HTML, no panel IDs, no Elementor dependency.
 * Escaping is the renderer's responsibility (Phase 4).
 */
class WpNavTree {

	/**
	 * Build a nested menu tree from a WordPress nav menu.
	 *
	 * Uses a 3-pass ID-based algorithm:
	 *   Pass 1 — Index all items by their post ID.
	 *   Pass 2 — Attach children to parents; collect root IDs.
	 *   Pass 3 — Extract root nodes as the final tree.
	 *
	 * @param int|string|\WP_Term $menu Menu ID, slug, name, or term object.
	 * @return array<int, array> Root-level tree nodes. Empty array if menu is empty/invalid.
	 */
	public static function build( $menu ): array {
		$items = wp_get_nav_menu_items( $menu );

		if ( ! $items || empty( $items ) ) {
			return [];
		}

		// Pass 1: Index all items by their post ID.
		$indexed = [];
		foreach ( $items as $item ) {
			$indexed[ (int) $item->ID ] = [
				'id'           => (int) $item->ID,
				'title'        => $item->title,
				'url'          => $item->url,
				'target'       => $item->target ?? '',
				'classes'      => is_array( $item->classes ) ? $item->classes : [],
				'has_children' => false,
				'children'     => [],
			];
		}

		// Pass 2: Attach children to parents. Collect root IDs.
		$root_ids = [];
		foreach ( $items as $item ) {
			$parent_id = (int) $item->menu_item_parent;

			if ( $parent_id > 0 && isset( $indexed[ $parent_id ] ) ) {
				$indexed[ $parent_id ]['children'][]   = & $indexed[ (int) $item->ID ];
				$indexed[ $parent_id ]['has_children'] = true;
			} else {
				$root_ids[] = (int) $item->ID;
			}
		}

		// Pass 3: Extract root nodes as the tree.
		$tree = [];
		foreach ( $root_ids as $id ) {
			$tree[] = $indexed[ $id ];
		}

		return $tree;
	}
}
