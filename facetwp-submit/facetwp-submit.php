<?php
/*
Plugin Name: FacetWP - Submit button
Description: Adds a shortcode to generate a "Submit" button
Version: 0.4
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-submit
*/

defined( 'ABSPATH' ) or exit;

class FacetWP_Submit_Addon
{

    function __construct() {
        add_filter( 'facetwp_assets', array( $this, 'assets' ) );
        add_filter( 'facetwp_shortcode_html', array( $this, 'shortcode' ), 10, 2 );
    }


    function assets( $assets ) {
        $assets['facetwp-submit.js'] = plugins_url( '', __FILE__ ) . '/facetwp-submit.js';
        return $assets;
    }


    function shortcode( $output, $atts ) {
        if ( isset( $atts['submit'] ) ) {
            $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Submit', 'fwp-submit' );
            $output = '<button class="fwp-submit" data-href="' . esc_attr( $atts['submit'] ) . '">' . esc_attr( $label ) . '</button>';
        }
        return $output;
    }
}


new FacetWP_Submit_Addon();
