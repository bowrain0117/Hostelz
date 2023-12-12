<?php

/*

Used for functions that use our mine our data for interesting insights for articles, etc.

https://dev-secure.hostelz.com/staff/data-mining/{function}

*/

/*

I can answer almost any question about hostels (that can be answered with factual or statistical data). AMA!

[Dan's IamA](https://www.reddit.com/r/IAmA/comments/3z2pl8/i_am_a_longterm_budget_traveller_who_has_stayed/) last weekend answered a lot of great general hostel/travel questions.  But I can answer a *different* type of question about hostels.  I work for [Hostelz.com](https://www.hostelz.com), the only comprehensive database of hostel information online.  So I'm in a unique position to crunch numbers from our database to answer all kinds of interesting **factual or statistical questions** about hostels that you won't find answers to anywhere else.

You can ask me things like...

* What month are hostels cheapest in Paris?
* What country has the highest ratio of girls to guys staying in the hostels?
* What's the *highest* rated hostel in Italy?  What's the *worst* rated?
* What country has the cheapest hostels?
* What percent of hostels are listed on Hostelworld?
* What percent of hostel bookings are for a solo traveler?
* Which of the hostel booking websites most often has the lowest price?
* What percent of hostels in Spain allow pets?
* What's the dirtiest hostel in Europe (lowest cleanliness rating)?
* What percent of hostels require you to bring your own sheets?
* What country has the most hostels?
* Why is there always a group of Australian guys in my dorm room at every hostel who want me to go party with them? (j/k, no one knows the answer to that one)

I'll do my best to answer any questions what can be answered with the data we have available (so that means anything about hostel prices, ratings, features, and booking statistics such as country of origin, gender, number of people, and number of nights booked).

Some questions may take a lot longer to research and compute than others, and I don't want to choke our poor database by running too many queries at once.  Some queries may be too difficult to compute because of limitations of the way the database is structured.  But I'll do my best to answer as many as I can all day long, especially questions with the most upvotes.

**Background info on Hostelz.com:** Hostelz.com is the only hostel information website that lists information on all hostels worldwide.  We aggregate ratings and other information from all the major hostel booking websites, but we also list the other 30% of lesser known hostels that don't use the major booking websites.  We don't charge any fees to hostels to be listed on the site (and since we're a real information and reviews website, we also don't remove hostels that want to be removed just because they don't like their reviews).  The site shows a price comparison of all the major booking websites for each hostel so you can see where to get the lowest price, and we also provide direct contact info for hostels for people who want to contact them directly.  We also have guest reviews and also has more comprehensive reviews with photos taken by the reviews to show what the accommodations really look like.

[Proof](https://www.facebook.com/hostelz)




We're doing a "ask me anything" on Reddit. This is your chance to ask any factual question you want about hostels. Such as... What month are hostels cheapest in Paris? What's the highest rated hostel in Italy? What's the worst? Ask us anything.


We're doing a "Ask me anything" about #hostels and #travel right now on Reddit... https://www.reddit.com/r/IAmA/comments/3zwxxb/i_can_answer_almost_any_question_about_hostels/ …


I'm doing a "ask me anything" on Reddit for hostels questions (representing Hostelz.com)...

(text mattt)

*/

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\Imported;
use App\Models\Listing\Listing;
use App\Models\PriceHistory;
use DB;
use Lib\Currencies;
use View;

class DataMiningController extends Controller
{
    public function go($function)
    {
        // set_time_limit(60*10);
        DB::disableQueryLog(); // to save memory

        return $this->$function();
    }

    // ** //

    private function jobsWork(): void
    {
        $listings = Listing::areLive()->where('propertyType', 'hostel')->where('compiledFeatures', 'like', '%work%')->get();

        foreach ($listings as $listing) {
            if (! isset($listing->compiledFeatures['extras']) || ! in_array('work', $listing->compiledFeatures['extras'])) {
                continue;
            } // in case some matched for some other reason
            echo "$listing->name\t$listing->city\t$listing->country\t" . $listing->getBestEmail('listingIssue') . "\n";
        }
    }

    private function graphTest()
    {
        $rows = Listing::areLive()->where('propertyType', 'Hostel')->groupBy('propertyType')
            ->select(DB::raw('propertyType, count(*) as count'))->get();

        return $this->showGraph($rows);
    }

    // What percent of hostel bookings are for a solo traveler?

