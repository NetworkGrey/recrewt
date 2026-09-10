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
    if ( is_page( array( 'profile-setup', 'account' ) ) || um_is_core_page( 'user' ) ) {
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

    // "Get Started" / "Join Beta" lead modal — loaded site-wide since these
    // buttons can appear on any page; harmless (no-op) when none are present.
    //
    // Inlined rather than enqueued from separate .js/.css files: this site's
    // deploy process has no way to add new files to the theme, only edit
    // existing ones, so registering an empty handle and attaching the code
    // via wp_add_inline_style()/wp_add_inline_script() is the only path
    // that's actually deployable here.
    wp_register_style( 'recrewt-lead-modal', false, array(), '1.0.0' );
    wp_enqueue_style( 'recrewt-lead-modal' );
    wp_add_inline_style( 'recrewt-lead-modal', recrewt_lead_modal_css() );

    wp_register_script( 'recrewt-lead-modal', false, array(), '1.0.0', true );
    wp_enqueue_script( 'recrewt-lead-modal' );
    wp_localize_script( 'recrewt-lead-modal', 'rcLeadModal', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'rc_lead_nonce' ),
    ) );
    wp_add_inline_script( 'recrewt-lead-modal', recrewt_lead_modal_js() );
}
add_action( 'wp_enqueue_scripts', 'recrewt_enqueue_scripts' );


/**
 * CSS for the shared "Get Started" / "Join Beta" lead-capture modal.
 * Inlined via wp_add_inline_style() -- see recrewt_enqueue_scripts() above.
 */
function recrewt_lead_modal_css() {
    return <<<'CSS'
.rc-lead-modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba( 0, 0, 0, 0.6 );
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 100000;
	padding: 20px;
}

.rc-lead-modal-overlay[hidden] {
	display: none;
}

.rc-lead-modal {
	background: #fff;
	border-radius: 8px;
	max-width: 420px;
	width: 100%;
	padding: 32px;
	position: relative;
	box-shadow: 0 10px 40px rgba( 0, 0, 0, 0.25 );
}

.rc-lead-modal-close {
	position: absolute;
	top: 12px;
	right: 16px;
	background: none;
	border: none;
	font-size: 28px;
	line-height: 1;
	cursor: pointer;
	color: #666;
}

.rc-lead-modal h2 {
	margin: 0 0 20px;
	font-size: 22px;
}

.rc-lead-form {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.rc-lead-form label {
	font-size: 13px;
	font-weight: 600;
	margin-top: 10px;
}

.rc-lead-form input[type="text"],
.rc-lead-form input[type="email"],
.rc-lead-form select {
	padding: 10px 12px;
	border: 1px solid #ccc;
	border-radius: 4px;
	font-size: 15px;
	width: 100%;
	box-sizing: border-box;
}

.rc-lead-hp-wrap {
	position: absolute;
	left: -9999px;
	width: 1px;
	height: 1px;
	overflow: hidden;
}

.rc-lead-submit {
	margin-top: 18px;
	background: #6a2ecf;
	color: #fff;
	border: none;
	border-radius: 4px;
	padding: 12px 16px;
	font-size: 15px;
	font-weight: 600;
	cursor: pointer;
}

.rc-lead-submit:disabled {
	opacity: 0.6;
	cursor: default;
}

.rc-lead-message {
	margin: 10px 0 0;
	font-size: 14px;
	min-height: 18px;
}

.rc-lead-message--success {
	color: #1a7f37;
}

.rc-lead-message--error {
	color: #c53030;
}
CSS;
}


/**
 * JS for the shared "Get Started" / "Join Beta" lead-capture modal.
 * Inlined via wp_add_inline_script() -- see recrewt_enqueue_scripts() above.
 * Relies on the rcLeadModal object (ajaxUrl, nonce) localized alongside it.
 */
