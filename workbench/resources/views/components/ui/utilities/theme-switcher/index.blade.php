@props ([
    'icon' => new Illuminate\View\ComponentSlot,
    'variant' => 'default',
    'darkIcon' => 'solid.moon-star',
    'lightIcon' => 'solid.sun-bright',
    'systemIcon' => 'solid.monitor',
])

@switch ($variant)
    @case ('inline')
        <x-ui.utilities.theme-switcher.variants.inline :attributes="$attributes" />
        @break
    @case ('stacked')
        <x-ui.utilities.theme-switcher.variants.stacked :attributes="$attributes" />
        @break
    @case ('dropdown')
        <x-ui.utilities.theme-switcher.variants.dropdown :attributes="$attributes" />
        @break
    @default
        <x-ui.utilities.theme-switcher.variants.default :attributes="$attributes" />
@endswitch
