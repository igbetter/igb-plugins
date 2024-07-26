<?php
/**
 * General CSS for the PDF file
 *
 * @package FrmPdfs
 *
 * @var object $entry    Entry object.
 * @var array  $defaults Default style data.
 * @var array  $args     The shortcode attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$font_family = ! empty( $defaults['font'] ) ? $defaults['font'] : '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
?>
body {
	background: #fff;
	color: #282F36;
	font-family: <?php echo FrmAppHelper::kses( $font_family ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>, 'DejaVu Sans', sans-serif;
	font-size: 13px;
	line-height: 1.4em;
	min-width: 600px;
}

h3 {
	font-size: 1.2em;
	margin: 0;
}

table.with_frm_style {
	font-size: 13px;
}

table.with_frm_style th,
table.with_frm_style td {
	width: auto;
	padding: 10px;
}

input, button, .frm_button,
.frm_pagination, .frm_pagination_cont, .frm_no_print {
	display: none !important;
}

/* Grid layout */
.frm_grid_container {
	display: table !important;
	width: 100%;
}

.frm_grid_container_no_table,
.frm-grid-view {
	display: block !important;
}

.frm_grid_container > div:not(.frm12),
.frm_grid_container .frm1,
.frm_grid_container .frm2,
.frm_grid_container .frm3,
.frm_grid_container .frm4,
.frm_grid_container .frm5,
.frm_grid_container .frm6,
.frm_grid_container .frm7,
.frm_grid_container .frm8,
.frm_grid_container .frm9,
.frm_grid_container .frm10,
.frm_grid_container .frm11 {
	display: table-cell;
}

.frm1 {
	width: 8.3%;
}

.frm2 {
	width: 16.6%;
}

.frm3 {
	width: 25%;
}

.frm4 {
	width: 33.3%;
}

.frm5 {
	width: 41.6%;
}

.frm6 {
	width: 50%;
}

.frm7 {
	width: 58.3%;
}

.frm8 {
	width: 66.6%;
}

.frm9 {
	width: 75%;
}

.frm10 {
	width: 83.3%;
}

.frm11 {
	width: 91.6%;
}

.frm12 {
	width: 100%;
}

img {
	max-width: 100%;
	height: auto;
}

/* Override CSS */
.frm-grid-view > div {
	border: none;
	padding: 0;
}

/* These are fallback values */
.frm-grid-view > div > div {
	border: 1px solid #efefef;
	padding: 10px;
}

.frm-star-group {
	display: inline-block; /* Stars don't show correctly in a float right container. Change to text-align left to fix. */
	width: 145px;
}

.frm-star-group .star-rating {
	height: 30px; /* Fix star icons don't display full height. */
}

.frm-star-group > .frmsvg + .frmsvg {
	margin-left: 5px;
}

<?php FrmPdfsAppHelper::print_pagination_css( $args ); ?>
