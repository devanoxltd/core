{{-- When using this button in another component that supports icons, this button will automatically inherit the icon-related props without needing to pass them explicitly each time. --}}
@aware ([
    'type',
    'size',
    'color',
    'colorInvert',
    'loading',
    'variant',
    'before' => new Illuminate\View\ComponentSlot,
    'after' => new Illuminate\View\ComponentSlot,
    'as',
    'squared',
])

@props ([
    'type' => 'button',
    'size' => 'md',
    'color' => null,
    'colorInvert' => false, // Invert the color scheme for outlined and ghost buttons
    'loading' => true, // Set to false to disable the loading indicator feature completely. loading will show only when wire:loading is present
    'variant' => 'primary',
    'before' => new Illuminate\View\ComponentSlot,
    'after' => new Illuminate\View\ComponentSlot,
    'as' => 'button',
    'squared' => false,
])

@php
    // Automatically convert to square style if no content slot is provided
    if ($squared === false) {
        $squared = $slot->isEmpty();
    }

    /* DEALING WITH SIZES - START */
    // Determine size-specific classes, including height, text size, and padding adjustments based on squared mode and icon presence
    $sizeClasses = match ($size) {
        'xs' => '[:where(&)]:h-6 text-xs ' . ($squared ? 'w-6' : 'px-2'),
        'sm' => '[:where(&)]:h-8 text-sm ' . ($squared ? 'w-8' : 'px-3'),
        'md' => '[:where(&)]:h-9 text-base ' . ($squared ? 'w-9' : ($before->isNotEmpty() ? 'ps-3' : 'ps-4') . ' ' . ($after->isNotEmpty() ? 'pe-3' : 'pe-4')), // default
        'lg' => '[:where(&)]:h-10 text-md ' . ($squared ? 'w-10' : ($before->isNotEmpty() ? 'ps-4' : 'ps-5') . ' ' . ($after->isNotEmpty() ? 'pe-4' : 'pe-5')),
        'xl' => '[:where(&)]:h-12 text-md ' . ($squared ? 'w-12' : ($before->isNotEmpty() ? 'ps-4' : 'ps-5') . ' ' . ($after->isNotEmpty() ? 'pe-4' : 'pe-5')),
        '2xl' => '[:where(&)]:h-14 text-lg ' . ($squared ? 'w-14' : ($before->isNotEmpty() ? 'ps-5' : 'ps-6') . ' ' . ($after->isNotEmpty() ? 'pe-5' : 'pe-6')),
        default => ($squared ? '' : ($before->isNotEmpty() ? 'ps-3' : 'ps-4') . ' ' . ($after->isNotEmpty() ? 'pe-3' : 'pe-4')),
    };
    /* SIZES - END */

    // Override theme variables based on the provided color for use in button styling (includes dark mode adjustments)
    $colors = match ($color) {
        'red' => $colorInvert ? '[--color-primary-content:var(--color-red-300)] [--color-primary-text:var(--color-red-950)] [--color-primary-shadow:var(--color-red-800)] [--color-primary:var(--color-red-500)] dark:[--color-primary-content:var(--color-red-600)] dark:[--color-primary-text:var(--color-red-950)] dark:[--color-primary-shadow:var(--color-red-700)] dark:[--color-primary:var(--color-red-500)]' : '[--color-primary-content:var(--color-red-600)] [--color-primary-text:var(--color-red-50)] [--color-primary-shadow:var(--color-red-700)] [--color-primary:var(--color-red-500)] dark:[--color-primary-content:var(--color-red-300)] dark:[--color-primary-text:var(--color-red-50)] dark:[--color-primary-shadow:var(--color-red-800)] dark:[--color-primary:var(--color-red-500)]',
        'orange' => $colorInvert ? '[--color-primary-content:var(--color-orange-300)] [--color-primary-text:var(--color-orange-950)] [--color-primary-shadow:var(--color-orange-800)] [--color-primary:var(--color-orange-500)] dark:[--color-primary-content:var(--color-orange-600)] dark:[--color-primary-text:var(--color-orange-50)] dark:[--color-primary-shadow:var(--color-orange-700)] dark:[--color-primary:var(--color-orange-500)]' : '[--color-primary-content:var(--color-orange-600)] [--color-primary-text:var(--color-orange-50)] [--color-primary-shadow:var(--color-orange-700)] [--color-primary:var(--color-orange-500)] dark:[--color-primary-content:var(--color-orange-300)] dark:[--color-primary-text:var(--color-orange-950)] dark:[--color-primary-shadow:var(--color-orange-800)] dark:[--color-primary:var(--color-orange-500)]',
        'amber' => $colorInvert ? '[--color-primary-content:var(--color-amber-300)] [--color-primary-text:var(--color-amber-950)] [--color-primary-shadow:var(--color-amber-800)] [--color-primary:var(--color-amber-500)] dark:[--color-primary-content:var(--color-amber-600)] dark:[--color-primary-text:var(--color-amber-50)] dark:[--color-primary-shadow:var(--color-amber-700)] dark:[--color-primary:var(--color-amber-500)]' : '[--color-primary-content:var(--color-amber-600)] [--color-primary-text:var(--color-amber-50)] [--color-primary-shadow:var(--color-amber-700)] [--color-primary:var(--color-amber-500)] dark:[--color-primary-content:var(--color-amber-300)] dark:[--color-primary-text:var(--color-amber-950)] dark:[--color-primary-shadow:var(--color-amber-800)] dark:[--color-primary:var(--color-amber-500)]',
        'yellow' => $colorInvert ? '[--color-primary-content:var(--color-yellow-300)] [--color-primary-text:var(--color-yellow-950)] [--color-primary-shadow:var(--color-yellow-800)] [--color-primary:var(--color-yellow-500)] dark:[--color-primary-content:var(--color-yellow-600)] dark:[--color-primary-text:var(--color-yellow-50)] dark:[--color-primary-shadow:var(--color-yellow-700)] dark:[--color-primary:var(--color-yellow-500)]' : '[--color-primary-content:var(--color-yellow-600)] [--color-primary-text:var(--color-yellow-50)] [--color-primary-shadow:var(--color-yellow-700)] [--color-primary:var(--color-yellow-500)] dark:[--color-primary-content:var(--color-yellow-300)] dark:[--color-primary-text:var(--color-yellow-950)] dark:[--color-primary-shadow:var(--color-yellow-800)] dark:[--color-primary:var(--color-yellow-500)]',
        'lime' => $colorInvert ? '[--color-primary-content:var(--color-lime-300)] [--color-primary-text:var(--color-lime-950)] [--color-primary-shadow:var(--color-lime-800)] [--color-primary:var(--color-lime-500)] dark:[--color-primary-content:var(--color-lime-600)] dark:[--color-primary-text:var(--color-lime-50)] dark:[--color-primary-shadow:var(--color-lime-700)] dark:[--color-primary:var(--color-lime-500)]' : '[--color-primary-content:var(--color-lime-600)] [--color-primary-text:var(--color-lime-50)] [--color-primary-shadow:var(--color-lime-700)] [--color-primary:var(--color-lime-500)] dark:[--color-primary-content:var(--color-lime-300)] dark:[--color-primary-text:var(--color-lime-950)] dark:[--color-primary-shadow:var(--color-lime-800)] dark:[--color-primary:var(--color-lime-500)]',
        'green' => $colorInvert ? '[--color-primary-content:var(--color-green-300)] [--color-primary-text:var(--color-green-950)] [--color-primary-shadow:var(--color-green-800)] [--color-primary:var(--color-green-500)] dark:[--color-primary-content:var(--color-green-600)] dark:[--color-primary-text:var(--color-green-950)] dark:[--color-primary-shadow:var(--color-green-700)] dark:[--color-primary:var(--color-green-500)]' : '[--color-primary-content:var(--color-green-600)] [--color-primary-text:var(--color-green-50)] [--color-primary-shadow:var(--color-green-700)] [--color-primary:var(--color-green-500)] dark:[--color-primary-content:var(--color-green-300)] dark:[--color-primary-text:var(--color-green-50)] dark:[--color-primary-shadow:var(--color-green-800)] dark:[--color-primary:var(--color-green-500)]',
        'emerald' => $colorInvert ? '[--color-primary-content:var(--color-emerald-300)] [--color-primary-text:var(--color-emerald-950)] [--color-primary-shadow:var(--color-emerald-800)] [--color-primary:var(--color-emerald-500)] dark:[--color-primary-content:var(--color-emerald-600)] dark:[--color-primary-text:var(--color-emerald-950)] dark:[--color-primary-shadow:var(--color-emerald-700)] dark:[--color-primary:var(--color-emerald-500)]' : '[--color-primary-content:var(--color-emerald-600)] [--color-primary-text:var(--color-emerald-50)] [--color-primary-shadow:var(--color-emerald-700)] [--color-primary:var(--color-emerald-500)] dark:[--color-primary-content:var(--color-emerald-300)] dark:[--color-primary-text:var(--color-emerald-50)] dark:[--color-primary-shadow:var(--color-emerald-800)] dark:[--color-primary:var(--color-emerald-500)]',
        'teal' => $colorInvert ? '[--color-primary-content:var(--color-teal-300)] [--color-primary-text:var(--color-teal-950)] [--color-primary-shadow:var(--color-teal-800)] [--color-primary:var(--color-teal-500)] dark:[--color-primary-content:var(--color-teal-600)] dark:[--color-primary-text:var(--color-teal-950)] dark:[--color-primary-shadow:var(--color-teal-700)] dark:[--color-primary:var(--color-teal-500)]' : '[--color-primary-content:var(--color-teal-600)] [--color-primary-text:var(--color-teal-50)] [--color-primary-shadow:var(--color-teal-700)] [--color-primary:var(--color-teal-500)] dark:[--color-primary-content:var(--color-teal-300)] dark:[--color-primary-text:var(--color-teal-50)] dark:[--color-primary-shadow:var(--color-teal-800)] dark:[--color-primary:var(--color-teal-500)]',
        'cyan' => $colorInvert ? '[--color-primary-content:var(--color-cyan-300)] [--color-primary-text:var(--color-cyan-950)] [--color-primary-shadow:var(--color-cyan-800)] [--color-primary:var(--color-cyan-500)] dark:[--color-primary-content:var(--color-cyan-600)] dark:[--color-primary-text:var(--color-cyan-950)] dark:[--color-primary-shadow:var(--color-cyan-700)] dark:[--color-primary:var(--color-cyan-500)]' : '[--color-primary-content:var(--color-cyan-600)] [--color-primary-text:var(--color-cyan-50)] [--color-primary-shadow:var(--color-cyan-700)] [--color-primary:var(--color-cyan-500)] dark:[--color-primary-content:var(--color-cyan-300)] dark:[--color-primary-text:var(--color-cyan-50)] dark:[--color-primary-shadow:var(--color-cyan-800)] dark:[--color-primary:var(--color-cyan-500)]',
        'sky' => $colorInvert ? '[--color-primary-content:var(--color-sky-300)] [--color-primary-text:var(--color-sky-950)] [--color-primary-shadow:var(--color-sky-800)] [--color-primary:var(--color-sky-500)] dark:[--color-primary-content:var(--color-sky-600)] dark:[--color-primary-text:var(--color-sky-950)] dark:[--color-primary-shadow:var(--color-sky-700)] dark:[--color-primary:var(--color-sky-500)]' : '[--color-primary-content:var(--color-sky-600)] [--color-primary-text:var(--color-sky-50)] [--color-primary-shadow:var(--color-sky-700)] [--color-primary:var(--color-sky-500)] dark:[--color-primary-content:var(--color-sky-300)] dark:[--color-primary-text:var(--color-sky-50)] dark:[--color-primary-shadow:var(--color-sky-800)] dark:[--color-primary:var(--color-sky-500)]',
        'blue' => $colorInvert ? '[--color-primary-content:var(--color-blue-300)] [--color-primary-text:var(--color-blue-950)] [--color-primary-shadow:var(--color-blue-800)] [--color-primary:var(--color-blue-500)] dark:[--color-primary-content:var(--color-blue-600)] dark:[--color-primary-text:var(--color-blue-950)] dark:[--color-primary-shadow:var(--color-blue-700)] dark:[--color-primary:var(--color-blue-500)]' : '[--color-primary-content:var(--color-blue-600)] [--color-primary-text:var(--color-blue-50)] [--color-primary-shadow:var(--color-blue-700)] [--color-primary:var(--color-blue-500)] dark:[--color-primary-content:var(--color-blue-300)] dark:[--color-primary-text:var(--color-blue-50)] dark:[--color-primary-shadow:var(--color-blue-800)] dark:[--color-primary:var(--color-blue-500)]',
        'indigo' => $colorInvert ? '[--color-primary-content:var(--color-indigo-300)] [--color-primary-text:var(--color-indigo-950)] [--color-primary-shadow:var(--color-indigo-800)] [--color-primary:var(--color-indigo-500)] dark:[--color-primary-content:var(--color-indigo-600)] dark:[--color-primary-text:var(--color-indigo-950)] dark:[--color-primary-shadow:var(--color-indigo-700)] dark:[--color-primary:var(--color-indigo-500)]' : '[--color-primary-content:var(--color-indigo-600)] [--color-primary-text:var(--color-indigo-50)] [--color-primary-shadow:var(--color-indigo-700)] [--color-primary:var(--color-indigo-500)] dark:[--color-primary-content:var(--color-indigo-300)] dark:[--color-primary-text:var(--color-indigo-50)] dark:[--color-primary-shadow:var(--color-indigo-800)] dark:[--color-primary:var(--color-indigo-500)]',
        'violet' => $colorInvert ? '[--color-primary-content:var(--color-violet-300)] [--color-primary-text:var(--color-violet-950)] [--color-primary-shadow:var(--color-violet-800)] [--color-primary:var(--color-violet-500)] dark:[--color-primary-content:var(--color-violet-600)] dark:[--color-primary-text:var(--color-violet-950)] dark:[--color-primary-shadow:var(--color-violet-700)] dark:[--color-primary:var(--color-violet-500)]' : '[--color-primary-content:var(--color-violet-600)] [--color-primary-text:var(--color-violet-50)] [--color-primary-shadow:var(--color-violet-700)] [--color-primary:var(--color-violet-500)] dark:[--color-primary-content:var(--color-violet-300)] dark:[--color-primary-text:var(--color-violet-50)] dark:[--color-primary-shadow:var(--color-violet-800)] dark:[--color-primary:var(--color-violet-500)]',
        'purple' => $colorInvert ? '[--color-primary-content:var(--color-purple-300)] [--color-primary-text:var(--color-purple-950)] [--color-primary-shadow:var(--color-purple-800)] [--color-primary:var(--color-purple-500)] dark:[--color-primary-content:var(--color-purple-600)] dark:[--color-primary-text:var(--color-purple-950)] dark:[--color-primary-shadow:var(--color-purple-700)] dark:[--color-primary:var(--color-purple-500)]' : '[--color-primary-content:var(--color-purple-600)] [--color-primary-text:var(--color-purple-50)] [--color-primary-shadow:var(--color-purple-700)] [--color-primary:var(--color-purple-500)] dark:[--color-primary-content:var(--color-purple-300)] dark:[--color-primary-text:var(--color-purple-50)] dark:[--color-primary-shadow:var(--color-purple-800)] dark:[--color-primary:var(--color-purple-500)]',
        'fuchsia' => $colorInvert ? '[--color-primary-content:var(--color-fuchsia-300)] [--color-primary-text:var(--color-fuchsia-950)] [--color-primary-shadow:var(--color-fuchsia-800)] [--color-primary:var(--color-fuchsia-500)] dark:[--color-primary-content:var(--color-fuchsia-600)] dark:[--color-primary-text:var(--color-fuchsia-950)] dark:[--color-primary-shadow:var(--color-fuchsia-700)] dark:[--color-primary:var(--color-fuchsia-500)]' : '[--color-primary-content:var(--color-fuchsia-600)] [--color-primary-text:var(--color-fuchsia-50)] [--color-primary-shadow:var(--color-fuchsia-700)] [--color-primary:var(--color-fuchsia-500)] dark:[--color-primary-content:var(--color-fuchsia-300)] dark:[--color-primary-text:var(--color-fuchsia-50)] dark:[--color-primary-shadow:var(--color-fuchsia-800)] dark:[--color-primary:var(--color-fuchsia-500)]',
        'pink' => $colorInvert ? '[--color-primary-content:var(--color-pink-300)] [--color-primary-text:var(--color-pink-950)] [--color-primary-shadow:var(--color-pink-800)] [--color-primary:var(--color-pink-500)] dark:[--color-primary-content:var(--color-pink-600)] dark:[--color-primary-text:var(--color-pink-950)] dark:[--color-primary-shadow:var(--color-pink-700)] dark:[--color-primary:var(--color-pink-500)]' : '[--color-primary-content:var(--color-pink-600)] [--color-primary-text:var(--color-pink-50)] [--color-primary-shadow:var(--color-pink-700)] [--color-primary:var(--color-pink-500)] dark:[--color-primary-content:var(--color-pink-300)] dark:[--color-primary-text:var(--color-pink-50)] dark:[--color-primary-shadow:var(--color-pink-800)] dark:[--color-primary:var(--color-pink-500)]',
        'rose' => $colorInvert ? '[--color-primary-content:var(--color-rose-300)] [--color-primary-text:var(--color-rose-950)] [--color-primary-shadow:var(--color-rose-800)] [--color-primary:var(--color-rose-500)] dark:[--color-primary-content:var(--color-rose-600)] dark:[--color-primary-text:var(--color-rose-950)] dark:[--color-primary-shadow:var(--color-rose-700)] dark:[--color-primary:var(--color-rose-500)]' : '[--color-primary-content:var(--color-rose-600)] [--color-primary-text:var(--color-rose-50)] [--color-primary-shadow:var(--color-rose-700)] [--color-primary:var(--color-rose-500)] dark:[--color-primary-content:var(--color-rose-300)] dark:[--color-primary-text:var(--color-rose-50)] dark:[--color-primary-shadow:var(--color-rose-800)] dark:[--color-primary:var(--color-rose-500)]',
        'slate' => $colorInvert ? '[--color-primary-content:var(--color-slate-800)] [--color-primary-text:var(--color-slate-50)] [--color-primary-shadow:var(--color-slate-950)] [--color-primary:var(--color-slate-800)] dark:[--color-primary-content:var(--color-slate-50)] dark:[--color-primary-text:var(--color-slate-800)] dark:[--color-primary-shadow:var(--color-slate-200)] dark:[--color-primary:var(--color-slate-50)]' : '[--color-primary-content:var(--color-slate-50)] [--color-primary-text:var(--color-slate-800)] [--color-primary-shadow:var(--color-slate-200)] [--color-primary:var(--color-slate-50)] dark:[--color-primary-content:var(--color-slate-800)] dark:[--color-primary-text:var(--color-slate-50)] dark:[--color-primary-shadow:var(--color-slate-950)] dark:[--color-primary:var(--color-slate-800)]',
        'gray' => $colorInvert ? '[--color-primary-content:var(--color-gray-700)] [--color-primary-text:var(--color-gray-50)] [--color-primary-shadow:var(--color-gray-950)] [--color-primary:var(--color-gray-700)] dark:[--color-primary-content:var(--color-gray-50)] dark:[--color-primary-text:var(--color-gray-700)] dark:[--color-primary:var(--color-gray-50)] dark:[--color-primary-shadow:var(--color-gray-200)] ' : '[--color-primary-content:var(--color-gray-50)] [--color-primary-text:var(--color-gray-700)] [--color-primary-shadow:var(--color-gray-200)] [--color-primary:var(--color-gray-50)] dark:[--color-primary-content:var(--color-gray-700)] dark:[--color-primary-text:var(--color-gray-50)] dark:[--color-primary-shadow:var(--color-gray-950)] dark:[--color-primary:var(--color-gray-700)]',
        'zinc' => $colorInvert ? '[--color-primary-content:var(--color-zinc-800)] [--color-primary-text:var(--color-zinc-50)] [--color-primary-shadow:var(--color-zinc-950)] [--color-primary:var(--color-zinc-800)] dark:[--color-primary-content:var(--color-zinc-50)] dark:[--color-primary-text:var(--color-zinc-800)] dark:[--color-primary-shadow:var(--color-zinc-200)] dark:[--color-primary:var(--color-zinc-50)]' : '[--color-primary-content:var(--color-zinc-50)] [--color-primary-text:var(--color-zinc-800)] [--color-primary-shadow:var(--color-zinc-200)] [--color-primary:var(--color-zinc-50)] dark:[--color-primary-content:var(--color-zinc-800)] dark:[--color-primary-text:var(--color-zinc-50)] dark:[--color-primary-shadow:var(--color-zinc-950)] dark:[--color-primary:var(--color-zinc-800)]',
        'neutral' => $colorInvert ? '[--color-primary-content:var(--color-neutral-800)] [--color-primary-text:var(--color-neutral-50)] [--color-primary-shadow:var(--color-neutral-950)] [--color-primary:var(--color-neutral-800)] dark:[--color-primary-content:var(--color-neutral-50)] dark:[--color-primary-text:var(--color-neutral-800)] dark:[--color-primary-shadow:var(--color-neutral-200)] dark:[--color-primary:var(--color-neutral-50)]' : '[--color-primary-content:var(--color-neutral-50)] [--color-primary-text:var(--color-neutral-800)] [--color-primary-shadow:var(--color-neutral-200)] [--color-primary:var(--color-neutral-50)] dark:[--color-primary-content:var(--color-neutral-800)] dark:[--color-primary-text:var(--color-neutral-50)] dark:[--color-primary-shadow:var(--color-neutral-950)] dark:[--color-primary:var(--color-neutral-800)]',
        'stone' => $colorInvert ? '[--color-primary-content:var(--color-stone-800)] [--color-primary-text:var(--color-stone-50)] [--color-primary-shadow:var(--color-stone-950)] [--color-primary:var(--color-stone-800)] dark:[--color-primary-content:var(--color-stone-50)] dark:[--color-primary-text:var(--color-stone-800)] dark:[--color-primary-shadow:var(--color-stone-200)] dark:[--color-primary:var(--color-stone-50)]' : '[--color-primary-content:var(--color-stone-50)] [--color-primary-text:var(--color-stone-800)] [--color-primary-shadow:var(--color-stone-200)] [--color-primary:var(--color-stone-50)] dark:[--color-primary-content:var(--color-stone-800)] dark:[--color-primary-text:var(--color-stone-50)] dark:[--color-primary-shadow:var(--color-stone-950)] dark:[--color-primary:var(--color-stone-800)]',
        'secondary' => $colorInvert ? '[--color-primary-content:var(--color-secondary-300)] [--color-primary-text:var(--color-secondary-950)] [--color-primary-shadow:var(--color-secondary-800)] [--color-primary:var(--color-secondary-500)] dark:[--color-primary-content:var(--color-secondary-600)] dark:[--color-primary-text:var(--color-secondary-950)] dark:[--color-primary-shadow:var(--color-secondary-700)] dark:[--color-primary:var(--color-secondary-500)]' : '[--color-primary-content:var(--color-secondary-600)] [--color-primary-text:var(--color-secondary-50)] [--color-primary-shadow:var(--color-secondary-700)] [--color-primary:var(--color-secondary-500)] dark:[--color-primary-content:var(--color-secondary-300)] dark:[--color-primary-text:var(--color-secondary-50)] dark:[--color-primary-shadow:var(--color-secondary-800)] dark:[--color-primary:var(--color-secondary-500)]',
        'primary' => $colorInvert ? '[--color-primary-content:var(--color-primary-300)] [--color-primary-text:var(--color-primary-950)] [--color-primary-shadow:var(--color-primary-800)] [--color-primary:var(--color-primary-500)] dark:[--color-primary-content:var(--color-primary-600)] dark:[--color-primary-text:var(--color-primary-950)] dark:[--color-primary-shadow:var(--color-primary-700)] dark:[--color-primary:var(--color-primary-500)]' : '[--color-primary-content:var(--color-primary-600)] [--color-primary-text:var(--color-primary-50)] [--color-primary-shadow:var(--color-primary-700)] [--color-primary:var(--color-primary-500)] dark:[--color-primary-content:var(--color-primary-300)] dark:[--color-primary-text:var(--color-primary-50)] dark:[--color-primary-shadow:var(--color-primary-800)] dark:[--color-primary:var(--color-primary-500)]',
        default => '',
    };

    // Determine variant-specific classes for background, text, borders, and hover states
    $variantClasses = match ($variant) {
        'primary' => [
            'bg-primary hover:bg-primary/80', // Background color
            'text-primary-text', // Text color
            'border border-black/10 dark:border-0', // Border styles
            'shadow-primary-shadow shadow-sm', // Shadow styles
            $colors => filled($color), // Ensure variables are set
        ],
        'solid' => [
            'bg-primary hover:bg-primary/80', // Background color
            'text-primary-text', // Text color
            $colors => filled($color), // Ensure variables are set
        ],
        'soft' => [
            'text-primary hover:text-primary/50', // Text color
            'bg-transparent',
            $colors => filled($color), // Ensure variables are set
        ],
        'outline' => [
            'border-primary/50 hover:border-primary/20 border-2', // Border
            'bg-primary/5 hover:bg-primary', // Background
            'text-primary hover:text-primary-text', // Text color
            $colors => filled($color), // Ensure variables are set
        ],
        'ghost' => [
            'hover:bg-primary/50 dark:hover:bg-primary/30 bg-transparent', // Background colors
            'text-primary dark:text-primary-text', // Text colors
            $colors => filled($color), // Ensure variables are set
        ],
        'danger' => [
            'bg-red-600 hover:bg-red-700 dark:bg-red-800 dark:hover:bg-red-700', // Background colors
            'text-white', // Text colors
        ],
        'none' => [],
        default => []
    };

    // Assemble base button classes, including layout, disabled states, and conditional styles
    $classes = [
        'relative inline-flex items-center justify-center gap-x-2 font-medium whitespace-nowrap transition-colors duration-200',
        'cursor-pointer disabled:pointer-events-none disabled:cursor-default disabled:opacity-75 dark:disabled:opacity-75',
        '[&_a]:decoration-none [&_a]:no-underline [&_a:hover]:no-underline' => $variant !== 'none', // Handle anchor tags inside the button
        'rounded-sm' => $variant !== 'none', // Apply rounding unless variant is 'none'

        // Handling loading logic via CSS: Show loading indicator as flex and set opacity-0 on its siblings
        '[&>[data-loading=true]:first-child]:flex', // Override 'hidden' to display the loading div during loading
        '[&>[data-loading=true]:first-child~*]:opacity-0', // Apply opacity-0 to all subsequent children (e.g., icons, text)
        $sizeClasses,
        ...$variantClasses,
    ];

    /* LOADING LOGIC - START */
    $loadingAttributes = new Illuminate\View\ComponentAttributeBag;
    $hasWireLoading = false;

    if ($loading) {
        // Check if any wire:loading attributes are present for dynamic handling
        $hasWireLoading = filled($attributes->whereStartsWith('wire:loading')->first());

        // Configure loading attributes for Livewire actions (adds data-loading="true" during loading)
        $loadingAttributes = $loadingAttributes->merge($hasWireLoading || $type === 'submit' ? [
            'wire:loading.attr' => 'data-loading',
            'wire:target' => $attributes->has('wire:target') ? $attributes->get('wire:target') : ($attributes->whereStartsWith('wire:click')->first() ?? null),
        ] : []);

        // aria-busy attribute will be added when wire:loading is active via the main attributes merge below
        $attributes = $attributes->merge($hasWireLoading || $type === 'submit' ? [
            'wire:loading.attr' => 'aria-busy',
        ] : []);
    }
    /* LOADING LOGIC - END */
