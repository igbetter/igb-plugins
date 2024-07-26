<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsLayoutHelper {

	const ONE_COLUMN   = 1;
	const TWO_COLUMN   = 2;
	const THREE_COLUMN = 3;
	const FOUR_COLUMN  = 4;
	const ONE_THREE    = 5;
	const THREE_ONE    = 6;
	const ONE_TWO_ONE  = 7;
	const ONE_TWO      = 8;
	const TWO_ONE      = 9;

	/**
	 * @var object
	 */
	protected $view;

	/**
	 * @var string either 'grid' or 'table'.
	 */
	protected $type;

	/**
	 * @var array string content by box id.
	 */
	protected $content_by_box;

	/**
	 * @var array array by box id.
	 */
	protected $styles_by_box;

	/**
	 * @var array any additional information (uncovered by content and style) about a box by box id.
	 */
	protected $additional_information_by_box;

	protected $layout_data;

	/**
	 * @var array used to track processed shortcodes so maybe_filter_content doesn't need to get called in a loop.
	 */
	private $processed_names;

	/**
	 * @var bool
	 */
	private $is_responsive;

	public function __construct( $view, $type = 'grid' ) {
		$this->view = $view;
		$this->type = $type;

		if ( 'table' === $type ) {
			$options               = get_post_meta( $this->view->ID, 'frm_options', true );
			$this->processed_names = array();
			$this->is_responsive   = ! empty( $options['table_responsive'] );
		}
	}

	/**
	 * @param string $key unique identifier (currently just "listing" or "detail").
	 * @return string
	 */
	public function get_layout_data( $key ) {
		return $this->get_layout_data_from_database( $key );
	}

	public function set_layout_data( $type ) {
		$layout = FrmViewsLayout::get_layouts_for_view( $this->view->ID, $type );
		if ( $layout && is_object( $layout ) ) {
			$this->layout_data = json_decode( $layout->data );
		}
	}

	public function force_layout_data( $layout_data ) {
		$this->layout_data = $layout_data;
	}

	private function get_layout_data_from_database( $key ) {
		$templates = FrmViewsLayout::get_layouts_for_view( $this->view->ID );
		if ( ! $templates ) {
			return $this->get_single_column_layout_data();
		}

		foreach ( $templates as $template ) {
			if ( $key === $template->type ) {
				return json_decode( $template->data );
			}
		}

		return $this->get_single_column_layout_data();
	}

	private function get_single_column_layout_data() {
		return '';
	}

	/**
	 * @param array  $content
	 * @param string $type
	 */
	public function flatten( $content, $type ) {
		if ( ! is_array( $content ) ) {
			return $content;
		}

		$layout = FrmViewsLayout::get_layouts_for_view( $this->view->ID, $type );
		if ( ! $layout || ! is_object( $layout ) ) {
			$layout       = new stdClass();
			$layout->data = '[{"boxes":[{"id":0}],"layout":1}]';
		}

		$this->index_content_by_box( $content );
		$layout_data = json_decode( $layout->data );

		$this->layout_data = $layout_data;

		return $this->get_output( $layout_data );
	}

	/**
	 * @param array $layout_data
	 * @return string
	 */
	public function get_output( $layout_data ) {
		$output = '';
		foreach ( $layout_data as $row ) {
			if ( isset( $row->boxes ) ) {
				$output .= $this->get_row_content( $row );
			}
		}
		return $output;
	}

	/**
	 * @param object $box
	 * @return string
	 */
	private function get_box_content( $box ) {
		if ( ! isset( $box->id ) ) {
			return '';
		}

		$box_content = '';

		if ( ! empty( $box->rows ) ) {
			foreach ( $box->rows as $row ) {
				$box_content .= $this->get_row_content( $row );
			}
		} elseif ( isset( $this->content_by_box[ $box->id ] ) ) {
			$box_content = $this->content_by_box[ $box->id ];
		}

		return $box_content;
	}

	/**
	 * @param object $box
	 * @return string
	 */
	private function get_box_style( $box ) {
		if ( ! isset( $box->id ) ) {
			return '';
		}

		$box_style = '';

		if ( isset( $this->styles_by_box[ $box->id ] ) ) {
			foreach ( $this->styles_by_box[ $box->id ] as $key => $value ) {
				if ( $value || '0' === $value ) {
					$box_style .= self::convert_camel_case_style( $key, 'grid-cell' ) . ': ' . $value . ';';
				}
			}
		}

		return $box_style;
	}

	/**
	 * Convert style names from their camel case setting key to the format required to work with CSS.
	 *
	 * @since 5.4.1 Added a new $context param.
	 *
	 * @param string $key
	 * @param string $context
	 * @return string
	 */
	public static function convert_camel_case_style( $key, $context = '' ) {
		switch ( $key ) {
			case 'backgroundColor':
				return 'background-color';
			case 'borderColor':
				return 'border-color';
			case 'borderWidth':
				if ( 'grid-top-level' === $context ) {
					/**
					 * @since 5.3.2 this was switched to border-thickness instead of border-width to avoid a WordPress styling conflict.
					 * The conflict style rule matches anything "border-width" and forces a border-style:
					 * html :where([style*="border-width"]) { border-style: solid; }
					 * At the top level this is used to define the --v-tl-border-thickness variable name.
					 */
					return 'border-thickness';
				}
				// Grid cells use border-width styles directly inside style tags instead of using CSS vars like the top level.
				return 'border-width';
			case 'borderRadius':
				return 'border-radius';
			case 'borderStyle':
				return 'border-style';
			case 'fontSize':
				return 'font-size';
			case 'lineHeight':
				return 'line-height';
		}
		return $key;
	}

	/**
	 * @param object $row
	 * @return string
	 */
	private function get_row_content( $row ) {
		$row_output = '';
		foreach ( $row->boxes as $box_index => $box ) {
			$box_content = $this->get_box_content( $box );

			if ( 'grid' === $this->type ) {
				$wrapper_class = self::get_layout_wrapper_class( $row->layout, $box_index );
				$wrapper_attrs = ' class="' . esc_attr( $wrapper_class ) . '"';

				$style = $this->get_box_style( $box );
				if ( $style ) {
					$wrapper_attrs .= ' style="' . esc_attr( $style ) . '"';
				}
			} else {
				$wrapper_attrs = '';
			}

			$tag          = 'grid' === $this->type ? 'div' : 'td';
			$inline_style = '';

			if ( 'td' === $tag ) {
				if ( $this->include_details_link( $box ) ) {
					$box_content = '<a href="[detaillink]">' . $box_content . '</a>';
				}
				$name = $this->get_table_column_name( $box );
				if ( $this->is_responsive ) {
					$processed_name = $this->process_table_column_name( $name );
					$processed_name = sanitize_text_field( $processed_name );
					$inline_style   = ' style=\'--v-responsive-table-label:"' . esc_attr( $processed_name ) . '";\'';
				}
			}

			$row_output .= '<' . $tag . $wrapper_attrs . $inline_style . '>' . $box_content . '</' . $tag . '>';
		}
		if ( 'grid' === $this->type ) {
			$tag   = 'div';
			$class = ' class="frm_grid_container"';
		} else {
			$tag   = 'tr';
			$class = '';
		}
		return '<' . $tag . $class . '>' . $row_output . '</' . $tag . '>';
	}

	/**
	 * Process the shortcodes in a table name so they can be used in an inline style for responsive tables.
	 *
	 * @param string $name the name we're processing, with the shortcodes included (possibly).
	 * @return string
	 */
	private function process_table_column_name( $name ) {
		if ( ! isset( $this->processed_names[ $name ] ) ) {
			$this->processed_names[ $name ] = $name;
			FrmViewsDisplaysController::maybe_filter_content( array( 'filter' => 0 ), $this->processed_names[ $name ] );
		}
		return $this->processed_names[ $name ];
	}

	/**
	 * @param stdClass $box
	 * @return bool
	 */
	private function include_details_link( $box ) {
		return ! empty( $this->additional_information_by_box[ $box->id ] ) && ! empty( $this->additional_information_by_box[ $box->id ]['detailsLink'] );
	}

	/**
	 * @param stdClass $box
	 * @return string
	 */
	private function get_table_column_name( $box ) {
		return ! empty( $this->additional_information_by_box[ $box->id ]['name'] ) ? $this->additional_information_by_box[ $box->id ]['name'] : '';
	}

	public function index_content_by_box( $content ) {
		if ( ! is_array( $content ) ) {
			return;
		}

		$indexed_content         = array();
		$indexed_styles          = array();
		$indexed_additional_info = array();
		foreach ( $content as $box_data ) {
			if ( ! isset( $box_data['box'] ) ) {
				continue;
			}

			$box_id = $box_data['box'];

			if ( ! empty( $box_data['content'] ) ) {
				$indexed_content[ $box_id ] = $box_data['content'];
			}

			if ( ! empty( $box_data['style'] ) ) {
				$indexed_styles[ $box_id ] = $box_data['style'];
			}

			if ( ! empty( $box_data['name'] ) ) {
				if ( ! isset( $indexed_additional_info[ $box_id ] ) ) {
					$indexed_additional_info[ $box_id ] = array();
				}
				$indexed_additional_info[ $box_id ]['name'] = $box_data['name'];
			}

			if ( ! empty( $box_data['detailsLink'] ) ) {
				if ( ! isset( $indexed_additional_info[ $box_id ] ) ) {
					$indexed_additional_info[ $box_id ] = array();
				}
				$indexed_additional_info[ $box_id ]['detailsLink'] = $box_data['detailsLink'];
			}
		}
		$this->content_by_box                = $indexed_content;
		$this->styles_by_box                 = $indexed_styles;
		$this->additional_information_by_box = $indexed_additional_info;
	}

	/**
	 * @param mixed $layout
	 * @param int   $index
	 * @return string
	 */
	private static function get_layout_wrapper_class( $layout, $index ) {
		if ( is_string( $layout ) ) {
			$layout = intval( $layout );
		}

		switch ( $layout ) {
			case self::ONE_COLUMN:
				return 'frm12';
			case self::TWO_COLUMN:
				return 'frm6';
			case self::THREE_COLUMN:
				return 'frm4';
			case self::FOUR_COLUMN:
				return 'frm3';
			case self::ONE_THREE:
				return 0 === $index ? 'frm3' : 'frm9';
			case self::THREE_ONE:
				return 0 === $index ? 'frm9' : 'frm3';
			case self::ONE_TWO_ONE:
				return 1 === $index ? 'frm6' : 'frm3';
			case self::ONE_TWO:
				return 0 === $index ? 'frm4' : 'frm8';
			case self::TWO_ONE:
				return 0 === $index ? 'frm8' : 'frm4';
			default:
				return '';
		}
	}

	/**
	 * @return string
	 */
	public function table_headers() {
		if ( ! is_array( $this->layout_data ) ) {
			return '';
		}

		$html = '<tr>';
		$row  = reset( $this->layout_data );
		if ( isset( $row->boxes ) ) {
			foreach ( $row->boxes as $box ) {
				$name  = isset( $this->additional_information_by_box[ $box->id ]['name'] ) ? $this->additional_information_by_box[ $box->id ]['name'] : '';
				$html .= '<th>' . FrmAppHelper::kses( $name, 'all' ) . '</th>';
			}
		}
		$html .= '</tr>';
		return $html;
	}

	/**
	 * @param string $headers
	 * @return string
	 */
	public function get_table_header_content( $headers ) {
		$options     = get_post_meta( $this->view->ID, 'frm_options', true );
		$table_class = 'with_frm_style';

		if ( ! empty( $options['table_responsive'] ) ) {
			$table_class .= ' frm-responsive-table';
		}

		if ( ! isset( $options['table_row_style'] ) ) {
			$table_class .= ' frm-alt-table'; // Zebra-stripe is the default.
		} elseif ( $options['table_row_style'] ) {
			$table_class .= ' ' . $options['table_row_style'];
		}

		if ( ! empty( $options['table_classes'] ) ) {
			$table_class .= ' ' . $options['table_classes'];
		}

		/**
		 * Filters the table view CSS class.
		 *
		 * @since 5.4
		 *
		 * @param string $table_class The table class.
		 * @param array  $args        {
		 *     Args.
		 *
		 *     @type array $options View options.
		 * }
		 */
		$table_class = apply_filters( 'frm_views_table_class', $table_class, compact( 'options' ) );

		return '<table class="' . esc_attr( $table_class ) . '"><thead>' . $headers . '</thead>';
	}
}
