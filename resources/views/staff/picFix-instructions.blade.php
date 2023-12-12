<?php

    use App\Services\Payments;
    
?>

@extends('layouts/admin')

@section('title', 'Fix Pics Instructions - Hostelz.com')

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
            {!! breadcrumb('Fix Pics Instructions') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.Pic') !!}</div>

        <h1>Fix Pics Instructions</h1>
                    
        <h2>Basics</h2>
        
        <p>Sign-in to Hostelz.com, then click on your email address and the top of the page, then Staff Menu.  From the Staff Menu, click the "Review Photos" link.</p>
        
        <p>Payments are based on a set pay amount per each photo.  Payments are sent to your email address using PayPal at the end of each month if your amount earned is at least ${!! Payments::MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT !!}.</p>
        
        <h2>Photo Tweak Options</h2>
        
        <p>For each photo, you have four parameters that you can alter for the photo:</p>
        
        <ul>
            <li><b>B (Brightness)</b> - Many photos will need to be brightened at least a little, and some will need to be made less bright.</li>
            <li><b>S (Saturation)</b> - The saturation may need to be altered. Photos that are brightened often need some additional saturation also since brightening the photos tends to wash out the colors.</li>
            <li><b>C (Contrast)</b> - The contract can be only be increased or decreased one step (unfortunately we don't have a more variable control over the contrast setting).</li>
            <li><b>R (Rotation)</b> - If needed you can rotate the photo 90 degrees either direction, or 180 degrees if it's upside-down.</li>
        </ul>
        
        <p>Be sure to use a computer with a good quality monitor that is well calibrated so that you aren't mistakenly setting all of the photos too bright or too dim.  Most often, you might need to adjust the Brightness of the photos a bit, and the other options you'll probably use less frequently.</p>
        
        <p>When you change a setting, the border of the photo will turn red while the photo re-loads with your changes.  You will be able to see the updated photo with your new settings once the border turns back to black.</p>
        
        <h2>The Editing Process</h2>
        
        <p>Each time you change a setting, all of the current settings are applied starting from the original photo.  So you don't have to be concerned about degrading the quality of the photo if you apply and undo various tweaks while you're trying to find the best options for each photo. If you try some tweaks, but then decide not to use any changes and you set all of the settings back to 0, then the original untouched photo will be used (and many photos may not need any tweaking at all).</p>
        
        <p>When you reach the end of a page of photos, click the "Save & Continue" button.  None of the changes you have chosen are saved until you click that button.  When you do, if there are more photos to do, the next page of photos will appear on the next page.</p>
        
        <p>Don't try to do too many in one sitting.  If there are a lot in the queue to do, after a while take a break so you don't get fatigued.</p>
        
        <h2>Log & Payments</h2>
        
        <p>From the staff menu there are links at the bottom of the page to view your Activity Log and the <a href="{!! routeURL('user:yourPay') !!}">Your Pay</a> page for what you have done so far.</p>

    </div>
    
@stop
