@props(['checked' => false])

<div class="d-flex align-items-center mb-2">
    <p class="mb-2">
        <img src="/images/{{ $checked ? 'green-check' : 'red-restriction' }}.svg" alt="#" class="mr-2" style="height:15px"> {{ $slot }}
    </p>
</div>