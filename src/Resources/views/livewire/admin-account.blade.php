<form class="mx-2 grid grid-cols-12 gap-2 px-4" wire:submit="submit" wire:loading.attr="disabled">
    @if (! $isCreated)
        <div class="col-span-12">
            <x-form.label for="userAccount.username" :value="__('core::install.steps.admin.form.username')" />
            <x-form.text
                id="userAccount.username"
                class="mt-1 w-full"
                wire:model="userAccount.username"
                autocomplete="name"
            />
            <x-form.error name="userAccount.username" class="mt-2" />
        </div>

        <div class="col-span-12">
            <x-form.label for="userAccount.email" :value="__('core::install.steps.admin.form.email')" />
            <x-form.input
                type="email"
                id="userAccount.email"
                class="mt-1 w-full"
                wire:model="userAccount.email"
                autocomplete="username"
            />
            <x-form.error name="userAccount.email" class="mt-2" />
        </div>

        <div class="col-span-12">
            <x-form.label for="userAccount.password" :value="__('core::install.steps.admin.form.password')" />
            <x-form.input
                type="password"
                id="userAccount.password"
                class="mt-1 w-full"
                wire:model="userAccount.password"
                autocomplete="new-password"
            />
            <x-form.error name="userAccount.password" class="mt-2" />
        </div>
        <div class="col-span-12">
            <x-form.label
                for="userAccount.passwordConfirmation"
                :value="__('core::install.steps.admin.form.passwordConfirmation')"
            />
            <x-form.input
                type="password"
                id="userAccount.passwordConfirmation"
                class="mt-1 w-full"
                wire:model="userAccount.passwordConfirmation"
                autocomplete="new-password"
            />
            <x-form.error name="userAccount.passwordConfirmation" class="mt-2" />
        </div>

        <div class="col-span-12 mt-2 flex justify-center">
            <x-form.button.primary type="submit" wire:loading.attr="disabled" wire:target="submit">
                @lang('core::install.steps.admin.form.submit')
            </x-form.button.primary>
        </div>
    @else
        <div class="col-span-12 flex flex-col items-center justify-center gap-2 text-center">
            <p class="text-green-600">@lang('core::install.steps.admin.success', ['email' => $userAccount->email])</p>
        </div>
    @endif
</form>
