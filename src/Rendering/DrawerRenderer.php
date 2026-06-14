<?php
/**
 * Stateless recursive tree-to-HTML drawer renderer.
 *
 * Converts the unified 8-field node tree (from WpNavTree / CustomTree) into
 * the complete off-canvas drawer HTML: overlay, drawer wrapper, header (brand +
 * close), nav with ARIA label, root panel, and recursive child panels.
 *
 * Emits the DOM contract that Phase 5's JavaScript consumes:
 *   - data-ddmm-* hook attributes (overlay, drawer, close)
 *   - Short navigation attributes (data-target, data-panel-id, data-back-target)
 *   - ARIA markup (aria-label, aria-hidden, aria-labelledby, aria-expanded, aria-controls)
 *
 * @package Devsroom_DDMM\Rendering
 */

namespace Devsroom_DDMM\Rendering;

/**
 * Stateless recursive renderer: tree -> drawer HTML.
 *
 * All methods are static. Public entry points:
 *   - render()              Frontend entry (overlay + drawer + panels).
 *   - render_editor_preview() Elementor edit-mode entry (static root <ul> only).
 *
 * @package Devsroom_DDMM\Rendering
 */
final class DrawerRenderer {

	/**
	 * Frontend entry point. Emits overlay + drawer + header + nav + root panel.
	 *
	 * Called from DrillDownMenu::render() after the empty-state guard. Echoes
	 * HTML directly (Elementor render() context). The drawer wrapper id
	 * matches the existing trigger aria-controls="ddmm-drawer-{widget_id}".
	 *
	 * @param array  $tree      Root-level nodes from WpNavTree/CustomTree (8-field contract).
	 * @param array  $settings  Elementor widget settings (brand_source, nav_label, etc.).
	 * @param string $widget_id Elementor widget unique ID.
	 * @return void Echos HTML directly.
	 */
	public static function render( array $tree, array $settings, string $widget_id ): void {
		// D-09: root panel gets a uniqid too (not a literal 'root').
		$root_panel_id = uniqid( 'ddmm-panel-', false );

		// D-21: configurable nav aria-label, default translatable "Mobile Menu".
		$nav_label = $settings['nav_label'] ?? __( 'Mobile Menu', 'devsroom-drilldown-mobile-menu' );

		// 1. Overlay (DRAW-02, D-25 data-ddmm-overlay hook).
		printf( '<div class="ddmm-overlay" data-ddmm-overlay aria-hidden="true"></div>' );

		// 2. Drawer opening div (A11Y-03, D-20, D-24, D-25, D-27).
		// The id MUST match the existing trigger aria-controls (Pitfall 7).
		printf(
			'<div class="ddmm-drawer" id="ddmm-drawer-%s" data-ddmm-drawer aria-hidden="true">',
			esc_attr( $widget_id )
		);

		// 3. Header (always present per D-07).
		self::render_header( $settings );

		// Phase 5 D-10: search box (opt-in per D-09). Sits between header and nav (D-07 sticky bar).
		if ( ! empty( $settings['search_enabled'] ) && 'yes' === $settings['search_enabled'] ) {
			self::render_search_box( $settings, $widget_id );
		}

		// 4. Nav opening (A11Y-01, D-21: aria-label from settings; never use role=menu per Pitfall 4).
		printf(
			'<nav class="ddmm-nav" aria-label="%s"><div class="ddmm-panels">',
			esc_attr( $nav_label )
		);

		// 5. Root panel ($is_root = true).
		self::render_panel( $tree, $settings, $root_panel_id, true );

		// 6. Closing tags: .ddmm-panels, nav, .ddmm-drawer.
		echo '</div></nav></div>';
	}

	/**
	 * Render the drawer header (brand + close button).
	 *
	 * Header always renders per D-07 so the close button is always reachable.
	 *
	 * @param array $settings Elementor widget settings.
	 * @return void Echos HTML directly.
	 */
	private static function render_header( array $settings ): void {
		echo '<div class="ddmm-header">';
		self::render_brand( $settings );

		// D-06: close button sits header-right, rendered via CSS glyph (&times;).
		printf(
			'<button type="button" class="ddmm-close" data-ddmm-close aria-label="%s">&times;</button>',
			esc_attr__( 'Close menu', 'devsroom-drilldown-mobile-menu' )
		);
		echo '</div>';
	}

