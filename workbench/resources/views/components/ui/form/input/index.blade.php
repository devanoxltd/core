@props ([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'prefix' => new Illuminate\View\ComponentSlot,
    'suffix' => new Illuminate\View\ComponentSlot,
    'leftIcon' => new Illuminate\View\ComponentSlot,
    'rightIcon' => new Illuminate\View\ComponentSlot,
    'container' => new Illuminate\View\ComponentSlot,
    'wrapper' => new Illuminate\View\ComponentSlot,
    'actions' => new Illuminate\View\ComponentSlot,
    'clearable' => null,
    'copyable' => null,
    'revealable' => null,
    'invalid' => null,
    'type' => 'text',
    'mask' => null,
    'size' => null,
    'kbd' => null,
    'as' => null,
    'bindScopeToParent' => false,
])

@php
    $invalid ??= $name && $errors->has($name);

    if ($invalid !== null) {
        $invalid = filter_var($invalid, FILTER_VALIDATE_BOOLEAN);
    }

    $iconCount = count(array_filter([$clearable, $copyable, $revealable, $rightIcon->attributes->get('name')]));
@endphp

<div
    {{
        $container->attributes->sanitize()->tailwindClass([
            // isolate stacking context to prevent z-index and shadow bleed from parent
            'isolate',

            'relative flex w-full items-stretch shadow-xs transition-colors duration-200 disabled:shadow-none',

            'rounded-md',
            // Tailwind v4 '_input' means space + 'input'; these selectors target the input child
            '[&:has([data-slot=input-prefix])_input]:rounded-l-none',  // remove left border-radius if prefix exists

            '[&:has([data-slot=input-suffix])_input]:rounded-r-none',  // remove right border-radius if suffix exists

            '[&:has([data-slot=input-prefix]):has([data-slot=input-suffix])_input]:rounded-none', // no border-radius if both exist
        ])
    }}
    data-slot="input-container"
