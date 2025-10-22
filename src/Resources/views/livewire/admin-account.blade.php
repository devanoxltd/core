<form class="mx-2 grid grid-cols-12 gap-2 px-4" wire:submit="submit" wire:loading.attr="disabled">
    @if (! $isCreated)
        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="userAccount.username" :value="__('core::install.steps.admin.form.username')" />
            <x-ui.form.input id="userAccount.username" wire:model="userAccount.username" autocomplete="name" />
            <x-ui.form.error name="userAccount.username" />
        </x-ui.form.field>

        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="userAccount.email" :value="__('core::install.steps.admin.form.email')" />
            <x-ui.form.input
                type="email"
                id="userAccount.email"
                wire:model="userAccount.email"
                autocomplete="username"
            />
            <x-ui.form.error name="userAccount.email" />
        </x-ui.form.field>

        <x-ui.form.field class="col-span-12">
            <x-ui.form.label for="userAccount.password" :value="__('core::install.steps.admin.form.password')" />
            <x-ui.form.input
                type="password"
                revealable
                id="userAccount.password"
                wire:model="userAccount.password"
                autocomplete="new-password"
            />
            <x-ui.form.error name="userAccount.password" />
        </x-ui.form.field>
        <x-ui.form.field class="col-span-12">
            <x-ui.form.label
                for="userAccount.passwordConfirmation"
                :value="__('core::install.steps.admin.form.passwordConfirmation')"
            />
            <x-ui.form.input
                type="password"
                revealable
                id="userAccount.passwordConfirmation"
                wire:model="userAccount.passwordConfirmation"
                autocomplete="new-password"
            />
            <x-ui.form.error name="userAccount.passwordConfirmation" />
        </x-ui.form.field>

        <div class="col-span-12 mt-2 flex justify-center">
            <x-ui.form.button type="submit" wire:loading.attr="disabled" wire:target="submit" size="sm">
                @lang('core::install.steps.admin.form.submit')
            </x-ui.form.button>
        </div>
    @else
        <div class="col-span-12 flex flex-col items-center justify-center gap-2 text-center">
            <p class="text-green-600">
                @lang('core::install.steps.admin.success', ['email' => $userAccount->email])
            </p>
        </div>
    @endif
</form>
