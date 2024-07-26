( function() {
	var __, maybeCreateModal, tag, div, button, a, activePage;

	function initialize() {
		setupExternalFunctionReferences();
		showModalPage( activePage = 1 );
	}

	function showModalPage( number ) {
		var contentFunctions, modalDetails;

		contentFunctions = getContentFunctions();
		modalDetails = contentFunctions[ number ]();

		maybeCreateModal({
			id: 'frm_views_editor_welcome_modal',
			content: getModalPage( modalDetails )
		});
	}

	function getContentFunctions() {
		var contentFunctions;
		if ( frmViewsEditorInfo.isGridType ) {
			contentFunctions = {
				1: getModalPageOne,
				2: getLayoutBuilderPage,
				3: getBoxContentModalPage,
				4: getFilterSortPage,
				5: getBeforeContentPage
			};
		} else {
			contentFunctions = {
				1: getModalPageOne,
				2: getBoxContentModalPage,
				3: getFilterSortPage,
				4: getBeforeContentPage
			};
		}
		return contentFunctions;
	}

	function getModalPageOne() {
		return {
			title: __( 'Welcome to Visual Views', 'formidable-views' ),
			description: __( 'Use Views to build any custom web application you can imagine. It starts when users submit a form. The user-generated content can be used in job boards, staff directories, testimonials, recipes, and so much more.', 'formidable-views' ),
			graphic: 'page-1'
		};
	}

	function getLayoutBuilderPage() {
		return {
			title: __( 'Build a custom layout to display entries', 'formidable-views' ),
			description: __( 'Create a unique card layout to present form data. This card will repeat for each form entry.', 'formidable-views' ),
			graphic: 'layout-builder'
		};
	}

	function getBoxContentModalPage() {
		return {
			title: __( 'Add data to the View', 'formidable-views' ),
			description: __( 'Click the plus icon then add or edit content in each box. The editor modal gives you access to the form data to insert.', 'formidable-views' ),
			graphic: 'box-content'
		};
	}

	function getFilterSortPage() {
		return {
			title: __( 'Filter and sort form entries', 'formidable-views' ),
			description: __( 'Add groups of conditions to get the exact entries you need.', 'formidable-views' ),
			graphic: 'filter'
		};
	}

	function getBeforeContentPage() {
		return {
			title: __( 'Add content before the repeating sections', 'formidable-views' ),
			description: __( 'Add search boxes, table headers, and other content before the list of entries.', 'formidable-views' ),
			graphic: 'before-content'
		};
	}

	function getModalPage( details ) {
		var title, description, graphic;

		title = details.title;
		description = details.description;
		graphic = details.graphic;

		return div({
			padding: '40px 80px',
			textAlign: 'center',
			children: [
				getSVG( 'welcome-modal/' + graphic + '-graphic' ),
				div({
					text: title,
					fontSize: '24px',
					color: '#282F36',
					margin: '20px 0'
				}),
				div({
					text: description,
					fontSize: '14px',
					fontWeight: 300,
					lineHeight: '18px',
					color: 'rgba(63, 75, 91, 0.8)',
					margin: '0 0 10px'
				}),
				getPageIndicator(),
				getContinueButton(),
				div({
					margin: '10px 0 0',
					child: getSkipButton()
				})
			]
		});
	}

	function getSVG( filename ) {
		var args = { child: tag( 'img' ) };
		args.child.setAttribute( 'src', frmViewsEditorInfo.svgPath + filename + '.svg' );
		args.child.style.maxWidth = '100%';
		return div( args );
	}

	function getPageIndicator() {
		var children, numberOfPages, index;
		numberOfPages = getNumberOfPages();
		children = [];
		for ( index = 0; index < numberOfPages; ++index ) {
			children.push( getDotForPageIndicator( index ) );
		}
		return div({
			margin: '20px 0',
			children: children
		});
	}

	function getNumberOfPages() {
		return Object.keys( getContentFunctions() ).length;
	}

	function getDotForPageIndicator( index ) {
		var page, dot, isActive;

		page = index + 1;
		dot = div({
			id: 'frm_page_dot_' + index,
			onclick: function() {
				showModalPage( activePage = page );
			}
		});
		isActive = activePage === page;

		dot.style.width = '10px';
		dot.style.height = '10px';
		dot.style.backgroundColor = isActive ? '#4199FD' : '#D9DCE8';
		dot.style.borderRadius = '10px';
		dot.style.display = 'inline-block';
		if ( ! isActive ) {
			dot.style.cursor = 'pointer';
		}
		if ( ! isLastPage( page ) ) {
			dot.style.marginRight = '5px';
		}
		return dot;
	}

	function getContinueButton() {
		return button({
			id: 'frm_continue_view_editor_welcome',
			text: __( 'Continue', 'formidable-views' ),
			onclickPreventDefault: function() {
				if ( isLastPage( activePage ) ) {
					closeModal( 'frm_views_editor_welcome_modal' );
				} else {
					showModalPage( ++activePage );
				}
			}
		});
	}

	function isLastPage( number ) {
		return getNumberOfPages() === number;
	}

	function closeModal( modalId ) {
		jQuery( '#' + modalId ).dialog( 'close' );
	}

	function getSkipButton() {
		return a({
			id: 'frm_skip_view_editor_welcome',
			text: __( 'Skip', 'formidable-views' ),
			onclickPreventDefault: function() {
				closeModal( 'frm_views_editor_welcome_modal' );
			},
			color: 'rgba(40, 47, 54, 0.5)'
		});
	}

	function setupExternalFunctionReferences() {
		__ = wp.i18n.__;
		tag = frmViewsDom.tag;
		div = frmViewsDom.div;
		button = frmViewsDom.button;
		a = frmViewsDom.a;
		maybeCreateModal = frmViewsModal.maybeCreateModal;
	}

	jQuery( document ).on( 'frmViewsEditorReady', '#view-editor', initialize );
}() );
