<?php
/**
 * Table HTML generator
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsTableHTMLGenerator
 */
class FrmPdfsTableHTMLGenerator extends FrmTableHTMLGenerator {

	/**
	 * Generate a table header.
	 *
	 * @return string
	 */
	public function generate_table_header() {
		return '<div class="frm_pdf_table" ' . $this->table_style . '>' . "\r\n";
	}

	/**
	 * Generate a table footer.
	 *
	 * @return string
	 */
	public function generate_table_footer() {
		return '</div><!-- End .frm_pdf_table -->';
	}

	/**
	 * Generate a single cell row for an HTML table.
	 *
	 * @param string $value Cell value.
	 * @return string
	 */
	public function generate_single_cell_table_row( $value ) {
		$row = '<div' . $this->tr_style();
		$row .= $this->add_row_class();
		$row .= '>';
		$row .= '<div class="frm_pdf_td" colspan="2"' . $this->td_style . '>' . $value . '</div><!-- End .frm_pdf_td -->';
		$row .= '</div><!-- End .frm_pdf_tr -->' . "\r\n";

		$this->switch_odd();

		return $row;
	}

	/**
	 * Add classes to the tr.
	 *
	 * @param bool $empty If the value in the row is blank.
	 * @return string
	 */
	protected function add_row_class( $empty = false ) {
		$class = 'frm_pdf_tr';
		if ( $empty ) {
			// Only add this class on two cell rows.
			$class .= ' frm-empty-row';
		}
		if ( $this->is_child ) {
			$class .= ' frm-child-row';
		}
		if ( $class ) {
			$class = ' class="' . trim( $class ) . '"';
		}
		return $class;
	}
}
