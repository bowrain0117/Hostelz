@php
	use Lib\FormHandler;

	/** @var FormHandler $formHandler */
@endphp

{{--

Parameters:
    
    $pageType
    $fieldName
    $fieldInfo
    $type
    $varName
    $value
    $errors

--}}

@if ($type === 'tabs')

	<input type="hidden" name="{!! $varName !!}" value="{{{ $value }}}">
	<ul class="nav nav-tabs formTabs">
		@foreach ($fieldInfo['options'] as $tab)
			<li @if ($value == $tab) class="active" @endif>
				<a href="#{{{ $tab }}}" data-toggle="tab">
					{!! $formHandler->getLanguageText('tabs', $fieldName, $pageType, $tab) !!}
				</a>
			</li>
		@endforeach
	</ul>

@elseif (in_array($type, [ '', 'text', 'password', 'email', 'url', 'number' ]))

	@if ($pageType === 'search' && in_array($type, [ 'email', 'url' ]))
		{{-- For the search page we want people to still be able to enter partial or invalid values that they just want to search by. --}}
            <?php $typeAttribute = 'text'; ?>
	@elseif ($type != '')
            <?php $typeAttribute = $type; ?>
	@else
            <?php $typeAttribute = 'text'; ?>
	@endif

	@if (!empty($fieldInfo['inputGroupPrefix']) || !empty($fieldInfo['inputGroupSuffix']))
		<div class="form-group">
			@endif

			@if (!empty($fieldInfo['inputGroupPrefix']))
				<span class=" input-group-addon">{!! $fieldInfo['inputGroupPrefix'] !!}</span>
			@endif

			<input
					class="form-control"
					name="{!! $varName !!}"
					value="{{ $formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, true) }}"
					type="{!! $typeAttribute !!}"

					@if (($temp = $formHandler->getLanguageText('popover', $fieldName, $pageType, null, '')) != '')
						data-toggle="popover" data-content="{{ $temp }}"
					@endif

					@if (($temp = $formHandler->getLanguageText('placeholder', $fieldName, $pageType, null, '')) != '')
						placeholder="{{ $temp }}"
					@endif

					@if (!empty($fieldInfo['size']))
						size="{{ $fieldInfo['size'] }}"
					@endif

					@if (!empty($fieldInfo['maxLength']))
						maxlength="{{ $fieldInfo['maxLength'] }}"
					@else
						maxlength="{{ $formHandler->defaultMaxLength }}"
					@endif

					@if ($formHandler->isNotSearchForm() && $formHandler->isRequiredField($fieldInfo))
						required
					@endif
			>

			@if (!empty($fieldInfo['inputGroupSuffix']))
				<span class="input-group-addon">{!! $fieldInfo['inputGroupSuffix'] !!}</span>
			@endif

			@if (!empty($fieldInfo['inputGroupPrefix']) || !empty($fieldInfo['inputGroupSuffix']))
		</div>
	@endif

@elseif ($type === 'hidden')

	{{-- This is useful for things like supplying some of the data[] variables in the URL on an insert form,
	which needs the data in a hidden element in case the validation of the form fails and we have to re-display the form again. --}}
	<input type="hidden" name="{!! $varName !!}"
	       value="{{{ $formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, true) }}}">

@elseif ($type === 'display')

	<p class="form-control-static">
		@if (@$fieldInfo['unescaped'])
			{!! $formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, true) !!}
		@else
			{!! nl2br(htmlentities($formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, true))) !!}
		@endif
	</p>

