$(function () {
  // =====================================================
  //     Reset input
  // =====================================================

  $('.input-reset .form-control').on('focus', function () {
    $(this).parents('.input-reset').addClass('focus');
  });
  $('.input-reset .form-control').on('blur', function () {
    setTimeout(function () {
      $('.input-reset .form-control').parents('.input-reset').removeClass('focus');
    }, 333);

  });

  // toTop Button functional
  var toTopBtn = $('#toTop');
  $(window).scroll(function() {
    var showAfter = 300;
    $(this).scrollTop() > showAfter ? toTopBtn.fadeIn() : toTopBtn.fadeOut();
  });

  $("#toTop").click(function() {
    $("html, body").animate({ scrollTop: 0 }, "slow");
    return false;
  });

  // ------------------------------------------------------- //
  //   Increase/Decrease product amount
  // ------------------------------------------------------ //
  $('.btn-items-decrease').on('click', function () {
    var input = $(this).siblings('.input-items');
    var value = parseInt(input.val(), 10);

    var minValue = parseInt(input.data('min'));
    if (minValue && value <= minValue) {
      return true;
    }

    if (value >= 1) {
      input.val(value - 1);
      input.change();
    }
  });

  $('.btn-items-increase').on('click', function () {
    var input = $(this).siblings('.input-items');
    var value = parseInt(input.val(), 10);

    input.val(value + 1);

    input.change();
  });

  // ------------------------------------------------------- //
  // Adding fade effect to dropdowns
  // ------------------------------------------------------ //

  $.fn.slideDropdownUp = function () {
    $(this).fadeIn().css('transform', 'translateY(0)');
    return this;
  };
  $.fn.slideDropdownDown = function (movementAnimation) {

    if (movementAnimation) {
      $(this).fadeOut().css('transform', 'translateY(30px)');
    } else {
      $(this).hide().css('transform', 'translateY(30px)');
    }
    return this;
  };

  $('.navbar').on('show.bs.dropdown', '.dropdown', function (e) {
    $(this).find('.dropdown-menu').first().slideDropdownUp();
  });

  $('.navbar').on('hide.bs.dropdown', '.dropdown', function (e) {
    var movementAnimation = true;

    // if on mobile or navigation to another page
    if ($(window).width() < 992 || (e.clickEvent && e.clickEvent.target.tagName.toLowerCase() === 'a')) {
      movementAnimation = false;
    }

    if ($(this).find('.dropdown-menu').hasClass('language-dropdown')) {
      movementAnimation = false
    }

    $(this).find('.dropdown-menu').first().slideDropdownDown(movementAnimation)
  });

    // ------------------------------------------------------- //
  //   Bootstrap tooltips
  // ------------------------------------------------------- //

  $('[data-toggle="tooltip"]').tooltip();

  // ------------------------------------------------------- //
  //   Smooth Scroll
  // ------------------------------------------------------- //

  var smoothScroll = new SmoothScroll('a[data-smooth-scroll]', {
    offset: 160
  });

});

//Countries Page Scripts
if ($('body').hasClass('countries-page')) {
  var vh = window.innerHeight / 2;
  var all = $('#all-cities-section').offset().top - vh;
  var map = $('#map-section').offset().top - vh;
  var know = $('#review-section').offset().top - vh;

  var tips;
  if ($('#tips-section').length) {
    tips = $('#tips-section').offset().top - vh;
  }

  var faq = $('#faq-section').offset().top - vh;

  $(window).on('scroll', function () {
    if (faq && window.pageYOffset > faq) {
      $('.nav-item').removeClass('active');
      $('#faq-link').addClass('active');
    }
    else if (tips && window.pageYOffset > tips) {
      $('.nav-item').removeClass('active');
      $('#tips-link').addClass('active');
    }
    else if (know && window.pageYOffset > know) {
      $('.nav-item').removeClass('active');
      $('#know-link').addClass('active');
    }
    else if (map && window.pageYOffset > map) {
      $('.nav-item').removeClass('active');
      $('#map-link').addClass('active');
    }
    else if (all && window.pageYOffset > all) {
      $('.nav-item').removeClass('active');
      $('#all-link').addClass('active');
    }
  });

  var smallMenu = $('.smallMenu');
  if (smallMenu.length > 0) {
    var smallMenuOffset = $('.smallMenu').offset().top;
    $(window).on('scroll', function () {
      if (window.pageYOffset > $('.smallMenu').offset().top) {
        smallMenu.addClass('fixed');
      }
      else if (window.pageYOffset < smallMenuOffset) {
        smallMenu.removeClass('fixed');
      }
    });
  }
}

$('body').on('click', '.accordion-custom--button', function(){
  $(this).parent().next().slideToggle( 400, function() {
    $(document).trigger('hostelz:accordionOpened');
  });
});

