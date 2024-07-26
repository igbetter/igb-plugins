<?php
/**
 * NPS field frontend
 *
 * @package FrmSurveys
 *
 * @var array  $field      Field data. Include `html_name` and `html_id` in the field array.
 * @var string $field_name Field name.
 * @var string $html_id    HTML ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$negative_statement = FrmField::get_option( $field, 'negative_statement' );
$positive_statement = FrmField::get_option( $field, 'positive_statement' );
$value              = isset( $field['value'] ) ? $field['value'] : '';

// NPS button params.
$nps_button_params = array(
	'class'           => 'frm_nps__buttons',
	'role'            => 'radiogroup',
	'aria-labelledby' => 'field_' . $field['field_key'] . '_label',
);
if ( $field['required'] === '1' ) {
	$nps_button_params['aria-required'] = 'true';
}
?>
<div id="<?php echo esc_attr( $html_id ); ?>" class="frm_nps">
	<div <?php FrmAppHelper::array_to_html_params( $nps_button_params, true ); ?>>
		<?php
		$length = 10;
		for ( $i = 0; $i <= $length; $i++ ) {
			?>
			<input
				type="radio"
				id="<?php echo esc_attr( $html_id . '-' . $i ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
				value="<?php echo intval( $i ); ?>"
				<?php
				checked( $i, $value );
				$field['input_class'] = 'frm_screen_reader';
				do_action( 'frm_field_input_html', $field );
				?>
			/>
			<label for="<?php echo esc_attr( $html_id . '-' . $i ); ?>" class="frm_nps__button"><?php echo intval( $i ); ?></label>
			<?php
		}
		?>
	</div>
	<div class="frm_nps__statements">
		<div class="frm_nps__negative">
			<?php
			printf(
				// Translators: Negative statement.
				esc_html__( '0 - %s', 'formidable-surveys' ),
				'<span id="field_' . esc_attr( $field['field_key'] ) . '_negative_text">' . esc_html( $negative_statement ) . '</span>'
			);
			?>
		</div>

		<div class="frm_nps__positive">
			<?php
			printf(
				// Translators: Positive statement.
				esc_html__( '10 - %s', 'formidable-surveys' ),
				'<span id="field_' . esc_attr( $field['field_key'] ) . '_positive_text">' . esc_html( $positive_statement ) . '</span>'
			);
			?>
		</div>
	</div>
</div>
