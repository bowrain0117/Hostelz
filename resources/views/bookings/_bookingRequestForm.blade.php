{{-- This may be included in the bookingRequest template directly, ?? fetched with AJAX. --}}


<form>
    <input name="foo">
</form>

<div class="messageBox2">
	<h1><img src="/images/checkmarkSuccess.png"> {#Success#}</h1>
	{#BookingCompleted#}
	<p>
	<b>{#BookingID#|replace:'[system]':$bookingSystemName|replace:'[bookingID]':$booking.bookingID}</b>
	<p>
    {capture assign=confirmationSender}if ($submitResult.confirmationSender != '')"{ $submitResult->confirmationSender }" else Hostelz.com endif {/capture}
    {#ConfirmationEmail#|replace:'[system]':$confirmationSender|replace:'[email]':$booking.email}
    <p>
    if (!$login->userid)
        To keep track of your bookings, and to earn award points on this booking and all future bookings, <a href="/register.php">sign-up for your Hostelz.com user account here</a>.
    endif 
</div>

{{-- <iframe src="/dummy.php?state=bookingSuccess&system={$booking.system|escape:'url'}&id={$booking.bookingID|escape:'url'}" height=1 width=1></iframe> --}}
