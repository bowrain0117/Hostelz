<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CityInfo;
use App\Models\Listing\Listing;
use App\Services\ListingCategoryPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lib\DataCorrection;
use Lib\FormHandler;
use Lib\Geocoding;

class EditCityInfoStaffController extends Controller
{
    public function __construct(
        protected ListingCategoryPageService $categoriesService
    ) {
    }

    public function __invoke(Request $request, $pathParameters = null)
    {
        $message = '';

        $formHandler = new FormHandler(
            'CityInfo',
            CityInfo::fieldInfo(auth()->user()?->isAdmin() ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );

        $formHandler->allowedModes = auth()->user()->hasPermission('admin')
            ? ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete']
            : ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];

        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['city', 'region', 'country', 'totalListingCount'];
        $formHandler->listSort['city'] = 'asc';
        $formHandler->callbacks = [
            'setModelData' => function ($formHandler, $data, &$dataTypeEventValues) use (&$message) {
                // Update listings
                if ($data['city'] != $formHandler->model->city || $data['region'] != $formHandler->model->region ||
                    $data['country'] != $formHandler->model->country) {
                    Listing::where('city', $formHandler->model->city)->where('region', $formHandler->model->region)->where('country', $formHandler->model->country)->
                    update(['city' => $data['city'], 'region' => $data['region'], 'country' => $data['country']]);
                }

                // Remember renaming
                if ($data['rememberRegionRenaming']) {
                    // saveCorrection($dbTable, $dbField, $oldValue, $newValue, $contextValue1 = null, $contextValue2 = null, $returnInsertAsArray = false)
                    if ($formHandler->model->region != $data['region'] && $formHandler->model->region != '') {
                        DataCorrection::saveCorrection('', 'region', $formHandler->model->region, $data['region'], $data['country'], '');
                        // Also rename the region in other cityInfos and all listings in other cities
                        Listing::where('country', $data['country'])->where('region', $formHandler->model->region)->update(['region' => $data['region']]);
                        CityInfo::where('country', $data['country'])->where('region', $formHandler->model->region)->update(['region' => $data['region']]);
                        $message .= "<p>Remembering '" . $formHandler->model->region . "' -> '$data[region]'.</p>";
                    }
                }
                if ($data['rememberCityRenaming']) {
                    if ($formHandler->model->city != $data['city'] && $formHandler->model->city != '') {
                        DataCorrection::saveCorrection('', 'city', $formHandler->model->city, $data['city'], $data['country'], '');
                        // (Note: Listings were already updated above.)
                        $message .= "<p>Remembering '" . $formHandler->model->city . "' -> '$data[city]'.</p>";
                    }
                }

                return $formHandler->setModelData($data, $dataTypeEventValues, false);
            },
        ];

        $formHandler->go(null, $formHandler->allowedModes, 'searchAndList');

        if ($formHandler->mode === 'update') {
            // check for duplicates
            $duplicatesQuery = CityInfo::where('id', '!=', $formHandler->model->id)
                ->whereRaw('BINARY country=?', [$formHandler->model->country])->whereRaw('BINARY city=?', [$formHandler->model->city]);
            if ($formHandler->model->region != '') {
                $duplicatesQuery->where(function ($query) use ($formHandler): void {
                    $query->whereRaw('BINARY region=?', [$formHandler->model->region])->orWhere('region', '');
                });
            }
            $duplicates = $duplicatesQuery->get();

            if ($duplicates) {
                foreach ($duplicates as $duplicate) {
                    // If there were multiple matches, our model may now have a region set,
                    // so need to make sure we don't merge it with one with a different region set.
                    if ($formHandler->model->region != '' && $duplicate->region != '' && $formHandler->model->region != $duplicate->region) {
                        continue;
                    }

                    $message .= "<p>Merging with duplicate city $duplicate->id '$duplicate->city'.</p>";
                    $formHandler->model->merge($duplicate);
                }
            }
        }

        if ($request->has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded

            switch ($request->input('objectCommand')) {
                case 'searchRank':
                    $rank = $formHandler->model->updateSearchRank();
                    $message = "Search rank updated ($rank).";

                    break;

                case 'geocodedInfo':
                    $result = Geocoding::reverseGeocode($formHandler->model->latitude, $formHandler->model->longitude, Listing::LATLONG_PRECISION);
                    $message = '<h3>Geocoded Info</h3><br><pre>' . print_r($result, true) . '</pre>';

                    break;

                case 'updateGeocoding':
                    $formHandler->model->updateGeocoding();
                    $formHandler->model = $formHandler->model->fresh(); // reload it in case anything changed.
                    $message = '<h3>Updated Geocoding Info.</h3>';

                    break;

                case 'updateSpecialListings':
                    $message = '<h3>Updated Special Listings</h3>';
                    $message .= "<p>OLD topRatedHostel={$formHandler->model->topRatedHostel}, cheapestHostel={$formHandler->model->cheapestHostel}</p>";

                    $formHandler->model = $formHandler->model->fresh(); // reload it in case anything changed.

                    $message .= $formHandler->model->updateSpecialListings() ?
                        '<p><strong>The data has been updated.</strong></p>' :
                        '<p><strong>The data has not been updated.</strong></p>';
                    $message .= "<p>NEW topRatedHostel={$formHandler->model->topRatedHostel}, cheapestHostel={$formHandler->model->cheapestHostel}</p>";

                    break;
            }
        }

        $categoryPages = $this->getCategoryPagesData($formHandler->model);

        return $formHandler->display('staff/edit-cityInfo', compact('message', 'categoryPages'));
    }

    private function getCategoryPagesData(?CityInfo $cityInfo): Collection
    {
        if (is_null($cityInfo)) {
            return collect();
        }

        return $this->categoriesService->allForCity($cityInfo)
            ->map(fn ($item) => (object) [
                'category' => $item->category,
                'categoryUrl' => $item->category->url($cityInfo),
                'listingsCount' => $item->listingsCount,
                'editLink' => $item->category->editUrl($cityInfo),
            ]);
    }
}
