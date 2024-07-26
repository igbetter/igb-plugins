(function($) {
    FWP.hooks.addAction('facetwp/refresh/time_since', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-radio.checked').each(function() {
            var val = $(this).attr('data-value');
            if ('' != val) {
                selected_values.push(val);
            }
        });
        FWP.facets[facet_name] = selected_values;
    });

    FWP.hooks.addFilter('facetwp/selections/time_since', function(output, params) {
        var labels = [];
        $.each(params.selected_values, function(val) {
            var label = params.el.find('.facetwp-radio[data-value="' + val + '"]').clone();
            label.find('.counts').remove();
            labels.push(label.text());
        });
        return labels.join(' / ');
    });

    $(document).on('click', '.facetwp-type-time_since .facetwp-radio', function() {
        var $facet = $(this).closest('.facetwp-facet');
        var facet_name = $facet.attr('data-name');
        $facet.find('.facetwp-radio').removeClass('checked');
        $(this).addClass('checked');
        if ('' != $(this).attr('data-value')) {
            FWP.frozen_facets[facet_name] = 'soft';
        }
        FWP.autoload();
    });
})(fUtil);
