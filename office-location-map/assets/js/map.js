/**
 * Office map marker → info modal behavior.
 *
 * Vanilla JS, no jQuery dependency. Delegated listeners so it works for
 * any number of [office_map] shortcodes on one page, including markers
 * added after this script runs. Scoped entirely to `.olm-marker` /
 * `.olm-modal` markup so it can't collide with any other plugin's modals.
 *
 * Click/tap a marker to open its modal; a native <button> also picks up
 * Enter/Space activation for free. Close via the close button, the
 * overlay, Escape, or by opening a different marker.
 *
 * @package OfficeLocationMap
 */
( function () {
	'use strict';

	if ( window.olmMapInitialized ) {
		return;
	}
	window.olmMapInitialized = true;

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
		// Force layout before adding the class so the CSS transition runs.
		// eslint-disable-next-line no-unused-expressions
		modal.offsetHeight;
		modal.classList.add( 'is-open' );
		document.body.classList.add( 'olm-modal-open' );

		var marker = document.querySelector( '[data-olm-marker-for="' + modal.id + '"]' );
		if ( marker ) {
			marker.classList.add( 'is-active' );
		}

		var dialog = modal.querySelector( '.olm-modal__dialog' );
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
		document.body.classList.remove( 'olm-modal-open' );
		document.removeEventListener( 'keydown', onKeydown, true );

		var marker = document.querySelector( '[data-olm-marker-for="' + modal.id + '"]' );
		if ( marker ) {
			marker.classList.remove( 'is-active' );
		}

		if ( activeModal === modal ) {
			activeModal = null;
		}

		if ( lastFocused && typeof lastFocused.focus === 'function' && document.body.contains( lastFocused ) ) {
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
		var marker = event.target.closest( '.olm-marker' );
		if ( marker ) {
			event.preventDefault();
			var modal = getModal( marker.getAttribute( 'data-olm-marker-for' ) );
			if ( modal ) {
				if ( activeModal === modal ) {
					closeModal( modal );
				} else {
					openModal( modal, marker );
				}
			}
			return;
		}

		var closer = event.target.closest( '[data-olm-modal-close]' );
		if ( closer ) {
			var parentModal = closer.closest( '.olm-modal' );
			if ( parentModal ) {
				event.preventDefault();
				closeModal( parentModal );
			}
		}
	} );
} )();
