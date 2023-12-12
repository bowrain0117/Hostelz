<?php

namespace App\Models;

use App\Models\Listing\Listing;
use Exception;
use Illuminate\Support\Facades\DB;
use Lib\BaseModel;

class SavedList extends BaseModel
{
    protected $table = 'savedLists';

    public static $staticTable = 'savedLists'; // just here so we can get the table name without needing an instance of the object

    public function __construct(array $attributes = [])
    {
        // Default values

        parent::__construct($attributes);
    }

    public function delete(): void
    {
        foreach ($this->allSavedListListingInfos() as $savedListListingInfo) {
            $this->removeListing($savedListListingInfo->listing_id);
        }

        parent::delete();
    }

    public static function fieldInfo($purpose)
    {
        switch ($purpose) {
            case 'adminEdit':
                return [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'user_id' => ['type' => $purpose == 'adminEdit' ? '' : 'ignore', 'validation' => 'required'],
                    'name' => ['validation' => 'required'],
                    'isShared' => ['type' => 'select', 'options' => ['1', '0'], 'optionsDisplay' => 'translate'],
                ];
                /*
                    determinesDynamicGroup - (Not needed for 'server' type dynamic methods, but 'server' type needs to set 'submitFormOnChange' to work). The value of this element is used to set the dynamicGroup, which is used to display/hide elements of the form.
    dynamicGroup / dynamicGroupValues - To show the element only if the dynamicGroup value is in dynamicGroupValues (CSV list).
    dynamicMethod - 'remove' (default), 'hide', or 'server'. Use 'hide' to keep the elements but not show them (which means they get submitted with the form). */

            case 'userEdit':
                return [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'name' => ['validation' => 'required'],
                    'isShared' => ['type' => 'select', 'options' => ['0', '1'], 'optionsDisplay' => 'translate', 'determinesDynamicGroup' => 'isShared'],
                ];

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }
    }

    /* Accessors & Mutators */

    /* savedListListings */

    public function allSavedListListingInfos()
    {
        return DB::table('savedListListings')->where('savedList_id', $this->id)->get()->all();
    }

    public function savedListListingInfo($listingID)
    {
        return DB::table('savedListListings')->where('savedList_id', $this->id)->where('listing_id', $listingID)->first();
    }

    public function hasListing($listingID)
    {
        return $this->savedListListingInfo($listingID) != null;
    }

    public function addListing($listingID): void
    {
        if ($this->hasListing($listingID)) {
            return;
        }
        DB::table('savedListListings')->insert(['savedList_id' => $this->id, 'listing_id' => $listingID]);
    }

    public function removeListing($listingID)
    {
        return DB::table('savedListListings')->where('savedList_id', $this->id)->where('listing_id', $listingID)->delete();
    }

    public function setListingNotes($listingID, $notes)
    {
        return DB::table('savedListListings')->where('savedList_id', $this->id)->where('listing_id', $listingID)->update(['notes' => $notes]);
    }

    public static function getAllListIDsWithListing($listingID)
    {
        return DB::table('savedListListings')->where('listing_id', $listingID)->pluck('savedList_id')->all();
    }

    /* Misc */

    // $query is assumed to be a query on the Listings table.

    public function makeListingsQuery($existingQuery = null)
    {
        $query = ($existingQuery ?: Listing::query());

        // whereIn() is used rather than whereExists() because there will be a limited number of listings per list.
        return $query->whereIn('id', function ($query): void {
            $query->select('savedListListings.listing_id')->from('savedListListings')->where('savedListListings.savedList_id', $this->id);
        });
    }

    /* Relationships */

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
