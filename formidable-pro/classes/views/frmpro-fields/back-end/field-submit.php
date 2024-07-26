<?php
/**
 * Submit field Pro settings.
 *
 * @package FormidablePro
 * @since 6.9
 *
 * @var array $field Field array.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_form_field">
	<label for="edit_text_<?php echo esc_attr( $field['id'] ); ?>">
		<?php esc_html_e( 'Update Button Text', 'formidable-pro' ); ?>
	</label>
	<input
		type="text"
		id="edit_text_<?php echo esc_attr( $field['id'] ); ?>"
		name="field_options[edit_text_<?php echo esc_attr( $field['id'] ); ?>]"
		value="<?php echo esc_attr( $field['edit_text'] ); ?>"
	/>
</p>

<p class="frm_form_field">
	<label for="align_<?php echo esc_attr( $field['id'] ); ?>">
		<?php esc_html_e( 'Submit Button Position', 'formidable-pro' ); ?>
	</label>
	<select
		id="align_<?php echo esc_attr( $field['id'] ); ?>"
		name="field_options[align_<?php echo esc_attr( $field['id'] ); ?>]"
	>
		<option value=""><?php esc_html_e( 'Default', 'formidable-pro' ); ?></option>
		<option value="center" <?php selected( $field['align'], 'center' ); ?>>
			<?php esc_html_e( 'Center', 'formidable-pro' ); ?>
		</option>
		<option value="full" <?php selected( $field['align'], 'full' ); ?>>
			<?php esc_html_e( 'Full Width', 'formidable-pro' ); ?>
		</option>
		<option value="inline" <?php selected( $field['align'], 'inline' ); ?>>
			<?php esc_html_e( 'Inline', 'formidable-pro' ); ?>
		</option>
		<option value="none" <?php selected( $field['align'], 'none' ); ?>>
			<?php esc_html_e( 'None', 'formidable-pro' ); ?>
		</option>
	</select>
</p>

<p>
	<label>
		<input
			type="checkbox"
			value="1"
			id="start_over_<?php echo esc_attr( $field['id'] ); ?>"
			name="field_options[start_over_<?php echo esc_attr( $field['id'] ); ?>]"
			data-toggleclass="start_over_label_wrapper_<?php echo esc_attr( $field['id'] ); ?>"
			<?php checked( 1, $field['start_over'] ); ?>
		/>
		<?php esc_html_e( 'Add Start over button', 'formidable-pro' ); ?>
	</label>
</p>

<?php
$class = 'frm_form_field start_over_label_wrapper_' . esc_attr( $field['id'] );
if ( ! $field['start_over'] ) {
	$class .= ' frm_hidden';
}
?>
<p class="<?php echo esc_attr( $class ); ?>">
	<label for="start_over_label_<?php echo esc_attr( $field['id'] ); ?>">
		<?php esc_html_e( 'Start Over Button Text', 'formidable-pro' ); ?>
	</label>
	<input
		type="text"
		id="start_over_label_<?php echo esc_attr( $field['id'] ); ?>"
		name="field_options[start_over_label_<?php echo esc_attr( $field['id'] ); ?>]"
		value="<?php echo esc_attr( $field['start_over_label'] ); ?>"
	/>
</p>
