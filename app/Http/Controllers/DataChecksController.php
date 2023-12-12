<?php

namespace App\Http\Controllers;

use App\Models\AttachedText;
use App\Models\CityInfo;
use App\Models\Listing\Listing;
use App\Models\Review;
use DB;
use Exception;
use Lib\GeoBounds;
use Lib\GeoPoint;

/*
To do:
- $0 commission bookings
- gnCityID = 0 or country, w/ mapCRC != 0
- check the bayesianFilter totals
- neighborhoods that have different capitalizations in the same city
*/

class DataChecksController extends Controller
{
    public function runChecks()
    {
        $output = '';

        /* to do (the current query doesn't really do what we're trying to do)
        $output .= "Countries with CityInfos with multiple Geonames Country IDs: ";
                $cityInfos = CityInfo::select(DB::raw('country, count(*) as count'))->areLive()->where('gnCountryID', '!=', 0)->groupBy('country', 'gnCountryID')->having('count', '>', 1)->get();
                foreach ($cityInfos as $cityInfo) {
                    $output .= "$cityInfo->country ";
                    continue;
                    $countryIDs = $countryInfo->cityInfos()->where('gnCountryID', '!=', 0)->groupBy('gnCountryID')->pluck('gnCountryID');
                    if ($countryIDs->isEmpty()) {
                        continue; // may just not have any hostels in the country
                    } elseif ($countryIDs->count() > 1) {
                        $countries = [ ];
                        foreach ($countryIDs as $countryID) {
                            $country = Geonames::getCountryByGeonamesID($countryID);
                            if (!$country)
                                $countries[] = "[$countryID not a country]";
                            else
                                $countries[] = "[$countryID $country->country]";
                        }
                        logWarning("$countryInfo->country has multiple gnCountryIDs in its CityInfos (".implode(', ', $countries).").");
                        $output .= "(multiple: ".implode(', ', $countries).") ";
                    }
                }
                $output .= "\n";
                return $output;
        */

        /*
        $output .= "\nLanguage Detection Training Data:\n";

        foreach (Languages::allLive() as $languageCode => $language) {
            BayesianFilter::knownTokensCount();
        }
        */

        $output .= "\nFormatting issues in edited reviews: ";

        $reviews = Review::where('status', 'publishedReview')
            ->where(function ($query): void {
                $query->where('editedReview', 'LIKE', "%\r\n\r\n\r\n%")// 3 returns in a row
                    ->orWhereRaw('REPLACE(editedReview, "\r\n\r\n", "") LIKE "%\n%"'); // A single return (all should be double returns)
            })->get();

        $output .= '(' . $reviews->count() . ' total) ';

        foreach ($reviews as $review) {
            $output .= '<a href="' . routeURL('staff-reviews', $review->id) . "\">$review->id ($review->reviewDate)</a> ";
        }

        $output .= "\n";

        $output .= "\nCount of word 'hostel' in city/region/country descriptions:\n";

        $attachedTexts = AttachedText::where('status', 'ok')->whereIn('subjectType', ['cityInfo', 'countryInfo'])
            ->where('type', 'description')->where('language', 'en')->get();
        foreach ($attachedTexts as $attachedText) {
            $occurrances = substr_count(strtolower($attachedText->data), strtolower('hostel'));
            if ($occurrances < 3 || $occurrances >= 8) {
                $output .= '  <a href="' . routeURL('staff-attachedTexts', $attachedText->id) . '">' . $attachedText->nameOfSubject() . " ($occurrances)</a>\n";
            }
        }

        $output .= "\nMisc city checks:\n";

        $cityInfos = CityInfo::areLive()->orderBy('id')->get();
        foreach ($cityInfos as $cityInfo) {
            // * Check for duplicate URLs *

            $url = $cityInfo->getURL('relative', 'en');
            $parts = explode('/', trim($url, '/'));
            if (count($parts) == 3) {
                $region = '';
                list($ignore, $country, $city) = $parts;
            } else {
                list($ignore, $country, $region, $city) = $parts;
            }
            $matches = CityInfo::areLive()->fromUrlParts($country, $region, $city)
                ->where('id', '>=', $cityInfo->id)->get(); // (so we don't list the same pairs more than once)
            if ($matches->count() > 1) {
                $output .= '  Multiple cities with same URL: ' . $this->cityLinks($matches) . "\n";
            }

            // * Cities with latitude/longitude far from their listings' average *

            $listingLocations = Listing::select('latitude', 'longitude')->byCityInfo($cityInfo)->areLive()->haveLatitudeAndLongitude()->get();
            if ($listingLocations->isEmpty()) {
                continue;
            }
            $cityPoint = new GeoPoint($cityInfo);
            $listingsBounds = GeoBounds::makeFromPoints($listingLocations);
            $listingsCenter = $listingsBounds->centerPoint();
            if (! $listingsBounds->containsPoint($cityPoint) && $cityPoint->distanceToPoint($listingsCenter, 'miles') > 60) {
                $output .= "  Latitude/longitude far from their listings' average: " . $this->cityLink($cityInfo) . "\n";
            }
        }

        $cityInfos = CityInfo::areLive()->whereIn('country', CityInfo::$countriesWithRegionInTheCityPageURL)->where('region', '')->get();
        if (! $cityInfos->isEmpty()) {
            $output .= "\nRegion missing in countries with region in the city URLs: " . $this->cityLinks($cityInfos) . "\n";
        }

        $output .= "\nCountries that couldn't be found in Geonames:\n";

        $cityInfos = CityInfo::areLive()->where('gnCountryID', 0)->groupBy('country')->get();
        foreach ($cityInfos as $cityInfo) {
            $cityInfo->updateGeocoding(); // try updating it in case it just wasn't updated yet
            if (! $cityInfo->gnCountryID) {
                $output .= '  <a href="' . routeURL('staff-cityInfos', $cityInfo->id) . "\">$cityInfo->country</a>\n";
            }
        }

        $output .= "\nRegions that couldn't be found in Geonames:\n";

        $cityInfos = CityInfo::areLive()->where('gnRegionID', 0)->where('region', '!=', '')->where('gnCountryID', '!=', 0)->groupBy('country', 'region')->get();
        foreach ($cityInfos as $cityInfo) {
            $cityInfo->updateGeocoding(); // try updating it in case it just wasn't updated yet
            if (! $cityInfo->gnRegionID) {
                $output .= '  <a href="' . routeURL('staff-cityInfos', $cityInfo->id) . "\">$cityInfo->region, $cityInfo->country</a>\n";
            }
        }

        $output .= "\nCities that couldn't be found in Geonames:\n";

        $cityInfos = CityInfo::areLive()->where('gnCityID', 0)->where('gnCountryID', '!=', 0)->groupBy('country', 'city')->get();
        foreach ($cityInfos as $cityInfo) {
            $cityInfo->updateGeocoding(); // try updating it in case it just wasn't updated yet
            if (! $cityInfo->gnCityID) {
                $output .= '  ' . $this->cityLink($cityInfo) . "\n";
            }
        }

        $output .= "\nCities with duplicate Geonames city IDs:\n";

        $geonamesCityIDs = CityInfo::areLive()->where('gnCityID', '!=', 0)
            ->select(DB::raw('gnCityID, count(*) as count'))
            ->groupBy('gnCityID')->having('count', '>', 1)->get(); // should probably change this to use havingRaw()
        foreach ($geonamesCityIDs as $geonamesCityID) {
            $cityInfos = CityInfo::areLive()->where('gnCityID', $geonamesCityID->gnCityID)->get();
            $output .= '  ' . $this->cityLinks($cityInfos) . "\n";
        }

        return view('staff/index')->with('message', '<pre>' . $output . '</pre>');

        $output .= "\nCorrect Listing City/Region/Country Capitalization:\n";

        // 'binary' is used for group by because we want all capitalizations separately because this code fixes capitalization too
        $listingCities = Listing::areLive()->select('id', 'city', 'cityAlt', 'region', 'country', 'zipcode')
            ->groupBy(DB::raw('BINARY city, BINARY region, BINARY country'))->get()->toArray();

        foreach ($listingCities as $listingCity) {
            if ($listingCity['city'] == '') {
                throw new Exception("Listing $listingCity[id] has no city!");
            }
            if ($listingCity['country'] == '') {
                throw new Exception("Listing $listingCity[id] has no country!");
            }

            // (This is a non-binary search, so it also finds cities that may be capitalized differently.)
            $cityInfos = self::where('city', $listingCity['city'])->where('region', $listingCity['region'])->where('country', $listingCity['country'])->get();

            if ($cityInfos->count() > 1) {
                $output .= "  There are multiple duplicate cityInfo rows matching $listingCity[city], $listingCity[country]!\n";
            } elseif ($cityInfos->count() == 0) {
                // try it without the region specified...
                $try2 = self::where('city', $listingCity['city'])->where('country', $listingCity['country'])->get();
                if (! $try2->isEmpty()) {
                    $cityInfo = $try2->first();
                    $output .= "  City '$listingCity[city]' in multiple regions or region mismatch: CityInfo: region '$cityInfo->region' and Listing: region '$listingCity[region]'.\n";
                    /*
    				(<a href=\"http://en.wikipedia.org/wiki/Special:Search?search=".urlencode($cityInfo->city'])."\">wikipedia</a>)
    				(<a href=\"/admin/index.php?action=query&s=".urlencode("INSERT INTO cityInfo (country,region,city,postalCode) VALUES ('".dbEscape($listingCity['country'])."','".dbEscape($listingCity['region'])."','".dbEscape($listingCity['city'])."','".dbEscape($listingCity['zipcode'])."')")."\">create new city</a>)";
    				*/
                    continue;
                }
            } elseif (count($cityInfos) == 1) {
                // Fix capitalization differences in hostels using the cityInfo
                $cityInfo = $cityInfos->first();
                if ($cityInfo->city == '') {
                    throw new Exception("CityInfo $cityInfo->id has no city!");
                }
                if ($cityInfo->country == '') {
                    throw new Exception("CityInfo $cityInfo->id has no country!");
                }

                /*
    			if (strcmp($listingCity['city'] != $cityInfo->city)
    				echo "<br>$listingCity[country]: City <a href=\"/staff/editCityInfo.php?qfUpdate=1&rememberRenaming=1&w[id]=$cityInfo[id]&d[city]=".urlencode($listingCity['city'])."\">$listingCity[city]</a> or <a href=\"/admin/editCorrectionData.php?qfInsert=Insert&d[dbTable]=hostels&d[dbField]=city&d[oldValue]=".urlencode($listingCity['city'])."&d[newValue]=".urlencode($cityInfo->city)."\">$cityInfo[city]</a>
    			for hostel <a href=/staff/editListings.php?m=d&w[id]=$listingCity[id]>$listingCity[id]</a>
    			(<a href=\"http://en.wikipedia.org/wiki/Special:Search?search=".urlencode($cityInfo->city)."\">wikipedia</a>)";

    			if ($listingCity['region'] != '' && $cityInfo->region!='' && strcmp($listingCity['region'],$cityInfo->region))
    				echo "<br>$listingCity[country]: Region <a href=\"/admin/editCorrectionData.php?qfInsert=Insert&d[dbTable]=hostels&d[dbField]=region&d[oldValue]=".urlencode($cityInfo->region)."&d[newValue]=".urlencode($listingCity['region'])."\">$listingCity[region]</a> or <a href=\"/admin/editCorrectionData.php?qfInsert=Insert&d[dbTable]=hostels&d[dbField]=region&d[oldValue]=".urlencode($listingCity['region'])."&d[newValue]=".urlencode($cityInfo->region)."\">$cityInfo[region]</a>
    			for hostel <a href=/staff/editListings.php?m=d&w[id]=$listingCity[id]>$listingCity[id]</a>
    			(<a href=\"http://en.wikipedia.org/wiki/Special:Search?search=".urlencode($cityInfo->region)."\">wikipedia</a>)";

    			if (strcmp($listingCity['country'],$cityInfo->country))
    				echo "<br>Country <a href=\"/admin/editCorrectionData.php?qfInsert=Insert&d[dbTable]=hostels&d[dbField]=country&d[oldValue]=".urlencode($cityInfo->country)."&d[newValue]=".urlencode($listingCity['country'])."\">$listingCity[country]</a> or <a href=\"/admin/editCorrectionData.php?qfInsert=Insert&d[dbTable]=hostels&d[dbField]=country&d[oldValue]=".urlencode($listingCity['country'])."&d[newValue]=".urlencode($cityInfo->country)."\">$cityInfo[country]</a>
    			for hostel <a href=/staff/editListings.php?m=d&w[id]=$listingCity[id]>$listingCity[id]</a>
    			(<a href=\"http://en.wikipedia.org/wiki/Special:Search?search=".urlencode($cityInfo->country)."\">wikipedia</a>)";
    			*/
            }
        }

        /*
    	taskTitle("Unapproved Hostels with onlineReservations");
    	$rows = dbGetAll("SELECT hostels.id,hostels.name,hostels.verified FROM hostels WHERE onlineReservations=1 AND hostels.verified<0 AND hostels.verified!=".$ListingStatuses['imported']." ORDER BY hostels.verified"); // AND hostels.verified!=".$ListingStatuses['removed']."

    	foreach($rows as $row) {
    		echo "<a href=\"/staff/editListings.php?m=d&w[id]=$row[id]\">$row[id]</a> - $row[name] ($row[verified])<br>";
    	}
    */

        /*
            taskTitle("Find cityAlts names that match a city");

            $cities = dbGetAll("SELECT DISTINCT(hostels.cityAlt) as cityAlt,cityInfo.city as city,cityInfo.country from hostels,cityInfo where hostels.country=cityInfo.country and hostels.cityAlt=cityInfo.city");

            if($cities) {
                taskTitle("CityAlts Matching a City");
                foreach($cities as $c) {
                    if($c['city'] != $c['cityAlt']) continue; // ignore when the db matches other foreign characters, etc.
                    echo "<a href=\"/staff/editListings.php?m=l&w[cityAlt]=$c[cityAlt]&w[country]=$c[counry]\">$c[cityAlt]</a> matching <a href=\"/staff/editListings.php?m=l&w[city]=$c[city]&w[country]=$c[counry]\">$c[city]</a><br>";
                }
            }

            // Find city names that match a cityAlt
            taskTitle("Find city names that match a cityAlt");

            $cities = dbGetAll("SELECT DISTINCT(hostels.city) as city,cityInfo.cityAlt as cityAlt from hostels,cityInfo where hostels.country=cityInfo.country and hostels.city=cityInfo.cityAlt GROUP BY city,cityAlt");

            if($cities) {
                taskTitle("City Matching a CityAlt");
                foreach($cities as $c) {
                    if($c['city'] != $c['cityAlt']) continue; // ignore when the db matches other foreign characters, etc.
                    echo "<a href=\"/staff/editListings.php?m=l&w[city]=$c[city]\">$c[city]</a> matching <a href=\"editCityInfo.php?m=l&w[cityAlt]=$c[cityAlt]\">$c[cityAlt]</a><br>";
                }
            }
        */

        return view('staff/index')->with('message', $output);
    }

    private function cityLink($cityInfo)
    {
        return '<a href="' . routeURL('staff-cityInfos', $cityInfo->id) . '">' . $cityInfo->fullDisplayName() . '</a>';
    }

    private function cityLinks($cityInfos)
    {
        $return = '';
        foreach ($cityInfos as $cityInfo) {
            $return .= '[' . $this->cityLink($cityInfo) . '] ';
        }

        return $return;
    }
}
