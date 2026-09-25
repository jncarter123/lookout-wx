{{-- resources/views/components/local-time.blade.php --}}
@props([
    'value' => null,
])

@php
    /** @var \Carbon\CarbonInterface|null $dt */
    $dt = $value instanceof \Carbon\CarbonInterface ? $value : null;
@endphp

@if($dt)
    <time
            {{ $attributes->merge(['class' => 'js-localtime']) }}
            datetime="{{ $dt->toIso8601String() }}"
            data-iso="{{ $dt->toIso8601String() }}"
    >
        {{ $dt->toDayDateTimeString() }}
    </time>
@else
    {{ $slot->isEmpty() ? '—' : $slot }}
@endif