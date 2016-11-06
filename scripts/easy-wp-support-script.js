(function($) {

    $(document).ready(function() {
        if ($('#posts-filter').length > 0) {
            $('#posts-filter').append($('#pop_up_button').html());
        }
    });

});
