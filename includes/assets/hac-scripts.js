jQuery(document).ready(function($) {
    var initialState = {};

    function loadColumns() {
        $('#hacLoader').show(); // Show loader when loading
        var postType = $('#post_type_selector').val();
        var nonce = $('#hac_nonce_name').val(); // Retrieve the nonce value
    
        $.ajax({
            url: hacAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_columns_for_post_type',
                post_type: postType,
                _ajax_nonce: nonce // Include the nonce in the data
            },
            success: function(response) {
                $('#hacLoader').hide(); // Hide loader after loading
                $('#columns_container').html(response);
                initializeCheckboxState();
            },
            error: function() {
                $('#hacLoader').hide(); // Ensure loader is hidden on error
                displayStatus('Failed to load columns. Please try again.', 'error');
            }
        });
    }

    function initializeCheckboxState() {
        $('#save_columns_button').show().prop('disabled', true);
        initialState = {};
        $('#columns_container input[type="checkbox"]').each(function() {
            var checkbox = $(this);
            initialState[checkbox.attr('name')] = checkbox.is(':checked');
        });
        attachCheckboxHandlers();
    }

    function attachCheckboxHandlers() {
        $('#columns_container input[type="checkbox"]').on('change', function() {
            var anyChange = false;
            $('#columns_container input[type="checkbox"]').each(function() {
                var checkbox = $(this);
                if (initialState[checkbox.attr('name')] !== checkbox.is(':checked')) {
                    anyChange = true;
                }
            });
            $('#save_columns_button').prop('disabled', !anyChange);
        });
    }

    $('#post_type_selector').change(loadColumns);
    loadColumns();

    $('#save_columns_button').on('click', function(event) {
        event.preventDefault();
        $('#hacLoader').show(); // Show loader when saving
        var nonce = $('#hac_nonce_name').val();
        var columnData = {};
        $('#columns_container input[type="checkbox"]').each(function() {
            columnData[$(this).attr('name')] = $(this).is(':checked') ? 1 : 0;
        });
        
        $.ajax({
            url: hacAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_column_visibility',
                post_type: $('#post_type_selector').val(),
                nonce: nonce,
                columns: columnData
            },
            success: function(response) {
                $('#hacLoader').hide(); // Hide loader after saving
                displayStatus('Settings saved successfully.', 'hac-success');
                initializeCheckboxState();
            },
            error: function() {
                $('#hacLoader').hide(); // Ensure loader is hidden on error
                displayStatus('Error saving settings. Please try again.', 'hac-error');
            }
        });
    });

    function displayStatus(message, type) {
        var statusHtml = `<div class='status-message ${type}'>${message}</div>`;
        $('#save_columns_button').after(statusHtml);
        setTimeout(function() {
            $('.status-message').fadeOut('slow', function() { $(this).remove(); });
        }, 3000); // Message disappears after 3 seconds
    }
});