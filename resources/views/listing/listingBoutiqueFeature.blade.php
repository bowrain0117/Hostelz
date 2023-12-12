{{--Boutique Feature--}}
	@if(isset($listing->boutiqueHostel) && $listing->boutiqueHostel === 1)
		<div class="shadow-1 rounded p-3 p-sm-4 ml-sm-n3 mr-sm-n3 mr-lg-0 mb-5" id="boutiquehostel">
        	<div class="d-flex justify-content-between align-items-center">
        	    <h3 class="font-weight-600 my-2 h4">A True Boutique Hostel in {!! $cityInfo->city !!}</h3>
	        </div>
	        <div class="tab-content pt-3 mt-3 mb-4 border-top " style="display: block;">
            	<p>{{{ $listing->name }}}, {!! $cityInfo->city !!}</p>
            </div>
            <div class="text-center">
            <div class="icon-rounded bg-warning mx-1 mx-md-1" data-toggle="tooltip" data-placement="top" title="" style="background: #ee8986 !important;" dataoriginal-title="Boutique Hostel in CITY">
        		<span class="w-1rem h-1rem text-white"><i class="fas fa-gem"></i></span>
			</div>
			</div>
    	</div>
    @endif