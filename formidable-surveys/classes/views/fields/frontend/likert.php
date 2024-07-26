<?php
/**
 * Likert field frontend
 *
 * @package FrmSurveys
 *
 * @var array  $args  Arguments.
 * @var array  $field Field data. Include `html_name` and `html_id` in the field array.
 */

use FrmSurveys\controllers\LikertController;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$field_name      = $args['field_name'];
$html_id         = $args['html_id'];
$rows            = LikertController::get_row_fields( $field );
$columns         = LikertController::get_columns( $field );
$multi_selection = FrmField::get_option( $field, 'multi_selection' );
$inline_column   = FrmField::get_option( $field, 'inline_column' );
$value           = isset( $field['value'] ) ? $field['value'] : '';
$classes         = 'frm_likert';
if ( $inline_column ) {
	$classes .= ' frm_likert--inline';
}
$css_variables = LikertController::get_css_variables( $field );
$min_width     = LikertController::get_min_width( $field );

$form = FrmForm::getOne( $field['form_id'] );

$args['form']         = apply_filters( 'frm_pre_display_form', $form );
$args['likert_field'] = $field;

$div_attrs = array(
	'id'              => $html_id,
	'class'           => $classes,
	'style'           => $css_variables,
	'data-min-width'  => floatval( $min_width ),
	'data-rows-count' => count( $rows ),
);
?>
<div <?php FrmAppHelper::array_to_html_params( $div_attrs, true ); ?>>
	<?php if ( ! $inline_column ) : ?>
		<?php LikertController::show_heading( $field ); ?>
	<?php endif; ?>

	<?php
	foreach ( $rows as $key => $row ) {
		LikertController::show_frontend_row( $row, $args );
		unset( $row );
	}
	?>

	<input <?php do_action( 'frm_field_input_html', $field ); ?> type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="1" data-frmval="1" />
</div>

<?php LikertController::print_responsive_js(); ?>
<?php unset( $rows, $columns, $form ); ?>
