<?php
/**
 * Likert Row options
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

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
$rows = LikertController::get_row_fields( $field_obj->field );
?>
<h3>
	<?php esc_html_e( 'Rows', 'formidable-surveys' ); ?>
	<i class="frm_icon_font frm_arrowdown6_icon"></i>
</h3>

<div class="frm_grid_container frm-collapse-me" role="group">
	<?php
	LikertController::show_likert_opts(
		array(
			'singular_name' => __( 'Row', 'formidable-surveys' ),
			'plural_name'   => __( 'Rows', 'formidable-surveys' ),
			'add_label'     => __( 'Add Row', 'formidable-surveys' ),
			'base_name'     => 'rows_' . intval( $field_obj->field_id ),
			'base_id'       => 'frm_rows_' . $field_obj->field_id,
			'items'         => $rows,
			'likert_id'     => $field_obj->field_id,
		)
	);
	?>
</div>
