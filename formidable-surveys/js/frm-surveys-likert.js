/**
 * @since 1.0.04
 */
( function( $ ) {
	'use strict';

	/**
	 * Gets likert error message.
	 *
	 * @param {HTMLElement} likertEl Likert element.
	 * @return {string}
	 */
	function getLikertErrorMsg( likertEl ) {
		var likertInput = likertEl.children[ likertEl.children.length - 1 ];
		return likertInput.dataset.reqmsg;
	}

	/**
	 * Gets likert error element.
	 *
	 * @param {HTMLElement} likertContainer Likert field container.
	 * @return {HTMLElement|false}
	 */
	function getLikertErrorEl( likertContainer ) {
		var errorEl = likertContainer.children[ likertContainer.children.length - 1 ];
		if ( errorEl.classList.contains( 'frm_error' ) ) {
			return errorEl;
		}
		return false;
	}

	/**
	 * Creates the error element.
	 *
	 * @param {String} errorKey Error key.
	 * @param {String} msg      Error message.
	 * @return {HTMLElement}
	 */
	function createErrorEl( errorKey, msg ) {
		var el = document.createElement( 'div' ),
			content = document.createTextNode( msg );

		el.appendChild( content );
		el.classList.add( 'frm_error' );
		el.setAttribute( 'role', 'alert' );
		el.id = 'frm_error_field_' + errorKey;

		return el;
	}

	$( document ).on( 'frmAddFieldError', function( event, $fieldCont, key, jsErrors ) {
		var rowField, rowError, likertEl, likertField, likertError;
		rowField = $fieldCont[0];

		likertEl = rowField.parentNode;
		if ( ! likertEl.classList.contains( 'frm_likert' ) ) {
			return;
		}

		// Remove error message inside likert row.
		rowError = rowField.querySelector( '.frm_error' );
		if ( rowError ) {
			rowError.remove();
		}

		likertField = rowField.parentNode.closest( '.frm_form_field' );
		likertError = getLikertErrorEl( likertField );
		if ( likertError ) {
			// Return if the likert error was added before.
			return;
		}

		likertField.appendChild( createErrorEl( key, getLikertErrorMsg( rowField.parentNode ) ) );
		likertField.classList.add( 'frm_blank_field' );
	});
}( jQuery ) );
