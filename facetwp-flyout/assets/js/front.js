(function($) {
    window.FWP = window.FWP || {};
    window.FWP.flyout = {
        open: function() {
            FWP.flyout.swapFacets('open');
        },
        close: function() {
            FWP.flyout.swapFacets('close');
        },
        getOrderedFacets: function() {
            var facets = [];

            $('.facetwp-facet').each(function() {
                var name = $(this).attr('data-name');
                var type = $(this).attr('data-type');
                var blacklist = ['map', 'pager'];

                if (! blacklist.includes(type)) {
                    facets.push(name);
                }
            });

            return FWP.hooks.applyFilters('facetwp/flyout/facets', facets);
        },
        init: function() {
            var content = '';
            var facet_html = `
            <div class="flyout-row name-{name}">
                <h3>{label}</h3>
                <div class="flyout-item"></div>
            </div>
            `;

            $.each(FWP.flyout.getOrderedFacets(), function(facet_name) {

                // Support for custom HTML
                var temp = FWP.hooks.applyFilters('facetwp/flyout/facet_html', facet_html, {
                    facet_name: facet_name
                });
                temp = temp.split('{label}').join(FWP.settings.labels[facet_name]);
                temp = temp.split('{name}').join(facet_name);
                content += temp;
            });

            // Custom flyout content
            $tpl = $('.facetwp-flyout-tpl');
            var layout = $tpl.len() ? $tpl.html() : '{content}';

            var flyout = `
            <div class="facetwp-flyout">
                <div class="facetwp-flyout-close">x</div>
                <div class="facetwp-flyout-wrap">
                    <div class="facetwp-flyout-content">${layout}</div>
                </div>
            </div>
            <div class="facetwp-flyout-fog"></div>
            `;

            // Hook to modify flyout HTML
            flyout = FWP.hooks.applyFilters('facetwp/flyout/flyout_html', flyout);
            flyout = flyout.replace('{content}', content);

            if (0 < $('.facetwp-flyout').len()) {
                $('.facetwp-flyout').nodes[0].remove();
                $('.facetwp-flyout-fog').nodes[0].remove();
            }

            $('body').append(flyout);
        },
        swapFacets: function(action) {
            var action = ('undefined' !== typeof action) ? action : 'open';
            var is_open = $('.facetwp-flyout.active').len() > 0;

            if ((is_open && 'open' == action) || (!is_open && 'close' == action)) {
                return;
            }

            // Loop through each facet
            $.each(FWP.flyout.getOrderedFacets(), function(facet_name) {

                if ('open' == action) {
                    var $this = $('.facetwp-facet-' + facet_name).nodes[0];
                    var $dest = $('.flyout-row.name-' + facet_name + ' .flyout-item:not(.ready)').nodes[0];
                    $this.insertAdjacentHTML('afterend', '<div class="placeholder-' + facet_name + '"></div>');
                    $dest.appendChild($this);
                    $dest.classList.add('ready');
                }
                else {
                    var $this = $('.flyout-item.ready .facetwp-facet-' + facet_name).nodes[0];
                    $this.parentNode.classList.remove('ready');
                    var $dest = $('.placeholder-' + facet_name).nodes[0];
                    $dest.parentNode.insertBefore($this, $dest.nextSibling);
                    $dest.remove();
                }
            });

            // Add the open or close CSS class
            var which = ('open' == action) ? 'addClass' : 'removeClass';
            $('.facetwp-flyout, .facetwp-flyout-fog')[which]('active');

            // Custom action
            FWP.hooks.doAction('facetwp/flyout/' + action);
        },
        hideEmptyFacets: function() {
            $.each(FWP.settings.num_choices, function(num_choices, facet_name) {
                var $el = $('.flyout-row.name-' + facet_name);
                var which = (num_choices < 1) ? 'addClass' : 'removeClass';
                $el[which]('facetwp-hidden');
            });
            $('.facetwp-hide-empty').each(function() {
                let $this = $(this);
                let facet_name = $this.closest('.facetwp-facet').attr('data-name');
                var $el = $('.flyout-row.name-' + facet_name);
                let facets = $this.nodes[0]._facets;
                var which = facets.every(val => FWP.facets[val].length < 1) ? 'addClass' : 'removeClass';
                $el[which]('facetwp-hidden');
            });
        }
    };

    $().on('facetwp-loaded', function() {
        if (!FWP.loaded) {
            FWP.flyout.init();
        }
        FWP.flyout.hideEmptyFacets();
    });

    $().on('click', '.facetwp-flyout-open', function(e) {
        e.preventDefault();
        FWP.flyout.open();
    });

    $().on('click', '.facetwp-flyout-close, .facetwp-flyout-fog', function() {
        FWP.flyout.close();
    });
})(fUtil);
