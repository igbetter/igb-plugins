<?php
/**
 * Scale display options
 *
 * @package FrmSurveys
 *
 * @var array $field Field array.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$options = FrmFieldsHelper::get_display_format_options( $field );
$args    = FrmFieldsHelper::get_display_format_args( $field, $options );
?>

<div id="frm_display_format_<?php echo intval( $field['id'] ); ?>_container" class="frm_form_field">
	<label for="frm_image_options_<?php echo intval( $field['id'] ); ?>"><?php esc_html_e( 'Display format', 'formidable-surveys' ); ?></label>
	<?php FrmAppHelper::images_dropdown( $args ); ?>
</div>
