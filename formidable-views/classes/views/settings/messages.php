<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_grid_container">
	<label for="frm_load_more_button_text" class="frm4 frm_form_field" >
		<?php esc_html_e( 'Load More Button Text', 'formidable-views' ); ?>
		<span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e( 'The text used in the Load More button that appears in views with AJAX Pagination.', 'formidable-views' ); ?>"></span>
	</label>
	<input type="text" id="frm_load_more_button_text" name="load_more_button_text" class="frm8 frm_form_field" value="<?php echo esc_attr( $frmviews_settings->load_more_button_text ); ?>" />
</p>
