<?php
/**
 * Elementor DrillDown Mobile Menu widget.
 *
 * @package Devsroom_DDMM\Elementor\Widget
 */

namespace Devsroom_DDMM\Elementor\Widget;

use Elementor\Widget_Base;

/**
 * DrillDown Mobile Menu widget for Elementor.
 *
 * Provides a mobile drill-down navigation menu with off-canvas drawer.
 * Phase 1 delivers the widget identity, asset dependencies, Content Tab
 * trigger controls, and trigger button rendering with four types.
 */
class DrillDownMenu extends Widget_Base {

    /**
     * Get the widget internal name.
     *
     * Used by Elementor for widget identification and CSS class generation.
     *
     * @return string Widget name.
     */
    public function get_name(): string {
        return 'ddmm-drilldown-menu';
    }

    /**
     * Get the widget title displayed in the Elementor panel.
     *
     * @return string Widget title.
     */
    public function get_title(): string {
        return esc_html__( 'DrillDown Mobile Menu', 'devsroom-drilldown-mobile-menu' );
    }

    /**
     * Get the widget icon displayed in the Elementor panel.
     *
     * Returns an inline SVG hamburger icon as a base64-encoded data URI.
     * Fallback: 'eicon-menu-bar' if the data URI does not render correctly.
     *
     * @return string Icon class or data URI.
     */
    public function get_icon(): string {
        return 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>'
        );
    }

    /**
     * Get the Elementor categories this widget belongs to.
     *
     * @return array<string> Category slugs.
     */
    public function get_categories(): array {
        return [ 'devsroom' ];
    }

    /**
     * Get script handles this widget depends on.
     *
     * Elementor enqueues these scripts only when the widget is present on the page.
     * The 'ddmm-frontend' handle is registered by the Assets\Registrar class.
     *
     * @return array<string> Registered script handles.
     */
    public function get_script_depends(): array {
        return [ 'ddmm-frontend' ];
    }

    /**
     * Get style handles this widget depends on.
     *
     * Elementor enqueues these styles only when the widget is present on the page.
     * The 'ddmm-frontend' handle is registered by the Assets\Registrar class.
     *
     * @return array<string> Registered style handles.
     */
    public function get_style_depends(): array {
        return [ 'ddmm-frontend' ];
    }

    /**
     * Register widget controls (Content Tab and Style Tab).
     *
     * Content Tab: Trigger Button configuration (Plan 02 / Phase 1).
     * Style Tab: Added in Phase 6.
     *
     * @return void
     */
    protected function _register_controls(): void {
        // --- Content Tab: Trigger Button Section ---
        $this->start_controls_section(
            'section_trigger',
            [
                'label' => esc_html__( 'Trigger Button', 'devsroom-drilldown-mobile-menu' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Trigger Type selector (per D-09).
        $this->add_control(
            'trigger_type',
            [
                'label'   => esc_html__( 'Trigger Type', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'hamburger',
                'options' => [
                    'hamburger'   => esc_html__( 'Hamburger Lines', 'devsroom-drilldown-mobile-menu' ),
                    'custom_icon' => esc_html__( 'Custom Icon', 'devsroom-drilldown-mobile-menu' ),
                    'text_only'   => esc_html__( 'Text Only', 'devsroom-drilldown-mobile-menu' ),
                    'icon_text'   => esc_html__( 'Icon + Text', 'devsroom-drilldown-mobile-menu' ),
                ],
            ]
        );

        // Custom Icon picker — shown only when trigger_type is 'custom_icon' (per TRIG-03, D-09).
        $this->add_control(
            'trigger_icon',
            [
                'label'     => esc_html__( 'Choose Icon', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-bars',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'trigger_type' => 'custom_icon',
                ],
            ]
        );

        // Button Text input — shown for text_only or icon_text (per TRIG-04, D-09).
        $this->add_control(
            'trigger_text',
            [
                'label'       => esc_html__( 'Button Text', 'devsroom-drilldown-mobile-menu' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => esc_html__( 'Menu', 'devsroom-drilldown-mobile-menu' ),
                'placeholder' => esc_html__( 'Enter menu text', 'devsroom-drilldown-mobile-menu' ),
                'condition'   => [
                    'trigger_type' => [ 'text_only', 'icon_text' ],
                ],
            ]
        );

        // Icon for Icon+Text mode (per TRIG-05, D-11).
        $this->add_control(
            'trigger_icon_text_icon',
            [
                'label'     => esc_html__( 'Choose Icon', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-bars',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'trigger_type' => 'icon_text',
                ],
            ]
        );

        // Icon Position (before/after text) — shown only for icon_text (per D-11).
        $this->add_control(
            'trigger_icon_position',
            [
                'label'     => esc_html__( 'Icon Position', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::CHOOSE,
                'default'   => 'before',
                'options'   => [
                    'before' => [
                        'title' => esc_html__( 'Before Text', 'devsroom-drilldown-mobile-menu' ),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'after'  => [
                        'title' => esc_html__( 'After Text', 'devsroom-drilldown-mobile-menu' ),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'condition' => [
                    'trigger_type' => 'icon_text',
                ],
            ]
        );

        $this->end_controls_section();

        // --- Content Tab: Menu Section (Phase 2, Plan 01) ---
        $this->start_controls_section(
            'section_menu',
            [
                'label'     => esc_html__( 'Menu', 'devsroom-drilldown-mobile-menu' ),
                'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
                'separator' => 'before',
            ]
        );

        // D-01: Menu Source toggle (WordPress Menu / Custom Builder).
        $this->add_control(
            'menu_source',
            [
                'label'   => esc_html__( 'Menu Source', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'wp_menu',
                'options' => [
                    'wp_menu' => esc_html__( 'WordPress Menu', 'devsroom-drilldown-mobile-menu' ),
                    'custom'  => esc_html__( 'Custom Builder', 'devsroom-drilldown-mobile-menu' ),
                ],
            ]
        );

        // WP Menu dropdown — visible only when source is 'wp_menu'.
        $this->add_control(
            'wp_menu_id',
            [
                'label'     => esc_html__( 'Select Menu', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => '',
                'options'   => $this->get_wp_menu_options(),
                'condition' => [
                    'menu_source' => 'wp_menu',
                ],
            ]
        );

        // --- Custom Menu Builder repeater (Phase 3, per D-04: condition on menu_source === 'custom') ---
        $custom_repeater = new \Elementor\Repeater();

        // Label field (per D-05 field order).
        $custom_repeater->add_control(
            'label',
            [
                'label'       => esc_html__( 'Label', 'devsroom-drilldown-mobile-menu' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => esc_html__( 'Menu Item Label', 'devsroom-drilldown-mobile-menu' ),
                'label_block' => true,
            ]
        );

        // URL field.
        $custom_repeater->add_control(
            'url',
            [
                'label'   => esc_html__( 'Link', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::URL,
                'default' => [ 'url' => '' ],
            ]
        );

        // Depth field (per D-01: NUMBER with min=0, step=1, default=0, no max cap).
        $custom_repeater->add_control(
            'depth',
            [
                'label'       => esc_html__( 'Depth', 'devsroom-drilldown-mobile-menu' ),
                'type'        => \Elementor\Controls_Manager::NUMBER,
                'min'         => 0,
                'step'        => 1,
                'default'     => 0,
                'description' => esc_html__( '0 = root, 1 = child, 2 = grandchild', 'devsroom-drilldown-mobile-menu' ),
            ]
        );

        // Icon field (per CMEN-05: Elementor Icons control).
        $custom_repeater->add_control(
            'icon',
            [
                'label'   => esc_html__( 'Icon', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::ICONS,
                'default' => [ 'value' => '', 'library' => '' ],
            ]
        );

        // Open in New Tab field.
        $custom_repeater->add_control(
            'new_tab',
            [
                'label'   => esc_html__( 'Open in New Tab', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        // Register the repeater as a widget control.
        $this->add_control(
            'custom_items',
            [
                'label'         => esc_html__( 'Menu Items', 'devsroom-drilldown-mobile-menu' ),
                'type'          => \Elementor\Controls_Manager::REPEATER,
                'fields'        => $custom_repeater->get_controls(),
                'title_field'   => '{{{ depth > 0 ? "—".repeat( depth ) + " " : "" }}} {{{ label }}}',
                'prevent_empty' => false,
                'condition'     => [
                    'menu_source' => 'custom',
                ],
            ]
        );

        $this->end_controls_section();

        // --- Content Tab: Drawer Header Section (Phase 4, Plan 02 / DRAW-03, D-05, D-06, D-08) ---
        $this->start_controls_section(
            'section_drawer_header',
            [
                'label'     => esc_html__( 'Drawer Header', 'devsroom-drilldown-mobile-menu' ),
                'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
                'separator' => 'before',
            ]
        );

        // D-05: Brand source SELECT — Site Logo (default) / Custom Image / Custom Text / None.
        $this->add_control(
            'brand_source',
            [
                'label'   => esc_html__( 'Brand', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'site_logo',
                'options' => [
                    'site_logo'    => esc_html__( 'Site Logo', 'devsroom-drilldown-mobile-menu' ),
                    'custom_image' => esc_html__( 'Custom Image', 'devsroom-drilldown-mobile-menu' ),
                    'custom_text'  => esc_html__( 'Custom Text', 'devsroom-drilldown-mobile-menu' ),
                    'none'         => esc_html__( 'None', 'devsroom-drilldown-mobile-menu' ),
                ],
            ]
        );

        // Custom Image — MEDIA control, conditional on brand_source === 'custom_image'.
        $this->add_control(
            'brand_image',
            [
                'label'     => esc_html__( 'Choose Image', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::MEDIA,
                'default'   => [ 'url' => '' ],
                'condition' => [ 'brand_source' => 'custom_image' ],
            ]
        );

        // Custom Text — TEXT control, conditional on brand_source === 'custom_text'.
        // Default = site name (sensible zero-config default).
        $this->add_control(
            'brand_text',
            [
                'label'       => esc_html__( 'Brand Text', 'devsroom-drilldown-mobile-menu' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => get_bloginfo( 'name' ),
                'placeholder' => esc_html__( 'Brand text', 'devsroom-drilldown-mobile-menu' ),
                'condition'   => [ 'brand_source' => 'custom_text' ],
            ]
        );

        $this->end_controls_section();

        // --- Content Tab: Drawer Settings Section (Phase 4, Plan 02 / D-21, D-12 + Phase 5 toggles D-15/D-16/D-17) ---
        $this->start_controls_section(
            'section_drawer_settings',
            [
                'label'     => esc_html__( 'Drawer Settings', 'devsroom-drilldown-mobile-menu' ),
                'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
                'separator' => 'before',
            ]
        );

        // D-21: nav aria-label — configurable, default translatable "Mobile Menu".
        $this->add_control(
            'nav_label',
            [
                'label'   => esc_html__( 'Navigation Label', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__( 'Mobile Menu', 'devsroom-drilldown-mobile-menu' ),
            ]
        );

        // D-12: show parent name in back row — default ON ('yes').
        $this->add_control(
            'show_back_title',
            [
                'label'   => esc_html__( 'Show Parent Name in Back Row', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        // Phase 5 / EXTR-03 / D-15: auto-open current page path — default ON ('yes').
        $this->add_control(
            'auto_open_current',
            [
                'label'   => esc_html__( 'Auto-Open Current Page Path', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        // Phase 5 / EXTR-04 / D-16: close drawer after a menu link is clicked — default ON ('yes').
        $this->add_control(
            'close_after_link',
            [
                'label'   => esc_html__( 'Close After Link Click', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        // Phase 5 / EXTR-05 / D-17: close drawer when the overlay is clicked — default ON ('yes').
        $this->add_control(
            'close_on_overlay',
            [
                'label'   => esc_html__( 'Close On Overlay Click', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // --- Content Tab: Animation Section (Phase 5, Plan 01 / ANIM-01, ANIM-02, ANIM-03, D-02) ---
        $this->start_controls_section(
            'section_animation',
            [
                'label'     => esc_html__( 'Animation', 'devsroom-drilldown-mobile-menu' ),
                'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
                'separator' => 'before',
            ]
        );

        // ANIM-01 / D-02: animation type SELECT — default 'slide'.
        $this->add_control(
            'animation_type',
            [
                'label'   => esc_html__( 'Animation Type', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'slide',
                'options' => [
                    'slide'     => esc_html__( 'Slide', 'devsroom-drilldown-mobile-menu' ),
                    'fade'      => esc_html__( 'Fade', 'devsroom-drilldown-mobile-menu' ),
                    'scale'     => esc_html__( 'Scale', 'devsroom-drilldown-mobile-menu' ),
                    'slidefade' => esc_html__( 'Slide + Fade', 'devsroom-drilldown-mobile-menu' ),
                ],
            ]
        );

        // ANIM-02: animation duration SLIDER — 100-2000ms, default 300ms.
        $this->add_control(
            'animation_duration',
            [
                'label'      => esc_html__( 'Duration (ms)', 'devsroom-drilldown-mobile-menu' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 100,
                        'max'  => 2000,
                        'step' => 10,
                    ],
                ],
                'default'    => [
                    'size' => 300,
                    'unit' => 'px',
                ],
            ]
        );

        // ANIM-03: animation easing SELECT — default 'ease'.
        $this->add_control(
            'animation_easing',
            [
                'label'   => esc_html__( 'Easing', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'ease',
                'options' => [
                    'ease'        => esc_html__( 'Ease', 'devsroom-drilldown-mobile-menu' ),
                    'ease-in'     => esc_html__( 'Ease In', 'devsroom-drilldown-mobile-menu' ),
                    'ease-out'    => esc_html__( 'Ease Out', 'devsroom-drilldown-mobile-menu' ),
                    'ease-in-out' => esc_html__( 'Ease In Out', 'devsroom-drilldown-mobile-menu' ),
                    'linear'      => esc_html__( 'Linear', 'devsroom-drilldown-mobile-menu' ),
                ],
            ]
        );

        $this->end_controls_section();

        // --- Content Tab: Search Section (Phase 5, Plan 01 / EXTR-01, D-09 opt-in) ---
        $this->start_controls_section(
            'section_search',
            [
                'label'     => esc_html__( 'Search', 'devsroom-drilldown-mobile-menu' ),
                'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
                'separator' => 'before',
            ]
        );

        // EXTR-01 / D-09: enable search — opt-in, default OFF ('').
        $this->add_control(
            'search_enabled',
            [
                'label'   => esc_html__( 'Enable Search', 'devsroom-drilldown-mobile-menu' ),
                'type'    => \Elementor\Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        // EXTR-01: search placeholder TEXT — shown only when search_enabled === 'yes'.
        $this->add_control(
            'search_placeholder',
            [
                'label'       => esc_html__( 'Placeholder', 'devsroom-drilldown-mobile-menu' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => esc_html__( 'Search menu…', 'devsroom-drilldown-mobile-menu' ),
                'label_block' => true,
                'condition'   => [
                    'search_enabled' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ====================================================================
        // Phase 6: Style Tab sections (STYL-01..06).
        // Plan 06-01 ships the first three: Trigger (STYL-01), Drawer (STYL-02),
        // and Header (STYL-03). Plans 06-02/06-03 append the remaining sections.
        // ====================================================================

        // --- Style Tab: Trigger Button (STYL-01 + D-02 trigger typography + D-03 trigger hover + D-05 responsive hamburger + D-06 per-side padding) ---
        $this->start_controls_section(
            'section_style_trigger',
            [
                'label'     => esc_html__( 'Trigger Button', 'devsroom-drilldown-mobile-menu' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'separator' => 'before',   // Pitfall 5: separate from last Content Tab section.
            ]
        );

        // Normal/Hover state tabs (D-03: trigger hover).
        $this->start_controls_tabs( 'trigger_state_tabs' );

        // NORMAL.
        $this->start_controls_tab(
            'trigger_normal',
            [ 'label' => esc_html__( 'Normal', 'devsroom-drilldown-mobile-menu' ) ]
        );
        $this->add_control(
            'trigger_color',
            [
                'label'     => esc_html__( 'Color', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#1a1a1a',   // Pitfall 9: matches --ddmm-trigger-color.
                'selectors' => [
                    '{{WRAPPER}}' => '--ddmm-trigger-color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'trigger_bg',
            [
                'label'     => esc_html__( 'Background', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--ddmm-trigger-bg: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_tab();

        // HOVER (D-03).
        $this->start_controls_tab(
            'trigger_hover',
            [ 'label' => esc_html__( 'Hover', 'devsroom-drilldown-mobile-menu' ) ]
        );
        $this->add_control(
            'trigger_hover_color',
            [
                'label'     => esc_html__( 'Color', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ddmm-trigger:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'trigger_hover_bg',
            [
                'label'     => esc_html__( 'Background', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ddmm-trigger:hover' => 'background: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // Hamburger size (D-05 responsive).
        $this->add_control(
            'hamburger_width',
            [
                'label'      => esc_html__( 'Hamburger Size', 'devsroom-drilldown-mobile-menu' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'responsive' => true,   // D-05.
                'range'      => [
                    'px' => [
                        'min'  => 16,
                        'max'  => 48,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 28,   // Pitfall 9: matches --ddmm-hamburger-width.
                ],
                'selectors'  => [
                    '{{WRAPPER}}' => '--ddmm-hamburger-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Padding (D-06 per-side Dimensions, linked by default).
        $this->add_control(
            'trigger_padding',
            [
                'label'      => esc_html__( 'Padding', 'devsroom-drilldown-mobile-menu' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'default'    => [
                    'top'      => 8,   // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.LongSpaceAlignment -- intentional grid for readability.
                    'right'    => 8,
                    'bottom'   => 8,
                    'left'     => 8,
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    // Pitfall 6: one {{UNIT}} per numeric token.
                    '{{WRAPPER}} .ddmm-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Border (group control — singular 'selector').
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'trigger_border',   // Pitfall 3: unique across widget.
                'label'    => esc_html__( 'Border', 'devsroom-drilldown-mobile-menu' ),
                'selector' => '{{WRAPPER}} .ddmm-trigger',
            ]
        );

        // Typography (D-02 trigger text — applies to the trigger button + its text span).
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'trigger_text_typography',   // Pitfall 3: unique across widget.
                'label'    => esc_html__( 'Typography', 'devsroom-drilldown-mobile-menu' ),
                'selector' => '{{WRAPPER}} .ddmm-trigger, {{WRAPPER}} .ddmm-trigger__text',
            ]
        );

        $this->end_controls_section();

        // --- Style Tab: Drawer (STYL-02 + D-05 drawer width responsive) ---
        $this->start_controls_section(
            'section_style_drawer',
            [
                'label' => esc_html__( 'Drawer', 'devsroom-drilldown-mobile-menu' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Width (D-05 responsive, px/vw/% per STYL-02).
        $this->add_control(
            'drawer_width',
            [
                'label'      => esc_html__( 'Drawer Width', 'devsroom-drilldown-mobile-menu' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vw', '%' ],   // STYL-02 requirement.
                'responsive' => true,                   // D-05.
                'range'      => [
                    'px' => [
                        'min'  => 240,
                        'max'  => 600,
                        'step' => 10,
                    ],
                    'vw' => [
                        'min'  => 50,
                        'max'  => 100,
                        'step' => 1,
                    ],
                    '%'  => [
                        'min'  => 50,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 320,   // Pitfall 9: matches --ddmm-drawer-width. CSS retains max-width:85vw cap (D-05 safety net).
                ],
                'selectors'  => [
                    '{{WRAPPER}}' => '--ddmm-drawer-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Drawer background.
        $this->add_control(
            'drawer_bg',
            [
                'label'     => esc_html__( 'Drawer Background', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',   // Pitfall 9: matches --ddmm-drawer-bg.
                'selectors' => [
                    '{{WRAPPER}}' => '--ddmm-drawer-bg: {{VALUE}};',
                ],
            ]
        );

        // Box shadow (group control).
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'drawer_box_shadow',   // Pitfall 3: unique across widget.
                'label'    => esc_html__( 'Box Shadow', 'devsroom-drilldown-mobile-menu' ),
                'selector' => '{{WRAPPER}} .ddmm-drawer',
            ]
        );

        // Overlay color (alpha-enabled per STYL-02).
        $this->add_control(
            'overlay_color',
            [
                'label'     => esc_html__( 'Overlay Color', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'alpha'     => true,   // Overlay is semi-transparent by design.
                'default'   => 'rgba(0,0,0,0.5)',   // Pitfall 9: matches --ddmm-overlay-bg.
                'selectors' => [
                    '{{WRAPPER}}' => '--ddmm-overlay-bg: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // --- Style Tab: Header (STYL-03 + D-02 title typography + D-03 close hover) ---
        $this->start_controls_section(
            'section_style_header',
            [
                'label' => esc_html__( 'Header', 'devsroom-drilldown-mobile-menu' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Header background.
        $this->add_control(
            'header_bg',
            [
                'label'     => esc_html__( 'Background', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ddmm-header' => 'background: {{VALUE}};',
                ],
            ]
        );

        // Header border (group control).
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'header_border',   // Pitfall 3: unique across widget.
                'label'    => esc_html__( 'Border', 'devsroom-drilldown-mobile-menu' ),
                'selector' => '{{WRAPPER}} .ddmm-header',
            ]
        );

        // Header height.
        $this->add_control(
            'header_height',
            [
                'label'      => esc_html__( 'Header Height', 'devsroom-drilldown-mobile-menu' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min'  => 40,
                        'max'  => 120,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 56,   // Pitfall 9: matches --ddmm-header-height.
                ],
                'selectors'  => [
                    '{{WRAPPER}}' => '--ddmm-header-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Title typography (D-02 — required by STYL-03).
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'header_title_typography',   // Pitfall 3: unique across widget.
                'label'    => esc_html__( 'Title Typography', 'devsroom-drilldown-mobile-menu' ),
                'selector' => '{{WRAPPER}} .ddmm-brand__text',
            ]
        );

        // Title color.
        $this->add_control(
            'header_title_color',
            [
                'label'     => esc_html__( 'Title Color', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#1a1a1a',   // Pitfall 9: matches --ddmm-header-title-color.
                'selectors' => [
                    '{{WRAPPER}}' => '--ddmm-header-title-color: {{VALUE}};',
                ],
            ]
        );

        // Close button color (D-03 close treatment).
        $this->add_control(
            'header_close_color',
            [
                'label'     => esc_html__( 'Close Button Color', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--ddmm-close-color: {{VALUE}};',
                ],
            ]
        );

        // Close button hover color (D-03 close hover).
        $this->add_control(
            'header_close_hover_color',
            [
                'label'     => esc_html__( 'Close Button Hover Color', 'devsroom-drilldown-mobile-menu' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--ddmm-close-hover-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build SELECT options from all registered WordPress nav menus.
     *
     * @return array<int|string, string> Menu term_id => menu name. Includes empty default.
     */
    protected function get_wp_menu_options(): array {
        $menus   = wp_get_nav_menus();
        $options = [ '' => esc_html__( '— Select a Menu —', 'devsroom-drilldown-mobile-menu' ) ];

        if ( empty( $menus ) ) {
            return $options;
        }

        foreach ( $menus as $menu ) {
            $options[ $menu->term_id ] = $menu->name;
        }

        return $options;
    }

    /**
     * Render the widget output on the frontend.
     *
     * Outputs the trigger button HTML based on the selected trigger type.
     * Four variants: Hamburger Lines (3 CSS spans), Custom Icon, Text Only,
     * and Icon + Text with configurable position.
     *
     * Phase 5: the .ddmm-widget wrapper now carries the config-bridge
     * data-* attributes and inline --ddmm-transition-* CSS vars consumed
     * by Plan 03 (CSS) and Plan 04 (JS). The trigger button carries the
     * data-ddmm-trigger hook attribute (Pitfall 1 fix).
     *
     * @return void
     */
    protected function render(): void {
        $settings     = $this->get_settings_for_display();
        $trigger_type = $settings['trigger_type'] ?? 'hamburger';
        $widget_id    = $this->get_id();

        // Phase 5 config bridge: extract per-instance settings (D-04, D-15, D-16, D-17).
        $anim_type     = $settings['animation_type'] ?? 'slide';
        $duration_size = $settings['animation_duration']['size'] ?? 300;
        $easing        = $settings['animation_easing'] ?? 'ease';
        $auto_open     = ( $settings['auto_open_current'] ?? 'yes' ) === 'yes';
        $close_link    = ( $settings['close_after_link'] ?? 'yes' ) === 'yes';
        $close_overlay = ( $settings['close_on_overlay'] ?? 'yes' ) === 'yes';
        ?>
        <div class="ddmm-widget ddmm-anim--<?php echo esc_attr( $anim_type ); ?>"
            id="ddmm-widget-<?php echo esc_attr( $widget_id ); ?>"
            data-ddmm-init
            data-ddmm-anim="<?php echo esc_attr( $anim_type ); ?>"
            data-ddmm-auto-open="<?php echo $auto_open ? 'true' : 'false'; ?>"
            data-ddmm-close-link="<?php echo $close_link ? 'true' : 'false'; ?>"
            data-ddmm-close-overlay="<?php echo $close_overlay ? 'true' : 'false'; ?>"
            style="--ddmm-transition-duration:<?php echo (int) $duration_size; ?>ms;--ddmm-transition-easing:<?php echo esc_attr( $easing ); ?>">
        <div class="ddmm-trigger-wrapper">
            <button
                type="button"
                class="ddmm-trigger ddmm-trigger--<?php echo esc_attr( $trigger_type ); ?>"
                aria-expanded="false"
                aria-controls="ddmm-drawer-<?php echo esc_attr( $widget_id ); ?>"
                data-ddmm-trigger
            >
                <?php
                switch ( $trigger_type ) {
                    case 'hamburger':
                        ?>
                        <span class="ddmm-hamburger">
                            <span class="ddmm-hamburger__line"></span>
                            <span class="ddmm-hamburger__line"></span>
                            <span class="ddmm-hamburger__line"></span>
                        </span>
                        <?php
                        break;

                    case 'custom_icon':
                        \Elementor\Icons_Manager::render_icon(
                            $settings['trigger_icon'],
                            [ 'aria-hidden' => 'true' ]
                        );
                        break;

                    case 'text_only':
                        echo esc_html( $settings['trigger_text'] );
                        break;

                    case 'icon_text':
                        // Capture icon HTML as string for concatenation with text.
                        ob_start();
                        \Elementor\Icons_Manager::render_icon(
                            $settings['trigger_icon_text_icon'],
                            [ 'aria-hidden' => 'true' ]
                        );
                        $icon_html = ob_get_clean();

                        $text     = esc_html( $settings['trigger_text'] );
                        $position = $settings['trigger_icon_position'] ?? 'before';

                        if ( 'before' === $position ) {
                            echo $icon_html . '<span class="ddmm-trigger__text">' . $text . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $icon_html already escaped by Icons_Manager
                        } else {
                            echo '<span class="ddmm-trigger__text">' . $text . '</span>' . $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $icon_html already escaped by Icons_Manager
                        }
                        break;
                }
                ?>
            </button>
        </div>
        <?php

        // --- Phase 2: Menu tree building (data layer only, per D-03) ---
        $menu_source = $settings['menu_source'] ?? 'wp_menu';
        $tree        = [];

        if ( 'wp_menu' === $menu_source && ! empty( $settings['wp_menu_id'] ) ) {
            $tree = \Devsroom_DDMM\MenuBuilder\WpNavTree::build( $settings['wp_menu_id'] );
        } elseif ( 'custom' === $menu_source && ! empty( $settings['custom_items'] ) ) {
            $tree = \Devsroom_DDMM\MenuBuilder\CustomTree::build( $settings['custom_items'] );
        }

        // D-05: Empty state — zero frontend HTML, editor-only hint.
        if ( empty( $tree ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                $hint = 'custom' === $menu_source
                    ? esc_html__( 'Add menu items to display', 'devsroom-drilldown-mobile-menu' )
                    : esc_html__( 'Select a menu to display', 'devsroom-drilldown-mobile-menu' );
                echo '<div class="ddmm-editor-hint">' . $hint . '</div>';
            }
            ?>
            </div><!-- /.ddmm-widget (empty-state early close) -->
            <?php
            return; // Zero frontend HTML for the menu portion.
        }

        // Phase 4: render the drawer / editor preview from $tree (D-03, D-16, D-18, D-20).
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if ( $is_editor ) {
            // D-18: editor-only static root-panel preview (inline, not off-canvas).
            // Sub-panels omitted. render_editor_preview() is owned and fully implemented in Plan 01.
            echo '<div class="ddmm-editor-preview">';
            \Devsroom_DDMM\Rendering\DrawerRenderer::render_editor_preview( $tree, $settings );
            echo '</div>';
        } else {
            // D-20: published frontend — full overlay + off-canvas drawer + panels always in DOM.
            \Devsroom_DDMM\Rendering\DrawerRenderer::render( $tree, $settings, $widget_id );
        }
        ?>
        </div><!-- /.ddmm-widget -->
        <?php
    }
}