    private function bookingsByNumberOfPeople(): void
    {
        $rows = Booking::where('bookingTime', 'LIKE', '201%')// ->where('messageText', 'like', '%dorm%')
            ->where('people', '>', 0)->groupBy('people')->select(DB::raw('people, count(*) as count'))->get();

        $total = 0;
        foreach ($rows as $row) {
            echo "$row->people -> $row->count<br>";
            $total += $row->count;
        }
        echo "total: $total";
    }

    // Most booked hostels (Booking.com wanted to know)

    private function topBookedHostels(): void
    {
        $rows = Booking::where('bookingTime', 'LIKE', '2016%')// ->where('messageText', 'like', '%dorm%')
            ->where('listingID', '!=', 0)
            ->groupBy('listingID')->select(DB::raw('listingID, count(*) as count'))
            ->having('count', '>', 10)
            ->orderBy('count', 'desc')
            ->get();

        foreach ($rows as $row) {
            $listing = Listing::find($row->listingID);
            if (! $listing || ! $listing->isLive()) {
                continue;
            }

            $hasBookingDotCom = $listing->importeds->where('system', 'BookingDotCom')->count();
            echo "$listing->id\t$listing->name\t$listing->city\t$listing->country\t" . ($hasBookingDotCom ? '' : '(not linked to Booking.com)') . "\n";
        }
    }

    // hostel vs non hostel bookings

    private function hostelVsNonHostelBookings(): void
    {
        $bookings = Booking::where('bookingTime', 'LIKE', '201%')->limit(1000)->get();

        $propTypeCounts = [];
        $commissions = [];
        foreach ($bookings as $booking) {
            $listing = $booking->listing;
            if (! $listing || ! $listing->isLive()) {
                continue;
            }
            $propTypeCounts[$listing->propertyType] = ($propTypeCounts[$listing->propertyType] ?? null) + 1;
            $commissions[$listing->propertyType] = ($commissions[$listing->propertyType] ?? null) + $booking->commission;
            unset($listing);
        }

        echo '<pre>';
        print_r($propTypeCounts);
        print_r($commissions);
    }

    private function countriesWithoutHostels(): void
    {
        $countries = CountryInfo::orderBy('country')->pluck('country');

        $cityCountries = CityInfo::where('hostelCount', '>', 0)->groupBy('country')->orderBy('country')->pluck('country')->toArray();

        foreach ($countries as $country) {
            if (in_array($country, $cityCountries)) {
                continue;
            }
            echo "$country, ";
        }
    }

    // * What month are hostels cheapest in Paris?

    private function cheapestMonthToBook(): void
    {
        $listings = Listing::areLive()->where('propertyType', 'Hostel')
            ->where('country', 'Japan')->select('id', 'name', 'city', 'country')->get();

        $prices = PriceHistory::where('roomType', 'dorm')->where('dataPointsInAverage', '>', 1)
            ->whereBetween('month', ['2014-01-01', '2015-01-31']) // (using 2015 only because 2014 has strange totals for October)
            ->where('averagePricePerNight', '<', 50) // remove weird outliers
            ->whereIn('listingID', $listings->pluck('id'))->groupBy('justMonthNumber')
            ->select(DB::raw('AVG(averagePricePerNight) as averagePriceForMonth, MONTH(month) as justMonthNumber'))->get();

        foreach ($prices as $key => $price) {
            echo '- ' . date('F', mktime(0, 0, 0, $price->justMonthNumber, 10)) . ' ' . Currencies::format($price->averagePriceForMonth, 'USD') . '<br>';
        }
    }

    // * What percent of hostels in Spain allow pets?

    private function percentOfHostelsAllowPets()
    {
        // This has to load the listing and imported records for each listing, so it shouldn't be used with too broad of an area
        $listings = Listing::areLive()->where('propertyType', 'Hostel') // ->where('compiledFeatures', 'like', '%petsAllowed%')
            ->where('country', 'Spain')->select('id', 'name', 'city', 'country', 'compiledFeatures')->get();

        $totalCount = $yes = 0;
        foreach ($listings as $listing) {
            $totalCount++;
            if (isset($listing->compiledFeatures['petsAllowed']) && $listing->compiledFeatures['petsAllowed'] === 'yes') {
                $yes++;
            }
        }

        return "$yes / $totalCount = " . round(100 * $yes / $totalCount) . '%';
    }

    // * What's the dirtiest hostel in Europe (lowest cleanliness rating)?

