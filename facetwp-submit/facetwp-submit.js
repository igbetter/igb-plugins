(function($) {
    $().on('click', '.fwp-submit', function() {
        FWP.parseFacets();

        var href = $(this).attr('data-href');
        var query_string = FWP.buildQueryString();

        if (query_string.length) {
            var prefix = (-1 < href.indexOf('?')) ? '&' : '?';
            href += prefix + query_string;
        }

        window.location.href = href;
    });
})(fUtil);
