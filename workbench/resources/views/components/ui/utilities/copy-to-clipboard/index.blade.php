@blaze(unsafe: ['text', 'success', 'iconClass', 'size'])

@props([
    'text' => '',
    'success' => null,
    'iconClass' => '',
    'size' => 'xs',
])

<div
    x-data="{
        copyNotification: false,
        copyToClipboard() {
            navigator.clipboard.writeText(this.$refs.copyText.textContent);
            this.copyNotification = true;
            let that = this;
            setTimeout(function () {
                that.copyNotification = false;
            }, 3000);
        },
    }"
    class="relative flex items-center"
>
    <span class="hidden" x-ref="copyText">{{ $text }}</span>
    <x-ui.form.button color="gray" @click="copyToClipboard()" :attributes="$attributes">
        <span x-show="! copyNotification" class="flex items-center">
            @if ($slot->isNotEmpty())
                {{ $slot }}
            @else
                <x-ui.icon name="outline.clipboard" class="size-4 shrink-0 stroke-current {{ $iconClass }}" />
            @endif
        </span>
        <span x-show="copyNotification" x-cloak class="flex items-center">
            @if ($success)
                <span {{ $success->attributes->sanitize()->tailwindClass('text-green-500') }}> {{ $success }} </span>
            @else
                <x-ui.icon name="outline.clipboard-check" class="size-4 shrink-0 text-green-500 {{ $iconClass }}" />
            @endif
        </span>
    </x-ui.form.button>
</div>
