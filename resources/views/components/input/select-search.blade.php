<div
        x-data="{value: @entangle($attributes->wire('model'))}"
        x-init="new Choices($refs.select);"
        @change="value = $event.target.value;"
        wire:ignore
>
    <select
            {{ $attributes->whereDoesntStartWith('wire:model.live')->merge(['class' => 'form-control']) }}
            x-ref="select"
    >
        {{ $slot }}
    </select>
</div>


@pushOnce('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
@endPushOnce