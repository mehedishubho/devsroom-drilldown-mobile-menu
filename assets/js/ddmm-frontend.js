/**
 * Devsroom DrillDown Mobile Menu - Frontend Bootstrap
 *
 * Phase 4: Bootstrap skeleton only (JSCR-01..05, D-14).
 * - IIFE-wrapped, pure ES6 (no jQuery for DOM logic).
 * - Dual-path init: Elementor element_ready hook + DOMContentLoaded fallback.
 * - data-ddmm-init double-init guard.
 * Phase 5 adds: trigger click → open, overlay/close → close,
 * chevron click → drill down (data-target), back click → go back (data-back-target),
 * animation system, search, keyboard nav.
 */
( function() {
    'use strict';

    /**
     * DrillDownMenu controller — one instance per .ddmm-widget container.
     * Phase 4: locates the container and marks it initialized.
     * Phase 5: wires all interaction listeners scoped to the container.
     */
    class DrillDownMenu {
        /**
         * Initialize the drill-down menu on a widget container.
         *
         * @param {HTMLElement} container The .ddmm-widget root element.
         */
        init( container ) {
            // JSCR-04 / D-14: double-init guard via data-ddmm-init attribute.
            if ( ! container || container.dataset.ddmmInit ) {
                return;
            }
            container.dataset.ddmmInit = 'true';

            // Phase 4: container located and marked. No listeners yet.
            //
            // Phase 5 will add (all scoped to `container`, no globals — D-16, Anti-Pattern 3):
            //   - container.querySelector( '[data-ddmm-trigger]' ) → click opens drawer
            //   - container.querySelector( '[data-ddmm-overlay]' ) → click closes
            //   - container.querySelector( '[data-ddmm-close]' ) → click closes
            //   - chevrons: container.querySelectorAll( '[data-target]' ) → click drills in
            //     (ID lookup: container.querySelector( '[data-panel-id="' + target + '"]' ) — DRAW-10)
            //   - back buttons: container.querySelectorAll( '[data-back-target]' ) → click goes back
            //   - class toggles: container.classList.add/remove( 'ddmm-is-open' )
            //     panel.classList.add/remove( 'ddmm-panel--active' )
            //   - aria toggles: drawer aria-hidden, chevron aria-expanded/aria-label
        }
    }

    // Single shared instance — stateless in Phase 4 (init just guards + marks).
    const ddmm = new DrillDownMenu();

    /**
     * Path 1 (JSCR-03): Elementor frontend element_ready hook.
     * Fires once per widget instance when Elementor renders it.
     * Hook string uses widget get_name() = 'ddmm-drilldown-menu'.
     *
     * @param {*} $scope Elementor passes a jQuery-wrapped scope element.
     */
    function onElementReady( $scope ) {
        if ( ! $scope ) {
            return;
        }
        // Normalize jQuery-wrapped scope to HTMLElement, then find .ddmm-widget.
        const el = $scope && $scope[ 0 ] ? $scope[ 0 ] : $scope;
        const container = el.classList && el.classList.contains( 'ddmm-widget' )
            ? el
            : el.querySelector( '.ddmm-widget' );

        if ( container ) {
            ddmm.init( container );
        }
    }

    /**
     * Register the element_ready action on Elementor's frontend hooks.
     * Guarded: only runs if elementorFrontend and its hooks API exist.
     */
    function registerElementorHook() {
        if ( typeof elementorFrontend === 'undefined' || ! elementorFrontend.hooks ) {
            return;
        }
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/ddmm-drilldown-menu.default',
            onElementReady
        );
    }

    /**
     * Path 2 (JSCR-03): DOMContentLoaded fallback.
     * Covers non-Elementor-rendered pages, PJAX navigation, and editor preview
     * where element_ready may not fire. Queries all .ddmm-widget containers.
     */
    function onDomReady() {
        const containers = document.querySelectorAll( '.ddmm-widget' );
        containers.forEach( ( container ) => {
            ddmm.init( container );
        } );
    }

    // --- Bootstrap: wire both paths ---

    // Path 1a: elementorFrontend already available (late-loaded scripts).
    if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks ) {
        registerElementorHook();
    } else if ( typeof jQuery !== 'undefined' ) {
        // Path 1b: wait for Elementor's frontend/init event via its jQuery event bus.
        // NOTE: This subscribes to Elementor's OWN event system — it does NOT use
        // jQuery for DOM manipulation (JSCR-01 mandate is about plugin logic, not
        // Elementor's internal event bus). Per RESEARCH A1, this is the standard
        // Elementor widget pattern.
        jQuery( window ).on( 'elementor/frontend/init', registerElementorHook );
    }

    // Path 2: DOMContentLoaded fallback (always registered).
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', onDomReady );
    } else {
        // DOM already parsed (script loaded with defer or after DOMContentLoaded).
        onDomReady();
    }
} )();
