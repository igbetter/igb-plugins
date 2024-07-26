<?php
/**
 * Rootline settings view
 *
 * @since 6.9
 * @package FormidablePro
 *
 * @var FrmProRootline $rootline Rootline object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$rootline_types = FrmProRootlineController::get_rootline_types();

$i                = 1;
$first_page_title = isset( $rootline->titles[0] ) ? $rootline->titles[0] : sprintf( __( 'Page %d', 'formidable-pro' ), $i );

$hide_rootline_class       = $rootline->is_enabled() ? '' : 'frm_hidden';
$hide_rootline_title_class = $rootline->show_titles ? '' : 'frm_hidden';
?>
<p>
	<label for="frm-rootline-type"><?php esc_html_e( 'Rootline', 'formidable-pro' ); ?></label>
	<select id="frm-rootline-type" name="frm_rootline[type]" data-toggleclass="hide_rootline">
		<option value=""><?php esc_html_e( 'Hide Progress bar and Rootline', 'formidable-pro' ); ?></option>
		<?php foreach ( $rootline_types as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $rootline->type ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
</p>

<p class="hide_rootline <?php echo esc_attr( $hide_rootline_class ); ?>">
	<label for="frm-pagination-position"><?php esc_html_e( 'Position', 'formidable-pro' ); ?></label>
	<select name="frm_rootline[position]" id="frm-pagination-position">
		<option value=""><?php esc_html_e( 'Below form title (default)', 'formidable-pro' ); ?></option>
		<option value="above_title" <?php selected( $rootline->position, 'above_title' ); ?>>
			<?php esc_html_e( 'Above form title', 'formidable-pro' ); ?>
		</option>
		<option value="above_submit" <?php selected( $rootline->position, 'above_submit' ); ?>>
			<?php esc_html_e( 'Above submit button', 'formidable-pro' ); ?>
		</option>
		<option value="below_submit" <?php selected( $rootline->position, 'below_submit' ); ?>>
			<?php esc_html_e( 'Below submit button', 'formidable-pro' ); ?>
		</option>
	</select>
</p>

<div class="hide_rootline <?php echo esc_attr( $hide_rootline_class ); ?>">
	<label>
		<input type="checkbox" value="1" name="frm_rootline[show_titles]" id="frm-rootline-titles-on" <?php checked( $rootline->show_titles, 1 ); ?> data-toggleclass="hide_rootline_titles" />
		<?php esc_html_e( 'Show page titles with steps', 'formidable-pro' ); ?>
	</label>

	<div
		id="frm-rootline-titles"
		class="frm_indent_opt hide_rootline_titles <?php echo esc_attr( $hide_rootline_title_class ); ?>"
		data-titles="<?php echo esc_attr( wp_json_encode( $rootline->titles ) ); ?>"
	>
		<p class="frm-rootline-title-setting">
			<label class="screen-reader-text" for="frm-rootline-title-<?php echo intval( $i ); ?>">
				<?php printf( esc_html__( 'Page %d title', 'formidable-pro' ), intval( $i ) ); ?>
			</label>
			<input
				type="text"
				value="<?php echo esc_attr( $first_page_title ); ?>"
				name="frm_rootline[titles][0]"
				class="large-text"
				placeholder="<?php echo esc_attr( sprintf( __( 'Page %d title', 'formidable-pro' ), $i ) ); ?>"
				id="frm-rootline-title-<?php echo intval( $i ); ?>"
			/>
		</p>
		<?php
		foreach ( $rootline->page_breaks as $page_field ) {
			++$i;
			?>
			<p class="frm-rootline-title-setting">
				<label class="screen-reader-text" for="frm-rootline-title-<?php echo intval( $i ); ?>"></label>
				<input
					type="text"
					value="<?php echo esc_attr( isset( $rootline->titles[ $page_field->id ] ) ? $rootline->titles[ $page_field->id ] : $page_field->name ); ?>"
					name="frm_rootline[titles][<?php echo esc_attr( $page_field->id ); ?>]"
					class="large-text"
					placeholder="<?php echo esc_attr( sprintf( __( 'Page %d title', 'formidable-pro' ), $i ) ); ?>"
					id="frm-rootline-title-<?php echo intval( $i ); ?>"
				/>
			</p>
		<?php } ?>
	</div>
</div>

<p class="hide_rootline <?php echo esc_attr( $hide_rootline_class ); ?>">
	<label>
		<input type="checkbox" value="1" name="frm_rootline[hide_numbers]" id="frm-rootline-numbers-off" <?php checked( ! $rootline->show_numbers ); ?> />
		<?php esc_html_e( 'Hide the page numbers', 'formidable-pro' ); ?>
	</label>
</p>

<p class="hide_rootline <?php echo esc_attr( $hide_rootline_class ); ?>">
	<label>
		<input type="checkbox" value="1" name="frm_rootline[hide_lines]" id="frm-rootline-lines-off" <?php checked( ! $rootline->show_lines ); ?> />
		<?php esc_html_e( 'Hide lines in the rootline or progress bar', 'formidable-pro' ); ?>
	</label>
</p>
