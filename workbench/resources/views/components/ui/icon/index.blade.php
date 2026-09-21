@blaze(unsafe: ['name', 'path'])
@props([
    'path' => null,
    'name',
])

@php
    $class = config('icon.class', '');
    $class = is_string($class) ? $class : '';

    /** @var array<string, mixed> $svgAttributes */
    $svgAttributes = $attributes->tailwindClass($class)->getIterator()->getArrayCopy();
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} data-icon="{{ $name }}"></svg>
