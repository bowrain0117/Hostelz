<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CityInfo;
use App\Models\IncomingLink;
use App\Models\Listing\Listing;
use App\Models\User;
use Response;

class AjaxDataQueryHandler
{
    public static function handleUserSearch($idSearch, $searchString = null, $baseQuery = null, $alsoApplyBaseQueryToIdFind = false)
    {
        if ($idSearch) {
            if (! $alsoApplyBaseQueryToIdFind || ! $baseQuery) {
                $baseQuery = User::query();
            }
            $results = $baseQuery->where('id', $idSearch)->get();
        } else {
            if ($searchString === null) {
                return null;
            } // wasn't a search, nothing to handle
            $searchString = trim($searchString);

            if (! $baseQuery) {
                $baseQuery = User::query();
            }
            $results = $baseQuery->where(function ($query) use ($searchString): void {
                if (is_numeric($searchString)) {
                    $query->where('id', $searchString);
                } else {
                    $query->where('username', 'like', "$searchString%")->orWhere('name', 'like', "$searchString%");
                }
            })->orderBy('id', 'desc')->select('id', 'username', 'name')->limit(30)->get();
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = [
                'id' => $result->id,
                'text' => $result->username .
                    ($result->name != '' ? " ($result->name)" : ''),
            ];
        }

        $return[] = ['id' => 0, 'text' => '(none)'];
        if ($idSearch) {
            $return = $return[0];
        }

        return Response::json(['results' => $return]);
    }

    public static function handleListingSearch($idSearch, $searchString = null, $baseQuery = null, $alsoApplyBaseQueryToIdFind = false)
    {
        if ($idSearch) {
            if (! $alsoApplyBaseQueryToIdFind || ! $baseQuery) {
                $baseQuery = Listing::query();
            }
            $results = $baseQuery->where('id', $idSearch)->get();
        } else {
            if ($searchString === null) {
                return null;
            } // wasn't a search, nothing to handle
            $searchString = trim($searchString);

            if (! $baseQuery) {
                $baseQuery = Listing::areNotListingCorrection();
            }
            $results = $baseQuery->where(function ($query) use ($searchString): void {
                if (is_numeric($searchString)) {
                    $query->where('id', $searchString);
                } else {
                    $query->where('name', 'like', "$searchString%")->orWhere('city', 'like', "$searchString%");
                }
            })->orderBy('name', 'desc')->select('id', 'name', 'city', 'country')->limit(40)->get();
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = [
                'id' => $result->id,
                'text' => "[$result->id] " . $result->fullDisplayName(),
            ];
        }

        $return[] = ['id' => 0, 'text' => '(none)'];
        if ($idSearch) {
            $return = $return[0];
        }

        return Response::json(['results' => $return]);
    }

    public static function handleFavoriteHostelsSearch($idSearch, $searchString = null, $baseQuery = null, $alsoApplyBaseQueryToIdFind = false)
    {
        if ($idSearch) {
            if (! $alsoApplyBaseQueryToIdFind || ! $baseQuery) {
                $baseQuery = Listing::query();
            }
            $results = $baseQuery->where('id', $idSearch)->get();
        } else {
            if ($searchString === null) {
                return null;
            } // wasn't a search, nothing to handle
            $searchString = trim($searchString);

            if (! $baseQuery) {
                $baseQuery = Listing::areLive();
            }
            $results = $baseQuery->where(function ($query) use ($searchString): void {
                if (is_numeric($searchString)) {
                    $query->where('id', $searchString);
                } else {
                    $query->where('name', 'like', "$searchString%")->orWhere('city', 'like', "$searchString%");
                }
            })->orderBy('name', 'desc')->select('id', 'name', 'city', 'country')->limit(40)->get();
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = [
                'id' => $result->id,
                'text' => $result->fullDisplayName(),
            ];
        }

        $return[] = ['id' => 0, 'text' => '(none)'];
        if ($idSearch) {
            $return = $return[0];
        }

        return Response::json(['results' => $return]);
    }

    public static function handleIncomingLinkSearch($idSearch, $searchString = null, $baseQuery = null, $alsoApplyBaseQueryToIdFind = false)
    {
        if ($idSearch) {
            if (! $alsoApplyBaseQueryToIdFind || ! $baseQuery) {
                $baseQuery = IncomingLink::query();
            }
            $results = $baseQuery->where('id', $idSearch)->get();
        } else {
            if ($searchString === null) {
                return null;
            } // wasn't a search, nothing to handle
            $searchString = trim($searchString);

            if (! $baseQuery) {
                $baseQuery = IncomingLink::query();
            }
            $results = $baseQuery->where(function ($query) use ($searchString): void {
                if (is_numeric($searchString)) {
                    $query->where('id', $searchString);
                } else {
                    $query->where('url', 'like', "%$searchString%");
                }
            })->orderBy('id', 'desc')->select('id', 'url', 'contactStatus')->limit(30)->get();
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = [
                'id' => $result->id,
                'text' => "$result->url ($result->contactStatus)",
            ];
        }

        $return[] = ['id' => 0, 'text' => '(none)'];
        if ($idSearch) {
            $return = $return[0];
        }

        return Response::json(['results' => $return]);
    }

    public static function handleBookingSearch($idSearch, $searchString = null, $baseQuery = null, $alsoApplyBaseQueryToIdFind = false)
    {
        if ($idSearch) {
            if (! $alsoApplyBaseQueryToIdFind || ! $baseQuery) {
                $baseQuery = Booking::query();
            }
            $results = $baseQuery->where('id', $idSearch)->get();
        } else {
            if ($searchString === null) {
                return null;
            } // wasn't a search, nothing to handle
            $searchString = trim($searchString);

            if (! $baseQuery) {
                $baseQuery = Booking::query();
            }
            $results = $baseQuery->where(function ($query) use ($searchString): void {
                if (is_numeric($searchString)) {
                    $query->where('id', $searchString);
                } else {
                    $query->where('bookingID', $searchString)->orWhere('lastName', 'like', "$searchString%");
                }
            })->orderBy('id', 'desc')->limit(30)->get();
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = [
                'id' => $result->id,
                'text' => $result->getBookingIdDisplayString() .
                    ($result->listing ? ' at ' . $result->listing->name : ''),
            ];
        }

        $return[] = ['id' => 0, 'text' => '(none)'];
        if ($idSearch) {
            $return = $return[0];
        }

        return Response::json(['results' => $return]);
    }

    public static function handleCityInfoSearch($idSearch, $searchString = null, $baseQuery = null, $alsoApplyBaseQueryToIdFind = false)
    {
        if ($idSearch) {
            if (! $alsoApplyBaseQueryToIdFind || ! $baseQuery) {
                $baseQuery = CityInfo::query();
            }
            $results = $baseQuery->where('id', $idSearch)->get();
        } else {
            if ($searchString === null) {
                return null;
            } // wasn't a search, nothing to handle
            $searchString = trim($searchString);

            if (! $baseQuery) {
                $baseQuery = CityInfo::query();
            }
            $results = $baseQuery->where(function ($query) use ($searchString): void {
                if (is_numeric($searchString)) {
                    $query->where('id', $searchString);
                } else {
                    $query->where('city', 'like', "$searchString%")->orWhere('cityAlt', 'like', "$searchString%");
                }
            })->orderBy('id', 'desc')->limit(30)->get();
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = [
                'id' => $result->id,
                'text' => $result->fullDisplayName(),
            ];
        }

        $return[] = ['id' => 0, 'text' => '(none)'];
        if ($idSearch) {
            $return = $return[0];
        }

        return Response::json(['results' => $return]);
    }
}
