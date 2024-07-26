<?php
/**
 * Add NPS options at top of field settings.
 *
 * @package FrmSurveys
 *
 * @var array $field Field data.
 */

$field_id        = $field['id'];
$multi_selection = FrmField::get_option( $field, 'multi_selection' );
$inline_column   = FrmField::get_option( $field, 'inline_column' );
$separate_value  = FrmField::get_option( $field, 'separate_value' );
?>
<div class="frm_grid_container">
	<p class="frm6">
		<label for="frm_likert_multi_selection_<?php echo intval( $field_id ); ?>">
			<input
				type="checkbox"
				id="frm_likert_multi_selection_<?php echo intval( $field_id ); ?>"
				class="frm_likert_multi_selection"
				name="field_options[multi_selection_<?php echo intval( $field_id ); ?>]"
				value="1"
				data-fid="<?php echo intval( $field_id ); ?>"
				data-likert-id="<?php echo intval( $field_id ); ?>"
				<?php checked( $multi_selection, '1' ); ?>
			/>
			<?php esc_html_e( 'Use checkboxes', 'formidable-surveys' ); ?>
		</label>
	</p>

	<p class="frm6">
		<label for="frm_likert_inline_column_<?php echo intval( $field_id ); ?>">
			<input
				type="checkbox"
				id="frm_likert_inline_column_<?php echo intval( $field_id ); ?>"
				class="frm_likert_inline_column"
				name="field_options[inline_column_<?php echo intval( $field_id ); ?>]"
				value="1"
				data-fid="<?php echo intval( $field_id ); ?>"
				data-likert-id="<?php echo intval( $field_id ); ?>"
				<?php checked( $inline_column, '1' ); ?>
			/>
			<?php esc_html_e( 'Repeat column headings', 'formidable-surveys' ); ?>
		</label>
	</p>

	<p class="frm6">
		<label for="frm_likert_separate_value_<?php echo intval( $field_id ); ?>">
			<input
				type="checkbox"
				id="frm_likert_separate_value_<?php echo intval( $field_id ); ?>"
				class="frm_likert_separate_value"
				name="field_options[separate_value_<?php echo intval( $field_id ); ?>]"
				value="1"
				data-fid="<?php echo intval( $field_id ); ?>"
				data-likert-id="<?php echo intval( $field_id ); ?>"
				<?php checked( $separate_value, '1' ); ?>
			/>
			<?php esc_html_e( 'Separate column values', 'formidable-surveys' ); ?>
		</label>
	</p>
</div>
