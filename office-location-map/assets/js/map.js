/**
 * Office map marker → info modal behavior.
 *
 * Vanilla JS, no jQuery dependency. Delegated listeners so it works for
 * any number of [office_map] shortcodes on one page, including markers
 * added after this script runs. Scoped entirely to `.olm-marker` /
 * `.olm-modal` markup so it can't collide with any other plugin's modals.
 *
 * Opens on hover (mouseenter) or keyboard focus, per spec. A click/tap
 * "pins" the modal open (since touch devices don't have real hover) until
 * it's explicitly closed via the close button, the overlay, Escape, or by
 * opening a different marker.
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
	var CLOSE_DELAY = 200;

	var activeModal = null;
	var activeMarker = null;
	var lastFocused = null;
	var pinned = false;
	var closeTimer = null;

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

	function cancelScheduledClose() {
		if ( closeTimer ) {
			window.clearTimeout( closeTimer );
			closeTimer = null;
		}
	}

	/**
	 * @param {HTMLElement} modal
	 * @param {HTMLElement} marker  Marker button that owns this modal.
	 * @param {HTMLElement} trigger Element that opened the modal (for focus restore).
	 * @param {boolean}     pin     Whether this open should ignore hover-out auto-close.
	 */
	function openModal( modal, marker, trigger, pin ) {
		cancelScheduledClose();

		if ( activeModal && activeModal !== modal ) {
			closeModal( activeModal );
		}

		if ( activeModal === modal ) {
			pinned = pinned || pin;
			return;
		}

		lastFocused = trigger || document.activeElement;
		activeModal = modal;
		activeMarker = marker;
		pinned = !! pin;

		modal.hidden = false;
		// Force layout before adding the class so the CSS transition runs.
		// eslint-disable-next-line no-unused-expressions
		modal.offsetHeight;
		modal.classList.add( 'is-open' );
		document.body.classList.add( 'olm-modal-open' );

		if ( marker ) {
			marker.classList.add( 'is-active' );
		}

		document.addEventListener( 'keydown', onKeydown, true );
	}

	/**
	 * @param {HTMLElement} modal
	 */
	function closeModal( modal ) {
		cancelScheduledClose();

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
			activeMarker = null;
			pinned = false;
		}

		if ( lastFocused && typeof lastFocused.focus === 'function' && document.body.contains( lastFocused ) ) {
			lastFocused.focus();
		}
		lastFocused = null;
	}

	/**
	 * Close the active modal after a short delay, unless the pointer/focus
	 * has moved onto its marker or the modal itself in the meantime, or the
	 * modal was pinned open by a click/tap.
	 */
	function scheduleClose() {
		if ( ! activeModal || pinned ) {
			return;
		}

		cancelScheduledClose();
		closeTimer = window.setTimeout( function () {
			if ( activeModal ) {
				closeModal( activeModal );
			}
		}, CLOSE_DELAY );
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

	function openFromMarker( marker, pin ) {
		var modal = getModal( marker.getAttribute( 'data-olm-marker-for' ) );
		if ( modal ) {
			openModal( modal, marker, marker, pin );
		}
	}

	document.addEventListener(
		'mouseover',
		function ( event ) {
			var marker = event.target.closest( '.olm-marker' );
			if ( marker ) {
				openFromMarker( marker, false );
				return;
			}

			if ( event.target.closest( '.olm-modal__dialog' ) ) {
				cancelScheduledClose();
			}
		},
		true
	);

	document.addEventListener(
		'mouseout',
		function ( event ) {
			var leavingMarker = event.target.closest( '.olm-marker' );
			var leavingDialog = event.target.closest( '.olm-modal__dialog' );

			if ( ! leavingMarker && ! leavingDialog ) {
				return;
			}

			var related = event.relatedTarget;
			var stillInside =
				related &&
				( related.closest && ( related.closest( '.olm-marker' ) === activeMarker || related.closest( '.olm-modal__dialog' ) ) );

			if ( ! stillInside ) {
				scheduleClose();
			}
		},
		true
	);

	document.addEventListener( 'focusin', function ( event ) {
		var marker = event.target.closest( '.olm-marker' );
		if ( marker ) {
			openFromMarker( marker, false );
			return;
		}

		if ( event.target.closest( '.olm-modal__dialog' ) ) {
			cancelScheduledClose();
		}
	} );

	document.addEventListener( 'focusout', function ( event ) {
		var leavingMarker = event.target.closest( '.olm-marker' );
		var leavingDialog = event.target.closest( '.olm-modal__dialog' );

		if ( ! leavingMarker && ! leavingDialog ) {
			return;
		}

		window.setTimeout( function () {
			var focused = document.activeElement;
			var stillInside = focused && focused.closest && ( focused.closest( '.olm-marker' ) === activeMarker || focused.closest( '.olm-modal__dialog' ) );
			if ( ! stillInside ) {
				scheduleClose();
			}
		}, 0 );
	} );

	document.addEventListener( 'click', function ( event ) {
		var marker = event.target.closest( '.olm-marker' );
		if ( marker ) {
			event.preventDefault();
			openFromMarker( marker, true );
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
