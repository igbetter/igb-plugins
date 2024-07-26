<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
.frmcal {
	padding-top:30px;
}

.frmcal-title {
	font-size:116%;
}

.frmcal table.frmcal-calendar {
	border-collapse:collapse;
	margin-top:20px;
	color:<?php echo esc_html( $defaults['text_color'] ); ?>;
}

.frmcal table.frmcal-calendar,
.frmcal table.frmcal-calendar tbody tr td {
	border:1px solid <?php echo esc_html( $defaults['border_color'] ); ?>;
}

.frmcal table.frmcal-calendar,
.frmcal,
.frmcal-header {
	width:100%;
}

.frmcal-header {
	text-align:center;
}

.frmcal-prev {
	margin-right:10px;
}

.frmcal-prev,
.frmcal-dropdown {
	float:left;
}

.frmcal-dropdown {
	margin-left:5px;
}

.frmcal-next {
	float:right;
}

.frmcal table.frmcal-calendar thead tr th {
	text-align:center;
	padding:2px 4px;
}

.frmcal table.frmcal-calendar tbody tr td {
	height:110px;
	width:14.28%;
	vertical-align:top;
	padding:0 !important;
	color:<?php echo esc_attr( $defaults['text_color'] ); ?>;
	font-size:12px;
}

table.frmcal-calendar .frmcal_date {
	background-color:<?php echo esc_html( empty( $defaults['bg_color'] ) ? 'transparent' : $defaults['bg_color'] ); ?>;
	padding:0 5px;
	text-align:right;
	box-shadow:0 2px 5px <?php echo esc_html( $defaults['border_color'] ); ?>;
}

table.frmcal-calendar .frmcal-today .frmcal_date {
	background-color:<?php echo esc_html( $defaults['bg_color_active'] ); ?>;
	padding:0 5px;
	text-align:right;
	box-shadow:0 2px 5px <?php echo esc_html( $defaults['border_color_active'] ); ?>;
}

.frmcal_day_name,
.frmcal_num {
	display:inline;
}

.frmcal-content {
	padding:2px 4px;
}