<?php
/**
 * reCREWt child theme functions
 *
 * Responsibilities:
 *  - Enqueue child theme stylesheet
 *  - Enqueue custom JS files
 *  - Ultimate Member post-registration redirect
 *  - Ultimate Member role-based redirects
 *  - Helper utilities
 *
 * Do not add business logic here. Business logic lives in /plugins/recrewt-core/.
 */

defined( 'ABSPATH' ) || exit;


/* ============================================================
   1. Enqueue styles and scripts
   ============================================================ */

/**
 * Enqueue child theme stylesheet after parent.
 */
function recrewt_enqueue_styles() {
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_uri(),
        array( 'hello-elementor-style' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'recrewt_enqueue_styles' );


/**
 * Enqueue custom JS files.
 * Each file is only loaded on the pages that need it.
 */
function recrewt_enqueue_scripts() {

    // Profile page interactions — loaded on profile and profile-setup pages only
    if ( is_page( array( 'profile-setup', 'account' ) ) || um_is_profile_page() ) {
        wp_enqueue_script(
            'recrewt-profile',
            get_stylesheet_directory_uri() . '/js/recrewt-profile.js',
            array( 'jquery' ),
            '1.0.0',
            true // load in footer
        );
    }

    // Swipe / discovery — loaded on the discover page only (Sprint 3)
    // Uncomment when Sprint 3 begins:
    // if ( is_page( 'discover' ) ) {
    //     wp_enqueue_script(
    //         'recrewt-swipe',
    //         get_stylesheet_directory_uri() . '/js/recrewt-swipe.js',
    //         array( 'jquery' ),
    //         '1.0.0',
    //         true
    //     );
    //     wp_localize_script( 'recrewt-swipe', 'rcSwipe', array(
    //         'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    //         'nonce'   => wp_create_nonce( 'rc_swipe_nonce' ),
    //         'userId'  => get_current_user_id(),
    //     ) );
    // }
}
add_action( 'wp_enqueue_scripts', 'recrewt_enqueue_scripts' );


/* ============================================================
   2. Ultimate Member — post-registration redirect
   ============================================================ */

/**
 * Redirect newly registered talent users to the profile setup page
 * instead of the default UM account page.
 *
 * @param int $user_id The newly registered user's ID.
 */
function recrewt_um_after_register_redirect( $user_id ) {
    // Only redirect talent role — other roles may have different flows
    if ( um_user( $user_id, 'role' ) === 'talent' ) {
        // Store a flag so we know this is a first-time setup visit
        update_user_meta( $user_id, 'rc_profile_setup_complete', 0 );

        // Redirect to profile setup page
        // Update the page slug below if you use a different slug in WP
        $setup_page = get_permalink( get_page_by_path( 'profile-setup' ) );
        if ( $setup_page ) {
            exit( wp_redirect( esc_url( $setup_page ) ) );
        }
    }
}
add_action( 'um_after_user_account_updated', 'recrewt_um_after_register_redirect' );

// Note: UM uses 'um_registration_complete' for registration, not account update.
// The correct hook depends on your UM version. If the above does not fire,
// try 'um_registration_complete' with the same callback signature.


/* ============================================================
   3. Ultimate Member — role-based login redirect
   ============================================================ */

/**
 * Send users to the right place after login based on their role.
 *
 * @param string $redirect_to The default redirect URL.
 * @param int    $user_id     The user being logged in.
 * @return string             Modified redirect URL.
 */
function recrewt_um_login_redirect( $redirect_to, $user_id ) {
    $role = um_user( $user_id, 'role' );

    switch ( $role ) {
        case 'talent':
            // If profile setup not yet complete, send back to setup
            $setup_complete = get_user_meta( $user_id, 'rc_profile_setup_complete', true );
            if ( ! $setup_complete ) {
                $setup_page = get_permalink( get_page_by_path( 'profile-setup' ) );
                return $setup_page ?: $redirect_to;
            }
            // Otherwise send to dashboard
            return get_permalink( get_page_by_path( 'dashboard' ) ) ?: $redirect_to;

        case 'casting_pro':
        case 'production':
            return get_permalink( get_page_by_path( 'dashboard' ) ) ?: $redirect_to;

        case 'administrator':
            return admin_url();

        default:
            return $redirect_to;
    }
}
add_filter( 'um_login_redirect_url', 'recrewt_um_login_redirect', 10, 2 );


/* ============================================================
   4. Mark profile setup as complete
   ============================================================ */

/**
 * When a talent user saves their profile for the first time,
 * mark the setup as complete so they are not redirected back on next login.
 *
 * @param int $user_id The user who saved their profile.
 */
function recrewt_mark_profile_setup_complete( $user_id ) {
    if ( um_user( $user_id, 'role' ) === 'talent' ) {
        update_user_meta( $user_id, 'rc_profile_setup_complete', 1 );
    }
}
add_action( 'um_after_user_account_updated', 'recrewt_mark_profile_setup_complete', 20 );
// Priority 20 so it fires after the redirect check above (priority 10)


/* ============================================================
   5. Utilities
   ============================================================ */

/**
 * Safe helper to get UM user meta with a fallback value.
 *
 * @param int    $user_id  WP user ID.
 * @param string $key      Meta key.
 * @param mixed  $fallback Value to return if meta is empty.
 * @return mixed
 */
function recrewt_get_user_meta( $user_id, $key, $fallback = '' ) {
    $value = get_user_meta( $user_id, $key, true );
    return ( $value !== '' && $value !== false ) ? $value : $fallback;
}


/**
 * Derive an age range string from a stored date of birth.
 * Used on public profile views — never expose the raw DOB publicly.
 *
 * @param string $dob Date string in Y-m-d format.
 * @return string     Age range string, e.g. "25-30", or empty string if invalid.
 */
function recrewt_age_range_from_dob( $dob ) {
    if ( empty( $dob ) ) {
        return '';
    }
    try {
        $birth = new DateTime( $dob );
        $now   = new DateTime();
        $age   = (int) $now->diff( $birth )->y;

        // Round down to nearest 5-year bracket
        $lower = (int) floor( $age / 5 ) * 5;
        $upper = $lower + 4;

        return $lower . '-' . $upper;
    } catch ( Exception $e ) {
        return '';
    }
}
