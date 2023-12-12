@php
	$fieldInfo['dynamicMethod'] = $fieldInfo['dynamicMethod'] ?? '';
@endphp

{{-- Dynamic --}}

@if (isset($fieldInfo['dynamicGroup']) && $fieldInfo['dynamicMethod'] !== 'server')
	@if (!isset($fhUsingClientSideDynamic) || !$fhUsingClientSideDynamic)
		<?php $fhUsingClientSideDynamic = true; ?>
		<script> var fhUsingClientSideDynamic = true; </script> {{-- tells formHandler.js that we need the related JS code --}}
	@endif

	<span data-dynamic-group="{!! $fieldInfo['dynamicGroup'] !!}"
		  data-dynamic-group-values="{!! $fieldInfo['dynamicGroupValues'] !!}"
		  data-dynamic-method="{!! $fieldInfo['dynamicMethod'] !!}"
		  style="display: none" {{-- temporary, just so it doesnt flash up all of the elements when the page loads --}}
	>
@endif

	{{ $slot }}

@if (isset($fieldInfo['dynamicGroup']) && $fieldInfo['dynamicMethod'] !== 'server')
	</span>
@endif
