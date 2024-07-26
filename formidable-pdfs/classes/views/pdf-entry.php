<?php
/**
 * The content of entry PDF export file
 *
 * @package FrmPdfs
 *
 * @var object $entry     Entry object.
 * @var array  $show_args The shortcode parameters to pass.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm-pdf-content">
	<p>
		<?php
		printf(
			// Translators: date string.
			esc_html__( 'Added on: %s', 'formidable-pdfs' ),
			esc_html( FrmAppHelper::get_formatted_time( $entry->created_at ) )
		);
		?>
	</p>

	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo FrmProEntriesController::show_entry_shortcode( $show_args );
	?>
</div>
