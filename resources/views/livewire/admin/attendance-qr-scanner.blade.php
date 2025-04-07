<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Livewire\Attributes\On;

new class extends Component {
    public $scan_type = 'datang';
    public $scan_message = '';
    public $scan_status = 'idle';

    public $scanned_user = null;
    public $show_confirmation = false;

    // Tambahan untuk menampilkan daftar user terbaru dan jumlah kehadiran
    public $recentAttendances = [];
    public $totalAttendanceToday = 0;
    public $welcomeMessage = '';

    public $refreshInterval;

    public $listeners = [
        'process-scan' => 'processScan',
        'confirm-attendance' => 'confirmAttendance',
        'cancel-attendance' => 'cancelAttendance',
    ];

    public function mount()
    {
        $this->loadRecentAttendances();
        $this->loadTotalAttendance();
        $this->setWelcomeMessage();

        // Refresh data setiap 15 detik
        $this->refreshInterval = 15000;
    }

    public function loadRecentAttendances()
    {
        $today = Carbon::now()->timezone('Asia/Jakarta')->toDateString();

        // Mengambil 5 presensi terbaru untuk tipe scan yang sedang aktif
        $this->recentAttendances = Attendance::with('user')
            ->where('attendance_date', $today)
            ->where('type', $this->scan_type)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($attendance) {
                return [
                    'name' => $attendance->user->name,
                    'time' => Carbon::parse($attendance->check_in_time)->format('H:i'),
                    'status' => $attendance->status,
                    'avatar' => !empty($attendance->user->profile_photo_path) ? asset('storage/' . $attendance->user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($attendance->user->name) . '&color=7F9CF5&background=EBF4FF',
                ];
            });
    }

    public function loadTotalAttendance()
    {
        $today = Carbon::now()->timezone('Asia/Jakarta')->toDateString();

        // Menghitung total presensi hari ini
        $this->totalAttendanceToday = Attendance::where('attendance_date', $today)->where('type', $this->scan_type)->count();
    }

    public function setWelcomeMessage()
    {
        $now = Carbon::now()->timezone('Asia/Jakarta');
        $hour = $now->hour;

        // Tentukan salam berdasarkan waktu
        $greeting = '';
        if ($hour >= 0 && $hour < 4) {
            $greeting = 'Selamat dini hari';
        } elseif ($hour >= 4 && $hour < 11) {
            $greeting = 'Selamat Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $greeting = 'Selamat Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $greeting = 'Selamat Sore';
        } else {
            $greeting = 'Selamat Malam';
        }

        // Gunakan data yang sudah di-load sebelumnya
        if (!empty($this->recentAttendances) && $this->scan_type === 'datang') {
            // Ambil nama dari presensi terbaru yang sudah di-load
            $latestUserName = $this->recentAttendances[0]['name'] ?? null;
            $this->welcomeMessage = $latestUserName ? "{$greeting}, {$latestUserName}" : $greeting;
        } elseif (!empty($this->recentAttendances) && $this->scan_type === 'pulang') {
            $latestUserName = $this->recentAttendances[0]['name'] ?? null;
            $this->welcomeMessage = $latestUserName ? "Selamat Pulang, {$latestUserName}" : 'Selamat Pulang';
        } else {
            // Fallback jika tidak ada data
            $this->welcomeMessage = $this->scan_type === 'datang' ? $greeting : 'Selamat Pulang';
        }
    }

    public function refreshData()
    {
        $this->loadRecentAttendances();
        $this->loadTotalAttendance();
    }

    public function changeScanType($type)
    {
        $this->scan_type = $type;
        $this->reset('scan_message', 'scan_status', 'scanned_user', 'show_confirmation');
        $this->setWelcomeMessage();
        $this->loadRecentAttendances();
        $this->loadTotalAttendance();

        // Kirim event untuk restart scanner
        $this->dispatch('restart-scanner');
    }

    public function resetMessage()
    {
        $this->scan_message = '';
        $this->scan_status = 'idle';
        $this->show_confirmation = false;
    }

    public function processScan($token)
    {
        // Validasi token
        $user = User::where('qr_token', $token)->first();

        if (!$user) {
            // Gunakan sistem toast untuk error
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'QR Code tidak valid',
            ]);
            // Kirim event untuk restart scanner
            $this->dispatch('restart-scanner');
            return;
        }

        // Validasi presensi sebelumnya
        try {
            $now = Carbon::now()->timezone('Asia/Jakarta');
            $today = $now->toDateString();

            $existingAttendance = Attendance::where('user_id', $user->id)->where('attendance_date', $today)->where('type', $this->scan_type)->first();

            if ($existingAttendance) {
                $message = $this->scan_type === 'datang' ? 'Anda sudah melakukan presensi datang hari ini.' : 'Anda sudah melakukan presensi pulang hari ini.';

                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => $message,
                ]);
                // Kirim event untuk restart scanner
                $this->dispatch('restart-scanner');
                return;
            }

            // Untuk presensi pulang, pastikan sudah presensi datang
            if ($this->scan_type === 'pulang') {
                $checkIn = Attendance::where('user_id', $user->id)->where('attendance_date', $today)->where('type', 'datang')->first();

                if (!$checkIn) {
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'Anda belum melakukan presensi datang hari ini.',
                    ]);
                    // Kirim event untuk restart scanner
                    $this->dispatch('restart-scanner');
                    return;
                }
            }

            // Tentukan status presensi
            $status = Attendance::determineAttendanceStatus($this->scan_type, $now);

            // Tampilkan konfirmasi
            $this->scanned_user = $user;
            $this->show_confirmation = true;

            // Persiapkan pesan konfirmasi dengan detail status
            $statusMessage = match ($status) {
                'terlambat' => 'Anda terlambat',
                'pulang_cepat' => 'Anda pulang lebih cepat',
                default => 'Presensi dalam waktu normal',
            };

            $this->scan_message = $this->scan_type === 'datang' ? "Konfirmasi presensi datang untuk {$user->name}. Status: {$statusMessage}" : "Konfirmasi presensi pulang untuk {$user->name}. Status: {$statusMessage}";

            $this->scan_status = 'confirmation';

            // Tidak perlu restart scanner di sini karena sedang menunggu konfirmasi
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ]);
            // Kirim event untuk restart scanner
            $this->dispatch('restart-scanner');
        }
    }

    public function confirmAttendance()
    {
        if (!$this->scanned_user) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Tidak ada data yang dikonfirmasi',
            ]);
            $this->show_confirmation = false;

            // Kirim event untuk restart scanner
            $this->dispatch('restart-scanner');
            return;
        }

        try {
            // Rekam kehadiran
            $attendance = Attendance::recordAttendance($this->scanned_user->id, $this->scan_type);

            // Customize pesan berdasarkan tipe scan
            $now = Carbon::now()->timezone('Asia/Jakarta');
            $message = $this->scan_type === 'datang' ? "Selamat datang, {$this->scanned_user->name}. Anda tercatat hadir pada {$now->format('H:i')}" : "Selamat pulang, {$this->scanned_user->name}. Anda tercatat pulang pada {$now->format('H:i')}";

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $message,
            ]);

            $this->show_confirmation = false;
            $this->scanned_user = null;

            // Refresh data presensi terbaru setelah berhasil
            $this->loadRecentAttendances();
            $this->loadTotalAttendance();
            $this->setWelcomeMessage(); // Update welcome message setelah scan berhasil

            // Kirim event untuk restart scanner
            $this->dispatch('restart-scanner');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat mencatat presensi: ' . $e->getMessage(),
            ]);
            $this->show_confirmation = false;
            // Kirim event untuk restart scanner
            $this->dispatch('restart-scanner');
        }
    }

    public function cancelAttendance()
    {
        $this->show_confirmation = false;
        $this->scanned_user = null;
        $this->dispatch('show-toast', [
            'type' => 'error',
            'message' => 'Presensi dibatalkan',
        ]);
        // Kirim event untuk restart scanner
        $this->dispatch('restart-scanner');
    }

    #[On('restart-scanner')]
    public function render(): mixed
    {
        return view('livewire.admin.attendance-qr-scanner', [
            'show_confirmation' => $this->show_confirmation,
            'scanned_user' => $this->scanned_user,
            'recentAttendances' => $this->recentAttendances,
            'totalAttendanceToday' => $this->totalAttendanceToday,
            'welcomeMessage' => $this->welcomeMessage,
        ]);
    }
};