	/**
	 * Render the search box (Phase 5 D-10).
	 *
	 * Emits a sticky bar with a search input and an empty results container.
	 * The input carries data-ddmm-search-input; the results ul carries
	 * data-ddmm-search-results. JS (ddmm-frontend.js) owns the live filtering.
	 *
	 * The container is EMPTY on render — JS populates results via DOM APIs
	 * (document.createElement + textContent, never innerHTML with user input —
	 * ASVS V5). The "No results" message is also emitted by JS so it is
	 * translatable via the same text domain.
	 *
	 * @param array  $settings  Widget settings (search_placeholder).
	 * @param string $widget_id Elementor widget ID (for unique input/results IDs).
	 * @return void Echos HTML directly.
	 */
	private static function render_search_box( array $settings, string $widget_id ): void {
		$placeholder = ! empty( $settings['search_placeholder'] )
			? $settings['search_placeholder']
			: __( 'Search menu…', 'devsroom-drilldown-mobile-menu' );

		printf(
			'<div class="ddmm-search" data-ddmm-search role="search">' .
				'<label class="screen-reader-text" for="ddmm-search-input-%1$s">%2$s</label>' .
				'<input type="search" id="ddmm-search-input-%1$s" class="ddmm-search__input" data-ddmm-search-input placeholder="%3$s" autocomplete="off" aria-controls="ddmm-search-results-%1$s">' .
				'<ul class="ddmm-search__results" data-ddmm-search-results id="ddmm-search-results-%1$s" aria-live="polite" aria-relevant="additions"></ul>' .
			'</div>',
			esc_attr( $widget_id ),
			esc_attr__( 'Search menu items', 'devsroom-drilldown-mobile-menu' ),
			esc_attr( $placeholder )
		);
	}

	/**
	 * Render the brand block (left side of header).
	 *
	 * Four brand sources per D-05 (Site Logo / Custom Image / Custom Text / None).
	 * Default Site Logo auto-detects via WP custom logo API with site-name
	 * text fallback. D-08: brand logo is a bare <img> (no inline width/height)
	 * for full CSS max-height control.
	 *
	 * @param array $settings Elementor widget settings.
	 * @return void Echos HTML directly.
	 */
	private static function render_brand( array $settings ): void {
		$source = $settings['brand_source'] ?? 'site_logo';

		echo '<div class="ddmm-brand">';

		switch ( $source ) {
			case 'site_logo':
				// D-05: auto-detect via WP custom logo; fallback to site name text.
				if ( has_custom_logo() ) {
					$logo_id = get_theme_mod( 'custom_logo' );
					if ( ! empty( $logo_id ) ) {
						// Bare <img> via attachment URL for full D-08 control (no inline w/h).
						$src = wp_get_attachment_image_url( (int) $logo_id, 'full' );
						if ( ! empty( $src ) ) {
							printf(
								'<img class="ddmm-brand__img" src="%s" alt="%s">',
								esc_url( $src ),
								esc_attr( get_bloginfo( 'name' ) )
							);
						} else {
							// Logo exists but no URL — fall back to site name text.
							printf(
								'<span class="ddmm-brand__text">%s</span>',
								esc_html( get_bloginfo( 'name' ) )
							);
						}
					} else {
						// No logo attachment — fall back to site name text.
						printf(
							'<span class="ddmm-brand__text">%s</span>',
							esc_html( get_bloginfo( 'name' ) )
						);
					}
				} else {
					// No custom logo set — fall back to site name text.
					printf(
						'<span class="ddmm-brand__text">%s</span>',
						esc_html( get_bloginfo( 'name' ) )
					);
				}
				break;

			case 'custom_image':
				// D-05: custom image from MEDIA control.
				$img = $settings['brand_image']['url'] ?? '';
				if ( ! empty( $img ) ) {
					printf(
						'<img class="ddmm-brand__img" src="%s" alt="%s">',
						esc_url( $img ),
						esc_attr( get_bloginfo( 'name' ) )
					);
				}
				break;

			case 'custom_text':
				// D-05: custom text, default site name.
				$text = $settings['brand_text'] ?? get_bloginfo( 'name' );
				printf(
					'<span class="ddmm-brand__text">%s</span>',
					esc_html( $text )
				);
				break;

			case 'none':
			default:
				// D-07: header still renders with close button; brand block is empty.
				break;
		}

		echo '</div>';
	}

