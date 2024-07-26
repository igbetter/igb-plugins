<?php

class FacetWP_Facet_Time_Since_Addon extends FacetWP_Facet
{
    public $ui_fields;

    function __construct() {
        $this->label = __( 'Time Since', 'facetwp-time-since' );
        $this->fields = [ 'label_any', 'choices', 'ui_type' ];
        $this->ui_fields = [
            'dropdown' => [],
            'fselect' => [ 'ui_ghosts' ],
            'radio' => [ 'ui_ghosts' ]
        ];
    }


    /**
     * Parse the multi-line options string
     */
    function parse_choices( $choices ) {
        $choices = explode( "\n", trim( $choices ) );
        foreach ( $choices as $key => $choice ) {
            $temp = array_map( 'trim', explode( '|', $choice ) );

            $choices[ $key ] = [
                'label' => facetwp_i18n( $temp[0] ),
                'format' => $temp[1],
                'range' => $this->calculate_date_range( $temp[1] ),
                'counter' => 0,
            ];
        }

        return $choices;
    }


    function calculate_date_range( $format ) {
        $now = new \DateTime();

        try {
            $dt = new \DateTime( $format );
        }
        catch (Exception $e) {
            $dt = $now;
        }

        // defaults
        $range = [
            'lower' => $now->format( 'Y-m-d-H:i:s' ),
            'upper' => $now->format( 'Y-m-d-H:i:s' )
        ];

        // -X <day/week/month> OR last <day of week>
        if ( 0 === strpos( $format, '-' ) || 0 === strpos( $format, 'last' ) ) {
            $range['lower'] = $dt->format( 'Y-m-d-H:i:s' );
            $range['upper'] = $now->format( 'Y-m-d-H:i:s' );
        }
        // +X <day/week/month> OR next <day of week>
        elseif ( 0 === strpos( $format, '+' ) || 0 === strpos( $format, 'next' ) ) {
            $range['lower'] = $now->format( 'Y-m-d-H:i:s' );
            $range['upper'] = $dt->format( 'Y-m-d-H:i:s' );
        }
        // today
        elseif ( 'today' == $format ) {
            $range['lower'] = $dt->format( 'Y-m-d');
            $range['upper'] = $dt->format( 'Y-m-d') . '-23:59:59';
        }
        // tomorrow
        elseif ( 'tomorrow' == $format ) {
            $range['lower'] = $dt->format( 'Y-m-d' );
            $range['upper'] = $dt->format( 'Y-m-d' ) . '-23:59:59';
        }
        // yesterday
        elseif ( 'yesterday' == $format ) {
            $range['lower'] = $dt->format( 'Y-m-d' ) . '-00:00:00';
            $range['upper'] = $dt->format( 'Y-m-d' ) . '-23:59:59';
        }

        return apply_filters( 'facetwp_time_since_date_range', $range, $format );
    }


    /**
     * Load the available choices
     */
    function load_values( $params ) {
        global $wpdb;

        $output = [];
        $facet = $params['facet'];
        $from_clause = $wpdb->prefix . 'facetwp_index f';

        // Facet in "OR" mode
        $where_clause = $this->get_where_clause( $facet );

        $sql = "
        SELECT f.facet_display_value
        FROM $from_clause
        WHERE f.facet_name = '{$facet['name']}' $where_clause";
        $results = $wpdb->get_col( $sql );

        // Parse facet choices
        $choices = $this->parse_choices( $facet['choices'] );

        // Loop through the results
        foreach ( $results as $val ) {
            foreach ( $choices as $key => $choice ) {
                if ( $val >= $choice['range']['lower'] && $val <= $choice['range']['upper'] ) {
                    $choices[ $key ]['counter']++;
                }
            }
        }

        $ui_type = isset( $params['facet']['ui_type'] ) && '' != $params['facet']['ui_type'] ? $params['facet']['ui_type'] : 'radio'; // default type radio
        $show_ghosts = 'dropdown' == $ui_type ? false : FWP()->helper->facet_is( $facet, 'ui_ghosts', 'yes' );

        // Return an associative array
        foreach ( $choices as $choice ) {
            if ( 0 < $choice['counter'] || $show_ghosts ) {
                $output[] = [
                    'facet_value' => FWP()->helper->safe_value( $choice['label'] ),
                    'facet_display_value' => $choice['label'],
                    'counter' => $choice['counter'],
                ];
            }
        }

        return $output;
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {
        return FWP()->helper->facet_types['radio']->render( $params );
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $selected_values = is_array( $selected_values ) ? $selected_values[0] : $selected_values;

        $choices = $this->parse_choices( $facet['choices'] );

        foreach ( $choices as $key => $choice ) {
            $safe_value = FWP()->helper->safe_value( $choice['label'] );
            if ( $safe_value === $selected_values ) {
                $lower = $choice['range']['lower'];
                $upper = $choice['range']['upper'];

                $sql = "
                SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
                WHERE facet_name = %s AND facet_value >= %s AND facet_value <= %s";
                $sql = $wpdb->prepare( $sql, $facet['name'], $lower, $upper );
                return $wpdb->get_col( $sql );
            }
        }

        return [];
    }


    /**
     * Output any admin scripts
     */
    function admin_scripts() {
?>
<script>
(function($) {
    $(function() {
        FWP.hooks.addAction('facetwp/load/time_since', function($this, obj) {
            $this.find('.facet-source').val(obj.source);
            $this.find('.facet-choices').val(obj.choices);
        });
    
        FWP.hooks.addFilter('facetwp/save/time_since', function(obj, $this) {
            obj['source'] = $this.find('.facet-source').val();
            obj['choices'] = $this.find('.facet-choices').val();
            return obj;
        });
    });
})(jQuery);
</script>
<?php
    }


    /**
     * Output any front-end scripts
     */
    function front_scripts() {
        FWP()->display->assets['time-since-front.js'] = [ plugins_url( '', __FILE__ ) . '/assets/js/front.js', FACETWP_TIME_SINCE_VERSION ];
        $facets = FWP()->helper->get_facets_by( 'type', 'time_since' );
        $active_facets = array_keys( FWP()->display->active_facets );
        foreach ( $facets as $facet ) {
            if ( in_array( $facet['name'], $active_facets ) && isset( $facet['ui_type'] ) && '' != $facet['ui_type'] ) {
                $facet_class = FWP()->helper->facet_types[ $facet['ui_type'] ];
                if ( method_exists( $facet_class, 'front_scripts' ) ) {
                    $facet_class->front_scripts(); 
                }
            }
        }
    }


    function register_fields() {
        return [
            'choices' => [
                'type' => 'textarea',
                'label' => __( 'Choices', 'facetwp-time-since' ),
                'notes' => 'Enter a list of <a href="https://facetwp.com/help-center/facets/facet-types/time-since/#available-options" target="_blank">available choices</a> (one per line)'
            ]
        ];
    }
}
