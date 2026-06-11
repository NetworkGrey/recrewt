/**
 * recrewt-swipe.js
 * Swipe / discovery feature — Sprint 3
 *
 * Loaded on: /discover page only
 * Dependencies: jQuery (loaded by WP)
 * Localised data: rcSwipe (ajaxUrl, nonce, userId) — see functions.php
 *
 * Behaviour:
 *  - Fetches paginated talent profiles via WP AJAX
 *  - Renders a stack of profile cards
 *  - Detects left / right swipe gestures (mouse drag + touch)
 *  - Right swipe: saves profile to current user's favourites via AJAX
 *  - Left swipe: dismisses card, loads next
 *  - Keyboard support: left arrow = dismiss, right arrow = favourite
 *
 * This file is a placeholder — implementation begins Sprint 3.
 * The structure and comments define the intended architecture.
 */

( function ( $ ) {
    'use strict';

    /* --------------------------------------------------------
       Constants
       -------------------------------------------------------- */

    var SWIPE_THRESHOLD  = 80;   // px — minimum drag distance to trigger a swipe action
    var CARD_ROTATE_MAX  = 15;   // deg — maximum card tilt at edge of swipe
    var ANIMATION_MS     = 300;  // ms — card fly-out animation duration


    /* --------------------------------------------------------
       State
       -------------------------------------------------------- */

    var state = {
        cards:        [],    // array of profile objects fetched from the server
        currentIndex: 0,     // index of the card currently on top
        isDragging:   false,
        dragStartX:   0,
        dragCurrentX: 0,
    };


    /* --------------------------------------------------------
       Fetch profiles from server
       -------------------------------------------------------- */

    /**
     * Load a batch of talent profiles via WP AJAX.
     * The AJAX handler lives in recrewt-core/includes/ajax-handlers.php.
     *
     * @param {number} offset - How many profiles to skip (for pagination).
     */
    function rcFetchProfiles( offset ) {
        $.ajax( {
            url:    rcSwipe.ajaxUrl,
            method: 'POST',
            data:   {
                action:   'rc_get_discover_profiles',
                nonce:    rcSwipe.nonce,
                offset:   offset || 0,
                per_page: 10,
            },
            success: function ( response ) {
                if ( response.success && response.data.profiles.length ) {
                    state.cards = state.cards.concat( response.data.profiles );
                    rcRenderCards();
                } else {
                    rcShowEmptyState();
                }
            },
            error: function () {
                rcShowError( 'Could not load profiles. Please try again.' );
            },
        } );
    }


    /* --------------------------------------------------------
       Render card stack
       -------------------------------------------------------- */

    /**
     * Render the top N cards into the DOM stack.
     * Only the top 3 cards are rendered at once for performance.
     */
    function rcRenderCards() {
        // Sprint 3 implementation
    }

    /**
     * Build a single card element from a profile object.
     *
     * @param {Object} profile - Profile data from the server.
     * @returns {jQuery} The card element.
     */
    function rcBuildCard( profile ) {
        // Sprint 3 implementation
        // Profile object shape (matches UM user meta keys):
        // {
        //   user_id:          int,
        //   full_name:        string,
        //   stage_name:       string,
        //   talent_categories: array,
        //   city:             string,
        //   province:         string,
        //   profile_photo:    string (URL),
        //   age_range:        string  (derived server-side, never raw DOB),
        //   gender:           string,
        //   height_cm:        int,
        //   languages:        array,
        // }
    }


    /* --------------------------------------------------------
       Drag / swipe gesture handling
       -------------------------------------------------------- */

    function rcOnDragStart( e ) {
        // Sprint 3 implementation
    }

    function rcOnDragMove( e ) {
        // Sprint 3 implementation
        // Apply transform: translateX + rotate based on drag distance
    }

    function rcOnDragEnd( e ) {
        if ( Math.abs( state.dragCurrentX - state.dragStartX ) > SWIPE_THRESHOLD ) {
            if ( state.dragCurrentX > state.dragStartX ) {
                rcSwipeRight();
            } else {
                rcSwipeLeft();
            }
        } else {
            rcSnapBack(); // not far enough — snap card back to centre
        }
    }


    /* --------------------------------------------------------
       Swipe actions
       -------------------------------------------------------- */

    /**
     * Right swipe — save to favourites and dismiss card.
     */
    function rcSwipeRight() {
        var card    = rcGetTopCard();
        var profile = state.cards[ state.currentIndex ];

        // Animate card off to the right
        rcAnimateOut( card, 'right', function () {
            rcSaveFavourite( profile.user_id );
            rcAdvance();
        } );
    }

    /**
     * Left swipe — dismiss card without saving.
     */
    function rcSwipeLeft() {
        var card = rcGetTopCard();
        rcAnimateOut( card, 'left', function () {
            rcAdvance();
        } );
    }

    /**
     * Advance to the next card. Fetch more if running low.
     */
    function rcAdvance() {
        state.currentIndex++;
        if ( state.currentIndex >= state.cards.length - 3 ) {
            // Pre-fetch next batch before we run out
            rcFetchProfiles( state.cards.length );
        }
        rcRenderCards();
    }


    /* --------------------------------------------------------
       Save favourite via AJAX
       -------------------------------------------------------- */

    /**
     * POST to the server to save a talent user ID as a favourite
     * for the currently logged-in casting pro / production user.
     *
     * @param {number} talentUserId - The talent user's WP user ID.
     */
    function rcSaveFavourite( talentUserId ) {
        $.ajax( {
            url:    rcSwipe.ajaxUrl,
            method: 'POST',
            data:   {
                action:         'rc_save_favourite',
                nonce:          rcSwipe.nonce,
                talent_user_id: talentUserId,
            },
            // Silent success — no UI feedback needed on save
            error: function () {
                // Fail silently on the swipe, retry logic can be added later
                console.warn( 'reCREWt: failed to save favourite for user ' + talentUserId );
            },
        } );
    }


    /* --------------------------------------------------------
       Keyboard support
       -------------------------------------------------------- */

    function rcInitKeyboard() {
        $( document ).on( 'keydown', function ( e ) {
            if ( e.key === 'ArrowRight' ) rcSwipeRight();
            if ( e.key === 'ArrowLeft' )  rcSwipeLeft();
        } );
    }


    /* --------------------------------------------------------
       Animation helpers
       -------------------------------------------------------- */

    function rcAnimateOut( $card, direction, callback ) {
        // Sprint 3 implementation
        // Translate off-screen left or right + slight rotation, then call callback
    }

    function rcSnapBack() {
        // Sprint 3 implementation
        // Animate card back to transform: translate(0, 0) rotate(0deg)
    }


    /* --------------------------------------------------------
       Empty state + error
       -------------------------------------------------------- */

    function rcShowEmptyState() {
        $( '.rc-swipe-stack' ).html(
            '<p class="rc-swipe-empty">You have reviewed all available talent. Check back soon.</p>'
        );
    }

    function rcShowError( message ) {
        $( '.rc-swipe-stack' ).prepend(
            '<p class="rc-swipe-error rc-text-danger">' + message + '</p>'
        );
    }


    /* --------------------------------------------------------
       Utility
       -------------------------------------------------------- */

    function rcGetTopCard() {
        return $( '.rc-swipe-card' ).first();
    }


    /* --------------------------------------------------------
       Init
       -------------------------------------------------------- */

    function rcInitSwipe() {
        if ( ! $( '.rc-swipe-stack' ).length ) return;
        rcFetchProfiles( 0 );
        rcInitKeyboard();
    }

    $( document ).ready( rcInitSwipe );

} )( jQuery );