@elseif ($type === 'datePicker')

        <?php
        Lib\HttpAsset::requireAsset('jquery-ui');
        if (! isset($GLOBALS['fhDatePickerCount'])) {
            $GLOBALS['fhDatePickerCount'] = 0;
        } else {
            $GLOBALS['fhDatePickerCount']++;
        }
        ?>

	<script>
        if (typeof fhDatePickers === 'undefined') var fhDatePickers = {};
        fhDatePickers[{!! $GLOBALS['fhDatePickerCount'] !!}] = {
            'showAnim': '',
            'dateFormat': 'yy-mm-dd',
            'minDate': '{{{ @$fieldInfo['minDate'] }}}',
            'maxDate': '{{{ @$fieldInfo['maxDate'] }}}',
            'defaultDate': '{{{ @$fieldInfo['defaultDate'] }}}'
        };
	</script>

	@if ($formHandler->mode === 'searchForm' || $formHandler->mode === 'searchAndList')

            <?php $formattedArray = $formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, false); ?>

		<script>
            fhDatePickers[{!! $GLOBALS['fhDatePickerCount'] !!}]['numberOfMonths'] = 3;
            fhDatePickers[{!! $GLOBALS['fhDatePickerCount']+1 !!}] = fhDatePickers[{!! $GLOBALS['fhDatePickerCount']++ !!}];
            fhDatePickers[{!! $GLOBALS['fhDatePickerCount']-1 !!}]['onClose'] = function (selectedDate) {
                $('#fhDatepicker{!! $GLOBALS['fhDatePickerCount'] !!}').datepicker("option", "minDate", selectedDate);
            };
		</script>

		<div class="row">
			<div class="col-md-6">
				<div class="input-group">
					<span class="input-group-addon">From: {!! $fieldInfo['inputGroupPrefix'] ?? '' !!}</span>
					<input type="text" id="fhDatepicker{!! $GLOBALS['fhDatePickerCount']-1 !!}"
					       name="{{{ $varName }}}[min]" value="{{{ @$formattedArray['min'] }}}" class="form-control">
				</div>
			</div>
			<div class="col-md-6" style="padding-left: 0">
				<div class="input-group">
					<span class="input-group-addon">To: {!! $fieldInfo['inputGroupPrefix'] ?? '' !!}</span>
					<input type="text" id="fhDatepicker{!! $GLOBALS['fhDatePickerCount'] !!}"
					       name="{{{ $varName }}}[max]" value="{{{ @$formattedArray['max'] }}}" class="form-control">
				</div>
			</div>
		</div>

	@else

		<input
				type="text"
				class="form-control"
				id="fhDatepicker{!! $GLOBALS['fhDatePickerCount'] !!}"
				name="{{{ $varName }}}"
				value="{{{ $formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType) }}}"

				@if (($temp = $formHandler->getLanguageText('placeholder', $fieldName, $pageType, null, '')) != '')
					placeholder="{{{ $temp }}}"
				@endif

				@if ($formHandler->isNotSearchForm() && $formHandler->isRequiredField($fieldInfo))
					required
				@endif
		>

	@endif

@elseif ($type === 'minMax' && ($formHandler->mode === 'searchForm' || $formHandler->mode === 'searchAndList'))

        <?php $formattedArray = $formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, false); ?>
	<div class="row">
		<div class="col-md-6">
			<div class="input-group">
				<span class="input-group-addon">Min: {!! $fieldInfo['inputGroupPrefix'] ?? '' !!}</span>
				<input type="text" class="form-control" name="{!! $varName !!}[min]"
				       value="{{{ $formattedArray['min'] ?? '' }}}">
				@if (!empty($fieldInfo['inputGroupSuffix']))
					<span class="input-group-addon">{!! $fieldInfo['inputGroupSuffix'] !!}</span>
				@endif
			</div>
		</div>
		<div class="col-md-6" style="padding-left: 0">
			<div class="input-group">
				<span class="input-group-addon">Max: {!! $fieldInfo['inputGroupPrefix'] ?? '' !!}</span>
				<input type="text" class="form-control" name="{!! $varName !!}[max]"
				       value="{{{ $formattedArray['max'] ?? '' }}}">
				@if (!empty($fieldInfo['inputGroupSuffix']))
					<span class="input-group-addon">{!! $fieldInfo['inputGroupSuffix'] !!}</span>
				@endif
			</div>
		</div>
	</div>

@elseif ($type === 'textarea')

	<textarea class="form-control" name="{!! $varName !!}"
	          @if (($temp = $formHandler->getLanguageText('popover', $fieldName, $pageType, null, '')) != '') data-toggle="popover"
	          data-content="{{{ $temp }}}" @endif
	          @if (($temp = $formHandler->getLanguageText('placeholder', $fieldName, $pageType, null, '')) != '') placeholder="{{{ $temp }}}"
	          @endif
	          @if (!empty($fieldInfo['rows'])) rows="{{{ $fieldInfo['rows'] }}}" @else rows="10" @endif
    >{{{ $formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, true) }}}</textarea>

