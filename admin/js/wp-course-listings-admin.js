/**
 * Media picker for the Open Graph image field on the course edit screen.
 */
( function ( $, wp ) {
	'use strict';

	var __ = wp.i18n.__;
	var frame;

	$( function () {
		$( '#wcl_og_image_upload' ).on( 'click', function ( event ) {
			event.preventDefault();

			if ( ! frame ) {
				frame = wp.media( {
					title: __( 'Select Media', 'wp-course-listings' ),
					multiple: false,
					library: {
						type: 'image'
					}
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					$( '#wcl_og_image' ).val( attachment.url );
				} );
			}

			frame.open();
		} );
	} );
}( jQuery, window.wp ) );
