<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( '\Elementor\Widget_Base' ) ) {
	class FrmViewsElementorWidget extends \Elementor\Widget_Base {

		public function get_name() {
			return 'formidable-views';
		}

		public function get_title() {
			return FrmAppHelper::get_menu_name() . ' ' . __( 'Views', 'formidable-views' );
		}

		public function get_icon() {
			if ( is_callable( 'FrmAppHelper::get_menu_icon_class' ) ) {
				return FrmAppHelper::get_menu_icon_class();
			}
			return 'frmfont frm_logo_icon';
		}

		public function get_categories() {
			return array( 'general' );
		}

		protected function register_controls() {
			$this->start_controls_section(
				'section_form_dropdown',
				array(
					'label' => __( 'Select View', 'formidable-views' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'view_id',
				array(
					'label'   => __( 'View', 'formidable-views' ),
					'type'    => \Elementor\Controls_Manager::SELECT2,
					'options' => $this->get_view_options(),
				)
			);

			$this->end_controls_section();
		}

		private function get_view_options() {
			$views   = FrmViewsDisplay::getAll( array(), 'post_title' );
			$options = array( '' => '' );
			foreach ( $views as $view ) {
				$options[ $view->ID ] = esc_html( $view->post_title );
			}
			return $options;
		}

		protected function render() {
			$settings = $this->get_settings_for_display();
			$view_id  = isset( $settings['view_id'] ) ? absint( $settings['view_id'] ) : 0;

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo FrmViewsDisplaysController::get_shortcode( array( 'id' => $view_id ) );
		}
	}
}
