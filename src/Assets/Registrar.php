<?php
/**
 * Frontend asset registrar — registers (does NOT enqueue) scripts and styles.
 *
 * @package Devsroom_DDMM\Assets
 */

namespace Devsroom_DDMM\Assets;

/**
 * Registers the plugin's frontend CSS and JS with WordPress.
 *
 * Actual enqueueing is handled by Elementor via the widget's
 * get_script_depends() and get_style_depends() methods, ensuring
 * assets load only on pages where the widget is present.
 */
class Registrar {

    /**
     * Register the frontend script and style on wp_enqueue_scripts.
     *
     * REGISTER ONLY — never call wp_enqueue_script() or wp_enqueue_style().
     * Elementor conditionally enqueues based on widget presence.
     *
     * @return void
     */
    public function register(): void {
        add_action( 'wp_enqueue_scripts', function (): void {
            wp_register_script(
                'ddmm-frontend',
                plugins_url( 'assets/js/ddmm-frontend.js', dirname( __DIR__, 2 ) ),
                [],
                '0.0.01',
                true
            );

            wp_register_style(
                'ddmm-frontend',
                plugins_url( 'assets/css/ddmm-frontend.css', dirname( __DIR__, 2 ) ),
                [],
                '0.0.01'
            );
        } );
    }
}