    private function dirtiestHostel(): void
    {
        // This has to load the listing and imported records for each listing, so it shouldn't be used with too broad of an area
        $listings = Listing::areLive()->where('propertyType', 'Hostel')->where('combinedRatingCount', '>=', 15)
            // ->where('continent', 'Western Europe')
            // ->whereIn('continent', [ 'UK & Ireland', 'Eastern Europe & Russia' ])
            ->where('city', 'London')
            ->select('id', 'name', 'city', 'country')->get()
            ->load('importeds');

        foreach ($listings as $listing) {
            $ratings = $listing->getImportedRatingScoresForDisplay();
            if (! $ratings || empty($ratings['average']['cleanliness'])) {
                continue;
            }
            $listing->data_temp_cleanliness = $ratings['average']['cleanliness']; // add a psuedo property with the value
        }

        $listings = $listings->filter(function ($listing) {
            return $listing->data_temp_cleanliness != 0;
        })
            ->sortBy('data_temp_cleanliness', SORT_REGULAR, false);
        $this->listingsListMarkup($listings, 'data_temp_cleanliness');
    }

    // Worst rated hostels

    private function worstHostels(): void
    {
        $listings = Listing::areLive()->where('propertyType', 'Hostel')->where('combinedRatingCount', '>', 20)
            ->whereBetween('combinedRating', [1, 50])
            ->where('country', 'Italy')
            ->orderBy('combinedRating')->orderBy('combinedRatingCount', 'desc')->limit(20)->get();
        $this->listingsListMarkup($listings, 'combinedRating');
    }

    // Best rated hostels

    private function bestHostels(): void
    {
        $listings = Listing::areLive()->where('propertyType', 'Hostel')->where('combinedRatingCount', '>', 20)
            ->whereBetween('combinedRating', [90, 100])
            //->where('country', 'Japan')
            ->whereIn('continent', ['Western Europe', 'Eastern Europe & Russia'])
            // ->where('continent', 'UK & Ireland')->where('country', '!=', 'Ireland')
            ->orderBy('combinedRating', 'desc')->orderBy('combinedRatingCount', 'desc')->limit(30)->get();
        $this->listingsListText($listings, 'combinedRating');
    }

    private function singleFemale(): void
    {
        $rows = Booking::whereIn('gender', ['Male', 'Female'])->where('bookingTime', 'LIKE', '2014%')->groupBy('gender')
            ->where('people', '>', 0)
            ->select('gender', DB::raw('count(*) as count'))->limit(50000)->get();

        foreach ($rows as $row) {
            echo "$row->gender $row->count<br>";
        }
    }

    // * What country has the highest ratio of girls to guys staying in the hostels?

    private function genderRatioByCountry(): void
    {
        $bookingsByListing = Booking::whereIn('gender', ['Male', 'Female'])->where('bookingTime', '>', '2010-01-01')
            ->where('listingID', '!=', 0)->groupBy('gender', 'listingID')
            ->select('gender', 'listingID', DB::raw('count(*) as count'))->limit(50000)->get();
        $listingIDs = array_unique($bookingsByListing->pluck('listingID')->toArray());
        $listingCities = Listing::whereIn('id', $listingIDs)->select('country', 'city', 'id')->get();

        $listingIDToCity = [];
        foreach ($listingCities as $listingCity) {
            $listingIDToCity[$listingCity->id] = $listingCity->country;
        }

        $cityCounts = [];

        foreach ($bookingsByListing as $booking) {
            if (! $booking->listingID || empty($listingIDToCity[$booking->listingID])) {
                continue;
            }
            $listingCity = $listingIDToCity[$booking->listingID];
            $cityCounts[$listingCity][$booking->gender] = ($cityCounts[$listingCity][$booking->gender] ?? null) + $booking->count;
        }

        // convert counts to ratios
        $ratios = [];
        foreach ($cityCounts as $key => $cityCount) {
            $ratios[$key] = (float) ($cityCount['Female'] ?? null) / (($cityCount['Male'] ?? null) + ($cityCount['Female'] ?? null));
        }

        arsort($ratios);

        echo '<table>';
        foreach ($ratios as $key => $ratio) {
            if (isset($cityCounts[$key]['Female']) && $cityCounts[$key]['Female'] < 100) {
                continue;
            } // ignore if not enough data
            echo "<tr><td>$key<td>" . round($ratio * 100) . '%<td>' . ($cityCounts[$key]['Female'] ?? null) . ' female to ' . ($cityCounts[$key]['Male'] ?? null) . ' male';
        }
        echo '</table>';
    }

