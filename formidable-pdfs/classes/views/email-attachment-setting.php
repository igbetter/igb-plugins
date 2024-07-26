<?php
/**
 * PDF email attachment setting
 *
 * @package FrmPdfs
 *
 * @var object $form_action Form action object.
 * @var array  $pass_args   Pass args.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( method_exists( 'FrmProHtmlHelper', 'admin_toggle' ) ) {
	$method = 'admin_toggle';
} else {
	$method = 'toggle';
}
?>
<div style="margin-top: 15px;">
	<?php
	call_user_func(
		array( 'FrmProHtmlHelper', $method ),
		'frm_attach_pdf',
		$pass_args['action_control']->get_field_name( 'attach_pdf' ),
		array(
			'div_class' => 'with_frm_style frm_toggle',
			'checked'   => FrmPdfsEmailActionController::is_pdf_attachment_enabled( $form_action ),
			'echo'      => true,
		)
	);
	?>
	<label id="frm_attach_pdf_label" for="frm_attach_pdf">
		<?php esc_html_e( 'Attach PDF of entry to email', 'formidable-pdfs' ); ?>
	</label>
</div>
<?php
unset( $method );
