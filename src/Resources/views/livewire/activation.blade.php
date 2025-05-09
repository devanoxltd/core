<div class="min-h-40 w-2xl rounded-lg bg-gray-100 pb-6 shadow-md dark:bg-gray-800">
    <div class="bg-primary-500 dark:bg-primary-700 rounded-t-lg py-10 text-center">
        {{-- TODO: Add Company LOGO --}}
        <h1 class="text-2xl font-bold text-white">
            @lang('core::install.steps.activation.title')
        </h1>
    </div>
    <div class="mt-4" wire:key="activation">
        @if (! $isActivated)
            <p class="text-center text-sm text-gray-500 dark:text-gray-400" wire:key="description" wire:poll.visible="check">
                @lang('core::install.steps.activation.description')
            </p>
            <form
                class="mx-2 grid grid-cols-12 gap-2 px-4"
                wire:submit="activate"
                wire:loading.attr="disabled"
                wire:target="activate"
                wire:key="activation-form"
            >
                {{-- <x-icon name="outline.loading-02" class="size-5 animate-spin" /> --}}
                <div class="col-span-12">
                    <x-form.label for="licenseKey" :value="__('core::install.steps.activation.form.licenseKey')" />
                    <x-form.text id="licenseKey" class="mt-1 w-full" wire:model="licenseKey" />
                    <x-form.error name="licenseKey" class="mt-2" />
                    <x-navigation.link
                        href="https://devanox.com/get-application-license-key"
                        target="_blank"
                        class="focus:ring-primary-500 rounded-md text-sm text-gray-700 hover:text-gray-900 focus:ring-2 focus:ring-offset-2 focus:outline-hidden dark:text-gray-300 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        @lang('core::install.steps.activation.form.getLicenseKey')
                    </x-navigation.link>
                </div>
                <div class="col-span-12 mt-2 flex justify-center">
                    <x-form.button.primary type="submit" wire:loading.attr="disabled" wire:target="activate" class="px-4 py-2 text-base">
                        @lang('core::install.steps.activation.form.submit')
                    </x-form.button.primary>
                </div>
            </form>
        @else
            <p class="text-center text-green-600" wire:poll.visible.500ms="openApp" wire:key="success">
                @lang('core::install.steps.activation.success')
            </p>
        @endif
    </div>
</div>
