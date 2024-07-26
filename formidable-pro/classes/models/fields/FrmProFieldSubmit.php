<?php
/**
 * Pro Submit field
 *
 * @since 6.9
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmProFieldSubmit
 */
class FrmProFieldSubmit extends FrmFieldSubmit {

	/**
	 * Registers extra field options.
	 *
	 * @return array
	 */
	protected function extra_field_opts() {
		$opts = parent::extra_field_opts();

		$opts['edit_text']        = __( 'Update', 'formidable-pro' );
		$opts['align']            = '';
		$opts['start_over']       = '';
		$opts['start_over_label'] = __( 'Start Over', 'formidable-pro' );

		return $opts;
	}

	/**
	 * Shows primary options.
	 *
	 * @param array $args Args.
	 */
	public function show_primary_options( $args ) {
		parent::show_primary_options( $args );

		$field = $args['field'];

		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/field-submit.php';
	}
}
