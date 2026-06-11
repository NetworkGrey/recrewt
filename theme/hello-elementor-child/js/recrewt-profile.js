/**
 * recrewt-profile.js
 * Profile page interactions — Sprint 1 + Sprint 2
 *
 * Loaded on: /profile-setup, /account, and UM profile pages
 * Dependencies: jQuery (loaded by WP)
 *
 * Current scope (Sprint 1):
 *  - Character counter on the bio_short textarea
 *  - Smooth section scroll on the guided setup form
 *
 * Future scope (Sprint 2):
 *  - Gallery image preview before upload
 *  - Showreel URL validation (YouTube / Vimeo format check)
 *  - Profile completion percentage indicator
 */

( function ( $ ) {
    'use strict';

    /* --------------------------------------------------------
       Bio character counter
       Attaches to the UM textarea field with meta key bio_short
       -------------------------------------------------------- */

    function rcInitBioCounter() {
        // UM renders textareas with a name attribute matching the meta key
        var $bio = $( 'textarea[name="bio_short"]' );
        if ( ! $bio.length ) return;

        var maxLength  = 300;
        var $counter   = $( '<span class="rc-bio-counter rc-text-muted"></span>' );

        $bio.after( $counter );

        function updateCounter() {
            var remaining = maxLength - $bio.val().length;
            $counter.text( remaining + ' characters remaining' );
            $counter.toggleClass( 'rc-text-danger', remaining < 20 );
        }

        $bio.on( 'input keyup', updateCounter );
        updateCounter(); // initialise on load
    }


    /* --------------------------------------------------------
       Form section headings — smooth scroll when user
       clicks a section label in the guided setup form
       -------------------------------------------------------- */

    function rcInitSectionScroll() {
        $( '.um-field-type-divider' ).each( function () {
            $( this ).css( 'cursor', 'default' );
        } );
    }


    /* --------------------------------------------------------
       Profile completion indicator (Sprint 2 placeholder)
       -------------------------------------------------------- */

    function rcProfileCompletion() {
        // Sprint 2: calculate and display profile completeness %
        // Fields to check: full_name, talent_categories, city, profile_photo,
        //                  bio_short, height_cm, languages
        // Each present field adds weight toward 100%
        // Uncomment and implement in Sprint 2
    }


    /* --------------------------------------------------------
       Init
       -------------------------------------------------------- */

    $( document ).ready( function () {
        rcInitBioCounter();
        rcInitSectionScroll();
    } );

    // Re-init after UM AJAX form reload (UM fires this event after dynamic updates)
    $( document ).on( 'um_after_form_reload', function () {
        rcInitBioCounter();
    } );

} )( jQuery );
