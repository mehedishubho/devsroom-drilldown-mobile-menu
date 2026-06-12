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
 * This Phase 1 shell registers the widget identity, icon, category, and
 * asset dependencies. Controls and rendering are populated in Plan 02.
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
    protected function register_controls(): void {
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
    }

    /**
     * Render the widget output on the frontend.
     *
     * Trigger button rendering added in Plan 02 / Phase 1.
     *
     * @return void
     */
    protected function render(): void {
        // Trigger button rendering added in Plan 02 / Phase 1.
    }
}
