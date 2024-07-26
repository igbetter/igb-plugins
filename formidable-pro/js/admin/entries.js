/**
 * This file is loaded on the entries page.
 *
 */
( function () {
	/** globals frmDom, wp */

	if ( 'undefined' === typeof frmDom || 'undefined' === typeof wp ) {
		return;
	}

	const __ = wp.i18n.__;

	function confirmDeleteAllEntriesModal( args ) {
		const self = this;
		const { link, initModal } = args;

		this.modal = initModal( '#frm_confirm_modal', '500px' );

		this.wrapper = document.getElementById( 'frm_confirm_modal' );
		this.confirmButton = document.getElementById( 'frm-confirmed-click' );

		this.confirmationInput = self.wrapper.querySelector(
			'.frm-delete-confirmation-input'
		);

		this.timeoutInterval = null;

		this.modalOptions = {
			heading: 'Delete all %entriesCount% entries?',
			headingSingleEntry: 'Delete the entry?',
			copy: '',
			inputPlaceholder: 'Type in "DELETE ALL" to delete all entries',
			entriesCount: 0,
		};

		this.deleteAllFormEntries = false;

		this.countEntries = function () {
			if ( null !== link.getAttribute( 'data-total-entries' ) ) {
				self.modalOptions.entriesCount = parseInt(
					link.getAttribute( 'data-total-entries' ),
					10
				);
				self.deleteAllFormEntries = true;
				return self.modalOptions.entriesCount;
			}

			self.modalOptions.entriesCount = 0;
			document
				.querySelectorAll( 'input[name="item-action[]"]' )
				.forEach( function ( checkbox ) {
					if ( checkbox.checked ) {
						self.modalOptions.entriesCount++;
					}
				} );
			return self.modalOptions.entriesCount;
		};

		this.getHeading = function () {
			if ( 1 === self.modalOptions.entriesCount ) {
				return self.modalOptions.headingSingleEntry;
			}
			return self.modalOptions.heading.replace(
				'%entriesCount%',
				self.modalOptions.entriesCount
			);
		};

		this.getCopy = function () {
			if ( ! link.getAttribute( 'data-frmverify' ) ) {
				return self.modalOptions.copy;
			}

			const copy = window.frmDom.tag( 'span' );
			copy.innerHTML = link.getAttribute( 'data-frmverify' );

			if ( false === self.deleteAllFormEntries ) {
				if ( 1 === self.modalOptions.entriesCount ) {
					copy.innerHTML = link
						.getAttribute( 'data-frmverify' )
						.replace( 'ALL entries', 'The selected entry' );
				} else {
					copy.innerHTML = link
						.getAttribute( 'data-frmverify' )
						.replace( 'ALL entries', 'The selected entries' );
				}
			}

			return ( self.modalOptions.copy = copy );
		};

		this.getConfirmationInput = function () {
			self.confirmationInput = window.frmDom.tag( 'input', {
				className: 'frm-delete-confirmation-input',
			} );
			self.confirmationInput.setAttribute( 'type', 'text' );
			return self.confirmationInput;
		};

		this.getOnDeleteActionsTriggerCheckbox = function() {
			const checkbox = window.frmDom.tag( 'input' );
			checkbox.type  = 'checkbox';
			checkbox.id    = 'frm_trigger_on_delete_entry_actions';
			checkbox.addEventListener( 'click', function( e ) {
				self.updateOnDeleteURL( e.target.checked );
			});

			const label = window.frmDom.tag( 'label', {
				children: [
					checkbox,
					document.createTextNode( __( 'Trigger all actions that happen "on entry deleted"', 'formidable-pro' ) ),
				],
			} );
			label.for = 'frm_trigger_on_delete_entry_actions';

			return label;
		};

		this.updateOnDeleteURL = function( triggerOnDeleteActions = true ) {
			let redirectURL = link.getAttribute( 'href' );
			if ( triggerOnDeleteActions ) {
				redirectURL += '&trigger_on_delete_entry_actions=delete';
			}
			self.confirmButton.setAttribute(
				'href',
				redirectURL
			);
		};
	
		this.initConfirmButton = function ( active ) {
			self.confirmButton.classList.add(
				link.getAttribute( 'data-frmverify-btn' )
			);
			if ( true === active ) {
				self.confirmButton.classList.remove( 'frm-btn-inactive' );
				self.confirmButton.classList.add( 'dismiss' );
				const triggerActionsOnDeleteEl = document.getElementById( 'frm_trigger_on_delete_entry_actions' );
				if ( triggerActionsOnDeleteEl ) {
					self.updateOnDeleteURL( triggerActionsOnDeleteEl.checked );
				} else {
					self.updateOnDeleteURL( link.getAttribute( 'data-total-entries' ) );
				}
				return;
			}
			self.confirmButton.setAttribute( 'href', '#' );
			self.confirmButton.classList.add( 'frm-btn-inactive' );
			self.confirmButton.classList.remove( 'dismiss' );
			self.modal.one( 'dialogclose', function () {
				self.confirmButton.classList.remove( 'frm-btn-inactive' );
			} );
		};

		this.initConfirmationInput = function () {
			self.confirmationInput.placeholder =
				self.modalOptions.inputPlaceholder;
			self.confirmationInput.addEventListener( 'keydown', function () {
				clearTimeout( self.timeoutInterval );
				self.timeoutInterval = setTimeout(
					self.confirmationCheck,
					100
				);
			} );
		};

		this.confirmationCheck = function () {
			if (
				'delete all' ===
				self.confirmationInput.value.toLowerCase().trim()
			) {
				self.initConfirmButton( true );
				return;
			}
			self.initConfirmButton( false );
		};

		this.initModal = function () {
			if (
				null === self.wrapper ||
				null === self.wrapper.querySelector( '.frm-confirm-msg' )
			) {
				return;
			}
			const copyWrapper =
				self.wrapper.querySelector( '.frm-confirm-msg' );
			copyWrapper.classList.add(
				'frm-delete-all-entries-modal-confirmation'
			);
			copyWrapper.innerHTML = '';
			copyWrapper.append( window.frmDom.tag( 'h2', self.getHeading() ) );
			copyWrapper.append( self.getCopy() );
			copyWrapper.append( self.getConfirmationInput() );
			if (
				link.getAttribute( 'data-total-entries' ) &&
				( 'undefined' === typeof frmEntriesData || frmEntriesData.hasPostAction === '0' )
			) {
				copyWrapper.append( self.getOnDeleteActionsTriggerCheckbox() );
			}

			self.initConfirmationInput();
			self.initConfirmButton( false );
		};

		this.openModal = function () {
			if ( false === self.modal || 0 === self.countEntries() ) {
				return false;
			}
			self.initModal();
			self.modal.dialog( 'open' );
		};

		return this.openModal();
	}

	function addFilter( hookName, callback ) {
		wp.hooks.addFilter( hookName, 'formidable', callback );
	}

	addFilter( 'frm_on_multiple_entries_delete', confirmDeleteAllEntriesModal );

	const resendEmailTrigger = document.getElementById( 'frm_resend_email' );
	if ( resendEmailTrigger ) {
		/**
		 * Resend emails when the "Resend emails" trigger is clicked in the sidebar.
		 *
		 * @since 6.10
		 *
		 * @param {Event} event
		 * @returns 
		 */
		const resendEmail = ( event ) => {
			event.preventDefault();

			const link    = resendEmailTrigger;
			const entryId = link.dataset.eid;
			const formId  = link.dataset.fid;

			let label = link.querySelector( '.frm_link_label' );
			if ( ! label ) {
				label = link;
			}

			jQuery( label ).append( '<span class="frm-wait"></span>' );

			jQuery.ajax({
				type: 'POST',
				url: frm_js.ajax_url, // eslint-disable-line camelcase
				data: {
					action: 'frm_entries_send_email',
					entry_id: entryId,
					form_id: formId,
					nonce: frm_js.nonce // eslint-disable-line camelcase
				},
				success: function( msg ) {
					label.innerHTML = '';
					jQuery( link ).after( msg );
				}
			});
			return false;
		};

		setTimeout(
			() => {
				if ( window.frm_js && undefined !== window.frm_js.include_resend_email ) {
					resendEmailTrigger.addEventListener( 'click', resendEmail );
				}
			},
			0
		);
	}
} )();
