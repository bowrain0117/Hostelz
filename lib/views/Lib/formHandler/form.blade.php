@php
	use Lib\FormHandler;

	/** @var FormHandler  $formHandler */
@endphp
{{--

Parameters:
    
    formHandler (required)
    horizontalForm - true/false (set to true for horizontal forms, need to also add "form-horizontal" class to the <form> element)
    formGroupClass
    optionColumns

--}}

<x-form-handler::form.top :$formHandler />

@php
	$horizontalForm = $horizontalForm ?? false;
@endphp

@foreach ($formHandler->fieldInfo as $fieldName => $fieldInfo)

	@php
        $pageType = $formHandler->determinePageType();
        $type = $formHandler->determineInputType($fieldName, $pageType);

        if ($type !== 'ignore') {
            if ($pageType === 'search') {
                $varName = $formHandler->searchVarName . '[' . $fieldName . ']';
                $value = $formHandler->getFieldValue($fieldName, true);
                if ($type === 'textarea' || $type === 'WYSIWYG') {
                    $type = 'text';
                }
            } else {
                $varName = $formHandler->inputDataVarName . '[' . $fieldName . ']';
                $value = $formHandler->getFieldValue($fieldName, true);
            }
        }
	@endphp

	{{-- Before Field --}}

	@isset ($fieldInfo['beforeField'])
		{!! $fieldInfo['beforeField'] !!}
	@endisset

	{{-- Columns --}}

	<x-form-handler::form.col>
		<x-form-handler::form.row :$fieldInfo :$horizontalForm >

			@if ($type === 'hidden')

				{{-- No label or anything, just the hidden input field. --}}
				@include('Lib/formHandler/inputField')

			@elseif ($type !== 'ignore')

				<x-form-handler::form.dynamic-group>

					<x-form-handler::form.field-div :$fieldInfo :$horizontalForm >

						@if (isset($fieldInfo['htmlBefore']) && $fieldInfo['htmlBefore'] !== '')
							{!! $fieldInfo['htmlBefore'] !!}
						@endif

						<x-form-handler::form.label :$formHandler :$fieldName :$pageType :$horizontalForm :$fieldInfo />

						@if (
							!$horizontalForm
							&& ($temp = $formHandler->getLanguageText('fieldDescription', $fieldName, $pageType, null, '')) !== ''
						)
							<p>{!! $temp !!}</p>
						@endif

						@if ( in_array($formHandler->mode, ['searchForm', 'searchAndList'])
							&& array_key_exists('comparisonTypeOptions', $fieldInfo)
						)
							<x-form-handler::form.comparison-start />
						@endif

						@if ($horizontalForm)
							<x-form-handler::form.horizontal-start :$formHandler :$fieldName :$pageType />
						@endif

							@include('Lib/formHandler/inputField', [ 'errors' => $formHandler->errors ])

						@if ($horizontalForm)
							<x-form-handler::form.horizontal-end />
						@endif

						@if (
							array_key_exists('comparisonTypeOptions', $fieldInfo)
							&& in_array($formHandler->mode, ['searchForm', 'searchAndList'])
						)
							<x-form-handler::form.comparison-end />
						@endif

					</x-form-handler::form.field-div>

				</x-form-handler::form.dynamic-group>

			@endif

		</x-form-handler::form.row>
	</x-form-handler::form.col>

	{{-- After Field --}}
	@isset($fieldInfo['afterField'])
		{!! $fieldInfo['afterField'] !!}
	@endisset

@endforeach

@section('pageBottom')

	@parent

@stop
