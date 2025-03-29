<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light!">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
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


    <script type="module" src="https://unpkg.com/cally"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        /* Memastikan dropdown menu selalu terlihat */
        [x-cloak] {
            display: none !important;
        }

        /* Prioritaskan z-index dropdown */
        .z-50 {
            z-index: 50 !important;
        }

        /* Perbaikan posisi dropdown di mobile */
        @media (max-width: 768px) {
            .origin-top-right.right-0 {
                right: 0 !important;
                left: auto !important;
            }
        }
    </style>

    @livewireStyles
    @fluxAppearance
</head>

<body class="my-0 font-sans antialiased" style="margin-bottom: none!important">
    <div class="flex min-h-screen bg-gray-100">
        <livewire:layout.navigation />

        <main class="flex-1 p-4 transition-all duration-300 ease-in-out md:ml-64">
            @if (isset($header))
                <header class="mt-10 w-full md:mt-0">
                    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif
            <div class="w-full">
                {{ $slot }}
            </div>
        </main>
    </div>
    @fluxScripts
</body>
@livewireScripts

</html>
