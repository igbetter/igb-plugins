<?php
/*
Plugin Name: FacetWP - Time Since
Description: "Time Since" facet
Version: 1.7
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-time-since
*/

defined( 'ABSPATH' ) or exit;

define( 'FACETWP_TIME_SINCE_VERSION', '1.7' );


/**
 * FacetWP registration hook
 */
add_filter( 'facetwp_facet_types', function( $types ) {
    include( dirname( __FILE__ ) . '/class-time-since.php' );
    $types['time_since'] = new FacetWP_Facet_Time_Since_Addon();
    return $types;
} );
