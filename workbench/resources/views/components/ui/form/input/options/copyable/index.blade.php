<x-ui.form.input.options.button
    x-data="{
        copied: false,
        async doCopy() {
            try {
                const input = $el
                    .closest('[data-slot=input-actions]')
                    .parentElement.querySelector('input[data-control-id=input]');
                if (!input?.value) return;

                await navigator.clipboard.writeText(input.value);
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            } catch (error) {
                console.warn('@lang('component.form.input.copyable.failed')', error);
            }
        },
    }"
    x-on:click="doCopy()"
    x-bind:data-slot-copied="copied"
    x-bind:aria-label="copied ? '@lang('component.form.input.copyable.copied')' : '@lang('component.form.input.copyable.copy')'"
    x-bind:title="copied ? '@lang('component.form.input.copyable.copied')' : '@lang('component.form.input.copyable.copy')'"
>
    <x-ui.icon
        data-slot="icon"
        name="clipboard-check"
        path="outline"
        class="[[data-slot-copied]>&]:inline-flex hidden size-5"
        aria-hidden="true"
    />
    <x-ui.icon
        data-slot="icon"
        name="clipboard"
        path="outline"
        class="[[data-slot-copied]>&]:hidden inline-flex size-5"
        aria-hidden="true"
    />
</x-ui.form.input.options.button>
