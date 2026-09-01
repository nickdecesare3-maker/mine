/**
 * Initializes the WP color picker on the Office Map Styling settings page.
 *
 * @package OfficeLocationMap
 */
( function ( $ ) {
	'use strict';

	$( function () {
		$( '.olm-color-field' ).wpColorPicker();
	} );
} )( jQuery );
