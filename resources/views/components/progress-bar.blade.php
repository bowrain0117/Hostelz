@props(['percentage' => 0])

<div class="progress">
    <div {{ $attributes->merge(['class' => 'progress-bar']) }}
         role="progressbar"
         style="width: {{ $percentage }}%;"
         aria-valuenow="{{ $percentage }}"
         aria-valuemin="0"
         aria-valuemax="100"
    >
        {{ $percentage }}%
    </div>
</div>