>
    {{-- HANDLE PREFIX SLOTS --}}
    @if ($prefix->isNotEmpty())
        <x-ui.form.input.extra-slot
            data-slot="input-prefix"
            :attributes="$prefix->attributes->sanitize()->tailwindClass()"
        >
            {{ $prefix }}
        </x-ui.form.input.extra-slot>
    @endif

    <div
        @unless ($bindScopeToParent)
            {{--
                When this input component is used on its own, it needs its own Alpine.js scope (`x-data`)
                to handle features like copy, clear, reveal, etc.

                However, when this input is nested inside a parent component that already has an Alpine.js scope,
                giving it a separate `x-data` creates duplicate Alpine scopes.

                Duplicate scopes mean that methods like `handleKeydown` exist both in the parent and child,
                so the same event gets handled twice, which is why you were seeing the keydown fire handled two times...

                Setting '$bindScopeToParent = true' disables this child scope, allowing the input to
                use the parent's Alpine.js scope, preventing duplicate event handling while still
                keeping all parent features intact.
            --}}
            x-data
        @endunless
        {{-- ========================================================================== --}}
        {{-- DYNAMIC GRID COLUMN TEMPLATE SYSTEM --}}
        {{-- ========================================================================== --}}
        {{-- Dynamically adjusts grid structure based on icon presence --}}
        @style ([
            // CSS custom properties for calculations
            '--icon-count: ' . $iconCount,              // Number of right-side action icons
            '--icon-width: 2rem',                      // Standard width for each icon

            // WITHOUT LEFT ICON: 2-column layout
            // Column 1: Input (flexible width)
            // Column 2: Action icons (fixed width based on count)
            'grid-template-columns: 1fr calc(var(--icon-width) * var(--icon-count))' => ! $leftIcon->attributes->get('name'),

            // WITH LEFT ICON: 3-column layout
            // Column 1: Left icon (fixed 2.3rem) 2 seems too small spacially for  left icons
            // Column 2: Input (flexible width)
            // Column 3: Action icons (fixed width based on count)
            'grid-template-columns: 2.3rem 1fr calc(var(--icon-width) * var(--icon-count))' => $leftIcon->attributes->get('name'),
        ])
        {{
            $wrapper->attributes->sanitize()->tailwindClass([
                // ============================================================================
                // GRID CONTAINER SETUP
                // ============================================================================
                // Creates a CSS Grid container that enables complex overlapping layouts, I challenge you to do the same with flex
                'isolate grid w-full',

                // ============================================================================
                // RIGHT-SIDE ACTIONS POSITIONING SYSTEM
                // ============================================================================
                // Complex conditional positioning for the actions container based on left icon presence
                // The actions need to be in different columns depending on grid layout:
                // - Without left icon: 2-column grid, actions go in column 2
                // - With left icon: 3-column grid, actions go in column 3

                // When no left icon exists, place actions in column 2
                '[&:not(:has([data-slot=left-icon]))>[data-slot=input-actions]]:col-start-2',

                // When left icon exists, place actions in column 3
                '[&:has([data-slot=left-icon])>[data-slot=input-actions]]:col-start-3',

                // '[&>[data-slot=input-actions]]:col-start-3',

                // Standard positioning for all action containers
                '[&>[data-slot=input-actions]]:row-start-1',        // Same row as input
                '[&>[data-slot=input-actions]]:place-self-center',  // Center within grid cell
                '[&>[data-slot=input-actions]]:z-10',               // Overlay above input (it work effect other elementswe're using `isolate`)

                // ============================================================================
                // INPUT FIELD BASE POSITIONING
                // ============================================================================
                // Input spans the full width regardless of icon presence - icons overlay on top
                '[&>[data-slot=control]]:col-start-1',      // Always start at column 1
                '[&>[data-slot=control]]:row-start-1',      // First (and only) row
                '[&>[data-slot=control]]:col-span-3',       // Span across all possible columns (it handle the case of 2 as well)

                // ============================================================================
                // LEFT ICON POSITIONING SYSTEM (when there is a one actually it handled like this has([data-slot=left-icon]))
                // ============================================================================
                // Left icon positioning - only applies when left icon exists
                // Places icon in first column, overlaying on top of input

                // Grid positioning
                '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:col-start-1',      // First column
                '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:row-start-1',      // Same row as input (what actually force overlap)
                '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:place-self-center', // Center within cell

                // Z-index stacking - higher than input and actions to be visible
                '[&:has([data-slot=left-icon])>[data-slot=left-icon]]:z-20',

                // ============================================================================
                // DYNAMIC PADDING MANAGEMENT SYSTEM
                // ============================================================================
                // Prevents text from overlapping with overlaid icons by adding padding

                // LEFT PADDING: Space for left icon when present
                '[&:has([data-slot=left-icon])>[data-slot=control]]:pl-[2.2rem]',

                // RIGHT PADDING: Dynamic padding based on number of action elements
                // Each action input option (or right icon) takes ~1.9rem of space, padding increases accordingly

                // 1 action element (clearable OR copyable OR revealable OR rightIcon so there is [1-4] element)
                '[&:has([data-slot=input-actions]):has([data-slot=input-option])>[data-slot=control]]:pr-[1.9rem]',

                // 2 action elements
                '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[3.8rem]',

                // 3 action elements
                '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[5.7rem]',

                // 4 action elements
                '[&:has([data-slot=input-actions]):has([data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option]+[data-slot=input-option])>[data-slot=control]]:pr-[7.6rem]',
            ])
        }}
        data-slot="input-wrapper"
    >
        @if ($leftIcon->attributes->get('name'))
            <x-ui.icon
                data-slot="left-icon"
                :name="$leftIcon->attributes->get('name')"
                :attributes="$leftIcon->attributes->sanitize()->tailwindClass('size-5 text-gray-500 dark:text-gray-500')"
            />
        @endif

        <input
            name="{{ $name }}"
            type="{{ $type }}"
            data-slot="control"
            {{
                $attributes->sanitize()->tailwindClass([
                    'z-10',
                    'inline-block w-full border p-2 text-base text-gray-800 placeholder-gray-400 disabled:text-gray-500 disabled:placeholder-gray-400/70 sm:text-sm dark:text-gray-300 dark:placeholder-gray-400 dark:disabled:text-gray-400 dark:disabled:placeholder-gray-500',
                    'bg-white dark:bg-gray-900 dark:disabled:bg-gray-800',
                    'transition-colors duration-200 disabled:cursor-not-allowed',
                    'rounded-md shadow-none disabled:shadow-none dark:shadow-sm',
                    'focus:ring-2 focus:ring-offset-0 focus:outline-none',
                    'border-primary-500/10 focus:border-primary-500/15 focus:ring-primary-500 dark:border-white/15 dark:focus:border-primary-500/20 dark:focus:ring-primary-500' => ! $invalid,
                    'border-2 border-red-600/30 focus:border-red-600/30 focus:ring-red-600/20 dark:border-red-400/30 dark:focus:border-red-400/30 dark:focus:ring-red-400/20' => $invalid,
                ])
            }}
            data-control-id="input"
            {{-- used for actions --}}
            @if ($invalid) invalid @endif
        />
        <div
            data-slot="input-actions"
            {{ $actions->attributes->sanitize()->tailwindClass('mr-1 flex h-full items-center justify-center gap-0.5') }}
        >
            @if ($copyable)
                <x-ui.form.input.options.copyable />
            @endif

            @if ($revealable)
                <x-ui.form.input.options.revealable />
            @endif

            @if ($clearable)
                <x-ui.form.input.options.clearable />
            @endif

            {{--
                This isn’t a real input option, just an icon slotted as one.
                It’s here purely to handle padding logic easly, don’t judge me 🤓
            --}}
            @if ($rightIcon->attributes->get('name'))
                <x-ui.icon
                    data-slot="input-option"
                    :name="$rightIcon->attributes->get('name')"
                    :attributes="$rightIcon->attributes->sanitize()->tailwindClass('text-gray-500 dark:text-gray-500 size-5')"
                />
            @endif
        </div>
    </div>

    {{-- HANDLE SUFFIX SLOTS --}}
    @if ($suffix->isNotEmpty())
        <x-ui.form.input.extra-slot
            data-slot="input-suffix"
            :attributes="$suffix->attributes->sanitize()->tailwindClass()"
        >
            {{ $suffix }}
        </x-ui.form.input.extra-slot>
    @endif
</div>
