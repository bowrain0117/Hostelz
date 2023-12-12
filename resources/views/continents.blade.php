<?php
Lib\HttpAsset::requireAsset('booking-main.js');
Lib\HttpAsset::requireAsset('cities.js');
?>

@extends('layouts.default', ['returnToThisPageAfterLogin' => true, 'showHeaderSearch' => true])

@section('title', __('SeoInfo.ContinentsCitiesMetaTitle'))

@section('header')
    <meta property="og:title" content="{{ __('SeoInfo.ContinentsCitiesMetaTitle') }}"/>
    <meta name="description" content="{{ __('SeoInfo.ContinentsCitiesMetaDescription') }}">
    <meta property="og:description" content="{{ __('SeoInfo.ContinentsCitiesMetaDescription') }}"/>
@stop

@section('bodyAttributes')
    class="continents-page"
@stop

@section('content')
    <section class="pt-3 pb-5 container">
        <div class="breadcrumbs">
            <ul class="breadcrumb black" vocab="http://schema.org/" typeof="BreadcrumbList">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(__('continents.worldHostels')) !!}
            </ul>
        </div>
        <div class="mb-lg-2 pb-md-2 mx-sm-n3 mx-lg-0">
            <h1 class="hero-heading h2" id="allhostels">{{ __('continents.worldHostels') }}</h1>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <p>{{ __('continents.worldIntroText') }}</p>
                    <p class="js-show-if-not-login"><a href="#signup" data-smooth-scroll="">Sign up with Hostelz.com</a>
                        and get access to exclusive hostel content and much more.</p>
                </div>
                <div class="col-md-4">
                    <img class="w-100" src="{!! url('images', 'all-hostels-in-the-world.jpg') !!}"
                         alt="{{ __('continents.worldHostels') }}" title="{{ __('continents.worldHostels') }}">
                </div>
            </div>
        </div>
    </section>

    <section class="container mt-4 mt-sm-5 mb-7 mb-md-8 mb-lg-9">
        <div id="accordion" role="tablist" class="vue-cities-tab">

            @foreach($continentsCountries as $continent => $countries)
                @php $continentUrl = $continents[$continent]->continentInfo['urlSlug'] @endphp

                <div id="heading{{ $loop->index }}" class="card border-0 mb-3">
                    <div role="tab" class="card-header bg-light border-0 py-0">
                        <h2 class="h3"><a data-toggle="collapse" href="#{{ $continentUrl }}"
                                          aria-controls="{{ $continentUrl }}"
                                          class="accordion-link collapsed text-hover-primary"
                                          aria-expanded="false">{{ $continent }}</a></h2>
                    </div>
                    <div id="{{ $continentUrl }}" role="tabpanel" aria-labelledby="heading{{ $loop->index }}"
                         data-parent="#accordion"
                            @class(['collapse', 'show' => $loop->iteration === 1])
                    >
                        <div class="card-body py-5">

                            <div class="row align-items-center justify-content-center">
                                <div class="col-md-8">
                                    @php $description = $continentDescriptions->get($continent) @endphp

                                    <h3 class="continents-hero"
                                        data-url="{{ routeURL('staff-attachedTexts', $description->id) }}">
                                        {!! __('continents.HostelsInContinent', [
                                            'link' => routeURL('continents', $continentUrl),
                                            'continent' => $continent])
                                        !!}
                                    </h3>

                                    @if(!empty($description) && $description->status === 'ok')
                                        {!! $description->data !!}
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    @if($continentPics->isNotEmpty() && $continentPics->has($continent))
                                        @php $pic = $continentPics->get($continent); @endphp
                                        <p>
                                            <img class="max-width-100"
                                                 src="{{ $pic->url(['']) }}"
                                                 alt="@if ($pic->caption) {{ $pic->caption }} @else {{ $continent }} @endif"
                                                 title="@if ($pic->caption) {{ $pic->caption }} @else {{ $continent }} @endif"
                                                 style="max-width: 100%;"
                                            >
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <cities-tab
                                    :data="{{ $continentsCountries->get($continent) }}"
                                    :all="'{{ __('cities.AllTypes') }}'"
                                    :hostels="'{{ __('cities.HostelsOnly') }}'"
                                    :continents-page="true"
                                    :continent="{{ json_encode($continent) }}"
                                    :radio-name="'{{ \Illuminate\Support\Str::slug($continent) }}'"
                            />


                        </div>
                    </div>
                </div>

            @endforeach

        </div>
    </section>
    <script src="{{ mix('js/vue/modules/cities-tabs.js') }}"></script>
@stop

@section('pageBottom')
    @parent
    <script type="text/javascript">
        initializeTopHeaderSearch();

        $(document).on("hostelz:frontUserData", function (e, data) {
            data.editURLFor = {target: 'continents', id: 1};
            return data;
        })

        $(document).on('hostelz:loadedFrontUserData', function (e, data) {
            if (data.editURL) {
                addEditURL(data.editURL)
            }
        });

        function addEditURL(editURL) {
            $('.edit-city').remove();

            $('.continents-hero').each(function (index, elem) {
                let url = $(elem).data('url')

                $(elem).after(
                    '<a class="d-block text-center text-decoration-underline edit-city" href="' + url + '">' +
                    'Edit Continent Description' +
                    '</a>'
                )
            })
        }

    </script>
@stop