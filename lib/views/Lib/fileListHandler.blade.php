{{--
Designed to work with Lib\FileListHandler.php.
Input:
    $fileList
    $fileListMode
    $fileListShowStatus (optional) - Set to true to show the status of the photos.
--}}

<style>
    .edit-in-progress input {
        width: 100%;
    }
</style>

@if ($fileList->list && !$fileList->list->isEmpty())
	@if ($fileList->makeSortableUsingNumberField)
		<div class="mb-3">{{{ langGet('fileList.dragPhotosToChangeOrder') }}}</div>
	@endif

	@if (@$fileListMode == 'photos')
		<div class="fileList row">
			@foreach ($fileList->list as $row)
				<div class="@if ($fileList->makeSortableUsingNumberField) col-md-4 @endif col-12 mb-5"
				     @if ($fileList->makeSortableUsingNumberField) id="sortOrder_{!! $row->id !!}" @endif>

					@if (@$fileListShowStatus)
						<div>Status: @langGet('Pic.forms.options.status.'.$row->status)</div>
					@endif

					@if ($fileList->viewLinkClosure)
						<a href="{!! $fileList->viewLinkClosure->__invoke($row) !!}">
							@endif

							<img src="{!! $row->url($fileList->picListSizeTypeNames) !!}"
							     class="@if ($fileList->makeSortableUsingNumberField) w-100 @endif"
							     style="max-height:200px @if ($fileList->makeSortableUsingNumberField) cursor: move; @endif">

							@if ($fileList->viewLinkClosure)
						</a>
					@endif

					<div class="caption mt-2">

						@if ($fileList->listFields)
							@foreach ($fileList->listFields as $fieldName)

								<p>
									{{-- Label --}}

									<b>{!! langGet($fileList->langFile.'.fileListLabels.'.$fieldName, ucwords($fieldName)) !!}
										:</b>

									{{-- Value --}}

									@if ($fileList->editableFields && in_array($fieldName, $fileList->editableFields))
										<span class="fileListEditable w-100" data-list-item-id="{!! $row->id !!}"
										      data-list-item-field="{!! $fieldName !!}">
                                    @endif

                                                <?php
                                                $value = $row->$fieldName ?>

											@if (is_array($value))
												{!! implode(', ', $value) !!}
											@elseif ($fieldName == 'filesize')
												{!! humanReadableFileSize($value) !!}
											@else
												{{{ $value }}}
											@endif

											@if ($fileList->editableFields && in_array($fieldName, $fileList->editableFields))
                                        </span>
										<i class="fileListEditableIcon fa fa-pencil text-primary"></i>
									@endif
								</p>

							@endforeach
						@endif

						@if ($fileList->useIsPrimary && count($fileList->list) > 1)
							@if ($row->isPrimary)
								<a class="btn btn-success btn-sm btn-block disabled text-white">(Current Primary
									Photo)</a>
							@else
								<a class="btn btn-light btn-sm btn-block text-lowercase"
								   href="/{!! Request::path() !!}?fileListCommand=makePrimary&item={{{ $row->id }}}">Use
									as the Primary Photo</a>
							@endif
						@endif

						@if ($fileList->allowDelete)
							<a class="btn btn-danger btn-sm text-lowercase @if ($fileList->makeSortableUsingNumberField) btn-block @endif"
							   href="/{!! Request::path() !!}?fileListCommand=delete&item={!! $row->id !!}"
							   onClick="javascript:return confirm('{{{ langGet('fileList.deleteAreYouSure') }}}')"><i
										class="fa fa-trash-o"></i>&nbsp; {{{ langGet('fileList.delete') }}}</a>
						@endif

					</div>

				</div>

			@endforeach
		</div>

	@else
		<table class="table table-striped">
			<thead>
			<tr>
				@foreach ($fileList->listFields as $fieldName)
					<th>{!! langGet($fileList->langFile.'.fileListLabels.'.$fieldName, ucwords($fieldName)) !!}</th>
				@endforeach
				<th></th> {{-- For delete/view buttons if any --}}

				@if ($fileList->showThumb)
					<th></th>
				@endif
			</tr>
			</thead>

			<tbody class="fileList">
			@foreach ($fileList->list as $row)
				<tr @if ($fileList->makeSortableUsingNumberField) id="sortOrder_{!! $row->id !!}" @endif>

					@if ($fileList->makeSortableUsingNumberField)
						<td style="width: 20px; cursor: move;"><i class="fa fa-arrows-v text-muted"></i></td>
					@endif

					@foreach ($fileList->listFields as $fieldName)
						<td>
							@if ($fileList->editableFields && in_array($fieldName, $fileList->editableFields))
								<span class="fileListEditable" data-list-item-id="{!! $row->id !!}"
								      data-list-item-field="{!! $fieldName !!}">
                        @endif

                                        <?php
                                        $value = $row->$fieldName ?>

									@if (is_array($value))
										{!! implode(', ', $value) !!}
									@elseif ($fieldName == 'filesize')
										{!! humanReadableFileSize($value) !!}
									@else
										{{{ $value }}}
									@endif
									@if ($fileList->editableFields && in_array($fieldName, $fileList->editableFields))</span>
								&nbsp; <i class="fileListEditableIcon fa fa-pencil text-primary"></i>
							@endif

						</td>
					@endforeach

					<td>
						@if ($fileList->viewLinkClosure)
							<a class="btn btn-success btn-xs"
							   href="{!! $fileList->viewLinkClosure->__invoke($row) !!}">{{{ langGet('fileList.view') }}}</a>
						@endif

						@if ($fileList->allowDelete)
							<a class="btn btn-danger btn-xs"
							   href="/{!! Request::path() !!}?fileListCommand=delete&item={!! $row->id !!}"
							   onClick="javascript:return confirm('{{{ langGet('fileList.deleteAreYouSure') }}}')">{{{ langGet('fileList.delete') }}}</a>
						@endif
					</td>

					@if ($fileList->showThumb)
						<td>
							<a class="" href="{!! $fileList->viewLinkClosure->__invoke($row) !!}">
								<img height="20" src="{!! $fileList->viewLinkClosure->__invoke($row) !!}" alt="">
							</a>
						</td>
					@endif

				</tr>
			@endforeach
			</tbody>
		</table>

		@if ($fileList->makeSortableUsingNumberField)
			<div>{{{ langGet('fileList.dragToChangeOrder') }}}</div>
		@endif

	@endif

