<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

new class extends Component {
    public $scan_type = 'datang';
    public $scan_message = '';
    public $scan_status = 'idle';

    public $scanned_user = null;
    public $show_confirmation = false;
    public $listeners = [
        'process-scan' => 'processScan',
        'confirm-attendance' => 'confirmAttendance',
        'cancel-attendance' => 'cancelAttendance',
    ];

    public function changeScanType($type)
    {
        $this->scan_type = $type;
        $this->reset('scan_message', 'scan_status', 'scanned_user', 'show_confirmation');
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
        ]);
    }
};

?>

<div x-data="qrScanner()" x-init="initializeLibrary()" class="flex min-h-[80vh] flex-col items-center justify-center">
    <!-- Tab datang/pulang -->
    <div class="flex w-full justify-center">
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

    <div class="w-full max-w-md overflow-hidden px-4 sm:px-0">
        <div class="p-4 sm:p-8">
            <h2 class="mb-4 text-center font-inter text-2xl font-medium">
                Presensi {{ $scan_type == 'datang' ? 'Datang' : 'Pulang' }}
            </h2>

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

            <!-- Area Scanner -->
            <div x-show="!isScanning" class="w-full">
                <button @click="startScanning()"
                    class="w-full rounded-[10px] bg-blue-600 py-3 text-sm font-medium text-white transition hover:bg-blue-700">
                    Buka Scanner QR
                </button>
            </div>

            <!-- Kontainer Scanner -->
            <div x-cloak x-show="isScanning" x-transition
                class="relative flex min-h-max w-full max-w-full flex-col gap-5 overflow-hidden">

                <!-- Memperbaiki QR Scanner dengan ukuran responsif -->
                <div id="reader-container" class="relative mx-auto mb-4 w-full"
                    style="aspect-ratio: 1/1; max-width: 550px; width: 100%; 
                            @media (min-width: 768px) { max-width: 700px; }">
                    <div id="reader"
                        class="absolute inset-0 overflow-hidden rounded-lg border-2 border-gray-300 bg-gray-100 object-contain">
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
                                }
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
                                const size = Math.min(viewfinderWidth, viewfinderHeight) * 0.75;
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
                                    width: {
                                        ideal: Math.max(containerWidth, containerHeight)
                                    },
                                    height: {
                                        ideal: Math.max(containerWidth, containerHeight)
                                    },
                                    deviceId: cameraId ? {
                                        exact: cameraId
                                    } : undefined,
                                    facingMode: {
                                        ideal: "environment"
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

                            // Fix styling issues for video element
                            setTimeout(() => {
                                const videoElement = readerElement.querySelector('video');
                                if (videoElement) {
                                    videoElement.style.objectFit = 'cover';
                                    videoElement.style.borderRadius = '0.375rem';
                                    videoElement.style.width = '100%';
                                    videoElement.style.height = '100%';
                                }
                            }, 500);

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
