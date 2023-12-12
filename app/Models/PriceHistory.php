<?php

namespace App\Models;

use App\Booking\RoomAvailability;
use App\Booking\SearchCriteria;
use App\Models\Listing\Listing;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lib\BaseModel;

class PriceHistory extends BaseModel
{
    use HasFactory;

    protected $table = 'priceHistory';

    public static $staticTable = 'priceHistory'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    // Reasonable limits to avoid bad price data from corrupting the price history information
    public const MAX_DORM_PRICE_USD = 120;

    public const MAX_PRIVATE_PRICE_USD = 700;

    public const CITY_DEFAULT_PRICE = 9; // $

    public const MONTH_RANGE = 6;

    protected $casts = [
        'month' => 'date:Y-m',
    ];

    /* Static */

    public static function recordPrice(Listing $listing, SearchCriteria $searchCriteria, $roomAvailabilities): void
    {
        $lock = acquireLock('recordPriceLock:' . $listing->id, 60, 15); // to avoid race conditions when checking existing / adding new price data

        $existingMatches = self::where('listingID', $listing->id)
                               ->bySearchCriteria($searchCriteria)
                               ->get();

        /** @var RoomAvailability[] $roomAvailabilities */
        foreach ($roomAvailabilities as $roomAvailability) {
            if ($roomAvailability->roomInfo->type != $searchCriteria->roomType) {
                logError("RoomTypes didn't match.");

                continue;
            }

            $pricePerNight = (float) $roomAvailability->averagePricePerBlockPerNight(false, 'USD');

            if ($searchCriteria->roomType === 'private') {
                if ($pricePerNight > self::MAX_PRIVATE_PRICE_USD) {
                    $listing->useForBookingPrice = false;
                    $listing->save();
                    // logError("Private room price $pricePerNight for $listingID is too high.");
                    continue;
                }
                $existing = $existingMatches->where('peoplePerRoom', $roomAvailability->roomInfo->peoplePerRoom);
            } else {
                if ($pricePerNight > self::MAX_DORM_PRICE_USD) {
                    // logError("Dorm room price $pricePerNight for $listingID is too high.");
                    $listing->useForBookingPrice = false;
                    $listing->save();

                    continue;
                }
                $existing = $existingMatches; // we ignore peoplePerRoom for dorm rooms
            }

            if ($existing->count() > 1) {
                logError("Multiple duplicate price histories for $listing->id " . $searchCriteria->summaryForDebugOutput());
            }

            /** @var self $existing */
            $existing = $existing->first();

            if ($existing) {
                $existing->averageInAnotherPrice($pricePerNight);
                $existing->save();
            } else {
                $new = new self([
                    'listingID' => $listing->id,
                    'month' => $searchCriteria->startDate->format('Y-m') . '-01',
                    'roomType' => $searchCriteria->roomType,
                    'peoplePerRoom' => $searchCriteria->roomType === 'private'
                        ? $roomAvailability->roomInfo->peoplePerRoom
                        : 0,
                    'dataPointsInAverage' => 1,
                    'averagePricePerNight' => $pricePerNight,
                ]);
                $new->save();
                $existingMatches->push($new);
            }

            if (! $listing->useForBookingPrice) {
                $listing->useForBookingPrice = true;
                $listing->save();
            }
        }

        releaseLock('recordPriceLock:' . $listing->id);
    }

    /* Accessors & Mutators */

    /* Static */

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'monthly':
                $output .= "Optimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    public static function mergeListings($primaryListingID, $mergeListingID): void
    {
        $otherListingPrices = self::where('listingID', $mergeListingID)->get();

        foreach ($otherListingPrices as $otherListingPrice) {
            $primaryListingPrice = self::where('listingID', $primaryListingID)
                                       ->where('roomType', $otherListingPrice->roomType)->where('month', $otherListingPrice->month)
                                       ->where('peoplePerRoom', $otherListingPrice->peoplePerRoom)->first();
            if ($primaryListingPrice) {
                for ($i = 0; $i < $otherListingPrice->dataPointsInAverage; $i++) {
                    $primaryListingPrice->averageInAnotherPrice($otherListingPrice->averagePricePerNight);
                }
                $primaryListingPrice->save();
                $otherListingPrice->delete();
            } else {
                $otherListingPrice->listingID = $primaryListingID;
                $otherListingPrice->save();
            }
        }
    }

    /* Misc */

    public function averageInAnotherPrice($pricePerNight): void
    {
        if ($this->dataPointsInAverage) {
            $this->averagePricePerNight = round(($this->averagePricePerNight * $this->dataPointsInAverage + $pricePerNight) / ($this->dataPointsInAverage + 1), 2);
        } else {
            $this->averagePricePerNight = $pricePerNight;
        }

        $this->dataPointsInAverage++;
    }

    /* Scopes */

    public function scopeBySearchCriteria($query, SearchCriteria $searchCriteria)
    {
        return $query->where('roomType', $searchCriteria->roomType)
                     ->where('month', $searchCriteria->startDate->format('Y-m') . '-01');
    }

    public function scopeActiveHistoryPastMonth(Builder $query, $plusOrMinusMonths = self::MONTH_RANGE, $roomType = null)
    {
        return $query->where([
            ['month', '>=', Carbon::now()->subMonths($plusOrMinusMonths)->startOfMonth()->format('Y-m-d')],
            ['month', '<=', Carbon::now()->addMonths($plusOrMinusMonths)->startOfMonth()->format('Y-m-d')],
        ])
                     ->when($roomType, fn ($query) => $query->where('roomType', $roomType));
    }

    public function scopePriceRange(Builder $query, $plusOrMinusMonths = self::MONTH_RANGE)
    {
        return $query->select(DB::raw('Min(averagePricePerNight) as min, Max(averagePricePerNight) as max, roomType'))
                     ->activeHistoryPastMonth($plusOrMinusMonths)
                     ->groupBy(['listingID', 'roomType']);
    }

    /* Relationships */

    public function listing(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(related: Listing::class, foreignKey: 'id', localKey: 'listingID');
    }

    /* custom */
}
