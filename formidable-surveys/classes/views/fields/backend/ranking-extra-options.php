<?php
/**
 * Ranking Field extra options
 *
 * @package FrmSurveys
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm6 grid-column-start"></p>
<p class="frm6 frm_form_field">
	<label for="frm_limit_ranking_selections_<?php echo absint( $field['id'] ); ?>">
		<input data-frmshow="#frm-ranking-field-limit-selection-<?php echo absint( $field['id'] ); ?>" type="checkbox" name="field_options[limit_selections_<?php echo absint( $field['id'] ); ?>]" id="frm_limit_ranking_selections_<?php echo absint( $field['id'] ); ?>" value="1" <?php isset( $field['limit_selections'] ) ? checked( $field['limit_selections'], 1 ) : 0; ?> />
		<?php esc_html_e( 'Limit selections', 'formidable-surveys' ); ?>
		<span class="frm_help frm_icon_font frm_tooltip_icon" data-placement="right" title="" data-container="body" data-original-title="<?php esc_attr_e( 'Specifies the maximum number of items you can choose', 'formidable-surveys' ); ?>"></span>
	</label>
</p>
<p class="frm6 frm_form_field">
	<label for="frm_randomize_ranking_options_<?php echo absint( $field['id'] ); ?>">
		<input type="checkbox" name="field_options[randomize_options_<?php echo absint( $field['id'] ); ?>]" id="frm_randomize_ranking_options_<?php echo absint( $field['id'] ); ?>" value="1" <?php isset( $field['randomize_options'] ) ? checked( $field['randomize_options'], 1 ) : 0; ?> />
		<?php esc_html_e( 'Randomize options', 'formidable-surveys' ); ?>
	</label>
</p>
<p class="frm6 <?php echo ( ! isset( $field['limit_selections'] ) || (string) $field['limit_selections'] !== '1' ? 'frm_hidden' : '' ); ?>" id="frm-ranking-field-limit-selection-<?php echo absint( $field['id'] ); ?>">
	<label class="frm_primary_label">
		<?php esc_html_e( 'Limit', 'formidable-surveys' ); ?>
		<input type="number" value="<?php echo isset( $field['answers_limit'] ) ? esc_attr( $field['answers_limit'] ) : ''; ?>" name="field_options[answers_limit_<?php echo esc_attr( $field['id'] ); ?>]" />
	</label>
</p>
