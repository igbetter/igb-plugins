<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm_hidden">
	<?php
	FrmAppHelper::icon_by_class( 'frmfont frm_plus_icon' );
	?>
	<svg id="frm_view_icon_grid" class="frmsvg">
		<use href="#frm_views_grid_icon"></use>
	</svg>
	<svg id="frm_views_calendar_type" class="frmsvg">
		<use href="#frm_views_calendar_icon"></use>
	</svg>
	<svg id="frm_views_table_type" class="frmsvg">
		<use href="#frm_views_table_icon"></use>
	</svg>
	<svg id="frm_views_editor_coming_soon_calendar" class="frmsvg">
		<use href="#frm_views_coming_soon_calendar"></use>
	</svg>
	<svg id="frm_views_editor_classic_view_icon" class="frmsvg">
		<use href="#frm_code_icon"></use>
	</svg>
	<svg id="frm_views_editor_map_icon" class="frmsvg">
		<use href="#frm_location_icon"></use>
	</svg>
	<svg id="frm_views_editor_ready_made_solution_icon" class="frmsvg">
		<use href="#frm_views_solid_folder_icon"></use>
	</svg>
	<div id="frm-hover-icons-template" class="frm-hover-icons">
		<a role="button" href="#" class="frm-create-view frm-create-hover-icon" aria-label="<?php esc_attr_e( 'Create view', 'formidable-views' ); ?>" title="<?php esc_attr_e( 'Create view', 'formidable-views' ); ?>">
			<svg class="frmsvg">
				<use href="#frm_plus_icon"></use>
			</svg>
		</a>
	</div>
</div>
