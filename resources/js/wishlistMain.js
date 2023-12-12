import {WishlistLogin, initWishlists, updateWishlistsIcons, wishlistCreate} from "./wishlistLib";

document.addEventListener("DOMContentLoaded", () => {
    updateWishlistsIcons();
    initWishlists();

    const wishlistLogin = new WishlistLogin();

    var createWishListModal = $('#createWishlistModal');
    wishlistCreate(createWishListModal);

    document.body.addEventListener(
        "hostelz:updateListingsSearchResultContent",
        (e) => {
            updateWishlistsIcons();
            initWishlists();
        }
    );

    /* delete wishList on the enter key pres */
    $(document).keypress(function (e) {
        if ($("#deleteWishlistModal").hasClass('show') && (e.keycode == 13 || e.which == 13)) {
            $("#deleteWishListForm").submit();
        }
    });
});