@elseif ($type === 'WYSIWYG')

        <?php
        Lib\HttpAsset::requireAsset('formHandler-WYSIWYG.js');
        ?>

	<div>{{-- a hack to restore the missing right border because of conflicting box-sizing models --}}
		<textarea name="{!! $varName !!}" class="wysiwyg"
		          @if ($formHandler->getLanguageText('popover', $fieldName, $pageType, null, '') != '') data-toggle="popover"
		          data-content="{{{ $formHandler->getLanguageText('popover', $fieldName, $pageType) }}}" @endif
		          @if (!empty($fieldInfo['rows'])) rows="{{{ $fieldInfo['rows'] }}}" @else rows="10" @endif
        >{{{ $formHandler->formatValueForDisplay(nl2p($value), $fieldName, $fieldInfo, $pageType, true) }}}</textarea>
	</div>

@elseif ($type === 'select')

	<select class="form-control" name="{!! $varName !!}">
		@if (!isset($fieldInfo['options']['']) && @$fieldInfo['showBlankOption'] !== false)
			<option value=""></option>
		@endif
		@isset($fieldInfo['options'])
			@foreach ($fieldInfo['options'] as $key => $option)
				<option value="{{{ $option }}}" @selected((is_a($value, 'BackedEnum') ? $value->value : $value) == $option) >
					{{{ $formHandler->getDisplayTextForOption($fieldName, $fieldInfo, $option, $key) }}}
				</option>
			@endforeach
		@endisset
	</select>

@elseif ($type === 'select-key')

	<select class="form-control" name="{!! $varName !!}">
		@if (!isset($fieldInfo['options']['']) && @$fieldInfo['showBlankOption'] !== false)
			<option value=""></option>
		@endif
		@if (@$fieldInfo['options'])
			@foreach ($fieldInfo['options'] as $key => $option)
				<option value="{{{ $key }}}" @if ((string) $value === (string) $key) SELECTED @endif>
					{{{ $formHandler->getDisplayTextForOption($fieldName, $fieldInfo, $option, $key) }}}
				</option>
			@endforeach
		@endif
	</select>

@elseif ($type === 'checkbox')

	@if (in_array($formHandler->mode, [ 'updateForm', 'insertForm', 'editableList' ]))
		{{-- we need this if no boxes were checked when submitting an update then
		the variable at least still exists so the code will set the database value to "".
		Note that this relies on the behavior that later input variables will overwrite earlier ones. --}}
		<input type="hidden" name="{!! $varName !!}" value="">
	@endif

	<div class="checkbox">
		<label>
			<input type="checkbox" name="{!! $varName !!}" value="{{{ $fieldInfo['value'] }}}"
			       @if ($value == $fieldInfo['value']) CHECKED @endif>
			{{{ $formHandler->getLanguageText('checkbox', $fieldName, $pageType) }}}
		</label>
	</div>

@elseif ($type === 'checkboxes')

	@if (in_array($formHandler->mode, [ 'updateForm', 'insertForm', 'editableList' ]))
		{{-- we need this if no boxes were checked when submitting an update then
		the variable at least still exists so the code will set the database value to "" --}}
		<input type="hidden" name="{!! $varName !!}[]" value="">
	@endif

	<div class="checkboxes">
		@if ($formHandler->mode === 'searchForm' || $formHandler->mode === 'searchAndList')
			<div class="checkbox searchAll">
				<label>
					<input type="checkbox" class="searchAll" @if (!$value) CHECKED @endif> <strong>Search All</strong>
				</label>
			</div>
		@endif
		@foreach ($fieldInfo['options'] as $key=>$option)
			@if (empty($fieldInfo['optionCounts']) || !empty($fieldInfo['optionCounts'][$option]))
				<div class="checkbox">
					<label>
						<input type="checkbox" name="{!! $varName !!}[]" value="{{{ $option }}}"
						       @if (is_array($value) && in_array($option, $value)) CHECKED @endif>
						{{{ $formHandler->getDisplayTextForOption($fieldName, $fieldInfo, $option, $key) }}}
						@if (!empty($fieldInfo['optionCounts']))
							<span class="text-muted">({!! (int) $fieldInfo['optionCounts'][$option] !!})</span>
						@endif
					</label>
				</div>
			@endif
		@endforeach
	</div>
