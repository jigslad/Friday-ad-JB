$( window ).scroll(function() {
    if ($("#top_bar_nav").hasClass('fixed')) {
        $("#sticky_nav_top").addClass('fixed');
        $("#sticky_nav_top").addClass('top-bar-shadow');
    } else {
        $("#sticky_nav_top").removeClass('fixed');
        $("#sticky_nav_top").removeClass('top-bar-shadow');
    }
});


if ($(window).width() < 760) {
   $(".mobile-form-view").css("display","none");
}
function handleRegEmail() {
    $(".mobile-block-reg").hide();
    $(".mobile-form-view").show();
    $(".mobile-reg-with-email-btn").hide();
}

$('#dl-menu').dlmenu({
    animationClasses : { classin : 'dl-animate-in-2', classout : 'dl-animate-out-2' }
});

$(function() {
    $('.dl-menu li').click(function() {
        setTimeout(function() {
            if ($('.dl-menu').hasClass('dl-subview')) {
                $('.dl-trigger').hide();
            } else {
                $('.dl-trigger').show();
            }
        }, 500);
    });
});
$(document).ready(function(){

  $('.own-item .dropdown-menu .dropdown-item').click(function() {
    $(this).siblings().removeClass('active');
    $(this).addClass('active');
    $(this).parent().siblings('.new-order-btn').html($(this).text());
    $(this).parent().siblings('.new-order-btn').removeClass('new-order preparing-dispatched dispatched delivered closed');
    $(this).parent().siblings('.new-order-btn').addClass($(this).data('target-class')); 
  });

  $('.own-item .dropdown-menu').each(function() {
    $(this).children('.dropdown-item').each(function() {
      if ($(this).hasClass('active')) {
        $(this).parent().siblings('.new-order-btn').html($(this).text());
        $(this).parent().siblings('.new-order-btn').removeClass('new-order preparing-dispatched dispatched delivered closed');
        $(this).parent().siblings('.new-order-btn').addClass($(this).data('target-class')); 
      }
    });
  });

    $('#recommended-item-slider').owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        dots:false,
        navText: ["<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-prev.svg'>", "<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-next.svg'>"],
        navClass: ['owl-prev', 'owl-next'],
        responsive:{
            0:{
                items:2
            },
            640:{
                items:3
            },
            760:{
                items:4
            },
            960:{
                items:5
            },
            1151:{
                items:6
            }
        }
    });

    $('#popular_shops_slider').owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        dots:false,
        navText: ["<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-prev.svg'>", "<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-next.svg'>"],
        navClass: ['owl-prev', 'owl-next'],
        responsive:{
            0:{
                items:1
            },
            640:{
                items:2
            },
            760:{
                items:3
            },
            960:{
                items:4
            }
        }
    });
    $('#featured-item-slider').owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        dots:false,
        navText: ["<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-prev.svg'>", "<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-next.svg'>"],
        navClass: ['owl-prev', 'owl-next'],
        responsive:{
            0:{
                items:1
            },
            640:{
                items:2
            },
            760:{
                items:3
            },
            960:{
                items:3
            }
        }
    });
    $('#similar-item-slider').owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        dots:false,
        navText: ["<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-prev.svg'>", "<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-next.svg'>"],
        navClass: ['owl-prev', 'owl-next'],
        responsive:{
            0:{
                items:1
            },
            640:{
                items:2
            },
            760:{
                items:3
            },
            960:{
                items:4
            }
        }
    });
    $('#latest-item-slider').owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        dots:false,
        navText: ["<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-prev.svg'>", "<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-next.svg'>"],
        navClass: ['owl-prev', 'owl-next'],
        responsive:{
            0:{
                items:1
            },
            640:{
                items:2
            },
            760:{
                items:3
            },
            960:{
                items:4
            }
        }
    });
    $('#related_businesses_slider').owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        dots:false,
        navText: ["<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-prev.svg'>", "<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-next.svg'>"],
        navClass: ['owl-prev', 'owl-next'],
        responsive:{
            0:{
                items:2
            },
            760:{
                items:1
            },
            1151:{
                items:2
            }
        }
    });

    $('#featured_employers_slider, #job_for_week_slider').owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        dots:false,
        navText: ["<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-prev.svg'>", "<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-next.svg'>"],
        navClass: ['owl-prev', 'owl-next'],
        responsive:{
            0:{
                items:1
            },
            640:{
                items:2
            },
            760:{
                items:3
            },
            960:{
                items:4
            }
        }
    });
    $('#recently-view-item-slider').owlCarousel({
        loop:true,
        margin:10,
        nav:true,
        dots:false,
        navText: ["<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-prev.svg'>", "<img src='//static.friday-ad.co.uk/bundles/fafrontend/images/small-slider-next.svg'>"],
        navClass: ['owl-prev', 'owl-next'],
        responsive:{
            0:{
                items:2
            },
            559:{
                items:3
            },
            960:{
                items:4
            }
        }
    });

    // Add minus icon for collapse element which is open by default
    $(".collapse.show").each(function(){
        $(this).prev(".panel-heading").find(".fa").addClass("fa-minus").removeClass("fa-plus");
    });
    // Toggle plus minus icon on show hide of collapse element
    $(".collapse").on('show.bs.collapse', function(){
        $(this).prev(".panel-heading").find(".fa").removeClass("fa-plus").addClass("fa-minus");
    }).on('hide.bs.collapse', function(){
        $(this).prev(".panel-heading").find(".fa").removeClass("fa-minus").addClass("fa-plus");
    });

    $('.nav-icon').on('click', function(){
        $(this).toggleClass('open');
    });

    $(".custom-select").each(function(){
        $(this).wrap("<span class='select-wrapper'></span>");
        $(this).after("<span class='holder'></span>");
    });
    $(".custom-select").change(function(){
        var selectedOption = $(this).find(":selected").text();
        $(this).next(".holder").text(selectedOption);
    }).trigger('change');

    $(".show_sort_menu").click(function() {
      $("#left_search").css({
        marginLeft: "0%",
        transition: '1s ease-in',
        width: '100% !important',
        padding: '0 !important'
      });
    });
    $(".hide_sort_menu").click(function() {
      $("#left_search").css({
        marginLeft: "-100%",
        transition: '1s ease-in',
        width: '100% !important',
        padding: '0 !important'
      });
    });

});
$(document).ready(function() {
    //
    // $("#search_preferences_link").on('click', function(){
    //     $('.search-sliders').toggle();
    // });
    //
    //     var slider1 = document.getElementById('price_slider');
    //
    //     if (slider1 != undefined) {
    //     noUiSlider.create(slider1, {
    //         snap: true,
    //         connect: true,
    //         start: [ 0, 500000 ],
    //         range: {
    //             'min': 0,
    //             '5%': 5,
    //             '10%': 10,
    //             '15%': 20,
    //             '20%': 50,
    //             '30%': 100,
    //             '40%': 250,
    //             '50%': 500,
    //             '60%': 1000,
    //             '70%': 2000,
    //             '80%': 5000,
    //             '90%': 10000,
    //             'max': 500000
    //         }
    //     });
    //   }
    //
    //     var slider2 = document.getElementById('distance_slider');
    //
    //     if (slider2 != undefined) {
    //     noUiSlider.create(slider2, {
    //         snap: true,
    //         connect: true,
    //         start: [ 0, 500000 ],
    //         range: {
    //             'min': 0,
    //             '5%': 5,
    //             '10%': 10,
    //             '15%': 20,
    //             '20%': 50,
    //             '30%': 100,
    //             '40%': 250,
    //             '50%': 500,
    //             '60%': 1000,
    //             '70%': 2000,
    //             '80%': 5000,
    //             '90%': 10000,
    //             'max': 500000
    //         }
    //         // start: [ 15 ],
    //         // connect: true,
    //         // range: {
    //         //     'min': [0, 2],
    //         //     '5%':  [2, 3],
    //         //     '10%': [5, 5],
    //         //     '20%': [20, 10],
    //         //     '25%': [30, 20],
    //         //     '35%': [50, 25],
    //         //     '55%': [100, 50],
    //         //     '85%': [200, 100000],
    //         //     'max': 100000
    //         // }
    //     });
    //   }
});

    

