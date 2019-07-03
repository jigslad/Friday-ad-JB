$(document).ready(function() {
    bindFormErrorEvents();
    bindCustomRadioEvent();
    bindCustomCheckboxEvent();
});

var fa_content_profile_page_contact_user = $("#fa_content_profile_page_contact_user").data('fa-content-profile-page-contact-user');

var fa_content_contact_request_sent_successfully = $("#fa_content_contact_request_sent_successfully").data('fa-content-contact-request-sent-successfully');

var fa_content_contact_user_get_obj_id = $("#fa_content_contact_user_get_obj_id").data('fa-content-contact-user-get-obj-id');

var fa_content_ajax_contact_seller_get_email_alert = $("#fa_content_ajax_contact_seller_get_email_alert").data('fa-content-ajax-contact-seller-get-email-alert');

var fa_content_ad_owner_name = $("#fa_content_ad_owner_name").data('fa-content-ad-owner-name');

$("#profile_page_contact_user_id").submit(function(event) {

    // Stop form from submitting normally
    event.preventDefault();
    var error = false;
    $.ajax({
        type: "POST",
        url: fa_content_profile_page_contact_user,
        data: new FormData(this),
        contentType: false,
        processData:false,
    })
        .always(function(response) {
            unblockElement('#profilePageContactUserModal');
        })
        .done(function(response) {
            if(response.result == 0) {
                for (var key in response.data) {
                    $($('#profile_page_contact_user_id').find('[name*="'+key+'"]')[0]).after('<small class="error">'+response.data[key]+'</small>');
                }
            } else if(response.error.length) {
                $(decorateMessage(response.error, 'alert')).insertBefore('#msg_div_id');
                /*$('#form_error_msg').html(response.error);*/
                scrollToElement('#msg_div_id', '1000', -150);
            } else {
                updateUserSiteViewCounterField(fa_content_contact_user_get_obj_id, 'profile_page_email_sent_count', '');
                $(decorateMessage(fa_content_contact_request_sent_successfully, 'success')).insertBefore('#msg_div_id');
                scrollToElement('#msg_div_id', '1000', -150);
                ga('send', 'event', 'CTA', 'Send email Profile', 'Profile '+fa_content_contact_user_get_obj_id);
            }
        });
});

var resetEmailalertFlag = true;
$("#fa_content_profile_page_contact_user_sender_email").blur(function(){
    if ($("#fa_content_profile_page_contact_user_sender_email").val() && resetEmailalertFlag) {
        $.ajax({
            type: "POST",
            url: fa_content_ajax_contact_seller_get_email_alert,
            data: {'emailAddress': $("#fa_content_profile_page_contact_user_sender_email").val()},
        })
            .done(function(response) {
                resetEmailalertFlag = false;
                if (response.isEmailAlertEnabled) {
                    $('#fa_content_profile_page_contact_user_email_alert').parent().addClass('checked');
                    $('#fa_content_profile_page_contact_user_email_alert').attr('checked', true);
                } else if (!response.isEmailAlertEnabled) {
                    $('#fa_content_profile_page_contact_user_email_alert').parent().removeClass('checked');
                    $('#fa_content_profile_page_contact_user_email_alert').attr('checked', false);
                }
            });
    }
});

function toggleContactNumber() {
    updateUserSiteViewCounterField(fa_content_contact_user_get_obj_id, 'profile_page_phone_click_count', 'phone1');
    $('#span_contact_number_full').toggle();
    $('#span_contact_number_part').toggle();
    $('#btn_reveal').toggle();
    ga('send', 'event', 'CTA', 'Call Now - business page', fa_content_ad_owner_name);
}

function toggleContactNumber2() {
    updateUserSiteViewCounterField(fa_content_contact_user_get_obj_id, 'profile_page_phone_click_count', 'phone2');
    $('#span_contact_number_full_2').toggle();
    $('#span_contact_number_part_2').toggle();
    $('#btn_reveal_2').toggle();
    ga('send', 'event', 'CTA', 'Call Now No.2 - business page', fa_content_ad_owner_name);
}