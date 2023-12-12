{{-- 

Input Variables:
     
    $listingEditHandler
    
--}}

{{-- Deactivated as breaks new CSS < ?php Lib\HttpAsset::requireAsset('staff.css'); ?> --}}

@if ($listingEditHandler->action != 'preview')
	<h1 class="hero-heading h2">{!! langGet('ListingEditHandler.icons.'.$listingEditHandler->action, '') !!} {{{ $listingEditHandler->listing->name }}}</h1>
	<h2 class="h3">{!! langGet('ListingEditHandler.actions.'.$listingEditHandler->action) !!}</h2>
@endif

@if ($listingEditHandler->showGenericUpdateSuccessPage)

	<div class="clearfix">
		<div class="alert alert-success"><span class="glyphicon glyphicon-ok"></span> Saved.</div>
	</div>
	<p><a href="{!! $listingEditHandler->returnToWhenDone !!}">Return to Menu</a></p>

@elseif ($listingEditHandler->action == 'preview')

	<div class="alert alert-warning text-center">
		<h3>{!! langGet('ListingEditHandler.actions.preview') !!}</h3>
		<p><a href="{!! $listingEditHandler->returnToWhenDone !!}">Return to the Menu</a></p>
	</div>
	<br>

	@include('listing', [ 'listingViewOptions' => [ ] ])

@elseif ($listingEditHandler->action == 'sticker')

	@if ($formHandler->mode == 'updateForm')
		<p class="pb-5">@langGet('ListingEditHandler.sticker.pageDescription')</p>

		<form method="post" action="{!! Request::fullUrl() !!}" class="formHandlerForm form-horizontal">
			<input type="hidden" name="_token" value="{!! csrf_token() !!}">

			<div class="form-group">
				<label for="mailingAddress" class="control-label col-md-3">Mailing Address</label>
				<div class="col-md-9">
					<textarea class="form-control" name="data[mailingAddress]" rows="5"></textarea>
				</div>
			</div>

			<div class="form-group">
				<label for="insideOutside" class="control-label col-md-3">Adhesive Type</label>
				<div class="col-md-9 fancyRadioButtons">
					<div class="btn-group" data-toggle="buttons">
						<label class="btn btn-default">
							<i class="fa fa-circle"></i><i class="fa fa-circle-o"></i>
							<img src="/images/windowStickers/inside-small.jpg">
							<input type="radio" name="searchCriteria[roomType]" value="private">
							<div class="bold">Front Adhesive</div>
							<div>(to stick on a window from the inside)</div>
						</label>
						<label class="btn btn-default">
							<i class="fa fa-circle"></i><i class="fa fa-circle-o"></i>
							<img src="/images/windowStickers/outside-small.jpg">
							<input type="radio" name="searchCriteria[roomType]" value="dorm">
							<div class="bold">Back Adhesive</div>
							<div>(to stick on the outside)</div>
						</label>
					</div>
				</div>
			</div>

			<div class="form-group">
				<label for="insideOutside" class="control-label col-md-3">Sticker Size</label>
				<div class="col-md-9 fancyRadioButtons">
					<div class="btn-group" data-toggle="buttons">
						<label class="btn btn-default">
							<i class="fa fa-circle"></i><i class="fa fa-circle"></i>
							<img src="/images/windowStickers/outside-large.jpg">
							<input type="radio" name="searchCriteria[roomType]" value="dorm">
							<div class="bold">Large</div>
							<div>(4.5 in. / 11 cm)</div>
						</label>
						<label class="btn btn-default">
							<i class="fa fa-circle"></i><i class="fa fa-circle"></i>
							<img src="/images/windowStickers/outside-small.jpg">
							<input type="radio" name="searchCriteria[roomType]" value="private">
							<div class="bold">Small</div>
							<div>(3.5 in. / 9 cm)</div>
						</label>
					</div>
				</div>
			</div>


		</form>
		@include('Lib/formHandler/doEverything', [ 'showTitles' => false, 'returnToURL' => $listingEditHandler->returnToWhenDone, 'horizontalForm' => true ])

	@else
		@include('Lib/formHandler/doEverything', [ 'showTitles' => false, 'returnToURL' => $listingEditHandler->returnToWhenDone, 'horizontalForm' => true ])
	@endif

