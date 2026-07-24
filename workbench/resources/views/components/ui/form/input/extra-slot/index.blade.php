<div
    {{ $attributes->sanitize()->tailwindClass('data-[slot=input-suffix]:rounded-r-md data-[slot=input-prefix]:rounded-l-md flex items-center gap-1.5 bg-gray-200 px-2.5 text-gray-600 dark:bg-gray-800 dark:text-gray-400') }}
>
    {{ $slot }}
</div>
