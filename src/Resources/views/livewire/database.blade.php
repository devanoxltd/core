<form class="mx-2 grid grid-cols-12 gap-2 px-4" wire:submit="submit" wire:loading.attr="disabled">
    @if (! $isConfigured)
        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="form.appUrl" :value="__('core::install.steps.database.form.appUrl')" />
            <x-ui.form.input id="form.appUrl" wire:model="form.appUrl" />
            <x-ui.form.error name="form.appUrl" />
        </x-ui.form.field>
        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="form.host" :value="__('core::install.steps.database.form.host')" />
            <x-ui.form.input id="form.host" wire:model="form.host" />
            <x-ui.form.error name="form.host" />
        </x-ui.form.field>
        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="form.port" :value="__('core::install.steps.database.form.port')" />
            <x-ui.form.input id="form.port" wire:model="form.port" />
            <x-ui.form.error name="form.port" />
        </x-ui.form.field>
        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="form.database" :value="__('core::install.steps.database.form.database')" />
            <x-ui.form.input id="form.database" wire:model="form.database" />
            <x-ui.form.error name="form.database" />
        </x-ui.form.field>
        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="form.dbUsername" :value="__('core::install.steps.database.form.dbUsername')" />
            <x-ui.form.input id="form.dbUsername" wire:model="form.dbUsername" />
            <x-ui.form.error name="form.dbUsername" />
        </x-ui.form.field>
        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="form.dbPassword" :value="__('core::install.steps.database.form.dbPassword')" />
            <x-ui.form.input id="form.dbPassword" type="password" wire:model="form.dbPassword" />
            <x-ui.form.error name="form.dbPassword" />
        </x-ui.form.field>
        <x-ui.form.field class="col-span-12 mt-2 flex justify-center">
            <x-ui.form.button type="submit" wire:loading.attr="disabled" wire:target="submit" size="sm">
                @lang('core::install.steps.database.form.submit')
            </x-ui.form.button>
        </x-ui.form.field>
    @else
        <div class="col-span-12 flex flex-col items-center justify-center gap-2 text-center">
            <p class="text-green-600">@lang('core::install.steps.database.connection.success')</p>
            <x-ui.form.button wire:loading.attr="disabled" wire:target="edit" wire:click="edit" color="gray" size="sm">
                @lang('core::install.steps.database.form.edit')
            </x-ui.form.button>
        </div>
    @endif
</form>
