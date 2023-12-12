<div
	@if (array_key_exists('formGroupClass', $fieldInfo))
		class="{!! $fieldInfo['formGroupClass'] !!}"
	@elseif (isset($formGroupClass))
		class="{!! $formGroupClass !!}"
	@elseif ($horizontalForm)
		class="form-group row"
	{{-- Note: .form-groups to behave as grid rows when using <form class="form-horizontal">. --}}
	@else
		class="form-group col-md-12"
	@endif

	@isset($fieldInfo['determinesDynamicGroup'])
		data-fh-determines-dynamic-group="{!! $fieldInfo['determinesDynamicGroup'] !!}"
	@endisset

	@isset($fieldInfo['submitFormOnChange'])
		fhSubmitFormOnChange="true"
	@endisset
>

	{{ $slot }}

</div>