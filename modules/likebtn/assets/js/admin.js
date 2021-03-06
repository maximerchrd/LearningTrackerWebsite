jQuery(document).ready(function() {
    // Image dropdown
    jQuery("select[name*='likebtn_settings_style']").select2({
        formatResult: likebtnFormatSelect,
        formatSelection: likebtnFormatSelect,
        escapeMarkup: function(m) { return m; },
        minimumResultsForSearch: -1
    });
});

// Format image dropdown
function likebtnFormatSelect(state)
{
    // optgroup
    /*if (!state.id) {
        return state.text;
    }*/
    var image_name;

    if (state.id) {
        image_name = state.id.toLowerCase();
    } else {
        return state.text;
    }
    var select_id = jQuery(state.element).parents('select:first').attr('id');
    var option_html = '<img src="//likebtn.com/bundles/likebtnwebsite/i/theme/' + image_name + '.png" style="border-style:none;" alt="' + image_name + '" />';

    /*if (typeof(state.text) !== "undefined" && state.text) {
        option_html += ' &nbsp;<span class="image_dropdown_text">-&nbsp; ' + state.text + '</span>';
    }*/

    return option_html;
}

/**
 * Test synchronization.
 */
function testSync(ajaxurl)
{
    jQuery(".likebtn_test_sync_container:first img").show();
    jQuery(".likebtn_test_sync_container:first .likebtn_test_sync_message").hide();

    jQuery.ajax({
        type: 'POST',
        dataType: "json",
        url: ajaxurl,
        data: {
            likebtn_account_email: jQuery("#edit-likebtn-account-data-email").val(),
            likebtn_account_api_key: jQuery("#edit-likebtn-account-data-api-key").val(),
            likebtn_account_site_id: jQuery("#edit-likebtn-account-data-site-id").val()
        },
        success: function(response) {
            var result_text = '';
            if (typeof(response.result_text) != "undefined") {
                result_text = response.result_text;
            }
            jQuery(".likebtn_test_sync_message:first").text(result_text).show();
            if (typeof(response.result) == "undefined" || response.result != "success") {
                jQuery(".likebtn_test_sync_message").css('color', 'red');
                if (typeof(response.message) != "undefined") {
                    var text = jQuery(".likebtn_test_sync_message").text() + ': ' + response.message;
                    jQuery(".likebtn_test_sync_message").text(text);
                }
            } else {
                jQuery(".likebtn_test_sync_message").css('color', 'green');
            }
            jQuery(".likebtn_test_sync_container:first img").hide();

        },
        error: function(response) {
            jQuery(".likebtn_test_sync_message").text('Error').css('color', 'red').show();
            jQuery(".likebtn_test_sync_container:first img").hide();
        }
    });

    return false;
}