$(window).scroll(function () {
    if($("#contact_seller_btn").length) {
        var contactBtnOffset = $("#contact_seller_btn").offset().top;
        var contactBtnPos = contactBtnOffset - $(window).scrollTop();
        if (contactBtnPos < -40) {
            $("#sticky_item_information_id").show();
        } else if (contactBtnPos > -40) {
            $("#sticky_item_information_id").hide();
        }
    }
});
   

//<![CDATA[
                                 
var mapLoadedFalg = false;
$(window).on('load', function() { 
    $('#adDetailMainTabs').on('toggled', function (event, tab) {
        if (tab.attr('id') == 'map_panel' && !mapLoadedFalg) {
            $('#map_panel').show();
            showCustomMarker('52.91537', '-1.756887', '//static.friday-ad.co.uk/bundles/fafrontend/images/map-marker.png');
            mapLoadedFalg = true;
        }
    });
});
//]]>


var changeSlide = 4; // mobile -1, desktop + 1
// Resize and refresh page. slider-two slideBy bug remove
var slide = changeSlide;
if ($(window).width() < 600) {
 var slide = changeSlide;
  slide--;
}
else if ($(window).width() > 999) {
 var slide = changeSlide;
  slide++;
}
else{
var slide = changeSlide;
}
$(document).ready(function() {

  $('.one').owlCarousel({
      nav: true,
      items: 1,
  })
  $('.two').owlCarousel({
      nav: true,
      margin: 5,
      mouseDrag: true,
      touchDrag: true,
      responsive: {
          0: {
              items: changeSlide - 1,
              slideBy: changeSlide - 1
          },
          600: {
              items: changeSlide,
              slideBy: changeSlide
          },
          1000: {
              items: changeSlide + 1,
              slideBy: changeSlide + 1
          }
      }
  })
  $('.three').owlCarousel({
      nav: true,
      items: 1,
  })
  
  var owl = $('.one');
  owl.owlCarousel();
  owl.on('translated.owl.carousel', function(event) {
      $(".right").removeClass("nonr");
      $(".left").removeClass("nonl");
      if ($('.one .owl-next').is(".disabled")) {
          $(".slider-photo .right").addClass("nonr");
      }
      if ($('.one .owl-prev').is(".disabled")) {
          $(".slider-photo .left").addClass("nonl");
      }
      $('.slider-two .item').removeClass("active");
      var c = $(".slider-photo .owl-item.active").index();
      $('.slider-two .item').eq(c).addClass("active");
      var d = Math.ceil((c + 1) / (slide)) - 1;
      $(".slider-two .owl-dots .owl-dot").eq(d).trigger('click');
  })
  $('.right').click(function() {
      $(".slider-photo .owl-next").trigger('click');
  });
  $('.left').click(function() {
      $(".slider-photo .owl-prev").trigger('click');
  });
  $('.slider-two .item').click(function() {
      var b = $(".item").index(this);
      $(".slider-photo .owl-dots .owl-dot").eq(b).trigger('click');
      $(".slider-two .item").removeClass("active");
      $(this).addClass("active");
  });
  var owl2 = $('.two');
  owl2.owlCarousel();
  owl2.on('translated.owl.carousel', function(event) {
      $(".right-t").removeClass("nonr-t");
      $(".left-t").removeClass("nonl-t");
      if ($('.two .owl-next').is(".disabled")) {
          $(".slider-two .right-t").addClass("nonr-t");
      }
      if ($('.two .owl-prev').is(".disabled")) {
          $(".slider-two .left-t").addClass("nonl-t");
      }
  })
  $('.right-t').click(function() {
      $(".slider-two .owl-next").trigger('click');
  });
  $('.left-t').click(function() {
      $(".slider-two .owl-prev").trigger('click');
  });

  var owl = $('.three');
  owl.owlCarousel();
  owl.on('translated.owl.carousel', function(event) {
      $(".right").removeClass("nonr");
      $(".left").removeClass("nonl");
      if ($('.three .owl-next').is(".disabled")) {
          $(".slider-zoom-photo .right").addClass("nonr");
      }
      if ($('.three .owl-prev').is(".disabled")) {
          $(".slider-zoom-photo .left").addClass("nonl");
      }
  })
  $('.right').click(function() {
      $(".slider-zoom-photo .owl-next").trigger('click');
  });
  $('.left').click(function() {
      $(".slider-zoom-photo .owl-prev").trigger('click');
  });
});

