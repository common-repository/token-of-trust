(function ($) {

	$(document).ready(function(){
        var $body = $( 'body' );
		$body.on( 'click', '.tot-dismiss-notice', dismissNotice);
        $body.on( 'change', 'select[data-tot-approval-status]', userApproval);
        
        /////////////
        // stats tabs
        /////////////
        $('#tot-tabs-nav li:first-child').addClass('active');
        $('.tot-tab-content').hide();
        $('.tot-tab-content:first').show();

        // Click function
        $('#tot-tabs-nav li').click(function(){
            $('#tot-tabs-nav li').removeClass('active');
            $(this).addClass('active');
            $('.tot-tab-content').hide();

            var activeTab = $(this).find('a').attr('href');
            $(activeTab).fadeIn();
            return false;
        });
            ///////////
            // Verification Gates
            ///////////
            var checkVerificationSetting = function (){
                var value = $('input[name="tot_options[tot_field_default_setting_verification_on_pages]"]:checked').val();
                if (value == 'exclusive'){
                    $('.tot_field_bypass_verification_for_pages').show();
                    $('.tot_field_require_verification_for_pages').hide();
                } else if (value == 'inclusive') {
                    $('.tot_field_bypass_verification_for_pages').hide();
                    $('.tot_field_require_verification_for_pages').show();
                }
                
                // fix width
                $('.tot_field_bypass_verification_for_pages span.select2, \n\
                    .tot_field_require_verification_for_pages span.select2').width('400px');
            };
            $('#tot_field_verification_gates_enabled').change(function () {
                var relatedFields = '.tot_field_default_setting_verification_on_pages' + 
                        ',.tot_field_bypass_verification_for_pages' + 
                        ',.tot_field_require_verification_for_pages';
                if ($(this).prop("checked")) {
                    $(relatedFields).show();
                    checkVerificationSetting();
                } else {
                    $(relatedFields).hide();
                }
                
                
            });
            $('#tot_field_verification_gates_enabled').trigger('change');
            
            
            $('input[name="tot_options[tot_field_default_setting_verification_on_pages]"]').change(checkVerificationSetting);

            //////////////////////
            // Toggle message
            /////////////////////
            // first move toggle wrapper to the end of the DOM
            $('.tot-modal-wrapper').appendTo('body');
            $('.tot-modal-wrapper').each(function (){
                $(this).on('tot-modal:toggle', function (){
                    let wrapper = $(this);
                    
                    // to make animation
                    if(wrapper.hasClass('is-visible')){
                        // hide
                        wrapper.find('.tot-modal').removeClass('is-visible');
                        setTimeout(function (){
                            wrapper.removeClass('is-visible');
                        },100);                    
                    } else {
                        // show
                        wrapper.addClass('is-visible');
                        setTimeout(function (){
                            wrapper.find('.tot-modal').addClass('is-visible');
                        },100);
                    }
                });
                
                // modal that will be opened automatically after page is loaded
                if($(this).data('open-automatically')){
                    $(this).trigger('tot-modal:toggle');
                }
                
            });
            
            // if we used a button to open modal
            $(".tot-modal-toggle").click(function () {
                if ($(this).data("modal-id")) {
                    var modalId = '#' + $(this).data("modal-id");
                } else {
                    var modalId = '#' + $(this).parents(".tot-modal-action").first().data("modal-id");
                }
                $(modalId).parent().trigger('tot-modal:toggle');
            });
            
            
            /**
             * changing keys automatically
             */
            let form = "div.automatic-keys-form-wrapper > form";
            // button to Connect using the new keys 
            $('#modal-connect-btn').click(function (){
                // prevent multiple submit
                if($("#tot_field_automatic_connect").length){
                    return;
                }
                
                $(this).find('span').html("Connecting...");
                $(this).addClass('tot-modal-connect-disabled');
                
                // create a new input for the automatic connect
                $('<input style="display:none;" type="checkbox" id="tot_field_automatic_connect" name="tot_field_automatic_connect" checked/>')
                        .appendTo(form);
                
                // set data
                $(form).find('#tot_field_prod_domain').val($('#tot_field_prod_domain_url').val());
                $(form).find('#tot_field_license_key').val($('#tot_field_license_key_url').val());
                
                // submit
                $(form + ' #submit').click();
            });
	});

    //////////
    function userApproval(evt) {
        var $select = $(evt.currentTarget);
        var newState = $select.val();
        var userId = $select.data('totApprovalStatus');
        var totUserId = $select.data('totUserId');
        var $wrapper = $select.parents('[data-tot-approval-status-wrap]');
        var $spinner = $wrapper.find('.spinner');

        $wrapper.addClass('tot-loading');
        $spinner.addClass('is-active');
        $select.prop('disabled', true);

        $.ajax( window.ajaxurl, {
            type: 'POST',
            data: {
                action: 'tot_set_user_approval',
                newState: newState,
                userId: userId
            }
        }).then(
            function(data) {
                $wrapper.removeClass('tot-loading');
                $spinner.removeClass('is-active');
                $select.prop('disabled', false);
                $row = $('#user-' + userId);
                var rolesString = '';
                for(var key in data.roles) {
                    if(!data.roles.hasOwnProperty(key)) {
                        continue;
                    }
                    if(rolesString !== '') {
                        rolesString += ', ';
                    }
                    rolesString += data.roles[key];
                }
                $row.find('td.column-role').html(rolesString);

                console.info(totUserId);
                $('[data-tot-widget][data-app-userid="' + totUserId + '"]').each(function(i, el) {
                    var $widget = $(el);
                    $widget.find('iframe').remove();
                    $widget
                        .removeAttr('data-tot-state')
                        .removeAttr('data-tot-index')
                        .attr('data-tot-override-status', newState);
                });

                window.tot('embedsInitialize');
            },
            function() {
                $wrapper.removeClass('tot-loading');
                $spinner.removeClass('is-active');
                $select.replaceWith('<p>Error</p>');
            }
        );
    }

    function dismissNotice(evt) {
        var type = $(evt.currentTarget).data( 'notice' );
        $.ajax( window.ajaxurl, {
            type: 'POST',
            data: {
                action: 'tot_dismissed_notice_handler',
                type: type
            }
        });
    }
})(jQuery);

(function ($) {
    $(document).ready(function() {
        if( typeof $().select2 !== 'function') {
            return;
        }
        $('select.tot_field_multiselect').select2();
    });
})(jQuery);

window.sendTOTAnalytics = (action) => {
    const url = totObj.totHost
        + '/api/reportAnalytics/wordpress/'
        + totObj.version
        + '/cust/'
        + action
        + '/?'
        + new URLSearchParams({
            'vendorAppDomain': totObj.appDomain
        });

    fetch(url, {
        mode: 'no-cors'
    })
        .catch((e) => {
            console.log(e);
        }).finally();
};