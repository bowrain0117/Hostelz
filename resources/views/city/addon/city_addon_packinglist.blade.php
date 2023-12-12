<div class="packing-list mb-3 mb-lg-5 pb-3 pb-lg-5 border-bottom ">
    <h2 class="sb-title cl-text mb-0 d-none d-lg-block" id="packinglist">{!! langGet('city.PackingListTitle', [ 'city' => $cityInfo->translation()->city]) !!}</h2>

    <p class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed" data-toggle="collapse" href="#packinglist-content">
        {!! langGet('city.PackingListTitle', [ 'city' => $cityInfo->translation()->city]) !!}
        <i class="fas fa-angle-down float-right"></i>
        <i class="fas fa-angle-up float-right"></i>
    </p>

    <div class="text-content mt-3 collapse d-lg-block" id="packinglist-content">

        <img src="{!! routeURL('images', 'packing-list-hostels-backpacking.jpg') !!}" alt="{!! langGet('city.PackingListTitle', [ 'city' => $cityInfo->translation()->city]) !!}" title="{!! langGet('city.PackingListTitle', [ 'city' => $cityInfo->translation()->city]) !!}" class="w-100 mb-3">

        <p class="tx-small">{!! langGet('city.PackingListSubTitle') !!}</p>

        <p class="">{!! langGet('city.PackingListText1', [ 'city' => $cityInfo->translation()->city, 'area' => $cityInfo->translation()->country]) !!}</p>

        <p>{!! langGet('city.PackingListText2', [ 'city' => $cityInfo->translation()->city, 'area' => $cityInfo->translation()->country]) !!}</p>

        <ul>
            <li><a href="https://amzn.to/345SqsM" title="Padlock" target="_blank">Padlock</a></li>
            <li><a href="https://amzn.to/2Pd2NqA" title="Earplugs" target="_blank">Earplugs</a></li>
            <li><a href="https://amzn.to/38fJELn" title="Sleeping Mask" target="_blank">Sleeping Mask</a></li>
            <li><a href="https://amzn.to/2LHU3pZ" title="Quick dry Towel" target="_blank">Quick Dry Travel Towel</a></li>
            <li><a href="https://amzn.to/348An57" title="Head Lamp" target="_blank">Head Lamp</a></li>
        </ul>

        <p>{!! langGet('city.PackingListText3') !!}</p>

        <a href="{!! routeURL('articles', 'what-to-pack') !!}" class="btn btn-primary" title="{!! langGet('city.PackingListTitle', [ 'city' => $cityInfo->translation()->city]) !!}" target="_blank">{!! langGet('city.PackingListButton') !!}</a>

    </div>

</div>
