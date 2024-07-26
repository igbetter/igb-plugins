<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
.with_frm_style .iti {
	width: 100%;
	width: var(--field-width);
}
.with_frm_style .iti__country {
	font-size: 14px;
	font-size: var(--field-font-size);
}
.with_frm_style .iti__selected-country {
	background-color: unset !important;
}
.with_frm_style .iti__flag {
	background-image: url('<?php echo esc_url( FrmProAppHelper::relative_plugin_url() ); ?>/images/intl-tel-input/flags.png?1');
}
@media (min-resolution: 2x) {
	.with_frm_style .iti__flag {
		background-image: url('<?php echo esc_url( FrmProAppHelper::relative_plugin_url() ); ?>/images/intl-tel-input/flags@2x.png?1');
	}
}
.with_frm_style .iti__globe {
	background-image: url('<?php echo esc_url( FrmProAppHelper::relative_plugin_url() ); ?>/images/intl-tel-input/globe.png');
}
@media (min-resolution: 2x) {
	.with_frm_style .iti__globe {
		background-image: url('<?php echo esc_url( FrmProAppHelper::relative_plugin_url() ); ?>/images/intl-tel-input/globe@2x.png');
	}
}
