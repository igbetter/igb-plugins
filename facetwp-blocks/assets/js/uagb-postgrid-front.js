// Intercept of pagination clicks for Spectra Post Grid block with pagination enabled
(function($) {
  $().on('click', '.facetwp-template .uagb-post-pagination-wrap a', function(event) {
    event.preventDefault();
    var matches = $(this).attr('href').match(/\/page\/(\d+)/);
    FWP.paged = null !== matches ? parseInt(matches[1]) : 1;
    FWP.soft_refresh = true;
    FWP.refresh();
  });
})(fUtil);