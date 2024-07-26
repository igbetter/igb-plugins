<?php
/**
 * Entry formatter for PDF file
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsEntryFormatter
 */
class FrmPdfsEntryFormatter extends FrmProEntryFormatter {

	/**
	 * Constructor.
	 *
	 * @param array $atts See {@see FrmEntriesController::show_entry_shortcode()}.
	 */
	public function __construct( $atts ) {
		$atts['format'] = 'text';
		parent::__construct( $atts );
	}

	/**
	 * Set the table_generator property.
	 *
	 * @param array $atts The atts of entry formatter.
	 */
	protected function init_table_generator( $atts ) {
		$this->table_generator = new FrmPdfsTableHTMLGenerator( 'entry', $atts );
	}

	/**
	 * Add a row in an HTML table
	 *
	 * @param array  $value_args The args:
	 *   $value_args = [
	 *     'label' => (string) The label. Required
	 *     'value' => (mixed) The value to add. Required
	 *     'field_type' => (string) The field type. Blank string if not a field.
	 *   ].
	 * @param string $content The formatted content.
	 */
	protected function add_html_row( $value_args, &$content ) {
		if ( is_callable( array( $this, 'maybe_process_shortcodes_in_label' ) ) ) {
			$value_args['label'] = $this->maybe_process_shortcodes_in_label( $value_args['label'] );
		}

		$display_value = $this->prepare_display_value_for_html_table( $value_args['value'], $value_args['field_type'] );
		if ( 'likert' === $value_args['field_type'] ) {
			$value_args['label'] = '<h3>' . $value_args['label'] . '</h3>';
		}
		$display_value = '<div class="field-label">' . $value_args['label'] . '</div><div class="field-value">' . $display_value . '</div>';

		$content .= $this->table_generator->generate_single_cell_table_row( $display_value );
	}

	/**
	 * Checks if section has children content or just the Section heading.
	 *
	 * @since 1.0.2
	 *
	 * @param string $section_substring Section substring.
	 * @return bool
	 */
	protected function section_heading_has_children( $section_substring ) {
		return substr_count( $section_substring, '<div class="frm_pdf_tr' ) > 1;
	}

	/**
	 * Prepare the display value for extra fields an HTML table.
	 *
	 * @since 2.0.1
	 *
	 * @param FrmProFieldValue $field_value   Field value object.
	 * @param string           $display_value Display value.
	 */
	protected function prepare_html_display_value_for_extra_fields( $field_value, &$display_value ) {
		parent::prepare_html_display_value_for_extra_fields( $field_value, $display_value );

		if ( 'html' === $field_value->get_field_type() ) {
			$display_value = $this->maybe_process_shortcodes_in_label( $display_value );
		}
	}
}
