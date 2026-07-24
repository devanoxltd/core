@aware (['required' => false])

@props ([
    'value' => null,
    'required' => false,
])

<label
    {{
        $attributes->sanitize()->tailwindClass([
            'font-medium text-sm text-start text-gray-700 dark:text-gray-100',
            'required' => $required,
        ])
    }}
    data-slot="label"
>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        {{ $value }}
    @endif
</label>
