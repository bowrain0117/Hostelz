@props(['isActive' => true])

<div
    @class([
       'spinner-wrap text-center',
       'd-none' => !$isActive,
   ])
>
    <div {{ $attributes->merge(['class' => "spinner-border text-primary"]) }} role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>