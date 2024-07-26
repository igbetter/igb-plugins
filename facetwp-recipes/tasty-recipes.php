<?php

class FacetWP_Tasty_Recipes_Integration
{

    function __construct() {
        add_filter( 'facetwp_facet_sources', [ $this, 'register_facet_sources' ] );
        add_filter( 'facetwp_indexer_row_data', [ $this, 'build_index_data' ], 10, 2 );
    }


    function register_facet_sources( $sources ) {
        $choices = [
            'tasty/title' => 'Title',
            'tasty/author_name' => 'Author Name',
            'tasty/ingredients' => 'Ingredients',
            'tasty/instructions' => 'Instructions',
            'tasty/prep_time' => 'Prep Time',
            'tasty/cook_time' => 'Cook Time',
            'tasty/total_time' => 'Total Time',
            'tasty/yield' => 'Yield',
            'tasty/category' => 'Category',
            'tasty/method' => 'Method',
            'tasty/cuisine' => 'Cuisine',
            'tasty/diet' => 'Diet',
            'tasty/keywords' => 'Keywords',
            'tasty/video_url' => 'Video URL',
            'tasty/serving_size' => 'Serving size',
            'tasty/calories' => 'Calories',
            'tasty/sugar' => 'Sugar',
            'tasty/sodium' => 'Sodium',
            'tasty/fat' => 'Fat',
            'tasty/saturated_fat' => 'Saturated Fat',
            'tasty/unsaturated_fat' => 'Unsaturated Fat',
            'tasty/trans_fat' => 'Trans Fat',
            'tasty/carbohydrates' => 'Carbohydrates',
            'tasty/fiber' => 'Fiber',
            'tasty/protein' => 'Protein',
            'tasty/cholesterol' => 'Cholesterol',
        ];

        $sources['tasty-recipes'] = [
            'label' => 'Tasty Recipes',
            'choices' => $choices,
            'weight' => 10
        ];

        return $sources;
    }


    /**
     * Index recipe values
     * @since 0.1
     */
    function build_index_data( $rows, $params ) {
        $source = $params['defaults']['facet_source'];
        $post_id = $params['defaults']['post_id'];
        $facet_type = $params['facet']['type'];

        if ( 0 === strpos( $source, 'tasty/' ) ) {
            $source = str_replace( 'tasty/', '', $source );
            $recipe_ids = Tasty_Recipes()->get_recipe_ids_for_post( $post_id );

            foreach ( $recipe_ids as $recipe_id ) {
                $val = get_post_meta( $recipe_id, $source, true );

                // force numeric values
                if ( in_array( $facet_type, [ 'slider', 'number_range' ] ) ) {
                    $val = preg_replace( "/[^0-9.]/", '', $val );
                }

                $new_row = $params['defaults'];
                $new_row['facet_value'] = $val;
                $new_row['facet_display_value'] = $val;
                $rows[] = $new_row;
            }
        }

        return $rows;
    }
}

new FacetWP_Tasty_Recipes_Integration();
