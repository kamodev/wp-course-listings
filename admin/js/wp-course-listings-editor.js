/**
 * Block editor: hide the Course Programs panel. Courses are assigned to a
 * program with the Program field in the Course Details box instead.
 */
( function ( wp ) {
	'use strict';

	var panel = 'taxonomy-panel-course_program';

	wp.domReady( function () {
		// WordPress 6.5+ keeps panels in core/editor; earlier versions in core/edit-post.
		var editor = wp.data.dispatch( 'core/editor' );

		if ( editor && editor.removeEditorPanel ) {
			editor.removeEditorPanel( panel );
		} else {
			wp.data.dispatch( 'core/edit-post' ).removeEditorPanel( panel );
		}
	} );
}( window.wp ) );
