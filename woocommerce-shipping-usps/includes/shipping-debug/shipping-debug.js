( function( $ ) {
	/*
	 * Add accordion widgets on first page load.
	 */
	$( document ).ready( function() {
		addAccordions();
	} );
	
	/*
	 * New debug info can come in via ajax, so we need to check and add accordion widgets.
	 */
	$( document ).ajaxComplete( function() {
		addAccordions();
	} );

	/*
	 * Add accordion widget in correct place.
	 */
	function addAccordions() {
		var $accordionContainers = $( ".woocommerce-shipping-debug-info-container" );
	
		$accordionContainers.find( ".woocommerce-shipping-debug-info-accordion" ).not( ".ui-accordion" )
			.accordion( { 
				collapsible: true,
				heightStyle: 'content',
				active: false,
			} )
			.show();
	}
} )( jQuery );
