<div class="col-md-9">

	@if (
		($temp = $formHandler->getLanguageText('fieldDescription', $fieldName, $pageType, null, '')) !== ''
	)
		<p>{!! $temp !!}</p>
	@endif