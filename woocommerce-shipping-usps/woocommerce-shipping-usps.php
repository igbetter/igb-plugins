<?php
/**
 * Plugin Name: WooCommerce USPS Shipping
 * Plugin URI: https://woocommerce.com/products/usps-shipping-method/
 * Description: Obtain shipping rates dynamically via the USPS Shipping API for your orders.
 * Version: 4.6.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Tested up to: 6.0
 * WC requires at least: 2.6
 * WC tested up to: 7.1
 * Copyright: © 2022 WooCommerce
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Woo: 18657:83d1524e8f5f1913e58889f83d442c32
 *
 * https://www.usps.com/webtools/htm/Rate-Calculators-v1-5.htm
 *
 * @package woocommerce-shipping-usps
 */

/**
 * Please read the Terms and Conditions of Use, indicate your acceptance or non-acceptance and then click the SUBMIT button at the end to register for  * the APIs.
 *
 * TERMS AND CONDITIONS OF USE
 * USPS Web Tools APPLICATION PROGRAM INTERFACES (APIs)
 *
 * ACKNOWLEDGEMENT AND ACCEPTANCE. Read this carefully before registering to use the USPS Web Tools Application Program Interface (API) servers. By *  utilizing the APIs, you hereby ACCEPT ALL of the terms and conditions of this agreement.
 *
 * LICENSE GRANT. The United States Postal Service (USPS or Postal Service) grants to the Business User, including customers of Developer, and  Developer (* jointly referred to as "User"), a worldwide, nonexclusive, nontransferable, royalty-free license to interface with USPS Web Tool (API)  servers to use * the trademarks/logos and USPS data received via the interfaces in accordance with this agreement, the USPS Web Tool User Guides, and  the Software * Distributor Policy Guide.
 *
 * INTELLECTUAL PROPERTY RIGHTS. The sample code, the documentation, and the trademarks/logos provided on this site and in hardcopy form are the *  intellectual property of USPS protected under U.S. laws. The information and images presented may not be reproduced, republished, adopted, used, or *  modified under any circumstances.
 *
 * USE REQUIREMENTS FOR BUSINESS USER AND DEVELOPER.
 * * User agrees to use the USPS Web site in accordance with any additional requirements that may be posted on the Web site screens, emails, provided  in * the USPS Web Tool Kit User Guides, or in the Software Distributors Policy Guide.
 * * User agrees to use the USPS Web site, APIs and USPS data to facilitate USPS shipping transactions only.
 * * The trademarks/logos and USPS data received via the interfaces may not be used in any way that implies endorsement or sponsorship by USPS of the *  User or any of the User's products, goods or services.
 * * User may not use the interface in any way that adversely affects the performance or function of the USPS Web Tools (API) servers.
 * * User may not post or transmit information or materials that would violate rights of any third party or which contains a virus or other harmful *  component.
 * * User agrees to provide and keep updated, complete and accurate information about User upon registration for the APIs.
 *
 * ADDITIONAL USE REQUIREMENTS FOR BUSINESS USER.
 * * Business User is responsible to maintain the confidentiality of its password and ID as specified in the registration process.
 * * Business User may not package software which interfaces with any or all USPS APIs with password and ID for resale or distribution to others.
 * * Business User may reuse and distribute the API documentation and sample code in order to provide API access to customers and affiliates.
 *
 * ADDITIONAL USE REQUIREMENTS FOR DEVELOPER
 * * Developer may package software which interfaces with any or all USPS APIs with password and ID for resale or distribution to others only after *  registering with USPS as a Developer and agreeing to these Terms and Conditions of Use.
 * * Developers shall distribute these USPS Terms and Conditions of Use with its software to its customers and any other Business User.
 *
 * DISCLAIMER OF WARRANTIES. THE MATERIALS IN THE WEB TOOLS DOCMENTATION SITE (WWW.USPS.COM/WEBTOOLS), THE SOFTWARE DESCRIBED ON AND DISTRIBUTED FROM *  SAID SITE, AND THE APPLICATION PROGRAM INTERFACES DESCRIBED ON SAID SITE ARE PROVIDED "AS IS" AND WITHOUT WARRANTIES OF ANY KIND EITHER EXPRESS OR *  IMPLIED. TO THE FULLEST EXTENT PERMISSIBLE PURSUANT TO APPLICABLE LAW, USPS DISCLAIMS ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING, BUT NOT LIMITED *  TO, IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE POSTAL SERVICE DOES NOT WARRANT OR REPRESENT THAT THE INFORMATION  * IS ACCURATE OR RELIABLE OR THAT THE SITE OR INTERFACES WILL BE FREE OF ERRORS OR VIRUSES.
 *
 * LIMITATION OF LIABILITY. UNDER NO CIRCUMSTANCES, INCLUDING BUT NOT LIMITED TO NEGLIGENCE, WILL USPS BE LIABLE FOR DIRECT, SPECIAL OR CONSEQUENTIAL *  DAMAGES THAT RESULT FROM THE USE OR INABILITY TO USE THE MATERIALS IN THE WEB TOOLS DOCUMENTATION SITE (WWW.USPS.COM/WEBTOOLS) OR THE APPLICATION *  PROGRAM INTERFACES REFERENCED AND DESCRIBED IN SAID SITE. IN NO EVENT SHALL USPS BE LIABLE TO A USER FOR ANY LOSS, DAMAGE OR CLAIM.
 *
 * TERMINATION. This agreement is effective until terminated by either party. Upon termination of this agreement, User shall immediately cease to use *  USPS APIs, all associated documentation, and trademarks/logos, and shall destroy all copies thereof in the control or possession of User.
 *
 * INDEMNIFICATION. User will indemnify and hold USPS harmless from all claims, damages, costs and expenses related to the operation of the User's Web  * site in conjunction with USPS Web site interface. User shall permit USPS to participate in any defense and shall seek USPS's written consent prior  to * entering into any settlement.
 *
 * MAILER RESPONSIBILITY. A mailer must comply with all applicable postal standards, including those in the Domestic Mail Manual and the International  * Mail Manual. In the event of a conflict between USPS Web site information and Mail Manual information, the USPS Mail Manuals will control.
 *
 * APPLICABLE LAW. This agreement shall be governed by United States Federal law.
 *
 * PRIVACY ACT STATEMENT. Collection of this information is authorized by 39 U.S.C. 401 and 404. This information will be used to provide User with *  Postal Service Web Tools information. The Postal Service may disclose this information to a government agency for law enforcement purposes; in a legal  * proceeding to which the USPS is a party or has an interest; to a government agency when relevant to a decision concerning employment, security *  clearances, contracts, licenses, grants or other benefits; to a person under contract with the USPS to fulfill an agency function; to an independent *  certified public accountant during an official audit of USPS finances, and for any other use authorized by law. Providing the information is *  voluntary; however, without the information, we cannot respond to your expressed interest in receiving, using or accessing the USPS Web Tools.
 *
 * I acknowledge, I have read, and understand the above terms and conditions and I am authorized to accept this agreement on behalf of stated User *  company.
 */