@elseif (in_array($listingEditHandler->action, [ 'basicInfo', 'features' ]))
	@if (Lang::has('ListingEditHandler.'.$listingEditHandler->action.'.pageDescription'))
		<p class="">@langGet('ListingEditHandler.'.$listingEditHandler->action.'.pageDescription')</p>
		<div role="alert" class="alert alert-info">@langGet('ListingEditHandler.uniqueText')</div>
	@endif

	@include('Lib/formHandler/doEverything', [ 'showTitles' => false, 'returnToURL' => $listingEditHandler->returnToWhenDone, 'horizontalForm' => true ])

@elseif (in_array($listingEditHandler->action, [ 'description', 'location' ]))

	<p class="">@langGet('ListingEditHandler.'.$listingEditHandler->action.'.pageDescription')</p>
	<div role="alert" class="alert alert-info">@langGet('ListingEditHandler.uniqueText')</div>

	<form method="post">
		<input type="hidden" name="_token" value="{!! csrf_token() !!}">

		@foreach (\App\Models\Languages::allLiveSiteCodes() as $language)
			<section class="contentBox pb-4">
				<h2 class="contentBoxTitle h3">
					<a>
						{!! langGet("LanguageNames.translated.$language") !!}
						@if ($language == 'en')
							@langGet('global.required')
						@else
							@langGet('global.optional')
						@endif
						@if (@$texts[$language] == '')
							<i class="fa fa-caret-down"></i>
						@endif
					</a>
				</h2>
				<div class="contentBoxContent @if ($language != 'en' && @$texts[$language] == '') contentBoxClosed @endif ">
					@if (@$texts[$language] != '' && strlen($texts[$language]) < $minPreferredLength)
						<div class="alert alert-danger">The text should be at least {!! $minPreferredLength !!}
							characters in length.
						</div>
					@endif
					{{-- we might want to disable copy-pasting to the textarea with onpaste="return false;" --}}
					<textarea minlength="{!! $minPreferredLength !!}" class="form-control"
					          name="texts[{!! $language !!}]"
					          placeholder="The text should be at least {!! $minPreferredLength !!} characters in length."
					          rows={!! $listingEditHandler->action == 'description' ? 20 : 10 !!}>{{{ @$texts[$language] }}}</textarea>
				</div>
			</section>
		@endforeach
		<div role="alert" class="alert alert-info">@langGet('ListingEditHandler.NotAllowedText')</div>

		<button class="btn btn-success btn-lg" type="submit">@langGet('global.Save')</button>

	</form>

@elseif ($listingEditHandler->action == 'mapLocation')

	<p class="pb-5">
		@langGet('ListingEditHandler.mapLocation.clickOrDrag')
		@langGet('ListingEditHandler.mapLocation.zoomInToPlace')
	</p>

	<form class="form-horizontal" role="form">
		<div class="form-group">
			<label for="gotoAddressAddress"
			       class="col-sm-2 control-label font-weight-600">@langGet('ListingEditHandler.mapLocation.findAddress')</label>
			<div class="col-sm-4 pb-5">
				<input class="form-control" id="gotoAddressAddress"
				       placeholder="@langGet('ListingEditHandler.mapLocation.findAddress')">
			</div>
			<button class="btn btn-primary"
			        id="gotoAddress">@langGet('ListingEditHandler.mapLocation.FindOnMap')</button>
		</div>
	</form>

	<div class="row">
		<div class="col-md-8">
			<p>
			<div id="mapCanvas" style="height:500px; border: 1px solid black; margin: 16px 0"></div>
			</p>
		</div>
	</div>

	<form method="post" id="mappingForm" action="/{!! Request::path() !!}">
		<input type="hidden" name="_token" value="{!! csrf_token() !!}">
		{{-- the javascript code will fill in hidden input fields on form submit --}}
		<p>
			<button class="btn btn-primary" id="submitMap" type="submit">@langGet('global.Save')</button>
		</p>
	</form>

	<p><a href="?reset=1">@langGet('ListingEditHandler.mapLocation.cancelAndReset')</a></p>

	{{-- Note: related javscript is at the bottom of this template --}}

@elseif ($listingEditHandler->action == 'pics')

	@if ($fileList->list->count() && $fileList->list->count() < \App\Models\Listing\Listing::MIN_PIC_COUNT_PREFERRED)
		<div class="alert alert-warning"><strong>Note: You have not yet uploaded enough photos for us to use them in
				your listing.</strong> We require a <strong>minimum
				of {!! \App\Models\Listing\Listing::MIN_PIC_COUNT_PREFERRED !!} photos</strong>, otherwise the
			system
			may use other photos imported from other websites instead of the photos you upload here.
		</div>
	@elseif ($fileList->list->count() > 1)
		<p><strong>The "primary photo" will be used as the small "thumbnail" image in your city's page.</strong></p>
	@endif

	@include('Lib/fileListHandler', [ 'fileListMode' => 'photos' ])

	<h3>Upload New Photos</h3>

	@include('Lib/fileUploadHandler')

