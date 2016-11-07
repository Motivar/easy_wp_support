(function($) {
    $(document).ready(function() {
        if ($('#posts-filter').length > 0) {
            $('#posts-filter').append($('#pop_up_button').html());
        }


        if ( undefined !== wp.media ) {
            wp.media.view.Attachment.Library = wp.media.view.Attachment.Library.extend({
                className: function () { return 'attachment ' + this.model.get( 'customClass' ); }
            });

        }
        if ($('#easy_wp_support_exclude_images').length>0 && $('#easy_wp_support_exclude_images').val()!='')
        {
        	setTimeout(function()
        	{
        	var val=$('#easy_wp_support_exclude_images').val().split(',');
        	$.each(val,function(i,index)
        	{
        		$('li.attachment[data-id="'+index+'"]').addClass('easy_wp_support_yoast_exlude');
        	});
        },1000);

        }



    });

})(jQuery);
