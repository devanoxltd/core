<button
    data-slot="input-option"
    type="button"
    {{ $attributes->sanitize()->tailwindClass('dark:[&_[data-slot=icon]]:text-gray-500 hover:dark:[&_[data-slot=icon]]:text-gray-400 [&_[data-slot=icon]]:text-gray-500 [&_[data-slot=icon]]:transition mx-0.5 flex cursor-pointer') }}
>
    {{ $slot }}
</button>