?>

<div x-data="qrScanner()" x-init="initializeLibrary()" class="flex min-h-[80vh] w-full flex-col items-center justify-center">
    <!-- Tab datang/pulang -->
    <div class="mt-5 flex w-full justify-center">
        <nav
            class="flex w-full max-w-xs items-center space-x-1 overflow-x-auto rounded-xl bg-gray-500/5 p-1 text-sm text-gray-600 dark:bg-gray-500/20 rtl:space-x-reverse">
            <button role="tab" type="button" wire:click="changeScanType('datang')"
                class="{{ $scan_type == 'datang' ? 'bg-white text-blue-600 shadow outline-none focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-blue-600 dark:text-white' : 'bg-gray-200 text-gray-600 hover:bg-blue-100 hover:text-blue-600' }} flex h-10 flex-1 items-center justify-center whitespace-nowrap rounded-lg px-3 font-medium transition-all duration-300 ease-in-out"
                aria-selected="{{ $scan_type == 'datang' }}">
                Datang
            </button>

            <button role="tab" type="button" wire:click="changeScanType('pulang')"
                class="{{ $scan_type == 'pulang' ? 'bg-white text-blue-600 shadow outline-none focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-blue-600 dark:text-white' : 'bg-gray-200 text-gray-600 hover:bg-blue-100 hover:text-blue-600' }} flex h-10 flex-1 items-center justify-center whitespace-nowrap rounded-lg px-3 font-medium transition-all duration-300 ease-in-out"
                aria-selected="{{ $scan_type == 'pulang' }}">
                Pulang
            </button>
        </nav>
    </div>

    <div class="w-full max-w-7xl overflow-hidden px-4 sm:px-6 lg:px-8">
        <div class="py-6">
            <!-- Ucapan Selamat Datang dan Statistik -->
            <div
                class="mb-6 rounded-lg bg-gradient-to-bl from-cyan-300 via-sky-300 to-blue-600 p-8 text-white shadow-md">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h2 class="font-inter text-3xl font-semibold">{{ $welcomeMessage }}</h2>
                        <p class="mt-2 font-inter text-blue-100">
                            {{ $scan_type == 'datang' ? 'Silahkan scan QR code untuk melakukan presensi kehadiran' : 'Silahkan scan QR code untuk melakukan presensi kepulangan' }}
                        </p>

                        <div class="mt-4">
                            <div class="flex items-center">

                                {{-- <div class="ml-4 rounded-md bg-white p-4 shadow-md">
                                    <p class="font-inter text-lg font-medium text-slate-900">Total
                                        {{ $scan_type == 'datang' ? 'Kehadiran' : 'Kepulangan' }}</p>
                                    <p class="font-inter text-3xl font-semibold text-slate-900">
                                        {{ $totalAttendanceToday }}</p>
                                </div> --}}
                                <div class="">

                                    <div
                                        class="flex flex-row items-center gap-2 rounded-2xl bg-white/20 p-6 shadow-sm backdrop-blur-lg">
                                        <div>
                                            <img src="{{ asset('images/logo-sekolah.png') }}" alt=""
                                                class="h-10 w-10">
                                        </div>
                                        <p class="font-inter text-xl font-semibold">Sistem Presensi Digital SMK
                                            Nurussalam
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden md:block">
                        <div class="flex h-full w-full flex-row items-center justify-end">
                            <div class="flex items-center gap-4">
                                <div class="rounded-2xl bg-white/20 p-6 shadow-sm backdrop-blur-lg">
                                    <p class="font-inter text-lg font-medium text-white">Total
                                        {{ $scan_type == 'datang' ? 'Kehadiran' : 'Kepulangan' }}</p>
                                    <p class="font-inter text-3xl font-bold text-white">{{ $totalAttendanceToday }}</p>
                                </div>

                                <div x-data="{ time: new Date().toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }) }" x-init="setInterval(() => time = new Date().toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)"
                                    class="rounded-2xl bg-white/20 p-6 shadow-sm backdrop-blur-lg">
                                    <p class="font-inter text-lg font-medium text-white">Waktu Sekarang</p>
                                    <p class="font-inter text-3xl font-bold text-white" x-text="time"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-5 md:gap-8">
                <!-- Scanner dan Konfirmasi -->
                <div class="md:col-span-2">
                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h2 class="mb-4 text-center font-inter text-2xl font-medium text-gray-800">
                            Presensi {{ $scan_type == 'datang' ? 'Datang' : 'Pulang' }}
                        </h2>

                        <!-- Area Scanner -->
                        <div x-show="!isScanning" class="w-full">
                            <button @click="startScanning()"
                                class="w-full rounded-[10px] bg-gradient-to-bl from-cyan-300 to-blue-600 py-3 text-sm font-medium text-white transition hover:bg-blue-700">
                                Buka Scanner QR
                            </button>
                        </div>

                        <!-- Kontainer Scanner -->
                        <div x-cloak x-show="isScanning" x-transition
                            class="relative flex min-h-max w-full max-w-full flex-col gap-5 overflow-hidden">

                            <!-- Memperbaiki QR Scanner dengan ukuran responsif -->
                            <div id="reader-container" class="relative mx-auto mb-4 w-full max-w-[550px]">
                                <div class="relative" style="aspect-ratio: 1/1;">
                                    <div id="reader"
                                        class="absolute inset-0 overflow-hidden rounded-lg border-2 border-gray-300 bg-gray-100">
                                    </div>
                                </div>
                            </div>

                            <!-- Camera selector - Perbaiki tampilan dropdown -->
                            <div x-show="cameras.length > 1" class="w-full max-w-[350px] md:max-w-[500px]">
                                <select id="camera-select" x-model="selectedCamera" @change="handleCameraChange()"
                                    class="w-full rounded-lg border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <template x-for="camera in cameras" :key="camera.id">
                                        <option :value="camera.id"
                                            x-text="camera.label || `Camera ${(cameras.indexOf(camera) + 1)}`"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div x-cloak x-show="isScanning" class="mt-3 flex space-x-4">
                            <button @click="stopScanning()"
                                class="flex-1 rounded-[10px] bg-gray-600 py-3 text-sm font-medium text-white transition hover:bg-gray-700">
                                Tutup Scanner QR
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Presensi Terbaru -->
                <div class="md:col-span-3">
                    <div class="max-h-[38rem] overflow-y-scroll rounded-lg bg-white p-6 shadow-md">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800">Presensi Terbaru</h2>
                            <button wire:click="refreshData" class="text-blue-600 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>

                        @if (count($recentAttendances) > 0)
                            <div class="space-y-4">
                                @foreach ($recentAttendances as $attendance)
                                    <div
                                        class="flex items-center rounded-lg border border-gray-100 bg-gray-50 p-3 shadow-sm transition hover:bg-gray-100">
                                        <div class="flex-shrink-0">
                                            <img src="{{ $attendance['avatar'] }}" alt="{{ $attendance['name'] }}"
                                                class="h-10 w-10 rounded-full object-cover">
                                        </div>
                                        <div class="ml-3 min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-gray-900">
                                                {{ $attendance['name'] }}</p>
                                            <p class="truncate text-xs text-gray-500">{{ $attendance['time'] }}</p>
                                        </div>
                                        <div class="ml-auto">
                                            @if ($attendance['status'] === 'hadir')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700">
                                                    Tepat Waktu
                                                </span>
                                            @elseif($attendance['status'] === 'terlambat')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700">
                                                    Terlambat
                                                </span>
                                            @elseif($attendance['status'] === 'pulang_cepat')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700">
                                                    Pulang Cepat
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                                    {{ ucfirst($attendance['status']) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-500">Menampilkan {{ count($recentAttendances) }} data
                                    terbaru</p>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="mt-2 text-sm font-medium text-gray-900">Belum ada presensi</p>
                                <p class="mt-1 text-xs text-gray-500">Data presensi terbaru akan muncul di sini</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    @if ($show_confirmation && $scanned_user)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4">
            <div class="w-full max-w-md rounded-lg bg-white p-6 text-center shadow-xl">
                <h3 class="mb-4 text-xl font-bold">Konfirmasi Presensi</h3>

                <div class="mb-4">
                    <p class="text-lg">
                        {{ $scan_type === 'datang' ? 'Presensi Datang' : 'Presensi Pulang' }}
                    </p>
                    <p class="font-semibold text-gray-700">{{ $scanned_user->name }}</p>
                    <p class="text-sm text-gray-600">{{ $scan_message }}</p>
                </div>

                <div class="flex justify-center space-x-4">
                    <button wire:click="confirmAttendance"
                        class="rounded bg-green-500 px-4 py-2 text-white transition hover:bg-green-600">
                        Konfirmasi
                    </button>
                    <button wire:click="cancelAttendance"
                        class="rounded bg-red-500 px-4 py-2 text-white transition hover:bg-red-600">
                        Batalkan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Toast Notification Component -->
    <div x-data="{
        toastMessage: '',
        toastType: '',
        showToast: false
    }"
        x-on:show-toast.window="
            const data = $event.detail[0] || $event.detail;
            toastMessage = data.message;
            toastType = data.type;
            showToast = true;
            setTimeout(() => showToast = false, 3000)
        ">
        <div x-cloak x-show="showToast" x-transition.opacity
            :class="toastType === 'success' ? 'bg-white text-gray-500' : 'bg-red-100 text-red-700'"
            class="z-60 fixed bottom-5 right-5 mb-4 flex w-full max-w-xs items-center rounded-lg p-4 shadow"
            role="alert">
            <template x-if="toastType === 'success'">
                <div
                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-500">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                    </svg>
                </div>
            </template>
            <template x-if="toastType === 'error'">
                <div
                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-500">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                    </svg>
                </div>
            </template>
            <div class="ml-3 text-sm font-normal" x-text="toastMessage"></div>
            <button type="button" @click="showToast = false"
                class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300">
                <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Auto Refresh Component (Polling) -->
    <div wire:poll.{{ $refreshInterval }}ms="loadRecentAttendances; loadTotalAttendance"></div>

    <script>
        function qrScanner() {
            return {
                isScanning: false,
                scanner: null,
                debugMessage: '',
                cameras: [],
                selectedCamera: null,
                lastSelectedCamera: null,

                initializeLibrary() {
                    if (typeof Html5Qrcode === 'undefined') {
                        const script = document.createElement('script');
                        script.src = 'https://unpkg.com/html5-qrcode';
                        script.async = true;
                        script.onload = () => {
                            this.debugMessage = 'Library QR berhasil dimuat';
                            console.log('Library QR berhasil dimuat');
                            this.loadAvailableCameras();
                        };
                        script.onerror = () => {
                            this.debugMessage = 'Gagal memuat library QR';
                            console.error('Gagal memuat library QR');
                        };
                        document.head.appendChild(script);
                    } else {
                        this.loadAvailableCameras();
                    }

                    // Listener untuk restart scanner
                    window.addEventListener('restart-scanner', () => {
                        console.log('Restarting scanner from event...');
                        // Berikan waktu untuk UI diperbarui
                        setTimeout(() => {
                            if (this.scanner) {
                                // Hanya start ulang jika scanner sudah berhenti
                                this.stopScanning().then(() => {
                                    this.startScanning();
                                });
                            } else {
                                this.startScanning();
                            }
                        }, 500);
                    });
                },

                loadAvailableCameras() {
                    if (typeof Html5Qrcode === 'undefined') return;

                    Html5Qrcode.getCameras()
                        .then(devices => {
                            if (devices && devices.length) {
                                // Log kamera yang tersedia untuk debugging
                                console.log('Available cameras:', devices);
                                this.cameras = devices;

                                // Coba temukan kamera belakang
                                const backCamera = devices.find(camera =>
                                    camera.label && (
                                        camera.label.toLowerCase().includes('back') ||
                                        camera.label.toLowerCase().includes('belakang') ||
                                        camera.label.toLowerCase().includes('environment')
                                    )
                                );

                                // Jika kamera belakang tidak ditemukan, gunakan kamera kedua jika ada
                                this.selectedCamera = backCamera ? backCamera.id :
                                    (devices.length > 1 ? devices[1].id : devices[0].id);

                                // Simpan pilihan awal
                                this.lastSelectedCamera = this.selectedCamera;
                                console.log('Selected camera:', this.selectedCamera);
                            }
                        })
                        .catch(err => {
                            console.error('Error getting cameras:', err);
                        });
                },

                handleCameraChange() {
                    console.log('Camera changed to:', this.selectedCamera);

                    // Cek apakah kamera benar-benar berubah
                    if (this.lastSelectedCamera !== this.selectedCamera) {
                        this.lastSelectedCamera = this.selectedCamera;

                        // Restart scanner dengan kamera baru
                        if (this.isScanning) {
                            console.log('Restarting scanner with new camera');
                            this.stopScanning().then(() => {
                                this.startScanning();
                            });
                        }
                    }
                },

                // Mengubah stopScanning untuk mengembalikan Promise
                stopScanning() {
                    return new Promise((resolve) => {
                        if (this.scanner) {
                            this.scanner.stop().then(() => {
                                this.scanner = null;
                                this.isScanning = false;
                                this.debugMessage = 'Scanner dihentikan';
                                resolve();
                            }).catch(err => {
                                console.error('Error stopping scanner:', err);
                                this.scanner = null;
                                this.isScanning = false;
                                resolve();
                            });
                        } else {
                            this.isScanning = false;
                            this.debugMessage = 'Scanner dihentikan';
                            resolve();
                        }
                    });
                },

                startScanning() {
                    if (typeof Html5Qrcode === 'undefined') {
                        this.debugMessage = 'Tunggu library dimuat...';
                        return;
                    }

                    this.debugMessage = 'Memulai scanning...';
                    this.isScanning = true;

                    this.$nextTick(() => {
                        const readerElement = document.getElementById('reader');
                        if (!readerElement) {
                            this.debugMessage = 'Elemen reader tidak ditemukan';
                            return;
                        }

                        // Bersihkan elemen reader
                        readerElement.innerHTML = '';

                        // Reset style pada elemen
                        readerElement.removeAttribute('style');

                        try {
                            // Config untuk scanner
                            const config = {
                                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                                experimentalFeatures: {
                                    useBarCodeDetectorIfSupported: true
                                },

                            };

                            this.scanner = new Html5Qrcode("reader", config);

                            // Gunakan kamera yang dipilih
                            const cameraId = this.selectedCamera || (this.cameras.length > 0 ? this.cameras[0].id :
                                null);

                            if (!cameraId) {
                                this.debugMessage = 'Tidak ada kamera yang tersedia';
                                this.isScanning = false;
                                return;
                            }

                            // Log untuk memastikan kamera yang dipilih
                            console.log('Starting scanner with camera:', cameraId);

                            // Get container dimensions
                            const readerContainer = document.getElementById('reader-container');
                            const containerWidth = readerContainer.clientWidth;
                            const containerHeight = readerContainer.clientHeight;

                            // Fungsi QR Box
                            const qrboxFunction = (viewfinderWidth, viewfinderHeight) => {
                                // Ukuran lebih kecil dari container
                                const size = Math.min(viewfinderWidth, viewfinderHeight) * 0.80;
                                console.log(viewfinderWidth, viewfinderHeight, size);
                                return {
                                    width: size,
                                    height: size
                                };
                            };

                            // Mulai scanner dengan kamera yang dipilih dan config custom
                            const scannerConfig = {
                                fps: 15, // Frame per second yang lebih tinggi
                                qrbox: qrboxFunction, // Fungsi dinamis untuk ukuran box
                                aspectRatio: 1.0, // Rasio 1:1 agar persegi sempurna
                                disableFlip: false, // Izinkan flip jika diperlukan
                                videoConstraints: { // Konfigurasi video khusus
                                    // Tingkatkan resolusi kamera
                                    width: {
                                        min: 640,
                                        ideal: 1280,
                                        max: 1920
                                    },
                                    height: {
                                        min: 480,
                                        ideal: 720,
                                        max: 1080
                                    },
                                    deviceId: cameraId ? {
                                        exact: cameraId
                                    } : undefined,
                                    facingMode: {
                                        ideal: "environment"
                                    },

                                    frameRate: {
                                        ideal: 30
                                    },
                                    focusMode: {
                                        ideal: "continuous"
                                    }
                                }
                            };

                            this.scanner.start(
                                cameraId,
                                scannerConfig,
                                (decodedText) => {
                                    console.log('QR Decoded:', decodedText);

                                    // Cek bahwa QR code valid sebelum mengirim ke Livewire
                                    if (decodedText) {
                                        // Pause scanner sementara (tidak berhenti total)
                                        this.scanner.pause();

                                        // Kirim data ke Livewire
                                        Livewire.dispatch('process-scan', {
                                            token: decodedText
                                        });
                                    }
                                },
                                (errorMessage) => {
                                    // Hanya log error serius
                                    if (errorMessage.includes("Camera access denied")) {
                                        console.error(`QR Error: ${errorMessage}`);
                                        this.debugMessage = "Akses kamera ditolak. Berikan izin dan coba lagi.";
                                        this.stopScanning();
                                    }
                                }
                            ).catch(err => {
                                console.error("Error starting scanner:", err);
                                this.debugMessage =
                                    `Error: ${err.message || "Tidak dapat mengakses kamera"}`;
                                this.isScanning = false;
                            });
                        } catch (e) {
                            console.error('Exception in startScanning:', e);
                            this.debugMessage = `Eksepsi: ${e.message}`;
                            this.isScanning = false;
                        }
                    });
                }
            };
        }
    </script>
</div>
