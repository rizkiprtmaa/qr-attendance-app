<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

new class extends Component {
    public $scan_type = 'datang';
    public $scan_message = '';
    public $scan_status = 'idle';
    public $last_scanned_token = null;
    public $listeners = ['process-scan' => 'processScan'];

    public function changeScanType($type)
    {
        $this->scan_type = $type;
        $this->reset('scan_message', 'scan_status', 'last_scanned_token');
    }

    public function resetMessage()
    {
        $this->scan_message = '';
        $this->scan_status = 'idle';
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

        try {
            // Rekam kehadiran
            $attendance = Attendance::recordAttendance($user->id, $this->scan_type);

            // Customize pesan berdasarkan tipe scan
            if ($this->scan_type === 'datang') {
                $this->scan_message = "Selamat datang, {$user->name}. Anda tercatat hadir pada " . Carbon::now()->timezone('Asia/Jakarta')->format('H:i');
            } else {
                $this->scan_message = "Selamat pulang, {$user->name}. Anda tercatat pulang pada " . Carbon::now()->timezone('Asia/Jakarta')->format('H:i');
            }

            $this->scan_status = 'success';
            $this->last_scanned_token = $token;
        } catch (\Exception $e) {
            $this->scan_message = 'Terjadi kesalahan saat mencatat presensi: ' . $e->getMessage();
            $this->scan_status = 'error';
            return;
        }

        // Dispatch event untuk update tabel
        $this->dispatch('scan-attendance');
    }

    #[On('scan-attendance')]
    public function render(): mixed
    {
        return view('livewire.admin.attendance-qr-scanner');
    }
};

?>

<div x-data="qrScanner()" x-init="initializeLibrary()" class="flex min-h-[80vh] flex-col items-center justify-center">

    <div class="min-w-[450px] overflow-hidden rounded-xl bg-white shadow-md">
        <div class="p-8">
            <h2 class="mb-4 text-center text-2xl font-bold">Presensi QR</h2>

            {{-- Tombol Pilih Tipe Scan --}}
            <div class="mb-6 flex justify-center space-x-6">
                <button wire:click="changeScanType('datang')"
                    class="{{ $scan_type == 'datang' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-800 border-2 border-gray-300 hover:bg-blue-100 hover:border-blue-500 hover:text-blue-600' }} rounded-full px-6 py-3 text-lg font-medium transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Scan Datang
                </button>
                <button wire:click="changeScanType('pulang')"
                    class="{{ $scan_type == 'pulang' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-800 border-2 border-gray-300 hover:bg-blue-100 hover:border-blue-500 hover:text-blue-600' }} rounded-full px-6 py-3 text-lg font-medium transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Scan Pulang
                </button>
            </div>



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
                    class="w-full rounded-lg bg-green-500 py-3 text-white transition hover:bg-green-600">
                    Buka Scanner
                </button>
            </div>

            {{-- Kontainer Scanner --}}
            <div x-show="isScanning" x-transition class="relative flex min-h-max w-full max-w-[450px] flex-col gap-5">
                <div id="reader" class="mb-4 h-72 w-full bg-gray-100"></div>

                <div class="mt-5 flex space-x-4">
                    <button @click="stopScanning()"
                        class="flex-1 rounded-lg bg-red-500 py-2 text-white transition hover:bg-red-600">
                        Tutup Scanner
                    </button>
                </div>
            </div>

            {{-- Debug Token --}}
            @if ($last_scanned_token)
                <div class="mt-6 rounded bg-gray-100 p-2">
                    <strong>Terakhir di-scan:</strong> {{ $last_scanned_token }}
                </div>
            @endif
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

                                    // Mulai scanner dengan kamera yang dipilih
                                    this.scanner.start(
                                        cameraId, {
                                            fps: 5,
                                            qrbox: 250,
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

                                                    // Restart scanner setelah beberapa detik
                                                    setTimeout(() => {
                                                        this.startScanning();
                                                    }, 1000);
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
