/**
 * @since 1.1
 */
( function( ) {
	function frmSurveysRankingField() {
		var self = this,
			rankingFieldContainers = document.querySelectorAll( '.frm-ranking-field-container' );

		if ( 0 === rankingFieldContainers.length || ! window.frmFrontForm ) {
			return;
		}

		this.selectors = {
			wrapper: '.frm-ranking-field-container',
			container: '.frm_opt_container',
			option: '.frm-ranking-field-option',
			select: '.frm-ranking-position',
			asnwersLimit: '.frm-ranking-answers-limit',
			activeSelections: '.frm-ranking-active-selections'
		};

		this.activeSelectionsOrder = {};
		this.options = {};

		this.initOptions = function() {
			var fieldsContainer = document.querySelectorAll( self.selectors.wrapper ),
				index, wrapperId, answersLimitInput;

			for ( index = 0; index < fieldsContainer.length; index++ ) {
				wrapperId         = fieldsContainer[ index ].getAttribute( 'id' );
				answersLimitInput = self.getElement( fieldsContainer[ index ], 'asnwersLimit' );

				self.setOption( wrapperId, 'answersLimit', parseInt( answersLimitInput.value, 10 ) );
			}
		};

		this.setOption = function( wrapperId, optionKey, value ) {
			if ( 'undefined' === typeof self.options[ wrapperId ]) {
				self.options[ wrapperId ] = [];
			}
			self.options[ wrapperId ][ optionKey ] = value;
		};

		this.getOption = function( wrapperId, optionKey ) {
			if ( 'undefined' === typeof self.options[ wrapperId ] || 'undefined' === typeof self.options[ wrapperId ][ optionKey ]) {
				return null;
			}
			return self.options[ wrapperId ][ optionKey ];
		};

		this.limitSelectionHasReached = function( wrapperId ) {
			var limit = self.getOption( wrapperId, 'answersLimit' );
			if ( null === limit || 'undefined' === typeof self.activeSelectionsOrder[ wrapperId ] || 0 === self.activeSelectionsOrder[ wrapperId ].length || limit !== self.activeSelectionsOrder[ wrapperId ].length ) {
				return false;
			}
			return true;
		};

		this.maybeLimitOrEnableSelections = function( wrapperId, wrapper ) {
			var selectItems;

			selectItems = wrapper.querySelectorAll( '.frm-ranking-field-option:not( .frm-ranking-draggable-option ) select' );
			if ( true === self.limitSelectionHasReached( wrapperId ) ) {
				wrapper.classList.add( 'frm-ranking-field-limit-reached' );
				self.enableSelectItems( selectItems, false );
				return;
			}
			self.enableSelectItems( selectItems, true );
			wrapper.classList.remove( 'frm-ranking-field-limit-reached' );
		};

		this.enableSelectItems = function( selectItems, enable ) {
			var index;
			for ( index = 0; index < selectItems.length; index++ ) {
				selectItems[ index ].disabled = ! enable;
			}
		};

		this.afterPositionChanged = function( wrapperId ) {
			var wrapper = document.getElementById( wrapperId );
			if ( null === wrapper ) {
				return;
			}

			self.maybeLimitOrEnableSelections( wrapperId, wrapper );
			self.removeErrorStyle( wrapper );
			self.toggleDragIcon( wrapperId, wrapper );
		};

		this.toggleDragIcon = function( wrapperId, wrapper ) {
			if ( 'undefined' === typeof self.activeSelectionsOrder[ wrapperId ] || 1 >= self.activeSelectionsOrder[ wrapperId ].length ) {
				wrapper.classList.remove( 'frm-enable-draggable' );
				return;
			}
			wrapper.classList.add( 'frm-enable-draggable' );
		};

		this.onPositionChange = function() {

			// Change position when an option is clicked
			window.frmFrontForm.documentOn( 'click', self.selectors.option, function( event ) {
				var fieldOrder, select, wrapperId;

				if ( 'SELECT' === event.target.nodeName || 'SVG' === event.target.nodeName ) {
					return false;
				}
				wrapperId = self.getElement( event.target, 'wrapper' ).getAttribute( 'id' );
				select    = self.getElement( event.target, 'select' );

				// Init the default possible active selections passed from backend.
				self.initDefaultActiveSelections( wrapperId );

				if ( 0 != select.value || true === self.limitSelectionHasReached( wrapperId ) ) {
					return false;
				}

				fieldOrder = self.findNextMissingOrder( wrapperId );
				select.value = fieldOrder;

				// It calls this.resetValues which will clone the options and append to parent container.
				// After calling this.resetValues, the event.target will become detached.
				self.updateOrder( event.target, fieldOrder, wrapperId );
				self.afterPositionChanged( wrapperId );
			});

			// Change position on SELECT change event
			window.frmFrontForm.documentOn( 'change', self.selectors.select, function( event ) {
				var wrapperId = self.getElement( event.target, 'wrapper' ).getAttribute( 'id' );

				// Init the default possible active selections passed from backend.
				self.initDefaultActiveSelections( wrapperId );

				if ( 0 != event.target.value && self.limitSelectionHasReached( wrapperId ) ) {
					return false;
				}

				self.updateOrder( event.target, parseInt( event.target.value, 10 ), wrapperId );
				self.afterPositionChanged( wrapperId );
			});
		};

		this.updateOrder = function( target, fieldOrder, wrapperId ) {
			var parentOption = self.getElement( target, 'option' );

			self.updateOrderData( parentOption, wrapperId, fieldOrder );
			self.toggleDraggableOptionByOrderValue( parentOption, fieldOrder );
			self.resetValues( self.getElement( parentOption, 'container' ), wrapperId );
		};

		this.updateSelectValuesAfterDragDrop = function( container ) {
			var items = container.querySelectorAll( self.selectors.option ),
				wrapperId = self.getElement( container, 'wrapper' ).getAttribute( 'id' ),
				index, select;

			self.updateActiveSelectionOrderAfterDragDrop( items, wrapperId );

			for ( index = 0; index < items.length; index++ ) {
				select = self.getElement( items[ index ], 'select' );
				if ( 0 != select.value ) {
					items[ index ].dataset.order = index + 1;
					select.value = index + 1;
				}
				self.enableOrDisableSelectOptions( select, wrapperId );
			}
		};

		this.enableOrDisableSelectOptions = function( select, wrapperId ) {
			var index;

			if ( 'undefined' === typeof self.activeSelectionsOrder[ wrapperId ]) {
				return;
			}

			for ( index = 0; index < select.options.length; index++ ) {
				if ( -1 !== self.activeSelectionsOrder[ wrapperId ].indexOf( parseInt( select.options[ index ].value, 10 ) ) && ! select.options[ index ].selected ) {
					select.options[ index ].disabled = true;
				} else {
					select.options[ index ].disabled = false;
				}
			}
		};

		this.resetValues = function( parentElement, wrapperId ) {
			var index, fieldOrder, select,
				options = parentElement.querySelectorAll( self.selectors.option );

			for ( index = 0; index < options.length; index++ ) {
				select     = self.getElement( options[ index ], 'select' ),
				fieldOrder = parseInt( select.value, 10 );
				self.moveFieldOption( options[ index ], fieldOrder, wrapperId );
			}
		};

		this.moveFieldOption = function( option, fieldOrder, wrapperId ) {
			var optionClone     = option.cloneNode( true ),
				parentContainer = self.getElement( option, 'container' ),
				select          = self.getElement( optionClone, 'select' ),
				index           =  0 === fieldOrder ? -1 : self.findNextIndex( wrapperId, fieldOrder );

			parentContainer.removeChild( option );
			optionClone.classList.remove( 'frm-no-drag' );

			optionClone.dataset.order = fieldOrder;
			select.value              = fieldOrder;

			self.insertBeforeByIndex( parentContainer, optionClone, parseInt( index, 10 ) );
			self.enableOrDisableSelectOptions( select, wrapperId );
		};

		this.insertBeforeByIndex = function( parentElement, newElement, index ) {
			var referenceNode = parentElement.children[ index ];

			if ( referenceNode ) {
				parentElement.insertBefore( newElement, referenceNode );
				return;
			}
			parentElement.appendChild( newElement );
		};

		this.getElement = function( target, selector ) {
			var element;
			if ( 'undefined' === typeof self.selectors[ selector ]) {
				return null;
			}

			element = target.closest( self.selectors[ selector ]);

			if ( null === element ) {
				element = target.querySelector( self.selectors[ selector ]);
			}

			return element;
		};

		this.updateOrderData = function( option, wrapperId, newValue ) {
			var oldValue = 'undefined' !== typeof option.dataset.order ? parseInt( option.dataset.order, 10 ) : 0;
			self.updateActiveSelectionOrder( wrapperId, newValue, oldValue );
		};

		this.updateActiveSelectionOrderAfterDragDrop = function( items, wrapperId ) {
			var activeSelection = [],
				index;

			for ( index = 0; index < items.length; index++ ) {
				select = self.getElement( items[ index ], 'select' );
				if ( 0 != select.value ) {
					activeSelection.push( index + 1 );
				}
			}
			self.updateActiveSelectionOrder( wrapperId, activeSelection, null );
		};

		this.updateActiveSelectionOrder = function( wrapperId, value, oldValue ) {
			if ( Array.isArray( value ) ) {
				self.activeSelectionsOrder[ wrapperId ] = value;
				return;
			}

			if ( null !== oldValue ) {
				self.activeSelectionsOrder[ wrapperId ] = self.activeSelectionsOrder[ wrapperId ].filter( function( item ) {
					return item !== oldValue;
				});
			}

			if ( 0 !== value ) {
				self.activeSelectionsOrder[ wrapperId ].push( parseInt( value, 10 ) );
			}

			self.activeSelectionsOrder[ wrapperId ] = self.sortAsc( self.activeSelectionsOrder[ wrapperId ]);
		};

		this.initDefaultActiveSelections = function( wrapperId ) {
			var defaultsInputData = document.querySelector( '#' + wrapperId + ' ' + self.selectors.activeSelections ),
				defaults;

			if ( 'undefined' !== typeof self.activeSelectionsOrder[ wrapperId ]) {
				return;
			}

			if ( null !== defaultsInputData && '' != defaultsInputData.value ) {
				defaults = defaultsInputData.value.split( ',' ).map( function( value ) {
					return parseInt( value, 10 );
				});
				self.activeSelectionsOrder[ wrapperId ] = defaults;
				return;
			}

			self.activeSelectionsOrder[ wrapperId ] = [];
		};

		this.sortAsc = function( array ) {
			return array.sort( function( a, b ) {
				return a - b;
			});
		};

		this.toggleDraggableOptionByOrderValue = function( option, orderValue ) {
			if ( 0 !== orderValue ) {
				option.classList.add( 'frm-ranking-draggable-option' );
				return;
			}
			option.classList.remove( 'frm-ranking-draggable-option' );
		};

		this.findNextIndex = function( wrapperId, fieldOrder ) {
			var i, array;
			if ( 'undefined' === typeof self.activeSelectionsOrder[ wrapperId ]) {
				return 0;
			}

			array = self.activeSelectionsOrder[ wrapperId ];
			if ( 0 === array.length ) {
				return 0;
			}
			for ( i = 0; i < array.length - 1; i++ ) {
				if ( fieldOrder - 1 < array[ i ]) {
					return i;
				}
			}
			// If no missing index found, return the next index after the last one
			return array.length - 1;
		};

		this.findNextMissingOrder = function( wrapperId ) {
			var i = 0,
				array;

			if ( 'undefined' === typeof self.activeSelectionsOrder[ wrapperId ]) {
				return 1;
			}

			array = self.activeSelectionsOrder[ wrapperId ];
			if ( 1 !== array[0]) {
				return 1;
			}

			for ( i = 0; i < array.length - 1; i++ ) {
				if ( array[ i + 1 ] - array[ i ] > 1 ) {
					return array[ i ] + 1;
				}
			}

			return array[ array.length - 1 ] + 1;
		};

		this.removeErrorStyle = function( wrapper ) {
			var error = wrapper.querySelector( '.frm_error' );

			if ( wrapper.classList.contains( 'frm_blank_field' ) ) {
				wrapper.classList.remove( 'frm_blank_field' );
				if ( null !== error ) {
					error.remove();
				}
			}
		};

		this.init = function() {
			self.initOptions();
			jQuery( self.selectors.wrapper ).sortable({
				tolerance: 'intersect',
				handle: '.frm-drag',
				axis: 'y',
				items: '.frm-ranking-draggable-option',
				cancel: '.frm-no-drag',
				update: function( event ) {
					self.updateSelectValuesAfterDragDrop( event.target );
					self.afterPositionChanged( event.target.getAttribute( 'id' ) );
				}
			});
		};

		jQuery( document ).on( 'frmAfterAddRow', function() {
			self.init();
		});
		this.init();
		this.onPositionChange();

	}
	new frmSurveysRankingField();
}() );
