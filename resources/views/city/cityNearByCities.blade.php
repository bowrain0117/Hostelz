<?php
use App\Models\CityInfo;
use Lib\GeoMath;
?>

@if ($cityInfo->nearbyCities)
    <div class="mb-3 mb-lg-5 border-bottom pb-3 border-bottom-lg-0">
        <h2 class="sb-title cl-text mb-0 d-none d-lg-block">@langGet('city.OtherNearbyCities')</h2>

        <p class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed" data-toggle="collapse" href="#nearbyCities-content">
            {!! langGet('city.OtherNearbyCities') !!}
            <i class="fas fa-angle-down float-right"></i>
            <i class="fas fa-angle-up float-right"></i>
        </p>

        <div class="mt-3 collapse d-lg-block" id="nearbyCities-content">
            @foreach ($cityInfo->nearbyCities as $nearbyCity)
                <?php $otherCity = CityInfo::find($nearbyCity['cityID']); ?>
                @if ($otherCity)
                    <div class="mb-3 mb-lg-5">
                        <div class="card border-0 shadow rounded-lg bg-light">
                            <div class="card-body p-2 p-lg-3">
                                <div class="d-flex align-items-center justify-content-start">
                                    <span class="mr-3" style="fill: #4A5268;">@include('partials.svg-icon', ['svg_id' => 'search-icon', 'svg_w' => '24', 'svg_h' => '24'])</span>
                                    <span class="tx-body cl-text font-weight-bold mr-3"><a href="{!! $otherCity->getURL() !!}" class="font-weight-bold text-uppercase" title="Hostels in {{{ $otherCity->translation()->city }}}">{{{ $otherCity->translation()->city }}}</a></span>
                                    <span class="tx-small cl-subtext ml-auto">({!! round($nearbyCity['km'], $nearbyCity['km'] < 1) !!} Km / {!! GeoMath::kmToMiles($nearbyCity['km'], true) !!} mi.)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif