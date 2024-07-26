<script>
( function() {
	'use strict';

	var frmElements, frmMinWidth, frmElementWidth;

	function frmGetElements() {
		if ( frmElements ) {
			return frmElements;
		}
		return document.querySelectorAll( '.frm_likert' );
	}

	function frmHandleResponsiveClass() {
		frmGetElements().forEach( function( element ) {
			frmMinWidth     = element.getAttribute( 'data-min-width' );
			frmElementWidth = element.clientWidth;

			if ( frmMinWidth > frmElementWidth || screen.width < 601 ) {
				element.classList.add( 'frm_likert--mobile' );
			} else {
				element.classList.remove( 'frm_likert--mobile' );
			}
		});
	}

	window.addEventListener( 'load', frmHandleResponsiveClass );
	window.addEventListener( 'resize', frmHandleResponsiveClass );
	document.addEventListener( 'frmShowField', frmHandleResponsiveClass );
	document.addEventListener( 'frmBeforeToggleSection', frmHandleResponsiveClass );
}() );
</script>
