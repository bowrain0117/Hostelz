@extends('staff/edit-layout', ['title' => 'Prettylink'])


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>

            @if (auth()->user()->hasPermission('staffEditUsers'))
                @if ($formHandler->model->userID)
                    <a href="{!! routeURL('staff-users', [ $formHandler->model->userID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
                @else
                    <a href="#" class="list-group-item disabled">(No user ID)</a>
                @endif
            @endif
        </div>

    @endif

@stop


@section('belowForm')


@stop


@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])

    @parent

@endsection

