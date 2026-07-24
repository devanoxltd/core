@aware ([
    'icon' => new Illuminate\View\ComponentSlot,
    'variant' => 'default',
    'darkIcon' => 'solid.moon-star',
    'lightIcon' => 'solid.sun-bright',
    'systemIcon' => 'solid.monitor',
])

<x-ui.form.button.abstract
    x-data="{}"
    @click="$theme.toggle()"
    aria-label="@lang('component.utilities.theme-switcher.label')"
    {{ $attributes->sanitize()->tailwindClass('cursor-pointer px-2 py-1.5 text-sm text-gray-800 transition-colors duration-200 focus:outline-none focus:ring-gray-500 disabled:pointer-events-none disabled:opacity-60 dark:text-gray-200') }}
>
    <x-ui.icon
        :name="$lightIcon"
        x-show="$theme.isLight"
        :attributes="$icon->attributes->sanitize()->tailwindClass('inline-block size-5')"
    />
    <x-ui.icon
        :name="$darkIcon"
        x-show="$theme.isDark"
        :attributes="$icon->attributes->sanitize()->tailwindClass('inline-block size-5')"
    />
    <x-ui.icon
        :name="$systemIcon"
        x-show="$theme.isSystem"
        :attributes="$icon->attributes->sanitize()->tailwindClass('inline-block size-5')"
    />
</x-ui.form.button.abstract>