//Hostel Page
if ($('body').hasClass('hostel-page')) {
  $('.accordion-custom').on('click', function(){
    $(this).next().slideToggle();
  });
  // $('body').on('click', '.accordion-custom--button', function(){
  //   $(this).parent().next().slideToggle();
  // });
  $('.play-button').on('click', function (){
    $('#video').play();
    $(this).hide();
  });

  $('#video').on('click', function () {
    $(this).pause();
    $('.play-button').show();
  })

  $('.delete-parent').on('click', function (e) {
    e.preventDefault();
    $(this).parent().remove();
  });

  $('.clear-all').on('click', function (){
    for (var i = 0; i < 4; i++) {
      $('.clear-all').prev().remove();
    }
    $('input').val('');
  });

  $('.filter-modal__trigger').on('click', function () {
    $('.open').not(this).removeClass('open');
    $(this).parent().addClass('open');
    setTimeout(function () {
      document.addEventListener('click', outOfFilter);
    }, 100);
  });

  function outOfFilter(e) {
    var container = $('.filter-modal');
    if ($('.filter-modal__box button').is(e.target)) {
      document.removeEventListener('click', outOfFilter);
      return;
    }
    if (!container.is(e.target) && container.has(e.target).length === 0) {
      $('.filter-modal__box').removeClass('open');
      document.removeEventListener('click', outOfFilter);
    }
  }
  $('.close-modal').on('click', function () {
    $('.filter-modal__box').removeClass('open');
  })
  $('.input-minus--nights').on('click', function () {
    document.getElementById("nights").stepDown(1);
  });
  $('.input-plus--nights').on('click', function () {
    document.getElementById("nights").stepUp(1);
  });
  $('.input-minus').on('click', function () {
    document.getElementById("people-number").stepDown(1);
  });
  $('.input-plus').on('click', function () {
    document.getElementById("people-number").stepUp(1);
  });


  function slideUp() {
    if (window.innerWidth < 992) {
      $('.accordion-custom').next().slideUp();
    }
  }
  slideUp();
  window.onresize = slideUp;

  //Clear Modal Value
  var defaultSelect = [];
  setTimeout( function () {
    var x = document.querySelectorAll('.filter-option-inner-inner');
    for (var i = 0; i < x.length; i++ ) {
      defaultSelect.push(x[i].textContent);
    }
    $('.clear-all-modal').on('click', function () {
      $('.filter-modal').val('');
      // $('.filter-modal input').val('1');
      $('.filter-modal input').val('1').prop('checked', false);
      for (var i = 0; i < defaultSelect.length; i++ ) {
        x[i].innerHTML = defaultSelect[i];
      }
    });
  }, 2000);
}

//City Page
if ($('body').hasClass('city-page')) {
  $('.accordion-custom').on('click', function(){
    $(this).next().slideToggle();
  });
  // $('.accordion-custom--button').on('click', function(){
  //   $(this).parent().next().slideToggle();
  // });
  $('.play-button').on('click', function (){
    $('#video').play();
    $(this).hide();
  });

  $('#video').on('click', function () {
    $(this).pause();
    $('.play-button').show();
  });

  $('.delete-parent').on('click', function (e) {
    e.preventDefault();
    $(this).parent().remove();
  });

  $('.clear-all').on('click', function (){
    for (var i = 0; i < 4; i++) {
      $('.clear-all').prev().remove();
    }
    $('input').val('');
  });

  $('.filter-modal__trigger').on('click', function () {
    $('.open').not(this).removeClass('open');
    $(this).parent().addClass('open');
    setTimeout(function () {
      document.addEventListener('click', outOfFilter);
    }, 100);
  });

  function outOfFilter(e) {
    var container = $('.filter-modal');
    if ($('.filter-modal__box button').is(e.target)) {
      document.removeEventListener('click', outOfFilter);
      return;
    }
    if (!container.is(e.target) && container.has(e.target).length === 0) {
      $('.filter-modal__box').removeClass('open');
      document.removeEventListener('click', outOfFilter);
    }
  }
  $('.close-modal').on('click', function () {
    $('.filter-modal__box').removeClass('open');
  });
  $('.see-all-filters').on('click', function () {
    $('.filters-block').toggleClass('open');
  });
  $('.input-minus').on('click', function () {
    document.getElementById("people-number").stepDown(1);
  });
  $('.input-plus').on('click', function () {
    document.getElementById("people-number").stepUp(1);
  });

  function slideUp() {
    if (window.innerWidth < 992) {
      $('.accordion-custom').next().slideUp();
    }
  }

  slideUp();
  window.onresize = slideUp;

  var selectedFilters = $('.selected-filters');
  if (selectedFilters.length > 0) {
    var selectedFiltersOriginalOffset = selectedFilters.offset().top;

    var space = 20;

    if (window.innerWidth > 767) {
      $(window).on('scroll', function () {
        const item = $('.selected-filters');

        if ((window.pageYOffset + space) > item.offset().top) {
          item.prev().css('margin-top', item.outerHeight() + space);

          item.addClass('fixed');
          item.removeClass('shadow-1');
          $('.selected-filters__base').removeClass('d-none');
        }

        if (window.pageYOffset < selectedFiltersOriginalOffset) {
          item.removeClass('fixed');
          item.addClass('shadow-1');
          $('.selected-filters__base').addClass('d-none');
          item.prev().css('margin-top', 0)
        }
      });
    }
  }


  var defaultSelect = [];
  setTimeout( function () {
    var x = document.querySelectorAll('.filter-option-inner-inner');
    for (var i = 0; i < x.length; i++ ) {
      defaultSelect.push(x[i].textContent);
    }
    $('.clear-all-modal').on('click', function () {
      $('.more-filters select').val('');
      for (var i = 0; i < defaultSelect.length; i++ ) {
        x[i].innerHTML = defaultSelect[i];
      }
    });
  }, 2000);
}

$('.language-select .dropdown-menu button').on('click', function () {
  document.getElementById("language-select__active").src = $(this).attr('data-src');
});

$('#importedRatingsInsertedHere').on('hostelz:importedReviewsDone', function () {
  $('#reviewsTabs').find('.nav-link').first().click();
});

function loadExploreSectionAvailability(items, optionsData) {
  items.each(function (i, e) {
    const item = $(e);

    $url = '/listing/minPrice/' + item.data('listing-id');

    $.ajax({
      url: $url + "?optionsData=" + optionsData,
      success: function (html) {
        item.find('.loadListingPrice').replaceWith(html);
      },
      error: function (xhr, textStatus, errorThrown) {
        item.find('.loadListingPrice').replaceWith('');
        console.warn('error');
      }
    });
  });
}