@elseif ($listingEditHandler->action == 'panoramas')

	@include('Lib/fileListHandler', [ 'fileListMode' => 'photos' ])

	<br>
	<h3>Upload New Panorama Photos</h3>

	@include('Lib/fileUploadHandler')

@elseif ($listingEditHandler->action == 'video')

	@if ($status == 'success')
		<div class="alert alert-success"><span
					class="glyphicon glyphicon-ok"></span> @langGet('ListingEditHandler.video.VideoAdded')</div>
	@endif

	@if ($listingEditHandler->listing->videoEmbedHTML != '')
		<h3>@langGet('ListingEditHandler.video.CurrentVideo')</h3>
		{!! $listingEditHandler->listing->videoEmbedHTML !!}
		<hr><br>
	@endif

	<p>@langGet('ListingEditHandler.video.VideoInstructions')</p>
	<br>

	<form method="post" role="form">
		<input type="hidden" name="_token" value="{!! csrf_token() !!}">

		@if ($status == 'extractionError')
			<div class="alert alert-danger">@langGet('ListingEditHandler.video.SorryUnableToFindVideo')</div>
		@elseif ($status == 'videoRemoved')
			<div class="alert alert-info">Video removed.</div>
		@endif

		<div class="form-group">
			<label for="videoURL" style="padding-right: 0">@langGet('ListingEditHandler.video.SubmitNewVideo')</label>
			<div class="row">
				<div class="col-md-9">
					<input class="form-control" id="videoURL" type="url" name="videoURL"
					       value="{{{ Request::input('videoURL') }}}">
				</div>
			</div>
		</div>

		<button class="btn btn-primary" type="submit" name="submitVideoURL" value=1>@langGet('global.Submit')</button>

		@if($listing->videoURL)
			<button class="btn btn-danger" type="submit" name="removeVideoURL"
			        value=1>@langGet('global.Remove')</button>
		@endif

		@if ($status === 'getSchemaError')
			<div class="alert alert-danger" style="margin-top: 16px;">Could not get schema.</div>
		@elseif ($status == 'schemaSuccess')
			<div class="alert alert-success" style="margin-top: 16px;">The schema has been added to your listing.</div>
		@endif

		{{--        <div class="form-group" style="margin-top: 16px;">
					<label for="videoID" style="padding-right: 0">videoID</label>
					<div class="row">
						<div class="col-md-9">
							<input class="form-control" id="videoID" type="text" name="videoID" value="{{{ Request::input('videoID') }}}">
							<code class="small">videoID = EiAwGWqahdw from https://www.youtube.com/watch?v=EiAwGWqahdw or https://youtu.be/EiAwGWqahdw</code>
						</div>
					</div>
				</div>
				<button class="btn btn-primary" type="submit" name="submitVideoSchema" value=2>Add Video Schema</button>--}}
	</form>

@elseif ($listingEditHandler->action == 'backlink')

	@if ($status == 'success')
		<div class="alert alert-success"><span
					class="glyphicon glyphicon-ok"></span> @langGet('ListingEditHandler.backlink.BacklinkVerified')
		</div>
	@endif

	@if ($listingEditHandler->listing->mgmtBacklink != '')
		<h3>@langGet('ListingEditHandler.backlink.CurrentLink')</h3>
		{!! $listingEditHandler->listing->mgmtBacklink !!}
		<hr>
	@endif

	<p>@langGet('ListingEditHandler.backlink.BacklinkInfo')</p>
	<p><b>@langGet('ListingEditHandler.backlink.BacklinkExample')</b></p>
	<pre class="mb-3 bg-light p-3 rounded">&lt;a href="{!! $listingEditHandler->listing->getURL('publicSite', 'en', true) !!}"&gt;{{{ $listingEditHandler->listing->name }}}&lt;/a&gt;</pre>

	<p>@langGet('ListingEditHandler.backlink.PleaseEnterURL')</p>

	<form method="post" role="form">
		<input type="hidden" name="_token" value="{!! csrf_token() !!}">

		@if ($status == 'invalidURL')
			<div class="alert alert-danger"><i
						class="fa fa-exclamation-circle"></i> @langGet('ListingEditHandler.backlink.SorryUnableToFindBacklink')
			</div>
		@elseif ($status == 'ourURL')
			<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Please enter the URL of the page on
				<b>your</b> website that contains a link back to www.hostelz.com.
			</div>
		@elseif ($status == 'pageError')
			<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Couldn't load the web page (could
				be the wrong address).
			</div>
		@elseif ($status == 'linkNotFound')
			<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> A link to http://www.hostelz.com
				wasn't found on the page at that address.
			</div>
		@endif

		<div class="pb-5">
			<label for="backlinkURL" class="font-weight-600">@langGet('ListingEditHandler.backlink.UrlOfPage')</label>
			<div class="row">
				<div class="col-md-9">
					<input class="form-control" placeholder="Add your website" id="backlinkURL" type="url"
					       name="backlinkURL" value="{{{ Request::input('backlinkURL') }}}" required>
				</div>
			</div>
		</div>

		<button class="btn btn-primary" type="submit">@langGet('global.Submit')</button>
	</form>

