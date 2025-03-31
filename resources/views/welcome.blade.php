<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">


    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="font-inter antialiased">
    <!-- Hero Section -->
    <div
        class="flex min-h-screen w-full flex-1 flex-row items-center bg-gradient-to-r from-blue-100/50 via-cyan-50 to-blue-100/50 px-5 py-20 text-center dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 md:px-4 md:text-start">
        <div class="flex max-w-7xl items-center justify-center">
            <div class="w-1/2">
                <div class="flex-col">
                    <a href="#fitur"
                        class="border-white-700 text-white-400 mb-5 rounded-lg border px-4 py-2 text-xs transition duration-300 ease-in-out hover:text-gray-500 dark:border-gray-300 dark:text-gray-300 dark:hover:text-gray-400 md:text-sm">
                        Menghadirkan Sistem Presensi Modern
                    </a>
                    <h1
                        class="font-display text-white-300 mt-5 max-w-4xl text-5xl font-bold tracking-normal dark:text-gray-300 lg:max-w-5xl">
                        Manajemen Kehadiran
                        <span class="text-white-600 relative dark:text-gray-300">Siswa & Guru dengan</span>
                        <span class="relative whitespace-nowrap text-blue-500 dark:text-blue-300">
                            <svg aria-hidden="true" viewBox="0 0 418 42"
                                class="absolute left-0 top-2/3 h-[0.58em] w-full fill-blue-500 dark:fill-blue-300/60"
                                preserveAspectRatio="none">
                                <path
                                    d="M203.371.916c-26.013-2.078-76.686 1.963-124.73 9.946L67.3 12.749C35.421 18.062 18.2 21.766 6.004 25.934 1.244 27.561.828 27.778.874 28.61c.07 1.214.828 1.121 9.595-1.176 9.072-2.377 17.15-3.92 39.246-7.496C123.565 7.986 157.869 4.492 195.942 5.046c7.461.108 19.25 1.696 19.17 2.582-.107 1.183-7.874 4.31-25.75 10.366-21.992 7.45-35.43 12.534-36.701 13.884-2.173 2.308-.202 4.407 4.442 4.734 2.654.187 3.263.157 15.593-.780 35.401-2.686 57.944-3.488 88.365-3.143 46.327.526 75.721 2.23 130.788 7.584 19.787 1.924 20.814 1.98 24.557 1.332l.066-.011c1.201-.203 1.53-1.825.399-2.335-2.911-1.31-4.893-1.604-22.048-3.261-57.509-5.556-87.871-7.36-132.059-7.842-23.239-.254-33.617-.116-50.627.674-11.629.540-42.371 2.494-46.696 2.967-2.359.259 8.133-3.625 26.504-9.810 23.239-7.825 27.934-10.149 28.304-14.005 .417-4.348-3.529-6-16.878-7.066Z">
                                </path>
                            </svg>
                            <span class="relative">QR Code</span>
                        </span>
                    </h1>
                    <h2
                        class="sm:text-white-400 text-white-500 text-md mt-12 max-w-xl font-medium leading-6 dark:text-gray-300 md:text-lg md:leading-7">
                        Rasakan kemudahan dan efisiensi sistem presensi QR code terbaru di SMK Nurussalam. Pantau
                        kehadiran,
                        kelola
                        izin,
                        dan dapatkan laporan kehadiran secara real-time dalam satu platform terintegrasi.
                    </h2>
                    <a class="text-md mt-8 inline-flex select-none items-center gap-2 whitespace-nowrap rounded-full bg-[#374bff] px-8 py-4 text-center font-inter font-semibold capitalize leading-[1.12] text-white shadow-[4px_6px_0px_0px_#050071] transition duration-300 ease-linear hover:bg-[#42e1f7] hover:shadow-none md:text-lg"
                        href="{{ route('login') }}">Masuk Sekarang</a>
                </div>
            </div>
            <div class="">
                <div class="absolute -right-5 top-40">
                    <img src="{{ asset('images/app-hero-image.png') }}" alt="" class="h-[700px]">
                </div>
            </div>
        </div>
    </div>

    <!-- Fitur Section -->
    <section id="fitur" class="flex min-h-[80vh] items-center justify-center bg-gray-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-800">Fitur Unggulan</h2>
                <p class="mx-auto mt-4 max-w-3xl text-xl text-gray-600">
                    Nikmati kemudahan manajemen presensi dengan teknologi QR code yang inovatif
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <!-- Fitur 1 -->
                <div
                    class="rounded-lg bg-white p-6 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-800">Presensi QR Code</h3>
                    <p class="text-gray-600">
                        Sistem presensi modern dengan scan QR code yang cepat dan aman. Mengurangi kemungkinan titip
                        absen.
                    </p>
                </div>

                <!-- Fitur 2 -->
                <div
                    class="rounded-lg bg-white p-6 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-800">Manajemen Izin</h3>
                    <p class="text-gray-600">
                        Pengajuan dan persetujuan izin secara online dengan bukti digital yang bisa dilampirkan.
                    </p>
                </div>

                <!-- Fitur 3 -->
                <div
                    class="rounded-lg bg-white p-6 shadow-md transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-800">Laporan Real-time</h3>
                    <p class="text-gray-600">
                        Monitor kehadiran secara real-time dengan dashboard intuitif dan laporan terperinci.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!-- Dashboard Preview Section -->
    <section class="bg-white py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-bold text-gray-800">Dashboard Intuitif</h2>
                <p class="mx-auto mt-4 max-w-3xl text-xl text-gray-600">
                    Pantau kehadiran dengan tampilan dashboard yang informatif dan mudah digunakan
                </p>
            </div>

            <div class="rounded-xl bg-blue-600 p-6 shadow-xl md:p-10">
                <!-- Card baris atas -->
                <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Card Kehadiran -->
                    <div class="rounded-lg bg-white p-4 shadow-md">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-medium text-gray-800">Kehadiran Hari Ini</h3>
                            <span class="rounded-full bg-green-100 px-2 py-1 text-sm text-green-800">Live</span>
                        </div>
                        <div class="flex items-center">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-2xl font-bold text-blue-600">
                                85%
                            </div>
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">215 dari 250 siswa</div>
                                <div class="flex items-center text-sm text-green-600">
                                    <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd"
                                            d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    5% dari minggu lalu
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Keterlambatan -->
                    <div class="rounded-lg bg-white p-4 shadow-md">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-medium text-gray-800">Keterlambatan</h3>
                            <span class="rounded-full bg-yellow-100 px-2 py-1 text-sm text-yellow-800">25 Siswa</span>
                        </div>
                        <div class="flex flex-col">
                            <div class="mb-2 text-sm text-gray-500">Trend Harian</div>
                            <div class="flex h-10 items-end space-x-1">
                                <div class="h-4 w-1/5 rounded-t bg-yellow-200"></div>
                                <div class="h-7 w-1/5 rounded-t bg-yellow-200"></div>
                                <div class="h-5 w-1/5 rounded-t bg-yellow-200"></div>
                                <div class="h-6 w-1/5 rounded-t bg-yellow-200"></div>
                                <div class="h-3 w-1/5 rounded-t bg-yellow-200"></div>
                            </div>
                            <div class="mt-1 flex space-x-1 text-xs text-gray-400">
                                <div class="w-1/5 text-center">Sen</div>
                                <div class="w-1/5 text-center">Sel</div>
                                <div class="w-1/5 text-center">Rab</div>
                                <div class="w-1/5 text-center">Kam</div>
                                <div class="w-1/5 text-center">Jum</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Izin & Sakit -->
                    <div class="rounded-lg bg-white p-4 shadow-md">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-medium text-gray-800">Izin & Sakit</h3>
                            <span class="rounded-full bg-blue-100 px-2 py-1 text-sm text-blue-800">10 Pending</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Izin</span>
                                <div class="flex items-center">
                                    <div class="mr-2 h-2 w-24 rounded-full bg-gray-200">
                                        <div class="h-2 w-1/4 rounded-full bg-blue-500"></div>
                                    </div>
                                    <span>8</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Sakit</span>
                                <div class="flex items-center">
                                    <div class="mr-2 h-2 w-24 rounded-full bg-gray-200">
                                        <div class="h-2 w-1/2 rounded-full bg-purple-500"></div>
                                    </div>
                                    <span>12</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Alpha</span>
                                <div class="flex items-center">
                                    <div class="mr-2 h-2 w-24 rounded-full bg-gray-200">
                                        <div class="h-2 w-1/6 rounded-full bg-red-500"></div>
                                    </div>
                                    <span>5</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Rekap -->
                <div class="rounded-lg bg-white p-4 shadow-md">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-medium text-gray-800">Rekap Kehadiran Mingguan</h3>
                        <div class="flex space-x-2">
                            <button class="rounded-md bg-blue-100 px-3 py-1 text-sm text-blue-600">Minggu Ini</button>
                            <button class="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-600">Minggu Lalu</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Kelas</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Total Siswa</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Hadir</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Terlambat</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Izin</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Sakit</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Alpha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-3 text-sm text-gray-800">10 A</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-800">32</td>
                                    <td class="px-4 py-3 text-center text-sm font-medium text-green-600">28</td>
                                    <td class="px-4 py-3 text-center text-sm text-yellow-600">2</td>
                                    <td class="px-4 py-3 text-center text-sm text-blue-600">1</td>
                                    <td class="px-4 py-3 text-center text-sm text-purple-600">1</td>
                                    <td class="px-4 py-3 text-center text-sm text-red-600">0</td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-3 text-sm text-gray-800">10 B</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-800">30</td>
                                    <td class="px-4 py-3 text-center text-sm font-medium text-green-600">26</td>
                                    <td class="px-4 py-3 text-center text-sm text-yellow-600">1</td>
                                    <td class="px-4 py-3 text-center text-sm text-blue-600">2</td>
                                    <td class="px-4 py-3 text-center text-sm text-purple-600">0</td>
                                    <td class="px-4 py-3 text-center text-sm text-red-600">1</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Manfaat Section -->
    <section id="manfaat" class="bg-gray-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-800">Manfaat Utama</h2>
                <p class="mx-auto mt-4 max-w-3xl text-xl text-gray-600">
                    Rasakan perbedaan dalam pengelolaan kehadiran siswa dan guru di sekolah Anda
                </p>
            </div>

            <div class="grid grid-cols-1 gap-12 md:grid-cols-2">
                <!-- Kolom kiri -->
                <div class="space-y-10">
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
                                Sistem presensi QR code memangkas waktu absensi manual. Tidak perlu lagi panggilan nama
                                satu per satu atau tanda tangan di kertas.
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
                                Menghilangkan kesalahan manual dalam pencatatan kehadiran. Data absensi menjadi lebih
                                akurat dan dapat dipertanggungjawabkan.
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
                </div>

                <!-- Kolom kanan -->
                <div class="space-y-10">
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
                                Notifikasi otomatis kepada orang tua/wali siswa mengenai kehadiran atau ketidakhadiran
                                anak mereka di sekolah.
                            </p>
                        </div>
                    </div>

                    <div class="flex">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-md bg-blue-600 text-white">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Paperless Solution</h3>
                            <p class="mt-2 text-base text-gray-600">
                                Mengurangi penggunaan kertas dan biaya administrasi, sekaligus berkontribusi pada
                                lingkungan yang lebih hijau.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="bantuan" class="bg-gray-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-16 text-center">
                <h2 class="text-3xl font-bold text-gray-800">Pertanyaan Umum</h2>
                <p class="mx-auto mt-4 max-w-3xl text-xl text-gray-600">
                    Jawaban untuk pertanyaan yang sering ditanyakan tentang sistem presensi QR
                </p>
            </div>

            <div class="mx-auto max-w-3xl divide-y divide-gray-200">
                <div x-data="{ open: false }" class="py-6">
                    <button @click="open = !open"
                        class="flex w-full items-center justify-between text-left focus:outline-none">
                        <h3 class="text-lg font-medium text-gray-900">Bagaimana cara menggunakan presensi QR?</h3>
                        <span class="ml-6 flex h-7 items-center">
                            <svg class="h-6 w-6 transform transition-transform duration-300"
                                :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </span>
                    </button>
                    <div x-show="open" class="mt-3 pr-12">
                        <p class="text-base text-gray-600">
                            Siswa dan guru cukup membuka aplikasi, pilih menu scan QR, kemudian arahkan kamera ke kode
                            QR yang tersedia di lokasi yang ditentukan. Sistem akan secara otomatis mencatat kehadiran
                            dengan timestamp dan lokasi.
                        </p>
                    </div>
                </div>

                <div x-data="{ open: false }" class="py-6">
                    <button @click="open = !open"
                        class="flex w-full items-center justify-between text-left focus:outline-none">
                        <h3 class="text-lg font-medium text-gray-900">Bagaimana cara mengajukan izin atau sakit?</h3>
                        <span class="ml-6 flex h-7 items-center">
                            <svg class="h-6 w-6 transform transition-transform duration-300"
                                :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </span>
                    </button>
                    <div x-show="open" class="mt-3 pr-12">
                        <p class="text-base text-gray-600">
                            Siswa atau orang tua dapat mengajukan izin atau sakit melalui aplikasi. Cukup pilih menu
                            "Pengajuan Izin", isi formulir yang tersedia, lampirkan bukti jika diperlukan (seperti surat
                            dokter), dan tunggu persetujuan dari wali kelas atau administrator.
                        </p>
                    </div>
                </div>

                <div x-data="{ open: false }" class="py-6">
                    <button @click="open = !open"
                        class="flex w-full items-center justify-between text-left focus:outline-none">
                        <h3 class="text-lg font-medium text-gray-900">Apakah orang tua dapat memantau kehadiran anak?
                        </h3>
                        <span class="ml-6 flex h-7 items-center">
                            <svg class="h-6 w-6 transform transition-transform duration-300"
                                :class="{ 'rotate-180': open, 'rotate-0': !open }" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </span>
                    </button>
                    <div x-show="open" class="mt-3 pr-12">
                        <p class="text-base text-gray-600">
                            Ya, orang tua dapat memantau kehadiran anak mereka melalui aplikasi dengan login menggunakan
                            akun yang terhubung dengan data siswa. Mereka akan menerima notifikasi kehadiran dan dapat
                            melihat rekap kehadiran bulanan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</body>

