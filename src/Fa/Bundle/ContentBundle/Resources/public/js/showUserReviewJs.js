function loadMoreReviews(userId, limit, page, excludeIds)
{
    var fa_profile_images_ajax_loader = $("#fa_profile_images_ajax_loader").data('fa-profile-images-ajax-loader');
    var fa_profile_loading_please_wait = $("#fa_profile_loading_please_wait").data('fa-profile-loading-please-wait');

    $('#show_more_link').html("<div class='row'><img src=\""+ fa_profile_images_ajax_loader +"\"> "+ fa_profile_loading_please_wait +"</div>");
    var route = Routing.generate('show_profile_user_reviews', { 'userId': userId, 'limit': limit, 'page': page, 'excludeIds': excludeIds });
    $.ajax({
        type: "POST",
        url : route,
    })
        .always(function(response) {
            $('#show_more_link').remove();
        })
        .done(function(response) {
            if (response.contentHtml.length) {
                $(response.contentHtml).appendTo('#user_review_main_div');
            }
        });
}