<?php
/**
 * CSS for the PDF entry table file
 *
 * @package FrmPdfs
 *
 * @var object $entry Entry object.
 * @var array  $defaults Default style data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
.frm_pdf_table {
	font-size: 1.1em;
	line-height: 1.5em;
	border: 1px solid <?php echo esc_html( $defaults['border_color'] ); ?>;
}

.frm_pdf_tr:nth-child(2n) {
	background-color: <?php echo esc_html( FrmStylesHelper::adjust_brightness( $defaults['border_color'], 45 ) ); ?>;
}

.frm_pdf_td {
	text-align: left;
	padding: 20px;
	vertical-align: top;
}

.frm-child-row {
	border-left: 3px solid #dbddE7;
}

.field-label {
	font-weight: 700;
}
