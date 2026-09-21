@blaze(unsafe: ['href', 'as'])

@props([
    'href' => null,
    'as' => null,
])

@php
    $tagType = match (true) {
        $as === 'div' && ! $href => 'div',
        $as === 'a' || $href => 'a',
        default => 'button',
    };
@endphp

@switch ($tagType)
    @case ('div')
        <div {{ $attributes }}>{{ $slot }}</div>
        @break
    @case ('a')
        <a href="{{ $href }}" wire:navigate {{ $attributes }}> {{ $slot }} </a>
        @break
    @default
        <button {{ $attributes->merge(['type' => 'button']) }}>{{ $slot }}</button>
@endswitch
