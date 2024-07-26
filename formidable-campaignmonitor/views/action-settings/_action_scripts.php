<?php
/**
 * Scripts for Campaign Monitor Action.
 *
 * @package CampaignMonitor
 * @author Strategy11
 */

?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#frm_notification_settings').on('change', '.frm_single_campaignmonitor_settings select[name$="[list_id]"]', frmCampaignMonitorFields);

	$( 'body' ).on( 'click', '.clrcache-campaignmonitor', function( event ) {
		event.preventDefault();
		this.classList.add( 'frm_loading_button' );
		jQuery.ajax({
			type:'POST',
			url:ajaxurl,
			data:{
				action: 'clear_campaignmonitor_fields_cache',
				security: '<?php echo esc_attr( wp_create_nonce( 'frmcampaignmonitor_ajax' ) ); ?>'
			},
			success:function( result ) {
				location.reload();
			}
		} );
	} );
});

function frmCampaignMonitorFields(){
	var form_id = jQuery('input[name="id"]').val();
	var id = jQuery(this).val();
	var key = jQuery(this).closest('.frm_single_campaignmonitor_settings').data('actionkey');
	var div = jQuery(this).closest('.campaignmonitor_list').find('.frm_campaignmonitor_fields');
	div.empty().append('<span class="spinner frm_campaignmonitor_loading_field"></span>');
	jQuery('.frm_campaignmonitor_loading_field').fadeIn('slow');
	jQuery.ajax({
		type:'POST',url:ajaxurl,
		data:{
			action:'frm_campaignmonitor_match_fields',
			form_id:form_id,
			list_id:id,
			action_key:key,
			security: '<?php echo esc_attr( wp_create_nonce( 'frmcampaignmonitor_ajax' ) ); ?>'
		},
		success:function(html){
			div.replaceWith(html).fadeIn('slow');
		}
	});
}
</script>
