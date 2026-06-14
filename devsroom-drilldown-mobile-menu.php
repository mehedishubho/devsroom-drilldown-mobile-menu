<?php
/**
 * Plugin Name: Devsroom DrillDown Mobile Menu
 * Description: A mobile drill-down menu widget for Elementor with off-canvas drawer navigation.
 * Version: 0.0.01
 * Author: MEHEDI HASSAN SHUBHO
 * Text Domain: devsroom-drilldown-mobile-menu
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// PSR-4 Autoloader — maps Devsroom_DDMM\ namespace to src/ directory.
spl_autoload_register( function ( string $class ): void {
    $prefix   = 'Devsroom_DDMM\\';
    $base_dir = __DIR__ . '/src/';
    $len      = strlen( $prefix );

    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// Initialize plugin after all loaded plugins have fired their hooks.
add_action( 'plugins_loaded', function (): void {
    \Devsroom_DDMM\Plugin::get_instance()->init();
} );
