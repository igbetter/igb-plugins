<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 1.0.01
 */
class FrmChatMigrate {

	/**
	 * @var int $new_version
	 */
	private $new_version = 1;

	/**
	 * @var string $option_name
	 */
	private $option_name = 'frm_chat_version';

	/**
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
	 * @return bool
	 */
	private function needs_migration() {
		return $this->get_version_from_db() < $this->new_version;
	}

	/**
	 * @return int the version saved in the options table.
	 */
	private function get_version_from_db() {
		return (int) get_option( $this->option_name, 0 );
	}

	/**
	 * @return void
	 */
	private function update_version() {
		update_option( $this->option_name, $this->new_version );
	}

	/**
	 * @return void
	 */
	public static function init() {
		new self();
	}

	/**
	 * @return void
	 */
	private static function update_stylesheet() {
		$frm_style = new FrmStyle();
		$frm_style->update( 'default' );
	}
}