@endphp

<x-ui.form.button.abstract
    :$as
    :$type
    :attributes="
        $attributes->sanitize()->tailwindClass($classes)->merge([
            'role' => $as === 'a' && ! $attributes->has('href') ? 'button' : null,
            'aria-disabled' => $attributes->has('disabled') ? 'true' : 'false',
            // I know it basic, but you can create mapping labels for popular icons like ['x-mark' => 'Close']...
            'aria-label' => $squared && blank($slot) ? __('component.form.button.label') : null,
        ])
    "
    data-slot="button"
>
    @if ($loading)
        <div
            @class ([
                'absolute inset-0 hidden items-center justify-center ',
            ])
            {{-- the is just adding here data-loading="true" to shows loading icon,  you can add it manually, using php, js ... --}}
            {{ $loadingAttributes }}
        >
            <x-ui.icon
                name="spinner.180-ring-with-bg"
                data-slot="loading-indicator"
                @class([
                    $size !== 'xs' ? 'size-5' : 'size-4',
                ])
            />
        </div>
    @endif

    @if ($before->isNotEmpty())
        {{ $before }}
    @endif

    @if ($slot->isNotEmpty())
        <span> {{ $slot }} </span>
    @endif

    @if ($after->isNotEmpty())
        {{ $after }}
    @endif
</x-ui.form.button.abstract>
