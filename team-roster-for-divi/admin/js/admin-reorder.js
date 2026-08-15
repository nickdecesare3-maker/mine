/**
 * Drag-and-drop reordering for the Team Members admin list table.
 * Vanilla jQuery UI Sortable (bundled with WP core), saved via AJAX.
 *
 * @package TeamRosterForDivi
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $tbody = $( '#the-list' );

		if ( ! $tbody.length || typeof trdReorder === 'undefined' ) {
			return;
		}

		var $status = $( '<span class="trd-reorder-status" aria-live="polite"></span>' );
		$( '.wp-list-table' ).before( $status );

		$tbody.sortable( {
			items: 'tr',
			axis: 'y',
			cursor: 'move',
			handle: '.column-title, .column-trd_photo',
			helper: function ( event, row ) {
				// Keep column widths stable while dragging.
				row.children().each( function () {
					$( this ).width( $( this ).width() );
				} );
				return row;
			},
			start: function ( event, ui ) {
				ui.item.addClass( 'trd-dragging' );
			},
			stop: function ( event, ui ) {
				ui.item.removeClass( 'trd-dragging' );
				saveOrder();
			},
		} );

		function saveOrder() {
			var order = $tbody.find( 'tr' ).map( function () {
				return $( this ).attr( 'id' ).replace( 'post-', '' );
			} ).get();

			$status.text( trdReorder.i18n.saving ).addClass( 'is-saving' );

			$.post( trdReorder.ajaxUrl, {
				action: trdReorder.action,
				nonce: trdReorder.nonce,
				order: order,
			} )
				.done( function ( response ) {
					if ( response && response.success ) {
						$status.text( trdReorder.i18n.saved ).removeClass( 'is-saving' ).addClass( 'is-saved' );
						setTimeout( function () {
							$status.text( '' ).removeClass( 'is-saved' );
						}, 2000 );
					} else {
						$status.text( trdReorder.i18n.error ).removeClass( 'is-saving' ).addClass( 'is-error' );
					}
				} )
				.fail( function () {
					$status.text( trdReorder.i18n.error ).removeClass( 'is-saving' ).addClass( 'is-error' );
				} );
		}
	} );
} )( jQuery );
