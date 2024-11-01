(function($){
    $(document).ready(function(){
        $(document).on('change', '#tot_charity_checkbox', function () {
            $(document.body).trigger("update_checkout");
        });
    });
})(jQuery);