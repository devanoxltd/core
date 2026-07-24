<x-ui.form.input.options.button
    x-data="{
        revealed: false,
        toggleReveal() {
            const input = $el
                .closest('[data-slot=input-actions]')
                .parentElement.querySelector('input[data-control-id=input]');
            if (!input) return;

            this.revealed = !this.revealed;
            input.type = this.revealed ? 'text' : 'password';
        },
    }"
    x-on:click="toggleReveal()"
    x-bind:data-slot-revealed="revealed"
    x-bind:aria-label="revealed ? '@lang('component.form.input.revealable.hide')' : '@lang('component.form.input.revealable.show')'"
    x-bind:title="revealed ? '@lang('component.form.input.revealable.hide')' : '@lang('component.form.input.revealable.show')'"
>
    <x-ui.icon
        data-slot="icon"
        name="eye-off"
        path="outline"
        class="[[data-slot-revealed]>&]:inline-flex hidden size-5"
        aria-hidden="true"
    />
    <x-ui.icon
        data-slot="icon"
        name="eye-2"
        path="outline"
        class="[[data-slot-revealed]>&]:hidden inline-flex size-5"
        aria-hidden="true"
    />
</x-ui.form.input.options.button>
