<?php
/**
 * Admin notice displayed when Elementor is not active.
 *
 * @package Devsroom_DDMM\Admin
 */

namespace Devsroom_DDMM\Admin;

/**
 * Displays a WordPress admin notice when Elementor is inactive.
 *
 * Checks whether Elementor is installed (but inactive) or not installed at all,
 * and provides an appropriate activation or installation link.
 */
class ElementorNotice {

    /**
     * Register the admin notice hook if Elementor is not loaded.
     *
     * @return void
     */
    public function register(): void {
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'renderNotice' ] );
        }
    }

    /**
     * Render the admin notice.
     *
     * Shows an activation link if Elementor is installed but inactive,
     * or an installation link if Elementor is not installed at all.
     *
     * @return void
     */
    public function renderNotice(): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $plugin_file = 'elementor/elementor.php';

        if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
            // Elementor is installed but not active — offer activation link.
            $action_url = wp_nonce_url(
                admin_url( 'plugins.php?action=activate&plugin=' . $plugin_file ),
                'activate-plugin_' . $plugin_file
            );
            $message = sprintf(
                /* translators: %s: activation link */
                esc_html__( 'Devsroom DrillDown Menu requires Elementor to be active. %s', 'devsroom-drilldown-mobile-menu' ),
                '<a href="' . esc_url( $action_url ) . '">' . esc_html__( 'Activate Elementor', 'devsroom-drilldown-mobile-menu' ) . '</a>'
            );
        } else {
            // Elementor is not installed — offer installation link.
            $install_url = admin_url( 'plugin-install.php?s=elementor&tab=search&type=term' );
            $message     = sprintf(
                /* translators: %s: installation link */
                esc_html__( 'Devsroom DrillDown Menu requires Elementor. %s', 'devsroom-drilldown-mobile-menu' ),
                '<a href="' . esc_url( $install_url ) . '">' . esc_html__( 'Install Elementor', 'devsroom-drilldown-mobile-menu' ) . '</a>'
            );
        }

        printf(
            '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
            wp_kses_post( $message )
        );
    }
}
