(function($){

    $(document).ready(function () {
        $(document.body)
            .on( 'checkout_error', check_errors_for_tot)
            .on( 'click', 'a[href="#tot_get_verified"]', openModal)
            .on('click', 'a[href="#tot_generated_get_verified"]', handleGeneratedVerificationLink);

        if($('[data-tot-verify-age]').length > 0) {
            // Is this used?
            openModal();
        }
        if($('[data-tot-auto-open-modal="true"]').length > 0) {
            openModal();
        }
        
        // update checkout if the payment method is changed
        // to recheck if the verification is required
        $( 'form.checkout.woocommerce-checkout' ).on( 'change', 'input[name=payment_method]', function (e){
            $( 'body' ).trigger( 'update_checkout' );
        });
    });

    //////////

    function check_errors_for_tot() {
        var errorText = $('.woocommerce-error').find('li').first().text().trim();
        if ( errorText ==='Verification with Token of Trust is required to complete your order.' ) {
            openModal();
        }
    }

    function handleGeneratedVerificationLink(event) {
        window.totModalType = $(this).data('type');
        window.totModalParams = $(this).data('params');
        window.totModalParams.disableClose = true;

        let somethingHappened = false;
        tot('bind', 'modalFormSubmitted', function () {
            somethingHappened = true;
        });
        tot('bind', 'modalClose', function (evt) {
            if (somethingHappened) {
                $('.woocommerce-NoticeGroup.woocommerce-NoticeGroup-checkout').remove();
                $( 'form.checkout' ).trigger('submit');
            }
        });

        openModal(event);
    }

    function openModal(event) {
        console.debug('Call to modal open with window of type %s.', window.totModalType);
        if(!window.totModalType || !window.totModalParams || !window.tot) {
            return;
        }

        if(event && event.preventDefault) {
            event.preventDefault();
        }

        window.tot("modalOpen", window.totModalType, window.totModalParams);
    }

})(jQuery);