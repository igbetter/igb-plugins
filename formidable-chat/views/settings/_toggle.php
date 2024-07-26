<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="with_frm_style frm_toggle">
	<label class="frm_switch_block">
		<input id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" type="checkbox" class="frm_hidden" <?php checked( ! empty( $args['checked'] ) ); ?> />
		<span class="frm_switch" tabindex="0" role="button" aria-labelledby="<?php echo esc_attr( $id ); ?>_label">
			<span class="frm_slider"></span>
		</span>
	</label>
</div>
