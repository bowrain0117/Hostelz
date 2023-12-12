import { wishlistCreate } from './wishlistLib';

$(document).ready (function () {
  var createWishListModal = $('#createWishlistModal');
  wishlistCreate(createWishListModal);

  $('body').on('hostelz:createWishlistSuccess', function (e, data) {
    createWishListModal.modal('hide')
    window.location.replace(data.result.redirect);
  });
});