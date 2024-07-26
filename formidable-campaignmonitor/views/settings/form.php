<?php
/**
 * Formidable Campaign Monitor Add-On settings template.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

?>

<?php
if ( isset( $errors ) ) {
	require( FrmAppHelper::plugin_path() . '/classes/views/shared/errors.php' );
}
?>
<table class="form-table">
	<tr class="form-field" valign="top">
		<td width="200px">
			<label for="frm_campaignmonitor_api_key">
				<?php esc_html_e( ' API Key', 'frm-campaignmonitor' ); ?>
			</label>
		</td>
		<td>
			<input type="text" name="frm_campaignmonitor_api_key" id="frm_campaignmonitor_api_key" value="<?php echo esc_attr( $settings->settings->api_key ); ?>" class="frm_long_input" />
			<p class="howto">
				<?php esc_html_e( 'Your API key can be found at yoursite.createsend.com/account/apikeys. Go to your Campaign Monitor account. Click on your name in the top corner then "Account Settings" - "API Keys"', 'frm-campaignmonitor' ); ?>
			</p>
		</td>
	</tr>

	<tr class="form-field" valign="top">
		<td>
			<label for="frm_campaignmonitor_api_url">
				<?php esc_html_e( 'Client ID', 'frm-campaignmonitor' ); ?>
			</label>
		</td>
		<td>
			<input type="text" name="frm_campaignmonitor_client_api_key" id="frm_campaignmonitor_api_url" value="<?php echo esc_attr( $settings->settings->client_api_key ); ?>" class="frm_long_input" />
		</td>
	</tr>
	<tr class="form-field" valign="top">
		<td><label><?php esc_html_e( 'Debug Mode', 'frm-campaignmonitor' ); ?></label></td>
		<td>
			<select name="frm_campaignmonitor_debug_mode" id="frm_campaignmonitor_debug_mode">
				<option <?php selected( $settings->settings->debug_mode, 'no' ); ?> value="no">
					<?php esc_html_e( 'No', 'frm-campaignmonitor' ); ?>
				</option>
				<option <?php selected( $settings->settings->debug_mode, 'yes' ); ?> value="yes">
					<?php esc_html_e( 'Yes', 'frm-campaignmonitor' ); ?>
				</option>
			</select>
			<p class="howto">
				<?php esc_html_e( 'See Campaign Monitor response on form submit', 'frm-campaignmonitor' ); ?>.
			</p>
		</td>
	</tr>
</table>
