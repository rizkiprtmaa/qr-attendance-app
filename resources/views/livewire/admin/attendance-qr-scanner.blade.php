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
            $this->scan_message = 'QR Code tidak valid';
            $this->scan_status = 'error';
            return;
        }

        // Validasi presensi sebelumnya
        try {
            $now = Carbon::now()->timezone('Asia/Jakarta');
            $today = $now->toDateString();

            $existingAttendance = Attendance::where('user_id', $user->id)->where('attendance_date', $today)->where('type', $this->scan_type)->first();

            if ($existingAttendance) {
                $this->scan_message = $this->scan_type === 'datang' ? 'Anda sudah melakukan presensi datang hari ini.' : 'Anda sudah melakukan presensi pulang hari ini.';
                $this->scan_status = 'error';
                return;
            }

            // Untuk presensi pulang, pastikan sudah presensi datang
            if ($this->scan_type === 'pulang') {
                $checkIn = Attendance::where('user_id', $user->id)->where('attendance_date', $today)->where('type', 'datang')->first();

                if (!$checkIn) {
                    $this->scan_message = 'Anda belum melakukan presensi datang hari ini.';
                    $this->scan_status = 'error';
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
        } catch (\Exception $e) {
            $this->scan_message = 'Terjadi kesalahan: ' . $e->getMessage();
            $this->scan_status = 'error';
        }
    }

    public function confirmAttendance()
    {
        if (!$this->scanned_user) {
            $this->scan_message = 'Tidak ada data yang dikonfirmasi';
            $this->scan_status = 'error';
            return;
        }

        try {
            // Rekam kehadiran
            $attendance = Attendance::recordAttendance($this->scanned_user->id, $this->scan_type);

            // Customize pesan berdasarkan tipe scan
            $now = Carbon::now()->timezone('Asia/Jakarta');
            $this->scan_message = $this->scan_type === 'datang' ? "Selamat datang, {$this->scanned_user->name}. Anda tercatat hadir pada {$now->format('H:i')}" : "Selamat pulang, {$this->scanned_user->name}. Anda tercatat pulang pada {$now->format('H:i')}";

            $this->scan_status = 'success';
            $this->show_confirmation = false;
            $this->scanned_user = null;

            // Dispatch event untuk update tabel
            $this->dispatch('scan-attendance');
        } catch (\Exception $e) {
            $this->scan_message = 'Terjadi kesalahan saat mencatat presensi: ' . $e->getMessage();
            $this->scan_status = 'error';
            $this->show_confirmation = false;
        }
    }

    public function cancelAttendance()
    {
        $this->show_confirmation = false;
        $this->scanned_user = null;
        $this->scan_message = 'Presensi dibatalkan';
        $this->scan_status = 'idle';
    }

    #[On('scan-attendance')]
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


    <div class="flex justify-center">
        <nav
            class="flex items-center space-x-1 overflow-x-auto rounded-xl bg-gray-500/5 p-1 text-sm text-gray-600 dark:bg-gray-500/20 rtl:space-x-reverse">
            <button role="tab" type="button" wire:click="changeScanType('datang')"
                @click="stopScanning(); startScanning()"
                class="{{ $scan_type == 'datang' ? 'bg-white text-blue-600 shadow outline-none focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-blue-600 dark:text-white' : 'bg-gray-200 text-gray-600 hover:bg-blue-100 hover:text-blue-600' }} flex h-8 items-center whitespace-nowrap rounded-lg px-5 font-medium transition-all duration-300 ease-in-out"
                aria-selected="{{ $scan_type == 'datang' }}">
                Datang
            </button>

            <button role="tab" type="button" wire:click="changeScanType('pulang')"
                @click="stopScanning(); startScanning()"
                class="{{ $scan_type == 'pulang' ? 'bg-white text-blue-600 shadow outline-none focus:ring-2 focus:ring-inset focus:ring-blue-600 dark:bg-blue-600 dark:text-white' : 'bg-gray-200 text-gray-600 hover:bg-blue-100 hover:text-blue-600' }} flex h-8 items-center whitespace-nowrap rounded-lg px-5 font-medium transition-all duration-300 ease-in-out"
                aria-selected="{{ $scan_type == 'pulang' }}">
                Pulang
            </button>
        </nav>
    </div>

    <div class="min-w-[450px] overflow-hidden">
        <div class="p-8">
            @if ($scan_type == 'datang')
                <h2 class="mb-4 text-center font-inter text-2xl font-medium" x-show="{{ $scan_type == 'datang' }}">
                    Presensi Datang
                </h2>
            @else
                <h2 class="mb-4 text-center font-inter text-2xl font-medium" x-show="{{ $scan_type == 'pulang' }}">
                    Presensi Pulang
                </h2>
            @endif



            {{-- Modal Konfirmasi --}}
            @if ($show_confirmation && $scanned_user)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
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
                            <button wire:click="confirmAttendance" @click="stopScanning()"
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

            {{-- Pesan Hasil Scan --}}
            @if ($scan_message)
                <div wire:key="{{ now() }}" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => { show = false; }, 3000)"
                    class="fixed bottom-4 right-4 z-50 rounded p-4 text-center shadow-lg transition-transform"
                    :class="{
                        'bg-red-100 text-red-800': '{{ $scan_status }}'
                        === 'error',
                        'bg-green-100 text-green-800': '{{ $scan_status }}'
                        === 'success',
                        'bg-yellow-100 text-yellow-800': '{{ $scan_status }}'
                        === 'idle'
                    }">
                    {{ $scan_message }}
                </div>
            @endif

            {{-- Area Scanner --}}
            <div x-show="!isScanning">
                <button @click="startScanning()"
                    class="w-full rounded-[10px] bg-blue-600 py-2 text-sm text-white transition hover:bg-blue-700">
                    Buka Scanner QR
                </button>
            </div>

            {{-- Kontainer Scanner --}}
            <div x-cloak x-show="isScanning" x-transition
                class="relative flex min-h-max w-full max-w-[450px] flex-col gap-5 overflow-hidden">

                <div id="reader" class="mb-4 h-72 w-full bg-gray-100 object-contain"></div>
            </div>

            <div x-cloak x-show="isScanning" class="mt-3 flex space-x-4">
                <button @click="stopScanning()"
                    class="flex-1 rounded-[10px] bg-gray-600 py-2 text-sm text-white transition hover:bg-gray-700">
                    Tutup Scanner QR
                </button>
            </div>


        </div>

        <script>
            function qrScanner() {
                return {
                    isScanning: false,
                    scanner: null,
                    debugMessage: '',

                    initializeLibrary() {
                        if (typeof Html5Qrcode === 'undefined') {
                            const script = document.createElement('script');
                            script.src = 'https://unpkg.com/html5-qrcode';
                            script.async = true;
                            script.onload = () => {
                                this.debugMessage = 'Library QR berhasil dimuat';
                                console.log('Library QR berhasil dimuat');
                            };
                            script.onerror = () => {
                                this.debugMessage = 'Gagal memuat library QR';
                                console.error('Gagal memuat library QR');
                            };
                            document.head.appendChild(script);
                        }
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

                            try {
                                this.scanner = new Html5Qrcode("reader", {
                                    formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
                                });

                                Html5Qrcode.getCameras().then(devices => {
                                    if (devices && devices.length) {
                                        // Gunakan kamera yang tersimpan sebelumnya atau kamera pertama
                                        const cameraId = this.lastCameraId ||
                                            (devices.length > 1 ? devices[1].id : devices[0].id);

                                        this.lastCameraId = cameraId; // Simpan untuk penggunaan berikutnya
                                        this.debugMessage = `Menggunakan kamera: ${cameraId}`;

                                        // Fungsi untuk menghitung qrbox yang responsif
                                        const qrboxFunction = (viewfinderWidth, viewfinderHeight) => {
                                            // Tentukan ukuran maksimum kotak QR
                                            const minDimension = Math.min(viewfinderWidth,
                                                viewfinderHeight);
                                            const qrboxSize = Math.floor(minDimension *
                                                0.7); // 70% dari dimensi terkecil

                                            return {
                                                width: qrboxSize,
                                                height: qrboxSize
                                            };
                                        };

                                        // Mulai scanner dengan kamera yang dipilih
                                        this.scanner.start(
                                            cameraId, {
                                                fps: 5,
                                                qrbox: qrboxFunction,
                                                aspectRatio: 1.333
                                            },
                                            (decodedText) => {
                                                console.log('QR Decoded:', decodedText);
                                                this.debugMessage = `Berhasil scan: ${decodedText}`;

                                                // Kirim token ke backend
                                                if (decodedText) {
                                                    // Stop scanner sepenuhnya
                                                    this.scanner.stop().then(() => {
                                                        // Kirim data ke Livewire
                                                        Livewire.dispatch('process-scan', {
                                                            token: decodedText
                                                        });

                                                        Livewire.on('scan-attendance', () => {
                                                            this.startScanning();
                                                        });


                                                    }).catch(err => {
                                                        console.error('Error stopping scanner:',
                                                            err);
                                                        // Coba restart scanner meski ada error
                                                        setTimeout(() => {
                                                            this.startScanning();
                                                        }, 2000);
                                                    });
                                                }
                                            },
                                            (errorMessage) => {
                                                // Hanya log error
                                                // console.error(`QR Error: ${errorMessage}`);
                                            }
                                        );
                                    } else {
                                        this.debugMessage = 'Tidak ada kamera yang tersedia';
                                    }
                                }).catch(err => {
                                    console.error('Camera access error:', err);
                                    this.debugMessage = `Error akses kamera: ${err.message}`;
                                });
                            } catch (e) {
                                console.error('Exception in startScanning:', e);
                                this.debugMessage = `Eksepsi: ${e.message}`;
                            }
                        });
                    },

                    stopScanning() {
                        if (this.scanner) {
                            this.scanner.stop();
                            this.scanner = null;
                        }
                        this.isScanning = false;
                        this.debugMessage = 'Scanner dihentikan';
                    }
                };
            }
        </script>
    </div>
