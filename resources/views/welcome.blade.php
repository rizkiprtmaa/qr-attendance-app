<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Sistem presensi berbasis QR code untuk SMK Nurussalam Salopa - solusi modern untuk manajemen kehadiran yang efisien dan akurat">

    <title>SMK Nurussalam - Sistem Presensi QR</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-50 font-inter text-gray-900 antialiased">
    <!-- Navbar -->
    <header class="fixed left-0 right-0 top-0 z-30 bg-white/90 shadow-sm backdrop-blur-sm">
        <div class="container mx-auto px-4 sm:px-6 lg:px-24" x-data="{ open: false }">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center space-x-2">
                    <x-application-logo class="h-8 w-auto" />
                </div>

                <!-- Desktop Menu -->
                <nav class="hidden items-center space-x-8 md:flex">
                    <a href="#fitur" class="text-sm font-medium transition duration-150 hover:text-blue-600">Fitur</a>
                    <a href="#dashboard"
                        class="text-sm font-medium transition duration-150 hover:text-blue-600">Dashboard</a>
                    <a href="#manfaat"
                        class="text-sm font-medium transition duration-150 hover:text-blue-600">Manfaat</a>
                    <a href="#bantuan" class="text-sm font-medium transition duration-150 hover:text-blue-600">FAQ</a>
                </nav>

                <div class="flex items-center space-x-4">
                    <a href="{{ route('login') }}"
                        class="hidden items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:inline-flex">
                        Masuk
                    </a>

                    <!-- Mobile menu button -->
                    <button type="button"
                        class="rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 md:hidden"
                        aria-controls="mobile-menu" aria-expanded="false" @click="open = !open"
                        :aria-expanded="open.toString()">
                        <span class="sr-only">Buka menu</span>
                        <!-- Icon when menu is closed -->
                        <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <!-- Icon when menu is open -->
                        <svg x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" aria-hidden="true" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu, show/hide based on menu state -->
            <div class="md:hidden" id="mobile-menu" x-show="open" @click.away="open = false" style="display: none;">
                <div class="space-y-1 px-2 pb-3 pt-2">
                    <a href="#fitur"
                        class="block rounded-md px-3 py-2 text-base font-medium hover:bg-gray-100 hover:text-blue-600">Fitur</a>
                    <a href="#dashboard"
                        class="block rounded-md px-3 py-2 text-base font-medium hover:bg-gray-100 hover:text-blue-600">Dashboard</a>
                    <a href="#manfaat"
                        class="block rounded-md px-3 py-2 text-base font-medium hover:bg-gray-100 hover:text-blue-600">Manfaat</a>
                    <a href="#bantuan"
                        class="block rounded-md px-3 py-2 text-base font-medium hover:bg-gray-100 hover:text-blue-600">FAQ</a>
                    <div class="pt-2">
                        <a href="{{ route('login') }}"
                            class="flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            Masuk
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative min-h-[80vh] overflow-hidden pb-20 pt-24">
        <div class="absolute inset-0 z-0 bg-gradient-to-br from-blue-50 to-cyan-50 opacity-70"></div>
        <div class="absolute right-0 top-1/4 -z-10 translate-x-1/3 transform">
            <svg width="404" height="404" fill="none" viewBox="0 0 404 404" aria-hidden="true"
                class="text-blue-100 opacity-50">
                <defs>
                    <pattern id="85737c0e-0916-41d7-917f-596dc7edfa27" x="0" y="0" width="20" height="20"
                        patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="4" height="4" class="text-blue-200" fill="currentColor">
                        </rect>
                    </pattern>
                </defs>
                <rect width="404" height="404" fill="url(#85737c0e-0916-41d7-917f-596dc7edfa27)"></rect>
            </svg>
        </div>
        <div class="absolute bottom-0 left-0 -z-10 -translate-x-1/3 translate-y-1/4 transform">
            <svg width="404" height="404" fill="none" viewBox="0 0 404 404" aria-hidden="true"
                class="text-blue-100 opacity-50">
                <defs>
                    <pattern id="85737c0e-0916-41d7-917f-596dc7edfa28" x="0" y="0" width="20" height="20"
                        patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="4" height="4" class="text-blue-200" fill="currentColor">
                        </rect>
                    </pattern>
                </defs>
                <rect width="404" height="404" fill="url(#85737c0e-0916-41d7-917f-596dc7edfa28)"></rect>
            </svg>
        </div>
        <div class="container relative z-10 mx-auto mt-10 px-6 md:mt-24 lg:px-24">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                <div class="text-center lg:text-left">
                    <div
                        class="mb-6 inline-flex items-center rounded-full bg-blue-100 px-4 py-1 text-sm font-semibold text-blue-600">
                        Menghadirkan Sistem Presensi Modern
                    </div>
                    <h1 class="mb-6 text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl">
                        Manajemen Kehadiran dengan
                        <span class="relative text-blue-600">QR Code</span>
                    </h1>
                    <p class="mb-8 max-w-full text-lg text-gray-600 lg:max-w-lg">
                        Rasakan kemudahan dan efisiensi sistem presensi QR code terbaru di SMK Nurussalam. Pantau
                        kehadiran, kelola izin, dan dapatkan laporan secara real-time.
                    </p>
                    <div class="flex w-full flex-col justify-center gap-4 sm:flex-row lg:justify-start">
                        <a href="{{ route('login') }}"
                            class="inline-flex transform items-center justify-center rounded-md bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-lg transition duration-150 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-xl">
                            Masuk Sekarang
                        </a>
                        <a href="#bantuan"
                            class="inline-flex items-center justify-center rounded-md border border-blue-200 bg-white px-6 py-3 text-base font-medium text-blue-600 transition duration-150 hover:bg-blue-50">
                            Pelajari Lebih Lanjut
                        </a>
                    </div>
                </div>
                <div class="">
                    <img src="{{ asset('images/hero-image.png') }}" alt="Hero Image" class="mx-auto max-w-full">
                </div>
            </div>
        </div>
    </section>

    <!-- Statistik Section -->
    <section class="bg-white py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-24">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div class="text-center">
                    <div class="mb-2 text-4xl font-bold text-blue-600">95%</div>
                    <p class="text-gray-600">Tingkat Efisiensi</p>
                </div>
                <div class="text-center">
                    <div class="mb-2 text-4xl font-bold text-blue-600">250+</div>
                    <p class="text-gray-600">Siswa Terdaftar</p>
                </div>
                <div class="text-center">
                    <div class="mb-2 text-4xl font-bold text-blue-600">50+</div>
                    <p class="text-gray-600">Guru & Staf</p>
                </div>
                <div class="text-center">
                    <div class="mb-2 text-4xl font-bold text-blue-600">24/7</div>
                    <p class="text-gray-600">Dukungan Online</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Fitur Section -->
    <section id="fitur" class="bg-gray-50 py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-24">
            <div class="mb-16 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900">Fitur Unggulan</h2>
                <p class="mx-auto max-w-3xl text-lg text-gray-600">
                    Nikmati kemudahan manajemen presensi dengan teknologi QR code yang inovatif
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <!-- Fitur 1 -->
                <div
                    class="rounded-xl bg-white p-6 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-3 text-xl font-semibold">Presensi QR Code</h3>
                    <p class="text-gray-600">
                        Sistem presensi modern dengan scan QR code yang cepat dan aman. Mengurangi kemungkinan titip
                        absen.
                    </p>
                </div>

                <!-- Fitur 2 -->
                <div
                    class="rounded-xl bg-white p-6 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-3 text-xl font-semibold">Manajemen Izin</h3>
                    <p class="text-gray-600">
                        Pengajuan dan persetujuan izin secara online dengan bukti digital yang bisa dilampirkan.
                    </p>
                </div>

                <!-- Fitur 3 -->
                <div
                    class="rounded-xl bg-white p-6 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-lg bg-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-3 text-xl font-semibold">Laporan Real-time dan Terintegrasi</h3>
                    <p class="text-gray-600">
                        Monitor kehadiran secara real-time dengan dashboard intuitif, terintegrasi dengan whatsapp dan
                        laporan terperinci.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Preview Section -->
    <section id="dashboard" class="overflow-hidden bg-white py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-24">
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900">Dashboard Intuitif</h2>
                <p class="mx-auto max-w-3xl text-lg text-gray-600">
                    Pantau kehadiran dengan tampilan dashboard yang informatif dan mudah digunakan
                </p>
            </div>

            <div class="relative">
                <div
                    class="absolute inset-0 z-0 -skew-y-6 transform rounded-3xl bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500">
                </div>
                <div class="relative z-10 overflow-hidden rounded-xl bg-white shadow-2xl">
                    <div class="aspect-w-16 aspect-h-9">
                        <img src="{{ asset('images/login-img.png') }}" class="object-cover">
                    </div>
                </div>
            </div>

            <!-- Fitur Dashboard -->
            <div class="mt-20 grid grid-cols-1 gap-6 md:mt-32 md:grid-cols-3">
                <div class="flex items-start">
                    <div
                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Analitik Kehadiran</h3>
                        <p class="mt-2 text-base text-gray-600">
                            Visualisasi data kehadiran dalam bentuk grafik dan diagram untuk analisis yang lebih mudah.
                        </p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div
                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Notifikasi</h3>
                        <p class="mt-2 text-base text-gray-600">
                            Dapatkan notifikasi real-time untuk keterlambatan, ketidakhadiran, dan permohonan izin.
                        </p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div
                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Ekspor Laporan</h3>
                        <p class="mt-2 text-base text-gray-600">
                            Ekspor laporan kehadiran dalam format PDF, Excel, dan CSV untuk keperluan administrasi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Manfaat Section -->
    <section id="manfaat" class="bg-gradient-to-br from-gray-50 to-blue-50 py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-24">
            <div class="mb-16 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900">Manfaat Utama</h2>
                <p class="mx-auto max-w-3xl text-lg text-gray-600">
                    Rasakan perbedaan dalam pengelolaan kehadiran siswa dan guru di sekolah Anda
                </p>
            </div>

            <div class="grid grid-cols-1 gap-x-8 gap-y-12 md:grid-cols-2">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-md bg-blue-600 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Efisiensi Waktu</h3>
                        <p class="mt-2 text-base text-gray-600">
                            Sistem presensi QR code memangkas waktu absensi manual. Tidak perlu lagi panggilan nama satu
                            per satu atau tanda tangan di kertas.
                        </p>
                    </div>
                </div>

                <div class="flex">
                    <div class="flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-md bg-blue-600 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Keakuratan Data</h3>
                        <p class="mt-2 text-base text-gray-600">
                            Menghilangkan kesalahan manual dalam pencatatan kehadiran. Data absensi menjadi lebih akurat
                            dan dapat dipertanggungjawabkan.
                        </p>
                    </div>
                </div>

                <div class="flex">
                    <div class="flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-md bg-blue-600 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Analisis & Pemantauan</h3>
                        <p class="mt-2 text-base text-gray-600">
                            Dasbor analitis memberikan wawasan tentang pola kehadiran dan ketidakhadiran untuk
                            pengambilan keputusan yang lebih baik.
                        </p>
                    </div>
                </div>

                <div class="flex">
                    <div class="flex-shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-md bg-blue-600 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Komunikasi yang Lebih Baik</h3>
                        <p class="mt-2 text-base text-gray-600">
                            Notifikasi otomatis kepada orang tua/wali siswa mengenai kehadiran atau ketidakhadiran anak
                            mereka di sekolah.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- CTA Section -->
    <section class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-blue-800 py-16 text-white">
        <div class="absolute inset-0 opacity-10">
            <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="grid-pattern" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid-pattern)" />
            </svg>
        </div>
        <div class="container relative z-10 mx-auto px-4 sm:px-6 lg:px-24">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="mb-6 text-3xl font-bold">Siap Untuk Modernisasi Presensi Sekolah Anda?</h2>
                <p class="mb-8 text-lg text-blue-100">
                    Mulai gunakan sistem presensi QR code sekarang dan rasakan perbedaannya dalam manajemen
                    kehadiran siswa dan guru.
                </p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-md bg-white px-6 py-3 text-base font-medium text-blue-600 shadow-lg transition duration-150 hover:bg-blue-50">
                        Masuk Sekarang
                    </a>
                    <a href="#bantuan"
                        class="inline-flex items-center justify-center rounded-md border border-white bg-transparent px-6 py-3 text-base font-medium text-white transition duration-150 hover:bg-blue-700">
                        Pelajari Lebih Lanjut
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="bantuan" class="bg-white py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-24">
            <div class="mb-16 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900">Pertanyaan Umum</h2>
                <p class="mx-auto max-w-3xl text-lg text-gray-600">
                    Jawaban untuk pertanyaan yang sering ditanyakan tentang sistem presensi QR
                </p>
            </div>

            <div class="mx-auto max-w-3xl">
                <div x-data="{ activeAccordion: 'none' }" class="divide-y divide-gray-200">
                    <!-- FAQ Item 1 -->
                    <div class="py-6">
                        <button @click="activeAccordion = activeAccordion === '1' ? 'none' : '1'"
                            class="flex w-full items-center justify-between text-left focus:outline-none">
                            <h3 class="text-lg font-medium text-gray-900">Bagaimana cara menggunakan presensi QR?</h3>
                            <span class="ml-6 flex h-7 items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-300"
                                    :class="{ 'rotate-180': activeAccordion === '1', 'rotate-0': activeAccordion !== '1' }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="activeAccordion === '1'" x-collapse x-cloak>
                            <div class="mt-3 pr-12">
                                <p class="text-base text-gray-600">
                                    Siswa dan guru cukup membuka aplikasi, pilih menu scan QR, kemudian arahkan kamera
                                    ke kode QR yang tersedia di lokasi yang ditentukan. Sistem akan secara otomatis
                                    mencatat kehadiran dengan timestamp dan lokasi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 2 -->
                    <div class="py-6">
                        <button @click="activeAccordion = activeAccordion === '2' ? 'none' : '2'"
                            class="flex w-full items-center justify-between text-left focus:outline-none">
                            <h3 class="text-lg font-medium text-gray-900">Bagaimana cara mengajukan izin atau sakit?
                            </h3>
                            <span class="ml-6 flex h-7 items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-300"
                                    :class="{ 'rotate-180': activeAccordion === '2', 'rotate-0': activeAccordion !== '2' }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="activeAccordion === '2'" x-collapse x-cloak>
                            <div class="mt-3 pr-12">
                                <p class="text-base text-gray-600">
                                    Siswa atau orang tua dapat mengajukan izin atau sakit melalui aplikasi. Cukup pilih
                                    menu "Pengajuan Izin", isi formulir yang tersedia, lampirkan bukti jika diperlukan
                                    (seperti surat dokter), dan tunggu persetujuan dari wali kelas atau administrator.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 3 -->
                    <div class="py-6">
                        <button @click="activeAccordion = activeAccordion === '3' ? 'none' : '3'"
                            class="flex w-full items-center justify-between text-left focus:outline-none">
                            <h3 class="text-lg font-medium text-gray-900">Apakah orang tua dapat memantau kehadiran
                                anak?</h3>
                            <span class="ml-6 flex h-7 items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-300"
                                    :class="{ 'rotate-180': activeAccordion === '3', 'rotate-0': activeAccordion !== '3' }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="activeAccordion === '3'" x-collapse x-cloak>
                            <div class="mt-3 pr-12">
                                <p class="text-base text-gray-600">
                                    Ya, orang tua dapat memantau kehadiran anak mereka melalui aplikasi dengan login
                                    menggunakan akun yang terhubung dengan data siswa. Mereka akan menerima notifikasi
                                    kehadiran dan dapat melihat rekap kehadiran bulanan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 4 -->
                    <div class="py-6">
                        <button @click="activeAccordion = activeAccordion === '4' ? 'none' : '4'"
                            class="flex w-full items-center justify-between text-left focus:outline-none">
                            <h3 class="text-lg font-medium text-gray-900">Bagaimana jika siswa kehilangan QR code
                                miliknya?</h3>
                            <span class="ml-6 flex h-7 items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-300"
                                    :class="{ 'rotate-180': activeAccordion === '4', 'rotate-0': activeAccordion !== '4' }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="activeAccordion === '4'" x-collapse x-cloak>
                            <div class="mt-3 pr-12">
                                <p class="text-base text-gray-600">
                                    Jika siswa kehilangan QR code miliknya, mereka dapat melaporkan ke admin sistem atau
                                    wali kelas. Admin akan melakukan reset dan menghasilkan QR code baru yang dapat
                                    diakses melalui akun siswa tersebut. QR code lama akan otomatis tidak berlaku lagi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 5 -->
                    <div class="py-6">
                        <button @click="activeAccordion = activeAccordion === '5' ? 'none' : '5'"
                            class="flex w-full items-center justify-between text-left focus:outline-none">
                            <h3 class="text-lg font-medium text-gray-900">Bagaimana keamanan sistem Presensi ini?</h3>
                            <span class="ml-6 flex h-7 items-center">
                                <svg class="h-6 w-6 transform transition-transform duration-300"
                                    :class="{ 'rotate-180': activeAccordion === '5', 'rotate-0': activeAccordion !== '5' }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="activeAccordion === '5'" x-collapse x-cloak>
                            <div class="mt-3 pr-12">
                                <p class="text-base text-gray-600">
                                    Aplikasi ini menggunakan teknologi enkripsi terkini untuk melindungi data pengguna.
                                    QR
                                    code bersifat unik untuk setiap pengguna dan memiliki masa berlaku tertentu. Sistem
                                    juga dilengkapi dengan geolokasi untuk memastikan kehadiran benar-benar di lokasi
                                    yang ditentukan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-12 sm:px-6 lg:px-24">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center">
                        <x-application-logo class="h-10 w-auto" />
                        <span class="ml-2 text-xl font-bold text-white">SMK Nurussalam</span>
                    </div>
                    <p class="mt-4 max-w-md text-base text-gray-400">
                        Sistem presensi berbasis QR code untuk SMK Nurussalam. Solusi modern untuk manajemen
                        kehadiran yang efisien dan akurat.
                    </p>
                    <div class="mt-6 flex space-x-6">
                        <a href="#" class="text-gray-400 hover:text-white">
                            <span class="sr-only">Facebook</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                                    clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <span class="sr-only">Instagram</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <span class="sr-only">YouTube</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 0 1-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 0 1-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 0 1 1.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418ZM15.194 12 10 15V9l5.194 3Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400">Tautan</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="#fitur" class="text-base text-gray-400 hover:text-white">Fitur</a></li>
                        <li><a href="#dashboard" class="text-base text-gray-400 hover:text-white">Dashboard</a></li>
                        <li><a href="#manfaat" class="text-base text-gray-400 hover:text-white">Manfaat</a></li>
                        <li><a href="#bantuan" class="text-base text-gray-400 hover:text-white">Bantuan</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400">SMK Nurussalam</h3>
                    <ul class="mt-4 space-y-4">
                        <li>
                            <a href="#" class="text-base text-gray-400 hover:text-white">
                                Jl. Raya Salopa Desa Kawitan, Kecamatan Salopa, Kabupaten Tasikmalaya 46192
                            </a>
                        </li>
                        <li>
                            <a href="mailto:info@smknurussalam.sch.id"
                                class="text-base text-gray-400 hover:text-white">
                                info@smknurussalam.sch.id
                            </a>
                        </li>
                        <li>
                            <a href="tel:+6282123456789" class="text-base text-gray-400 hover:text-white">
                                +62 821-2345-6789
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 border-t border-gray-800 pt-8">
                <p class="text-center text-base text-gray-400">
                    &copy; {{ date('Y') }} SMK Nurussalam Salopa. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Back to top button -->
    <div x-data="{ showButton: false }" @scroll.window="showButton = window.pageYOffset > 300"
        class="fixed bottom-6 right-6 z-50">
        <button x-show="showButton" @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition-all hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            aria-label="Back to top">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
        </button>
    </div>

</body>

</html>