    // * What country has the cheapest hostels?

    private function countryWithCheapestHostels(): void
    {
        $priceHistoryByListing = PriceHistory::where('roomType', 'dorm')->where('dataPointsInAverage', '>', 1)
            ->where('month', '>', '2016-01-01') // ->where('month', '<', '2016-01-01')
            ->where('averagePricePerNight', '<', 50) // remove weird outliers
            ->select('averagePricePerNight', 'listingID')->limit(50000)->get();
        $listingIDs = array_unique($priceHistoryByListing->pluck('listingID')->toArray());
        $listingCities = Listing::whereIn('id', $listingIDs)->select('country', 'city', 'id')
            // ->whereIn('continent', ['Western Europe', 'UK & Ireland' /*, 'Eastern Europe & Russia' */ ])
            ->whereIn('continent', ['Eastern Europe & Russia'])
            ->get();

        $listingIDToCity = [];
        foreach ($listingCities as $listingCity) {
            $listingIDToCity[$listingCity->id] = "$listingCity->country";
        }

        $cityCounts = $cityTotals = [];

        foreach ($priceHistoryByListing as $price) {
            $listingCity = $listingIDToCity[$price->listingID] ?? null;
            if (! $listingCity) {
                continue;
            }
            $cityCounts[$listingCity] = ($cityCounts[$listingCity] ?? null) + 1;
            $cityTotals[$listingCity] = ($cityTotals[$listingCity] ?? null) + $price->averagePricePerNight;
        }

        // convert counts/totals to averages
        $averages = [];
        foreach ($cityCounts as $key => $cityCount) {
            $averages[$key] = $cityTotals[$key] / $cityCount;
        }

        asort($averages);

        $tsvData = [];
        foreach ($averages as $key => $average) {
            if ($cityCounts[$key] < 10) {
                continue;
            } // ignore if not enough data
            echo "- $key (" . Currencies::format($average, 'USD') . ')<br>'; // ."<td>".$cityCounts[$key]." prices";
            $tsvData[] = [$key, Currencies::format($average, 'USD')];
        }

        $this->tsv(['Country', 'Average USD$ Dorm Price'], $tsvData);
    }

    // * What city has the most hostels?

    private function cityWithTheMostHostels(): void
    {
        $results = Listing::areLive()->where('propertyType', 'Hostel')->groupBy('country', 'city')->select('city', 'region', 'country', DB::raw('count(*) as count'))
            ->whereIn('continent', ['Western Europe', 'UK & Ireland', 'Eastern Europe & Russia'])
            //->where('country', 'USA')
            ->get()
            ->sortBy('count', SORT_REGULAR, true)->take(20);

        foreach ($results as $result) {
            echo $this->cityLink($result->cityInfo) . " ($result->count)<br>";
        }
    }

    // * What country has the most hostels?

    private function countryWithTheMostHostels()
    {
        $country = Listing::areLive()->where('propertyType', 'Hostel')->groupBy('country')->select('country', DB::raw('count(*) as count'))->get()
            ->sortBy('count', SORT_REGULAR, true)->first();

        return "$country->country with $country->count";
    }

    /* Used for http://travel.stackexchange.com/questions/16462/why-are-moroccan-hi-hostels-not-listed-on-hostel-sites/54319#54319 */

    private function percentOnBookingSites(): void
    {
        $totalCount = $totalBookable = 0;
        $hwTotal = $hbTotal = 0;
        $countries = Listing::areLive()->where('propertyType', 'hostel')->groupBy('country')->pluck('country');
        foreach ($countries as $country) {
            $listings = Listing::areLive()->where('propertyType', 'hostel')->where('country', $country)->get();
            $count = $bookable = 0;
            foreach ($listings as $listing) {
                $count++;
                $totalCount++;
                if (! $listing->activeImporteds->where('system', 'BookHostels')->isEmpty()) {
                    $hwTotal++;
                }
                if (! $listing->activeImporteds->where('system', 'Hostelbookers')->isEmpty()) {
                    $hbTotal++;
                }
                if (! $listing->activeImporteds->where('system', 'BookHostels')->isEmpty() ||
                    ! $listing->activeImporteds->where('system', 'Hostelbookers')->isEmpty()) {
                    $bookable++;
                    $totalBookable++;
                }
            }
            if (! $count) {
                continue;
            }
            $percent = round(100 * $bookable / $count);
            echo "**$country** ($percent%), ";
        }

        $percent = round(100 * $totalBookable / $totalCount);
        echo "**TOTAL** ($percent%)";
        $percent = round(100 * $hwTotal / $totalCount);
        echo "**hwTotal** ($percent%)";
        $percent = round(100 * $hbTotal / $totalCount);
        echo "**hbTotal** ($percent%)";
    }

