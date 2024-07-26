<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="howto">
	<?php esc_html_e( 'Convert a classic form into modern lead generators with one question at a time.', 'formidable-chat' ); ?>
</p>

<div>
	<?php
	self::toggle(
		'frm_chat_toggle',
		'options[chat]',
		array(
			'div_class' => 'with_frm_style frm_toggle',
			'checked'   => ! empty( $values['chat'] ),
			'echo'      => true,
		)
	);
	?>
	<label for="frm_chat_toggle" id="frm_chat_toggle_label">
		<?php esc_html_e( 'Turn on conversational form mode', 'formidable-chat' ); ?>
		<?php if ( empty( $values['chat'] ) && ( empty( $values['js_validate'] ) || ! empty( $values['save_draft'] ) ) ) { ?>
			<strong role="alert">
				<?php
				$warnings = array();
				if ( empty( $values['js_validate'] ) ) {
					$warnings[] = __( 'enables JavaScript validation', 'formidable-chat' );
				}
				if ( ! empty( $values['save_draft'] ) ) {
					$warnings[] = __( 'disables saving drafts', 'formidable-chat' );
				}
				printf(
					/* translators: %s the other settings that will change when conversational forms are enabled. */
					esc_html__( '(this %s)', 'formidable-chat' ),
					implode( ' ' . esc_html__( 'and', 'formidable-chat' ) . ' ', array_map( 'esc_html', $warnings ) )
				);
				?>
			</strong>
		<?php } ?>
	</label>

	<div id="frm_chat_options" class="with_frm_style <?php echo empty( $values['chat'] ) ? 'frm_hidden' : ''; ?>" style="margin-top: 15px;">
		<p>
			<?php
			self::toggle(
				'frm_chat_include_arrows_toggle',
				'options[chat_include_arrows]',
				array(
					'div_class' => 'with_frm_style frm_toggle',
					'checked'   => ! empty( $values['chat_include_arrows'] ),
					'echo'      => true,
				)
			);
			?>
			<label id="frm_chat_include_arrows_toggle_label" for="frm_chat_include_arrows_toggle">
				<?php esc_html_e( 'Include arrow button navigation', 'formidable-chat' ); ?>
			</label>
		</p>
		<p>
			<?php
			$should_show_start_page = false;
			if ( isset( $values['chat_show_start_page'] ) ) {
				$should_show_start_page = ! empty( $values['chat_show_start_page'] );
			} else {
				// If no option is set, get the previous v1.0.01 behaviour (a start page was shown if title or description is visible).
				$should_show_start_page = ( ! empty( $values['name'] ) && ! empty( $values['show_title'] ) ) || ( ! empty( $values['description'] ) && ! empty( $values['show_description'] ) );
			}

			self::toggle(
				'frm_chat_show_start_page_toggle',
				'options[chat_show_start_page]',
				array(
					'div_class' => 'with_frm_style frm_toggle',
					'checked'   => $should_show_start_page,
					'echo'      => true,
				)
			);
			?>
			<label id="frm_chat_show_start_page_toggle_label" for="frm_chat_show_start_page_toggle">
				<?php esc_html_e( 'Show start page', 'formidable-chat' ); ?>
			</label>
		</p>
		<div class="frm_grid_container" style="margin-top: 15px;">
			<div class="frm4">
				<label for="frm_chat_custom_continue_text"><?php esc_html_e( 'Type of Progress Bar', 'formidable-chat' ); ?></label>
			</div>
			<div class="frm8">
				<?php $progress_type = ! empty( $values['chat_progress_type'] ) ? $values['chat_progress_type'] : ''; ?>
				<select name="options[chat_progress_type]">
					<option value="" <?php selected( $progress_type, '' ); ?>><?php esc_html_e( 'None', 'formidable-chat' ); ?></option>
					<option value="bar" <?php selected( $progress_type, 'bar' ); ?>><?php esc_html_e( 'Progress Bar', 'formidable-chat' ); ?></option>
					<option value="text" <?php selected( $progress_type, 'text' ); ?>><?php esc_html_e( 'Text', 'formidable-chat' ); ?></option>
					<option value="both" <?php selected( $progress_type, 'both' ); ?>><?php esc_html_e( 'Progress Bar and Text', 'formidable-chat' ); ?></option>
				</select>
			</div>
		</div>
		<p id="frm_continue_button_text_container">
			<label for="frm_chat_custom_continue_text" class="frm_left_label">
				<?php esc_html_e( 'Continue Button Text', 'formidable-chat' ); ?>
			</label>
			<input id="frm_chat_custom_continue_text" name="options[chat_continue_text]" type="text" value="<?php echo esc_attr( ! empty( $values['chat_continue_text'] ) ? $values['chat_continue_text'] : __( 'Continue', 'formidable-chat' ) ); ?>" />
		</p>

		<p>
			<label for="frm_chat_start_button_text" class="frm_left_label">
				<?php esc_html_e( 'Start Button Text', 'formidable-chat' ); ?>
			</label>
			<input id="frm_chat_start_button_text" name="options[chat_start_button_text]" type="text" value="<?php echo esc_attr( ! empty( $values['chat_start_button_text'] ) ? $values['chat_start_button_text'] : __( 'Start', 'formidable-chat' ) ); ?>" />
		</p>
	</div>
</div>
