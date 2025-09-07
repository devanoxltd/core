<div class="mx-2">
    @if (! $isMigrationRun)
        <div
            class="mt-2 flex items-center justify-center"
            wire:key="migrations-not-run"
            wire:init="runAppDbMigrateInstall"
        >
            <div class="text-yellow flex items-center text-2xl dark:text-yellow-400">
                <x-ui.icon name="outline.info" class="mr-2 size-6" />
                @lang('core::install.steps.migrations.not_run')
            </div>
        </div>
    @endif

    @if ($isMigrationRunning)
        <div
            class="mt-2 flex items-center justify-center"
            wire:key="migrations-running"
            wire:poll.visible="checkStatus"
        >
            <div class="text-blue flex items-center text-2xl dark:text-blue-400">
                <x-ui.icon name="outline.spinner" class="mr-2 size-6 animate-spin" />
                @lang('core::install.steps.migrations.running')
            </div>
        </div>
    @endif

    @if ($isMigrationComplete)
        <div class="mt-2 flex items-center justify-center" wire:key="migrations-complete">
            <div class="text-green flex items-center text-xl dark:text-green-400">
                <x-ui.icon name="outline.check" class="mr-2 size-6" />
                @lang('core::install.steps.migrations.complete')
            </div>
        </div>
    @endif
</div>
