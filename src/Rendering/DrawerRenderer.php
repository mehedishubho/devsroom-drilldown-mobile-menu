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
}
