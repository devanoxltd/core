@props ([
    'title' => config('app.name'),
    'description' => null,
    'dir' => in_array(app()->getLocale(), ['ar', 'he', 'fa', 'ur']) ? 'rtl' : 'ltr',
    'head' => new Illuminate\View\ComponentSlot,
    'html' => new Illuminate\View\ComponentSlot,
])

<!DOCTYPE html>
<html
    {{ $html->attributes->sanitize()->merge([
        'lang' => str_replace('_', '-', app()->getLocale()),
        'dir' => $dir
    ])->tailwindClass() }}
>
<head {{ $head->attributes->sanitize()->tailwindClass() }}>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title ?? config('app.name') }}</title>

    @if (isset($description) && $description)
        <meta name="description" content="{{ $description }}" />
    @endif

    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

    @fonts

    <!-- Styles & Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite (['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
            }
        </script>
        @livewireScripts
    @endif

    @livewireStyles

    {{ $head }}
</head>
<body
    {{ $attributes->sanitize()->tailwindClass('font-sans min-h-dvh min-w-dvw antialiased transition-all duration-200 bg-white text-gray-950 dark:bg-gray-950 dark:text-gray-50') }}
>
    {{ $slot }}

    <x-ui.utilities.theme-switcher class="fixed top-4 right-4 sm:top-6 sm:right-6" />
    @persist ('toast')
        <x-ui.feedback.toast />
    @endpersist

    @livewireScriptConfig
</body>
</html>
