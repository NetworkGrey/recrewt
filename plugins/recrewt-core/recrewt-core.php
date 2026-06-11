<?php
/**
 * Plugin Name:  reCREWt Core
 * Plugin URI:   https://recrewt.com
 * Description:  Core backend logic for reCREWt. Handles custom AJAX endpoints,
 *               Ultimate Member hooks, and role-specific capabilities.
 *               This plugin must be active at all times.
 * Version:      1.0.0
 * Author:       Network Grey
 * Author URI:   https://networkgrey.co.za
 * Text Domain:  recrewt-core
 * Requires WP:  6.0
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;


/* ============================================================
   Constants
   ============================================================ */

define( 'RECREWT_CORE_VERSION', '1.0.0' );
define( 'RECREWT_CORE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'RECREWT_CORE_URL',     plugin_dir_url( __FILE__ ) );


/* ============================================================
   Autoload includes
   ============================================================ */

$includes = array(
    'includes/roles.php',
    'includes/um-hooks.php',
    'includes/ajax-handlers.php',
);

foreach ( $includes as $file ) {
    $path = RECREWT_CORE_PATH . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}


/* ============================================================
   Activation hook
   ============================================================ */

register_activation_hook( __FILE__, 'recrewt_core_activate' );

function recrewt_core_activate() {
    // Register custom roles on activation
    recrewt_register_roles();
    // Flush rewrite rules so custom permalinks work immediately
    flush_rewrite_rules();
}


/* ============================================================
   Deactivation hook
   ============================================================ */

register_deactivation_hook( __FILE__, 'recrewt_core_deactivate' );

function recrewt_core_deactivate() {
    flush_rewrite_rules();
}
