function CharsStart(){$(".chart").easyPieChart({barColor:!1,trackColor:!1,scaleColor:!1,scaleLength:!1,lineCap:!1,lineWidth:!1,size:!1,animate:7e3,onStep:function(a,b,c){$(this.el).find(".percent").text(Math.round(c))}})}$(document).ready(function(){"use strict";$(window).height(),$(window).width();$(".dropdown").hover(function(){$(".dropdown-menu",this).stop(!0,!0).slideDown("fast"),$(this).toggleClass("open")},function(){$(".dropdown-menu",this).stop(!0,!0).slideUp("fast"),$(this).toggleClass("open")}),$(".yamm .navbar-nav>li").hover(function(){$(".dropdown-menu",this).fadeIn("fast")},function(){$(".dropdown-menu",this).fadeOut("fast")}),window.prettyPrint&&prettyPrint(),$(document).on("click",".yamm .dropdown-menu",function(a){a.stopPropagation()}),$(".btn-collapse").click(function(){$(".panel").removeClass("panel-default"),$(this).parents(".panel").addClass("panel-default")})}),$("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:"normal",theme:"light_square",slideshow:3e3}),$("a.prettyPhoto").prettyPhoto({animation_speed:"normal",theme:"light_square",slideshow:3e3});var $container=$(".isotope-filter");$container.imagesLoaded(function(){$container.isotope({itemSelector:".isotope-item"})}),$("#filter  a").click(function(){var a=$(this).attr("data-filter");return $container.isotope({filter:a}),!1}),$("body").length&&$(window).on("scroll",function(){$(window).scrollTop();$(".list-progress").waypoint(function(){$(".chart").each(function(){CharsStart()})},{offset:"80%"})});var Core={initialized:!1,initialize:function(){this.initialized||(this.initialized=!0,this.build())},build:function(){this.initOwlCarousel()},initOwlCarousel:function(a){function b(a){var b=a,c=b.data("after-move-delay"),d=b.data("main-text-animation");d&&setTimeout(function(){$(".main-slider_zoomIn").css("visibility","visible").addClass("zoomIn"),$(".main-slider_slideInUp").css("visibility","visible").addClass("slideInUp"),$(".main-slider_fadeInLeft").css("visibility","visible").addClass("fadeInLeft"),$(".main-slider_fadeInRight").css("visibility","visible").addClass("fadeInRight"),$(".main-slider_fadeInLeftBig").css("visibility","visible").addClass("fadeInLeftBig"),$(".main-slider_fadeInRightBig").css("visibility","visible").addClass("fadeInRightBig")},c)}$(".enable-owl-carousel").each(function(a){var c=$(this),d=c.data("items"),e=c.data("navigation"),f=c.data("pagination"),g=c.data("single-item"),h=c.data("auto-play"),i=c.data("transition-style"),j=c.data("main-text-animation"),k=c.data("after-init-delay"),l=c.data("stop-on-hover"),m=c.data("min600"),n=c.data("min800"),o=c.data("min1200");c.owlCarousel({navigation:e,pagination:f,singleItem:g,autoPlay:h,transitionStyle:i,stopOnHover:l,navigationText:["<i></i>","<i></i>"],items:d,itemsCustom:[[0,1],[600,m],[800,n],[1200,o]],afterInit:function(a){j&&setTimeout(function(){$(".main-slider_zoomIn").css("visibility","visible").removeClass("zoomIn").addClass("zoomIn"),$(".main-slider_fadeInLeft").css("visibility","visible").removeClass("fadeInLeft").addClass("fadeInLeft"),$(".main-slider_fadeInLeftBig").css("visibility","visible").removeClass("fadeInLeftBig").addClass("fadeInLeftBig"),$(".main-slider_fadeInRightBig").css("visibility","visible").removeClass("fadeInRightBig").addClass("fadeInRightBig")},k)},beforeMove:function(a){j&&($(".main-slider_zoomIn").css("visibility","hidden").removeClass("zoomIn"),$(".main-slider_slideInUp").css("visibility","hidden").removeClass("slideInUp"),$(".main-slider_fadeInLeft").css("visibility","hidden").removeClass("fadeInLeft"),$(".main-slider_fadeInRight").css("visibility","hidden").removeClass("fadeInRight"),$(".main-slider_fadeInLeftBig").css("visibility","hidden").removeClass("fadeInLeftBig"),$(".main-slider_fadeInRightBig").css("visibility","hidden").removeClass("fadeInRightBig"))},afterMove:b,afterUpdate:b})})}};Core.initialize(),(new WOW).init();