include_once dirname( __FILE__ ) . '/includes/trait-util.php';

use WooCommerce\USPS\Util;

define( 'WC_USPS_VERSION', '4.6.0' ); // WRCS: DEFINED_VERSION.

// Plugin init hook.
add_action( 'plugins_loaded', 'wc_shipping_usps_init' );

// Subscribe to automated translations.
add_filter( 'woocommerce_translations_updates_for_' . basename( __FILE__, '.php' ), '__return_true' );
/**
 * Initialize plugin.
 */
function wc_shipping_usps_init() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		require_once dirname( __FILE__ ) . '/includes/tgmpa/class-tgm-plugin-activation.php';

		add_action( 'tgmpa_register', 'wc_shipping_usps_register_required_plugins' );

		return;
	}

	new WC_USPS();
}

/**
 * Register required plugins for TGM Plugin Activation.
 */
function wc_shipping_usps_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(
		array(
			'name'     => 'WooCommerce',
			'slug'     => 'woocommerce',
			'required' => true,
		),
	);

	/*
	 * Configuration settings.
	 */
	$config = array(
		'id'           => 'woocommerce-shipping-usps',
		'default_path' => '',
		'menu'         => 'tgmpa-install-plugins',
		'parent_slug'  => 'plugins.php',
		'capability'   => 'manage_options',
		'has_notices'  => true,
		'dismissable'  => false,
		'dismiss_msg'  => '',
		'is_automatic' => true,
		'message'      => '',
	);

	tgmpa( $plugins, $config );
}

/**
 * WooCommerce Deactivated Notice.
 */
