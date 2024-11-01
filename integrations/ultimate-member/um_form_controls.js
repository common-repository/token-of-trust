'use strict';

(function ($) {
    $(document).ready(function () {
        showHideAllowedRolesField();
        onOffButtonsControl();
        hideVerificationPermissionLabel();
    });

    function showHideAllowedRolesField() {
        var verificationPermission = $('#tot-plugin-verification-permission');
        var allowedRolesLabel = $("th:contains('Allowed Roles')");
        var allowedRolesField = $('#tot-profile-tab-verification-roles');
        var verificationTabIsDisabled = $('label[class*="tot-disable"][data-id="tot-plugin-verification-tab"]').hasClass("tot-selected");

        if (verificationPermission.val() === '4' && !verificationTabIsDisabled) {
            allowedRolesField.show();
            allowedRolesLabel.show();
        } else {
            allowedRolesLabel.hide();
            allowedRolesField.hide();
        }
        verificationPermission.change(function () {
            var val = this.value;
            if (val === '4') {
                allowedRolesField.show();
                allowedRolesLabel.show();
            } else {
                allowedRolesField.hide();
                allowedRolesLabel.hide();
            }
        })
    }

    function onOffButtonsControl() {
        $('body')
            .on('click', '.tot-switch-options > .tot-enable', function(){
                if (!$(this).hasClass("tot-disabled")) {
                    var data_id = $(this).attr("data-id");
                    $("#" + data_id).prop("checked", true).val(1);
                    $('label.tot-disable[data-id="' + data_id + '"]').removeClass("tot-selected");
                    $(this).addClass("tot-selected");
                    if (data_id === "tot-plugin-verification-tab") {
                        changeFieldsStatus();
                    }
                }
            })
            .on('click', '.tot-switch-options > .tot-disable', function(){
                if (!$(this).hasClass("tot-disabled")) {
                    var data_id = $(this).attr("data-id");
                    $("#" + data_id).prop("checked", false).val(0);
                    $('label.tot-enable[data-id="' + data_id + '"]').removeClass("tot-selected");
                    $(this).addClass("tot-selected");
                    if (data_id === "tot-plugin-verification-tab") {
                        changeFieldsStatus("disable");
                    }
                }
            });
    }

    function changeFieldsStatus(option) {
        var reportAbuseOffOpt = $('label[class*="tot-disable"][data-id="tot-plugin-report-abuse-button"]');
        var reportAbuseOnOpt = $('label[class*="tot-enable"][data-id="tot-plugin-report-abuse-button"]');
        var selectVerificationTabPermission = $("#tot-plugin-verification-permission");
        var selectVerificationTabPermissionLabel = $("th:contains('Who can see Verification Tab')");
        var allowedRolesLabel = $("th:contains('Allowed Roles')");
        var allowedRolesField = $('#tot-profile-tab-verification-roles');
        if (option === "disable") {
            reportAbuseOffOpt.addClass("tot-disabled");
            reportAbuseOnOpt.addClass("tot-disabled");
            selectVerificationTabPermission.hide();
            selectVerificationTabPermissionLabel.hide();
            allowedRolesLabel.hide();
            allowedRolesField.hide();
        } else {
            reportAbuseOffOpt.removeClass("tot-disabled");
            reportAbuseOnOpt.removeClass("tot-disabled");
            selectVerificationTabPermission.show();
            selectVerificationTabPermissionLabel.show();
            showHideAllowedRolesField();
        }

    }

    function hideVerificationPermissionLabel() {
        var verificationTab = $('label[class*="tot-disable"][data-id="tot-plugin-verification-tab"]');
        if (verificationTab.hasClass("tot-selected")) $("th:contains('Who can see Verification Tab')").hide();
    }
})(jQuery);
