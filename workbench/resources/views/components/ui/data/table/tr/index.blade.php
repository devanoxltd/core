@blaze(fold: true)

<tr {{ $attributes->sanitize()->tailwindClass('transition-colors hover:bg-gray-200/60 dark:hover:bg-gray-900/60') }}>
    {{ $slot }}
</tr>
