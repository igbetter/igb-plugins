<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Template for showing upgrade modal for Geolocation add on.
 *
 * @since 6.9
 * @package FormidablePro
 */
$upgrade = wp_json_encode( FrmProAddonsController::install_link( 'geo' ) );
$params  = array(
	'data-oneclick' => $upgrade,
	'data-upgrade'  => __( 'Geolocation fields', 'formidable-pro' ),
);
?>
<p class="frm12 frm_form_field">
	<label class="frm_show_upgrade" <?php FrmAppHelper::array_to_html_params( $params, true ); ?>>
		<input type="checkbox"/>
		<?php esc_html_e( 'Add address autocomplete', 'formidable-pro' ); ?>
	</label>
</p>
<p class="frm6 frm_form_field">
	<label class="frm_show_upgrade" <?php FrmAppHelper::array_to_html_params( $params, true ); ?>>
		<input type="checkbox" />
		<?php esc_html_e( 'Show map', 'formidable-pro' ); ?>
	</label>
</p>
<p class="frm6 frm_form_field">
	<label class="frm_inline_label frm_help frm_show_upgrade" <?php FrmAppHelper::array_to_html_params( $params, true ); ?>>
		<input type="checkbox" />
		<?php esc_html_e( 'Use visitor location', 'formidable-pro' ); ?>
	</label>
</p>