<!-- Footer -->
<footer class="border-t border-gray-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center">
                    <img src="{{ asset('img/logo-smk.png') }}" alt="Logo SMK Nurussalam" class="h-8 w-auto">
                    <span class="ml-2 text-xl font-bold text-blue-600">PRESQR</span>
                </div>
                <p class="mt-4 max-w-md text-base text-gray-600">
                    Sistem presensi berbasis QR code untuk SMK Nurussalam Salopa. Solusi modern untuk manajemen
                    kehadiran yang efisien dan akurat.
                </p>
                <div class="mt-6 flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Instagram</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
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
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Tautan</h3>
                <ul class="mt-4 space-y-4">
                    <li><a href="#fitur" class="text-base text-gray-600 hover:text-gray-900">Fitur</a></li>
                    <li><a href="#manfaat" class="text-base text-gray-600 hover:text-gray-900">Manfaat</a></li>
                    <li><a href="#testimonial" class="text-base text-gray-600 hover:text-gray-900">Testimonial</a>
                    </li>
                    <li><a href="#bantuan" class="text-base text-gray-600 hover:text-gray-900">Bantuan</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">SMK Nurussalam</h3>
                <ul class="mt-4 space-y-4">
                    <li>
                        <a href="#" class="text-base text-gray-600 hover:text-gray-900">
                            Jl. Raya Salopa Desa Kawitan, Kecamatan Salopa, Kabupaten Tasikmalaya 46192
                        </a>
                    </li>
                    <li>
                        <a href="mailto:info@smknurussalam.sch.id"
                            class="text-base text-gray-600 hover:text-gray-900">
                            info@smknurussalam.sch.id
                        </a>
                    </li>
                    <li>
                        <a href="tel:+6282123456789" class="text-base text-gray-600 hover:text-gray-900">
                            +62 821-2345-6789
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-12 border-t border-gray-200 pt-8">
            <p class="text-center text-base text-gray-500">
                &copy; {{ date('Y') }} SMK Nurussalam Salopa. All rights reserved.
            </p>
        </div>
    </div>
</footer>


</html>