function recrewt_lead_modal_js() {
    return <<<'JS'
( function () {
	'use strict';

	if ( typeof rcLeadModal === 'undefined' ) {
		return;
	}

	var ROLE_OPTIONS = [ 'Talent', 'Crew', 'Casting Agent', 'Enterprise' ];
	var modalEl = null;
	var currentSource = '';

	function buildModal() {
		var overlay = document.createElement( 'div' );
		overlay.className = 'rc-lead-modal-overlay';
		overlay.setAttribute( 'hidden', '' );

		var optionsHtml = '<option value="">I am a...</option>';
		ROLE_OPTIONS.forEach( function ( role ) {
			optionsHtml += '<option value="' + role + '">' + role + '</option>';
		} );

		overlay.innerHTML =
			'<div class="rc-lead-modal" role="dialog" aria-modal="true" aria-labelledby="rc-lead-modal-title">' +
				'<button type="button" class="rc-lead-modal-close" aria-label="Close">&times;</button>' +
				'<h2 id="rc-lead-modal-title">Join the Beta</h2>' +
				'<form class="rc-lead-form" novalidate>' +
					'<label for="rc-lead-name">Name</label>' +
					'<input type="text" id="rc-lead-name" name="name" required>' +

					'<label for="rc-lead-email">Email</label>' +
					'<input type="email" id="rc-lead-email" name="email" required>' +

					'<label for="rc-lead-role">I am a...</label>' +
					'<select id="rc-lead-role" name="role" required>' + optionsHtml + '</select>' +

					'<div class="rc-lead-hp-wrap" aria-hidden="true">' +
						'<label for="rc-lead-hp">Leave this field blank</label>' +
						'<input type="text" id="rc-lead-hp" name="rc_lead_hp" tabindex="-1" autocomplete="off">' +
					'</div>' +

					'<button type="submit" class="rc-lead-submit">Submit</button>' +
					'<p class="rc-lead-message" role="status" aria-live="polite"></p>' +
				'</form>' +
			'</div>';

		document.body.appendChild( overlay );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				closeModal();
			}
		} );
		overlay.querySelector( '.rc-lead-modal-close' ).addEventListener( 'click', closeModal );
		overlay.querySelector( '.rc-lead-form' ).addEventListener( 'submit', handleSubmit );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! overlay.hasAttribute( 'hidden' ) ) {
				closeModal();
			}
		} );

		return overlay;
	}

	function openModal( source ) {
		if ( ! modalEl ) {
			modalEl = buildModal();
		}
		currentSource = source;

		var form = modalEl.querySelector( '.rc-lead-form' );
		form.reset();
		form.querySelectorAll( 'input, select, button' ).forEach( function ( el ) {
			el.disabled = false;
		} );
		var message = modalEl.querySelector( '.rc-lead-message' );
		message.textContent = '';
		message.className = 'rc-lead-message';

		modalEl.removeAttribute( 'hidden' );
		modalEl.querySelector( '#rc-lead-name' ).focus();
	}

	function closeModal() {
		if ( modalEl ) {
			modalEl.setAttribute( 'hidden', '' );
		}
	}

	function isValidEmail( email ) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
	}

	function handleSubmit( e ) {
		e.preventDefault();

		var form = e.target;
		var message = form.querySelector( '.rc-lead-message' );
		var name = form.querySelector( '#rc-lead-name' ).value.trim();
		var email = form.querySelector( '#rc-lead-email' ).value.trim();
		var role = form.querySelector( '#rc-lead-role' ).value;
		var honeypot = form.querySelector( '#rc-lead-hp' ).value;

		message.className = 'rc-lead-message';
		message.textContent = '';

		if ( ! name || ! email || ! role ) {
			message.textContent = 'Please fill in all fields.';
			message.className = 'rc-lead-message rc-lead-message--error';
			return;
		}
		if ( ! isValidEmail( email ) ) {
			message.textContent = 'Please enter a valid email address.';
			message.className = 'rc-lead-message rc-lead-message--error';
			return;
		}

		var submitBtn = form.querySelector( '.rc-lead-submit' );
		submitBtn.disabled = true;

		var body = new URLSearchParams();
		body.append( 'action', 'rc_submit_lead' );
		body.append( 'nonce', rcLeadModal.nonce );
		body.append( 'name', name );
		body.append( 'email', email );
		body.append( 'role', role );
		body.append( 'source', currentSource );
		body.append( 'rc_lead_hp', honeypot );

		fetch( rcLeadModal.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} )
			.then( function ( response ) { return response.json(); } )
			.then( function ( json ) {
				submitBtn.disabled = false;
				if ( json.success ) {
					form.querySelectorAll( 'input, select, button' ).forEach( function ( el ) {
						el.disabled = true;
					} );
					message.textContent = ( json.data && json.data.message ) || "Thanks! We'll be in touch soon.";
					message.className = 'rc-lead-message rc-lead-message--success';
				} else {
					message.textContent = ( json.data && json.data.message ) || 'Something went wrong. Please try again later.';
					message.className = 'rc-lead-message rc-lead-message--error';
				}
			} )
			.catch( function () {
				submitBtn.disabled = false;
				message.textContent = 'Something went wrong. Please try again later.';
				message.className = 'rc-lead-message rc-lead-message--error';
			} );
	}

	function init() {
		document.querySelectorAll( '.rc-cta-lead' ).forEach( function ( wrapper ) {
			var trigger = wrapper.querySelector( 'a, button' ) || wrapper;
			trigger.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var label = trigger.textContent.trim();
				var source = window.location.pathname + ' — "' + label + '"';
				openModal( source );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
JS;
}


/* ============================================================
   2. Ultimate Member — profile setup completion
   ============================================================ */

/**
 * When a talent user's profile is saved, mark setup as complete and
 * send them to the dashboard. Registration's own redirect to
 * /profile-setup is handled separately by UM's per-role "URL redirect
 * after email activation" setting, so this only needs to handle the
 * save itself.
 *
 * Hooked to um_after_user_updated, not um_after_user_account_updated —
 * the latter only fires for UM's Account-settings form (includes/core/
 * class-account.php), never for a Profile-type form save like this
 * one. um_after_user_updated is the hook UM's own profile-save handler
 * (includes/core/um-actions-profile.php) actually fires, confirmed
 * against UM 2.13.0 source. Signature: ( $user_id, $args, $to_update ).
 *
 * Role is read via get_userdata(), not um_user( $user_id, 'role' ) —
 * um_user()'s real signature is um_user( $data, $attrs = null ); it has
 * no user-ID parameter and only ever reads whichever user UM last
 * fetched via um_fetch_user(), so passing a user ID as its first arg
 * silently returns false. Confirmed against UM 2.13.0 source
 * (includes/um-short-functions.php).
 *
 * @param int $user_id The user whose profile was just saved.
 */
function recrewt_um_profile_setup_done( $user_id ) {
    $user = get_userdata( $user_id );
    $role = $user && ! empty( $user->roles ) ? $user->roles[0] : '';

    if ( $role === 'talent' ) {
        update_user_meta( $user_id, 'rc_profile_setup_complete', 1 );
        $dashboard = get_permalink( get_page_by_path( 'dashboard' ) );
        if ( $dashboard ) {
            exit( wp_redirect( esc_url( $dashboard ) ) );
        }
    }
}
add_action( 'um_after_user_updated', 'recrewt_um_profile_setup_done' );


/* ============================================================
   3. Ultimate Member — role-based login redirect
   ============================================================ */

/**
 * Send users to the right place after login based on their role.
 *
 * Role is read via get_userdata(), not um_user( $user_id, 'role' ) —
 * see the note on recrewt_um_profile_setup_done() above for why.
 *
 * @param string $redirect_to The default redirect URL.
 * @param int    $user_id     The user being logged in.
 * @return string             Modified redirect URL.
 */
function recrewt_um_login_redirect( $redirect_to, $user_id ) {
    $user = get_userdata( $user_id );
    $role = $user && ! empty( $user->roles ) ? $user->roles[0] : '';

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
   4. Utilities
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
