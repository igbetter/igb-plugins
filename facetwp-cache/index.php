<?php
/*
Plugin Name: FacetWP - Cache
Description: Caching support for FacetWP
Version: 1.7
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-cache
*/

defined( 'ABSPATH' ) or exit;

class FacetWP_Cache
{

    private static $instance;


    function __construct() {

        // setup variables
        define( 'FACETWP_CACHE_VERSION', '1.7' );
        define( 'FACETWP_CACHE_DIR', dirname( __FILE__ ) );

        add_action( 'init' , [ $this, 'init' ] );
        add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 999 );
        add_action( 'deactivate_facetwp-cache/index.php', [ $this, 'deactivate' ] );

        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
    }


    /**
     * Singleton
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**
     * Intialize
     */
    function init() {

        // upgrade
        include( FACETWP_CACHE_DIR . '/includes/class-upgrade.php' );
        $upgrade = new FacetWP_Cache_Upgrade();

        add_filter( 'facetwp_ajax_response', [ $this, 'save_cache' ], 10, 2 );
        add_action( 'facetwp_inject_template', [ $this, 'inject_template' ] );
        add_action( 'facetwp_cache_cleanup', [ $this, 'cleanup' ] );

        // Schedule daily cleanup
        if ( ! wp_next_scheduled( 'facetwp_cache_cleanup' ) ) {
            wp_schedule_single_event( time() + 86400, 'facetwp_cache_cleanup' );
        }

        // Manually purge cache
        if ( isset( $_GET['fwpcache'] ) && current_user_can( 'manage_options' ) ) {
            $this->cleanup( $_GET['fwpcache'] );
        }
    }


    /**
     * Cache the AJAX response
     */
    function save_cache( $output, $params ) {
        global $wpdb;

        // Caching support
        if ( defined( 'FACETWP_CACHE' ) && FACETWP_CACHE ) {
            $data = $params['data'];

            // Generate the cache token
            $cache_name = md5( json_encode( $data ) );
            $cache_uri = $data['http_params']['uri'];

            // Set the cache expiration
            $cache_lifetime = apply_filters( 'facetwp_cache_lifetime', 3600, [
                'uri' => $cache_uri
            ]);

            $nocache = isset( $data['http_params']['get']['nocache'] );

            if ( false === $nocache ) {
                $wpdb->insert( $wpdb->prefix . 'facetwp_cache', [
                    'name' => $cache_name,
                    'uri' => $cache_uri,
                    'value' => $output,
                    'expire' => date( 'Y-m-d H:i:s', time() + $cache_lifetime )
                ]);
            }
        }

        return $output;
    }


    /**
     * Support CSS-based templates
     * Save the cached output right before PHP shutdown
     */
    function inject_template( $output ) {
        $data = stripslashes_deep( $_POST['data'] );
        $this->save_cache( json_encode( $output ), [ 'data' => $data ] );
    }


    /**
     * Delete expired cache
     */
    function cleanup( $uri = false ) {
        global $wpdb;

        if ( false === $uri ) {
            $now = date( 'Y-m-d H:i:s' );
            $wpdb->query( "DELETE FROM {$wpdb->prefix}facetwp_cache WHERE expire < '$now'" );
        }
        elseif ( 'all' == $uri ) {
            $wpdb->query( "TRUNCATE {$wpdb->prefix}facetwp_cache" );
        }
        else {
            $uri = ( 'this' == $uri ) ? $this->get_uri() : $uri;
            $wpdb->query(
                $wpdb->prepare( "DELETE FROM {$wpdb->prefix}facetwp_cache WHERE uri = %s", $uri )
            );
        }
    }


    /**
     * Activation hook
     */
    function activate() {
        if ( ! file_exists( $db = WP_CONTENT_DIR . '/db.php' ) && function_exists( 'symlink' ) ) {
            @symlink( plugin_dir_path( __FILE__ ) . 'db.php', $db );
        }
    }


    /**
     * Deactivation hook
     */
    function deactivate() {
        $this->cleanup( 'all' );
    }


    /**
     *
     */
    function admin_bar_menu( $wp_admin_bar ) {

        // Only show the menu on the front-end
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $args = [
            [
                'id' => 'fwp-cache',
                'title' => 'FWP',
            ],
            [
                'parent' => 'fwp-cache',
                'id' => 'fwp-cache-clear-all',
                'title' => 'Clear cache (all)',
                'href' => add_query_arg( 'fwpcache', 'all' )
            ]
        ];

        if ( ! is_admin() ) {
            $args[] = [
                'parent' => 'fwp-cache',
                'id' => 'fwp-cache-clear-page',
                'title' => 'Clear cache (this page)',
                'href' => add_query_arg( 'fwpcache', 'this' )
            ];
        }

        foreach ( $args as $arg ) {
            $wp_admin_bar->add_node( $arg );
        }
    }


    /**
     * Get the current page URI
     */
    function get_uri() {
        $uri = $_SERVER['REQUEST_URI'];
        if ( false !== ( $pos = strpos( $uri, '?' ) ) ) {
            $uri = substr( $uri, 0, $pos );
        }
        return trim( $uri, '/' );
    }
}


function FWP_Cache() {
    return FacetWP_Cache::instance();
}


$fwp_cache = FWP_Cache();
