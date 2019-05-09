$(document).ready(function(){
    $("#profilepage_contactuser").bind( "click", function() {
        profilePageContactUser();
    });
});

function profilePageContactUser()
{
    var fa_content_user_profile_page_contact_user = $("#fa_content_user_profile_page_contact_user").data('fa_content_user_profile_page_contact_user');

    var fa_content_msg_div_id = $("#fa_content_msg_div_id").data('fa-content-msg-div-id');

    var fa_content_profile_user_id = $("#fa_content_profile_user_id").data('fa-content-profile-user-id');

    blockPage();
    $.ajax({
        type: "GET",
        url: fa_content_user_profile_page_contact_user,
        data: {'msg_div_id': fa_content_msg_div_id},
    })
        .always(function(response) {
            unblockPage();
        })
        .done(function(response) {
            hideAlertMessage();
            if (response.htmlContent.length) {
                $('#profilePageContactUserModal').html(response.htmlContent);
                $('#profilePageContactUserModal').foundation('reveal', 'open');
                ga('send', 'event', 'CTA', 'Contact Seller button Profile', 'Profile '+fa_content_profile_user_id);
            } else if (response.error.length) {
                $(decorateMessage(response.error, 'alert')).insertBefore(fa_content_msg_div_id);
                scrollToElement(fa_content_msg_div_id, '1000', -150);
            }
        });
}