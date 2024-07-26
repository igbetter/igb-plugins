<?php

class FacetWP_WP_Recipe_Maker_Integration
{

    function __construct() {
        add_filter( 'facetwp_facet_sources', [ $this, 'register_facet_sources' ] );
        add_filter( 'facetwp_indexer_row_data', [ $this, 'build_index_data' ], 10, 2 );
        add_filter( 'facetwp_indexer_query_args', [ $this, 'maybe_index_post_type' ], 5 );
        add_filter( 'facetwp_settings_admin', [ $this, 'settings_admin' ], 10, 2 );
        add_filter( 'facetwp_builder_item_value', [ $this, 'builder_item_value' ], 5, 2 );
    }


    function register_facet_sources( $sources ) {
        $taxonomies = WPRM_Taxonomies::get_taxonomies( true );
        $nutrition = WPRM_Nutrition::get_fields();

        $choices = [
            'wprm/author_name'          => 'Author Name',
            'wprm/rating_average'       => 'Average Rating',
            'wprm/servings'             => 'Servings',
            'wprm/cost'                 => 'Estimated Cost',
            'wprm/prep_time'            => 'Prep Time',
            'wprm/cook_time'            => 'Cook Time',
            'wprm/custom_time'          => 'Custom Time',
            'wprm/total_time'           => 'Total Time',
        ];

        foreach ( $taxonomies as $key => $meta ) {
            $choices[ 'wprm/' . $key ] = $meta['name'];
        }

        foreach ( $nutrition as $key => $meta ) {
            $choices[ 'wprm/nutrition_' . $key ] = $meta['label'];
        }

        if ( class_exists( 'WPRMPCF_Manager' ) ) {
            $fields = WPRMPCF_Manager::get_custom_fields();

            foreach ( $fields as $key => $meta ) {
                $choices[ 'wprm/custom_field_' . $key ] = $key;
            }
        }

        $sources['wp-recipe-maker'] = [
            'label' => 'WP Recipe Maker',
            'choices' => $choices,
            'weight' => 10
        ];

        // remove "wprm_" duplicates
        foreach ( $sources['taxonomies']['choices'] as $key => $label ) {
            if ( 0 === strpos( $key, 'tax/wprm_' ) ) {
                unset( $sources['taxonomies']['choices'][ $key ] );
            }
        }

        foreach ( $sources['custom_fields']['choices'] as $key => $label ) {
            if ( 0 === strpos( $key, 'cf/wprm_' ) ) {
                unset( $sources['custom_fields']['choices'][ $key ] );
            }
        }

        return $sources;
    }


    /**
     * Index recipe values
     * @since 0.1
     */
    function build_index_data( $rows, $params ) {
        $source = $params['defaults']['facet_source'];
        $post_id = $params['defaults']['post_id'];

        if ( 0 === strpos( $source, 'wprm/' ) ) {
            $source = str_replace( 'wprm/', '', $source );
            $valid_taxonomies = array_keys( WPRM_Taxonomies::get_taxonomies( true ) );
            $to_index = FWP()->helper->get_setting( 'recipe_index', 'post' );
            $recipe_ids = [];

            if ( 'wprm_recipe' == get_post_type( $post_id ) ) {
                if ( 'recipe' == $to_index || 'both' == $to_index ) {
                    $recipe_ids[] = $post_id;
                }
            }
            elseif ( 'post' == $to_index || 'both' == $to_index ) {
                $recipe_ids = WPRM_Recipe_Manager::get_recipe_ids_from_post( $post_id );
            }

            foreach ( $recipe_ids as $recipe_id ) {
                $recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

                if ( false !== $recipe ) {
                    if ( in_array( $source, $valid_taxonomies ) ) {
                        $terms = $recipe->tags( str_replace( 'wprm_', '', $source ) );
    
                        foreach ( $terms as $term ) {
                            $label = $term->name;

                            if ( 'wprm_suitablefordiet' == $source ) {
                                $label = get_term_meta( $term->term_id, 'wprm_term_label', true );
                            }

                            $new_row = $params['defaults'];
                            $new_row['facet_value'] = $term->slug;
                            $new_row['facet_display_value'] = $label;
                            $new_row['term_id'] = $term->term_id;
                            $rows[] = $new_row;
                        }
                    }
                    else {
                        if ( 'author_name' == $source ) {
                            $value = $recipe->author();
                        }
                        else {
                            $value = $recipe->meta( 'wprm_' . $source, '' );
                        }
    
                        $new_row = $params['defaults'];
                        $new_row['facet_value'] = $value;
                        $new_row['facet_display_value'] = $value;
                        $rows[] = $new_row;
                    }
                }
            }
        }

        return $rows;
    }


    /**
     * Maybe index the "wprm_recipe" post type
     * @since 0.1
     */
    function maybe_index_post_type( $args ) {
        if ( 'post' != FWP()->helper->get_setting( 'recipe_index', 'post' ) ) {
            if ( 'any' == $args['post_type'] ) {
                $args['post_type'] = array_keys( get_post_types( [ 'public' => true ] ) );
            }

            $args['post_type'][] = 'wprm_recipe';
        }

        return $args;
    }


    /**
     * Display proper layout builder values
     * Piggyback on $this->build_index_data() to grab data
     */
    function builder_item_value( $value, $item ) {
        global $post;

        if ( 0 === strpos( $item['source'], 'wprm/' ) ) {
            $params = [
                'defaults' => [
                    'facet_source' => $item['source'],
                    'post_id' => $post->ID
                ]
            ];

            $rows = $this->build_index_data( [], $params );
            $value = wp_list_pluck( $rows, 'facet_display_value' );
        }

        return $value;
    }


    /**
     * Register admin UI settings
     * @since 0.1
     */
    function settings_admin( $settings, $class ) {
        $settings['general']['fields']['recipe_index'] = [
            'label' => __( 'Index recipe data for', 'fwp' ),
            'html' => $class->get_setting_html( 'recipe_index', 'dropdown', [
                'choices' => [
                    'post'      => 'Blog posts',
                    'recipe'    => 'Recipes',
                    'both'      => 'Both'
                ]
             ])
        ];

        return $settings;
    }
}

new FacetWP_WP_Recipe_Maker_Integration();