	/**
	 * Render one panel (root or child) and its items.
	 *
	 * ID-threading rule (Pitfall 1, 2): $panel_id serves three roles:
	 *   1. data-panel-id on this panel's own <div>.
	 *   2. The ancestor passed to render_item() inside it — so child panel
	 *      back buttons reference THIS panel via data-back-target.
	 *   3. NOT the data-target on items' chevrons — that is the freshly-generated
	 *      $child_panel_id created in render_item().
	 *
	 * @param array  $items             Nodes belonging to this panel.
	 * @param array  $settings          Widget settings.
	 * @param string $panel_id          THIS panel's uniqid (data-panel-id).
	 * @param bool   $is_root           Root panel: no back row, gets --active.
	 * @param string $ancestor_panel_id Ancestor panel id (data-back-target target). Empty for root.
	 * @param string $parent_title      Parent item title for the back-row title. Empty for root.
	 * @param string $title_id          Back-row title span id (aria-labelledby target). Empty for root.
	 * @return void Echos HTML directly.
	 */
	private static function render_panel( array $items, array $settings, string $panel_id, bool $is_root, string $ancestor_panel_id = '', string $parent_title = '', string $title_id = '' ): void {
		// Panel wrapper classes (D-26: root gets --active).
		$classes = 'ddmm-panel';
		if ( $is_root ) {
			$classes .= ' ddmm-panel--active';
		}

		// D-24: child panels ship aria-hidden=true; root is active (no aria-hidden).
		$aria_hidden = $is_root ? '' : ' aria-hidden="true"';

		// D-22: child panel aria-labelledby points to back-row title span id.
		$labelledby = ( ! $is_root && ! empty( $title_id ) ) ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : '';

		printf(
			'<div class="%s" data-panel-id="%s"%s%s>',
			esc_attr( $classes ),
			esc_attr( $panel_id ),
			$labelledby,
			$aria_hidden
		);

		// Child panels render a back row at the top (D-10, D-11). Root has none.
		if ( ! $is_root && ! empty( $ancestor_panel_id ) ) {
			self::render_back_row( $parent_title, $ancestor_panel_id, $title_id, $settings );
		}

		echo '<ul class="ddmm-menu">';

		// Pass $panel_id as the ancestor for items in THIS panel (role 2 of threading rule).
		foreach ( $items as $node ) {
			self::render_item( $node, $settings, $panel_id );
		}

		echo '</ul></div>'; // Close ul and the panel div.
	}

	/**
	 * Render one menu item. Emits <li> + split <a>/<button> for parents,
	 * <li>+<a> for leaves. For parents: ALSO emits the child panel as a
	 * sibling IMMEDIATELY AFTER </li> (D-13, Pitfall 3).
	 *
	 * @param array  $node              Tree node (8-field contract).
	 * @param array  $settings          Widget settings.
	 * @param string $ancestor_panel_id The panel this item lives in (= back-target for child panel).
	 * @return void Echos HTML directly.
	 */
	private static function render_item( array $node, array $settings, string $ancestor_panel_id ): void {
		echo '<li class="ddmm-menu__item">';

		// Icon (D-29, D-30): only render when non-empty. WP items have icon=[].
		$icon_html = self::render_icon( $node['icon'] ?? [] );
		echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons_Manager output is pre-escaped.

		// Label link (D-01 split pattern, D-04 leaf passthrough).
		$url         = $node['url'] ?? '#';
		$target_attr = ! empty( $node['target'] ) ? ' target="_blank"' : '';

		// WP classes passthrough (D-04): filter empties, sanitize_html_class each, join.
		$classes_attr = '';
		if ( ! empty( $node['classes'] ) && is_array( $node['classes'] ) ) {
			$sanitized = array_filter( array_map( 'sanitize_html_class', $node['classes'] ) );
			if ( ! empty( $sanitized ) ) {
				$classes_attr = ' class="' . esc_attr( implode( ' ', $sanitized ) ) . '"';
			}
		}

		printf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$target_attr,
			$classes_attr,
			esc_html( $node['title'] )
		);

