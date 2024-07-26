<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<h3><?php esc_html_e( 'Pagination', 'formidable-pro' ); ?></h3>
<table class="form-table">
	<?php if ( FrmProRootlineController::is_rootline_available_in_builder() ) : ?>
		<tr>
			<td>
				<div class="frm_note_style" style="margin-top: 0;">
					<?php esc_html_e( 'Rootline settings were moved to the form builder.', 'formidable-pro' ); ?>
				</div>

				<?php
				FrmProFormsHelper::array_to_hidden_inputs(
					array(
						'rootline'             => $values['rootline'],
						'pagination_position'  => $values['pagination_position'],
						'rootline_titles'      => $values['rootline_titles'],
						'rootline_titles_on'   => $values['rootline_titles_on'],
						'rootline_numbers_off' => $values['rootline_numbers_off'],
						'rootline_lines_off'   => $values['rootline_lines_off'],
					),
					'options'
				);
				?>
			</td>
		</tr>
	<?php else : ?>
		<tr>
			<td>
				<select name="options[rootline]" id="frm_rootline_opt" data-toggleclass="hide_rootline">
					<option value=""><?php esc_html_e( 'Hide Progress bar and Rootline', 'formidable-pro' ); ?></option>
					<option value="progress" <?php selected( $values['rootline'], 'progress' ); ?>>
						<?php esc_html_e( 'Show Progress bar', 'formidable-pro' ); ?>
					</option>
					<option value="rootline" <?php selected( $values['rootline'], 'rootline' ); ?>>
						<?php esc_html_e( 'Show Rootline', 'formidable-pro' ); ?>
					</option>
				</select>
			</td>
		</tr>
		<tr class="hide_rootline <?php echo esc_attr( $hide_rootline_class ); ?>">
			<td>
				<select name="options[pagination_position]" id="frm_pagination_position_opt">
					<option value=""><?php esc_html_e( 'Below form title (default)', 'formidable-pro' ); ?></option>
					<option value="above_title" <?php selected( $values['pagination_position'], 'above_title' ); ?>>
						<?php esc_html_e( 'Above form title', 'formidable-pro' ); ?>
					</option>
					<option value="above_submit" <?php selected( $values['pagination_position'], 'above_submit' ); ?>>
						<?php esc_html_e( 'Above submit button', 'formidable-pro' ); ?>
					</option>
					<option value="below_submit" <?php selected( $values['pagination_position'], 'below_submit' ); ?>>
						<?php esc_html_e( 'Below submit button', 'formidable-pro' ); ?>
					</option>
				</select>
			</td>
		</tr>
		<tr class="hide_rootline <?php echo esc_attr( $hide_rootline_class ); ?>">
			<td>
				<label>
					<input type="checkbox" value="1" name="options[rootline_titles_on]" <?php checked( $values['rootline_titles_on'], 1 ); ?> data-toggleclass="hide_rootline_titles" />
					<?php esc_html_e( 'Show page titles with steps', 'formidable-pro' ); ?>
				</label>

				<div class="frm_indent_opt hide_rootline_titles <?php echo esc_attr( $hide_rootline_title_class ); ?>">
					<p>
						<label class="screen-reader-text" for="page_title_<?php echo intval( $i ); ?>">
							<?php printf( esc_html__( 'Page %d title', 'formidable-pro' ), intval( $i ) ); ?>
						</label>
						<input type="text" value="<?php echo esc_attr( isset( $values['rootline_titles'][0] ) ? $values['rootline_titles'][0] : sprintf( __( 'Page %d', 'formidable-pro' ), 1 ) ); ?>" name="options[rootline_titles][0]" class="large-text" placeholder="<?php echo esc_attr( sprintf( __( 'Page %d title', 'formidable-pro' ), $i ) ); ?>" id="page_title_<?php echo esc_attr( $i ); ?>" />
					</p>
					<?php
					foreach ( $page_fields as $page_field ) {
						++$i;
						?>
						<p>
							<label class="screen-reader-text" for="page_title_<?php echo esc_attr( $i ); ?>"></label>
							<input type="text" value="<?php echo esc_attr( isset( $values['rootline_titles'][ $page_field->id ] ) ? $values['rootline_titles'][ $page_field->id ] : $page_field->name ); ?>" name="options[rootline_titles][<?php echo esc_attr( $page_field->id ); ?>]" class="large-text" placeholder="<?php echo esc_attr( sprintf( __( 'Page %d title', 'formidable-pro' ), $i ) ); ?>" id="page_title_<?php echo esc_attr( $i ); ?>" />
						</p>
					<?php } ?>
				</div>
			</td>
		</tr>
		<tr class="hide_rootline <?php echo esc_attr( $hide_rootline_class ); ?>">
			<td>
				<label>
					<input type="checkbox" value="1" name="options[rootline_numbers_off]" <?php checked( $values['rootline_numbers_off'], 1 ); ?> />
					<?php esc_html_e( 'Hide the page numbers', 'formidable-pro' ); ?>
				</label>
			</td>
		</tr>
		<tr class="hide_rootline <?php echo esc_attr( $hide_rootline_class ); ?>">
			<td>
				<label>
					<input type="checkbox" value="1" name="options[rootline_lines_off]" <?php checked( $values['rootline_lines_off'], 1 ); ?> />
					<?php esc_html_e( 'Hide lines in the rootline or progress bar', 'formidable-pro' ); ?>
				</label>
			</td>
		</tr>
	<?php endif; ?>
</table>
