<?php

    use App\Services\Payments;
    
?>

@extends('layouts/admin')

@section('title', 'Incoming Link Instructions - Hostelz.com')

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
            {!! breadcrumb('Incoming Link Instructions') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.IncomingLink') !!}</div>

        <h1>Incoming Link Instructions</h1>
        
        <h2>Training Videos</h2>
        
        You should start by watching the training videos here: <a href="https://www.youtube.com/playlist?list=PL0-qoIiJdI5pip2iSU1cwW54yDs2ASUwH">https://www.youtube.com/playlist?list=PL0-qoIiJdI5pip2iSU1cwW54yDs2ASUwH</a>
        
        <h2>How to Handle Specific Kinds of Websites</h2>
        
        <ul>
            <li><strong>Hostels' own websites</strong> - Choose "accommodation" as the type and then just generate the email text without choosing any contact topics.  That will generate an email that just lets them know that we have them listed on our site with a free listing that links to their website, and that we just ask that they link back to us.
            <li><strong>Past events</strong> - You'll often see web pages that mention accommodations for events and conferences that already happened.  Even when the event already took place, we've had good success with asking them to keep us in mind for future events.  So it's usually still worth contacting them.
            <li><strong>City tours</strong> - Companies that do city tours or similar services are going to be most interested in doing a cross promotion with us.  That tends to be something they're very interested in since it's a perfect fit for their market.  For these I would only ask about cross promotion and not bother with the features or affiliate program.
            <li><strong>Web Directory</strong> - The the website is entirely nothing but a web directory of links (<a href="http://www.alistdirectory.com">like this one</a>), and they have a "submit your website" link that takes you to a page that either requires a payment or requires that you link back to them, that kind of website should be ignored.  When you choose the "Ignored Because" reason, there is a new "Paid Link Directory" option you can choose.  Note that this isn't the same thing as a travel blog or other kinds of websites that might have a page of links, or legitimate travel website that have links pages that aren't just paid links.  Those are ok to contact.
        </ul>
        
        <h2>Specific Place or Listing</h2>
        
        <p>When the website or competitor site link is about a particular city, country, or hostel, you should use the "Specific Place or Listing" field to find that city, country, or hostel on our site.  That way when you generate the email it will give them URLs and info that are specific to that place.  We always prefer to get people to link directly to our city our country pages whenever possible.</p>
        
        <h2>Contact Topics</h2>
        
        <ul>
            <li><strong>Affiliate Program</strong> - It's often worth mentioning to travel blogs, and other kinds of sites that use advertising or are wanting to profit from their content.  I wouldn't use the affiliate option if it's a university's own website, a government site, a nonprofit, a news article, an accommodation, etc.  And there are some kinds of sites that are more likely to be interested in a cross promotion (such as walking tours and other travel services), so for those you may want to use cross-promotion instead (or mention both).</li>
            
            <li><strong>Cross-promotion</strong> - We can offer to run ads on our city pages in exchange for the website linking to us.  You should use the cross-promotion contact topic fairly often.  By far it has been our most successful strategy, with websites responding positively to that about 4x as often as other strategies likes the affiliate program.  So don't forget to use cross-promotion websites that might be interested in that, which means most websites that are offering products or information for an audience related to travel.  Some of the larger travel blogs are interested in cross-promotion for example, and of course tours, travel insurance, etc. But it wouldn't make sense for things like newspapers (NY Times, etc.), very small personal blog sites are less likely to be interested (if it isn't a money-making kind of blog), and we aren't doing cross promotion for individual hostels/hotels.</li>
            
            <li>(to be completed)</li>
        </ul>
        
        <h2>Finding Contact Information</h2>
        
        <h3>First Search on the Website</h3>
        
        <h3>"Possible Contacts Found"</h3>
        <p>Our system automatically scans the websites to look for email addresses and other contact info.  If it finds any, it will list that info at the bottom of the link under "Possible Contacts Found".  If you already searched the website yourself and didn't find a better contact email address, you may want to use an address that's listed in the "Possible Contacts Found". (But you should look for one on the actual website first since there may be a better email address to use.)  You can see an example <a href="@routeURL('staff-incomingLinks', 3487)">here</a>.</p>
        
        <h3>"Search for Contact Email Addresses"</h3>
        <p>On the link page there is a "Search for Contact Email Addresses" tab at the bottom of the page.  If you click on that, it will open a panel that lets you search the website for email addresses (it uses the EmailHunter.co service).  You should only use this if you have already tried to find a contact form or email address on the actual website and you were completely unable to find one.  That's because we are paying for a limited number of contact searches, but also their information isn't always accurate and it is more reliable if you can find an actual email address or contact form on their website.</p>
        <p>You can optionally enter a name to search for.  That's mostly useful if the website is something like a newspaper (such as nytimes.com) and you're trying to find a way to contact a particular writer that wrote an article about hostels.</p>
        <h2>Reminders</h2>
        
        <p>When the contact status of the link is "Discussing" or "Closed", there is a field at the bottom where you can set a "Reminder Date".  If you set a date in that field, the link will show up in your "Reminder Due" list that you can find on the staff page starting on that date.  So it is up to you to remember to occasionally check the "Reminder Due" list if you're using reminders.  To clear the reminder, delete the Reminder Date from the link and Submit it.</p> 

        
        <h2>Using the Email System</h2>
        
        <p>When you are looking at an email it will show you any Incoming Links that are associated with the sender's email address.  If it didn't find the incoming link, you can do a search from the "Incoming Link" text box to search for the link by entering part of the URL.  If the link doesn't have this sender's email address it will ask you if you want to "Click here to add it" to add the email's email address to the Incoming Link.</p>
        <p>One thing to be aware of, when you use that "Click here to add it" link to add their email address, be sure that you aren't already editing the incoming link on another tab.  If you use the "Click here to add it" link, and then save the incoming link that you already had open in another tab, it will lose that email address you previously had it ad because it overwrites it with the new info when you save the incoming link you already had open without the email address (I hope that makes sense).  So add the email address first, *then* open the link in a new tab when you edit it to change the status, etc.</p>
        
        <p>Buttons:</p>
        <ul>
            <li><strong>Spam</strong> - You can use the "Is Spam" button to mark an email as spam.  Be careful to only use it on spam emails or unwanted kinds of emails because it will teach the email system to learn that emails of that kind should be filtered out.</li>
        </ul>
              
        <p>You can set a reminder date for an email you received if you want to remember to come back to it at a later date.  On the email page, look for "Reminder Date" in the middle of the page and set the date with that.  But then you have to check the "Reminder Emails Due" list from your staff page occasionally to check for any emails that have a reminder due.</p>
          
        <h3>Responding to Types of Emails</h3>
        <ul>
            <li><strong>Paid Advertising/Links</strong> - If they ask if we want to directly pay them for advertising or links, Defer the email to admin and we'll take a look at whether that makes sense to do.</li>
        </ul>
        
        @if (auth()->user()->hasPermission('staffMarketingLevel2'))
            
            <h2>Cross Promotion (Creating Ads)</h2>
            
            <p>The ads are what we offer websites that want to do a cross promotion with us.  When you are able to set up an arrangement with someone to link to us in exchange for an ad on our site, you can create the ad.  It's a fairly easy process.  Here is a video to show you how to do that:  <a href="https://www.youtube.com/playlist?list=PL0-qoIiJdI5o0pQHkJq2nYBH96TsQF0nJ">https://www.youtube.com/playlist?list=PL0-qoIiJdI5o0pQHkJq2nYBH96TsQF0nJ</a></p>
            
            <p>There are also macros you can use when composing an email that you can use to tell people about cross promotions and also a "ad created" one with a template for what you can tell them after you created the ad.</p>
            
        @endif
            
        <h2>Log & Payments</h2>
        
        <p>From the staff menu there are links at the bottom of the page to view your Activity Log and the <a href="{!! routeURL('user:yourPay') !!}">Your Pay</a> page for what you have done so far.  Payments are sent to your email address using PayPal at the end of each month if your amount earned is at least ${!! Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}.  You are paid for updating link information and for sending emails.  If you use a website's online form to send them a message (and enter a copy of the email text with the Incoming Links form, that also counts the same as sending an email.</p>
        
        <p>Additionally, if a website you have contacted adds a link to Hostelz.com, you will most likely be paid a commission for bookings that are made by users who visit Hostelz.com through that link.  This is a new and experimental aspect of our pay system, and the commission rate and length of time you will be paid a commission may change.  So you shouldn't consider the commission bonus to be a guaranteed part of your pay, we do intend to try to make that a real incentive that most likely will add significantly to your earnings.</p>
        
        <p>You are working for Hostelz.com as an independent contractor.  It is your responsibility to pay an income taxes that are required in your country of residence.  If you are a U.S. resident and we pay you more than $600 during the year, we are required to report your earnings to the IRS and to you on a form 1099-MISC.  The IRS requires that you include the income when you file your taxes.</p>
    
    </div>

@stop
