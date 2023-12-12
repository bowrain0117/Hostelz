<?php

use App\Models\Listing\Listing;
use App\Models\Review;
use App\Services\Payments;
use Lib\Currencies;

Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', 'Reviewer Instructions - Hostelz.com')

@section('header')
    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('My Reviews', routeURL('reviewer:reviews')) !!}
            {!! breadcrumb('Reviewer Instructions') !!}
        </ol>
    </div>

    <div class="container">

        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.Review') !!}</div>

        <h1 class="hero-heading h2">Reviewer Instructions</h1>

        <p>We have two types of reviews, paid reviews and user ratings. User ratings appear at the bottom of the
            listings with stars next to them. But the paid reviews appear at the top of the page under the section
            titled "@langGet('listingDisplay.HostelzReview')" (for examples see <a
                    href="{!! \App\Models\Listing\Listing::areLive()->findOrFail(215471)->getURL() !!}">this one</a> or
            <a href="{!! Listing::find(7411)->getURL() !!}">this one</a>). A paid review is basically a few pictures of
            the hostel along with a text review that tells what the hostel is like.</p>

        <h2>Pay</h2>

        <p>We pay {!! Currencies::format(Review::PAY_AMOUNT, 'USD', false) !!}</b> per review (that's
            roughly {!! Currencies::convert(Review::PAY_AMOUNT, 'USD', 'EUR', true, false) !!}
            , {!! Currencies::convert(Review::PAY_AMOUNT, 'USD', 'CAD', true, false) !!},
            or {!! Currencies::convert(Review::PAY_AMOUNT, 'USD', 'AUD', true, false) !!}) per review. That should be
            enough to make it worth the couple of minutes it takes to take a few pictures and write a few paragraphs for
            each hostel you stay at. Payments will be paid via <strong>PayPal.com</strong> (it's free to sign up and you
            can have the payment deposited in your bank account). The PayPal payments will be made to the email address
            you signed up with on Hostelz.com. If your PayPal account uses a different email address, you can enter a
            different payments email addres on the <a href="{!! routeURL('user:yourPay') !!}">Your Pay</a> page. You
            don't need a PayPal "business/premier" account, just a personal PayPal account.</p>

        <p>We typically approve reviews that have been submitted about once a month. Your total earnings will be
            transferred to your PayPal account automatically at the beginning of the month if your amount due is at
            least ${!! Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}. If you haven't yet reached
            ${!! Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}, your balance will continue to accrue each month until
            you reach that amount. If you haven't yet reached that amount and you don't plan to submit more reviews,
            contact us and we can send your payment for the balance of your current earnings.</p>

        <p>But besides making some money, you will be helping to create a really useful and complete worldwide hostel
            guide that's used by hundreds of thousands of backpackers. So the words you write will be an important
            source of information to find out what they can expect at a hostel before they arrive.</p>

        <h2>Holding Hostels & Submitting Reviews</h2>

        <p>You can hold hostels that you plan to review so that other reviewers won't be able to review the same hostel.
            Note that we only accept one paid review per hostel (unless the current review is more than a few years
            old), but the reviewer system will inform you whether or not a hostel is available for you to review.</p>

        <h2>Booking Your Hostel Stay</h2>

        <p>Any hostel that can be booked through Hostelz.com should be booked using our system so we can verify your
            booking (use the "Check Availability" button at the top of the listing). The booking system lets you
            complete your booking through Hostelworld and other booking sites, but as long as you clicked through from
            our site, we'll be able to track your booking information. You will need to enter your booking confirmation
            code when you submit the review. There is an exception for hostels that don't work with any of the booking
            systems we use (but most hostels do work with at least one of our booking system partners).</p>

        <h2>The Reviews</h2>

        <p>The review must be at least <strong>{!! Review::$minimumWordsAccepted !!} words minimum</strong>, but if you
            want to write a longer review that's great (we love long, detailed reviews!). You can say whatever you want,
            but more than anything we want to give backpackers some sense as to what to expect.</p>

        <p>Important: Reviews should be written in <strong>present tense</strong> (instead of saying "the hostel
            <em>was</em> marvelous", say "the hostel <em>is</em> marvelous"). But if there is something you want to
            mention about your particular experience, you might need to use some past tense for that, but you should
            <strong>use "we" instead of "I"</strong> since it sounds more professional.</p>

        <p>Reviews should include both positive and negative points about the hostel. All reviews should at least
            mention the condition of the building and how clean it is, what the bathroom failicities are like, what the
            common areas (kitchen, sitting areas) are like, and what the atmosphere is like (fun, quiet, social, dreary,
            happy, colorful, cold, dull, etc). We don't usually say much about the staff because they can change from
            month to month, but you might want to mention the staff if something stands out or if the hostel is run by
            the actual owners. Similarly, it's best not to quote exact prices since prices change frequently, but you
            can comment on whether prices are cheap or expensive. You can submit the reviews during your trip, or just
            take good notes and submit them after the trip.</p>

        <p>Tip: <a href="{!! routeURL('articles', 'hostel-owner-suggestions') !!}">This article</a> is a great list of
            things to look for in a hostel.</p>

        <p><b>Important:</b> We have a template for you to use to structure your reviews with a particular set of
            <strong>subtitled sections</strong>. You will see the template when you add a hostel to your hostel list and
            then click on it to start your review.</p>

        <h2>The Pictures</h2>

        <p>You must take pictures of the hostel during your stay to be paid for the review. After you enter your review
            text online, you can also use the website to submit the photos online. We prefer large high-resolution
            pictures. Photos must be at least <strong>{!! Review::NEW_PIC_MIN_WIDTH !!}
                x {!! Review::NEW_PIC_MIN_HEIGHT !!} pixels or larger</strong>. There is no maximum size, the system
            will resize the pictures for the website as needed (we prefer large photos).</p>

        <p>We've found that if you tell the hostel staff you're taking pictures for a website, they're often reluctant
            or they'll ask you to wait until they ask permission of the owner (who is usually out of town for the next
            week or two). So it's best not to try to ask permission. Of course you're not breaking any rules by taking
            pictures in a hostel, and if the owner complains after the photos are posted on the website we can always
            remove them later. Of the hundreds of hostels we've photographed, no hostel has yet complained after the
            photos have been posted on the site.</p>

        <p>For the pictures, we need to have <strong>at least {!! Review::MIN_PIC_COUNT !!} pictures for each
                hostel</strong>, but more is better. It doesn't matter whether or not we already have any photos of the
            hostel on our site, the complete set of review pictures are still required if you want to be paid for the
            review. We always want to have a picture of the <strong>outside of the hostel's building</strong>. The
            outside pictures usually look best if you take the picture from across the street, and you might have to
            kneel down to get a good angle. Inside the hostel you should get pictures of the <strong>common
                areas</strong> (such as the sitting area or kitchen), and it's always nice to get a few travelers
            hanging out in the picture too. You should also get a picture of a <strong>dorm room</strong>. And a picture
            of the <strong>bathroom</strong> if possible because people like to know how clean or nice the bathrooms
            are. We'll eventually also want to have at least <strong>one picture for each city</strong> on our site, so
            if you get any pictures that can represent a city (such as the Eiffel tower in Paris), then we could use one
            of those as well (when you upload your pictures, there is a separate link on that page for uploading city
            pictures!).</p>

        <p>Other than that, any other pictures you take might also be useful, so take a bunch if you have enough space
            and send us all of them. Be careful to keep track of which pictures were from which hostels because it's not
            always easy to figure it out later!</p>

        <h2>Finding Hostels</h2>

        <p>We want reviews for <em>all</em> hostels, whether or not they are currently listed on Hostelz.com. So if you
            find a hostel that isn't already on the site, you can submit it as a new hostel and then submit a review for
            it.
            <string>To add a new hostel, we require that they have a website (sorry, hostels without a website can not
                be added).</strong>
        </p>

        <p>Make sure that the place you review <strong>really is a hostel</strong>. A place must offer shared dorm rooms
            to be considered a hostel on our site. Some of the hostels on our site could be mislabeled, so it's up to
            you to verify that it really is a hostel with dorm rooms. By the way, a "hostal" is usually not a hostel,
            that's a kind of hotel.</p>

        <h2>Other Misc Details</h2>

        <p>You can not tell the hostel before or during your stay that you are reviewing them. They must not be aware
            that you are any different than any regular guest.</p>

        <p>If we don't accept your review for any reason, then we can't pay you for the review. But we generally accept
            all reviews that follow the guidelines outlined above. We may (and probably will) edit reviews and photos
            before they're listed on the website. If we pay you for your review, the pictures and reviews become the
            property of Hostelz.com and they may not be published elsewhere without our permission. </p>

        <p>So those are the details. Remember it's always up to you to review as many or as few hostels as you want. You
            don't have to decide before you leave on your trip. If you decide that you're having too much fun on your
            trip and you don't want to take any more pictures or write reviews, than you're perfectly welcome to stop or
            start whenever you want. If you submit reviews we usually require at least <strong>two hostel reviews as a
                minimum</strong> (to discourage a hostel owner from just submitting a review of his own hostel).</p>

        <p>Hostelz.com will be a very thorough guide to hostels anywhere in the world and we'll do our best to get the
            word out so that it will be <em>the</em> place backpackers go to look for hostel information. For most
            hostels, there is no other source of unbiased reviews, so your reviews will be the authoritative source of
            information about the hostels you review, and they will be read and used by thousands of other travelers for
            a long time to come.</p>

    </div>

@stop
