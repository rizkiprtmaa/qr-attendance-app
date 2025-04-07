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

    <script src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@material-tailwind/html@latest/styles/material-tailwind.css" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    @livewireStyles

    <style>
        #reader {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        #reader video {
            max-width: 100% !important;
            height: auto !important;
            object-fit: contain !important;
        }
    </style>
</head>

<body class="font-inter antialiased">
    <div class="flex min-h-screen bg-gray-100">

        <livewire:admin.attendance-qr-scanner />

    </div>
</body>
@livewireScripts

</html>
