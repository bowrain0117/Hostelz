<?php 
    use App\Models\CityInfo;
    use App\Models\CountryInfo;
    use Lib\Currencies;
    use App\Services\Payments;
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', 'Place Description Instructions - Hostelz.com')

@section('header')

    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            @breadcrumb(langGet('User.menu.PlaceDescriptions'), routeURL('placeDescriptions'))
            {!! breadcrumb('Place Description Instructions') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.CityInfo') !!}</div>

        <h1 class="hero-heading h2">Place Description Instructions</h1>
        
        <h2>Writing City Descriptions</h2>
        
        <p>For an example of a city description see <strong><a href="{!! CityInfo::find(18561)->getURL() !!}">Austin</a></strong> (the description is in the column on the left side under the heading "About Austin").  The purpose of a city description is to give people a little introduction to the city and tell them something useful about it, particularly in regards to the hostels in the city.  The description must be at least  <strong>{!! CityInfo::CITY_DESCRIPTION_MINIMUM_WORDS !!} words in length</strong>.  We pay {!! Currencies::format(CityInfo::CITY_DESCRIPTION_PAY, 'USD', false) !!}</b> per accepted city description (that's roughly {!! Currencies::convert(CityInfo::CITY_DESCRIPTION_PAY, 'USD', 'EUR', true, false) !!}, {!! Currencies::convert(CityInfo::CITY_DESCRIPTION_PAY, 'USD', 'CAD', true, false) !!}, or {!! Currencies::convert(CityInfo::CITY_DESCRIPTION_PAY, 'USD', 'AUD', true, false) !!}).</p>
        
        <p>Since people looking at Hostelz.com are looking for a hostel, it's great if you can give them some useful advice about looking for a hostel in that city.  But the tricky part is hostels frequently close or change names and new hostels open.  So you <strong>shouldn't refer to specific hostels</strong> because that information may get outdated over time, but you can talk about the hostels in the city in general, discuss different neighbhorhoods and areas of this city where they might want to consider looking for a hostel, the general conditions to expect for hostels in that part of the country, or give other advice about what to look for in a hostel.  For some cities, especially smaller ones with one or two hostels, you may not be able to come up with much to say about hostels there.  If that case, say what you can about the hostels, but you can also include some other useful information about the city in general.</p> 
        
        <p>When looking at cities, if you notice any corrections that need to be made (the same city listed multiple times with different names, etc.), please <a href="@routeURL('contact-us')">let us know</a>.</p>
        
        <p><strong>Don't copy any text</strong> from anywhere online or from any other source (including Wikipedia or any other website).  If you do your account will be deleted from our system.  All of the text must be your own unique work written specifically for Hostelz.com.</p>
        
        <p>One last note, other than helping people learn about the city and its hostels, a secondary purpose of the city descriptions is that providing this good text content helps the city show up better in Google search results.  This is because Google likes to see text on the page that talks about the subject of the page.  So for this reason, it's good if your description uses phrases like "[city name] hostels" and "hostels in [city name]", or just find ways to mention the word "hostel" because that will help the page rank a little better in Google searches for those phrases.  This is secondary to the primary goal of writing a good, useful description that's helpful to people using the site.  If possible please try to use the work "hostel" or "hostels" at least two or three times in each city description.</p>
        
        <h2>Writing Country Descriptions</h2>
        
        <p>{{-- (todo) For an example of a country description see <strong><a href="">---</a></strong>. --}} The purpose of a country description is to give people a little introduction to the country and tell them something useful about it, particularly in regards to the hostels in the city.  The description must be at least <strong>{!! CountryInfo::COUNTRY_DESCRIPTION_MINIMUM_WORDS !!} words in length</strong>.  We  pay {!! Currencies::format(CountryInfo::COUNTRY_DESCRIPTION_PAY, 'USD', false) !!}</b> per accepted country description (that's roughly {!! Currencies::convert(CountryInfo::COUNTRY_DESCRIPTION_PAY, 'USD', 'EUR', true, false) !!}, {!! Currencies::convert(CountryInfo::COUNTRY_DESCRIPTION_PAY, 'USD', 'CAD', true, false) !!}, or {!! Currencies::convert(CountryInfo::COUNTRY_DESCRIPTION_PAY, 'USD', 'AUD', true, false) !!}).</p>
        
        <p>Country descriptions should start with some general information about the country that would be interesting to budget travelers thinking about visiting it.  You may want to mention whether it's especially expensive or cheap to travel there, and some of the reasons why people visit that country.  You'll probably then want to talk about what cities and regions are particularly worth visiting.  Maybe include some mentions of cities and parts of the country that are lesser known, but worth visiting.  If you spell the city or region name the same way it is spelled on our site, our system will automatically make those names into links that people can click to go directly to the city you're disussing.</p>
        
        <p>As with the city descriptions, it is necessary that you mention the word "hostel" or "hostels" at least two or three times in the description.  This is partly important because we want Google to realize that this is a page about hostels, but also because we want to tell people about when to expect when they're looking for hostels in the country.  But we also want to tell users what to expect from hostels in that country.  You can talk about things like which cities have the most hostels, the general quality of hostels in the country, whether hostels are expensive or cheap in different areas, how popular they are, what times of year they are most crowded,  etc.  Don't talk about specific hostels by name because hostels often come and go and we don't want the text to become out of date when a hostel closes.</p>
        
        <h2>Writing Region Descriptions</h2>
        
        <p>What we call a "region" depends on the country.  In the United States it's a state, in Ireland it's a county, and in Canada it's a province or territory.  All you really need to know if that you can also write descriptions for regions and a region description is similar to a country description as far as what you should write about.  Not all countries on our site have regions that you can write about, but if you use the Place Descriptions page to search for a region and it is available, then you can write about it.</p>
        
        <p>We are currently accepting region descriptions for the regions we have listed in our database for these countries: <em>{{{ CountryInfo::where('regionType', '!=', '')->pluck('country')->sort()->implode(', ') }}}</em>.</p>
        
        <p>Region descriptions must be at least <strong>{!! CountryInfo::REGION_DESCRIPTION_MINIMUM_WORDS !!} words in length</strong>.  We pay {!! Currencies::format(CountryInfo::REGION_DESCRIPTION_PAY, 'USD', false) !!}</b> per accepted country description (that's roughly {!! Currencies::convert(CountryInfo::REGION_DESCRIPTION_PAY, 'USD', 'EUR', true, false) !!}, {!! Currencies::convert(CountryInfo::REGION_DESCRIPTION_PAY, 'USD', 'CAD', true, false) !!}, or {!! Currencies::convert(CountryInfo::REGION_DESCRIPTION_PAY, 'USD', 'AUD', true, false) !!})</p>
        
        <h2>Languages</h2>
        
        <p>We only allow each person to submit descriptions in one language.  So please choose your primary (native) language and only submit descriptions in that language.  It must be your native language.  Automatic translations or poorly written translations will result in your account being suspended.</p>
        
        <h2>Pay</h2>
        
        <p>Payments will be paid via PayPal.com (it's free to sign up and you can have the payment deposited in your bank account). The PayPal payments will be made to the email address you signed up with on Hostelz.com.  If your PayPal account uses a different email address, you can enter a different payments email addres on the <a href="{!! routeURL('user:yourPay') !!}">Your Pay</a> page. You don't need a PayPal "business/premier" account, just a personal PayPal account.</p>
        
        <p>We typically approve reviews that have been submitted about once a month.  Your total earnings will be transferred to your PayPal account automatically at the beginning of the month if your amount due is at least ${!! Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}.  If you haven't yet reached ${!! Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}, your balance will continue to accrue each month until you reach that amount.  If you haven't yet reached that amount and you don't plan to submit more reviews, contact us and we can send your payment for the balance of your current earnings.</p>
        
        <p>Enjoy your travels and let us know if you have any questions.</p>

    </div>

@stop
