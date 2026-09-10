<?php
/**
 * recrewt-core: um-hooks.php
 * Ultimate Member action and filter hooks for reCREWt-specific behaviour.
 *
 * Keeps UM customisations out of the theme's functions.php.
 * Theme functions.php handles redirects; this file handles data logic.
 */

defined( 'ABSPATH' ) || exit;


/* ============================================================
   Profile field visibility — role-based restrictions
   ============================================================ */

/**
 * Restrict the date_of_birth and ethnicity fields so they are only visible to
 * casting_pro, production, and admin roles — never the public or talent peers.
 *
 * UM calls this filter for each field when building a profile view. UM's own
 * um_can_view_field() only ever passes two args — $can_view and the full
 * field-config array ($data, keyed by 'metakey' etc.) — not a bare $key and
 * $profile_id as originally assumed here. That mismatch caused a fatal
 * ArgumentCountError on every UM profile page. Signature confirmed against
 * UM 2.13.0 source (includes/um-short-functions.php) before this fix.
 *
 * @param bool  $can_view Whether the current viewer can see this field.
 * @param array $data     The field config array, keyed by 'metakey' etc.
 * @return bool
 */
function recrewt_um_can_view_field( $can_view, $data ) {
    $restricted_fields = array( 'date_of_birth', 'ethnicity' );

    $key = isset( $data['metakey'] ) ? $data['metakey'] : '';

    if ( ! in_array( $key, $restricted_fields, true ) ) {
        return $can_view;
    }

    $profile_id = function_exists( 'um_profile_id' ) ? um_profile_id() : 0;

    // Always allow the profile owner and admins
    if ( current_user_can( 'administrator' ) || ( $profile_id && get_current_user_id() === (int) $profile_id ) ) {
        return true;
    }

    // Allow casting_pro and production roles
    $viewer     = wp_get_current_user();
    $allowed    = array( 'casting_pro', 'production' );
    $user_roles = (array) $viewer->roles;

    if ( array_intersect( $allowed, $user_roles ) ) {
        return true;
    }

    return false;
}
add_filter( 'um_can_view_field', 'recrewt_um_can_view_field', 10, 2 );


/* ============================================================
   Sanitise profile fields on save
   ============================================================ */

/**
 * Sanitise the bio_short field to strip HTML and enforce max length.
 * UM fires 'um_user_after_updating_profile' after a profile save.
 *
 * Signature confirmed against UM 2.13.0 source
 * (includes/core/um-actions-profile.php): the hook actually fires as
 * do_action( 'um_user_after_updating_profile', $to_update, $user_id, $args ) —
 * three args, not the single $user_id originally assumed here. That mismatch
 * meant this callback was silently never receiving a usable $user_id
 * (WordPress passed $to_update, an array, into the $user_id parameter slot).
 *
 * @param array $to_update Submitted form data (unused here).
 * @param int   $user_id   The user whose profile was just saved.
 * @param array $args      UM form args (unused here).
 */
function recrewt_sanitise_bio_on_save( $to_update, $user_id, $args ) {
    $bio = get_user_meta( $user_id, 'bio_short', true );
    if ( ! empty( $bio ) ) {
        $bio = wp_strip_all_tags( $bio );
        $bio = mb_substr( $bio, 0, 300 );
        update_user_meta( $user_id, 'bio_short', $bio );
    }
}
add_action( 'um_user_after_updating_profile', 'recrewt_sanitise_bio_on_save', 10, 3 );


/* ============================================================
   Directory query — exclude admin and non-talent accounts
   ============================================================ */

/**
 * Modify the UM directory query to only show talent-role users.
 * Prevents admin or casting pro accounts appearing in the public talent directory.
 *
 * @param array $args WP_User_Query arguments built by UM.
 * @return array Modified query args.
 */
function recrewt_um_directory_query_args( $args ) {
    // Only apply to the talent directory (UM directory form ID — update ID below)
    // To find the form ID: UM admin → Forms, hover the talent directory form, check the ID in the URL
    $talent_directory_form_id = 0; // TODO: replace 0 with actual UM form ID after Elouise creates it

    if ( isset( $args['um_form_id'] ) && (int) $args['um_form_id'] === $talent_directory_form_id ) {
        $args['role__in'] = array( 'talent' );
    }

    return $args;
}
add_filter( 'um_query_args_filter', 'recrewt_um_directory_query_args' );
