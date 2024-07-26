( function() {
	wp.hooks.addAction(
		'frm_after_authorize',
		'formidable',
		function( msg ) {
			if ( msg.success ) {
				handleAfterLicenseAuthorizeSuccess();
			}
		}
	);

	function handleAfterLicenseAuthorizeSuccess() {
		const formData = new FormData();
		window.frmDom.ajax.doJsonPost( 'update_stylesheet', formData );
	}

	frmDom.util.documentOn( 'change', 'input[name="frm_menu_icon"]', ( e ) => {
		document.getElementById( 'frm_hide_dashboard_videos_wrapper' )?.classList.toggle( 'frm_hidden', ! e.target.value );
	});
}() );
