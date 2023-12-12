@props(['label', 'for', 'error' => false])

<div class="form-group row">
    <label for="{{ $for }}" class="col-sm-3 col-form-label">{{ $label }}</label>
    <div class="col-sm-9">

        {{ $slot }}

        @if ($error)
            <div class="text-danger small mt-1">{!! $error !!}</div>
        @endif
    </div>
</div>