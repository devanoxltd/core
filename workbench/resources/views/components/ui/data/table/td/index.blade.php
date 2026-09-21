@blaze(fold: true)

<td {{ $attributes->sanitize()->tailwindClass('px-2 py-1.5 text-sm text-gray-700 dark:text-gray-300') }}>
    {{ $slot }}
</td>
