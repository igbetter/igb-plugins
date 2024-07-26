( function() {
	'use strict';

	const FrmSurveys = {
		isBuilderPage: function() {
			return 'undefined' !== typeof wp.template;
		},

		stringToHtml: function( str ) {
			var template = document.createElement( 'template' );
			template.innerHTML = str.trim();
			return template.content.firstChild;
		},

		generateRandomString: function( length ) {
			if ( ! length ) {
				length = 5;
			}

			let result        = '';
			const chars       = 'abcdefghijklmnopqrstuvwxyz0123456789';
			const charsLength = chars.length;

			for ( let i = 0; i < length; i++ ) {
				result += chars.charAt( Math.floor( Math.random() * charsLength ) );
			}

			return result;
		},

		displayFormat: function( fieldId ) {
			var field, i,
				fieldValue = '0';

			field = document.getElementsByName( 'field_options[image_options_' + fieldId + ']' );
			if ( field === null ) {
				return fieldValue;
			}

			for ( i = 0; i < field.length; i++ ) {
				if ( field[ i ].checked ) {
					fieldValue = field[ i ].value;
				}
			}
			return fieldValue;
		},

		initHooks: function() {
			const hooks = frmAdminBuild.hooks;

			const filterChoiceFieldImagesAsOptions = ( result, fieldId ) => {
				if ( ! document.getElementById( 'frm_display_format_' + fieldId + '_container' ) ) {
					return result;
				}

				const displayFormat = this.displayFormat( fieldId );

				if ( '1' === displayFormat ) {
					return true;
				}

				const useImagesInButtonsCheckbox = document.getElementById( 'frm_use_images_in_buttons_' + fieldId );
				return 'buttons' === displayFormat && useImagesInButtonsCheckbox.checked;
			};

			const filterChoiceFieldShowingLabelWithImage = ( showing, fieldId ) => {
				const displayFormat = this.displayFormat( fieldId );
				if ( 'buttons' === displayFormat ) {
					return true; // Always show button label.
				}
				return showing;
			};

			const filterChoiceFieldLabel = ( label, fieldId, optVal, hasImageOptions ) => {
				const displayFormat = this.displayFormat( fieldId );

				if ( 'buttons' !== displayFormat || hasImageOptions ) {
					// We also use the existing images markup for buttons with images.
					return label;
				}

				label = '<div class="frm_label_button_container">' + label + '</div>';
				return label;
			};

			hooks.addFilter( 'frm_choice_field_showing_label_with_image', filterChoiceFieldShowingLabelWithImage );
			hooks.addFilter( 'frm_choice_field_images_as_options', filterChoiceFieldImagesAsOptions );
			hooks.addFilter( 'frm_choice_field_label', filterChoiceFieldLabel );
			hooks.addFilter( 'frm_admin_get_field_list', this.addLikertRowsToGetFieldList );
			hooks.addFilter( 'frm_admin.build_single_option_template', this.rankingFieldBuildSingleOptionTemplate );

		},

		addLikertRowsToGetFieldList: function( fields, fieldType ) {
			if ( fieldType && 'radio' !== fieldType && 'checkbox' !== fieldType ) {
				return fields;
			}

			const addRowFieldsToFields = ( fieldList, rowFields, likertId, likertName ) => {
				rowFields.forEach( rowField => {
					if ( 'undefined' === typeof likertName ) {
						likertName = document.getElementById( 'field_label_' + likertId ).innerText;
					}

					// Prepend Likert field name.
					rowField.fieldName = likertName + ' - ' + rowField.fieldName;
					fieldList.push( rowField );
				});
			};

			const getLikertIdRowsMapping = () => {
				const likertRows = document.querySelectorAll(
					'.frm_likert .frm_form_field' + ( fieldType ? '[data-ftype="' + fieldType + '"]' : '' )
				);

				if ( ! likertRows.length ) {
					return false;
				}

				const mapping = {};

				likertRows.forEach( likertRow => {
					const likertId = likertRow.closest( '.edit_field_type_likert' ).getAttribute( 'data-fid' );
					if ( 'undefined' === typeof mapping[ likertId ]) {
						mapping[ likertId ] = [];
					}

					mapping[ likertId ].push({
						fieldId: likertRow.dataset.fid,
						fieldName: likertRow.dataset.fname,
						fieldType: likertRow.dataset.ftype,
						fieldKey: likertRow.dataset.fkey
					});
				});

				return mapping;
			};

			const likertIdRowsMapping = getLikertIdRowsMapping();
			if ( ! likertIdRowsMapping ) {
				return fields;
			}

			// Start building new fields.
			const newFields = [];

			// Loop through current fields list, if a Likert field found, add its rows.
			fields.forEach( field => {
				if ( 'undefined' === typeof likertIdRowsMapping[ field.fieldId ]) {
					newFields.push( field );
					return;
				}

				// Only add Likert rows, not Likert field.
				addRowFieldsToFields( newFields, likertIdRowsMapping[ field.fieldId ], field.fieldId, field.fieldName );
				delete likertIdRowsMapping[ field.fieldId ];
			});

			// Add remaining Likert rows in case their Likert field isn't in the fields list.
			Object.keys( likertIdRowsMapping ).forEach( likertId => {
				addRowFieldsToFields( newFields, likertIdRowsMapping[ likertId ], likertId );
			});

			return newFields;
		},

		/**
		 * Init modal.
		 *
		 * @param {String} selector Modal selector string.
		 * @param {String} width    Modal width string (Include unit).
		 *
		 * @returns {boolean|*|Window.jQuery|HTMLElement}
		 */
		initModal: function( selector, width ) {
			var $info = jQuery( selector );
			if ( $info.length < 1 ) {
				return false;
			}

			if ( 'undefined' === typeof width ) {
				width = '550px';
			}

			const bindClickForDialogClose = $modal => {
				jQuery( '.ui-widget-overlay, a.dismiss' ).on( 'click', function() {
					$modal.dialog( 'close' );
				});
			};

			$info.dialog({
				dialogClass: 'frm-dialog',
				modal: true,
				autoOpen: false,
				closeOnEscape: true,
				width: width,
				resizable: false,
				draggable: false,
				open: function() {
					jQuery( '.ui-dialog-titlebar' ).addClass( 'frm_hidden' ).removeClass( 'ui-helper-clearfix' );
					jQuery( '#wpwrap' ).addClass( 'frm_overlay' );
					jQuery( '.frm-dialog' ).removeClass( 'ui-widget ui-widget-content ui-corner-all' );
					$info.removeClass( 'ui-dialog-content ui-widget-content' );
					// bindClickForDialogClose( $info );
				},
				close: function() {
					jQuery( '#wpwrap' ).removeClass( 'frm_overlay' );
					jQuery( '.spinner' ).css( 'visibility', 'hidden' );

					const optionType = document.getElementById( 'bulk-option-type' );
					if ( optionType ) {
						optionType.value = '';
					}
				}
			});

			return $info;
		},

		handleRadioOptions: function() {
			/**
			 * Hides an element.
			 *
			 * @param {HTMLElement|string} element HTML element or query selector string.
			 */
			const hideElement = element => {
				if ( ! element ) {
					return;
				}

				let elements = [];
				if ( 'string' === typeof element ) {
					elements = document.querySelectorAll( element );
				} else if ( 'object' === typeof element && element.classList ) {
					elements.push( element );
				}

				elements.forEach( el => el.classList.add( 'frm_hidden' ) );
			};

			/**
			 * Shows an element.
			 *
			 * @param {HTMLElement|string} element HTML element or query selector string.
			 */
			const showElement = element => {
				if ( ! element ) {
					return;
				}

				let elements = [];
				if ( 'string' === typeof element ) {
					elements = document.querySelectorAll( element );
				} else if ( 'object' === typeof element && element.classList ) {
					elements.push( element );
				}

				elements.forEach( el => {
					el.classList.remove( 'frm_hidden' );
					if ( 'none' === el.style.display ) {
						el.style.removeProperty( 'display' );
					}
				});
			};

			/**
			 * Triggers reload field view.
			 *
			 * @param {Number} fieldId Field ID.
			 */
			const triggerReloadField = fieldId => {
				let opts, i, fieldKey, container;
				const fieldContainer = document.getElementById( 'frm_field_id_' + fieldId );

				if ( ! fieldContainer ) {
					return;
				}
				const fieldType = document.getElementById( 'frm_field_id_' + fieldId ).getAttribute( 'data-ftype' );

				if ( fieldType === 'scale' ) {
					opts = getScaleOpts( fieldId, this.displayFormat( fieldId ) );
					fieldKey = document.querySelector( 'input[name="field_options[field_key_' + fieldId + ']"' ).value;

					container = jQuery( '#field_' + fieldId + '_inner_container > .frm_form_fields' );
					container.html( '' );
					for ( i = 0; i < opts.length; i++ ) {
						container.append( frmAdminBuild.addRadioCheckboxOpt( 'scale', opts[ i ], fieldId, fieldKey, false, '' ) );
					}

					return;
				}

				document.querySelector( '#frm_field_' + fieldId + '_opts .frm_image_preview_wrapper .frm_image_id' ).dispatchEvent( new Event( 'change', { bubbles: true }) );
			};

			const getScaleOpts = ( fieldId, displayFormat ) => {
				let opts = [];
				let label, saved, optObj;
				const optVals = document.querySelectorAll( 'input[name^="item_meta[' + fieldId + ']"]' );

				optVals.forEach( ( opt, index ) => {
					saved = opt.value;
					label = 'buttons' === displayFormat ? '<div class="frm_label_button_container">' + saved + '</div>' : saved;
					optObj = {
						saved: saved,
						label: label,
						key: index.toString()
					};

					opts.push( optObj );
				});

				return opts;
			};

			/**
			 * Enables image options.
			 *
			 * @param {Number} fieldId Field ID.
			 */
			const enableImageOptions = fieldId => {
				const imageSizeDropdown   = document.getElementById( 'field_options_image_size_' + fieldId );
				const imageUploadSelector = '#frm_field_' + fieldId + '_opts .frm_image_preview_wrapper';
				const displayField        = document.getElementById( 'frm_field_id_' + fieldId );

				showElement( imageUploadSelector );
				displayField.classList.add( 'frm_image_options' );
				displayField.classList.add( 'frm_image_size_' + imageSizeDropdown.value );

				triggerReloadField( fieldId );
			};

			/**
			 * Disables image options.
			 *
			 * @param {Number} fieldId Field ID.
			 */
			const disableImageOptions = ( fieldId, displayFormat ) => {
				const imageSizeDropdown   = document.getElementById( 'field_options_image_size_' + fieldId );
				const imageUploadSelector = '#frm_field_' + fieldId + '_opts .frm_image_preview_wrapper';
				const displayField        = document.getElementById( 'frm_field_id_' + fieldId );
				const fieldType = displayField.getAttribute( 'data-ftype' );

				hideElement( imageUploadSelector );
				if ( 'scale' !== fieldType || ( 'scale' === fieldType && 'buttons' !== displayFormat ) ) {
					displayField.classList.remove( 'frm_image_options' );
				}
				if ( imageSizeDropdown ) {
					displayField.classList.remove( 'frm_image_size_' + imageSizeDropdown.value );
				}

				triggerReloadField( fieldId );
			};

			const updateBulkEditVisibility = ( target, displayFormat ) => {
				const fieldSettings = target.closest( '.frm-single-settings' );
				let shoudHideBulkEdit = parseInt( displayFormat ) === 1;
				if ( displayFormat === 'buttons' ) {
					shoudHideBulkEdit = fieldSettings.querySelector( '.frm_use_images_in_button' ).checked;
				}

				fieldSettings.querySelector( '.frm-bulk-edit-link' )?.classList.toggle( 'frm_hidden', shoudHideBulkEdit );
			};

			/**
			 * Handles changing 'Use images in buttons' checkbox.
			 *
			 * @param {Event} event Event object.
			 */
			const onChangeUseImagesInButton = useImagesInButtonCheckbox => {
				const bulkEditButton = useImagesInButtonCheckbox.closest( '.frm-single-settings' ).querySelector( '.frm-bulk-edit-link' );
				bulkEditButton.classList.toggle( 'frm_hidden', useImagesInButtonCheckbox.checked );
			};

			/**
			 * Handles changing Display Format dropdown.
			 *
			 * @param {Event} event Event object.
			 */
			const onChangeDisplayFormat = event => {
				const displayFormat = event.target.value;
				const fieldId       = event.target.getAttribute( 'data-fid' );

				const useImagesInButtonsEl = document.getElementById( 'frm_use_images_in_buttons_' + fieldId + '_container' );
				const hideOptionTextEl     = document.getElementById( 'frm_hide_option_text_' + fieldId + '_container' );
				const alignmentEl          = document.getElementById( 'frm_alignment_' + fieldId );
				const displayField         = document.getElementById( 'frm_field_id_' + fieldId );
				const imageSizeEl          = document.getElementById( 'frm_image_size_' + fieldId + '_container' );
				const imageAlignEl         = document.getElementById( 'frm_image_align_' + fieldId + '_container' );
				const textAlignEl          = document.getElementById( 'frm_text_align_' + fieldId + '_container' );
				const useImagesInButtonsCheckbox = document.getElementById( 'frm_use_images_in_buttons_' + fieldId );

				if ( '1' === displayFormat ) {
					// Use images.
					hideElement( useImagesInButtonsEl );
					hideElement( alignmentEl );
					hideElement( imageAlignEl );
					hideElement( textAlignEl );
					showElement( hideOptionTextEl );
					showElement( imageSizeEl );

					displayField.classList.remove( 'frm_display_format_buttons' );

					enableImageOptions( fieldId );
				} else if ( 'buttons' === displayFormat ) {
					// Use buttons.
					showElement( useImagesInButtonsEl );
					showElement( alignmentEl );
					showElement( textAlignEl );
					hideElement( imageSizeEl );
					hideElement( hideOptionTextEl );

					displayField.classList.add( 'frm_display_format_buttons' );
					displayField.classList.add( 'frm_image_options' );

					if ( useImagesInButtonsCheckbox && useImagesInButtonsCheckbox.checked ) {
						if ( 'center' !== document.getElementById( 'frm_text_align_' + fieldId ).value ) {
							showElement( imageAlignEl );
						} else {
							hideElement( imageAlignEl );
						}

						enableImageOptions( fieldId );
					} else {
						hideElement( imageAlignEl );

						disableImageOptions( fieldId, displayFormat );
					}
				} else {
					showElement( alignmentEl );
					hideElement( useImagesInButtonsEl );
					hideElement( hideOptionTextEl );
					hideElement( imageSizeEl );
					hideElement( imageAlignEl );
					hideElement( textAlignEl );

					displayField.classList.remove( 'frm_display_format_buttons' );

					disableImageOptions( fieldId, displayFormat );
				}

				triggerReloadField( fieldId );
				updateBulkEditVisibility( event.target, displayFormat );
			};

			/**
			 * Handles changing Use Images In Options checkbox.
			 *
			 * @param {Event} event Event object.
			 */
			const onChangeUseImagesInOptions = event => {
				if ( event.target.closest( '.frm_form_field' ).classList.contains( 'frm_hidden' ) ) {
					return; // Do not handle if this field is hidden.
				}

				const fieldId      = event.target.getAttribute( 'data-fid' );
				const imageAlignEl = document.getElementById( 'frm_image_align_' + fieldId + '_container' );
				const textAlignVal = document.getElementById( 'frm_text_align_' + fieldId ).value;
				const useImages    = event.target.checked;

				if ( useImages ) {
					enableImageOptions( fieldId );
				} else {
					disableImageOptions( fieldId );
				}

				if ( useImages && 'center' !== textAlignVal ) {
					showElement( imageAlignEl );
				} else {
					hideElement( imageAlignEl );
				}
			};

			const onChangeTextAlignOption = event => {
				const fieldId   = event.target.getAttribute( 'data-fid' );
				const displayEl = document.getElementById( 'frm_field_id_' + fieldId );
				if ( ! displayEl ) {
					return;
				}

				const align = event.target.value;

				[ 'left', 'center', 'right' ].forEach( align => displayEl.classList.remove( 'frm_text_align_' + align ) );

				displayEl.classList.add( 'frm_text_align_' + align );

				// Hide Image alignment option if Text align is set to center.
				const imageAlignEl  = document.getElementById( 'frm_image_align_' + fieldId + '_container' );
				const imageAlignVal = document.getElementById( 'frm_image_align_' + fieldId ).value;
				const useImages     = document.getElementById( 'frm_use_images_in_buttons_' + fieldId ).checked;
				if ( 'center' === align || ! useImages ) {
					hideElement( imageAlignEl );
					displayEl.classList.remove( 'frm_image_align_' + imageAlignVal );
				} else {
					showElement( imageAlignEl );
					displayEl.classList.add( 'frm_image_align_' + imageAlignVal );
				}
			};

			const onChangeImageAlignOption = event => {
				const fieldId   = event.target.getAttribute( 'data-fid' );
				const displayEl = document.getElementById( 'frm_field_id_' + fieldId );
				if ( ! displayEl ) {
					return;
				}

				const align = event.target.value;

				[ 'left', 'right' ].forEach( align => displayEl.classList.remove( 'frm_image_align_' + align ) );

				displayEl.classList.add( 'frm_image_align_' + align );
			};

			document.addEventListener( 'change', event => {
				const target = event.target;
				if ( target.matches( '.frm_radio_display_format' ) || target.matches( '.frm_scale_display_format' ) ) {
					onChangeDisplayFormat( event );
					return;
				}

				if ( target.matches( '.frm_use_images_in_button' ) ) {
					onChangeUseImagesInButton( event.target );
					return;
				}

				if ( target.matches( '.frm_scale_opt' ) ) {
					if ( ! frmAdminBuild.addRadioCheckboxOpt || ! target.closest( '.frm-type-scale' ) || ! target.closest( '.frm-type-scale' ).dataset.fid ) {
						return;
					}
					const fieldId = target.closest( '.frm-type-scale' ).dataset.fid;
					triggerReloadField( fieldId );
				}

				if ( target.matches( '.frm_use_images_in_button' ) ) {
					onChangeUseImagesInOptions( event );
					return;
				}

				if ( target.matches( '.frm_surveys_text_align_option' ) ) {
					onChangeTextAlignOption( event );
					return;
				}

				if ( target.matches( '.frm_surveys_image_align_option' ) ) {
					onChangeImageAlignOption( event );
				}
			}, false );
		},

		toggleLikertType: function( event ) {
			var i, opts,
				fieldId = event.target.getAttribute( 'data-likert-id' ),
				field = document.querySelector( '#frm_field_id_' + fieldId ),
				newtype = event.target.checked ? 'checkbox' : 'radio';

			if ( field !== null ) {
				opts = field.querySelectorAll( '[name^="item_meta"]' );
				for ( i = 0; i < opts.length; i++ ) {
					opts[i].type = newtype;
				}
			}
		},

		toggleLikertHeading: function( event ) {
			var inlineclass = 'frm_likert--inline',
				fieldId = event.target.getAttribute( 'data-likert-id' ),
				field = document.querySelector( '#frm_field_id_' + fieldId + ' .frm_likert' );

			if ( event.target.checked ) {
				field.classList.add( inlineclass );
			} else {
				field.classList.remove( inlineclass );
			}
		},

		toggleSeparateValues: function( event ) {
			const fieldId      = event.target.getAttribute( 'data-likert-id' );
			const selector     = '.frm_likert_opts_container[data-likert-id="' + fieldId + '"][data-option-type="likert_column"]';
			const optContainer = document.querySelector( selector ).parentNode;

			optContainer.setAttribute( 'data-separate-values', event.target.checked ? '1' : '0' );
		},

		triggerReloadLikertFieldDisplay: function( fieldId ) {
			const event = new Event( 'frm_likert_field_changed' );
			event.frmFieldId = fieldId;
			document.dispatchEvent( event );
		},

		handleLikertOptions: function() {
			const self = this;

			const onClickAddOption = event => {
				event.preventDefault();

				const wrapperEl  = event.target.closest( '.frm_likert_opts_container' );
				const optionType = wrapperEl.getAttribute( 'data-option-type' );
				const likertId   = wrapperEl.getAttribute( 'data-likert-id' );
				const basedId    = wrapperEl.getAttribute( 'data-base-id' );
				const basedName  = wrapperEl.getAttribute( 'data-base-name' );
				const optionsEl  = document.getElementById( basedId + '_options' );

				let newOptTmpl, newOpt;

				// Reset value.
				if ( 'likert_column' === optionType ) {
					const newKey = this.generateRandomString();

					newOptTmpl = wp.template( 'frm_likert_column_opt' );
					newOpt     = this.stringToHtml( newOptTmpl({
						likertId: likertId,
						key: newKey,
						title: '',
						value: '',
						baseName: basedName
					}) );
				} else {
					newOptTmpl = wp.template( 'frm_likert_row_opt' );
					newOpt     = this.stringToHtml( newOptTmpl({
						likertId: likertId,
						title: '',
						id: '',
						value: '',
						baseName: basedName
					}) );
				}

				optionsEl.append( newOpt );

				this.triggerReloadLikertFieldDisplay( likertId );
			};

			const onClickDeleteOption = event => {
				event.preventDefault();

				const optionEl  = event.target.closest( '.frm_likert_opt' );
				const wrapperEl = optionEl.closest( '.frm_likert_opts_container' );
				const likertId  = wrapperEl.getAttribute( 'data-likert-id' );
				const key       = optionEl.getAttribute( 'data-key' );

				// Fade the item.
				optionEl.style.opacity = '0';
				optionEl.addEventListener( 'transitionend', () => {
					optionEl.remove();
					this.triggerReloadLikertFieldDisplay( likertId );
				});

				if ( key ) {
					const basedId       = wrapperEl.getAttribute( 'data-base-id' );
					const deletedKeysEl = document.getElementById( basedId + '_deleted_keys' );
					if ( deletedKeysEl ) {
						const deletedKeys = deletedKeysEl.value ? JSON.parse( deletedKeysEl.value ) : [];
						deletedKeys.push( key );
						deletedKeysEl.value = JSON.stringify( deletedKeys );
					}
				}
			};

			const initSortable = el => {
				const $el     = jQuery( el );
				const options = {
					axis: 'y',
					opacity: 0.65,
					handle: '.frm-drag',
					stop: function( ev, ui ) {
						self.triggerReloadLikertFieldDisplay( this.closest( '.frm_likert_opts_container' ).getAttribute( 'data-likert-id' ) );
					}
				};

				$el.sortable( options );
			};

			document.addEventListener( 'click', event => {
				if ( event.target.matches( '.frm_likert_add_opt' ) || event.target.closest( '.frm_likert_add_opt' ) ) {
					onClickAddOption( event );
					return;
				}

				if ( event.target.matches( '.frm_likert_delete_opt' ) ) {
					onClickDeleteOption( event );
				}
			}, false );

			document.addEventListener( 'change', event => {
				if ( event.target.matches( '.frm_likert_opt input:not(.frm_likert_opt_value)' ) ) {
					this.triggerReloadLikertFieldDisplay( event.target.getAttribute( 'data-likert-id' ) );
					return;
				}

				if ( event.target.matches( '.frm_likert_multi_selection' ) ) {
					this.toggleLikertType( event );
					return;
				}

				if ( event.target.matches( '.frm_likert_inline_column' ) ) {
					this.toggleLikertHeading( event );
					return;
				}

				if ( event.target.matches( '.frm_likert_separate_value' ) ) {
					this.toggleSeparateValues( event );
				}
			});

			document.addEventListener( 'DOMContentLoaded', event => {
				document.querySelectorAll( '.frm_likert_opts' ).forEach( el => {
					initSortable( el );
				});
			});

			document.addEventListener( 'frm_added_field', event => {
				if ( 'likert' !== event.frmType ) {
					return;
				}

				const fieldId = event.frmField.getAttribute( 'data-fid' );
				initSortable( document.getElementById( 'frm_rows_' + fieldId + '_options' ) );
				initSortable( document.getElementById( 'frm_columns_' + fieldId + '_options' ) );
			});

			document.addEventListener( 'frm_ajax_loaded_field', event => {
				event.frmFields.forEach( field => {
					if ( 'likert' !== field.type ) {
						return;
					}

					initSortable( document.getElementById( 'frm_rows_' + field.id + '_options' ) );
					initSortable( document.getElementById( 'frm_columns_' + field.id + '_options' ) );
				});
			});
		},

		handleLikertLiveUpdating: function() {
			const addRowsSettings = settings => {
				settings.rows = [];

				const list = document.getElementById( 'frm_rows_' + settings.field_id + '_options' );
				if ( ! list ) {
					return;
				}

				const items = list.querySelectorAll( '.frm_likert_opt' );
				if ( ! items || ! items.length ) {
					return;
				}

				items.forEach( item => {
					const itemName = item.querySelector( 'input' ).value;
					if ( item.getAttribute( 'data-key' ) ) {
						settings.rows.push({
							name: itemName,
							id: item.getAttribute( 'data-id' ),
							key: item.getAttribute( 'data-key' )
						});
					} else {
						settings.rows.push( itemName );
					}
				});
			};

			const addColumnsSettings = settings => {
				settings.columns = [];

				const list = document.getElementById( 'frm_columns_' + settings.field_id + '_options' );
				if ( ! list ) {
					return;
				}

				const items = list.querySelectorAll( '.frm_likert_opt' );
				if ( ! items ) {
					return;
				}

				if ( ! items ) {
					return;
				}

				if ( settings.separate_value ) {
					items.forEach( item => {
						settings.columns.push({
							label: item.querySelector( '.frm_likert_opt_label' ).value,
							value: item.querySelector( '.frm_likert_opt_value' ).value
						});
					});
				} else {
					items.forEach( item => {
						const label = item.querySelector( '.frm_likert_opt_label' ).value;
						settings.columns.push({
							label: label,
							value: label
						});
					});
				}
			};

			/**
			 * Gets field settings.
			 *
			 * @param {Number} fieldId Field ID.
			 * @returns {Object}
			 */
			const getFieldSettings = fieldId => {
				const settings = {
					field_id: fieldId
				};

				// Checkbox options.
				[ 'multi_selection', 'inline_column', 'separate_value' ].forEach( name => {
					const checkbox = document.getElementById( 'frm_likert_' + name + '_' + fieldId );
					settings[ name ] = checkbox && checkbox.checked ? 1 : 0;
				});

				addRowsSettings( settings );

				addColumnsSettings( settings );

				return settings;
			};

			/**
			 * Reloads field display.
			 *
			 * @param {Number} fieldId Field ID.
			 */
			const reloadFieldDisplay = fieldId => {
				const displayEl = document.querySelector( '#field_' + fieldId + '_inner_container .frm_opt_container' );
				if ( ! displayEl ) {
					return;
				}

				const settings = getFieldSettings( fieldId );

				settings._ajax_nonce = FrmSurveysL10n.ajaxNonce;
				settings.action      = 'frm_load_likert_display';

				wp.ajax.send({
					type: 'post',
					data: settings,
					success: function( response ) {
						displayEl.innerHTML = response;
					},
					error: function( error ) {
						console.error( error );
					}
				});
			};

			document.addEventListener( 'frm_likert_field_changed', event => reloadFieldDisplay( event.frmFieldId ) );
		},

		handleBulkEditLink: function() {
			const $info = this.initModal( '#frm-bulk-modal', '700px' );
			if ( $info === false ) {
				return;
			}

			const bulkOptionsContentEl = document.getElementById( 'frm_bulk_options' );
			const bulkFieldIdEl        = document.getElementById( 'bulk-field-id' );
			const bulkOptionType       = document.getElementById( 'bulk-option-type' );

			const insertBulkPreset = event => {
				event.preventDefault();
				const opts = JSON.parse( event.target.getAttribute( 'data-opts' ) );
				document.getElementById( 'frm_bulk_options' ).value = opts.join( '\n' );
			};

			const onClickBulkEdit = event => {
				event.preventDefault();

				const fieldId = event.target.closest( '[data-likert-id]' ).getAttribute( 'data-likert-id' );
				const opts    = event.target.closest( '.frm_likert_opts_container' ).querySelectorAll( '.frm_likert_opt input' );
				const optsLength = opts.length;

				let breakChar,
					content = '';

				bulkFieldIdEl.value  = fieldId;
				bulkOptionType.value = event.target.getAttribute( 'data-option-type' );

				if ( 'likert_column' === bulkOptionType.value ) {
					const useSeparateValues = document.getElementById( 'frm_likert_separate_value_' + fieldId ).checked;

					// The opts could be label or value inputs.
					Array.prototype.forEach.call( opts, ( opt, i ) => {
						breakChar = i + 1 === optsLength ? '' : '\r\n';
						const isValueInput = opt.classList.contains( 'frm_likert_opt_value' );
						if ( useSeparateValues ) {
							// Append `|` if this is the value.
							if ( isValueInput ) {
								content = content + '|' + opt.value + breakChar;
							} else {
								content += opt.value;
							}
						} else if ( ! isValueInput ) {
							content = content + opt.value + breakChar;
						}
					});
				} else {
					const savedOpts = {};

					Array.prototype.forEach.call( opts, ( opt, i ) => {
						breakChar = i + 1 === optsLength ? '' : '\r\n';
						content   = content + opt.value + breakChar;

						const key = opt.parentNode.getAttribute( 'data-key' );
						if ( key ) {
							savedOpts[ opt.value ] = key;
						}
					});

					// This is used to get row IDs that will be deleted.
					bulkOptionsContentEl.setAttribute( 'data-saved-opts', JSON.stringify( savedOpts ) );
				}

				bulkOptionsContentEl.value = content;

				$info.attr( 'data-option-type', bulkOptionType.value );
				$info.dialog( 'open' );
			};

			const onClickUpdateOptions = event => {
				const optionType = bulkOptionType.value;

				if ( 'likert_row' !== optionType && 'likert_column' !== optionType ) {
					return;
				}

				event.preventDefault();
				event.target.classList.add( 'frm_loading_button' );

				const fieldId    = bulkFieldIdEl.value;
				const options    = bulkOptionsContentEl.value.split( '\n' );
				const newOptions = [];
				let tmpl, optionList, savedOptions;

				if ( 'likert_column' === optionType ) {
					optionList = document.getElementById( 'frm_columns_' + fieldId + '_options' );
					tmpl       = wp.template( 'frm_likert_column_opt' );

					options.forEach( ( option, i ) => {
						if ( -1 === option.indexOf( '|' ) ) {
							newOptions.push({
								label: option,
								value: option,
								key: i
							});
						} else {
							option = option.split( '|' );
							newOptions.push({
								label: option[0],
								value: option[1],
								key: i
							});
						}
					});
				} else {
					optionList   = document.getElementById( 'frm_rows_' + fieldId + '_options' );
					tmpl         = wp.template( 'frm_likert_row_opt' );
					savedOptions = JSON.parse( bulkOptionsContentEl.getAttribute( 'data-saved-opts' ) );

					options.forEach( option => {
						if ( savedOptions.hasOwnProperty( option ) ) {
							newOptions.push({
								label: option,
								key: savedOptions[ option ]
							});

							delete savedOptions[ option ];
						} else {
							newOptions.push({
								label: option,
								key: ''
							});
						}
					});
				}

				const baseName = optionList.parentNode.getAttribute( 'data-base-name' );

				optionList.innerHTML = '';

				newOptions.forEach( newOption => {
					optionList.append( this.stringToHtml( tmpl({
						likertId: fieldId,
						key: newOption.key,
						title: newOption.label,
						value: newOption.value,
						baseName: baseName
					}) ) );
				});

				this.triggerReloadLikertFieldDisplay( fieldId );

				$info.dialog( 'close' );
				$info.removeAttr( 'data-option-type' );
				bulkOptionType.value = '';

				event.target.classList.remove( 'frm_loading_button' );

				// Add removed rows to the delete input.
				if ( 'likert_row' === optionType && savedOptions && {} !== savedOptions ) {
					const deleteInput = document.getElementById( 'frm_rows_' + fieldId + '_deleted_keys' );
					const deleteKeys  = deleteInput.value ? JSON.parse( deleteInput.value ) : [];
					for ( let x in savedOptions ) {
						deleteKeys.push( savedOptions[ x ]);
					}

					deleteInput.value = JSON.stringify( deleteKeys );
				}
			};

			document.querySelector( '.frm-insert-preset' ).addEventListener( 'click', insertBulkPreset, false );

			document.addEventListener( 'click', event => {
				if ( event.target.matches( '.frm_likert_bulk_edit_opts a' ) ) {
					onClickBulkEdit( event );
					return;
				}

				if ( event.target.id === 'frm-update-bulk-opts' ) {
					onClickUpdateOptions( event );
				}
			}, false );
		},

		handleNPSLiveUpdating: function() {
			const onChangeDefault = event => {
				const baseId = event.target.getAttribute( 'data-changeme' );
				const input = document.getElementById( baseId + '-' + event.target.value );

				document.querySelectorAll( '#' + baseId + ' input[type="radio"]' ).forEach( el => {
					el.checked = false;
				});

				if ( input ) {
					input.checked = true;
				}
			};

			document.addEventListener( 'change', event => {
				if ( event.target.matches( '.frm-type-nps .default-value-field' ) ) {
					onChangeDefault( event );
				}
			}, false );
		},

		rankingFieldBuildSingleOptionTemplate: function( singleHtml, args ) {
			const { opt, type, id, fieldId, classes } = args;

			if ( 'ranking' !== type ) {
				return singleHtml;
			}

			const createOption = function() {
				const self = this;

				this.hidenInput = function() {
					const input = frmDom.tag(
						'input',
						{
							id: id,
							data: {
								'field-type': type
							}
						}
					);
					input.value = frmAdminBuild.purifyHtml( opt.saved );
					input.setAttribute( 'type', 'hidden' );
					input.setAttribute( 'name', 'item_meta[' + fieldId + '][]' );
					return input;
				};

				this.select = function() {
					const select = frmDom.tag( 'select', { className: 'frm-ranking-position' });
					const option = document.createElement( 'option' );

					option.value = 0;
					option.innerHTML = '&mdash;';

					select.appendChild( option );
					return select;
				};

				this.text = function() {
					return frmDom.tag( 'label', { child: opt.label });
				};

				this.svg = function() {
					return frmDom.svg({ href: '#frm_drag_icon', classname: 'frmsvg frm_drag_icon frm-drag' });
				};

				this.span = function() {
					return frmDom.span({ children: [ self.select(), self.hidenInput(), opt.label ] });
				};

				return frmDom.div({
					className: 'frm_' + type + ' ' + type + ' ' + classes + ' frm-ranking-field-option frm-flex-box frm-justify-between frm-items-center',
					id: 'frm_' + type + '_' + fieldId + '-' + opt.key,
					children: [ self.span(), self.svg() ]
				});
			};

			return new createOption();

		},

		init: function() {
			this.initHooks();

			if ( this.isBuilderPage() ) {
				this.handleRadioOptions();
				this.handleLikertOptions();
				this.handleLikertLiveUpdating();
				this.handleBulkEditLink();
				this.handleNPSLiveUpdating();
			}
		}
	};

	FrmSurveys.init();
}() );
