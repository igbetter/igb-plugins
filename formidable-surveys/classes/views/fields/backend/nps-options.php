<?php
/**
 * Backend options for NPS field
 *
 * @package FrmSurveys
 *
 * @var array $field Field data.
 * @var array $args  Args of FrmFieldType::show_primary_options().
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$negative_statement = FrmField::get_option( $field, 'negative_statement' );
$positive_statement = FrmField::get_option( $field, 'positive_statement' );
?>
<p class="frm6">
	<label for="frm_negative_statement_<?php echo intval( $field['id'] ); ?>">
		<?php esc_html_e( 'Negative Statement', 'formidable-surveys' ); ?>
	</label>
	<input
		type="text"
		id="frm_negative_statement_<?php echo intval( $field['id'] ); ?>"
		name="field_options[negative_statement_<?php echo intval( $field['id'] ); ?>]"
		value="<?php echo esc_attr( $negative_statement ); ?>"
		data-changeme="field_<?php echo esc_attr( $field['field_key'] ); ?>_negative_text"
	/>
</p>

<p class="frm6">
	<label for="frm_positive_statement_<?php echo intval( $field['id'] ); ?>">
		<?php esc_html_e( 'Positive Statement', 'formidable-surveys' ); ?>
	</label>
	<input
		type="text"
		id="frm_positive_statement_<?php echo intval( $field['id'] ); ?>"
		name="field_options[positive_statement_<?php echo intval( $field['id'] ); ?>]"
		value="<?php echo esc_attr( $positive_statement ); ?>"
		data-changeme="field_<?php echo esc_attr( $field['field_key'] ); ?>_positive_text"
	/>
</p>
