<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsAppHelper {

	/**
	 * @var string
	 */
	public static $plug_version = '5.6.1';

	/**
	 * @var FrmViewsSettings|null
	 */
	private static $settings;

	/**
	 * @return string
	 */
	public static function plugin_version() {
		return self::$plug_version;
	}

	public static function plugin_folder() {
		return basename( self::plugin_path() );
	}

	public static function plugin_path() {
		return dirname( dirname( __DIR__ ) );
	}

	public static function views_path() {
		return self::plugin_path() . '/classes/views';
	}

	public static function plugin_url() {
		return plugins_url( '', self::plugin_path() . '/formidable-views.php' );
	}

	public static function relative_plugin_url() {
		return str_replace( array( 'https:', 'http:' ), '', self::plugin_url() );
	}

	/**
	 * @return bool
	 */
	public static function use_minified_js_file() {
		if ( self::debug_scripts_are_on() && self::has_unminified_js_file() ) {
			return false;
		}
		return self::has_minified_js_file();
	}

	/**
	 * @return bool
	 */
	public static function debug_scripts_are_on() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	/**
	 * @return string
	 */
	public static function js_suffix() {
		return self::use_minified_js_file() ? '.min' : '';
	}

	/**
	 * @return bool
	 */
	public static function has_unminified_js_file() {
		return is_readable( self::plugin_path() . '/js/editor.js' );
	}

	/**
	 * @return bool
	 */
	public static function has_minified_js_file() {
		return is_readable( self::plugin_path() . '/js/editor.min.js' );
	}

	/**
	 * @param array $arr
	 * @return array
	 */
	public static function reset_keys( $arr ) {
		$new_arr = array();
		if ( ! $arr ) {
			return $new_arr;
		}

		foreach ( $arr as $val ) {
			$new_arr[] = $val;
			unset( $val );
		}
		return $new_arr;
	}

	/**
	 * @return void
	 */
	public static function add_dom_script() {
		$version = self::plugin_version();
		wp_register_script( 'formidable_views_dom', self::plugin_url() . '/js/dom.js', array(), $version, true );
		wp_enqueue_script( 'formidable_views_dom' );
	}

	/**
	 * @return void
	 */
	public static function add_modal_css() {
		$version = self::plugin_version();
		wp_register_style( 'formidable_views_modal', self::plugin_url() . '/css/modal.css', array(), $version );
		wp_enqueue_style( 'formidable_views_modal' );
	}

	/**
	 * @return void
	 */
	public static function maybe_redirect_old_view_editor() {
		if ( ! is_admin() ) {
			return;
		}

		if ( self::creating_a_new_view() ) {
			$trid        = FrmAppHelper::get_param( 'trid', '', 'get', 'absint' );
			$lang        = FrmAppHelper::get_param( 'lang', '', 'get', 'sanitize_text_field' );
			$source_lang = FrmAppHelper::get_param( 'source_lang', '', 'get', 'sanitize_text_field' );
			if ( $trid && $lang && $source_lang ) {
				$view_id = self::create_translation_copy( $trid, $lang, $source_lang );
			}
			if ( empty( $view_id ) ) {
				$view_id = FrmViewsDisplay::create();
			}
			self::redirect_to_view_editor( $view_id );
		} elseif ( self::visiting_the_legacy_view_editor_url() ) {
			$post_id = FrmAppHelper::get_param( 'post', '', 'get', 'absint' );
			self::redirect_to_view_editor( $post_id );
		}
	}

	/**
	 * @param int    $trid
	 * @param string $lang
	 * @param string $source_lang
	 * @return int view id
	 */
	private static function create_translation_copy( $trid, $lang, $source_lang ) {
		$view_id = FrmDb::get_var(
			'icl_translations',
			array(
				'trid'          => $trid,
				'language_code' => $source_lang,
			),
			'element_id'
		);

		if ( $view_id ) {
			$view    = get_post( $view_id );
			$form_id = get_post_meta( $view_id, 'frm_form_id', true );
			$type    = get_post_meta( $view_id, 'frm_show_count', true );
			$view_id = FrmViewsDisplay::create( $form_id, $type );
			self::insert_wpml_translation( $view_id, $trid, $lang, $source_lang );
		}

		return $view_id;
	}

	/**
	 * @param int    $view_id
	 * @param int    $trid
	 * @param string $lang
	 * @param string $source_lang
	 */
	private static function insert_wpml_translation( $view_id, $trid, $lang, $source_lang ) {
		global $wpdb;
		$where = array(
			'trid'          => $trid,
			'language_code' => $lang,
		);
		if ( FrmDb::get_var( 'icl_translations', $where, 'translation_id' ) ) {
			// avoid duplicates.
			return;
		}
		$wpdb->insert(
			$wpdb->prefix . 'icl_translations',
			array(
				'element_type'         => 'post_frm_display',
				'element_id'           => $view_id,
				'trid'                 => $trid,
				'language_code'        => $lang,
				'source_language_code' => $source_lang,
			)
		);
	}

	/**
	 * Duplicated views however could go to the old editor, so need to check the original view for new editor support first.
	 *
	 * @return bool true if we're creating a new view, and we should be redirecting to the new editor.
	 */
	private static function creating_a_new_view() {
		global $pagenow;
		if ( 'post-new.php' !== $pagenow ) {
			return false;
		}
		$duplicating_view_id = FrmAppHelper::get_param( 'copy_id', '', 'get', 'absint' );
		if ( $duplicating_view_id ) {
			self::maybe_duplicate_and_redirect( $duplicating_view_id );
			return false;
		}
		$post_type = FrmAppHelper::get_param( 'post_type' );
		return FrmViewsDisplaysController::$post_type === $post_type;
	}

	/**
	 * @param int $view_id
	 * @return void
	 */
	private static function maybe_duplicate_and_redirect( $view_id ) {
		$new_view_id = FrmViewsDisplay::duplicate( $view_id );
		self::redirect_to_view_editor( $new_view_id );
	}

	/**
	 * @return bool
	 */
	private static function visiting_the_legacy_view_editor_url() {
		global $pagenow;
		if ( 'post.php' !== $pagenow || 'edit' !== FrmAppHelper::get_param( 'action' ) ) {
			return false;
		}
		$post_id = FrmAppHelper::get_param( 'post', '', 'get', 'absint' );
		if ( ! $post_id ) {
			return false;
		}
		$post = get_post( $post_id );
		return $post && FrmViewsDisplaysController::$post_type === $post->post_type;
	}

	/**
	 * @return bool true if the active page is the new view editor.
	 */
	public static function view_editor_is_active() {
		return FrmAppHelper::is_admin_page( 'formidable-views-editor' );
	}

	/**
	 * Check if we're on the views listing page.
	 * This is only called when we're on a formidable page so the check can be pretty generic.
	 *
	 * @return bool
	 */
	public static function is_on_views_listing_page() {
		global $pagenow;
		if ( 'edit.php' !== $pagenow ) {
			return false;
		}
		return ! self::view_editor_is_active();
	}

	/**
	 * @param int $view_id
	 * @return string
	 */
	public static function get_url_to_view_editor( $view_id ) {
		$format = '?page=formidable-views-editor&view=%d';
		$path   = sprintf( $format, $view_id );
		return admin_url( $path );
	}

	/**
	 * @param int $view_id
	 * @return void
	 */
	private static function redirect_to_view_editor( $view_id ) {
		$url = self::get_url_to_view_editor( $view_id );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * @param object $post
	 * @return bool
	 */
	private static function view_is_a_calendar_type( $post ) {
		$show_count = get_post_meta( $post->ID, 'frm_show_count', true );
		return 'calendar' === $show_count;
	}

	/**
	 * Try to make the new view editor look similar to the legacy view editor so that other plugins still properly load.
	 *
	 * @return void
	 */
	public static function emulate_legacy_view_editor() {
		global $pagenow;
		$pagenow = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		global $post_type_object;
		$post_type_object       = new stdClass(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post_type_object->name = FrmViewsDisplaysController::$post_type;

		global $title;
		if ( is_null( $title ) ) {
			$title = __( 'Views', 'formidable-views' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$_GET['action'] = 'edit'; // WPML has an is_edit_action check that requires this to render the "Translate this Document" table.

		$view_id = FrmAppHelper::get_param( 'view', '', 'get', 'absint' );
		if ( $view_id ) {
			$view = get_post( $view_id );
			self::force_view_as_post_global( $view );
		}

		if ( ! function_exists( 'add_meta_box' ) ) {
			// Required for WP Client and for All-in-One Event Calendar to work without issues.
			require_once ABSPATH . '/wp-admin/includes/template.php';
		}

		add_action( 'registered_post_type', 'FrmViewsEditorController::setup_screen_after_view_post_type_is_registered' );
		add_action( 'admin_init', 'FrmViewsEditorController::call_load_post_action_on_admin_init' );

		add_filter(
			'submenu_file',
			function () {
				return 'edit.php?post_type=frm_display';
			}
		);
	}

	/**
	 * Force the global $post object.
	 *
	 * @param object $view
	 * @return void
	 */
	public static function force_view_as_post_global( $view ) {
		global $post;
		$post = $view; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/* Genesis Integration */
	public static function load_genesis() {
		// Add classes to view pagination
		add_filter( 'frm_pagination_class', 'FrmViewsAppHelper::gen_pagination_class' );
		add_filter( 'frm_prev_page_label', 'FrmViewsAppHelper::gen_prev_label' );
		add_filter( 'frm_next_page_label', 'FrmViewsAppHelper::gen_next_label' );
		add_filter( 'frm_prev_page_class', 'FrmViewsAppHelper::gen_prev_class' );
		add_filter( 'frm_next_page_class', 'FrmViewsAppHelper::gen_next_class' );
		add_filter( 'frm_page_dots_class', 'FrmViewsAppHelper::gen_dots_class', 1 );
	}

	/**
	 * @param string $class
	 * @return string
	 */
	public static function gen_pagination_class( $class ) {
		$class .= ' archive-pagination pagination';
		return $class;
	}

	public static function gen_prev_label() {
		return apply_filters( 'genesis_prev_link_text', '&#x000AB;' . __( 'Previous Page', 'formidable-views' ) );
	}

	public static function gen_next_label() {
		return apply_filters( 'genesis_next_link_text', __( 'Next Page', 'formidable-views' ) . '&#x000BB;' );
	}

	/**
	 * @param string $class
	 * @return string
	 */
	public static function gen_prev_class( $class ) {
		$class .= ' pagination-previous';
		return $class;
	}

	/**
	 * @param string $class
	 * @return string
	 */
	public static function gen_next_class( $class ) {
		$class .= ' pagination-next';
		return $class;
	}

	/**
	 * @param string $class
	 * @return string
	 */
	public static function gen_dots_class( $class ) {
		$class = 'pagination-omission';
		return $class;
	}
	/* End Genesis */

	/**
	 * @return string
	 */
	public static function get_default_content_filter() {
		return 'limited';
	}

	/**
	 * Check if the unserialized content is in the new format.
	 *
	 * @param mixed $content
	 * @return bool
	 */
	public static function unserialized_content_is_grid_format( $content ) {
		_deprecated_function( __METHOD__, '5.1', 'FrmViewsContentHelper::content_is_an_array' );
		if ( ! is_array( $content ) ) {
			return false;
		}
		return isset( $content[0] ) && isset( $content[0]['box'] );
	}

	/**
	 * Get a limit value for the visual views previews, to ensure values like 1000000 only try show a partial set in the preview.
	 *
	 * @return int
	 */
	public static function get_visual_views_preview_limit() {
		return apply_filters( 'frm_visual_views_preview_limit', 1000 );
	}

	/**
	 * @since 5.2
	 *
	 * @param int $view_id
	 * @return array<int>
	 */
	public static function get_application_ids_for_view( $view_id ) {
		if ( ! taxonomy_exists( 'frm_application' ) ) {
			return array();
		}

		$terms = wp_get_object_terms( $view_id, 'frm_application' );
		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return wp_list_pluck( $terms, 'term_id' );
	}

	/**
	 * Get Settings object for Views.
	 *
	 * @since 5.3
	 *
	 * @return FrmViewsSettings
	 */
	public static function get_settings() {
		if ( ! isset( self::$settings ) ) {
			self::$settings = new FrmViewsSettings();
		}
		return self::$settings;
	}

	/**
	 * @return void
	 */
	public static function add_view_embed_examples_script() {
		$plugin_url      = self::plugin_url();
		$version         = self::plugin_version();
		$js_dependencies = array( 'wp-i18n', 'formidable_embed' );
		wp_register_script( 'formidable_views_embed_examples', $plugin_url . '/js/embed.js', $js_dependencies, $version, true );
		wp_enqueue_script( 'formidable_views_embed_examples' );
	}
}
