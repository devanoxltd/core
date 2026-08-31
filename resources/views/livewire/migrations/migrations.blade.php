<div class="mx-2">
    @if (! $isMigrationRun)
        <div
            class="mt-2 flex items-center justify-center"
            wire:key="migrations-not-run"
            wire:init="runAppDbMigrateInstall"
        >
            <div class="text-yellow-500 flex items-center text-2xl dark:text-yellow-400">
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
            <div class="text-primary-500 flex items-center text-2xl dark:text-primary-400">
                <x-ui.icon name="outline.spinner" class="mr-2 size-6 animate-spin" />
                @lang('core::install.steps.migrations.running')
            </div>
        </div>
    @endif

    @if ($isMigrationComplete)
        <div class="mt-2 flex items-center justify-center" wire:key="migrations-complete">
            <div class="text-green-500 flex items-center text-xl dark:text-green-400">
                <x-ui.icon name="outline.check" class="mr-2 size-6" />
                @lang('core::install.steps.migrations.complete')
            </div>
        </div>
    @endif

    <pre
        wire:key="migration-output"
        class="mt-2 max-h-60 w-full overflow-y-auto rounded-lg bg-gray-200 px-4 text-left font-mono text-sm whitespace-pre-wrap text-primary-500 dark:bg-gray-900 dark:text-primary-400"
        wire:stream="output"
        @scroll="onScroll"
        x-data="{
            autoScroll: true,
            scroll() {
                if (this.autoScroll) {
                    this.$el.scrollTop = this.$el.scrollHeight
                }
            },
            onScroll() {
                this.autoScroll = this.$el.scrollTop + this.$el.clientHeight >= this.$el.scrollHeight - 15;
            },
            initObserver() {
                let observer = new MutationObserver(() => {
                    this.scroll()
                })
                observer.observe(this.$el, { childList: true, characterData: true, subtree: true })
            }
        }"
        x-init="
            scroll();
            initObserver();
        "
    >{{ $output }}</pre>
</div>
