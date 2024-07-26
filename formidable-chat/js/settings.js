( function() {
	const chatToggle = document.getElementById( 'frm_chat_toggle' );
	const navArrowsToggle = document.getElementById( 'frm_chat_include_arrows_toggle' );

	if ( ! chatToggle || ! navArrowsToggle ) {
		return;
	}

	function syncDisabledOptions( event ) {
		const chatIsEnabled = chatToggle.checked;
		const jsValidateOption = document.getElementById( 'js_validate' );
		const saveDraftOption = document.getElementById( 'save_draft' );
		const rootlineSelect = document.getElementById( 'frm_rootline_opt' );
		const chatOptionsContainer = document.getElementById( 'frm_chat_options' );

		if ( chatIsEnabled ) {
			if ( jsValidateOption ) {
				jsValidateOption.checked = true;
				disableCheckboxOption( jsValidateOption );
			}
			if ( saveDraftOption ) {
				saveDraftOption.checked = false;
				disableCheckboxOption( saveDraftOption );
			}
			if ( rootlineSelect ) {
				rootlineSelect.value = '';
				disableCheckboxOption( rootlineSelect );
			}
			if ( 'object' === typeof event && 'change' === event.type ) {
				// turn on nav arrows when toggling on chat forms.
				navArrowsToggle.checked = true;

				// turn on start page when toggling on chat forms.
				const startPageToggle = document.getElementById( 'frm_chat_show_start_page_toggle' );
				startPageToggle.checked = true;
			}
		} else {
			if ( jsValidateOption ) {
				enableCheckboxOption( jsValidateOption );
			}
			if ( saveDraftOption ) {
				enableCheckboxOption( saveDraftOption );
			}
			if ( rootlineSelect ) {
				enableCheckboxOption( rootlineSelect );
			}
		}

		if ( chatOptionsContainer ) {
			chatOptionsContainer.classList.toggle( 'frm_hidden', ! chatIsEnabled );
		}
	}

	function disableCheckboxOption( option ) {
		option.disabled = true;
		option.parentNode.classList.add( 'frm_noallow' );
	}

	function enableCheckboxOption( option ) {
		option.disabled = false;
		option.parentNode.classList.remove( 'frm_noallow' );
	}

	chatToggle.addEventListener( 'change', syncDisabledOptions );

	syncDisabledOptions();
}() );