$(function(e) {
  if ($(this).hasClass("checked")) {
    $(this).removeClass("checked");
    if ($(this).parent().parent("li")) {
      $(this).parent().parent("li").removeClass("active")
    }
  } else {
    $(this).addClass("checked");
    if ($(this).parent().parent("li")) {
      $(this).parent().parent("li").addClass("active")
    }
  }
});

 $(function(){
    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
    });

    $(window).resize(function(e) {
      if($(window).width()<=768){
        $("#wrapper").removeClass("toggled");
      }else{
        $("#wrapper").addClass("toggled");
      }
    });
  });

 $(document).ready(function(){
    $('#profile_change_btn_with_opt_anchor, #pickProfileFile1').hover(
         function () {
            $('.profile-img-overlay').show();
         }, 
         function () {
           $('.profile-img-overlay').hide();
         }
    );
    $('#shop_banner_anchhor').hover(
        function () {
           $('.cover-img-overlay').show();
        }, 
        function () {
            $('.cover-img-overlay').hide();
        }
    );
});

 $('#menu_off_canvas_anchor').on('click', function () {
    if ($(this).hasClass('db-menu-close')) {
      $('#menu_off_canvas_anchor').text('Open menu');
      $('#menu_off_canvas_anchor').removeClass('db-menu-close');
      $('.off-canvas-wrap').removeClass('move-right');
    } else {
      $('#menu_off_canvas_anchor').addClass('db-menu-close');
      $('#menu_off_canvas_anchor').text('Close menu');
      $('.off-canvas-wrap').addClass('move-right');
    }
});


