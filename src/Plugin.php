<?php
/**
 * Main plugin bootstrap class.
 *
 * @package Devsroom_DDMM
 */

namespace Devsroom_DDMM;

use Devsroom_DDMM\Admin\ElementorNotice;
use Devsroom_DDMM\Assets\Registrar;
use Devsroom_DDMM\Elementor\Widget\DrillDownMenu;

/**
 * Plugin singleton — initializes hooks, checks Elementor dependency, registers widget.
 */
final class Plugin {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return self
     */
    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor prevents external instantiation.
     */
    private function __construct() {
        // Singleton — no external construction.
    }

    /**
     * Prevent cloning of the singleton.
     */
    private function __clone() {
        // Singleton — no cloning.
    }

    /**
     * Initialize the plugin.
     *
     * Checks for Elementor presence. If Elementor is not active, registers an
     * admin notice and returns early. Otherwise registers the widget category,
     * the widget itself, and the asset registrar.
     *
     * @return void
     */
    public function init(): void {
        // Always register the admin notice class — it guards itself internally.
        ( new ElementorNotice() )->register();

        // Without Elementor, nothing else should proceed.
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        // Register custom "Devsroom" widget category in Elementor.
        add_action( 'elementor/elements/categories_registered', [ $this, 'registerCategory' ] );

        // Register the drill-down menu widget.
        add_action( 'elementor/widgets/register', [ $this, 'registerWidget' ] );

        // Register (not enqueue) frontend assets — Elementor loads them conditionally.
        ( new Registrar() )->register();
    }

    /**
     * Register the "Devsroom" category in the Elementor widget panel.
     *
     * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
     * @return void
     */
    public function registerCategory( \Elementor\Elements_Manager $elements_manager ): void {
        $elements_manager->add_category(
            'devsroom',
            [
                'title' => esc_html__( 'Devsroom', 'devsroom-drilldown-mobile-menu' ),
            ]
        );
    }

    /**
     * Register the DrillDownMenu widget with Elementor.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
     * @return void
     */
    public function registerWidget( \Elementor\Widgets_Manager $widgets_manager ): void {
        $widgets_manager->register( new DrillDownMenu() );
    }
}
