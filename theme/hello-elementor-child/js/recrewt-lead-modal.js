/**
 * Shared "Get Started" / "Join Beta" lead-capture modal.
 * Intercepts clicks on any .rc-cta-lead button/link, opens one shared
 * modal, validates, and submits via AJAX to rc_submit_lead.
 */
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
		var message = modalEl.querySelector( '.rc-lead-message' );
		message.textContent = '';
		message.className = 'rc-lead-message';
		form.style.display = '';

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
