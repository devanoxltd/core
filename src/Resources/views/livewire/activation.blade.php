<form class="mx-2 grid grid-cols-12 gap-2 px-4" wire:submit="activate" wire:loading.attr="disabled">
    {{-- <x-icon name="outline.loading-02" class="size-5 animate-spin" /> --}}
    @if (! $isActivated)
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
            <x-form.button.primary type="submit" wire:loading.attr="disabled" wire:target="activate">
                @lang('core::install.steps.activation.form.submit')
            </x-form.button.primary>
        </div>
    @else
        <div class="col-span-12 flex flex-col items-center justify-center gap-2 text-center">
            <p class="text-green-600">@lang('core::install.steps.activation.success')</p>
        </div>
    @endif
</form>
