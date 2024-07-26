<?php
/**
 * Campaign Monitor Action field match template.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

?>
<div class="frm_campaignmonitor_fields <?php echo esc_attr( $action_control->get_field_id( 'frm_campaignmonitor_fields' ) ); ?> frm_grid_container">

	<?php
	if ( is_array( $list_fields ) ) {
		foreach ( $list_fields as $list_field ) {
			?>
				<p class="frm6">
					<label class="frm_left_label">
						<?php echo esc_html( ucfirst( $list_field['name'] ) ); ?>
						<?php if ( $list_field['req'] ) { ?>
							<span class="frm_required">*</span>
						<?php } ?>
					</label>

					<select name="<?php echo esc_attr( $action_control->get_field_name( 'fields' ) ); ?>[<?php echo esc_attr( $list_field['tag'] ); ?>]">
						<option value=""><?php esc_html_e( '&mdash; Select &mdash;' ); ?></option>
						<?php
						foreach ( $form_fields as $form_field ) {
							$selected = ( isset( $list_options['fields'][ $list_field['tag'] ] ) && $list_options['fields'][ $list_field['tag'] ] == $form_field->id ) ? ' selected="selected"' : '';
							?>
							<option value="<?php echo esc_attr( $form_field->id ); ?>" <?php echo esc_attr( $selected ); ?>>
								<?php echo esc_html( $form_field->name ); ?>
							</option>
						<?php } ?>
					</select>
				</p>
		<?php } ?>
	<?php } ?>
</div>
