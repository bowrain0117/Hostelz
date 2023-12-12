{{-- Note: This currently only formats correctly if $horizontalForm is not true. --}}
<div class="row">
	<div class="col-sm-4">
		<select class="form-control" name="{!! $formHandler->comparisonTypesVarName !!}[{!! $fieldName !!}]">
			@foreach ($fieldInfo['comparisonTypeOptions'] as $optionName)
				<option value="{{{ $optionName }}}"
					@if (
						(
							$formHandler->comparisonTypes
							&& (
								isset($formHandler->comparisonTypes[$fieldName])
								&& $formHandler->comparisonTypes[$fieldName] === $optionName
							)
						)
						|| (!$formHandler->comparisonTypes && $formHandler->defaultComparisonType === $optionName)
					)
						SELECTED
					@endif
				>
					{!! $formHandler->getLanguageText('comparisonTypes', $fieldName, $pageType, $optionName, $optionName) !!}
				</option>
			@endforeach
		</select>
	</div>
	<div class="col-sm-8">