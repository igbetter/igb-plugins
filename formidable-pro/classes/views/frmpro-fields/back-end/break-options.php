<?php
/**
 * Backend settings for Page Break field
 *
 * @since 6.9
 *
 * @package FormidablePro
 *
 * @var array $field Field array.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$transition = FrmProFormsHelper::get_form_option( $field['form_id'], 'transition' );
$html_id    = 'frm_prev_label_' . absint( $field['id'] );
?>
<p class="frm_form_field">
	<label for="<?php echo esc_attr( $html_id ); ?>"><?php esc_html_e( 'Previous Label', 'formidable-pro' ); ?></label>

	<input
		type="text"
		id="<?php echo esc_attr( $html_id ); ?>"
		name="field_options[prev_label_<?php echo absint( $field['id'] ); ?>]"
		value="<?php echo esc_attr( $field['prev_label'] ); ?>"
	/>
</p>

<?php $html_id = 'frm_transition_' . absint( $field['id'] ); ?>
<p>
	<label for="<?php echo esc_attr( $html_id ); ?>">
		<?php esc_html_e( 'Page Turn Transitions', 'formidable-pro' ); ?>
		<span class="frm_help frm_icon_font frm_tooltip_icon frm_tooltip_expand" title="<?php esc_attr_e( 'This setting applies to all page break fields in this form.', 'formidable-pro' ); ?>"></span>
	</label>

	<select
		id="<?php echo esc_attr( $html_id ); ?>"
		name="field_options[transition_<?php echo absint( $field['id'] ); ?>]"
		class="frm_page_transition_setting"
	>
		<option value=""><?php esc_html_e( 'None', 'formidable-pro' ); ?></option>
		<option value="slidein" <?php selected( $transition, 'slidein' ); ?>>
			<?php esc_html_e( 'Slide horizontally', 'formidable-pro' ); ?>
		</option>
		<option value="slideup" <?php selected( $transition, 'slideup' ); ?>>
			<?php esc_html_e( 'Slide vertically', 'formidable-pro' ); ?>
		</option>
	</select>
</p>
