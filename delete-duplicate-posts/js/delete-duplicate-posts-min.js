"use strict";

/* globals jQuery:true, ajaxurl:true, cp_ddp:true  */

function cp_ddp_freemius_opt_in(element) {
  // eslint-disable-line no-unused-vars
  var nonce = jQuery('#cp-ddp-freemius-opt-nonce').val(); // Nonce.
  var choice = jQuery(element).data('opt'); // Choice.
  jQuery('#cp-ddp-opt-spin').addClass('is-active');
  jQuery.ajax({
    type: 'POST',
    url: ajaxurl,
    async: true,
    data: {
      action: 'cp_ddp_freemius_opt_in',
      opt_nonce: nonce,
      choice: choice
    },
    success: function () {
      jQuery('#cp-ddp-opt-spin').removeClass('is-active');
      location.reload();
    },
    error: function (xhr, textStatus, error) {
      console.log(xhr.statusText);
      console.log(textStatus);
      console.log(error);
    }
  });
}
jQuery(document).ready(function () {
  ddp_refresh_log();
  jQuery('#ddp_redirtable').DataTable({
    "processing": true,
    "serverSide": true,
    "autoWidth": true,
    "ordering": true,
    "ajax": {
      "url": ajaxurl,
      "type": "POST",
      "data": function (d) {
        return jQuery.extend({}, d, {
          "action": "ddp_get_redirects",
          "_ajax_nonce": cp_ddp.nonce
        });
      },
      "dataSrc": function (json) {
        return json.data;
      }
    },
    "columns": [{
      "data": "ID",
      "visible": false
    }, {
      "data": "from_url",
      "title": "From URL"
    }, {
      "data": "target_url",
      "title": "Target URL"
    }],
    "select": {
      style: 'multi'
    },
    "dom": '<"top"Bfli>rt<"bottom"Bfli>p',
    "buttons": [{
      "text": 'Select Visible',
      "className": 'button button-secondary button-small',
      "action": function (e, dt, node, config) {
        dt.rows({
          page: 'current'
        }).select();
      }
    }, {
      "text": 'Delete Selected',
      "className": 'button button-secondary button-small',
      "action": function (e, dt, node, config) {
        var selectedData = dt.rows({
          selected: true
        }).data().toArray();
        var postData = selectedData.map(function (row) {
          return {
            ID: row.ID
          };
        });
        jQuery.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'ddp_delete_redirects',
            _ajax_nonce: cp_ddp.nonce,
            checked_posts: postData
          },
          success: function (response) {
            // Handle the response, e.g., refresh the DataTable or show a success message
            dt.ajax.reload();
          },
          error: function (xhr, status, error) {
            // Handle any errors, e.g., show an error message
          }
        });
      }
    }],
    "pageLength": 10,
    "rowCallback": function (row, data) {
      jQuery(row).addClass('wp-list-table widefat fixed striped table-view-list');
    },
    "lengthMenu": [[10, 25, 50, 100, 250, 500], [10, 25, 50, 100, 250, 500]]
  });

  /** 
   * Newsletter signup handling
   */
  jQuery('.newsletter .ml-block-form, #cp-ddp-newsletter .ml-block-form').on('submit', function (e) {
    e.preventDefault();
    var formData = jQuery(this).serialize();
    jQuery.ajax({
      url: jQuery(this).attr('action'),
      type: 'POST',
      dataType: 'jsonp',
      data: formData,
      success: function (response) {
        if (response.success) {
          jQuery('.ml-block-form').html('<p>Thank you for signing up!</p>');
        } else {
          jQuery('.ml-block-form').append('<p>There was an error. Please try again.</p>');
        }
      },
      error: function () {
        jQuery('.newsletter .ml-block-form, #cp-ddp-newsletter .ml-block-form').append('<p>There was an error. Please try again.</p>');
      }
    });
  });
  var startTime,
    elapsedTime = 0;
  var interval; // Make sure this is accessible in both beforeSend and complete

  // Initialize DataTable
  var table = jQuery('#ddp_dupetable').DataTable({
    "select": {
      style: 'multi'
    },
    "autoWidth": true,
    "processing": true,
    language: {
      processing: '<div id="processingMessage">Looking for duplicates</div>'
    },
    "serverSide": true,
    "searching": false,
    "ordering": false,
    "dom": 'BflrtipB',
    "ajax": {
      "url": ajaxurl,
      "type": "POST",
      "data": function (d) {
        return jQuery.extend({}, d, {
          "action": "ddp_get_duplicates",
          "_ajax_nonce": cp_ddp.nonce
        });
      },
      "dataSrc": function (json) {
        return json.data;
      },
      "beforeSend": function () {
        startTime = new Date().getTime();
        interval = setInterval(updateTime, 1000);
        jQuery("#ddp_dupetable .dt-button").prop('disabled', true);
        jQuery('#ddp_dupetable tbody').css('opacity', '0.5'); // Grey out the tbody	 
      },

      "complete": function () {
        clearInterval(interval);
        jQuery('#ddp_dupetable tbody').css('opacity', '1'); // Restore tbody opacity
        jQuery("#ddp_dupetable .dt-button").prop('disabled', false);
        ddp_refresh_log();
      }
    },
    "columns": [{
      "data": "ID",
      "visible": false
    }, {
      "data": "orgID",
      "visible": false
    }, {
      "data": "duplicate",
      "title": "Duplicate",
      "orderable": false
    },
    // Disable sorting for this column
    {
      "data": "original",
      "title": "Original",
      "orderable": false
    } // Disable sorting for this column
    ],

    "rowCallback": function (row, data) {
      jQuery(row).addClass('wp-list-table widefat fixed striped table-view-list'); // WordPress table classes
    },

    "lengthMenu": [[10, 25, 50, 100, 250, 500], [10, 25, 50, 100, 250, 500]]
  });

  // #ddp_redirtable

  // Create and insert buttons
  var buttonsDiv = createButtons();
  buttonsDiv.insertBefore('#ddp_dupetable_length');

  // Initialize button as disabled
  // var deleteButton = jQuery('.ddp-delete-selected');
  // deleteButton.prop('disabled', true);

  /**
   * refreshTable.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Friday, January 5th, 2024.
   * @return	void
   */
  function refreshTable() {
    table.ajax.reload();
  }

  /**
   * deleteSelected.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Friday, January 5th, 2024.
   * @global
   * @return	void
   */
  function deleteSelected() {
    var selectedRows = table.rows({
      selected: true
    }).data();

    // Check if there are any selected rows
    if (selectedRows.length === 0) {
      alert("Please select at least one row to delete.");
      return;
    }

    // Existing confirmation and deletion code
    if (!confirm(cp_ddp.text_areyousure)) {
      return;
    }
    var checked_posts = [];
    jQuery.each(selectedRows, function (index, value) {
      checked_posts.push({
        'ID': value.ID,
        'orgID': value.orgID
      });
    });
    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        '_ajax_nonce': cp_ddp.deletedupes_nonce,
        'action': 'ddp_delete_duplicates',
        'checked_posts': checked_posts
      },
      success: function (response) {
        // Check if the response indicates success
        if (response.success) {
          table.ajax.reload(null, false); // Keep current pagination
          ddp_refresh_log();
        } else {
          // Handle the soft failure
          alert("Reponse from the server: " + response.data); // Show the error message from the response
        }
      },

      error: function (jqXHR, textStatus, errorThrown) {
        // Handle other types of errors (e.g., network errors, server errors)
        alert("An error occurred: " + textStatus);
      }
    });
  }

  /**
   * selectVisible.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Friday, January 5th, 2024.
   * @global
   * @return	void
   */
  function selectVisible() {
    table.rows({
      page: 'current'
    }).select();
  }

  /**
   * selectNone.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Friday, January 5th, 2024.
   * @global
   * @return	void
   */
  function selectNone() {
    table.rows().deselect();
  }

  /**
   * createButtons.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Friday, January 5th, 2024.
   * @global
   * @return	mixed
   */
  function createButtons() {
    var buttonsDiv = jQuery('<div/>', {
      class: 'dt-buttons'
    });
    buttonsDiv.append(jQuery('<button/>', {
      text: 'Refresh',
      click: refreshTable,
      class: 'dt-button button button-secondary button-small'
    }));
    buttonsDiv.append(jQuery('<button/>', {
      text: 'Delete Selected',
      click: deleteSelected,
      class: 'dt-button button button-secondary button-small ddp-delete-selected'
    }));
    buttonsDiv.append(jQuery('<button/>', {
      text: 'Select Visible',
      click: selectVisible,
      class: 'dt-button button button-secondary button-small'
    }));
    buttonsDiv.append(jQuery('<button/>', {
      text: 'Select None',
      click: selectNone,
      class: 'dt-button button button-secondary button-small'
    }));
    return buttonsDiv;
  }

  /**
   * updateTime.
   *
   * @author	Unknown
   * @since	v0.0.1
   * @version	v1.0.0	Tuesday, October 31st, 2023.
   * @global
   * @return	void
   */
  function updateTime() {
    var currentTime = new Date().getTime();
    var elapsedTime = (currentTime - startTime) / 1000;
    jQuery('#requestTime').html("Request: " + elapsedTime + " sec.");
    jQuery('#processingMessage').html("Looking for duplicates " + elapsedTime + " sec.");
  }

  /**
   * Add event listener for row selection
   *
   * @var		mixed	#ddp_dupetabl
   * @global
   */
  jQuery('#ddp_dupetable tbody').on('click', 'tr', function () {
    jQuery(this).toggleClass('selected');
  });

  // REFRESH LIST
  jQuery(document).on('click', '#deleteduplicateposts_resetview', function (e) {
    e.preventDefault();
    jQuery('#ddp_container .dupelist .duplicatetable tbody').empty();
    ddp_get_duplicates(1, senddata);
    ddp_refresh_log();
  });

  /**
   * ddp_refresh_log.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 10th, 2021.
   * @return	void
   */
  function ddp_refresh_log() {
    jQuery('#ddp_log').empty();
    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        '_ajax_nonce': cp_ddp.loglines_nonce,
        'action': 'ddp_get_loglines'
      },
      dataType: "json",
      success: function (response) {
        let loglines = response.data.results;
        if (loglines) {
          jQuery.each(loglines, function (key, value) {
            jQuery('#ddp_log').append('<li><code>' + value.datime + '</code> ' + value.note + '</li>');
          });
        }
        jQuery('#log .spinner').removeClass('is-active');
      }
    }).fail(function (response) {
      jQuery('#log .spinner').removeClass('is-active');
      if (window.console && window.console.log) {
        window.console.log(response.statusCode + ' ' + response.statusText);
      }
    });
  }

  /**
   * ddp_get_duplicates.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 10th, 2021.	
   * @version	v1.0.1	Thursday, June 9th, 2022.
   * @param	mixed	stepid	- integer, starts at 1
   * @param	mixed	data  	
   * @param	mixed	self  	
   * @return	void
   */
  function ddp_get_duplicates(stepid, data, self) {
    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        '_ajax_nonce': cp_ddp.nonce,
        'action': 'ddp_get_duplicates',
        'stepid': stepid
      },
      dataType: "json",
      success: function (response) {
        let dupes = response.data.dupes;
        if (dupes) {
          jQuery('#ddp_container #dashboard .statusdiv .statusmessage').html(response.data.msg).show();
          jQuery('#ddp_container #dashboard .statusdiv .dupelist .duplicatetable').show();
          jQuery.each(dupes, function (key, value) {
            jQuery('#ddp_container #dashboard .statusdiv .dupelist .duplicatetable tbody').append('<tr><th scope="row" class="check-column"><label class="screen-reader-text" for="cb-select-' + value.ID + '">Select Post</label><input id="cb-select-' + value.ID + '" type="checkbox" name="delpost[]" value="' + value.ID + '" data-orgid="' + value.orgID + '"><div class="locked-indicator"></div></th><td><a href="' + value.permalink + '" target="_blank">' + value.title + '</a> (ID #' + value.ID + ' type:' + value.type + ' status:' + value.status + ')</td><td><a href="' + value.orgpermalink + '" target="_blank">' + value.orgtitle + '</a> (ID #' + value.orgID + ') ' + value.why + '</td></tr>');
          });
          jQuery('#ddp_container #dashboard .statusdiv .dupelist .duplicatetable tbody').slideDown();
        } else {
          jQuery('#ddp_container #dashboard .statusdiv .statusmessage').html(response.data.msg).show();
        }
        if ('-1' == response.data.nextstep) {
          // Something went wrong.
        } else {
          if (parseInt(response.data.nextstep) > 0) {
            ddp_get_duplicates(parseInt(response.data.nextstep), data, self);
          }
        }
        //ddp_refresh_log();
      }
    }).fail(function (response) {
      if (window.console && window.console.log) {
        window.console.log(response.statusCode + ' ' + response.statusText);
      }
    });
  }

  // Show / hide input fields in admin based on selected compare method.
  jQuery(document).on('click', '.ddpcomparemethod li', function () {
    jQuery(".ddpcomparemethod input:radio").each(function () {
      if (this.checked) {
        jQuery(this).closest('li').find('.ddp-compare-details').show();
      } else {
        jQuery(this).closest('li').find('.ddp-compare-details').hide();
      }
    });
  });

  // Pretend click
  jQuery('.ddpcomparemethod li').trigger('click');
});

//# sourceMappingURL=delete-duplicate-posts-min.js.map
