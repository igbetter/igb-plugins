<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div id="frm_logic_<?php echo esc_attr( $field['id'] . '_' . $meta_name ); ?>" class="frm_logic_row">
<select name="field_options[hide_field_<?php echo esc_attr( $field['id'] ); ?>][]" class="frm_logic_field_opts" data-type="<?php echo esc_attr( $field['type'] ); ?>">
	<option value=""><?php esc_html_e( '&mdash; Select &mdash;' ); ?></option>
    <?php
    $sel         = false;
	$prefiltered = FrmProConditionalLogicOptionData::data_is_available( $field['form_id'] );
	foreach ( $form_fields as $ff ) {
		if ( ! FrmProConditionalLogicController::is_field_present_in_logic_options( $field, $ff, $prefiltered ) ) {
            continue;
        }

		if ( $ff->id == $hide_field ) {
            $sel = true;
		}

		FrmProHtmlHelper::echo_dropdown_option(
			$ff->name,
			(string) $ff->id === (string) $hide_field,
			array(
				'value' => $ff->id,
			)
		);
    }
	?>
</select>
<?php
if ( $hide_field && ! $sel ) {
//remove conditional logic if the field doesn't exist
?>
<script type="text/javascript">jQuery(document).ready(function(){frmAdminBuild.triggerRemoveLogic(<?php echo (int) $field['id']; ?>, '<?php echo esc_attr( $meta_name ); ?>');});</script>
<?php
}
$field['hide_field_cond'][ $meta_name ] = isset( $field['hide_field_cond'][ $meta_name ] ) ? htmlspecialchars_decode( $field['hide_field_cond'][ $meta_name ] ) : '';
?>

<select name="field_options[hide_field_cond_<?php echo esc_attr( $field['id'] ); ?>][]" class="auto_width">
	<option value="==" <?php selected( $field['hide_field_cond'][ $meta_name ], '==' ); ?>>
		<?php esc_html_e( 'equals', 'formidable-pro' ); ?>
	</option>
	<option value="!=" <?php selected( $field['hide_field_cond'][ $meta_name ], '!=' ); ?>>
		<?php esc_html_e( 'does not equal', 'formidable-pro' ); ?> &nbsp;
	</option>
	<option value=">" <?php selected( $field['hide_field_cond'][ $meta_name ], '>' ); ?>>
		<?php esc_html_e( 'is greater than', 'formidable-pro' ); ?>
	</option>
	<option value=">=" <?php selected( $field['hide_field_cond'][ $meta_name ], '>=' ); ?>>
		<?php esc_html_e( 'is greater than or equal to', 'formidable-pro' ); ?>
	</option>
	<option value="<" <?php selected( $field['hide_field_cond'][ $meta_name ], '<' ); ?>>
		<?php esc_html_e( 'is less than', 'formidable-pro' ); ?>
	</option>
	<option value="<=" <?php selected( $field['hide_field_cond'][ $meta_name ], '<=' ); ?>>
		<?php esc_html_e( 'is less than or equal to', 'formidable-pro' ); ?>
	</option>
	<option value="LIKE" <?php selected( $field['hide_field_cond'][ $meta_name ], 'LIKE' ); ?>>
		<?php esc_html_e( 'contains', 'formidable-pro' ); ?>
	</option>
	<option value="not LIKE" <?php selected( $field['hide_field_cond'][ $meta_name ], 'not LIKE' ); ?>>
		<?php esc_html_e( 'does not contain', 'formidable-pro' ); ?> &nbsp;
	</option>
	<option value="LIKE%" <?php selected( $field['hide_field_cond'][ $meta_name ], 'LIKE%' ); ?>>
		<?php esc_html_e( 'starts with', 'formidable-pro' ); ?> &nbsp;
	</option>
	<option value="%LIKE" <?php selected( $field['hide_field_cond'][ $meta_name ], '%LIKE' ); ?>>
		<?php esc_html_e( 'ends with', 'formidable-pro' ); ?> &nbsp;
	</option>
</select>

<span id="frm_show_selected_values_<?php echo esc_attr( $field['id'] . '_' . $meta_name ); ?>">
<?php
	$selector_field_id = $hide_field && is_numeric( $hide_field ) ? (int) $hide_field : 0;
	$selector_args     = array(
		'html_name' => 'field_options[hide_opt_' . $field['id'] . '][]',
		'value'     => isset( $field['hide_opt'][ $meta_name ] ) ? $field['hide_opt'][ $meta_name ] : '',
		'source'    => $field['type'],
	);

	FrmFieldsHelper::display_field_value_selector( $selector_field_id, $selector_args );
?>
</span>
<a href="javascript:void(0)" class="frm_icon_font frm_remove_tag" data-removeid="frm_logic_<?php echo esc_attr( $field['id'] . '_' . $meta_name ); ?>" data-showlast="#logic_<?php echo esc_attr( $field['id'] ); ?>" data-hidelast="#frm_logic_rows_<?php echo absint( $field['id'] ); ?>"></a>
</div>
