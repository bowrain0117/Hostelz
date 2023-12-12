@props(['title' => '', 'count' => 0, 'color' => 'primary'])

<p>
    <span
            {{ $attributes->merge(['class' => 'bg-'.$color.' py-2 px-2 rounded-3 d-inline-block']) }}
    >
        {{ $title }}: <span class="badge">{{ $count }}</span>
    </span>
</p>