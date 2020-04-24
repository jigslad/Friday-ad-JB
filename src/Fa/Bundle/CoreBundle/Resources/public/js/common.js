function scrollToElement(selector, time, verticalOffset) 
{
  time = typeof(time) != 'undefined' ? time : 1000;
  verticalOffset = typeof(verticalOffset) != 'undefined' ? verticalOffset : 0;
  
  if ($(selector).length)
  {
    element = $(selector);
    offset = element.offset();
    offsetTop = offset.top + verticalOffset;
    $('html, body').animate({
      scrollTop: offsetTop
    }, time);
  }
}

function fbLoginPopup(url){
    var newwindow;
    var  screenX    = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft,
         screenY    = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop,
         outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.body.clientWidth,
         outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : (document.body.clientHeight - 22),
         width    = 500,
         height   = 270,
         left     = parseInt(screenX + ((outerWidth - width) / 2), 10),
         top      = parseInt(screenY + ((outerHeight - height) / 2.5), 10),
         features = (
            'width=' + width +
            ',height=' + height +
            ',left=' + left +
            ',top=' + top
         );

    if(navigator.userAgent.match('CriOS')) {
        window.location.href = url;
    } else {
        newwindow=window.open(url+'&display=popup','Facebook Login Popup',features);
        if (window.focus) {newwindow.focus()}
    }

  return false;
}

function googleLoginPopup(url){
    var newwindow;
    var  screenX    = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft,
         screenY    = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop,
         outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.body.clientWidth,
         outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : (document.body.clientHeight - 22),
         width    = 600,
         height   = 400,
         left     = parseInt(screenX + ((outerWidth - width) / 2), 10),
         top      = parseInt(screenY + ((outerHeight - height) / 2.5), 10),
         features = (
            'width=' + width +
            ',height=' + height +
            ',left=' + left +
            ',top=' + top +
            ',scrollbars=yes'
         );

    newwindow=window.open(url,'Google Login Popup',features);

   if (window.focus) {newwindow.focus()}
  return false;
}

function openWindow(url, windowName, width, height){
    var newwindow;
    var  screenX    = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft,
         screenY    = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop,
         outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.body.clientWidth,
         outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : (document.body.clientHeight - 22),
         left     = parseInt(screenX + ((outerWidth - width) / 2), 10),
         top      = parseInt(screenY + ((outerHeight - height) / 2.5), 10),
         features = (
            'width=' + width +
            ',height=' + height +
            ',left=' + left +
            ',top=' + top +
            ',scrollbars=yes'
         );

    newwindow=window.open(url, windowName, features);

   if (window.focus) {newwindow.focus()}
  return false;
}

function clearForm(form) {
    $(':input', form).each(function() {
        var type = this.type;
        var tag = this.tagName.toLowerCase(); // normalize case
        if (type == 'text' || type == 'password' || tag == 'textarea') {
            this.value = "";
            $(this).nextAll('small.error').first().remove();
            $(this).parent().nextAll('span').find('small.error').remove();
            $(this).removeClass('error');
        } else if (type == 'checkbox' || type == 'radio') {
            this.checked = false;
            $(this).parent().nextAll('small.error').first().remove();
            $(this).removeClass('error');
            if (type == 'checkbox') {
                $(this).parent().removeClass('checked');
            }
        } else if (tag == 'select') {
            this.selectedIndex = 0;
            $(this).nextAll('small.error').first().remove();
            $(this).parent().nextAll('span').find('small.error').remove();
            $(this).parent().nextAll('span').removeClass('error-bdr');
            $(this).nextAll('span').first().removeClass('error-bdr');
        }
    });
}

function decorateMessage(message, type) {
    return '<div data-alert class="alert-box '+ type +' radius outside-tricky"><span class="alert-icon">&nbsp;</span>'+ message +'<a href="javascript:hideAlertMessage()" class="close"></a></div>';
}

function hideAlertMessage() {
    $('.alert-box').hide();
}

function bindFormErrorEvents(){
    if ($('small.error:visible').length > 0) {
        $('html, body').animate({
            scrollTop: $('small.error:visible').first().offset().top - 150
        }, 2000);
    }

    $('form input[type="password"], form input[type="text"], form select, form textarea, form input[type="number"], form input[type="email"]').focus(function(e){
        if ($(this).hasClass('error')) {
            $(this).nextAll('small.error').first().remove();
            $(this).parent().nextAll('span').find('small.error').remove();
            $(this).removeClass('error');
        }
    });
    
    $('form input[type="radio"],form input[type="checkbox"]').click(function(e){
        if ($(this).hasClass('error')) {
            $(this).parent().nextAll('small.error').first().remove();
            $(this).removeClass('error');
        }
    });

    $('.capsule-links a').click(function(e){
        $(this).parent().siblings('small.error:first').remove();
    });

    $('.upload-thumb-img').click(function(e){
        $('#upload_image_div').siblings('small.error:first').remove();
    });
}

function addToCurrentUrl(params, redirect) {
    $.each(params, function(key, value) {
        $.query.SET(key, value);
    });

    var url = window.location.href.split('?')[0]+$.query.toString();
    url     = decodeURIComponent(url);

    if (redirect)
        window.location.href = url;
    else
        return url;
}

function removeFromCurrentUrl(params, redirect) {
    $.each(params, function(key, value) {
        url += $.query.REMOVE(value);
    });
    var url = window.location.href.split('?')[0]+$.query.toString();
    if (redirect)
        window.location.href = url;
    else
        return url;
}