function wc_shipping_usps_woocommerce_deactivated() {
	/* translators: %s: WooCommerce link */
	echo '<div class="error"><p>' . sprintf( esc_html__( 'WooCommerce USPS Shipping requires %s to be installed and active.', 'woocommerce-shipping-usps' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</p></div>';
}

register_activation_hook( __FILE__, 'wc_shipping_usps_activation_check' );

/**
 * Check plugin can run
 */
function wc_shipping_usps_activation_check() {
	if ( ! function_exists( 'simplexml_load_string' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die( 'Sorry, but you cannot run this plugin, it requires the SimpleXML library installed on your server/hosting to function.' );
	}
}

/**
 * WC_USPS class
 */
class WC_USPS {

	use Util;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_install' ), 5 );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'woocommerce_shipping_init', array( $this, 'init' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_method' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_notices', array( $this, 'environment_check' ) );
		add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
		add_action( 'wp_ajax_usps_dismiss_upgrade_notice', array( $this, 'dismiss_upgrade_notice' ) );

		include_once dirname( __FILE__ ) . '/includes/shipping-debug/class-wc-shipping-debug.php';
		add_action( 'wp_enqueue_scripts', array( 'WCShippingDebug', 'enqueue_resources' ) );

		include_once dirname( __FILE__ ) . '/includes/class-wc-shipping-usps-admin.php';
		if ( is_admin() ) {
			new WC_Shipping_USPS_Admin();
		}
	}

	/**
	 * Check the environment.
	 *
	 * @return void
	 */
	public function environment_check() {
		if ( version_compare( WC_VERSION, '2.6.0', '<' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$admin_page              = 'wc-settings';
		$is_usps_gate_registered = isset( WC()->shipping->get_shipping_methods()['usps'] ) && is_a( WC()->shipping->get_shipping_methods()['usps'], 'WC_Shipping_USPS' ) ? true : false;

		if ( get_woocommerce_currency() !== 'USD' ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'USPS requires that the <a href="%s">currency</a> is set to US Dollars.', 'woocommerce-shipping-usps' ), admin_url( 'admin.php?page=' . $admin_page . '&tab=general' ) ) . '</p>
			</div>';
		} elseif ( ! in_array( WC()->countries->get_base_country(), array( 'US', 'PR', 'VI', 'MH', 'FM' ), true ) ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'USPS requires that the <a href="%s">base country/region</a> is the United States.', 'woocommerce-shipping-usps' ), admin_url( 'admin.php?page=' . $admin_page . '&tab=general' ) ) . '</p>
			</div>';
		} elseif ( $is_usps_gate_registered && '150WOOTH2143' === WC()->shipping->get_shipping_methods()['usps']->get_option( 'user_id' ) || ( $is_usps_gate_registered && empty( WC()->shipping->get_shipping_methods()['usps']->get_option( 'user_id' ) ) && $this->instances_exist() ) ) {
			echo '<div class="error">
				<p>' . sprintf(
					__( 'The WooCommerce USPS User ID your site is currently using is no longer valid. Registering for an account at USPS is now required. <br />Please register for an %1$saccount at USPS%2$s and %3$senter your user ID here%2$s.', 'woocommerce-shipping-usps' ),
					'<a href="https://www.usps.com/business/web-tools-apis/welcome.htm" target="_blank">',
					'</a>',
					'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=usps' ) ) . '" target="_blank">'
				) . '</p>
			</div>';
		} elseif ( $is_usps_gate_registered && empty( WC()->shipping->get_shipping_methods()['usps']->get_option( 'user_id' ) ) ) {
			echo '<div class="error">
				<p>' . sprintf(
					__( 'WooCommerce USPS Shipping plugin requires you to %1$sregister for an account at USPS%2$s and %3$senter your user ID here%2$s.', 'woocommerce-shipping-usps' ),
					'<a href="https://www.usps.com/business/web-tools-apis/welcome.htm" target="_blank">',
					'</a>',
					'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=usps' ) ) . '" target="_blank">'
				) . '</p>
			</div>';
		}
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-shipping-usps', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Declare High-Performance Order Storage (HPOS) compatibility
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#declaring-extension-incompatibility
	 *
	 * @return void
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'woocommerce-shipping-usps/woocommerce-shipping-usps.php' );
		}
	}

	/**
	 * Settings page links.
	 *
	 * @param array $links List of plugin URLs.
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=usps' ) . '">' . __( 'Settings', 'woocommerce-shipping-usps' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Plugin page links to support and documentation
	 *
	 * @param  array  $links List of plugin links.
	 * @param  string $file Current file.
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_shipping_usps_docs_url', 'https://docs.woocommerce.com/document/usps-shipping-method/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-shipping-usps' ) ) . '">' . __( 'Docs', 'woocommerce-shipping-usps' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_shipping_usps_support_url', 'https://woocommerce.com/my-account/create-a-ticket/?select=18657' ) ) . '" title="' . esc_attr( __( 'Open a support request at WooCommerce.com', 'woocommerce-shipping-usps' ) ) . '">' . __( 'Support', 'woocommerce-shipping-usps' ) . '</a>',
			);
			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}

	/**
	 * Load gateway class
	 */
	public function init() {
		require_once __DIR__ . '/includes/class-wc-usps-privacy.php';
		include_once __DIR__ . '/includes/class-wc-shipping-usps.php';
	}

	/**
	 * Add method to WC
	 *
	 * @param array $methods List of shipping methods.
	 */
	public function add_method( $methods ) {
		if ( version_compare( WC_VERSION, '2.6.0', '<' ) ) {
			$methods[] = 'WC_Shipping_USPS';
		} else {
			$methods['usps'] = 'WC_Shipping_USPS';
		}

		return $methods;
	}

	/**
	 * Enqueue scripts
	 */
	public function scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	/**
	 * Checks the plugin version
	 *
	 * @since 4.4.0
	 * @version 4.4.0
	 * @return bool
	 */
	public function maybe_install() {
		// Only need to do this for versions less than 4.4.0 to migrate
		// settings to shipping zone instance.
		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		if ( ! $doing_ajax
			&& ! defined( 'IFRAME_REQUEST' )
			&& version_compare( WC_VERSION, '2.6.0', '>=' )
			&& version_compare( get_option( 'wc_usps_version' ), '4.4.0', '<' ) ) {

			$this->install();

		}

		return true;
	}

	/**
	 * Update/migration script
	 *
	 * @since 4.4.0
	 * @version 4.4.0
	 */
	public function install() {
		// get all saved settings and cache it.
		$usps_settings = get_option( 'woocommerce_usps_settings', false );

		// settings exists.
		if ( $usps_settings ) {
			global $wpdb;

			// unset un-needed settings.
			unset( $usps_settings['enabled'] );
			unset( $usps_settings['availability'] );
			unset( $usps_settings['countries'] );

			// first add it to the "rest of the world" zone when no usps
			// instance.
			if ( ! $this->is_zone_has_usps( 0 ) ) {
				$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}woocommerce_shipping_zone_methods ( zone_id, method_id, method_order, is_enabled ) VALUES ( %d, %s, %d, %d )", 0, 'usps', 1, 1 ) );
				// add settings to the newly created instance to options table.
				$instance = $wpdb->insert_id;
				add_option( 'woocommerce_usps_' . $instance . '_settings', $usps_settings );
			}

			update_option( 'woocommerce_usps_show_upgrade_notice', 'yes' );
		}

		update_option( 'wc_usps_version', WC_USPS_VERSION );
	}

	/**
	 * Show the user a notice for plugin updates
	 *
	 * @since 4.4.0
	 */
	public function upgrade_notice() {
		$show_notice = get_option( 'woocommerce_usps_show_upgrade_notice' );

		if ( 'yes' !== $show_notice ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$query_args      = array(
			'page' => 'wc-settings',
			'tab'  => 'shipping',
		);
		$zones_admin_url = add_query_arg( $query_args, get_admin_url() . 'admin.php' );
		?>
		<div class="notice notice-success is-dismissible wc-usps-notice">
			<p><?php echo sprintf( __( 'USPS now supports shipping zones. The zone settings were added to a new USPS method on the "Rest of the World" Zone. See the zones %1$shere%2$s ', 'woocommerce-shipping-usps' ), '<a href="' . $zones_admin_url . '">', '</a>' ); ?></p>
		</div>

		<script type="application/javascript">
			jQuery( '.notice.wc-usps-notice' ).on( 'click', '.notice-dismiss', function () {
				wp.ajax.post('usps_dismiss_upgrade_notice');
			});
		</script>
		<?php
	}

	/**
	 * Turn of the dismisable upgrade notice.
	 *
	 * @since 4.4.0
	 */
	public function dismiss_upgrade_notice() {
		update_option( 'woocommerce_usps_show_upgrade_notice', 'no' );
	}
}
