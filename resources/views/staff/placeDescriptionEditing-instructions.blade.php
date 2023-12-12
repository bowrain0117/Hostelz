<?php

    use App\Services\Payments;
    
?>

@extends('layouts/admin')

@section('title', 'City/Region/Country Description Editing Instructions - Hostelz.com')

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
            {!! breadcrumb('City/Region/Country Description Editing Instructions') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.IncomingLink') !!}</div>

        <h1>City/Region/Country Description Editing Instructions</h1>
        
        <p><a href="@routeURL('placeDescriptions:instructions')">Read the instructions</a> for writing descriptions if you haven't already. That will tell you what to look for in the descriptions, and what to allow and not allow in them.</p>
        
        <p>The descriptions should already be fairly decent quality writings. We have a higher minimum standard of writing quality that we'll accept for descriptions than we do for reviews.  If one just isn't a good descriptions of the place, and there isn't enough good information in the text so that you can easily fix it, you can use the button "Flag" the description.  If you "Flag" the description, that puts it in a list for our admins to look at it to determine what to do with it.  You can also use the "Staff Notes" box if you want to add a note to the admin about why you flagged it.</p>
        
        <p>If you're also editing reviews, note that reviews are already pre-approved before then end up in your list.  But the descriptions haven't yet been approved by anyone when you see them, so you may get ones that shouldn't be accepted.</p>
        
        <p>Otherwise, edit the description and then click the "Approve" button.  There is also an "Update" button which will save your changes, but not yet approve it (which means it will stay in your list).  So you may want to use that if you have started editing a description but aren't yet ready to approve it.</p>
        
        <p>Other notes:</p>
        
        <ul>
            <li>The descriptions shouldn't mention specifics about individual hostels (that information should go on the hostels' listings instead because it may change as hostels open and close, etc.). It is ok if the descriptions say general things about the hostels in the city as a whole (that there are few or many, that they tend to be located in the center of town, etc.</li>
        </ul>

            
        <h2>Log & Payments</h2>
        
        <p>From the staff menu there are links at the bottom of the page to view your Activity Log and the <a href="{!! routeURL('user:yourPay') !!}">Your Pay</a> page for what you have done so far.  Payments are sent to your email address using PayPal at the end of each month if your amount earned is at least ${!! Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}.  You are paid for updating link information and for sending emails.  If you use a website's online form to send them a message (and enter a copy of the email text with the Incoming Links form, that also counts the same as sending an email.</p>
        
        <p>You are working for Hostelz.com as an independent contractor.  It is your responsibility to pay an income taxes that are required in your country of residence.  If you are a U.S. resident and we pay you more than $600 during the year, we are required to report your earnings to the IRS and to you on a form 1099-MISC.  The IRS requires that you include the income when you file your taxes.</p>
    
    </div>

@stop
