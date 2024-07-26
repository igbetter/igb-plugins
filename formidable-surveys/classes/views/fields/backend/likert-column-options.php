<?php
/**
 * Likert Column options
 *
 * @package FrmSurveys
 *
 * @var array  $field      Field data.
 * @var Likert $field_obj  Field type object.
 * @var array  $display    Display data.
 * @var array  $values     Values.
 */

use FrmSurveys\controllers\LikertController;
use FrmSurveys\models\fields\Likert;

$columns        = LikertController::get_columns( $field_obj->field );
$separate_value = FrmField::get_option( $field_obj->field, 'separate_value' );
?>
<h3 class="frm-collapsed" aria-expanded="false" tabindex="0" role="button" aria-label="<?php esc_html_e( 'Collapsible Columns Settings', 'formidable-surveys' ); ?>" aria-controls="collapsible-section">
	<?php esc_html_e( 'Columns', 'formidable-surveys' ); ?>
	<i class="frm_icon_font frm_arrowdown6_icon"></i>
</h3>

<div class="frm_grid_container frm-collapse-me" data-separate-values="<?php echo $separate_value ? 1 : 0; ?>" role="group">
	<?php
	LikertController::show_likert_opts(
		array(
			'singular_name'        => __( 'Column', 'formidable-surveys' ),
			'plural_name'          => __( 'Columns', 'formidable-surveys' ),
			'add_label'            => __( 'Add Column', 'formidable-surveys' ),
			'base_name'            => 'columns_' . intval( $field_obj->field_id ),
			'base_id'              => 'frm_columns_' . $field_obj->field_id,
			'items'                => $columns,
			'likert_id'            => $field_obj->field_id,
			'remove_key_from_name' => true,
			'option_type'          => 'likert_column',
		)
	);
	?>
</div>