@elseif ($type === 'radio')

	<div class="radio">
            <?php $valueMatchFound = false ?>
		@foreach ($fieldInfo['options'] as $key => $option)
			@if (!@$fieldInfo['optionCounts'] || @$fieldInfo['optionCounts'][$option])
				<div>
					@if (isset($fieldInfo['lastOptionStringInput']) && $option === end($fieldInfo['options']))
						<label class="lastOptionStringInput">

							{{-- String Input Option --}}

							{{-- Note: If they select the radio button but don't enter a string, the value returned is the option key. --}}
							<input type="radio" name="{!! $varName !!}" value="{{{ $option }}}"
							       lastOptionStringInputDefaultValue="{{{ $option }}}"
							       @if ($value != '' && !$valueMatchFound) CHECKED @endif>
							{{{ $formHandler->getDisplayTextForOption($fieldName, $fieldInfo, $option, $key) }}}

							<span>
                                @if ($fieldInfo['lastOptionStringInput'] != '')
									&nbsp;&nbsp;<em>{{{ langGet($fieldInfo['lastOptionStringInput']) }}}</em>
								@endif
                                
                                <input {{-- Note: no "name" so no value gets submitted. Instead we update the radio value with jquery. --}}
                                       type="text" class="bg-light rounded border p-2"
                                       @if ($value != $key && !$valueMatchFound) value="{{{ $value }}}" @endif
                                       @if (@$fieldInfo['maxLength'] != '') maxlength="{{{ $fieldInfo['maxLength'] }}}"
                                       @else maxlength="{{{ $formHandler->defaultMaxLength }}}" @endif
                                       @if (@$fieldInfo['size'] != '')
	                                       size="{{{ $fieldInfo['size'] }}}"
                                       @elseif (@$fieldInfo['maxLength'] && $fieldInfo['maxLength'] < $formHandler->defaultMaxLength)
	                                       size="{{{ $fieldInfo['maxLength'] }}}"
                                       @else
	                                       size="20"
                                    @endif
                                >
                            </span>

						</label>
					@else
						<label>
							<input type="radio" name="{!! $varName !!}" value="{{{ $option }}}"
							       @if ($option == $value) CHECKED <?php $valueMatchFound = true ?> @endif>
							{{{ $formHandler->getDisplayTextForOption($fieldName, $fieldInfo, $option, $key) }}}
							@if (@$fieldInfo['optionCounts'])
								<span class="text-muted">({!! (int) $fieldInfo['optionCounts'][$option] !!})</span>
							@endif

						</label>
					@endif
				</div>
			@endif
		@endforeach

	</div>

@elseif ($type === 'multi')

	@if (!@$fhUsingMulti)
            <?php $fhUsingMulti = true; ?>
		<script> var fhUsingMulti = true; </script> {{-- tells formHandler.js that we need the related JS code --}}
	@endif

	@if ($formHandler->mode === 'display' && !$noArrayPlaceholder)
		{{-- we need this if no boxes were checked when submitting an update then
		the d[] variable at least still exists so the code will set the database value to "" --}}
		<input type="hidden" name="{!! $varName !!}[]" value="">
	@endif

	<table class="fhMulti" @if (!@$fieldInfo['size'] && @!$fieldInfo['keySize']) style="width: 100%" @endif>
            <?php

            //  todo
            if (! is_array($value)) {
                $value = [$value];
            }

            if (! $value) {
                $value[''] = '';
            } // Display a blank one so it's more obvious to the user how to start entering items

            if ($fieldInfo['showAddButton'] ?? true) {
                $value[] = null; // Add a special one to use as a template for adding new key/value pairs
            }
            ?>

		@foreach ($value as $k => $v)
			@if ($v === null)
				<tr>
					<td><a class="fhMulti_add text-sm 12 ml-2" href="#"><i class="fas fa-plus-circle"></i> Add</a>
				<tr class="fhMulti_template"> {{-- an invisible one to use as a template for adding new ones --}}
			@else
				<tr>
					@endif

					@if (@$fieldInfo['keys'] && is_array($fieldInfo['keys']))
						<td>
							<select class="fhMulti_key form-control">
								<option value=""></option>
								@foreach ($fieldInfo['keys'] as $key => $option)
									<option value="{{{ $option }}}"
									        @if ((string) $k === (string) $option) SELECTED @endif>
										{{{ $formHandler->getDisplayTextForOption($fieldName, $fieldInfo, $option, $key) }}}
									</option>
								@endforeach
							</select>
						</td>
					@elseif (@$fieldInfo['keys'] === '')
						<td>
							<input class="fhMulti_key form-control" value="@if ($v !== null){{{ $k }}}@endif"
							       @if (@$fieldInfo['keySize'] != '') size="{{{ $fieldInfo['keySize'] }}}" @endif
							       @if (@$fieldInfo['keyMaxLength'] != '') maxlength="{{{ $fieldInfo['keyMaxLength'] }}}"
							       @else maxlength="{{{ $formHandler->defaultMaxLength }}}" @endif
							       @if (($temp = $formHandler->getLanguageText('keyPlaceholder', $fieldName, $pageType, null, '')) != '') placeholder="{{{ $temp }}}" @endif
							>
							</div>
						</td>
					@endif

					<td>
						@if (@$fieldInfo['options'] && is_array($fieldInfo['options']))
							<select class="fhMulti_value form-control {{ $fieldInfo['itemClass'] ?? '' }}"
							        data-fh-var-name="{!! $varName !!}"
							        name="{!! $varName !!}[@if (isset($fieldInfo['keys'])){{{ $k }}}@endif]">
								<option value=""></option>
								@foreach ($fieldInfo['options'] as $key=>$option)
									<option value="{{{ $option }}}"
									        @if ((string) $v === (string) $option) SELECTED @endif>
										{{{ $formHandler->getDisplayTextForOption($fieldName, $fieldInfo, $option, $key) }}}
									</option>
								@endforeach
							</select>
						@else
							<input class="fhMulti_value form-control {{ $fieldInfo['itemClass'] ?? '' }}"
							       data-fh-var-name="{!! $varName !!}"
							       name="{!! $varName !!}[@if (isset($fieldInfo['keys'])){{{ $k }}}@endif]"
                                   <?php $formattedArray = $formHandler->formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, false); ?>
							       value="{{{ is_array($formattedArray) && array_key_exists($k, $formattedArray) ? $formattedArray[$k] : '' }}}"
							       @if (($temp = $formHandler->getLanguageText('popover', $fieldName, $pageType, null, '')) != '') data-toggle="popover"
							       data-content="{{{ $temp }}}" @endif
							       @if (($temp = $formHandler->getLanguageText('placeholder', $fieldName, $pageType, null, '')) != '') placeholder="{{{ $temp }}}"
							       @endif
							       @if (@$fieldInfo['size'] != '') size="{{{ $fieldInfo['size'] }}}" @endif
							       @if (@$fieldInfo['maxLength'] != '') maxlength="{{{ $fieldInfo['maxLength'] }}}"
							       @else maxlength="{{{ $formHandler->defaultMaxLength }}}" @endif
							       @if ($fieldInfo['disabled'] ?? false) disabled @endif
							>
					@endif

					@if($fieldInfo['showRemoveButton'] ?? true )
						<td><a href="#" class="fhMulti_remove ml-2"><i class="fa fa-times text-danger"
						                                               aria-hidden="true"></i></a>
					@endif
				</tr>
				@endforeach
	</table>

@endif


{{-- Errors --}}

@if (@$errors && $errors->has($fieldName))
	<div class="text-danger"><i class="fa fa-exclamation-circle"></i> {!! $errors->first($fieldName) !!}</div>
@endif

@if (@$fieldInfo['htmlAfter'] != '')
	{!! $fieldInfo['htmlAfter'] !!}
@endif
 
