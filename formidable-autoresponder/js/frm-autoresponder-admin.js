jQuery( function( $ ) {
	/* global ajaxurl */
	/* global frmGlobal */

	$( '#frm_notification_settings' )
		.on( 'click', 'h3.frm_add_autoresponder_link', function( e ) {
			var me = $( this );
			e.preventDefault();
			me.fadeOut( function() {
				me.siblings( '.frm_autoresponder_rows' ).fadeIn().find( '.frm-autoresponder-is-active' ).val( '1' );
			} );
		} )
		.on( 'click', '.frm-autoresponder-send-after,.frm-autoresponder-send-after-limit', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( 'label' ).next( '.frm-autoresponder-send-after-meta' ).stop().fadeIn();
			} else {
				$( this ).closest( 'label' ).next( '.frm-autoresponder-send-after-meta' ).stop().fadeOut();
			}
		} )
		.on( 'click', '.frm_remove_autoresponder', function( e ) {
			var wrapper = $( this ).closest( '.frm_autoresponder_rows' );
			e.preventDefault();
			wrapper.find( '.frm-autoresponder-is-active' ).val( '' );
			wrapper.fadeOut( function() {
				wrapper.siblings( '.frm_add_autoresponder_link' ).fadeIn();
			} );
		} )
		.on( 'click', '.frm_remove_autoresponder_log', function( e ) {
			var url,
				confirmText = $( this ).data( 'deleteconfirm' );

			e.preventDefault();

			if ( window.confirm( confirmText ) ) {
				url = $( this ).prev( 'a' ).attr( 'href' ).match( /&log=(.*.log)/ )[1];

				$( this ).closest( 'li' ).fadeOut(
					function() {
						if ( ! $( this ).closest( 'ul' ).children( 'li' ).not( '.frm-autoresponder-toggle' ).not( this ).length ) {
							$( this ).closest( '.frm-autoresponder-debug-detail' ).remove();
						}
						$( this ).remove();
					}
				);

				$.post( ajaxurl, {
					action: 'formidable_autoresponder_delete_log',
					url: url,
					nonce: frmGlobal.nonce
				} );
			}
		} )
		.on( 'click', '.frm_remove_autoresponder_queue', function( e ) {
			var confirmText = $( this ).data( 'deleteconfirm' );
			e.preventDefault();
			if ( window.confirm( confirmText ) ) {
				$( this ).closest( 'tr' ).fadeOut(
					function() {
						if ( ! $( this ).closest( 'tbody' ).children( 'tr' ).not( '.frm-autoresponder-toggle' ).not( this ).length ) {
							$( this ).closest( 'table' ).remove();
						}
						$( this ).remove();
					}
				);
				$.post( ajaxurl, {
					action: 'formidable_autoresponder_delete_queue_item',
					timestamp: $( this ).data( 'timestamp' ),
					entry_id: $( this ).data( 'entry-id' ),
					action_id: $( this ).data( 'action-id' ),
					nonce: frmGlobal.nonce
				} );
			}
		} )
		.on( 'click', '.frm-autoresponder-refresh', function( e ) {
			e.preventDefault();
			$( this ).toggleClass( 'spin' );
		} )
		.on( 'mouseenter', '.frm-autoresponder-trigger-verbage,.frm-autoresponder-trigger-select', function() {
			$( this ).closest( 'td' ).find( 'p .btn' ).css( 'border-color', '#0073aa' );
		} )
		.on( 'mouseleave', '.frm-autoresponder-trigger-verbage,.frm-autoresponder-trigger-select', function() {
			$( this ).closest( 'td' ).find( 'p .btn' ).css( 'border-color', '#dddddd' );
		} )
		.on( 'change', '.frm-autoresponder-send-after-interval,.frm-autoresponder-send-after-interval-field', function() {
			if ( $( this ).val() ) {
				$( this ).prev( 'input' ).prop( 'checked', true );
			}
		} )
		.on( 'change', '.frm-autoresponder-before-after', function() {
			var dateVal,
				dateField = $( this ).next( '.frm_autoresponder_date_field' ),
				options = dateField.find( 'option[value="create"],option[value="update"]' );

			if ( 'before' === $( this ).val() ) {
				dateVal = dateField.val();
				if ( 'create' === dateVal || 'update' === dateVal ) {
					dateField.val( '' );
				}
				options.prop( 'disabled', true );
			} else {
				options.prop( 'disabled', false );
			}
		} )
		.on( 'change', '.frm-autoresponder-debug', function() {
			$( this ).closest( 'label' ).siblings( '.frm-autoresponder-debug-detail' ).toggle( $( this ).is( ':checked' ) );
		} )
		.on( 'click', '.frm-autoresponder-toggle', function( e ) {
			$( this ).hide().siblings( '.frm-autoresponder-toggle' ).show();
			$( this ).siblings( '.frm-autoresponder-more' ).toggle( $( this ).is( '.frm-autoresponder-toggle-more' ) );
			e.preventDefault();
		} );

    $( '#frm_notification_settings' ).find( '.frm-autoresponder-debug' ).trigger( 'change' );

} );
