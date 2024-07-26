<?php
/**
 * Handle the database interactions.
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\models;

use FrmSurveys\helpers\AppHelper;
use FrmStyle;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Database class.
 *
 * @since 1.0
 */
class DB {

	/**
	 * New DB version. Change me when the CSS changes.
	 *
	 * @var int
	 */
	private $new_db_version = 3;

	/**
	 * Option name used for storing DB version.
	 *
	 * @var string
	 */
	private $option_name = 'frm_surveys_db_version';

	/**
	 * Save the db version to the database.
	 */
	private function update_db_version() {
		update_option( $this->option_name, AppHelper::$plug_version . '-' . $this->new_db_version );
	}

	/**
	 * Check if need migrating.
	 *
	 * @return bool
	 */
	public function need_to_migrate() {
		return \FrmAppController::compare_for_update(
			array(
				'option'             => $this->option_name,
				'new_db_version'     => $this->new_db_version,
				'new_plugin_version' => AppHelper::$plug_version,
			)
		);
	}

	/**
	 * Migrate data to current version, if needed.
	 */
	public function migrate() {
		$this->update_db_version();
	}

	/**
	 * Reduces the DB version.
	 */
	public function reduce_db_version() {
		update_option( $this->option_name, AppHelper::$plug_version . '-' . ( $this->new_db_version - .1 ) );
	}
}
