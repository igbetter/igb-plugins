<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmChatAppController {

	/**
	 * @var FrmChatHtmlHelper|null $html_helper
	 */
	private static $html_helper;

	/**
	 * @var stdClass $form
	 */
	private static $form;

	/**
	 * @return void
	 */
	public static function load_hooks() {
		// actions
		add_action( 'frm_include_front_css', array( __CLASS__, 'include_chat_css' ) );
		add_action( 'frm_enqueue_stripe_scripts', array( __CLASS__, 'add_form_object_filter' ), 9 );
		add_action( 'wp_ajax_frm_add_form_row', array( __CLASS__, 'add_repeater_row_hooks' ), 1 );
		add_action( 'wp_ajax_nopriv_frm_add_form_row', array( __CLASS__, 'add_repeater_row_hooks' ), 1 );
		add_action( 'init', array( __CLASS__, 'load_lang' ) );

		// filters
		add_filter( 'frm_pre_get_form', array( __CLASS__, 'maybe_init_chat_form' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ), 15 );

		if ( is_admin() ) {
			self::load_admin_hooks();
		}
	}

	/**
	 * @return void
	 */
	private static function load_admin_hooks() {
		// actions
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );

		// filters
		add_filter( 'frm_form_options_before_update', array( __CLASS__, 'update_options' ), 15 );
		add_filter( 'frm_form_strings', array( __CLASS__, 'add_form_strings' ), 10, 2 );
	}

	/**
	 * @return void
	 */
	public static function load_form_hooks() {
		?>
		<svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
			<defs>
				<symbol id="frm_trash_icon" viewBox="0 0 20 20" fill="none"><path d="M2.5 5h15M6.667 5V3.334a1.667 1.667 0 011.667-1.667h3.333a1.667 1.667 0 011.667 1.667V5m2.5 0v11.667a1.667 1.667 0 01-1.667 1.667H5.834a1.667 1.667 0 01-1.667-1.667V5h11.667z" stroke="#484E54" stroke-linecap="round" stroke-linejoin="round"/></symbol>
			</defs>
		</svg>
		<?php

		// actions
		add_action( 'frm_enqueue_form_scripts', array( __CLASS__, 'enqueue_scripts' ), 5 );

		// filters
		add_filter( 'frm_filter_final_form', array( __CLASS__, 'filter_final_form' ) );
		add_filter( 'frm_show_submit_button', array( __CLASS__, 'hide_submit_button' ) );
	}

	/**
	 * Remove conversational forms filters when a subsequent form is loaded after a conversational form.
	 *
	 * @return void
	 */
	private static function remove_form_hooks() {
		remove_action( 'frm_entry_form', array( __CLASS__, 'load_form_hooks' ) );
		remove_filter( 'frm_before_replace_shortcodes', array( __CLASS__, 'before_replace_shortcodes' ) );
		remove_filter( 'frm_filter_final_form', array( __CLASS__, 'filter_final_form' ) );
		remove_filter( 'frm_fields_container_class', array( __CLASS__, 'add_field_container_class' ) );
		remove_filter( 'frm_show_submit_button', array( __CLASS__, 'hide_submit_button' ) );
	}

	/**
	 * @return bool
	 */
	public static function hide_submit_button() {
		return false;
	}

	/**
	 * @return void
	 */
	public static function load_lang() {
		$plugin_folder_name = basename( FrmChatAppHelper::path() );
		load_plugin_textdomain( 'formidable-chat', false, $plugin_folder_name . '/languages/' );
	}

	/**
	 * @param stdClass $form
	 * @return stdClass
	 */
	public static function maybe_init_chat_form( $form ) {
		if ( ! empty( $form->options['chat'] ) ) {
			self::init_chat_form( $form );
		} else {
			self::remove_form_hooks();
		}
		return $form;
	}

	/**
	 * @param stdClass $form
	 * @return void
	 */
	private static function init_chat_form( $form ) {
		self::$form = $form;
		self::maybe_change_custom_style( $form );

		if ( isset( self::$html_helper ) ) {
			// guarantee that a subsequent conversational form loads a fresh HTML helper.
			self::$html_helper = new FrmChatHtmlHelper( $form );
		}

		// actions
		add_action( 'frm_entry_form', array( __CLASS__, 'load_form_hooks' ) );

		// filters
		self::add_chat_classes_filter();
		add_filter( 'frm_add_form_style_class', array( __CLASS__, 'add_form_container_class' ) );
		add_filter( 'frm_fields_container_class', array( __CLASS__, 'add_field_container_class' ) );

		self::get_helper()->extract_title_and_description();

		if ( self::should_init_stripe_js( $form ) ) {
			self::init_stripe_js();
		}
	}

	/**
	 * Determine if Stripe Lite is being used before initializing JS.
	 *
	 * @since 1.1
	 *
	 * @param stdClass $form
	 * @return bool
	 */
	private static function should_init_stripe_js( $form ) {
		if ( class_exists( 'FrmStrpHooksController', false ) ) {
			// Conversational forms are covered in frmstrp.js when the Stripe add on is active.
			return false;
		}

		if ( ! class_exists( 'FrmStrpLiteActionsController', true ) ) {
			// Stripe Lite is not available, so return false.
			return false;
		}

		$action = FrmStrpLiteActionsController::get_stripe_link_action( $form->id );
		return is_object( $action );
	}

	/**
	 * Enqueue additional scripts required to support Stripe Lite.
	 * The script does a few things:
	 * - It marks the Authentication element as a conversational field.
	 * - It adjusts the progress bar total amount to include the Authentication element.
	 * - It checks if Stripe elements are complete before enabling the next button.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	private static function init_stripe_js() {
		$suffix       = FrmChatAppHelper::js_suffix();
		$dependencies = array( 'formidable', 'formidable_chat' );
		wp_register_script( 'formidable_chat_stripe', FrmChatAppHelper::plugin_url( '/js/stripe' . $suffix . '.js' ), $dependencies, FrmChatAppHelper::plugin_version(), true );

		wp_enqueue_script( 'formidable_chat_stripe' );
	}

	/**
	 * @return void
	 */
	private static function add_chat_classes_filter() {
		add_filter( 'frm_before_replace_shortcodes', array( __CLASS__, 'before_replace_shortcodes' ), 10, 4 );
	}

	/**
	 * @since 1.0.01
	 *
	 * @return void
	 */
	public static function admin_init() {
		self::include_updater();
		FrmChatMigrate::init();
	}

	/**
	 * @return void
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			include FrmChatAppHelper::path() . '/classes/models/FrmChatUpdate.php';
			FrmChatUpdate::load_hooks();
		}
	}

	/**
	 * @return void
	 */
	public static function enqueue_scripts() {
		$suffix       = FrmChatAppHelper::js_suffix();
		$dependencies = array( 'formidable' );
		wp_register_script( 'formidable_chat', FrmChatAppHelper::plugin_url( '/js/chat' . $suffix . '.js' ), $dependencies, FrmChatAppHelper::plugin_version(), true );

		wp_enqueue_script( 'formidable_chat' );
	}

	/**
	 * @param string   $html
	 * @param array    $field
	 * @param array    $errors
	 * @param stdClass $form
	 * @return string
	 */
	public static function before_replace_shortcodes( $html, $field, $errors, $form ) {
		return self::get_helper()->maybe_hide_field( compact( 'html', 'field', 'errors', 'form' ) );
	}

	/**
	 * @param string $classes
	 * @return string
	 */
	public static function add_form_container_class( $classes ) {
		return $classes . ' frm_chat_form_cont';
	}

	/**
	 * @param string $class_string
	 * @return string
	 */
	public static function add_field_container_class( $class_string ) {
		return str_replace( 'class="', 'class="frm_chat_form ', $class_string );
	}

	/**
	 * @return FrmChatHtmlHelper
	 */
	private static function get_helper() {
		if ( ! isset( self::$html_helper ) ) {
			self::$html_helper = new FrmChatHtmlHelper( self::$form );
		}
		return self::$html_helper;
	}

	/**
	 * Add the filter early when a form is loaded for frm_form_object before Stripe adds Script variables.
	 * Stripe style settings get set from from frm_enqueue_stripe_scripts which is called from frm_setup_new_fields_vars.
	 *
	 * @since 1.0.03
	 *
	 * @param array $atts {
	 *     @type string $form_id
	 * }
	 * @return void
	 */
	public static function add_form_object_filter( $atts ) {
		$form_id = (int) $atts['form_id'];
		add_filter(
			'frm_form_object',
			/**
			 * @param stdClass|null $form
			 * @param int           $form_id
			 * @return stdClass|null
			 */
			function( $form ) use ( $form_id ) {
				if ( is_object( $form ) && $form_id === (int) $form->id ) {
					$form = self::filter_form_object( $form );
				}
				return $form;
			}
		);
	}

	/**
	 * Filter form object so the custom style is consistent with every form object.
	 *
	 * @since 1.0.03
	 *
	 * @param stdClass $form
	 * @return stdClass
	 */
	private static function filter_form_object( $form ) {
		if ( ! empty( $form->options['chat'] ) ) {
			self::maybe_change_custom_style( $form );
		}
		return $form;
	}

	/**
	 * A conversational form uses the "Lines" template by default. This gets downloaded when Conversational forms are activated.
	 *
	 * @since 1.0.03 Moved to a separate function from self::init_chat_form so it could also be called from self::filter_form_object.
	 *
	 * @param stdClass $form
	 * @return void
	 */
	private static function maybe_change_custom_style( $form ) {
		$uses_default_style = empty( $form->options['custom_style'] ) || 1 === (int) $form->options['custom_style'];
		if ( ! $uses_default_style ) {
			// Only change to "Lines" style if set to default so that custom styles work as expected.
			return;
		}

		$style_api               = new FrmChatStyleTemplateApi();
		$conversational_style_id = $style_api->get_conversational_style();
		if ( $conversational_style_id ) {
			$form->options['custom_style'] = $conversational_style_id;
		}
	}

	/**
	 * @param string $form
	 * @return string
	 */
	public static function filter_final_form( $form ) {
		$helper = self::get_helper();

		$wrapper  = $helper->get_button_wrapper();
		$wrapper .= $helper->maybe_get_continue_button();
		$wrapper .= $helper->maybe_get_arrow_navigation();
		$wrapper .= $helper->maybe_get_key_instructions();
		$wrapper .= '</div>'; // closing .frm-chat-wrapper

		$form = preg_replace( '/<\/form>/', '</form>' . $wrapper, $form, 1 );
		$form = $helper->maybe_inject_start_page( $form );

		$progress_html = $helper->maybe_get_progress_html();
		if ( $progress_html ) {
			$form = preg_replace( '/<div class="frm_chat_form/', $progress_html . '<div class="frm_chat_form', $form, 1 );
		}

		return $form;
	}

	/**
	 * @return void
	 */
	public static function include_chat_css() {
		readfile( FrmChatAppHelper::path() . '/css/chat.css' );
	}

	/**
	 * Updates the form Chat settings.
	 *
	 * @param array $options The form options.
	 *
	 * @return array
	 */
	public static function update_options( $options ) {
		if ( ! empty( $options['chat'] ) ) {
			if ( empty( $options['js_validate'] ) ) {
				// always turn on JavaScript validation for conversational forms.
				$options['js_validate'] = 1;
			}
			if ( ! empty( $options['save_draft'] ) ) {
				// saving drafts is not supported by conversational forms.
				$options['save_draft'] = 0;
			}
			$options['chat_show_start_page'] = ! empty( $options['chat_show_start_page'] ) ? 1 : 0;
		}

		foreach ( array( 'chat_continue_text', 'chat_start_button_text' ) as $chat_option ) {
			if ( ! empty( $options[ $chat_option ] ) ) {
				$options[ $chat_option ] = FrmAppHelper::kses( $options[ $chat_option ], 'all' );
			}
		}

		return $options;
	}

	/**
	 * @since 1.0.01
	 *
	 * @param array  $strings
	 * @param object $form
	 * @return array<string>
	 */
	public static function add_form_strings( $strings, $form ) {
		if ( ! empty( $form->options['chat'] ) ) {
			$strings[] = 'chat_start_button_text';
			$strings[] = 'chat_continue_text';
		}
		return $strings;
	}

	/**
	 * @return void
	 */
	public static function add_repeater_row_hooks() {
		$field_id = FrmAppHelper::get_post_param( 'field_id', '', 'absint' );
		if ( ! $field_id ) {
			return;
		}

		$field = FrmField::getOne( $field_id );
		if ( ! $field ) {
			return;
		}

		$form = FrmForm::getOne( $field->form_id );
		if ( $form && ! empty( $form->options['chat'] ) ) {
			self::$form = $form;
			self::add_chat_classes_filter();
		}
	}

	/**
	 * @param array $classes
	 * @return array
	 */
	public static function body_class( $classes ) {
		if ( ! in_array( 'single-frm_landing_page', $classes, true ) || ! self::active_post_is_landing_page() ) {
			return $classes;
		}

		global $post;
		$form_id = get_post_meta( $post->ID, 'frm_landing_form_id', true );

		if ( ! $form_id || ! is_numeric( $form_id ) ) {
			return $classes;
		}

		$form = FrmForm::getOne( $form_id );
		if ( ! $form || empty( $form->options['chat'] ) ) {
			return $classes;
		}

		$classes[] = 'frm_conversational_landing_page';
		if ( ! empty( $form->options['chat_progress_type'] ) && in_array( $form->options['chat_progress_type'], array( 'bar', 'both' ), true ) ) {
			$classes[] = 'frm_with_progress_bar';
		}

		return $classes;
	}

	/**
	 * @since 1.0.01
	 *
	 * @return bool
	 */
	private static function active_post_is_landing_page() {
		global $post;
		return $post instanceof WP_Post && 'frm_landing_page' === $post->post_type;
	}
}
