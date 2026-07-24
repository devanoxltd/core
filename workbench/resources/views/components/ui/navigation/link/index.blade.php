@props ([
    'href' => 'javascript:;',
])

<a
    href="{{ $href }}"
    {!! $attributes->sanitize()->tailwindClass('text-primary-500 hover:underline dark:text-primary-400') !!}
    @if (! $attributes->has('target') && $href !== 'javascript:;' && $href !== '#')
        wire:navigate
    @endif
    data-slot="link"
>
    {{ $slot }}
</a>
