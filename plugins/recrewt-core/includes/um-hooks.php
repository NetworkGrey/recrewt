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
 * Restrict the date_of_birth field so it is only visible to
 * casting_pro, production, and admin roles — never the public or talent peers.
 *
 * UM calls this filter for each field when building a profile view.
 *
 * @param bool   $can_view  Whether the current viewer can see this field.
 * @param string $key       The field meta key.
 * @param int    $profile_id The user ID whose profile is being viewed.
 * @return bool
 */
function recrewt_um_can_view_field( $can_view, $key, $profile_id ) {
    $restricted_fields = array( 'date_of_birth' );

    if ( ! in_array( $key, $restricted_fields, true ) ) {
        return $can_view;
    }

    // Always allow the profile owner and admins
    if ( current_user_can( 'administrator' ) || get_current_user_id() === (int) $profile_id ) {
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
add_filter( 'um_can_view_field', 'recrewt_um_can_view_field', 10, 3 );


/* ============================================================
   Sanitise profile fields on save
   ============================================================ */

/**
 * Sanitise the bio_short field to strip HTML and enforce max length.
 * UM fires 'um_user_after_updating_profile' after a profile save.
 *
 * @param int $user_id The user whose profile was just saved.
 */
function recrewt_sanitise_bio_on_save( $user_id ) {
    $bio = get_user_meta( $user_id, 'bio_short', true );
    if ( ! empty( $bio ) ) {
        $bio = wp_strip_all_tags( $bio );
        $bio = mb_substr( $bio, 0, 300 );
        update_user_meta( $user_id, 'bio_short', $bio );
    }
}
add_action( 'um_user_after_updating_profile', 'recrewt_sanitise_bio_on_save' );


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
