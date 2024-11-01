(function($){

    var quarantineButtonSelector = 'a[href="#tot_remove_quarantine"]';
    var emailReminderSelector = 'a[href="#tot_order_detail_verification_reminder"]';

    $(document).ready(function(){
        window.tot && tot_reload_page_listener();

        // radio field with custom value
        $('.tot_field_number_to_radio').each(function () {
            $(this).on('change', function () {
                let radioId = $(this).data('for');
                $('#' + radioId).val($(this).val());
            });
        });

        /**
         * Charity
         */
        $('input[name="tot_options[tot_field_woo_charity]"]').on('change', function () {
           let val = $('input[name="tot_options[tot_field_woo_charity]"]:checked').val();
           if (val == 'deactivated') {
               $('.extra-fields-to-tot_field_woo_charity').hide();
               $('.tot_field_woo_charity_opt').closest('tr').hide();
           } else {
               $('.extra-fields-to-tot_field_woo_charity').show();
               $('.tot_field_woo_charity_opt').closest('tr').show();
           }
        });
        $('input[name="tot_options[tot_field_woo_charity]"]').trigger('change');

        $('body')
            .on('click', quarantineButtonSelector, removeQuarantine)
            .on('click', emailReminderSelector, emailReminder);

        /**
         * product sync
         */
        // function updateProductTypeFields() {
        //     let productType = $('#product-type_attribute').val();
        //     if (productType === 'vape') {
        //         $('.depend-on-vape').show();
        //     } else {
        //         $('.depend-on-vape').hide();
        //     }
        // }
        // updateProductTypeFields();
        // $('#product-type_attribute').on('change', updateProductTypeFields);
        if ($('#product-type_attribute').length) {
            $('form#post').on('submit', function (e) {
                let productType = $('#product-type_attribute').val();
                if (productType === 'vape' ) {
                    let emptyFields = $(".depend-on-vape input, .depend-on-vape select").filter(function (){
                        return $.trim($(this).val()).length === 0
                    });

                    if(emptyFields.length !== 0) {
                        $(".depend-on-vape input, .depend-on-vape select").attr('style', '');
                        e.preventDefault();
                        emptyFields.css("border", "2px solid red");
                        alert('For proper taxation please supply the vape excise tax attributes or select “Exempt from Vape Taxes” if this is an exempt product.');
                    }
                }
            });
        }
    });

    //////////


    function tot_reload_page_listener() {
        var somethingHappened = false;
        var alreadyReloading = false;
        tot('bind', 'modalFormSubmitted', function () {
            somethingHappened = true;
        });
        tot('bind', 'modalClose', function () {
            if( ! somethingHappened  || alreadyReloading) {
                return;
            }
            alreadyReloading = true;

            setTimeout(function(){
                if( ! somethingHappened) {
                    return;
                }
                var promptResponse = confirm(
                    'Do you want to reload the page to see the updated status?'
                    + ' Any in-progress changes will be lost.'
                );

                somethingHappened = false;
                if ( promptResponse ) {
                    console.warn('Reloading page.');
                    window.location.reload();
                }
                alreadyReloading = false;
            }, 500);
        });
    }

    function removeQuarantine( event ) {
        event.preventDefault();
        var $el = $(event.currentTarget);
        var order = $el.data('order');
        var $spinner = $('<span class="spinner is-active"></span>');
        var $buttons = $(quarantineButtonSelector);

        var confirmation = confirm('Are you sure you want to remove the Token of Trust Verification Hold?');

        if(!confirmation) {
            return;
        }

        $buttons.addClass('tot-button-loading').append($spinner);

        if(!window.ajaxurl) {
            removeQuarantineFailed();
            return;
        }

        $.ajax({
            type: "POST",
            url: window.ajaxurl,
            data: {
                'action': 'tot_wc_order_unquarantine',
                'order_id': order
            },
            success: removeQuarantineSuccess,
            error: removeQuarantineFailed
        });
    }

    function removeQuarantineFailed() {
        removeQuarantLoading();
    }

    function removeQuarantineSuccess(data) {

        if(!data || (data.success === false)) {
            removeQuarantineFailed();
            return;
        }
        document.location.reload();
    }

    function removeQuarantLoading() {
        var $buttons = $(quarantineButtonSelector);
        $buttons.removeClass('tot-button-loading').find('.spinner').remove();
    }

    function emailReminder( event ) {
        event.preventDefault();
        var $el = $(event.currentTarget);
        var order = $el.data('order');
        var $spinner = $('<span class="spinner is-active"></span>');
        var $buttons = $(emailReminderSelector);

        var confirmation = confirm('Are you sure you want to email the user?');

        if(!confirmation) {
            return;
        }

        $buttons.addClass('tot-button-loading').append($spinner);

        if(!window.ajaxurl) {
            removeQuarantineFailed();
            return;
        }

        console.info('order', order, $el);

        $.ajax({
            type: "POST",
            url: window.ajaxurl,
            data: {
                'action': 'tot_wc_email_reminder',
                'order_id': order
            },
            success: emailSuccess,
            error: emailError
        });
    }

    function emailSuccess( data ) {
        if(!data || (data.success === false)) {
            emailError();
            return;
        }
        var $buttons = $(emailReminderSelector);
        $buttons.removeClass('tot-button-loading').find('.spinner').remove();
    }

    function emailError() {
        var $buttons = $(emailReminderSelector);
        $buttons.removeClass('tot-button-loading').find('.spinner').remove();
    }

})(jQuery);