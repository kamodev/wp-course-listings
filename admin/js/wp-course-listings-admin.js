/**
 * Course edit screen: type-ahead program and prerequisite fields, and the
 * media picker for the Open Graph image field.
 */
( function ( $, wp ) {
	'use strict';

	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;

	/**
	 * Search paths for each lookup source, using the core REST search endpoint.
	 */
	var sources = {
		course: { type: 'post', subtype: 'course' },
		program: { type: 'term', subtype: 'course_program' }
	};

	/**
	 * Turn a .wcl-lookup element into a type-ahead field.
	 *
	 * Selected items are list entries holding a hidden input, so the form
	 * submits their IDs without JavaScript having to sync anything else.
	 *
	 * @param {jQuery} $root Lookup container.
	 */
	function initLookup( $root ) {
		var source = sources[ $root.data( 'source' ) ];
		var name = $root.data( 'name' );
		var multiple = 1 === $root.data( 'multiple' );
		var exclude = String( $root.data( 'exclude' ) || '' );
		var $list = $root.find( '.wcl-lookup-selected' );
		var $input = $root.find( '.wcl-lookup-input' );

		function selectedIds() {
			return $list.find( 'input' ).map( function () {
				return this.value;
			} ).get();
		}

		function addItem( id, label ) {
			if ( ! multiple ) {
				$list.empty();
			}

			var $item = $( '<li class="wcl-lookup-item"></li>' );
			$( '<span class="wcl-lookup-label"></span>' ).text( label ).appendTo( $item );
			$( '<button type="button" class="button-link wcl-lookup-remove"><span aria-hidden="true">&times;</span></button>' )
				/* translators: %s: name of the selected course or program. */
				.attr( 'aria-label', sprintf( __( 'Remove %s', 'wp-course-listings' ), label ) )
				.appendTo( $item );
			$( '<input type="hidden">' ).attr( { name: name, value: id } ).appendTo( $item );
			$list.append( $item );

			/* translators: %s: name of the selected course or program. */
			wp.a11y.speak( sprintf( __( '%s added.', 'wp-course-listings' ), label ) );
		}

		$list.on( 'click', '.wcl-lookup-remove', function () {
			var $item = $( this ).closest( '.wcl-lookup-item' );
			var label = $item.find( '.wcl-lookup-label' ).text();

			$item.remove();
			$input.trigger( 'focus' );
			/* translators: %s: name of the removed course or program. */
			wp.a11y.speak( sprintf( __( '%s removed.', 'wp-course-listings' ), label ) );
		} );

		$input.autocomplete( {
			minLength: 0,
			delay: 250,
			classes: {
				'ui-autocomplete': 'wcl-lookup-menu'
			},
			source: function ( request, response ) {
				var path = wp.url.addQueryArgs( '/wp/v2/search', {
					search: request.term,
					type: source.type,
					subtype: source.subtype,
					per_page: 20
				} );

				wp.apiFetch( { path: path } ).then( function ( results ) {
					var taken = selectedIds().concat( exclude ? [ exclude ] : [] );

					response( results.filter( function ( result ) {
						return -1 === taken.indexOf( String( result.id ) );
					} ).map( function ( result ) {
						return {
							label: wp.htmlEntities.decodeEntities( result.title ),
							value: result.id
						};
					} ) );
				} ).catch( function () {
					response( [] );
				} );
			},
			focus: function ( event ) {
				// Keep the typed text instead of replacing it with the item ID.
				event.preventDefault();
			},
			select: function ( event, ui ) {
				event.preventDefault();
				addItem( ui.item.value, ui.item.label );
				$input.val( '' );
			},
			messages: {
				noResults: __( 'No matches found.', 'wp-course-listings' ),
				results: function ( count ) {
					/* translators: %d: number of search results. */
					return sprintf( __( '%d results found. Use up and down arrow keys to navigate.', 'wp-course-listings' ), count );
				}
			}
		} ).on( 'focus', function () {
			$input.autocomplete( 'search', $input.val() );
		} );
	}

	/**
	 * Media picker for the Open Graph image field.
	 */
	function initMediaPicker() {
		var frame;

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
	}

	$( function () {
		$( '.wcl-lookup' ).each( function () {
			initLookup( $( this ) );
		} );

		if ( wp.media ) {
			initMediaPicker();
		}
	} );
}( jQuery, window.wp ) );
