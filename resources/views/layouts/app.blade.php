<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased">
    <div class="flex min-h-screen bg-gray-100">
        <livewire:layout.navigation />

        <main class="flex-1 p-6 md:ml-64">
            @if (isset($header))
                <header class="">
                    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
@livewireScripts

</html>
