<form class="mx-2 grid grid-cols-12 gap-2 px-4" wire:submit="submit" wire:loading.attr="disabled">
    @if (! $isConfigured)
        <div class="col-span-12">
            <x-ui.form.label for="form.appUrl" :value="__('core::install.steps.database.form.appUrl')" />
            <x-form.text id="form.appUrl" class="mt-1 w-full" wire:model="form.appUrl" />
            <x-ui.form.error name="form.appUrl" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-ui.form.label for="form.host" :value="__('core::install.steps.database.form.host')" />
            <x-form.text id="form.host" class="mt-1 w-full" wire:model="form.host" />
            <x-ui.form.error name="form.host" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-ui.form.label for="form.port" :value="__('core::install.steps.database.form.port')" />
            <x-form.text id="form.port" class="mt-1 w-full" wire:model="form.port" />
            <x-ui.form.error name="form.port" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-ui.form.label for="form.database" :value="__('core::install.steps.database.form.database')" />
            <x-form.text id="form.database" class="mt-1 w-full" wire:model="form.database" />
            <x-ui.form.error name="form.database" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-ui.form.label for="form.dbUsername" :value="__('core::install.steps.database.form.dbUsername')" />
            <x-form.text id="form.dbUsername" class="mt-1 w-full" wire:model="form.dbUsername" />
            <x-ui.form.error name="form.dbUsername" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-ui.form.label for="form.dbPassword" :value="__('core::install.steps.database.form.dbPassword')" />
            <x-form.input id="form.dbPassword" type="password" class="mt-1 w-full" wire:model="form.dbPassword" />
            <x-ui.form.error name="form.dbPassword" class="mt-2" />
        </div>
        <div class="col-span-12 mt-2 flex justify-center">
            <x-ui.form.button type="submit" wire:loading.attr="disabled" wire:target="submit" size="sm">
                @lang('core::install.steps.database.form.submit')
            </x-ui.form.button>
        </div>
    @else
        <div class="col-span-12 flex flex-col items-center justify-center gap-2 text-center">
            <p class="text-green-600">@lang('core::install.steps.database.connection.success')</p>
            <x-ui.form.button wire:loading.attr="disabled" wire:target="edit" wire:click="edit" color="gray" size="sm">
                @lang('core::install.steps.database.form.edit')
            </x-ui.form.button>
        </div>
    @endif
</form>
