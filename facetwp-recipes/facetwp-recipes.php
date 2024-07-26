<?php
/*
Plugin Name: FacetWP - Recipes integration
Description: Filter by recipe attributes
Version: 0.4.2
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-recipes
*/

defined( 'ABSPATH' ) or exit;

class FacetWP_Recipes_Addon
{
    function __construct() {
        add_action( 'plugins_loaded', [ $this, 'load_integration' ] );
    }

    function load_integration() {
        if ( class_exists( 'Tasty_Recipes' ) ) {
            include( dirname( __FILE__ ) . '/tasty-recipes.php' );
        }

        if ( class_exists( 'WP_Recipe_Maker' ) ) {
            include( dirname( __FILE__ ) . '/wp-recipe-maker.php' );
        }
    }
}

new FacetWP_Recipes_Addon();
