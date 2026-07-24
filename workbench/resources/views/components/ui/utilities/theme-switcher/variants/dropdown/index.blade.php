@aware ([
    'icon' => new Illuminate\View\ComponentSlot,
    'visibleIcon' => new Illuminate\View\ComponentSlot,
    'variant' => 'default',
    'darkIcon' => 'solid.moon-star',
    'lightIcon' => 'solid.sun-bright',
    'systemIcon' => 'solid.monitor',
])

<x-ui.navigation.dropdown :attributes="$attributes">
    <x-slot:button
        class="cursor-pointer text-gray-950 transition hover:opacity-80 dark:text-gray-50"
        role="button"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="theme-menu"
    >
        <x-ui.icon
            :name="$lightIcon"
            x-show="$theme.isLight"
            :attributes="$visibleIcon->attributes->sanitize()->tailwindClass('size-5')"
        />
        <x-ui.icon
            :name="$darkIcon"
            x-show="$theme.isDark"
            :attributes="$visibleIcon->attributes->sanitize()->tailwindClass('size-5')"
        />
        <x-ui.icon
            :name="$systemIcon"
            x-show="$theme.isSystem"
            :attributes="$visibleIcon->attributes->sanitize()->tailwindClass('size-5')"
        />
    </x-slot:button>

    <x-slot:menu>
        <x-ui.navigation.dropdown.item
            x-on:click="
                $theme.setLight();
                close();
            "
            x-bind:class="{ 'bg-primary-500 text-primary-text': $theme.isLight }"
        >
            <x-slot:before>
                <x-ui.icon
                    :name="$lightIcon"
                    :attributes="$icon->attributes->sanitize()->tailwindClass('mr-2 size-4 place-self-center')"
                    data-slot="right-icon"
                />
            </x-slot:before>
            @lang ('component.utilities.theme-switcher.light')
        </x-ui.navigation.dropdown.item>

        <x-ui.navigation.dropdown.item
            x-on:click="
                $theme.setDark();
                close();
            "
            x-bind:class="{ 'bg-primary-500 text-primary-text': $theme.isDark }"
        >
            <x-slot:before>
                <x-ui.icon
                    :name="$darkIcon"
                    :attributes="$icon->attributes->sanitize()->tailwindClass('mr-2 size-4 place-self-center')"
                    data-slot="right-icon"
                />
            </x-slot:before>
            @lang ('component.utilities.theme-switcher.dark')
        </x-ui.navigation.dropdown.item>

        <x-ui.navigation.dropdown.item
            x-on:click="
                $theme.setSystem();
                close();
            "
            x-bind:class="{ 'bg-primary-500 text-primary-text': $theme.isSystem }"
        >
            <x-slot:before>
                <x-ui.icon
                    :name="$systemIcon"
                    :attributes="$icon->attributes->sanitize()->tailwindClass('mr-2 size-4 place-self-center')"
                    data-slot="right-icon"
                />
            </x-slot:before>
            @lang ('component.utilities.theme-switcher.system')
        </x-ui.navigation.dropdown.item>
    </x-slot:menu>
</x-ui.navigation.dropdown>
