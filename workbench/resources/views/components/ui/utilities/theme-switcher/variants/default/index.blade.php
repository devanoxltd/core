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
    {{ $attributes->sanitize()->tailwindClass('cursor-pointer rounded-full bg-gray-200 px-2 py-1.5 text-sm text-gray-800 transition-colors duration-200 hover:bg-gray-300 focus:outline-none focus:ring-1  focus:ring-gray-500 disabled:pointer-events-none disabled:opacity-60 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600') }}
>
    <x-ui.icon
        :name="$lightIcon"
        x-show="$theme.isLight"
        :attributes="$icon->attributes->sanitize()->tailwindClass('inline-block size-4')"
    />
    <x-ui.icon
        :name="$darkIcon"
        x-show="$theme.isDark"
        :attributes="$icon->attributes->sanitize()->tailwindClass('inline-block size-4')"
    />
    <x-ui.icon
        :name="$systemIcon"
        x-show="$theme.isSystem"
        :attributes="$icon->attributes->sanitize()->tailwindClass('inline-block size-4')"
    />
</x-ui.form.button.abstract>