@elseif ($listingEditHandler->action == 'ratings')

	@if ($formHandler->mode == 'list')

		@if ($listingEditHandler->listing->reviews()->count())
			<div class="row form-block">
				<div class="col-lg-4">
					<h3>Official Hostelz Review</h3>
				</div>
				<div class="col-lg-7 ml-auto">
					<p class="alert alert-success tx-body">@include('partials.svg-icon', ['svg_id' => 'verify-woman-user', 'svg_w' => '24', 'svg_h' => '24'])
						Congrats, your listing received an official Hostelz.com review.</p>
					<p>You can read <a href="reviews">the official Hostelz.com review here</a>. You can also reply
						officially as the owner and manager.</p>
				</div>
			</div>
		@endif

		<div class="row form-block">
			<div class="col-lg-4">
				<h3>User Reviews and Ratings</h3>
			</div>
			<div class="col-lg-7 ml-auto">
				@if ($formHandler->list->isEmpty())
					<div class="well">No reviews yet.</div>
				@else
					<p>@langGet('ListingEditHandler.ratings.pageDescription')</p>
				@endif
			</div>
			<div class="container pt-5 w-100">
				@if (!$formHandler->list->isEmpty())
					@include('Lib/formHandler/doEverything', [ 'showTitles' => false, 'returnToURL' => $listingEditHandler->returnToWhenDone, 'horizontalForm' => true ])
				@endif
			</div>
		</div>

		<div class="row form-block">
			<div class="col-lg-4">
				<h3>"{{{ $listingEditHandler->listing->name }}}" current Hostelz.com Overall Score:</h3>
				<div class="hostel-card-rating mb-5">
					<span class="combinedRating">@if ($listingEditHandler->listing->combinedRating <= 0)
							(Not yet scored.)
						@else
							{!! $listingEditHandler->listing->formatCombinedRating() !!}
						@endif </span>
				</div>
				<p><b>The Hostelz Score Key:</b></p>
				<p class="alert alert-success">@langGet('listingDisplay.combinedRatingScores.Score9')</p>
				<p class="alert alert-warning">@langGet('listingDisplay.combinedRatingScores.Score8')</p>
				<p class="alert alert-danger">@langGet('listingDisplay.combinedRatingScores.Score6')</p>
			</div>
			<div class="col-lg-7 ml-auto">
				<h3>How is the score calcuted?</h3>
				<p>@langGet('ListingEditHandler.ratings.HowScoreCalculated')</p>
				<p>@langGet('ListingEditHandler.ratings.WhenScoreUpdated')</p>
				<h3>@langGet('ListingEditHandler.ratings.TipToImprove')</h3>
				<p>@langGet('ListingEditHandler.ratings.ToHaveHighRating')</p>
			</div>
		</div>

	@elseif ($formHandler->mode == 'updateForm')
		<div class="well">You can use this form to add an official "response from the owner" if you wish. <b>Your
				response will appear on the website</b> just after the user's review. This allows you to respond to
			reviews from users to provide your side of the story or to point out any misinformation in the review.
		</div>
		@include('Lib/formHandler/doEverything', [ 'showTitles' => false, 'returnToURL' => $listingEditHandler->returnToWhenDone, 'horizontalForm' => true ])
		<div class="well pt-5">Note: Your response will be live on the website as soon as you click Submit (but you can
			always edit it again later).
		</div>
	@endif

