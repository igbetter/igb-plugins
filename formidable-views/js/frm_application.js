( function() {
	/* globals wp */

	if ( 'undefined' === typeof wp ) {
		return;
	}

	const __ = wp.i18n.__;

	addFilter( 'frm_add_application_item_options', addViewItemOption );

	function addViewItemOption( options ) {
		const viewOption = { text: __( 'view', 'formidable-views' ), type: 'view' };
		options.splice( 1, 0, viewOption );
		return options;
	}

	function addFilter( hookName, callback ) {
		wp.hooks.addFilter( hookName, 'formidable', callback );
	}
}() );
