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

        // autoOpenCurrentPath implemented in Task 3.
        autoOpenCurrentPath() { /* Task 3 */ }

        /**
         * D-06, D-08: Build flat index of all menu items with breadcrumbs.
         * Walks .ddmm-menu a[href] once on init. Breadcrumb via back-target
         * ancestor walk. All values stored as plain strings — safe to render later.
         */
        buildSearchIndex() {
            this.searchIndex = [];
            const seen = new Set();
            const links = this.container.querySelectorAll( '.ddmm-menu a[href]' );
            links.forEach( ( link ) => {
                const href = link.getAttribute( 'href' );
                if ( ! href || href === '#' || seen.has( href ) ) return;
                seen.add( href );
                const title = ( link.textContent || '' ).trim();
                if ( ! title ) return;

                // Breadcrumb: walk ancestor panels via back-target, collect back-row titles.
                const breadcrumb = [];
                let panel = link.closest( '.ddmm-panel' );
                while ( panel ) {
                    const titleEl = panel.querySelector( '.ddmm-back__title' );
                    if ( titleEl ) {
                        const t = ( titleEl.textContent || '' ).trim();
                        if ( t ) breadcrumb.unshift( t );
                    }
                    const backBtn = panel.querySelector( '[data-back-target]' );
                    const parentId = backBtn ? backBtn.dataset.backTarget : null;
                    panel = parentId
                        ? this.container.querySelector( '[data-panel-id="' + parentId + '"]' )
                        : null;
                }
                breadcrumb.push( title );

                // Drill target: if this item's <li> has a chevron child, that's the drill panel.
                const li = link.closest( '.ddmm-menu__item' );
                const chevron = li ? li.querySelector( '[data-target]' ) : null;

                this.searchIndex.push( {
                    title: title,
                    breadcrumb: breadcrumb.join( ' › ' ), // ›
                    href: href,
                    target: link.target,
                    drillPanelId: chevron ? chevron.dataset.target : null,
                } );
            } );
        }

        /**
         * Wire search input: 200ms-debounced filter (Anti-Pattern 4), Esc clears,
         * delegated result-click (parent drills, leaf navigates + closes per D-16).
         */
        wireSearch() {
            const input = this.container.querySelector( '[data-ddmm-search-input]' );
            if ( input ) {
                // Debounce wraps the FILTER only — input value updates immediately (Anti-Pattern 4).
                input.addEventListener( 'input', ( e ) => {
                    clearTimeout( this.searchTimer );
                    const value = e.target.value;
                    this.searchTimer = setTimeout( () => {
                        this.filterSearch( value );
                    }, 200 ); // Claude's Discretion (A3): 200ms.
                } );
                // Esc clears (D-06: clearing returns to drill view).
                input.addEventListener( 'keydown', ( e ) => {
                    if ( e.key === 'Escape' ) {
                        input.value = '';
                        this.clearSearch();
                        input.blur();
                    }
                } );
            }

            // Delegated click on results: parent drills (D-08), leaf navigates (+ closes per D-16).
            const resultsContainer = this.container.querySelector( '[data-ddmm-search-results]' );
            if ( resultsContainer ) {
                resultsContainer.addEventListener( 'click', ( e ) => {
                    const drillAnchor = e.target.closest( '[data-ddmm-search-drill]' );
                    if ( drillAnchor ) {
                        e.preventDefault();
                        this.clearSearch();
                        this.drill( drillAnchor.dataset.ddmmSearchDrill );
                        return;
                    }
                    const link = e.target.closest( 'a[href]' );
                    if ( link && this.config.closeLink && link.target !== '_blank' ) {
                        this.close();
                    }
                } );
            }
        }

        /**
         * Filter the search index by case-insensitive title substring (D-08) and
         * render matches via DOM APIs (ASVS V5 — zero string-concat HTML).
         * @param {string} rawQuery The current input value.
         */
        filterSearch( rawQuery ) {
            const results = this.container.querySelector( '[data-ddmm-search-results]' );
            if ( ! results ) return;
            const query = ( rawQuery || '' ).trim().toLowerCase();

            if ( ! query ) {
                this.clearSearch();
                return;
            }

            // D-06: enter search mode — hide drill view, show results.
            this.container.classList.add( 'ddmm-search-active' );

            // Clear previous results (safe — no user input here).
            while ( results.firstChild ) {
                results.removeChild( results.firstChild );
            }

            // Case-insensitive title substring match (D-08).
            const matches = this.searchIndex.filter(
                ( item ) => item.title.toLowerCase().indexOf( query ) >= 0
            );

            if ( ! matches.length ) {
                // D-11: "No results" message. textContent — NEVER string-concat HTML.
                const li = document.createElement( 'li' );
                li.className = 'ddmm-search__no-results';
                li.textContent = 'No results'; // Phase 7 i18n packaging; text domain ready.
                results.appendChild( li );
                return;
            }

            // Build result items via DOM APIs. textContent for title/breadcrumb (ASVS V5).
            matches.forEach( ( item ) => {
                const li = document.createElement( 'li' );
                li.className = 'ddmm-search__result-item';

                const a = document.createElement( 'a' );
                a.className = 'ddmm-search__result';
                if ( item.drillPanelId ) {
                    // Parent result: clicking drills into the panel (D-08). Do NOT navigate.
                    a.setAttribute( 'data-ddmm-search-drill', item.drillPanelId );
                    a.setAttribute( 'href', '#' );
                    // Prevent the close-after-link listener from firing on the # href.
                    a.setAttribute( 'role', 'button' );
                } else {
                    // Leaf result: navigate.
                    a.setAttribute( 'href', item.href );
                    if ( item.target === '_blank' ) {
                        a.setAttribute( 'target', '_blank' );
                    }
                }

                const titleSpan = document.createElement( 'span' );
                titleSpan.className = 'ddmm-search__result-title';
                titleSpan.textContent = item.title; // safe — textContent, not string concat

                const crumbSpan = document.createElement( 'span' );
                crumbSpan.className = 'ddmm-search__result-breadcrumb';
                crumbSpan.textContent = item.breadcrumb; // safe

                a.appendChild( titleSpan );
                a.appendChild( crumbSpan );
                li.appendChild( a );
                results.appendChild( li );
            } );
        }

        /**
         * Clear search state: exit search mode (removes ddmm-search-active),
         * empty results, clear input. Called on close(), empty query, Esc, and
         * parent-result drill (which exits search mode before calling drill()).
         */
        clearSearch() {
            this.container.classList.remove( 'ddmm-search-active' );
            const results = this.container.querySelector( '[data-ddmm-search-results]' );
            if ( results ) {
                while ( results.firstChild ) {
                    results.removeChild( results.firstChild );
                }
            }
            const input = this.container.querySelector( '[data-ddmm-search-input]' );
            if ( input ) {
                input.value = '';
            }
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
