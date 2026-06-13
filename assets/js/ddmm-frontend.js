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
     * Phase 5: wires open/close/drill/back + animation + search + auto-open.
     * All queries scoped to this.container (Anti-Pattern 3). All navigation
     * uses ID lookup (DRAW-10). DOM-API-only result rendering (ASVS V5 XSS mitigation).
     */
    class DrillDownMenu {
        /**
         * Initialize the drill-down menu on a widget container.
         *
         * @param {HTMLElement} container The .ddmm-widget root element.
         */
        init( container ) {
            // JSCR-04: double-init guard via data-ddmm-init attribute.
            if ( ! container || container.dataset.ddmmInit ) {
                return;
            }

            // Pitfall 3: editor-mode guard. The Elementor editor preview emits
            // no [data-ddmm-drawer] (Phase 4 D-18 static preview), so listeners
            // would no-op anyway — but skip them explicitly for clarity.
            if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode() ) {
                return;
            }

            container.dataset.ddmmInit = 'true';
            this.container = container;
            this.history = [];
            this.searchIndex = [];
            this.searchTimer = null;

            // Pattern 4: parse config once (data-* bridge from PHP).
            this.config = {
                anim:         container.dataset.ddmmAnim || 'slide',
                autoOpen:     container.dataset.ddmmAutoOpen !== 'false',
                closeLink:    container.dataset.ddmmCloseLink !== 'false',
                closeOverlay: container.dataset.ddmmCloseOverlay !== 'false',
                searchOn:     !! container.querySelector( '[data-ddmm-search]' ),
            };

            // Apply the animation-type class. Plan 01 also emits it server-side,
            // but this guarantees it for any edge case where PHP missed it.
            this.applyAnimationType( this.config.anim );

            // Wire listeners (each method adds its own scoped listeners).
            this.wireOpenClose();
            this.wireDrillBack();
            this.wireCloseBehaviors();
            if ( this.config.searchOn ) {
                this.buildSearchIndex();
                this.wireSearch();
            }
        }

        /**
         * Apply the ddmm-anim--{type} container class. Plan 03 CSS resolves it.
         * @param {string} type Animation type (slide|fade|scale|slidefade).
         */
        applyAnimationType( type ) {
            const valid = [ 'slide', 'fade', 'scale', 'slidefade' ];
            const t = valid.indexOf( type ) >= 0 ? type : 'slide';
            // Strip any prior anim class, add the new one.
            valid.forEach( ( v ) => this.container.classList.remove( 'ddmm-anim--' + v ) );
            this.container.classList.add( 'ddmm-anim--' + t );
        }

        /**
         * Wire trigger open, close button, and overlay click listeners.
         */
        wireOpenClose() {
            // Trigger open. Pitfall 1: query both the hook attr AND the class fallback.
            const trigger = this.container.querySelector( '[data-ddmm-trigger], .ddmm-trigger' );
            if ( trigger ) {
                trigger.addEventListener( 'click', () => this.open() );
            }
            // Close button always closes (Phase 4 D-07).
            const closeBtn = this.container.querySelector( '[data-ddmm-close]' );
            if ( closeBtn ) {
                closeBtn.addEventListener( 'click', () => this.close() );
            }
            // Overlay gated by toggle (D-17).
            const overlay = this.container.querySelector( '[data-ddmm-overlay]' );
            if ( overlay && this.config.closeOverlay ) {
                overlay.addEventListener( 'click', () => this.close() );
            }
        }

        /**
         * Open the drawer: toggle ddmm-is-open, trigger morph, drawer/overlay aria.
         * D-12: auto-open current path on manual open (instant, no animation).
         */
        open() {
            this.container.classList.add( 'ddmm-is-open' );
            // Trigger morph + aria.
            const trigger = this.container.querySelector( '[data-ddmm-trigger], .ddmm-trigger' );
            if ( trigger ) {
                trigger.classList.add( 'ddmm-trigger--active' );
                trigger.setAttribute( 'aria-expanded', 'true' );
            }
            // Drawer + overlay aria.
            const drawer = this.container.querySelector( '[data-ddmm-drawer]' );
            const overlay = this.container.querySelector( '[data-ddmm-overlay]' );
            if ( drawer ) drawer.setAttribute( 'aria-hidden', 'false' );
            if ( overlay ) overlay.setAttribute( 'aria-hidden', 'false' );
            // D-12: auto-open current path on manual open (instant).
            if ( this.config.autoOpen ) {
                this.autoOpenCurrentPath();
            }
        }

        /**
         * D-19: single close path. Reverses everything open() and drill() did.
         */
        close() {
            this.container.classList.remove( 'ddmm-is-open' );
            const trigger = this.container.querySelector( '[data-ddmm-trigger], .ddmm-trigger' );
            if ( trigger ) {
                trigger.classList.remove( 'ddmm-trigger--active' );
                trigger.setAttribute( 'aria-expanded', 'false' );
            }
            const drawer = this.container.querySelector( '[data-ddmm-drawer]' );
            const overlay = this.container.querySelector( '[data-ddmm-overlay]' );
            if ( drawer ) drawer.setAttribute( 'aria-hidden', 'true' );
            if ( overlay ) overlay.setAttribute( 'aria-hidden', 'true' );

            this.resetPanels();
            this.clearSearch();
            this.history = [];
        }

        /**
         * Reset all panels to initial state: root active, others off-stage.
         */
        resetPanels() {
            const panels = this.container.querySelectorAll( '.ddmm-panel' );
            panels.forEach( ( panel, idx ) => {
                panel.classList.remove( 'ddmm-panel--exited-left' );
                if ( idx === 0 ) {
                    panel.classList.add( 'ddmm-panel--active' );
                    panel.setAttribute( 'aria-hidden', 'false' );
                } else {
                    panel.classList.remove( 'ddmm-panel--active' );
                    panel.setAttribute( 'aria-hidden', 'true' );
                }
            } );
        }

        /**
         * Wire delegated drill (chevron) + back listeners on the drawer.
         */
        wireDrillBack() {
            const drawer = this.container.querySelector( '[data-ddmm-drawer]' );
            if ( ! drawer ) return;
            drawer.addEventListener( 'click', ( e ) => {
                // Drill: chevron (data-target).
                const chevron = e.target.closest( '[data-target]' );
                if ( chevron ) {
                    e.preventDefault();
                    this.drill( chevron.dataset.target );
                    return;
                }
                // Back: back button (data-back-target).
                const backBtn = e.target.closest( '[data-back-target]' );
                if ( backBtn ) {
                    e.preventDefault();
                    this.back();
                    return;
                }
            } );
        }

        /**
         * D-05: outgoing active -> exited-left, incoming off-stage-right -> active.
         * DRAW-10: ID-based lookup ONLY — no positional logic.
         * @param {string} targetPanelId data-panel-id of the child panel.
         */
        drill( targetPanelId ) {
            if ( ! targetPanelId ) return;
            const incoming = this.container.querySelector(
                '[data-panel-id="' + targetPanelId + '"]'
            );
            if ( ! incoming ) return;
            const outgoing = this.container.querySelector( '.ddmm-panel--active' );
            if ( ! outgoing ) return;

            outgoing.classList.remove( 'ddmm-panel--active' );
            outgoing.classList.add( 'ddmm-panel--exited-left' );
            outgoing.setAttribute( 'aria-hidden', 'true' );

            incoming.classList.remove( 'ddmm-panel--exited-left' ); // safety
            incoming.classList.add( 'ddmm-panel--active' );
            incoming.setAttribute( 'aria-hidden', 'false' );

            // History for back-nav: push the OUTGOING panel id.
            this.history.push( outgoing.dataset.panelId );

            // Chevron aria-expanded flip (D-23).
            const chevron = this.container.querySelector( '[data-target="' + targetPanelId + '"]' );
            if ( chevron ) {
                chevron.setAttribute( 'aria-expanded', 'true' );
            }

            // Scroll incoming to top; reset outgoing after transitionend (Pitfall 4).
            incoming.scrollTop = 0;
            const self = this; // eslint-disable-line no-unused-vars
            outgoing.addEventListener( 'transitionend', function onEnd( ev ) {
                if ( ev.propertyName !== 'transform' ) return; // Pitfall 4
                outgoing.removeEventListener( 'transitionend', onEnd );
                outgoing.scrollTop = 0;
            } );
        }

        /**
         * Reverse of drill: pop history, current -> off-stage, previous exited-left -> active.
         */
        back() {
            if ( ! this.history.length ) return;
            const previousId = this.history.pop();
            const incoming = this.container.querySelector(
                '[data-panel-id="' + previousId + '"]'
            );
            const outgoing = this.container.querySelector( '.ddmm-panel--active' );
            if ( ! incoming || ! outgoing ) return;

            // Outgoing: active -> off-stage-right (remove both state classes).
            outgoing.classList.remove( 'ddmm-panel--active' );
            outgoing.classList.remove( 'ddmm-panel--exited-left' );
            outgoing.setAttribute( 'aria-hidden', 'true' );

            // Incoming: exited-left -> active.
            incoming.classList.remove( 'ddmm-panel--exited-left' );
            incoming.classList.add( 'ddmm-panel--active' );
            incoming.setAttribute( 'aria-hidden', 'false' );

            // Chevron aria-expanded flip back (D-23).
            const chevron = this.container.querySelector(
                '[data-target="' + outgoing.dataset.panelId + '"]'
            );
            if ( chevron ) {
                chevron.setAttribute( 'aria-expanded', 'false' );
            }
        }

        /**
         * D-16, D-18: close-after-link-click delegated on panels.
         * New-tab links leave the drawer open.
         */
        wireCloseBehaviors() {
            const panels = this.container.querySelector( '.ddmm-panels' );
            if ( panels ) {
                panels.addEventListener( 'click', ( e ) => {
                    if ( ! this.config.closeLink ) return;
                    const link = e.target.closest( 'a[href]' );
                    if ( ! link ) return;
                    // D-18: new-tab links leave the drawer open.
                    if ( link.target === '_blank' || link.getAttribute( 'target' ) === '_blank' ) {
                        return;
                    }
                    this.close();
                } );
            }
        }

        // Placeholder stubs — implemented in Task 2 (search) and Task 3 (auto-open).
        // They exist here so open()/close() can call them without undefined errors.
        autoOpenCurrentPath() { /* Task 3 */ }
        buildSearchIndex() { /* Task 2 */ }
        wireSearch() { /* Task 2 */ }
        clearSearch() { /* Task 2 */ }
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
