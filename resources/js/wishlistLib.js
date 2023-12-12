var errorClass = 'is-invalid';
var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

class WishlistLogin {

    constructor() {
        this.modal = $('#wishlistLoginModal');
        this.form = $('#wishlistLoginForm');
        this.submitBTN = this.modal.find("button[type='submit']");

        this.init();
    }

    init() {
        this.addEvents();
    }

    addEvents() {
        this.form.submit(this.onSubmit.bind(this));

        $('body').on('hostelz:userWishlistsAccessDenied', this.showModal.bind(this));

        $(document).on('hostelz:loadedFrontUserData', this.updateCsrf.bind(this));
    }

    onSubmit(e) {
        e.preventDefault();

        this.removeErrors();
        this.showSpinner();

        $.post('/flogin', this.form.serializeArray())
            .done(this.submitDone.bind(this))
            .fail(this.submitFail.bind(this))
    }

    submitDone(result) {
        if (result.status === 'success') {

            this.modal.modal('hide');

            $('body').trigger('hostelz:wishlistLoginSuccess');

            updateWishlistsIcons();

            // this.currentWishlistIcon.click();

        } else {
            $.each(result.errors, function (i, e) {
                $('#' + i)
                    .addClass(errorClass)
                    .next().text(e);
            });
        }

        this.hideSpinner();
    }

    submitFail(e) {
        // console.warn( "error", e );
    }

    showModal(e, obj) {
        this.hideSpinner()
        this.modal.modal('show');

        // if (obj.wishlistIcon) {
        //   this.currentWishlistIcon = obj.wishlistIcon;
        // }
    }

    updateCsrf(e, data) {
        $('body').find("input[name='_token']").val(data.csrf);
    }

    removeErrors() {
        this.form.find('.' + errorClass).removeClass(errorClass);
    }

    showSpinner() {
        this.submitBTN
            .prop('disabled', true)
            .find('.spinner').show();
    }

    hideSpinner() {
        this.submitBTN
            .prop('disabled', false)
            .find('.spinner').hide();
    }
}

/*  toggle wishlist heart */
function initWishlists() {
    var addToWishlistModal = $('#wishlistModal');
    var addToWishlistsModalBody = addToWishlistModal.find('#wishlistModalBody');
    var addToWishlistsModalSpinner = addToWishlistModal.find('.spinner-wrap');

    var createWishListModal = $('#createWishlistModal');

    $('.wishlistHeartWrap').click(function (e) {
        e.preventDefault();

        var wishlistIcon = $(this).find('.wishlistHeart');

        if (wishlistIcon.hasClass('selected')) {
            removeFromWishlist(wishlistIcon);
        } else {
            addToWishlist(wishlistIcon);
        }
    });

    function removeFromWishlist(wishlistIcon) {
        $.post('/wishlists/listing/' + wishlistIcon.data('listing'), {'_method': 'DELETE', "_token": csrf})
            .done(function (result) {
                if (!result.status) {
                    return true;
                }

                var wishlistDashboard = wishlistIcon.closest('.wishlistDashboard');
                if (wishlistDashboard.length !== 0) {
                    wishlistDashboard.fadeOut(300, function () {
                        wishlistDashboard.remove();
                    });
                }

                updateWishlistsIcons();

                $('#removedWishlistToastLink').attr('href', result.wishlist.path).text(result.wishlist.name);
                $('#removedWishlistToast').toast('show');

            })
            .fail(function () {
                // console.warn( "error" );
            })
    }

    function addToWishlist(wishlistIcon) {
        addToWishlistModal.modal('show');
        showSpinner();
        $.get('/wishlists/userLists')
            .done(function (result) {
                if (result.status !== 'success') {
                    return true;
                }

                addToWishlistsModalBody.html(result.wishlists);
                hideSpinner();

                $('.wishlistItem').click(function (e) {
                    var wishlistItem = $(this);
                    if (wishlistItem.hasClass('wishlistItemCreate')) {
                        createWishListModal.modal('show');

                        addToWishlistModal.modal('hide');

                        $('body').on('hostelz:createWishlistSuccess', function (e, obj) {
                            createWishListModal.modal('hide');
                            wishlistIcon.click();
                        });
                    } else {
                        $.post(
                            '/wishlists/' + wishlistItem.data('wishlist') + '/listing/' + wishlistIcon.data('listing'),
                            {"_token": csrf}
                        )
                            .done(function (result) {
                                addToWishlistModal.modal('hide');

                                updateWishlistsIcons();

                                $('#addedWishlistToastLink').attr('href', result.wishlist.path).text(result.wishlist.name);
                                $('#addedWishlistToast').toast('show');

                            })
                            .fail(function (e) {
                                console.warn(e);
                            })
                    }
                });

            })
            .fail(function (e) {
                if (e.responseJSON && e.responseJSON.error === "accessDenied") {

                    addToWishlistModal.modal('hide');

                    $('body').trigger('hostelz:userWishlistsAccessDenied', {wishlistIcon: wishlistIcon});
                }
            })
    }

    function showSpinner() {
        addToWishlistsModalSpinner.removeClass('d-none').addClass('d-flex');
    }

    function hideSpinner() {
        addToWishlistsModalSpinner.removeClass('d-flex').addClass('d-none');
    }
}


/*  highlight wishlist heart */
function updateWishlistsIcons() {
    var wishlistHeart = $('.wishlistHeart');
    if (wishlistHeart.length === 0) {
        return true;
    }

    getWishlistsListings();

    function getWishlistsListings() {
        $.get('/wishlists/isActive')
            .done(function (result) {
                if (!result.listings) {
                    return true;
                }

                $.each(wishlistHeart, function (i, e) {
                    var item = $(e);
                    if (result.listings.includes(item.data('listing'))) {
                        activateListingIcon(item);
                    } else {
                        deactivateListingIcon(item);
                    }
                });
            })
            .fail(function (e, t) {
                // console.warn( "error", e, t );
            })
    }

    function activateListingIcon(item) {
        if (!item.hasClass('selected')) {
            item.addClass('fas selected');
        }
    }

    function deactivateListingIcon(item) {
        item.removeClass('fas selected');
    }
}

/*  create wishlist */
function wishlistCreate(createWishListModal) {
    var createWishListForm = createWishListModal.find('#createWishListForm');
    var submitBTN = createWishListModal.find("button[type='submit']");
    // var errorClass = 'is-invalid';

    createWishListModal.on('show.bs.modal', function (e) {
        removeErrors(createWishListForm);
        hideSpinner(submitBTN);
    })

    createWishListForm.submit(function (e) {
        e.preventDefault();

        var form = $(this);

        removeErrors(form);
        showSpinner(submitBTN);

        $.post(form.attr('action'), form.serializeArray())
            .done(function (result) {

                if (result.status === 'success') {
                    $('body').trigger('hostelz:createWishlistSuccess', {result: result, modal: createWishListModal});
                } else {
                    $.each(result.messages, function (i, e) {
                        $('#' + i)
                            .addClass(errorClass)
                            .next().text(e);

                    });
                }

                hideSpinner(submitBTN);
            })
            .fail(function () {
                // console.warn( "error" );
            })
    });

    function removeErrors(form) {
        form.find('.' + errorClass).removeClass(errorClass);
    }

    function showSpinner(submitBTN) {
        submitBTN
            .prop('disabled', true)
            .find('.spinner').show();
    }

    function hideSpinner(submitBTN) {
        submitBTN
            .prop('disabled', false)
            .find('.spinner').hide();
    }
}

export {wishlistCreate, updateWishlistsIcons, initWishlists, WishlistLogin};