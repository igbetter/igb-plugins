<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProSimpleBlocksController {

	/**
	 * Adds View values to info sent to block JS
	 *
	 * @param $script_vars
	 *
	 * @return mixed
	 */
	public static function block_editor_assets() {
		$script_vars = array(
			'forms' => self::get_calc_forms(),
		);

		wp_register_script(
			'formidable-calculator-block',
			FrmProAppHelper::plugin_url() . '/js/block_calculator.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor' ),
			FrmProDb::$db_version,
			true
		);

		wp_localize_script( 'formidable-calculator-block', 'formidable_block_calculator', $script_vars );
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'formidable-calculator-block', 'formidable-pro', FrmProAppHelper::plugin_path() . '/languages' );
		}

		// Register Views block assets for Formidable Views < 5.6
		self::backward_compatibility_register_views_block_assets();
	}

	/**
	 * For backward compatibility with older versions of Formidable Views < 5.6
	 * The views block was moved to the Views plugin in version 5.6+
	 *
	 * The assets that can be later removed are: 
	 * - js/src/view/block.js
	 * - js/src/view/inspector.js 
	 * - js/src/view/viewselect.js 
	 * - js/src/view/viewshortcode.js
	 * - js/block_views.js
	 *
	 * @since 6.9.1
	 *
	 * @return void
	 */
	private static function backward_compatibility_register_views_block_assets() {
		if ( is_callable( 'FrmViewsSimpleBlocksController::block_editor_assets' ) ) {
			return;
		}

		$script_vars = array(
			'views'        => self::get_views_options(),
			'show_counts'  => is_callable( 'FrmViewsDisplaysHelper::get_show_counts' ) ? FrmViewsDisplaysHelper::get_show_counts() : array(),
			'view_options' => FrmProDisplaysHelper::get_frm_options_for_views( 'limit' ),
			'name'         => FrmAppHelper::get_menu_name() . ' ' . __( 'Views', 'formidable-views' ),
		);

		wp_register_script(
			'formidable-view-selector',
			FrmProAppHelper::plugin_url() . '/js/block_views.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor' ),
			FrmProDb::$db_version,
			true
		);

		wp_localize_script( 'formidable-view-selector', 'formidable_view_selector', $script_vars );
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'formidable-view-selector', 'formidable-pro', FrmProAppHelper::plugin_path() . '/languages' );
		}
	}

	/**
	 * Returns an array of Views options with name as the label and the id as the value, sorted by label
	 *
	 * @return array
	 */
	private static function get_views_options() {
		if ( is_callable( 'FrmViewsSimpleBlocksController::get_views_options' ) ) {
			return FrmViewsSimpleBlocksController::get_views_options();
		}
		return array();
	}

	/**
	 * Returns a filtered list of form options with the name as label and the id as value, sorted by label.
	 * Get all total fields and calculated fields.
	 *
	 * @since 4.05
	 * @return array
	 */
	private static function get_calc_forms() {
		$where      = array(
			'or'   => 1,
			'type' => 'total',
			array(
				'field_options like'     => '"calc";s:',
				'field_options not like' => '"calc";s:0:',
			),
		);
		$calc_forms = FrmDb::get_col( 'frm_fields', $where, 'form_id' );
		$calc_forms = array_unique( (array) $calc_forms );

		return self::get_forms( $calc_forms );
	}

	/**
	 * @param array $ids
	 */
	private static function get_forms( $ids ) {
		$forms = FrmForm::getAll(
			array(
				'is_template' => 0,
				'status'      => 'published',
				'id'          => $ids,
			),
			'name'
		);
		return self::set_form_options( $forms );
	}

	/**
	 * Returns an array for a form with name as label and id as value
	 *
	 * @since 4.05
	 * @param $form
	 * @return array
	 */
	private static function set_form_options( $forms ) {
		$list   = array();
		$parent = array();
		foreach ( $forms as $form ) {
			if ( ! empty( $form->parent_form_id ) ) {
				$parent[] = $form->parent_form_id;
			} else {
				$list[ $form->id ] = array(
					'label' => $form->name,
					'value' => $form->id,
				);
			}
		}

		if ( ! empty( $parent ) ) {
			$parent = array_diff( $parent, array_keys( $list ) );
			if ( ! empty( $parent ) ) {
				$parents = self::get_forms( $parent );
				$list   += $parents;
			}
		}

		$list = array_values( $list );
		return $list;
	}

	/**
	 * Registers simple View block
	 */
	public static function register_simple_view_block() {
		if ( ! is_callable( 'register_block_type' ) ) {
			return;
		}

		if ( is_admin() ) {
			// register back-end scripts
			add_action( 'enqueue_block_editor_assets', 'FrmProSimpleBlocksController::block_editor_assets' );
		}

		register_block_type(
			'formidable/calculator',
			array(
				'attributes'      => array(
					'formId'      => array(
						'type' => 'string',
					),
					'title'       => array(
						'type' => 'string',
					),
					'description' => array(
						'type' => 'string',
					),
					'minimize'    => array(
						'type' => 'string',
					),
					'className'   => array(
						'type' => 'string',
					),
				),
				'editor_style'    => 'formidable',
				'editor_script'   => 'formidable-calculator-block',
				'render_callback' => 'FrmSimpleBlocksController::simple_form_render',
			)
		);
	}

	/**
	 * @since 5.5.2
	 */
	public static function before_simple_form_render() {
		self::maybe_process_frm_set_get_shortcode();
	}

	/**
	 * Fixes Pro issue #3853. [frm-set-get] shortcodes don't work if the form is in a Gutenberg block.
	 *
	 * @since 5.5.2
	 *
	 * @return void
	 */
	private static function maybe_process_frm_set_get_shortcode() {
		global $post;
		if ( ! is_object( $post ) || empty( $post->post_content ) ) {
			return;
		}

		if ( ! self::should_process_frm_set_get_shortcode( $post->post_content ) ) {
			return;
		}

		$pattern = get_shortcode_regex( array( 'frm-set-get', 'frm_set_get' ) );
		preg_replace_callback(
			"/$pattern/",
			/**
			 * Process frm-set-get/frm_set_get shortcode.
			 *
			 * @param array $match
			 * @return void
			 */
			function ( $match ) {
				do_shortcode( $match[0] );
			},
			$post->post_content
		);
	}

	/**
	 * Do a soft check for the frm-set-get shortcode before trying to do a regex calblack.
	 *
	 * @since 5.5.2
	 *
	 * @param string $post_content
	 * @return bool True if the value should be processed.
	 */
	private static function should_process_frm_set_get_shortcode( $post_content ) {
		return false !== strpos( $post_content, '[frm-set-get' ) || false !== strpos( $post_content, '[frm_set_get' );
	}
}
