@props ([
    'required' => false,
    'disabled' => false,
])

<div
    {{
        $attributes->sanitize()->tailwindClass([
            'w-full min-w-0',
            // Label spacing - default margin, reduced when followed by description
            '[&>[data-slot=label]]:mb-2',
            '[&>[data-slot=label]:has(+[data-slot=description])]:mb-1.5',

            // Description spacing - tight after label, normal after other elements
            '[&>[data-slot=label]+[data-slot=description]]:mt-0',
            '[&>[data-slot=label]+[data-slot=description]]:mb-2',
            '[&>*:not([data-slot=label])+[data-slot=description]]:mt-2',

            // Error message spacing
            '[&>[data-slot=error]]:mt-1.5',

            // Disabled state - dim labels when controls are disabled
            '[&>[data-slot=label]]:opacity-50' => $disabled,
            '[&:has([data-slot=control][disabled])>[data-slot=label]]:opacity-50',
        ])
    }}
    data-slot="field"
>
    {{ $slot }}
</div>
