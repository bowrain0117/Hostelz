/* translationOptions from translation template */

$(document).ready (function () {
  if (translationOptions.languageTo === 'en') {
    return true;
  }

  $(document).on('hostelz:staffTranslationResponse', loadTranslation);
  $(document).on('hostelz:staffTranslationResponse', function (e) {
    $('body').find('.spinner').hide();
  });

  /*  Search single */
  $('.translationGet').click(function (e) {
    e.preventDefault();

    const item = $(this).closest('.translationWrap').find('.transitionString');
    const strings = [{
        id: item.data('field-id'),
        string: item.text().trim()
      }];

    getTranslation(strings, translationOptions.languageTo);
  });

  /* Search All */
  $('.translationGetAll').click(function (e) {
    e.preventDefault();

    const strings = [];
    $('.translationWrap').each(function (index, elem){
      const wrap = $(this);
      if (wrap.find('.translationField').val() !== '') {
        return true;
      }

      const item = wrap.find('.transitionString');
      strings.push({
        id: item.data('field-id'),
        string: item.text().trim()
      });
    });

    $(this).closest('div').find('.spinner').show();

    getTranslation(strings, translationOptions.languageTo);
  });

  /*  Insert single */
  $('.translationInsert').click(function (e) {
    e.preventDefault();

    insertTranslation($(this).closest('.translationWrap'));
  });

  /* Insert All */
  $('.translationInsertAll').click(function (e) {
    e.preventDefault();

    $('.translationWrap').each(function (index, elem){
      insertTranslation($(this));
    });
  });

  function insertTranslation(parent) {
    const target = parent.find('.transitionTarget');
    const field = parent.find('.translationField');
    if (target.text() === '' || field.val() !== '' ) {
      return true;
    }

    field.val(target.text());
  }


  function getTranslation(strings, languageTo) {
    const data = {strings: strings, languageTo: languageTo};

    $.post( '/translation', data )
      .done(function(response) {
        $(document).trigger('hostelz:staffTranslationResponse', response)
      })
      .fail(function(e) {
        console.log( "error" );
      })
  }

  function loadTranslation(e, data) {
    $.each(data.result, function (i, e) {
      $('#target-' + e.id).text(e.string).closest('.transitionTargetWrap').show();
    })
  }

});