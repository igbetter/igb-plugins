<?php
/**
 * Likert field backend
 *
 * @package FrmSurveys
 *
 * @var array  $field      Field data. Include `html_name` and `html_id` in the field array.
 * @var string $field_name Field name.
 * @var string $html_id    HTML ID.
 */

use FrmSurveys\controllers\LikertController;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$rows          = LikertController::get_row_fields( $field );
$columns       = LikertController::get_columns( $field );
$inline_column = FrmField::get_option( $field, 'inline_column' );
$value         = isset( $field['value'] ) ? $field['value'] : '';
$classes       = 'frm_likert';
if ( $inline_column ) {
	$classes .= ' frm_likert--inline';
}
$css_variables = LikertController::get_css_variables( $field );
?>
<div
	id="<?php echo esc_attr( $html_id ); ?>"
	class="<?php echo esc_attr( $classes ); ?>"
	style="<?php echo esc_attr( $css_variables ); ?>"
	data-rows-count="<?php echo intval( count( $rows ) ); ?>"
>
	<?php LikertController::show_heading( $field ); ?>

	<?php
	foreach ( $rows as $key => $row ) {
		$row_attrs = array(
			'id'         => 'frm_field_' . $row->id . '_container',
			'class'      => 'frm_form_field form-field frm_top_container vertical_radio',
			'data-fid'   => $row->id,
			'data-fname' => $row->name,
			'data-ftype' => $row->type,
			'data-fkey'  => $row->field_key,
		);
		?>
		<div <?php FrmAppHelper::array_to_html_params( $row_attrs, true ); ?>>
			<div id="field_<?php echo esc_attr( $row->field_key ); ?>_label" class="frm_primary_label">
				<?php
				echo esc_html( $row->name );
				if ( intval( $row->id ) ) {
					echo '<span class="frm-sub-label">';
					printf(
						// translators: Likert row ID.
						esc_html__( '(ID %d)', 'formidable-surveys' ),
						intval( $row->id )
					);
					echo '</span>';
				}
				?>
			</div>
			<div class="frm_opt_container" aria-labelledby="field_<?php echo esc_attr( $row->field_key ); ?>_label" role="group">
				<?php
				$row_field_obj = FrmFieldFactory::get_field_type( $row->type, $row );
				$row_field_obj->show_on_form_builder();
				?>
			</div>
		</div>
		<?php
		unset( $row_attrs, $row_field_obj, $row );
	}//end foreach
	?>
</div>
