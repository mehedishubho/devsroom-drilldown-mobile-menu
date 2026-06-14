<?php
/**
 * Uninstaller for Devsroom DrillDown Mobile Menu.
 *
 * Runs when the plugin is deleted via the WordPress admin "Plugins" screen.
 * WordPress core sets the WP_UNINSTALL_PLUGIN constant immediately before
 * including this file — it is NOT defined during normal requests or direct access.
 *
 * @package Devsroom_DDMM
 *
 * Plugin Name: Devsroom DrillDown Mobile Menu
 * Version:     0.0.01
 * Author:      MEHEDI HASSAN SHUBHO
 * Text Domain: devsroom-drilldown-mobile-menu
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Plugin options to delete on uninstall.
 *
 * The plugin currently stores no options. To add cleanup for a future option,
 * append its key to this array — the deletion loop below handles the rest
 * automatically (single site AND multisite).
 *
 * @var string[]
 */
$devsroom_drilldown_options = array(
    // 'devsroom_drilldown_mobile_menu_settings',
    // 'devsroom_drilldown_mobile_menu_version',
);

foreach ( $devsroom_drilldown_options as $option_key ) {
    delete_option( $option_key );
}

if ( is_multisite() ) {
    global $wpdb;

    // Delete options from every blog on the network.
    $site_ids = get_sites(
        array(
            'fields'                 => 'ids',
            'number'                 => 0, // All sites, no limit.
            'update_site_cache'      => false,
            'update_site_meta_cache' => false,
        )
    );

    foreach ( $site_ids as $site_id ) {
        switch_to_blog( (int) $site_id );

        foreach ( $devsroom_drilldown_options as $option_key ) {
            delete_option( $option_key );
        }
    }

    restore_current_blog();

    // Also clean up any network-wide (site) options.
    foreach ( $devsroom_drilldown_options as $option_key ) {
        delete_site_option( $option_key );
    }
}

/**
 * Fires after Devsroom DrillDown Mobile Menu's own data has been removed.
 *
 * Third-party integrations or future plugin modules can hook here to clean
 * up their own data (custom post types, taxonomies, transients, etc.).
 *
 * @since 0.0.01
 */
do_action( 'devsroom_drilldown_mobile_menu_uninstall' );
