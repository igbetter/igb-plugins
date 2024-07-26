<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * FrmViewsMigrate class.
 *
 * @since 5.3.1
 */
class FrmViewsMigrate {

	/**
	 * FrmViewsMigrate instance.
	 *
	 * @since 5.3.1
	 *
	 * @var FrmViewsMigrate|null
	 */
	private static $instance = null;

	/**
	 * New addon version.
	 *
	 * @since 5.3.1
	 *
	 * @var string
	 */
	private $new_version = '5.3.1';

	/**
	 * Option name.
	 *
	 * @since 5.3.1
	 *
	 * @var string
	 */
	private $option_name = 'frm_views_version';

	/**
	 * Get the instance of class.
	 *
	 * @since 5.3.1
	 *
	 * @return FrmViewsMigrate
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor check for migration.
	 *
	 * @since 5.3.1
	 *
	 * @return void
	 */
	private function __construct() {
		if ( $this->needs_migration() ) {
			// For now, there are no migrate functions.
			// We just need to update the stylesheet with every new version.
			self::update_stylesheet();
			$this->update_version();
		}
	}

	/**
	 * Version compare between the old version and new one.
	 *
	 * @since 5.3.1
	 *
	 * @return bool
	 */
	private function needs_migration() {
		return version_compare( $this->get_version_from_db(), $this->new_version, '<' );
	}

	/**
	 * Get current version of addon.
	 *
	 * @since 5.3.1
	 *
	 * @return string the version saved in the options table.
	 */
	private function get_version_from_db() {
		return strval( get_option( $this->option_name, 0 ) );
	}

	/**
	 * Update to new version.
	 *
	 * @since 5.3.1
	 *
	 * @return void
	 */
	private function update_version() {
		update_option( $this->option_name, $this->new_version );
	}

	/**
	 * Update stylesheet.
	 *
	 * @since 5.3.1
	 *
	 * @return void
	 */
	private static function update_stylesheet() {
		FrmViewsAppController::update_stylesheet();
	}
}
