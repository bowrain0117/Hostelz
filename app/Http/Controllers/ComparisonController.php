<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comparison\ComparisonRequest;
use App\Models\Comparison;
use App\Models\Listing\Listing;
use App\Services\ComparisonService;
use App\Services\Listings\ListingsPoiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ComparisonController extends Controller
{
    public function __construct(
        private ComparisonService $comparisonService,
        private ListingsPoiService $listingsPoiService,
    ) {
    }

    public function index()
    {
        if (Auth::check()) {
            $comparisons = $this->getUserComparisons();

            if ($comparisons->isEmpty()) {
                return view('comparison.comparisonDefault');
            }

            $params = $comparisons->pluck('id')->implode('+');

            return redirect()->route('comparison.show', $params);
        }

        if (! $this->isSessionExist()) {
            return view('comparison.comparisonDefault');
        }

        $params = implode('+', $this->getCompareItems());

        return redirect()->route('comparison.show', $params);
    }

    public function show(ComparisonRequest $request)
    {
        $_listings = Listing::whereIn('id', $request->validated()['listingsId'])->areLive()->get()->keyBy('id');

        $pois = $this->listingsPoiService->getMapPoi(
            $_listings,
            fn ($item) => [$item->latitude, $item->longitude, $item->id, $item->name, $item->city, $item->country]
        );

        $listings = $this->comparisonService->getListingsWithFeaturesData($_listings);

        $listingsFeatures = $this->comparisonService->getFeatures();

        return view('comparison.comparison', compact('listings', 'listingsFeatures', 'pois'));
    }

    public function update(int $listingId): JsonResponse
    {
        if (Auth::check()) {
            if ($this->getUserComparisons()->count() === Comparison::MAX_COMPARE_ITEMS) {
                return response()->json([
                    'count' => Comparison::MORE_THAN_MAX_COMPARE_ITEMS,
                ]);
            }

            Comparison::updateOrCreate([
                'user_id' => Auth::id(),
                'listing_id' => $listingId,
            ]);

            return response()->json([
                'count' => $this->getUserComparisons()->count(),
            ]);
        }

        if (! $this->isSessionExist() || ! $this->isListingExistInSession($listingId)) {
            if ($this->getCompareSessionItemsCount() === Comparison::MAX_COMPARE_ITEMS) {
                return response()->json([
                    'count' => Comparison::MORE_THAN_MAX_COMPARE_ITEMS,
                ]);
            }

            session()->push(Comparison::SESSION_COMPARE_KEY, $listingId);
        }

        return response()->json([
            'count' => $this->getCompareSessionItemsCount(),
        ]);
    }

    public function destroy(int $listingId): JsonResponse
    {
        if (Auth::check()) {
            Comparison::where('user_id', Auth::id())->where('listing_id', $listingId)->delete();

            $comparisons = $this->getUserComparisons();

            return response()->json([
                'href' => $this->getUrlToRedirect($comparisons),
                'count' => $this->getUserComparisonsCount($comparisons),
            ]);
        }

        $deleteItemId = array_search($listingId, $this->getCompareItems(), true);
        session()->pull(Comparison::SESSION_COMPARE_KEY . '.' . $deleteItemId);

        if (empty($this->getCompareItems())) {
            session()->forget(Comparison::SESSION_COMPARE_KEY);
        }

        return response()->json([
            'href' => $this->getSessionUrlToRedirect(),
            'count' => $this->getCompareSessionItemsCount(),
        ]);
    }

    private function getUserComparisons()
    {
        return Auth::user()->comparisons()->get();
    }

    private function getUrlToRedirect(Collection $comparisons): string
    {
        if ($comparisons->isEmpty()) {
            return route('comparison');
        }

        return route('comparison.show', $comparisons->pluck('id')->implode('+'));
    }

    private function getUserComparisonsCount(Collection $comparisons): int
    {
        if ($comparisons->isEmpty()) {
            return false;
        }

        return $comparisons->count();
    }

    private function getCompareItems(): array
    {
        return session()->get(Comparison::SESSION_COMPARE_KEY, []);
    }

    private function getSessionUrlToRedirect(): string
    {
        if (! $this->isSessionExist()) {
            return route('comparison');
        }

        return implode('+', $this->getCompareItems());
    }

    private function getCompareSessionItemsCount(): int
    {
        if (! $this->isSessionExist()) {
            return false;
        }

        return count($this->getCompareItems());
    }

    private function isSessionExist(): bool
    {
        return session()->has(Comparison::SESSION_COMPARE_KEY);
    }

    private function isListingExistInSession(int $listingId): bool
    {
        return
            $this->isSessionExist() &&
            in_array($listingId, $this->getCompareItems(), true);
    }
}
