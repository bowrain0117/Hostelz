@if (isset($distancesToPoiInKm[$listing->id]))
    <div class="py-2 px-2 px-md-3 bg-gray-100 rounded mt-2 mb-3">
        <span class="font-weight-600 display-4"><i class="fa fas fa-map-marker-alt w-1rem"></i> @langGet('city.DistanceToPOI', [ 'landmark' => $selectedPoiInfo['name'], 'kmDistance' => round($distancesToPoiInKm[$listing->id], 1), 'miDistance' => Lib\GeoMath::kmToMiles($distancesToPoiInKm[$listing->id], 1) ])</span>
    </div>
@endif