    /* Used for http://travel.stackexchange.com/questions/16462/why-are-moroccan-hi-hostels-not-listed-on-hostel-sites/54319#54319 */

    private function hiHostelCountries(): void
    {
        $totalCount = $totalBookable = 0;
        $hiCountries = Imported::where('system', 'HI')->where('status', 'active')->groupBy('country')->pluck('country');
        foreach ($hiCountries as $country) {
            $importeds = Imported::where('system', 'HI')->where('status', 'active')->where('country', $country)->get();
            $count = $bookable = 0;
            foreach ($importeds as $imported) {
                if (! $imported->listing || ! $imported->listing->isLive()) {
                    continue;
                }
                $count++;
                $totalCount++;
                if (! $imported->listing->activeImporteds->where('system', 'BookHostels')->isEmpty() ||
                    ! $imported->listing->activeImporteds->where('system', 'Hostelbookers')->isEmpty()) {
                    $bookable++;
                    $totalBookable++;
                }
            }
            if (! $count) {
                continue;
            }
            $percent = round(100 * $bookable / $count);
            echo "**$country** ($percent%), ";
        }
        $percent = round(100 * $totalBookable / $totalCount);
        echo "**TOTAL** ($percent%)";
    }

    // * What percent of hostels require you to bring your own sheets?
    /* Used for http://travel.stackexchange.com/questions/54213/why-do-hostels-require-you-to-rent-bedding/54234#54234 */

    private function sheetsStats(): void
    {
        $thing = 'towels';
        $features = Listing::areLive()->where('propertyType', 'hostel')->select('compiledFeatures', 'id')->where('compiledFeatures', 'LIKE', "%$thing%")->get();

        $counts = [];
        foreach ($features as $feature) {
            //echo "$feature->id ".$feature->compiledFeatures[$thing].'<br>';
            if (! isset($counts[$feature->compiledFeatures[$thing]])) {
                $counts[$feature->compiledFeatures[$thing]] = 0;
            }
            $counts[$feature->compiledFeatures[$thing]]++;
        }
        echo 'total:' . array_sum($counts);
    }

    private function priceHistory()
    {
        $listing = Listing::find(1);

        //return $listing->priceHistory();
        return PriceHistory::where('listingID', 1)->where('roomType', 'dorm')->where('month', '>', Carbon::now()->subMonths(6))->where('month', '<', Carbon::now()->addMonths(6))->get();
    }

    /* Useful functions */

    private function showGraph($data) // to do... not yet working
    {
        return view('staff/data-mining');
    }

    private function listingsListMarkup($listings, $showField = null): void
    {
        foreach ($listings as $listing) {
            $url = $listing->getURL('publicSite', null, true);
            echo "-  [$listing->name](<a href=\"$url\">$url</a>) ($listing->city, $listing->country)";
            if ($showField !== null) {
                echo ' (' . $listing->$showField . ')';
            }
            echo '<br>';
        }
    }

    private function listingsListText($listings, $showField = null): void
    {
        foreach ($listings as $listing) {
            $url = $listing->getURL('publicSite', null, true);
            echo "-  $listing->name (<a href=\"$url\">$url</a>) ($listing->city, $listing->country)";
            if ($showField !== null) {
                echo ' (' . $listing->$showField . ')';
            }
            echo '<br>';
        }
    }

    private function cityLink($cityInfo)
    {
        if (! $cityInfo) {
            return '(null) ';
        }
        $url = $cityInfo->getURL('publicSite', null, true);

        return "[$cityInfo->city, $cityInfo->country](<a href=\"$url\">$url</a>)";
    }

    // View as source, paste into Google Docs spreadsheet (Edit -> Past Special -> Values Only", generate graph, save graph or publish
    private function tsv($columns, $data): void
    {
        echo implode("\t", $columns) . "\n";
        foreach ($data as $dataLine) {
            echo implode("\t", $dataLine) . "\n";
        }
    }
}
