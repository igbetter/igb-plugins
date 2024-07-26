<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<label><?php esc_html_e( 'Content', 'formidable-views' ); ?>
	<span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e( 'The HTML for the page. If \'All Entries\' is selected above, this content will be repeated for each entry. The field ID and Key work synonymously, although there are times one choice may be better. If you are panning to copy the view settings to other blogs, use the Key since they will be copied and the ids may differ from blog to blog.', 'formidable-views' ); ?>"></span>
</label>
<p style="float:right;margin:0;">
	<label for="options_no_rt">
		<input type="checkbox" id="options_no_rt" name="options[no_rt]" value="1" <?php checked( $view->frm_no_rt, 1 ); ?> /> 
		<?php esc_html_e( 'Disable visual editor for this view', 'formidable-views' ); ?>
	</label>
	<span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e( 'It is recommended to check this box if you include a <table> tag in the Before Content box. If you are editing a view and notice the visual tab is selected and the table HTML is missing, you can switch to the HTML tab, go up to the url in your browser and hit enter to reload the page. As long as the settings have not been saved, the old HTML will be back to way it was before loading it in the visual tab.', 'formidable-views' ); ?>"></span>
</p>
<div class="clear"></div>
<div id="<?php echo ! $view->frm_no_rt && user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea frm_full_rte">
	<?php wp_editor( '', 'content', $editor_args ); ?>
</div>