@elseif ($listingEditHandler->action == 'reviews')

	@if ($formHandler->mode == 'list')

		<div class="row">
			<div class="col-md-12">

				@if ($formHandler->list->isEmpty())

					<div class="well">No reviews yet.</div>

				@else

					<p>@langGet('ListingEditHandler.reviews.pageDescription')</p>
					@include('Lib/formHandler/doEverything', [ 'showTitles' => false, 'returnToURL' => $listingEditHandler->returnToWhenDone, 'horizontalForm' => true ])

				@endif

			</div>

		</div>

	@elseif ($formHandler->mode == 'updateForm')

		<div class="well">You can use this form to add an official "response from the owner" if you wish. <b>Your
				response will appear on the website</b> just after the review. This allows you to respond to reviews to
			provide your side of the story or to point out any misinformation in the review.
		</div>

		@include('Lib/formHandler/doEverything', [ 'showTitles' => false, 'returnToURL' => $listingEditHandler->returnToWhenDone, 'horizontalForm' => true ])

		<br>
		<div class="well pt-5">Note: Your response will be live on the website as soon as you click Submit (but you can
			always edit it again later).
		</div>
	@endif

@else

        <?php throw new \Exception("Unknown action '" . $listingEditHandler->action . "'."); ?>

@endif



@section('pageBottom')

	@if ($listingEditHandler->action == 'sticker')
		<script type="text/javascript">
            $(document).ready(function () {

            });
		</script>

	@elseif ($listingEditHandler->action == 'mapLocation')

		<script src="https://maps.googleapis.com/maps/api/js?key={!! urlencode(config('custom.googleApiKey.clientSide')) !!}"></script>

		<script type="text/javascript">
            $(document).ready(function () {

                var listingPoint = new google.maps.LatLng({!! $latitude !!}, {!! $longitude !!});

                var map = new google.maps.Map(document.getElementById("mapCanvas"), {
                    zoom: 12,
                    center: listingPoint,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    scaleControl: true,
                    streetViewControl: false,
                    minZoom: 3,
                    maxZoom: 19,
                });

                var marker = new google.maps.Marker({position: listingPoint, map: map, draggable: true});

				{{-- Move the listingPoint to the center if it isn't visible (have to use an event because the map's bounds aren't immediately known at first) --}}
				{{-- (it may not be visible if the geocoding changed, or just if the user saved the map panned away from the marker) --}}
                var didBoundsCheck = false;
                google.maps.event.addListenerOnce(map, 'bounds_changed', function () {
                    if (!didBoundsCheck) {
                        didBoundsCheck = true;
                        if (!map.getBounds().contains(listingPoint)) map.setCenter(listingPoint);
                    }
                });

				{{-- Allow clicking to place the marker. --}}
                google.maps.event.addListener(map, "click", function (event) {
                    marker.setPosition(event.latLng);
                });

                $("button#submitMap").click(function () {
                    $('#mappingForm').children('input').not('[name=_token]').remove();
					{{-- Add hidden input elements of all the values we want to submit. --}}
                    var point = marker.getPosition();
                    $('<input>').attr({type: 'hidden', name: 'latitude', value: point.lat()}).appendTo('#mappingForm');
                    $('<input>').attr({type: 'hidden', name: 'longitude', value: point.lng()}).appendTo('#mappingForm');
                });

                $("button#gotoAddress").click(function (event) {
                    event.preventDefault();
                    var address = $('input#gotoAddressAddress').val();
                    var geocoder = new google.maps.Geocoder();
                    geocoder.geocode({'address': address}, function (results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            map.setCenter(results[0].geometry.location);
                            marker.setPosition(results[0].geometry.location);
                        } else {
                            alert("{{{ langGet('ListingEditHandler.mapLocation.CantGeocode') }}}");
                        }
                    });
                });

            });

		</script>
	@elseif ($listingEditHandler->action == 'features')
		<script>

            $(document).ready(function ($) {
                //  good for
                var goodForCheckboxes = $('input[name="data[goodFor][]"]');

                goodForCheckboxes.click(function (event) {
                    //  if checked -> not check
                    if (!$(this).prop("checked")) {
                        return true;
                    }

                    var val = $(this).val();

                    if ((val === 'families' || val === 'quiet') && goodForCheckboxes.filter('[value="partying"]:checked').length) {
                        alert('You cannot select both ' + val.toUpperCase() + ' and PARTY hostel for your property. Please select one type.');
                        event.preventDefault();
                        return false;
                    }

                    if (val === 'partying' && (goodForCheckboxes.filter('[value="families"]:checked').length || goodForCheckboxes.filter('[value="quiet"]:checked').length)) {
                        alert('You cannot select both ' + val.toUpperCase() + ' and FAMILIES/QUIET hostel for your property. Please select one type.');
                        event.preventDefault();
                        return false;
                    }
                });
            });

		</script>

	@endif

	@parent
@stop