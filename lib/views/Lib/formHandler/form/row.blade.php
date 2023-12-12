@php
	$fieldInfo['row'] = $fieldInfo['row'] ?? null;
@endphp

{{-- (Note: This needs to happen even if type is ignore or else the row open/close divs can get out of sync if a start or end row element is 'ignore' for some page types.) --}}
{{-- Note: Not used for horizontalForm because Bootstrap's "form-horizontal" class makes formGroup's act as rows. ("form-horizontal" class must be added to the <form> element). --}}
@if (
	!$horizontalForm
	&& ($fieldInfo['row'] === '' || $fieldInfo['row'] === 'start' || isset($fieldInfo['row']['start']))
	&& $fieldInfo['row'] !== 'skip'
)
	<div class="row noColumnBreak">
@endif

	{{-- Coll --}}
	@isset($fieldInfo['col'])
		<div class="{{ $fieldInfo['col'] }}">
	@endisset

		{{ $slot }}

	@isset ($fieldInfo['col'])
		</div>
	@endisset

@if (!$horizontalForm && ($fieldInfo['row'] === '' || $fieldInfo['row'] === 'end' || isset($fieldInfo['row']['end'])))
	</div>
@endif