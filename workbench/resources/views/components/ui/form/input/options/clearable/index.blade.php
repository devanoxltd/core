<x-ui.form.input.options.button
    x-data="{
        clearInput() {
            const input = $el
                .closest('[data-slot=input-actions]')
                .parentElement.querySelector('input[data-control-id=input]');
            if (input) {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },
    }"
    x-on:click="clearInput()"
>
    <x-ui.icon data-slot="icon" name="x-close" path="outline" class="size-5" aria-hidden="true" />
</x-ui.form.input.options.button>
