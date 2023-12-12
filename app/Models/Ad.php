<?php

namespace App\Models;

use App;
use App\Traits\PlaceFields;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Lib\BaseModel;

class Ad extends BaseModel
{
    use PlaceFields;

    public static $placeTypes = ['ContinentInfo', 'CountryInfo', 'Region', 'CityInfo'];

    protected $table = 'ads';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    protected $casts = [
        'startDate' => 'datetime',
        'endDate' => 'datetime',
    ];

    public static $statusOptions = ['ok', 'disabled'];

    public static $placementTypeOptions = ['citiesAndListings'];

    public const PIC_RESIZED_MAX_WIDTH = 260; // about the widest we'll probably display it

    public function __construct(array $attributes = [])
    {
        // Default values
        $this->status = 'ok';
        $this->placementType = 'citiesAndListings';
        $this->userID = auth()->id();
        $this->viewsRemaining = -1;
        $this->viewsPerDay = 999999;

        parent::__construct($attributes);
    }

    public function delete(): void
    {
        foreach ($this->pics as $pic) {
            $pic->delete();
        }

        parent::delete();
    }

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $return = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'name' => ['maxLength' => 100],
                    'placeID' => ['type' => 'ignore'], // just here so we can make URLs that search by place
                    'placeType' => ['type' => 'ignore'], // just here so we can make URLs that search by place
                    'placementType' => ['type' => 'select', 'options' => self::$placementTypeOptions],
                    'placeSelector' => self::placeSelectorFieldInfo(),
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'linkURL' => ['type' => 'url', 'maxLength' => 250, 'validation' => 'url', 'sanitize' => 'url'],
                    'adText' => ['type' => 'textarea', 'rows' => 3, 'maxLength' => 500],
                    'viewsRemaining' => ['maxLength' => 10, 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int',
                        'popoverText' => 'Or -1 for unlimited.', ],
                    'startDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 10],
                    'endDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 10],
                    'viewsPerDay' => ['maxLength' => 10, 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'viewsToday' => ['maxLength' => 10, 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'notes' => ['type' => 'textarea', 'rows' => 3],
                    'incomingLinkID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->incomingLink ? $model->incomingLink->url : $model->incomingLinkID;
                        }, ],
                ];

                break;

            case 'incomingLinkAd':
                $return = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'placeSelector' => self::placeSelectorFieldInfo(),
                    'linkURL' => ['maxLength' => 250],
                    'adText' => ['type' => 'textarea', 'rows' => 3, 'maxLength' => 500],
                    //'startDate' => [ 'type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 10 ],
                    //'endDate' => [ 'type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 10 ],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $return;
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'daily':
                $output .= "\nReset viewsToday.\n";
                self::where('viewsToday', '>', 0)->update(['viewsToday' => 0]);

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    /* Accessors & Mutators */

    /* Static */

    public static function getAdForCity($cityInfo)
    {
        // Get Matching Ads

        // scopeFindByPlaceMatchingCityInfo($query, $cityInfo)
        $ads = self::areLive()->where('placementType', 'citiesAndListings')->findByPlaceMatchingCityInfo($cityInfo, true)->get();
        if ($ads->isEmpty()) {
            return '';
        }

        // Sort from most specific to least specific
        $ads = $ads->sort(function ($a, $b) {
            return $b->placeSpecificityRank() - $a->placeSpecificityRank();
        });

        // Only use ads of the most specific type
        $mostSpecificType = $ads->first()->placeType;
        $ads = $ads->filter(function ($ad) use ($mostSpecificType) {
            return $ad->placeType == $mostSpecificType;
        });

        // Select Random Ad (weighted by remainingViewsToday())

        // Calculate total pool
        $totalRemainingViewsToday = $ads->sum(function ($ad) {
            return $ad->remainingViewsToday();
        });

        // Select random spot in the pool
        $random = rand(1, $totalRemainingViewsToday);
        foreach ($ads as $ad) {
            if ($ad->remainingViewsToday() >= $random) {
                break;
            } // found the winner
            $random -= $ad->remainingViewsToday();
        }

        // Record ad view
        if (App::environment('production')) {
            self::where('id', $ad->id)->increment('viewsToday');
            if ($ad->viewsRemaining != -1) {
                self::where('id', $ad->id)->where('viewsRemaining', '>', 0)->decrement('viewsRemaining');
            }
        }

        // Get Ad Pics
        $pic = $ad->pics()->orderBy('isPrimary', 'DESC')->orderByRaw('RAND()')->first();
        if (! $pic && $ad->adText == '') {
            logError("No pics or text found for ad $ad->id.");

            return '';
        }

        // (Not making them "rel=nofollow" because there is no need since they're loaded dynamically and redirect through our non-crawled site,
        // and link partners prefer not to see "nofollow" on the link.)
        return '<a href="' . routeURL('adClick', $ad->id, 'protocolRelative') . '" target="_blank" class="asidebar-link">' .
            ($pic ? '<div class="text-center"><img class="asidebar-img w-75 mb-4" src="' . $pic->url(['', 'originals']) . '"></div>' : '') .
            ($ad->adText != '' ? "<div class=\"asidebar-text text-dark\">$ad->adText</div>" : '') .
            '</a>';
    }

    /* Misc */

    public function duplicate()
    {
        $new = $this->replicate();
        $new->save(); // have to save it first so that it has an id
        $newID = $new->id;

        // We have to re-load the new because the replicate one thinks it has relationships (like agents) of the original that it doesn't actually have yet (Laravel bug?).
        $new = $new->fresh();

        // photos
        foreach ($this->pics as $pic) {
            $new->addPic($pic->localFilePath('originals'), $pic->caption);
        }

        return $new;
    }

    public function addPic($picFilePath, $caption = '')
    {
        return Pic::makeFromFilePath($picFilePath, [
            'subjectType' => 'ads', 'subjectID' => $this->id, 'type' => '', 'status' => 'ok',
            'caption' => (string) $caption,
        ], [
            'originals' => [],
            '' => ['saveAsFormat' => 'jpg', 'maxWidth' => self::PIC_RESIZED_MAX_WIDTH, 'outputQuality' => 80, 'skipIfUnmodified' => true],
        ]);
    }

    public function remainingViewsToday()
    {
        return $this->viewsPerDay - $this->viewsToday;
    }

    public function recordClick(): void
    {
        $adStats = DB::table('adStats')->where('adID', $this->id)->where('statsDate', Carbon::now()->format('Y-m-d'))->first();
        if (! $adStats) {
            DB::table('adStats')->insert(['adID' => $this->id, 'statsDate' => Carbon::now()->format('Y-m-d'), 'clickCount' => 1]);
        } else {
            DB::table('adStats')->where('id', $adStats->id)->increment('clickCount');
        }
    }

    /* Scopes */

    public function scopeAreLive($query)
    {
        return $query->where('status', 'ok')->where(DB::raw('viewsPerDay - viewsToday'), '>', 0)
            ->where(function ($query): void {
                $query->where('viewsRemaining', '>', 0)->orWhere('viewsRemaining', '=', -1);
            });
    }

    /* Relationships */

    public function pics()
    {
        return $this->hasMany(Pic::class, 'subjectID')->where('subjectType', 'ads');
    }

    public function incomingLink()
    {
        return $this->belongsTo(IncomingLink::class, 'incomingLinkID');
    }
}
