<form class="mx-2 grid grid-cols-12 gap-2 p-4" wire:submit="submit" wire:loading.attr="disabled">
    @if (! $isConfigured)
        <div class="col-span-12">
            <x-form.label for="form.appUrl" :value="__('core::install.steps.database.form.appUrl')" />
            <x-form.text id="form.appUrl" class="mt-1 w-full" wire:model.live="form.appUrl" />
            <x-form.error name="form.appUrl" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-form.label for="form.host" :value="__('core::install.steps.database.form.host')" />
            <x-form.text id="form.host" class="mt-1 w-full" wire:model.live="form.host" />
            <x-form.error name="form.host" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-form.label for="form.port" :value="__('core::install.steps.database.form.port')" />
            <x-form.text id="form.port" class="mt-1 w-full" wire:model.live="form.port" />
            <x-form.error name="form.port" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-form.label for="form.database" :value="__('core::install.steps.database.form.database')" />
            <x-form.text id="form.database" class="mt-1 w-full" wire:model.live="form.database" />
            <x-form.error name="form.database" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-form.label for="form.dbUsername" :value="__('core::install.steps.database.form.dbUsername')" />
            <x-form.text id="form.dbUsername" class="mt-1 w-full" wire:model.live="form.dbUsername" />
            <x-form.error name="form.dbUsername" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-form.label for="form.dbPassword" :value="__('core::install.steps.database.form.dbPassword')" />
            <x-form.input id="form.dbPassword" type="password" class="mt-1 w-full" wire:model.live="form.dbPassword" />
            <x-form.error name="form.dbPassword" class="mt-2" />
        </div>
        <div class="col-span-12 flex justify-center">
            <x-form.button.primary type="submit" wire:loading.attr="disabled" wire:target="submit">
                @lang('core::install.steps.database.form.submit')
            </x-form.button.primary>
        </div>
    @else
        <div class="col-span-12 flex justify-center items-center text-center flex-col gap-2">
            <p class="text-green-600">@lang('core::install.steps.database.connection.success')</p>
            <x-form.button.secondary wire:loading.attr="disabled" wire:target="edit" wire:click="edit">
                @lang('core::install.steps.database.form.edit')
            </x-form.button.secondary>
        </div>
    @endif
</form>
