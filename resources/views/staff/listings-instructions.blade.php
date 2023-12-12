<?php

    use App\Services\Payments;
    
?>

@extends('layouts/admin')

@section('title', 'Listing Editing Instructions - Hostelz.com')

@section('header')

    <style>

    </style>

    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Listing Editing Instructions') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.Listing') !!}</div>

        <h1>Listing Editing Instructions</h1>
        
        <h2>Merging Duplicates & Unmerging</h2>
        
        <h3>Videos</h3>
        <p>You should start by <a href="https://www.youtube.com/playlist?list=PL0-qoIiJdI5oCwab0qQDK-niz4lhpWPIr">watching the training videos</a>.</p>
        
        <h3>Fields</h3>
        
        <p>Some notes on some of the database fields.</p>
        
        <ul>
            <li><strong>Country</strong> - In most cases the country is the country name.  Usually you should follow the spelling and naming style that we are currently using (the United States is "USA" for example).  For the United Kingdom, we don't use "United Kingdom" as the country but instead use the more specific names England, Scotland, Northern Ireland, etc.</li>
            <li><strong>City</strong> - The city field is usually used for the city, but there are some exceptions.  For example, in Hawaii we use the "city" field for the island name (and put the city in the Neighborhood field of each listing).  We sometimes do that in places where we're already using the "region" field for something else (the state Hawaii), but there are small islands or other geographic areas that we want to organize listings by.</li>
            <li><strong>Telephone/Fax</strong> - If there are two different phone numbers when merging, you can copy one of them and add it to the listing after (like "+39 3467249131, +39 031523662").  If one phone number is the preferred or correct one, the hostel owner can fix it later when we contact them to update their listing info.  But I might contact them if the two different phone numbers are just one of the reasons I'm already uncertain if the two listings are really duplicates.</li>
            <li><strong>Customer Support Email Addresses</strong> - If you can find contact info for a hostel from their website, you can add their email address to this field.</li>
        </ul>
        
        <h3>Buttons</h3>
        
        <ul>
            {{-- (removed - have them email me instead) <li><strong>Flag for Admin</strong> - You can use this if you have a question about what to do with the listings, ?? if there is another issue with the listings that we should be aware of.  Use the Notes field to add any relevant notes.</li> --}}
            <li><strong>Hold</strong> - This puts the duplicates in your "hold" list to do later.  You may want to use list if you need to send an email to the listing to ask them a question.</li>
            <li><strong>Non-Duplicates</strong> - This marks the listings as non-duplicates so that they won't appear in the merge lists any more.</li>
        </ul>
        
        <h3>Merging Listings</h3>
		<p>Merging is one of the most important tasks on Hostelz.com. Merging means we merge listings from different booking sites so we can actually compare rates for the same hostel.</p>
		<p>E.g. Fun Hostel is listed on</p>
		<ul>
			<li>Hostelworld</li>
			<li>Booking.com</li>
			<li>Hostelsclub</li>
		</ul>
		<p>It needs to be merged. The system detects possible duplicates and determnies the likelyhood of it being a merging-case/ duplication.</p>
		<p><strong>What happens if a listing is not merged?</strong></p>
		<p>If a listing is not merged, it is likely that we show both listing, separated in the city. Meaning, we have duplicated listings which is not good for Google, user-experience and trust.</p>
		<p>New Hostelworld listings are automatically approved by the system. Booking.com listings are <span style="text-decoration: underline;"><strong>not</strong></span> automatically approved. This means when we import a listing from Booking.com that we already imported earlier with Hostelworld, the new booking.com listing will <span style="text-decoration: underline;"><strong>not</strong></span> appear on the website until we manually approve it.</p>
		<p><strong>What if I merge a listing and the link is broken?</strong></p>
		<p>Sometimes, when merging two listings, the final link can be broken. Why? Most likely it is because the city it is listed in, has a "region". The listings you just merged are most likely missing this region. Please check the city in <em>editing</em> and check your listing in <em>editing</em>.</p>
		<p><strong>United Kingdom vs England/Wales/Northern Ireland/Scotland</strong>: This is a special case. We do have the United Kingdom separated into England, Wales, Scotland, Northern Ireland. When merging listings from the UK, you can choose between UK and a country mentioned. You will need to pick the country.</p>
		<p><strong>Important</strong>: When merging listings, please verify the final URL/ Link is working by pressing "View Listing"</p>
        
        <h2>Unmerging/ Separating: Two properties not correctly merged</h2>
        <p>Basically you have to separate things out by hand. The first step is to click on the "<em>Imported</em>" link to one of the booking systems that was wrongly associated with the listing. Then go to the "<em>Listing</em>" field and change it to "<em>0</em>" (zero) and save it. That un-associates it from the listing and then there is an Imported record that isn't associated with any listing.</p>   

		<p>Then go to the staff menu and click "<em>Insert New Listings from Importeds</em>". That will create listings from Imported records that aren't associated with any listings, which includes that one that you just un-associated. To find the listing that it created for it you can go back to the Imported page a reload it and go to the Listing link.</p>   

		<p>If the original listing also had other Imported booking pages that were wrongly associated with that listing, you can now go to those and change the listing ID to the ID of the new listing.</p>   

		<p>Then it's just a matter of editing the original listing to fix any incorrect information left over from the merge.</p>    
            
        <h3>General Tips</h3>
        
        <ul>
            <li><strong>Accents / Foreign Characters</strong> - Names and addresses with accented characters are usually more correct, so usually the accented option is the better choice.  So choose "Nîmes" instead of "Nimes" (this is the same as what Wikipedia uses for their spelling, which is usally a good guide).  But we should avoid alphabets like Cyrillic or Asian characters that aren't readable at all to someone who only knows English (choose "Sportivniy per." instead of "Спортивный пер.").</li>
            <li><strong>When in doubt, do what Wikipedia does</strong> - For city/region/country names with multiple spellings, we typically use the same spelling as Wikipedia does.  There is a "Wikipedia Search" button you can use at the top of the city editing pages to quickly lookup a city.</li>
            <li><strong>Same place with "Hostel" and "Hotel" listings</strong> - Some accommodations will be in the database with two separate listings, one for their "Hostel" rooms and a different listing for their "Hotel" room.  For example, "Something Hostel" and "Something Hotel", but bother are at the same address and pictures are clearly of the same place.  Those we usually merge since it's really the same hotel/hostel. And you should choose the "Hostel" name since the hostel is what we mostly focus on.</li>
            <li><strong>Multiple apartments</strong> - There are some listings where there are separate listings for several apartments, such as "Bob's Apartments - A", "Bob's Apartments - B", etc.  In some cases we can merge these into one listing, but in some cases they probably need to be kept separate.  Just keep in mind that once they're merged, when someone views the listing it will only show the photos and description of one of the original listings, but the "Book Now" buttons will direct them to the booking page for any of the booking systems listings that were merged with that listing.  So if each apartment is substantially different (significantly different accommodations with different photos and descriptions), it may be better to keep the listings separate so that people know which one they're booking.  But if they're pretty much the same, you would probabl want to merge them.</li>
            <li><strong>Viewing imported / not yet live listings</strong> - For those listings that are imported and not yet live we may have very little data in our system to view. So the best way to view photos/descriptions/etc. for those listings is to go directly to the booking system's own listing.  The easiest way to do that directly is to click on the booking system's link under "Imported" in the table on the merge page.  Then click the link for "Booking System's Website" in the purple bar at the top of that page.</li>
        </ul>
        
        
        <h2>Advanced Listing Editing</h2>
        
        <p>You should start by <a href="https://www.youtube.com/playlist?list=PL0-qoIiJdI5o0_HRtmq0kbfpUL-yZLAF8">watching these additional training videos</a>.</p>
        
        
        <h2>Log & Payments</h2>
        
        <p>From the staff menu there are links at the bottom of the page to view your Activity Log and the <a href="{!! routeURL('user:yourPay') !!}">Your Pay</a> page for what you have done so far.  Payments are sent to your email address using PayPal at the end of each month if your amount earned is at least ${!! Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}.  You are paid for updating link information and for sending emails.  If you use a website's online form to send them a message (and enter a copy of the email text with the Incoming Links form, that also counts the same as sending an email.</p>
        
        <p>You are working for Hostelz.com as an independent contractor.  It is your responsibility to pay an income taxes that are required in your country of residence.  If you are a U.S. resident and we pay you more than $600 during the year, we are required to report your earnings to the IRS and to you on a form 1099-MISC.  The IRS requires that you include the income when you file your taxes.</p>
    
    </div>

@stop
