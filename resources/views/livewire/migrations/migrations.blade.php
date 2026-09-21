<div class="px-6 pb-2">
    @if (! $isMigrationRun)
        <div
            class="mt-2 flex items-center justify-center"
            wire:key="migrations-not-run"
            wire:init="runAppDbMigrateInstall"
        >
            <div class="flex items-center text-2xl text-yellow-500 dark:text-yellow-400">
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
            <div class="text-primary-500 dark:text-primary-400 flex items-center text-2xl">
                <x-ui.icon name="outline.spinner" class="mr-2 size-6 animate-spin" />
                @lang('core::install.steps.migrations.running')
            </div>
        </div>
    @endif

    @if ($isMigrationComplete)
        <div class="mt-2 flex items-center justify-center" wire:key="migrations-complete">
            <div class="flex items-center text-xl text-green-500 dark:text-green-400">
                <x-ui.icon name="outline.check" class="mr-2 size-6" />
                @lang('core::install.steps.migrations.complete')
            </div>
        </div>
    @endif

    <pre
        wire:key="migration-output"
        class="text-primary-500 dark:text-primary-400 mt-2 max-h-60 w-full overflow-y-auto rounded-lg bg-gray-200 px-4 text-left font-mono text-sm whitespace-pre-wrap dark:bg-gray-900"
        wire:stream="output"
        @scroll="onScroll"
        x-data="{
            autoScroll: true,
            scroll() {
                if (this.autoScroll) {
                    this.$el.scrollTop = this.$el.scrollHeight;
                }
            },
            onScroll() {
                this.autoScroll = this.$el.scrollTop + this.$el.clientHeight >= this.$el.scrollHeight - 15;
            },
            initObserver() {
                let observer = new MutationObserver(() => {
                    this.scroll();
                });
                observer.observe(this.$el, { childList: true, characterData: true, subtree: true });
            },
        }"
        x-init="
            scroll();
            initObserver();
        "
    >{{ $output }}</pre>
</div>
