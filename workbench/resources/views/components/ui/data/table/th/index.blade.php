@blaze(unsafe: ['table', 'name'])

@props(['table' => null, 'name' => null])
<th
    {{
        $attributes->sanitize()->tailwindClass([
            'px-2 py-2.5 text-left text-sm font-medium text-gray-800 text-nowrap dark:text-gray-200',
            'cursor-pointer' => $name,
        ])
    }}
    @if ($name && $table)
        wire:click="sort('{{ $name }}')"
    @endif
>
    {{ $slot }}
    @if ($name && $table)
        <span class="ml-1" wire:key="sort-icon-{{ $name }}">
            @if ($table->sortBy !== $name)
                <x-ui.icon name="solid.arrow-sort" class="inline size-4" />
            @elseif ($table->sortDirection === 'asc')
                <x-ui.icon name="solid.arrow-sort-down" class="inline size-4" />
            @else
                <x-ui.icon name="solid.arrow-sort-up" class="inline size-4" />
            @endif
        </span>
    @endif
</th>
