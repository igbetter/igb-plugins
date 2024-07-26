<?php
/*
Plugin Name: FacetWP - Flyout menu
Description: Displays a flyout facet menu
Version: 0.8.3
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-flyout
*/

defined( 'ABSPATH' ) or exit;

class FacetWP_Flyout_Addon
{

    function __construct() {
        define( 'FACETWP_FLYOUT_VERSION', '0.8.3' );
        define( 'FACETWP_FLYOUT_URL', plugins_url( '', __FILE__ ) );

        add_filter( 'facetwp_assets', array( $this, 'assets' ) );
    }


    function assets( $assets ) {
        $assets['facetwp-flyout.js'] = [ FACETWP_FLYOUT_URL . '/assets/js/front.js', FACETWP_FLYOUT_VERSION ];
        $assets['facetwp-flyout.css'] = [ FACETWP_FLYOUT_URL . '/assets/css/front.css', FACETWP_FLYOUT_VERSION ];
        return $assets;
    }
}


new FacetWP_Flyout_Addon();