// $("select").change(function() {
//    $(this).removeClass($(this).attr('class')).addClass($(":selected", this).attr('class'));
// });

$(document).ready(function(){
  $(".sortDropdown").click(function(){
    $(".sortDropdownContent").toggle();
  });

  $("#fa_paa_login_user_type_1").on('click',function(){
    $("#new_account").hide();
    $("#fa_paa_login_save").text('Sign up with email');
  });
  $("#fa_paa_login_user_type_0").on('click',function(){
    $("#new_account").show();
    $("#fa_paa_login_save").text('Next step: add more details');
  });


  var chooseEdition = true;
  document.editionHandler = function(){
     console.log("Function called");
    if(chooseEdition){
        $(".edition-fields").show();
        $("#print_edition_show_anchor").text('Hide edition');
        chooseEdition = false;
    }else{
         $(".edition-fields").hide();
        $("#print_edition_show_anchor").text('Change your edition');
        chooseEdition = true;
    }
  }

});

 /**
COPY HERE
*/
(function($) {
  /** change value here to adjust parallax level */
  var parallax = -0.5;

  var $bg_images = $(".wp-block-cover-image");
  var offset_tops = [];
  $bg_images.each(function(i, el) {
    offset_tops.push($(el).offset().top);
  });

  $(window).scroll(function() {
    var dy = $(this).scrollTop();
    $bg_images.each(function(i, el) {
      var ot = offset_tops[i];
      $(el).css("background-position", "50% " + (dy - ot) * parallax + "px");
    });
  });
})(jQuery);