function handleReadMoreLess(targetId, len)
{
    var originalContent = $('#'+targetId).html();
    var truncatedContent =  '';

    if (typeof originalContent !== 'undefined'&& originalContent.length > len) {
        truncatedContent = textCutter(len, originalContent)+'...';
        $('#'+targetId).html(truncatedContent);
        $('#'+targetId).siblings( ".read-more" ).show();
        $('#'+targetId).next('div').find('.read-more').show();

        $('#'+targetId).siblings( ".read-more").click(function() {
            $('#'+targetId).siblings( ".read-more" ).hide();
            $('#'+targetId).html(originalContent);
            $('#'+targetId).siblings( ".read-less" ).show();
        });

        $('#'+targetId).next('div').find('.read-more').click(function() {
            $('#'+targetId).next('div').find('.read-more').hide();
            $('#'+targetId).html(originalContent);
            $('#'+targetId).next('div').find('.read-less').show();
        });
        
        $('#'+targetId).siblings( ".read-less" ).click(function() {
            $('#'+targetId).siblings( ".read-less" ).hide();
            $('#'+targetId).html(truncatedContent);
            $('#'+targetId).siblings( ".read-more" ).show();
        });
        
        $('#'+targetId).next('div').find('.read-less').click(function() {
            $('#'+targetId).next('div').find('.read-less').hide();
            $('#'+targetId).html(truncatedContent);
            $('#'+targetId).next('div').find('.read-more').show();
        });
    }
}

function textCutter(i, text) {
   var short = text.substr(0, i);
   if (/^\S/.test(text.substr(i))) {
       short = short.replace(/\s+\S*$/, "");
   }
   return short.replace(/(<br>|<br \/>|<br\/>)+$/, "");
}

function bindCustomCheckboxEvent()
{
    $('.custom-checkbox').click(function(e) {
        if ($(this).hasClass('checked')) {
            $(this).removeClass('checked');
            if ($(this).parent().parent('li')) {
                $(this).parent().parent('li').removeClass('active');
            }
        } else {
            $(this).addClass('checked');
            if ($(this).parent().parent('li')) {
                $(this).parent().parent('li').addClass('active');
            }
        }
    });
}

function bindCustomRadioEvent()
{
    $('.custom-radio').click(function(e) {
        $('.custom-radio').each(function(e) {
            if ($(this).hasClass('checked')) {
                $(this).removeClass('checked');
            }
        });
        $('input:checked').each(function(){
            $(this).parent().addClass('checked');
        });
    });
}

function toggleTableTr(anchorId, divId)
{
    $('#'+divId).toggle('fast', function() {
        if ($('#'+divId).is(':visible')) {
            $('#'+anchorId).find('i').addClass('fi-minus').removeClass('fi-plus');
        } else {
            $('#'+anchorId).find('i').addClass('fi-plus').removeClass('fi-minus');
        }
    });
}

function bindNumberEvent()
{
    $('input[type=number]').each(function() {
        $("#"+$(this).attr('id')).keydown(function (e) {
            // Allow: backspace, delete, tab, escape, enter and .
            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                 // Allow: Ctrl+A
                (e.keyCode == 65 && e.ctrlKey === true) || 
                 // Allow: home, end, left, right, down, up
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                     // let it happen, don't do anything
                     return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    });
}

function getViewport()
{
    var e = window;
    var a = 'inner';
    if (!('innerWidth' in window)){
        a = 'client';
        e = document.documentElement || document.body;
    }

    return { width : e[ a+'Width' ] , height : e[ a+'Height' ] }
}

function closeRevealModel(modelId)
{
    $(modelId).modal('hide');
}

$(document).ready(function(){
    bindCustomRadioEvent();
    bindCustomCheckboxEvent();
    bindNumberEvent();
});

$.fn.toggleText = function (value1, value2) {
    return this.each(function () {
        var $this = $(this),
            text = $this.text();
 
        if (text.indexOf(value1) > -1) {
            $this.text(text.replace(value1, value2));
        } else {
            $this.text(text.replace(value2, value1));
        }
    });
};

function toggleDashboardLeftMenu(menuId)
{
    $('#'+menuId+'_ul').toggle('slow');
    
    if ($('#'+menuId+'_i').hasClass('fi-plus')) {
        $('#'+menuId+'_i').removeClass('fi-plus');
        $('#'+menuId+'_i').addClass('fi-minus');
    } else {
        $('#'+menuId+'_i').removeClass('fi-minus');
        $('#'+menuId+'_i').addClass('fi-plus');
    }
}

function adEnquiryIncrement(variableName, adId)
{
    var route = Routing.generate('ajax_ad_enquiry_increment', { 'variableName':variableName, 'adId': adId });
    $.ajax({
        type: "POST",
        url: route,
    })
}

function decodeHtml(value) {
    var txt       = document.createElement("textarea");
    txt.innerHTML = value;
    return txt.value;
}

function updateUserSiteViewCounterField(userId, fieldName, subField)
{
    var route = Routing.generate('ajax_update_user_site_view_counter_field', { 'userId': userId, 'fieldName': fieldName, 'subField': subField });
    $.ajax({
        type: "POST",
        url : route,
    })
    .always(function(response) {
        //nothing
    })
    .done(function(response) {
        //nothing
    });
}