		// Parent item (has non-empty children) — D-01, D-02, D-13, D-23.
		if ( ! empty( $node['has_children'] ) && ! empty( $node['children'] ) ) {
			// Generate child panel ID ONCE (single source of truth — Pitfall 1, DRAW-06).
			$child_panel_id = uniqid( 'ddmm-panel-', false );

			// Chevron <button> (D-01, D-02, D-23, D-25). The > glyph comes from CSS ::after.
			printf(
				'<button type="button" class="ddmm-chevron" data-target="%s" aria-expanded="false" aria-controls="%s" aria-label="%s"></button>',
				esc_attr( $child_panel_id ),
				esc_attr( $child_panel_id ),
				esc_attr( sprintf( __( 'Show %s submenu', 'devsroom-drilldown-mobile-menu' ), $node['title'] ) )
			);

			// IMPORTANT: close li BEFORE emitting the child panel (D-13, Pitfall 3).
			echo '</li>';

			// Back-row title span id for this child panel (Pitfall 4 — each unique).
			$title_id = uniqid( 'ddmm-back-title-', false );

			// Emit child panel as a SIBLING after </li> (D-13).
			// $child_panel_id = new panel's data-panel-id.
			// $ancestor_panel_id (THIS panel's id) = child's back-target (role 2 of threading rule).
			// $node['title'] = parent name for back-row title (D-11).
			// $title_id threads aria-labelledby <-> span id (D-22, Pitfall 4).
			self::render_panel( $node['children'], $settings, $child_panel_id, false, $ancestor_panel_id, $node['title'], $title_id );
		} else {
			// Leaf item (D-04) — just close the li.
			echo '</li>';
		}
	}

	/**
	 * Render the back row for a child panel (back button + always-present parent title).
	 *
	 * The title span is ALWAYS emitted so render_panel()'s aria-labelledby reference
	 * resolves in every configuration (WR-01 fix). The span class switches between
	 * visible ddmm-back__title (show_back_title === 'yes') and visually-hidden
	 * ddmm-back__title screen-reader-text (toggle OFF) so screen readers still
	 * announce the parent name while honoring the user's visible-hide choice.
	 *
	 * @param string $parent_title      Parent item title for the title span.
	 * @param string $ancestor_panel_id Ancestor panel id (data-back-target target).
	 * @param string $title_id          Title span id (aria-labelledby target).
	 * @param array  $settings          Widget settings.
	 * @return void Echos HTML directly.
	 */
	private static function render_back_row( string $parent_title, string $ancestor_panel_id, string $title_id, array $settings ): void {
		echo '<div class="ddmm-back">';

		// D-10: back button carries data-back-target = ancestor panel id.
		printf(
			'<button type="button" class="ddmm-back__button" data-back-target="%s">&larr; %s</button>',
			esc_attr( $ancestor_panel_id ),
			esc_html__( 'Back', 'devsroom-drilldown-mobile-menu' )
		);

		// D-12 / WR-01 fix: ALWAYS emit the title span so render_panel()'s aria-labelledby
		// reference resolves in every configuration. The class switches between visible
		// (show_back_title === 'yes') and WordPress screen-reader-text (toggle OFF) so
		// screen readers still announce the parent name when the panel opens, while the
		// visible title honors the user's toggle choice.
		$show_back_title = $settings['show_back_title'] ?? 'yes';
		$title_class     = ( 'yes' === $show_back_title )
			? 'ddmm-back__title'
			: 'ddmm-back__title screen-reader-text';

		printf(
			'<span class="%s" id="%s">%s</span>',
			esc_attr( $title_class ),
			esc_attr( $title_id ),
			esc_html( $parent_title )
		);

		echo '</div>';
	}

	/**
	 * Render a menu item icon. Returns a string (does NOT echo).
	 *
	 * Replicates the Phase 1 trigger-icon ob_start/ob_get_clean pattern.
	 * D-29: WP items have icon=[] -> text-only (returns ''). D-30: present icons
	 * render via Icons_Manager inside an aria-hidden span.
	 *
	 * @param array $icon Elementor icon data ['value'=>..., 'library'=>...] or [].
	 * @return string Wrapped icon HTML or '' if empty.
	 */
	private static function render_icon( array $icon ): string {
		// D-29: only render when non-empty. WP nodes have icon = [] (text-only).
		if ( empty( $icon ) || empty( $icon['value'] ) ) {
			return '';
		}

		ob_start();
		\Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
		$icon_html = ob_get_clean();

		if ( empty( $icon_html ) ) {
			return '';
		}

		// D-30: wrap in aria-hidden span; output is pre-escaped by Icons_Manager.
		return '<span class="ddmm-menu__icon" aria-hidden="true">' . $icon_html . '</span>';
	}

	/**
	 * Elementor edit-mode entry point (D-18, D-07 full representative preview).
	 *
	 * Emits EVERY BEM surface the Style Tab controls so all six sections
	 * (STYL-01..06) are visible in the editor (SC#5 strict parity, D-08).
	 * Real BEM classes + same --ddmm-* vars/selectors mean Style Tab changes
	 * cascade through identically to the published page.
	 *
	 * Structure (in order):
	 *   1. Representative Trigger (.ddmm-trigger + .ddmm-hamburger) — STYL-01.
	 *   2. Representative Drawer container (.ddmm-drawer) — STYL-02. The
	 *      .ddmm-editor-preview CSS neutralizes the off-canvas transform
	 *      (Pitfall 8 — see ddmm-frontend.css).
	 *   3. Representative Header (.ddmm-header + .ddmm-brand__text +
	 *      .ddmm-close) — STYL-03.
	 *   4. Representative Search sample (.ddmm-search + .ddmm-search__input) —
	 *      STYL-06. Always rendered so users can pre-style search before
	 *      enabling it (D-08 discretion: always-show).
	 *   5. Representative Back Row (.ddmm-back + .ddmm-back__button +
	 *      .ddmm-back__title) — STYL-04.
	 *   6. Representative Menu Items (.ddmm-menu__item). The FIRST item carries
	 *      the ddmm-current-item marker class so the STYL-05 Active tab is
	 *      visible in the editor (D-04). Reuses render_editor_item() for the
	 *      item bodies (icon + label + chevron).
	 *
	 * Emits NO overlay, NO off-canvas transform, NO child panel siblings.
	 * Sub-panels are omitted per D-18 (root representative only). The method
	 * is wrapped in <div class="ddmm-editor-preview"> by DrillDownMenu::render().
	 *
	 * @param array $tree     Root-level nodes from WpNavTree/CustomTree.
	 * @param array $settings Widget settings.
	 * @return void Echos HTML directly.
	 */
	public static function render_editor_preview( array $tree, array $settings ): void {
		// 1. Representative Trigger (STYL-01) — hamburger type (default).
		// The .ddmm-trigger-wrapper mirrors the frontend trigger wrapper.
		echo '<div class="ddmm-trigger-wrapper">';
		echo '<button type="button" class="ddmm-trigger ddmm-trigger--hamburger" aria-expanded="false">';
		echo '<span class="ddmm-hamburger">';
		echo '<span class="ddmm-hamburger__line"></span>';
		echo '<span class="ddmm-hamburger__line"></span>';
		echo '<span class="ddmm-hamburger__line"></span>';
		echo '</span>';
		echo '</button>';
		echo '</div>';

		// 2. Representative Drawer preview container (STYL-02) — carries .ddmm-drawer
		// so the Drawer section selectors apply. The .ddmm-editor-preview CSS
		// neutralizes the off-canvas transform (Pitfall 8 — see Task 2 CSS).
		echo '<div class="ddmm-drawer">';

		// 3. Representative Header (STYL-03) — brand text + close button.
		echo '<div class="ddmm-header">';
		$brand_text = ! empty( $settings['brand_text'] )
			? $settings['brand_text']
			: get_bloginfo( 'name' );
		if ( empty( $brand_text ) ) {
			$brand_text = __( 'Brand Name', 'devsroom-drilldown-mobile-menu' );
		}
		printf(
			'<div class="ddmm-brand"><span class="ddmm-brand__text">%s</span></div>',
			esc_html( $brand_text )
		);
		printf(
			'<button type="button" class="ddmm-close" aria-label="%s">&times;</button>',
			esc_attr__( 'Close menu', 'devsroom-drilldown-mobile-menu' )
		);
		echo '</div>'; // .ddmm-header

		// 4. Representative Search sample (STYL-06) — always rendered so users
		// can pre-style search before enabling it (D-08 discretion: always-show).
		// Uses the same .ddmm-search / .ddmm-search__input classes as the frontend.
		$search_placeholder = ! empty( $settings['search_placeholder'] )
			? $settings['search_placeholder']
			: __( 'Search menu…', 'devsroom-drilldown-mobile-menu' );
		printf(
			'<div class="ddmm-search" role="search">' .
				'<input type="search" class="ddmm-search__input" placeholder="%s" disabled>' .
			'</div>',
			esc_attr( $search_placeholder )
		);

		// 5. Representative Back Row (STYL-04) — back button + sample parent title.
		echo '<div class="ddmm-back">';
		printf(
			'<button type="button" class="ddmm-back__button">&larr; %s</button>',
			esc_html__( 'Back', 'devsroom-drilldown-mobile-menu' )
		);
		printf(
			'<span class="ddmm-back__title">%s</span>',
			esc_html__( 'Parent Item', 'devsroom-drilldown-mobile-menu' )
		);
		echo '</div>'; // .ddmm-back

		// 6. Representative Menu Items (STYL-05) — render the configured tree
		// (capped to keep the preview compact). The FIRST leaf carries the
		// ddmm-current-item marker class so the STYL-05 Active tab is visible
		// in the editor (D-04). render_editor_item() is reused for the bodies.
		if ( ! empty( $tree ) ) {
			echo '<ul class="ddmm-menu">';

			// Cap at 6 items so the preview stays compact.
			$preview_items = array_slice( $tree, 0, 6 );
			$is_first      = true;
			foreach ( $preview_items as $node ) {
				if ( $is_first ) {
					self::render_editor_item( $node, $settings, true ); // $mark_active = true.
					$is_first = false;
				} else {
					self::render_editor_item( $node, $settings, false );
				}
			}
			echo '</ul>';
		} else {
			// Empty-tree fallback — show 2 sample placeholder items so the preview
			// is never empty even when the user hasn't configured a menu yet.
			echo '<ul class="ddmm-menu">';
			echo '<li class="ddmm-menu__item ddmm-current-item"><a href="#">' . esc_html__( 'Sample Current Page', 'devsroom-drilldown-mobile-menu' ) . '</a></li>';
			// WR-01 fix: chevron emitted as a SIBLING of <a> inside the <li> (NOT nested inside the anchor).
			// Matches the populated-tree branch in render_editor_item() — button type, ddmm-chevron class,
			// aria-label string, esc_attr__() escaping. Nesting the button inside <a> was invalid HTML5
			// (interactive content inside an anchor) and broke .ddmm-chevron { margin-left: auto }
			// which requires the button to be a flex child of .ddmm-menu__item.
			echo '<li class="ddmm-menu__item"><a href="#">' . esc_html__( 'Sample Menu Item', 'devsroom-drilldown-mobile-menu' ) . '</a><button type="button" class="ddmm-chevron" aria-label="' . esc_attr__( 'Show submenu', 'devsroom-drilldown-mobile-menu' ) . '"></button></li>';
			echo '</ul>';
		}

		echo '</div>'; // .ddmm-drawer
	}

	/**
	 * Non-recursive editor item helper (D-18, D-04 active marker for STYL-05).
	 *
	 * Emits a single <li> with optional icon + <a>/text + CSS-::after chevron
	 * (when has_children). Does NOT recurse into render_panel() and emits NO
	 * sibling child panel <div> and NO back row. The chevron here is a
	 * NON-FUNCTIONAL visual placeholder (the editor preview does not drill down).
	 *
	 * When $mark_active is true, the <li> additionally carries the
	 * ddmm-current-item marker class so the STYL-05 Active tab is visible in
	 * the editor preview (D-04). The caller (render_editor_preview) sets this
	 * on the first preview item only.
	 *
	 * @param array $node        Tree node (8-field contract).
	 * @param array $settings    Widget settings.
	 * @param bool  $mark_active Optional. Append ddmm-current-item marker class.
	 * @return void Echos HTML directly.
	 */
	private static function render_editor_item( array $node, array $settings, bool $mark_active = false ): void {
		$li_class = 'ddmm-menu__item';
		if ( $mark_active ) {
			$li_class .= ' ddmm-current-item'; // D-04: show Active tab in editor.
		}
		echo '<li class="' . esc_attr( $li_class ) . '">';

		// Icon (same render_icon helper — D-30).
		$icon_html = self::render_icon( $node['icon'] ?? [] );
		echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Icons_Manager output is pre-escaped.

		// Label <a> (D-01 / D-04) — identical to render_item.
		$url         = $node['url'] ?? '#';
		$target_attr = ! empty( $node['target'] ) ? ' target="_blank"' : '';

		$classes_attr = '';
		if ( ! empty( $node['classes'] ) && is_array( $node['classes'] ) ) {
			$sanitized = array_filter( array_map( 'sanitize_html_class', $node['classes'] ) );
			if ( ! empty( $sanitized ) ) {
				$classes_attr = ' class="' . esc_attr( implode( ' ', $sanitized ) ) . '"';
			}
		}

		printf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$target_attr,
			$classes_attr,
			esc_html( $node['title'] )
		);

		// Chevron visual ONLY when has_children (D-18 editor preview).
		// NO data-target/aria-controls/aria-expanded — static preview indicator.
		if ( ! empty( $node['has_children'] ) ) {
			printf(
				'<button type="button" class="ddmm-chevron" aria-label="%s"></button>',
				esc_attr( sprintf( __( 'Show %s submenu', 'devsroom-drilldown-mobile-menu' ), $node['title'] ) )
			);
		}

		echo '</li>';
	}
}
