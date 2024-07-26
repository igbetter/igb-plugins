( function() {
	/* globals wp */

	if ( 'undefined' === typeof wp ) {
		return;
	}

	const __ = wp.i18n.__;

	addFilter( 'frm_embed_examples', addViewEmbedExamples );
	addFilter( 'frm_embed_edit_page_url', updateEditPageUrl );

	function addViewEmbedExamples( examples, { type, objectId }) {
		if ( 'view' !== type ) {
			return examples;
		}

		examples.push(
			{
				label: __( 'WordPress shortcode', 'formidable' ),
				example: '[display-frm-data id=' + objectId + ']',
				link: 'https://formidableforms.com/knowledgebase/publish-a-form/#kb-insert-the-shortcode-manually',
				linkLabel: __( 'How to use shortcodes in WordPress', 'formidable' )
			},
			{
				label: __( 'Use PHP code', 'formidable' ),
				example: '<?php echo FrmViewsDisplaysController::get_shortcode( array( \'id\' => ' + objectId + ' ) ); ?>'
			}
		);
		return examples;
	}

	function updateEditPageUrl( pageUrl, { type, objectId }) {
		if ( 'view' !== type ) {
			return pageUrl;
		}
		return pageUrl + '&frmView=' + objectId;
	}

	function addFilter( hookName, callback ) {
		wp.hooks.addFilter( hookName, 'formidable', callback );
	}
}() );