@else
	<div>None yet.</div>
@endif

@section('pageBottom')
    <?php
    Lib\HttpAsset::requireAsset('inlineEdit');
    Lib\HttpAsset::requireAsset('jquery-ui');  // used for sortable()
    ?>

    <?php
    $requestURL = isset($requestURL) ? $requestURL : Request::url(); ?>

	<script type="text/javascript">
        $(function () {
			{{-- Allow the pencil icon to be clicked in order to edit the field. --}}
            $('.fileList .fileListEditableIcon').click(function (e) {
                $(this).siblings('.fileListEditable').click();
            });

            $('.fileListEditable').inlineEdit({
                buttons: '<button class="btn btn-sm btn-light save mr-1 mt-2">Save</button><button class="btn btn-sm btn-light cancel mt-2">Cancel</button>',
                placeholder: '[edit]',
                hover: 'captionHover',
                control: 'input',
                cancelOnBlur: false,
                saveOnBlur: false,
                nl2br: false,
                save: function (event, data) {
                    var html = $.ajax({
                        url: '{!! $requestURL !!}', type: 'POST', async: false,
                        data: {
                            'fileListCommand': 'editValue',
                            'item': $(this).data('listItemId'),
                            'field': $(this).data('listItemField'),
                            'value': data.value,
                            '_token': "{!! csrf_token() !!}" {{-- (for CSRF) --}} }
                    }).responseText;

                    if (html.trim() == 'ok') {
                        location.reload();
						{{-- best to refresh everything in case FileList changed the file extension back or anything --}}
                            return true;
                    } else {
						{{-- some error occurred --}}
                        alert('An error occurred. The item could not be edited.');
                        return false;
                    }
                },
            });

			@if ($fileList->makeSortableUsingNumberField)
            $('.fileList').sortable({
                cursor: "move", distance: 4,
                update: function () {
                    $.ajax({
                        data: 'fileListCommand=reorder&_token={!! urlencode(csrf_token()) !!}&' + $(this).sortable('serialize'),
                        url: '{!! $requestURL !!}', type: 'POST'
                    });
                }
            });
			@endif
        });
	</script>

	@parent
@stop