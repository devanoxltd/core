@blaze(unsafe: ['head', 'foot', 'body', 'bodyWrapper', 'tableWrapper'])

@props([
    'head' => new Illuminate\View\ComponentSlot,
    'foot' => new Illuminate\View\ComponentSlot,
    'body' => new Illuminate\View\ComponentSlot,
    'bodyWrapper' => new Illuminate\View\ComponentSlot,
    'tableWrapper' => new Illuminate\View\ComponentSlot,
])
<div {{ $bodyWrapper->attributes->sanitize()->tailwindClass('relative min-w-full overflow-x-auto') }}>
    <div {{ $tableWrapper->attributes->sanitize()->tailwindClass('inline-block min-w-full overflow-hidden rounded-lg border border-gray-200/60 bg-white shadow-sm dark:border-gray-700/50 dark:bg-gray-900/50') }}>
        <table {{ $attributes->sanitize()->tailwindClass('min-w-full divide-y divide-gray-200/60 dark:divide-gray-700/50') }}>
            @if ($head->isNotEmpty())
                <thead {{ $head->attributes->sanitize()->tailwindClass('bg-gray-50/50 dark:bg-gray-800/50') }}>
                    {{ $head }}
                </thead>
            @endif

            @if ($body->isNotEmpty())
                <tbody {{ $body->attributes->sanitize()->tailwindClass('divide-y divide-gray-200/60 dark:divide-gray-700/50') }}>
                    {{ $body }}
                </tbody>
            @endif

            @if ($foot->isNotEmpty())
                <tfoot {{ $foot->attributes->sanitize()->tailwindClass('bg-gray-50/50 dark:bg-gray-800/50') }}>
                    {{ $foot }}
                </tfoot>
            @endif
        </table>
    </div>
</div>
