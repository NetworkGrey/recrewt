<?php
/**
 * recrewt-core: ajax-handlers.php
 * WP AJAX endpoints for reCREWt frontend features.
 *
 * All endpoints require:
 *  - A logged-in user (no nopriv handlers for write actions), except
 *    rc_submit_lead below — a public marketing form anonymous visitors
 *    must be able to submit, protected instead by nonce + honeypot.
 *  - A valid nonce checked before any data is read or written
 *
 * Endpoints registered here:
 *  - rc_save_favourite         (Sprint 3) — save a talent user to favourites
 *  - rc_remove_favourite       (Sprint 3) — remove a talent user from favourites
 *  - rc_get_discover_profiles  (Sprint 3) — fetch paginated talent profiles for the swipe stack
 *  - rc_submit_lead            — "Get Started" / "Join Beta" modal submissions, emailed to hello@recrewt.app
 */

defined( 'ABSPATH' ) || exit;


/* ============================================================
   rc_save_favourite
   ============================================================ */

add_action( 'wp_ajax_rc_save_favourite', 'recrewt_ajax_save_favourite' );

function recrewt_ajax_save_favourite() {
    // Verify nonce
    check_ajax_referer( 'rc_swipe_nonce', 'nonce' );

    $viewer_id    = get_current_user_id();
    $talent_id    = isset( $_POST['talent_user_id'] ) ? absint( $_POST['talent_user_id'] ) : 0;

    if ( ! $viewer_id || ! $talent_id ) {
        wp_send_json_error( array( 'message' => 'Invalid request.' ), 400 );
    }

    // Confirm the target user exists and is a talent
    $talent_user = get_userdata( $talent_id );
    if ( ! $talent_user || ! in_array( 'talent', (array) $talent_user->roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Target user is not a talent account.' ), 400 );
    }

    // Get current favourites array and add the new ID (deduplicated)
    $favourites   = get_user_meta( $viewer_id, 'rc_favourites', true );
    $favourites   = is_array( $favourites ) ? $favourites : array();
    $favourites[] = $talent_id;
    $favourites   = array_unique( array_map( 'absint', $favourites ) );

    update_user_meta( $viewer_id, 'rc_favourites', $favourites );

    wp_send_json_success( array( 'saved' => $talent_id ) );
}


/* ============================================================
   rc_remove_favourite
   ============================================================ */

add_action( 'wp_ajax_rc_remove_favourite', 'recrewt_ajax_remove_favourite' );

function recrewt_ajax_remove_favourite() {
    check_ajax_referer( 'rc_swipe_nonce', 'nonce' );

    $viewer_id = get_current_user_id();
    $talent_id = isset( $_POST['talent_user_id'] ) ? absint( $_POST['talent_user_id'] ) : 0;

    if ( ! $viewer_id || ! $talent_id ) {
        wp_send_json_error( array( 'message' => 'Invalid request.' ), 400 );
    }

    $favourites = get_user_meta( $viewer_id, 'rc_favourites', true );
    $favourites = is_array( $favourites ) ? $favourites : array();
    $favourites = array_filter( $favourites, function ( $id ) use ( $talent_id ) {
        return (int) $id !== $talent_id;
    } );

    update_user_meta( $viewer_id, 'rc_favourites', array_values( $favourites ) );

    wp_send_json_success( array( 'removed' => $talent_id ) );
}


/* ============================================================
   rc_get_discover_profiles
   ============================================================ */

add_action( 'wp_ajax_rc_get_discover_profiles', 'recrewt_ajax_get_discover_profiles' );

function recrewt_ajax_get_discover_profiles() {
    check_ajax_referer( 'rc_swipe_nonce', 'nonce' );

    $viewer_id = get_current_user_id();
    $offset    = isset( $_POST['offset'] )   ? absint( $_POST['offset'] )   : 0;
    $per_page  = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
    $per_page  = min( $per_page, 20 ); // cap to prevent large queries

    // Get IDs already in the viewer's favourites to exclude them
    $already_saved = get_user_meta( $viewer_id, 'rc_favourites', true );
    $already_saved = is_array( $already_saved ) ? $already_saved : array();

    // Also exclude the viewer themselves
    $exclude = array_merge( $already_saved, array( $viewer_id ) );

    $query_args = array(
        'role'         => 'talent',
        'number'       => $per_page,
        'offset'       => $offset,
        'exclude'      => $exclude,
        'orderby'      => 'registered',
        'order'        => 'DESC',
        'meta_key'     => 'rc_profile_setup_complete',
        'meta_value'   => '1', // only show users who completed basic setup
        'meta_compare' => '=',
    );

    $user_query = new WP_User_Query( $query_args );
    $users      = $user_query->get_results();

    if ( empty( $users ) ) {
        wp_send_json_success( array( 'profiles' => array() ) );
    }

    $profiles = array();

    foreach ( $users as $user ) {
        $dob       = get_user_meta( $user->ID, 'date_of_birth', true );
        $photo_id  = get_user_meta( $user->ID, 'profile_photo', true );
        $photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium_large' ) : '';

        // Fallback to UM profile photo URL if stored differently
        if ( ! $photo_url && function_exists( 'um_user' ) ) {
            um_fetch_user( $user->ID );
            $photo_url = um_user( 'profile_photo', 300 );
        }

        $profiles[] = array(
            'user_id'           => $user->ID,
            'full_name'         => get_user_meta( $user->ID, 'full_name', true ) ?: $user->display_name,
            'stage_name'        => get_user_meta( $user->ID, 'stage_name', true ),
            'talent_categories' => (array) get_user_meta( $user->ID, 'talent_categories', true ),
            'city'              => get_user_meta( $user->ID, 'city', true ),
            'province'          => get_user_meta( $user->ID, 'province', true ),
            'profile_photo'     => esc_url( $photo_url ),
            'age_range'         => recrewt_age_range_from_dob( $dob ), // from functions.php
            'gender'            => get_user_meta( $user->ID, 'talent_gender', true ),
            'height_cm'         => (int) get_user_meta( $user->ID, 'height_cm', true ),
            'languages'         => (array) get_user_meta( $user->ID, 'talent_languages', true ),
            'profile_url'       => esc_url( um_user_profile_url( $user->ID ) ),
        );
    }

    wp_send_json_success( array( 'profiles' => $profiles ) );
}


/* ============================================================
   rc_submit_lead
   ============================================================ */

add_action( 'wp_ajax_rc_submit_lead', 'recrewt_ajax_submit_lead' );
add_action( 'wp_ajax_nopriv_rc_submit_lead', 'recrewt_ajax_submit_lead' );

/**
 * Handle "Get Started" / "Join Beta" modal submissions.
 * Public form (nopriv) -- protected by nonce + honeypot, not login.
 */
function recrewt_ajax_submit_lead() {
    check_ajax_referer( 'rc_lead_nonce', 'nonce' );

    // Honeypot: real users never fill this in. Pretend success so bots
    // don't learn to look elsewhere; don't send mail or validate further.
    if ( ! empty( $_POST['rc_lead_hp'] ) ) {
        wp_send_json_success( array( 'message' => "Thanks! We'll be in touch soon." ) );
    }

    $name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $role   = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
    $source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';

    $allowed_roles = array( 'Talent', 'Crew', 'Casting Agent', 'Enterprise' );

    if ( '' === $name || '' === $email || '' === $role ) {
        wp_send_json_error( array( 'message' => 'Please fill in all fields.' ), 400 );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ), 400 );
    }

    if ( ! in_array( $role, $allowed_roles, true ) ) {
        wp_send_json_error( array( 'message' => 'Please select a valid option.' ), 400 );
    }

    $to      = 'hello@recrewt.app';
    $subject = 'New Beta signup: ' . $name;
    $body    = "New \"Get Started\" / \"Join Beta\" submission:\n\n"
        . "Name: {$name}\n"
        . "Email: {$email}\n"
        . "I am a: {$role}\n"
        . "Source: {$source}\n";

    $sent = wp_mail( $to, $subject, $body );

    if ( $sent ) {
        wp_send_json_success( array( 'message' => "Thanks! We'll be in touch soon." ) );
    } else {
        wp_send_json_error( array( 'message' => 'Something went wrong. Please try again later.' ), 500 );
    }
}
