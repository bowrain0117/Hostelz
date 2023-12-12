@if (isset($fieldInfo['columns']) && $fieldInfo['columns'] === 'start')
	<div style="-webkit-column-count: {!! $fieldInfo['columnCount'] !!}; -moz-column-count: {!! $fieldInfo['columnCount'] !!}; column-count: {!! $fieldInfo['columnCount'] !!};">
@endif

		{{ $slot }}

@if (isset($fieldInfo['columns']) && $fieldInfo['columns'] === 'end')
	</div>
@endif