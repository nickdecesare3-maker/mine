/**
 * Accessible "Read Bio" modal for Team Roster for Divi cards.
 *
 * Vanilla JS, no jQuery dependency. Delegated listeners so it works for
 * any number of cards/modals added by either the Team Member Card block
 * or the Team Grid block, including ones added after this script runs.
 * Scoped entirely to `.trd-modal` / `[data-trd-modal-open]` markup so it
 * can't collide with Divi 5's own modals or any other plugin's.
 *
 * @package TeamRosterForDivi
 */
( function () {
	'use strict';

	// Guard against this file being enqueued more than once on the same
	// page (the Team Member Card and Team Grid blocks both declare it as
	// their viewScript).
	if ( window.trdModalInitialized ) {
		return;
	}
	window.trdModalInitialized = true;

	var FOCUSABLE_SELECTOR =
		'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

	var activeModal = null;
	var lastFocused = null;

	/**
	 * @param {string} id Modal element id.
	 * @return {HTMLElement|null}
	 */
	function getModal( id ) {
		return document.getElementById( id );
	}

	/**
	 * @param {HTMLElement} modal
	 * @return {HTMLElement[]}
	 */
	function getFocusable( modal ) {
		return Array.prototype.slice.call( modal.querySelectorAll( FOCUSABLE_SELECTOR ) );
	}

	/**
	 * @param {HTMLElement} modal
	 * @param {HTMLElement} trigger Element that opened the modal (for focus restore).
	 */
	function openModal( modal, trigger ) {
		if ( activeModal ) {
			closeModal( activeModal );
		}

		lastFocused = trigger || document.activeElement;
		activeModal = modal;

		modal.hidden = false;
		modal.classList.add( 'is-open' );
		document.body.classList.add( 'trd-modal-open' );

		var dialog = modal.querySelector( '.trd-modal__dialog' );
		if ( dialog ) {
			dialog.focus();
		}

		document.addEventListener( 'keydown', onKeydown, true );
	}

	/**
	 * @param {HTMLElement} modal
	 */
	function closeModal( modal ) {
		modal.classList.remove( 'is-open' );
		modal.hidden = true;
		document.body.classList.remove( 'trd-modal-open' );
		document.removeEventListener( 'keydown', onKeydown, true );

		if ( activeModal === modal ) {
			activeModal = null;
		}

		if ( lastFocused && typeof lastFocused.focus === 'function' ) {
			lastFocused.focus();
		}
		lastFocused = null;
	}

	/**
	 * Escape closes, Tab/Shift+Tab is trapped inside the open dialog.
	 *
	 * @param {KeyboardEvent} event
	 */
	function onKeydown( event ) {
		if ( ! activeModal ) {
			return;
		}

		if ( 'Escape' === event.key || 'Esc' === event.key ) {
			event.preventDefault();
			closeModal( activeModal );
			return;
		}

		if ( 'Tab' !== event.key ) {
			return;
		}

		var focusable = getFocusable( activeModal );
		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var opener = event.target.closest( '[data-trd-modal-open]' );
		if ( opener ) {
			var modal = getModal( opener.getAttribute( 'data-trd-modal-open' ) );
			if ( modal ) {
				event.preventDefault();
				openModal( modal, opener );
			}
			return;
		}

		var closer = event.target.closest( '[data-trd-modal-close]' );
		if ( closer ) {
			var parentModal = closer.closest( '.trd-modal' );
			if ( parentModal ) {
				event.preventDefault();
				closeModal( parentModal );
			}
		}
	} );
} )();
