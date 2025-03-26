<div class="min-h-64 w-2xl rounded-lg bg-gray-100 pb-6 shadow-md dark:bg-gray-800">
    <div class="bg-primary-500 dark:bg-primary-700 rounded-t-lg pt-10 pb-16 text-center">
        {{-- TODO: Add Company LOGO --}}
        <h1 class="text-2xl font-bold text-white">
            @lang('core::install.steps.' . $activeStep . '.title')
        </h1>
    </div>
    <div class="-mt-5 flex w-full items-center justify-center" wire:key="stepper">
        <div class="flex-1 border-t-4 border-gray-300 dark:border-gray-600"></div>
        <div
            @tailwindClass([
                'flex size-10 items-center justify-center rounded-full text-white',
                'bg-secondary-400 dark:bg-secondary-700' => $activeStep == 'home',
                'bg-gray-300 dark:bg-gray-600' => $activeStep != 'home',
            ])
            wire:key="home"
        >
            <x-icon name="solid.home" class="size-5" />
        </div>
        <div class="flex-1 border-t-4 border-gray-300 dark:border-gray-600"></div>
        <div
            @tailwindClass([
                'flex size-10 items-center justify-center rounded-full text-white',
                'bg-secondary-400 dark:bg-secondary-700' => $activeStep == 'requirements',
                'bg-gray-300 dark:bg-gray-600' => $activeStep != 'requirements',
            ])
            wire:key="requirements"
        >
            <x-icon name="solid.list-check" class="size-5" />
        </div>
        <div class="flex-1 border-t-4 border-gray-300 dark:border-gray-600"></div>
        <div
            @tailwindClass([
                'flex size-10 items-center justify-center rounded-full text-white',
                'bg-secondary-400 dark:bg-secondary-700' => $activeStep == 'permissions',
                'bg-gray-300 dark:bg-gray-600' => $activeStep != 'permissions',
            ])
            wire:key="permissions"
        >
            <x-icon name="solid.folder" class="size-5" />
        </div>

        <div class="flex-1 border-t-4 border-gray-300 dark:border-gray-600"></div>
        <div
            @tailwindClass([
                'flex size-10 items-center justify-center rounded-full text-white',
                'bg-secondary-400 dark:bg-secondary-700' => in_array($activeStep, ['migrations', 'database']),
                'bg-gray-300 dark:bg-gray-600' => !in_array($activeStep, ['migrations', 'database']),
            ])
            wire:key="database"
        >
            <x-icon name="solid.database" class="size-5" />
        </div>

        <div class="flex-1 border-t-4 border-gray-300 dark:border-gray-600"></div>
        <div
            @tailwindClass([
                'flex size-10 items-center justify-center rounded-full text-white',
                'bg-secondary-400 dark:bg-secondary-700' => $activeStep == 'admin',
                'bg-gray-300 dark:bg-gray-600' => $activeStep != 'admin',
            ])
            wire:key="admin"
        >
            <x-icon name="solid.settings" class="size-5" />
        </div>

        <div class="flex-1 border-t-4 border-gray-300 dark:border-gray-600"></div>
        <div
            @tailwindClass([
                'flex size-10 items-center justify-center rounded-full text-white',
                'bg-secondary-400 dark:bg-secondary-700' => $activeStep == 'activation',
                'bg-gray-300 dark:bg-gray-600' => $activeStep != 'activation',
            ])
            wire:key="activation"
        >
            <x-icon name="solid.key" class="size-5" />
        </div>

        <div class="flex-1 border-t-4 border-gray-300 dark:border-gray-600"></div>
        <div
            @tailwindClass([
                'flex size-10 items-center justify-center rounded-full text-white',
                'bg-secondary-400 dark:bg-secondary-700' => $activeStep == 'finish',
                'bg-gray-300 dark:bg-gray-600' => $activeStep != 'finish',
            ])
            wire:key="finish"
        >
            <x-icon name="solid.check" class="size-5" />
        </div>

        <div class="flex-1 border-t-4 border-gray-300 dark:border-gray-600"></div>
    </div>

    <div class="mt-4" wire:key="step-content">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400" wire:key="step-description">
            @lang('core::install.steps.' . $activeStep . '.description')
        </p>

        @switch($activeStep)
            @case('requirements')
                <livewire:core::requirements />
                @break
            @case('permissions')
                <livewire:core::permissions />
                @break
            @case('database')
                <livewire:core::database />
                @break
            @case('migrations')
                <livewire:core::migrations />
                @break
            @case('admin')
                <livewire:core::admin-account />
                @break
            @case('activation')
                <livewire:core::activation />
                @break
        @endswitch
    </div>

    @if ($nextStep || $activeStep == 'finish')
        <div class="mt-5 flex justify-center px-5" wire:key="button">
            @switch($activeStep)
                @case('home')
                @case('requirements')
                @case('permissions')
                @case('database')
                @case('migrations')
                @case('admin')
                @case('activation')
                    <x-form.button.primary
                        wire:click="goToStep('{{ $nextStep }}')"
                        wire:loading.attr="disabled"
                        wire:target="goToStep"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:loading.class.remove="opacity-100 cursor-pointer"
                    >
                        @lang('core::install.steps.' . $activeStep . '.button')
                    </x-form.button.primary>

                    @break
                @case('finish')
                    <x-form.button.primary
                        wire:click="finish"
                        wire:loading.attr="disabled"
                        wire:target="finish"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:loading.class.remove="opacity-100 cursor-pointer"
                    >
                        @lang('core::install.steps.' . $activeStep . '.button')
                    </x-form.button.primary>

                    @break
            @endswitch
        </div>
    @endif
</div>
