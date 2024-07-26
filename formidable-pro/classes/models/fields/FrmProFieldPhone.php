<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 3.0
 */
class FrmProFieldPhone extends FrmFieldPhone {

	protected function field_settings_for_type() {
		$settings = parent::field_settings_for_type();

		$settings['autopopulate'] = true;
		$settings['unique']       = true;
		$settings['read_only']    = true;
		$settings['prefix']       = true;

		FrmProFieldsHelper::fill_default_field_display( $settings );
		return $settings;
	}

	/**
	 * Retrieves the HTML for an 'International' option in a dropdown.
	 *
	 * @since 6.9
	 *
	 * @return void Outputs the HTML option tag directly.
	 */
	protected function print_international_option() {
		?>
		<option value="international" <?php selected( FrmField::get_option( $this->field, 'format' ), 'international' ); ?>>
			<?php esc_html_e( 'International', 'formidable' ); ?>
		</option>
		<?php
	}

	/**
	 * @since 4.05
	 */
	protected function builder_text_field( $name = '' ) {
		$html  = FrmProFieldsHelper::builder_page_prepend( $this->field );
		$field = parent::builder_text_field( $name );
		return str_replace( '[input]', $field, $html );
	}

	protected function include_front_form_file() {
		$this->maybe_register_intl_phone_scripts();
		return parent::include_front_form_file();
	}

	/**
	 * @return void
	 */
	private function maybe_register_intl_phone_scripts() {
		if ( 'international' !== FrmField::get_option( $this->field, 'format' ) ) {
			return;
		}

		if ( FrmAppHelper::js_suffix() ) {
			// If we are using minified scripts, check if the intl phone input is included.
			// If it is return before we enqueue the scripts.
			$pro_js_files = FrmProAppController::get_pro_js_files( 'minified' );
			if ( isset( $pro_js_files['intl-tel-input'] ) ) {
				return;
			}
		}

		$files = FrmProAppController::get_intl_phone_js_details();
		foreach ( $files as $key => $file ) {
			FrmProAppController::register_js( $key, $file );
		}
	}
}
