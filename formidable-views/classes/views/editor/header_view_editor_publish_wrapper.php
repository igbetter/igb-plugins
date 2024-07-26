<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

FrmAppHelper::get_admin_header(
	array(
		'label'   => empty( $title ) ? __( 'Untitled', 'formidable-views' ) : $title,
		'publish' => array( 'FrmViewsEditorController::publish_button', array() ),
		'close'   => admin_url( 'edit.php?post_type=' . FrmViewsDisplaysController::$post_type ),
	)
);
