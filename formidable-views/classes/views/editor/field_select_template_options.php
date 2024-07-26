<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<option value=""><?php esc_html_e( '&mdash; Select &mdash;', 'formidable-views' ); ?></option>
<option value="created_at">
	<?php esc_html_e( 'Entry creation date', 'formidable-views' ); ?>
</option>
<option value="updated_at">
	<?php esc_html_e( 'Entry updated date', 'formidable-views' ); ?>
</option>
<option value="id">
	<?php esc_html_e( 'Entry ID', 'formidable-views' ); ?>
</option>
<option value="item_key">
	<?php esc_html_e( 'Entry key', 'formidable-views' ); ?>
</option>
<option value="post_id">
	<?php esc_html_e( 'Post ID', 'formidable-views' ); ?>
</option>
<option value="parent_item_id">
	<?php esc_html_e( 'Parent entry ID', 'formidable-views' ); ?>
</option>
<option value="is_draft">
	<?php esc_html_e( 'Entry status', 'formidable-views' ); ?>
</option>
<?php
if ( $form_id ) {
	$name_fields    = FrmField::get_all_types_in_form( $form_id, 'name' );
	$address_fields = FrmField::get_all_types_in_form( $form_id, 'address' );
	$exclude_types  = array( 'break', 'end_divider', 'divider', 'file', 'captcha', 'form' );

	if ( is_callable( 'FrmHtmlHelper::echo_dropdown_option' ) && ( $name_fields || $address_fields ) ) {
		// Inject (First) and (Last) name field options after the Name option that gets generated from FrmProFieldsHelper::get_field_options.
		ob_start();
		FrmProFieldsHelper::get_field_options( $form_id, '', 'not', $exclude_types, array( 'inc_sub' => 'include' ) );
		$html = ob_get_clean();

		foreach ( $name_fields as $name_field ) {
			ob_start();
			$subfields = array(
				'first' => __( 'First', 'formidable-views' ),
				'last'  => __( 'Last', 'formidable-views' ),
			);
			FrmViewsEditorController::render_subfield_sort_options( (int) $name_field->id, $name_field->name, $subfields, false );
			$additional_name_options_html = ob_get_clean();

			$name_option_html = '<option value="' . $name_field->id . '" >' . FrmAppHelper::truncate( $name_field->name, 50 ) . '</option>';

			// We want to add the name field as a filter option but not a sort option.
			$new_name_option_html = str_replace(
				'<option ',
				'<option class="frm-views-filter-option" ',
				$name_option_html
			);
			$html                 = str_replace(
				$name_option_html,
				$new_name_option_html . $additional_name_options_html,
				$html
			);
		}

		foreach ( $address_fields as $address_field ) {
			ob_start();
			$subfields = array(
				'country' => __( 'Country', 'formidable-views' ),
				'state'   => __( 'State', 'formidable-views' ),
				'city'    => __( 'City', 'formidable-views' ),
				'zip'     => __( 'Zip', 'formidable-views' ),
			);
			FrmViewsEditorController::render_subfield_sort_options( (int) $address_field->id, $address_field->name, $subfields );
			?>
			<?php
			$additional_address_options_html = ob_get_clean();
			$address_option_html             = '<option value="' . $address_field->id . '" >' . FrmAppHelper::truncate( $address_field->name, 50 ) . '</option>';

			// Remove the Address option because sorting for a whole address doesn't make a lot of sense.
			// And the query for parsing an address would be too complex, but works okay for Name fields.
			$html = str_replace(
				$address_option_html,
				$additional_address_options_html,
				$html
			);
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		FrmProFieldsHelper::get_field_options( $form_id, '', 'not', $exclude_types, array( 'inc_sub' => 'include' ) );
	}
}
?>
<option value="ip">
	<?php esc_html_e( 'IP', 'formidable-views' ); ?>
</option>
