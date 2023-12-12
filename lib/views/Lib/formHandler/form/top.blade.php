<?php
    Lib\HttpAsset::requireAsset('formHandler.js');
?>

@if ($formHandler->errors && $formHandler->errors->any())
    <div class="row">
        <div @if (isset($formGroupClass)) class="{!! $formGroupClass !!}" @else class="col-md-12" @endif>
            @if ($formHandler->errors->has('_general_')) {{-- special errors that's aren't specific to a particular field --}}
                <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> {!! $formHandler->errors->first('_general_') !!}</div>
            @else
                <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> See error messages below.</div>
            @endif
        </div>
    </div>
@endif

