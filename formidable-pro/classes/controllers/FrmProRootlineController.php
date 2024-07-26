<?php
/**
 * Rootline controller
 *
 * @since 6.9
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmProRootlineController
 */
class FrmProRootlineController {

	/**
	 * Checks if rootline is available in builder.
	 *
	 * @return bool
	 */
	public static function is_rootline_available_in_builder() {
		return FrmProSubmitHelper::is_available();
	}

	/**
	 * Shows rootline in form builder.
	 *
	 * @param array $form_array Form array.
	 */
	public static function show_rootline_in_builder( $form_array ) {
		$rootline = self::get_rootline_obj( $form_array );
		?>
		<div id="frm-backend-rootline-wrapper" class="frm_field_box">
			<div class="frm-show-field-settings" data-fid="rootline">
				<?php
				if ( is_callable( array( $rootline, 'backend_output' ) ) ) {
					$rootline->backend_output();
				}
				?>
			</div>

			<div class="frm-single-settings frm_hidden frm-fields frm-type-rootline frm_grid_container" id="frm-single-settings-rootline" data-fid="rootline">
				<input type="hidden" name="frm_fields_submitted[]" value="rootline" />
				<?php self::show_rootline_settings( $rootline ); ?>
			</div>
		</div>

		<p class="frm-show-rootline-wrapper">
			<a href="#" class="frm-show-field-settings" data-fid="rootline"><?php esc_html_e( 'Click here to set up progress indicator', 'formidable-pro' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Gets rootline type class.
	 *
	 * @param array $form_array Form array.
	 * @return string
	 */
	private static function get_rootline_type_class( $form_array ) {
		/**
		 * Filters the backend rootline class.
		 *
		 * @since 6.9
		 *
		 * @param string $class      Rootline class.
		 * @param array  $form_array Form array.
		 */
		return apply_filters( 'frm_pro_rootline_class', 'FrmProRootline', $form_array );
	}

	/**
	 * Gets rootline object.
	 *
	 * @param array $form_array Form array.
	 * @return object
	 */
	public static function get_rootline_obj( $form_array ) {
		$class = self::get_rootline_type_class( $form_array );
		if ( ! $class || ! class_exists( $class ) ) {
			return new FrmProRootline( $form_array );
		}

		return new $class( $form_array );
	}

	/**
	 * Shows rootline settings.
	 *
	 * @param object $rootline Rootline object.
	 */
	private static function show_rootline_settings( $rootline ) {
		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-forms/rootline-settings.php';
	}

	public static function save_rootline_to_form_options( &$options ) {
		$post_data = FrmAppHelper::get_post_param( 'frm_rootline', array() );
		if ( ! $post_data ) {
			return;
		}

		$options['rootline']             = isset( $post_data['type'] ) ? $post_data['type'] : '';
		$options['pagination_position']  = isset( $post_data['position'] ) ? $post_data['position'] : '';
		$options['rootline_titles']      = isset( $post_data['titles'] ) ? $post_data['titles'] : array();
		$options['rootline_titles_on']   = isset( $post_data['show_titles'] );
		$options['rootline_numbers_off'] = isset( $post_data['hide_numbers'] );
		$options['rootline_lines_off']   = isset( $post_data['hide_lines'] );
	}

	/**
	 * Gets rootline types.
	 *
	 * @return array
	 */
	public static function get_rootline_types() {
		/**
		 * Filters the rootline types.
		 *
		 * @since 6.9
		 *
		 * @param array Rootline types, with keys are types, values are labels.
		 */
		return apply_filters(
			'frm_pro_rootline_types',
			array(
				'rootline' => __( 'Show Rootline', 'formidable-pro' ),
				'progress' => __( 'Show Progress bar', 'formidable-pro' ),
			)
		);
	}
}
