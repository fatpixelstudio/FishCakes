/**
 * FishCakes — settings page behavior.
 *
 * Color pickers, the script repeater and the inline/URL toggle.
 *
 * @package FishCakes
 */

( function ( $ ) {
	'use strict';

	$( function () {
		$( '.fc-color-field' ).wpColorPicker();

		/**
		 * Shows the code textarea or the URL input of a row,
		 * depending on its type select.
		 */
		function syncRowType( row ) {
			const type = row.find( '.fc-script-type' ).val();

			row.find( '.fc-script-row__code' ).toggle( 'inline' === type );
			row.find( '.fc-script-row__url' ).toggle( 'url' === type );
		}

		$( '.fc-script-row' ).each( function () {
			syncRowType( $( this ) );
		} );

		$( document ).on( 'change', '.fc-script-type', function () {
			syncRowType( $( this ).closest( '.fc-script-row' ) );
		} );

		$( document ).on( 'click', '#fc-script-add', function ( event ) {
			event.preventDefault();

			const template = document.getElementById( 'fc-script-row-template' );
			const rows = document.getElementById( 'fc-script-rows' );

			if ( ! template || ! rows ) {
				return;
			}

			// Rows are submitted with explicit numeric indices so PHP
			// keeps one row's fields together. The template carries a
			// __IDX__ placeholder; replace it with the next free index.
			const nextIndex = parseInt( rows.getAttribute( 'data-next-index' ), 10 ) || 0;
			const html = template.innerHTML.replaceAll( '__IDX__', String( nextIndex ) );

			rows.insertAdjacentHTML( 'beforeend', html );
			rows.setAttribute( 'data-next-index', String( nextIndex + 1 ) );

			const $added = $( rows ).find( '.fc-script-row' ).last();
			syncRowType( $added );
			$added.find( 'input[type="text"]' ).trigger( 'focus' );
		} );

		$( document ).on( 'click', '.fc-script-remove', function ( event ) {
			event.preventDefault();
			$( this ).closest( '.fc-script-row' ).remove();
		} );
	} );
} )( jQuery );
