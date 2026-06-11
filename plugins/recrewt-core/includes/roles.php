<?php
/**
 * recrewt-core: roles.php
 * Register and configure custom user roles.
 *
 * Roles are registered on plugin activation via recrewt_register_roles().
 * They are also checked on init in case the roles were lost (e.g. after DB restore).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'recrewt_register_roles' );

/**
 * Register reCREWt custom roles if they do not already exist.
 * Safe to call multiple times — checks before adding.
 */
function recrewt_register_roles() {

    // Talent — actors, models, extras, crew
    if ( ! get_role( 'talent' ) ) {
        add_role(
            'talent',
            __( 'Talent', 'recrewt-core' ),
            array(
                'read'           => true,
                'upload_files'   => false,
                'edit_posts'     => false,
                'delete_posts'   => false,
            )
        );
    }

    // Casting professional — casting directors, agencies
    if ( ! get_role( 'casting_pro' ) ) {
        add_role(
            'casting_pro',
            __( 'Casting Professional', 'recrewt-core' ),
            array(
                'read'           => true,
                'upload_files'   => false,
                'edit_posts'     => false,
                'delete_posts'   => false,
            )
        );
    }

    // Production — production companies, directors, producers
    if ( ! get_role( 'production' ) ) {
        add_role(
            'production',
            __( 'Production', 'recrewt-core' ),
            array(
                'read'           => true,
                'upload_files'   => false,
                'edit_posts'     => false,
                'delete_posts'   => false,
            )
        );
    }
}
