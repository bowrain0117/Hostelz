<?php

namespace App\Http\Controllers;

use App\Models\Listing\Listing;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $lists = $user->wishlists;

        return view('wishlist.index', compact('lists'));
    }

    public function show(Wishlist $wishlist)
    {
        $user = auth()->user();

        if (! $user?->isSame($wishlist->user)) {
            return redirect()->route('wishlist:index');
        }

        $listings = $wishlist->listings;

        return view('wishlist.show', compact('wishlist', 'listings'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50',
            'isShared' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'messages' => $validator->messages(),
            ]);
        }

        auth()->user()->wishlists()->create($validator->valid());

        return response()->json([
            'status' => 'success',
            'redirect' => routeURL('wishlist:index'),
        ]);
    }

    public function destroy(Wishlist $wishlist)
    {
        $user = auth()->user();

        if (! $user->isSame($wishlist->user)) {
            return redirect()->route('wishlist:index');
        }

        $wishlist->delete();

        return redirect(routeURL('wishlist:index'));
    }

    public function isActive()
    {
        $user = auth()->user();
        $listings = $user->wishlists->map(function ($item) {
            return $item->listings->pluck('id');
        })->flatten();

        return response()->json(compact('listings'));
    }

    public function userLists()
    {
        $wishlists = auth()->user()->wishlists;

        return response()->json([
            'status' => 'success',
            'wishlists' => view('wishlist.userWishlists', compact('wishlists'))->render(),
        ]);
    }

    public function addListing(Wishlist $wishlist, Listing $listing)
    {
        auth()->user()
            ->wishlists()->find($wishlist->id)
            ->listings()->toggle($listing);

        return response()->json([
            'status' => 'success',
            'wishlist' => [
                'name' => $wishlist->name,
                'path' => $wishlist->path,
            ],
        ]);
    }

    public function deleteListing(Listing $listing)
    {
        $wishlist = $listing->wishlists()->where('user_id', auth()->user()->id)->first();

        $status = 0;
        if ($wishlist) {
            $status = $wishlist->listings()->detach($listing);
        }

        return response()->json([
            'status' => $status,
            'wishlist' => [
                'name' => $wishlist->name,
                'path' => $wishlist->path,
            ],
        ]);
    }
}
