<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$form       = clone $form;
$form->name = $view->post_title;
FrmAppHelper::get_admin_header(
	array(
		'form'    => $form,
		'publish' => array( 'FrmViewsEditorController::publish_button', array() ),
		'close'   => admin_url( 'edit.php?post_type=' . FrmViewsDisplaysController::$post_type ),
	)
);
