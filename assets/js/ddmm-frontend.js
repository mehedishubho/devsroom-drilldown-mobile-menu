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
            // Phase 7 D-07: track the trigger so close() can restore focus.
            this.lastTrigger = null;
            // Phase 7: store the document-level keydown handler reference so it can be detached (Pitfall 2).
            this.docHandler = null;

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
            // Phase 7: drawer-scoped keydown for Arrow roving (Tab trap + Esc attach on open).
            this.wireKeyboard();
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
            // Phase 7 A11Y-04/05/08: attach doc-level listeners, move focus, announce.
            this.attachDocListeners();
            this.focusInitialTarget(); // D-03: focus moves into the drawer.
            this.announcePanelContext(); // D-08: announce the initial panel context.
        }

        /**
         * D-19: single close path. Reverses everything open() and drill() did.
         */
        close() {
            // Phase 7 D-07: capture the trigger NOW so focus can be restored after cleanup.
            this.lastTrigger = this.container.querySelector( '[data-ddmm-trigger], .ddmm-trigger' );
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
            // Phase 7 A11Y-05/08: detach the Tab trap (Pitfall 2) and restore focus to the trigger (D-07).
            this.detachDocListeners();
            if ( this.lastTrigger ) {
                this.lastTrigger.focus();
            }
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
            // Phase 7 D-05/D-08: focus the first item of the new panel + announce its context.
            this.focusInitialTarget();
            this.announcePanelContext();
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
            // Phase 7 D-05/D-08: focus the first item of the returned panel + announce its context.
            this.focusInitialTarget();
            this.announcePanelContext();
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

        /**
         * Phase 7: wire the drawer-scoped keydown listener (ArrowUp/ArrowDown roving).
         * Tab trap + Esc are document-level (attachDocListeners/detachDocListeners)
         * because Tab can otherwise leak past the drawer boundary and Esc precedence
         * must be global while the drawer is open.
         */
        wireKeyboard() {
            const drawer = this.container.querySelector( '[data-ddmm-drawer]' );
            if ( ! drawer ) return;
            drawer.addEventListener( 'keydown', ( e ) => this.onDrawerKeydown( e ) );
        }

        /**
         * Drawer-scoped keydown handler: ArrowUp/ArrowDown move the roving tabindex
         * among sibling items in the active panel. Enter/Space are intentionally NOT
         * handled here — native <a>/<button> activation handles them, and the existing
         * delegated click handler at line 168 routes chevron/back clicks to drill()/back().
         */
        onDrawerKeydown( e ) {
            if ( e.key === 'ArrowDown' || e.key === 'ArrowUp' ) {
                e.preventDefault();
                this.moveRoving( e.key === 'ArrowDown' ? 1 : -1 );
            }
            // Enter / ' ' (Space) fall through to native activation.
        }

        /**
         * Phase 7 D-02 + A11Y-04/05: attach the document-level keydown handler
         * (Esc back-then-close + Tab trap). Called from open(). Stores the handler
         * reference on the instance so detachDocListeners() can remove the EXACT
         * same function reference (Pitfall 2).
         */
        attachDocListeners() {
            this.docHandler = ( e ) => this.onDocKeydown( e );
            document.addEventListener( 'keydown', this.docHandler );
        }

        /**
         * Pitfall 2: detach the document-level keydown handler. Called from close()
         * so the trap detaches on EVERY close path (D-19 single close path guarantees
         * this is reached for Esc / overlay / ✕ / link-click).
         */
        detachDocListeners() {
            if ( this.docHandler ) {
                document.removeEventListener( 'keydown', this.docHandler );
                this.docHandler = null;
            }
        }

        /**
         * Document-level keydown: Esc precedence (D-02) + Tab trap (A11Y-05).
         * Anti-Pattern 3: scope via this.container.contains(e.target) so container A
         * never affects container B.
         */
        onDocKeydown( e ) {
            // Per-container scope guard.
            if ( ! this.container.contains( e.target ) ) return;

            if ( e.key === 'Escape' ) {
                // Pitfall 1: if the search input has focus, let the existing listener
                // at line 447 handle Esc (clear + blur). Once blurred, the next Esc
                // press correctly routes here -> back-then-close.
                const searchInput = this.container.querySelector( '[data-ddmm-search-input]' );
                if ( searchInput && document.activeElement === searchInput ) return;

                e.preventDefault();
                // D-02: back one level first; if already at root, close.
                if ( this.history.length > 0 ) {
                    this.back();
                    this.focusInitialTarget(); // D-05: focus first item of the panel we returned to
                    this.announcePanelContext(); // D-08: announce the panel we returned to
                } else {
                    this.close(); // routes through D-19 single close path (restores focus + detaches trap)
                }
                return;
            }

            if ( e.key === 'Tab' ) {
                this.trapTab( e );
            }
        }

        /**
         * A11Y-05 Tab trap: Tab on last focusable wraps to first; Shift+Tab on first
         * wraps to last. Per Pattern 2 selector. Pitfall 6: filter display:none elements
         * via offsetParent so hidden search results are never Tab targets.
         */
        trapTab( e ) {
            const focusables = this.getFocusables();
            if ( ! focusables.length ) return;
            const first = focusables[ 0 ];
            const last = focusables[ focusables.length - 1 ];
            if ( e.shiftKey && document.activeElement === first ) {
                e.preventDefault();
                last.focus();
            } else if ( ! e.shiftKey && document.activeElement === last ) {
                e.preventDefault();
                first.focus();
            }
        }

        /**
         * Pattern 2 focusable selector — query inside this.container only.
         * Selects: close, back, search input, active panel's leaf <a>s + chevrons.
         * Pitfall 6: filter out display:none (search results hidden unless .ddmm-search-active).
         */
        getFocusables() {
            const all = this.container.querySelectorAll(
                '[data-ddmm-close], [data-back-target], [data-ddmm-search-input], ' +
                '.ddmm-panel--active .ddmm-menu > li > a, ' +
                '.ddmm-panel--active .ddmm-menu .ddmm-chevron'
            );
            return Array.from( all ).filter( ( el ) => el.offsetParent !== null );
        }

        /**
         * A11Y-06 / D-11 roving tabindex move. Operates on the active panel's
         * sibling items (leaf <a>s + chevrons). Pitfall 3: reset ALL items to -1
         * before setting the new tabindex=0 (prevents drift across drill/back).
         * @param {number} direction +1 for ArrowDown, -1 for ArrowUp.
         */
        moveRoving( direction ) {
            const panel = this.container.querySelector( '.ddmm-panel--active' );
            if ( ! panel ) return;
            const items = Array.from(
                panel.querySelectorAll( '.ddmm-menu > li > a, .ddmm-menu .ddmm-chevron' )
            );
            if ( items.length < 2 ) return;
            const currentIdx = items.findIndex( ( el ) => el.tabIndex === 0 );
            const startIdx = currentIdx >= 0 ? currentIdx : 0;
            const nextIdx = ( startIdx + direction + items.length ) % items.length;
            // Pitfall 3: reset ALL, then set the new active.
            items.forEach( ( el ) => { el.tabIndex = -1; } );
            items[ nextIdx ].tabIndex = 0;
            items[ nextIdx ].focus();
        }

        /**
         * D-03/D-05/D-11: set up roving for the active panel and move focus to the
         * D-03 target. Called from open() after autoOpenCurrentPath(), and from
         * drill()/back()/Esc-back after panel state changes.
         *
         * D-03 target priority: the auto-opened current item (.ddmm-current-item > a)
         * if present, else the first sibling item (leaf <a> or chevron).
         */
        focusInitialTarget() {
            const panel = this.container.querySelector( '.ddmm-panel--active' );
            if ( ! panel ) return;
            const items = Array.from(
                panel.querySelectorAll( '.ddmm-menu > li > a, .ddmm-menu .ddmm-chevron' )
            );
            if ( ! items.length ) return;
            // D-03: prefer the auto-opened current item's anchor.
            const current = panel.querySelector( '.ddmm-current-item > a' );
            const target = current || items[ 0 ];
            // Pitfall 3: reset ALL items to tabindex=-1, then set tabindex=0 on target.
            items.forEach( ( el ) => { el.tabIndex = -1; } );
            target.tabIndex = 0;
            target.focus();
        }

        /**
         * D-08: write the active panel's context to the aria-live region.
         * Child panel -> its .ddmm-back__title textContent (parent name).
         * Root panel -> the .ddmm-nav aria-label.
         * textContent only — NEVER innerHTML (ASVS V5, Threat T-07-01-01).
         */
        announcePanelContext() {
            const status = this.container.querySelector( '[data-ddmm-sr-status]' );
            if ( ! status ) return;
            const panel = this.container.querySelector( '.ddmm-panel--active' );
            const title = panel ? panel.querySelector( '.ddmm-back__title' ) : null;
            const nav = this.container.querySelector( '.ddmm-nav' );
            const navLabel = nav ? nav.getAttribute( 'aria-label' ) : '';
            status.textContent = ( title && title.textContent ) || navLabel || '';
        }

        /**
         * D-14: URL normalization. Native WHATWG URL API — no regex, no library.
         * Strips hash, trims trailing slash (except root), lowercases host,
         * sorts query params for order-insensitive comparison.
         * @param {string} raw URL to normalize (item href or window.location.href).
         * @returns {string|null} Normalized "host/path?query" string, or null.
         */
        normalizeUrl( raw ) {
            if ( ! raw || raw === '#' ) return null;
            try {
                const u = new URL( raw, window.location.origin );
                let path = u.pathname.replace( /\/+$/, '' ) || '/'; // Pitfall 8: root stays '/'
                if ( u.search ) {
                    const params = new URLSearchParams( u.search );
                    const sorted = Array.from( params.keys() ).sort().map(
                        ( k ) => k + '=' + params.get( k )
                    ).join( '&' );
                    if ( sorted ) path += '?' + sorted;
                }
                return u.host.toLowerCase() + path;
            } catch ( e ) {
                return null;
            }
        }

        /**
         * D-14: WP 'current-menu-item' class is a hint (WP source only, server-injected).
         * URL match is the authoritative fallback (works for both menu sources).
         * @returns {HTMLAnchorElement|null} The matching anchor, or null if not in menu.
         */
        findCurrentPageItem() {
            const current = this.normalizeUrl( window.location.href );
            if ( ! current ) return null;
            const links = this.container.querySelectorAll( '.ddmm-menu a[href]' );
            for ( const link of links ) {
                // Hint short-circuit: WP Walker injects current-menu-item on the <li>.
                if ( link.closest( '.current-menu-item' ) ) {
                    return link;
                }
                // URL match authoritative.
                if ( this.normalizeUrl( link.href ) === current ) {
                    return link;
                }
            }
            return null;
        }

        /**
         * D-12, D-13: on open, drill to current item's panel instantly + mark item + ancestors.
         * Instant-drill via 0ms --ddmm-transition-duration override, restored via double-rAF.
         */
        autoOpenCurrentPath() {
            const item = this.findCurrentPageItem();
            if ( ! item ) return; // D-14: not in menu -> do nothing.

            // D-13: mark current item + ancestor <li>s.
            const itemLi = item.closest( '.ddmm-menu__item' );
            if ( itemLi ) {
                itemLi.classList.add( 'ddmm-current-item' );
            }
            // Mark all ancestor <li>s across the panel chain; collect panels root-first.
            const ancestorPanels = [];
            let cursor = item.closest( '.ddmm-panel' );
            while ( cursor ) {
                const ancestorLis = cursor.querySelectorAll( '.ddmm-menu__item' );
                ancestorLis.forEach( ( li ) => {
                    if ( li.contains( item ) && li !== itemLi ) {
                        li.classList.add( 'ddmm-current-ancestor' );
                    }
                } );
                ancestorPanels.unshift( cursor ); // root-first order
                const backBtn = cursor.querySelector( '[data-back-target]' );
                const parentId = backBtn ? backBtn.dataset.backTarget : null;
                cursor = parentId
                    ? this.container.querySelector( '[data-panel-id="' + parentId + '"]' )
                    : null;
            }

            // If only the root panel is in the chain, no drill needed.
            if ( ancestorPanels.length <= 1 ) return;

            // Instant-drill: temporarily set duration to 0, restore after double-rAF.
            // The double-rAF ensures the browser commits the panel state change at 0ms
            // BEFORE restoring the configured duration (prevents a flash of animation).
            this.container.style.setProperty( '--ddmm-transition-duration', '0ms' );

            for ( let i = 1; i < ancestorPanels.length; i++ ) {
                const prev = ancestorPanels[ i - 1 ];
                const curr = ancestorPanels[ i ];
                prev.classList.remove( 'ddmm-panel--active' );
                prev.classList.add( 'ddmm-panel--exited-left' );
                prev.setAttribute( 'aria-hidden', 'true' );
                curr.classList.remove( 'ddmm-panel--exited-left' ); // safety
                curr.classList.add( 'ddmm-panel--active' );
                curr.setAttribute( 'aria-hidden', 'false' );
                this.history.push( prev.dataset.panelId );
            }

            // Restore configured duration on the second rAF (Open Question 2 recommendation).
            requestAnimationFrame( () => {
                requestAnimationFrame( () => {
                    this.container.style.removeProperty( '--ddmm-transition-duration' );
                } );
            } );
        }

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
