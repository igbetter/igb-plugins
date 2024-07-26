<?php
/**
 * Formidable Campaign Monitor Action form.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

?>
<div class="campaignmonitor_list frm_grid_container">
	<p class="frm6">
					<label class="frm_left_label" style="clear:none;">
						<?php esc_html_e( 'Contact List', 'frm-campaignmonitor' ); ?>
						<span class="frm_required">*</span>
					</label>
					<select name="<?php echo esc_attr( $action_control->get_field_name( 'list_id' ) ); ?>">
						<option value=""><?php esc_html_e( '&mdash; Select &mdash;' ); ?></option>
						<?php
						if ( ! empty( $lists ) ) {
							foreach ( $lists as $list ) {
								?>
								<option value="<?php echo esc_attr( $list['id'] ); ?>" <?php selected( $list_id, $list['id'] ); ?> >
									<?php echo esc_html( $list['name'] ); ?>
								</option>
								<?php
							}
						}
						?>
					</select>
	</p>

	<?php
	if ( isset( $list_fields ) && is_array( $list_fields ) ) {
		include dirname( __FILE__ ) . '/_match_fields.php';
	} else {
		?>
		<div class="frm_campaignmonitor_fields"></div>
		<?php
	}
	?>
	<p class="frm6">
		<a href="javascript:void(0)"  class="button frm-button-secondary clrcache-campaignmonitor">
			<?php esc_html_e( 'Clear Cache', 'frm-campaignmonitor' ); ?>
		</a>
	</p>
</div>
