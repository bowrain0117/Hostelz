@if (($temp = $formHandler->getLanguageText('fieldLabel', $fieldName, $pageType)) !== '')

    <label for="{{{ $fieldName }}}"
        @if ($horizontalForm)
            class="{{ $fieldInfo['label']['class'] ?? 'control-label col-md-3 font-weight-600' }}"
        @else
            class="{{ $fieldInfo['label']['class'] ?? 'formHandlerLabel control-label font-weight-600' }}"
        @endif
    >
        {!! $temp !!}

        @if ($formHandler->isNotSearchForm() && $formHandler->isRequiredField($fieldInfo))
            <span class="badge badge-primary ml-1">required</span>
        @endif

    </label>

@endif