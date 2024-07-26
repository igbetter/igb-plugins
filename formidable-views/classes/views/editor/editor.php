<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div id="view-editor" class="columns-2"></div>
<div class="frm_hidden">
	<?php
	require $editor_path . 'svg.html';
	require FrmViewsAppHelper::plugin_path() . '/images/icons.svg';
	self::wysiwyg( $view );
	?>
	<div id="frm_adv_info">
		<div class="postbox">
			<div class="postbox-header" >
				<h2>Customization</h2>
			</div>
			<div id="frm_box_content_customization"><?php FrmViewsDisplaysController::mb_adv_info( $view ); ?></div>
		</div>
	</div>
	<?php FrmFormsHelper::forms_dropdown( 'frm_data_source_form_select_template', '', array( 'inc_children' => 'include' ) ); ?>
	<select id="frm_field_select_template">
		<?php FrmViewsEditorController::render_field_select_template_options( $form_id ); ?>
	</select>
	<?php self::render_metaboxes( $view ); ?>

	<?php /* this input helps to add backward compatibility with third party plugins by mocking the legacy editor. */ ?>
	<input id="post_ID" value="<?php echo absint( $view_id ); ?>" type="hidden" />

	<?php /* these inputs help track the value of before_content and show_count for the old frm_export_view.js to work. */ ?>
	<input id="before_content" value="<?php echo $is_table_type ? '<table>' : ''; ?>" />
	<input type="radio" name="show_count" value="all" <?php checked( $check_all_option ); ?> />
	<input type="radio" name="show_count" value="one" <?php checked( ! $check_all_option ); ?> />
</div>
<script>
	var frmViewsEditorViewData, frmViewsEditorInfo;

	// dynamic data that will typically change as the editor is updated.
	// the majority of data is loaded after page load through the dataProcessor.
	frmViewsEditorViewData = {
		id: <?php echo absint( $view_id ); ?>,
		name: '<?php echo esc_js( $view->post_title ); ?>',
		viewKey: '<?php echo esc_js( $view->post_name ); ?>',
		formId: '<?php echo esc_js( $form_id ); ?>',
		applicationIds: <?php echo wp_json_encode( $application_ids ); ?>,
		showCount: '<?php echo esc_js( $show_count ); ?>',
		activePreviewFilter: '<?php echo esc_js( $active_preview_filter ); ?>'
	};

	// (mostly) static data that gets passed to the interface that that isn't needed for the save action.
	frmViewsEditorInfo = {
		svgPath: '<?php echo esc_url( FrmViewsAppHelper::plugin_url() . '/images/' ); ?>',
		siteUrl: '<?php echo esc_url( FrmAppHelper::site_url() ); ?>',
		viewUrl: '<?php echo esc_url( get_post_permalink( $view_id ) ); ?>',
		siteName: '<?php echo esc_js( FrmAppHelper::site_name() ); ?>',
		includeCopyOption: <?php echo absint( $include_copy_option ); ?>,
		showEducation: <?php echo absint( $show_education ); ?>,
		startAddingContentUrl: '<?php echo esc_url( $start_adding_content_url ); ?>',
		isGridType: <?php echo absint( $is_grid_type ); ?>,
		dateFieldIds: <?php self::echo_date_field_ids( $form_id ); ?>,
		draftDropdownOptions: <?php self::echo_draft_dropdown_options(); ?>,
		statusDropdownOptions: <?php self::echo_status_dropdown_options( $form_id ); ?>,
		addressFieldOptions: <?php self::echo_address_field_options( $form_id ); ?>,
		statusFieldId: <?php echo absint( self::get_status_field_id() ); ?>,
		isTableType: <?php echo absint( $is_table_type ); ?>,
		isMapType: <?php echo absint( $is_map_type ); ?>,
		previewLimit: <?php echo absint( FrmViewsAppHelper::get_visual_views_preview_limit() ); ?>
	};

	<?php if ( $is_grid_type ) { ?>
		frmViewsEditorInfo.defaultGridStyles = <?php self::echo_default_grid_styles(); ?>;
	<?php } ?>
</script>
