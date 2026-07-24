@aware ([
    'icon' => new Illuminate\View\ComponentSlot,
    'variant' => 'default',
    'darkIcon' => 'solid.moon-star',
    'lightIcon' => 'solid.sun-bright',
    'systemIcon' => 'solid.monitor',
])

<div
    class="flex w-fit items-center overflow-hidden rounded-lg border border-gray-950/10 text-gray-950 transition-all duration-200 dark:border-gray-50/10 dark:text-gray-50"
    x-data="{}"
>
    <x-ui.form.button
        class="hover:bg-primary-600 hover:text-primary-text"
        x-on:click="$theme.setLight()"
        x-bind:class="{
            'bg-primary-500 text-primary-text': $theme.isLight,
        }"
        role="button"
        aria-pressed="true"
        x-bind:aria-pressed="$theme.isLight"
        aria-label="{{ __('component.utilities.theme-switcher.activate', ['theme' => __('component.utilities.theme-switcher.light')]) }}"
    >
        <x-slot:before>
            <x-ui.icon :name="$lightIcon" :attributes="$icon->attributes->sanitize()->tailwindClass('size-4')" />
        </x-slot:before>
    </x-ui.form.button>

    <x-ui.form.button
        class="hover:bg-primary-600 hover:text-primary-text"
        x-on:click="$theme.setDark()"
        x-bind:class="{
            'bg-primary-500 text-primary-text': $theme.isDark,
        }"
        role="button"
        aria-pressed="true"
        x-bind:aria-pressed="$theme.isDark"
        aria-label="{{ __('component.utilities.theme-switcher.activate', ['theme' => __('component.utilities.theme-switcher.dark')]) }}"
    >
        <x-slot:before>
            <x-ui.icon :name="$darkIcon" :attributes="$icon->attributes->sanitize()->tailwindClass('size-4')" />
        </x-slot:before>
    </x-ui.form.button>
    <x-ui.form.button
        class="hover:bg-primary-600 hover:text-primary-text"
        x-on:click="$theme.setSystem()"
        x-bind:class="{
            'bg-primary-500 text-primary-text': $theme.isSystem,
        }"
        role="button"
        aria-pressed="true"
        x-bind:aria-pressed="$theme.isSystem"
        aria-label="{{ __('component.utilities.theme-switcher.activate', ['theme' => __('component.utilities.theme-switcher.system')]) }}"
    >
        <x-slot:before>
            <x-ui.icon :name="$systemIcon" :attributes="$icon->attributes->sanitize()->tailwindClass('size-4')" />
        </x-slot:before>
    </x-ui.form.button>
</div>
