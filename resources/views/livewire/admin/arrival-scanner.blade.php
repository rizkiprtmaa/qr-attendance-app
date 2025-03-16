<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Livewire\Attributes\On;

new class extends Component {
    public $scan_message = '';
    public $scan_status = 'idle';
    public $last_scanned_token = null;
    public $canProcess = true;

    public function mount()
    {
        // Varian sederhana hanya untuk absen datang
    }

    public function resetMessage()
    {
        $this->scan_message = '';
        $this->scan_status = 'idle';
    }

    #[On('process-scan')]
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
            // Rekam kehadiran tipe datang
            $attendance = Attendance::recordAttendance($user->id, 'datang');

            $this->scan_message = "Selamat datang, {$user->name}. Anda tercatat hadir pada " . Carbon::now()->timezone('Asia/Jakarta')->format('H:i');
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

    public function render(): mixed
    {
        return view('livewire.admin.arrival-scanner');
    }
};

?>

<div x-data="qrScanner()" x-init="initializeLibrary()" class="flex min-h-[80vh] flex-col items-center justify-center">
    <div class="min-w-[450px] overflow-hidden rounded-xl bg-white shadow-md">
        <div class="p-8">
            <h2 class="mb-4 text-center text-2xl font-bold">Presensi Datang QR</h2>

            <!-- Pesan Hasil Scan -->
            @if ($scan_message)
                <div wire:key="{{ now() }}" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => {
                    show = false;
                    $wire.resetMessage();
                }, 3000)"
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

            <!-- Kontainer QR Scanner -->
            <div class="relative">
                <div id="reader" class="mb-4 h-72 w-full bg-gray-100"></div>

                <!-- Overlay saat memproses -->
                <div x-show="!canProcess"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-black bg-opacity-50">
                    <div class="rounded-lg bg-blue-600 p-4 text-lg font-bold text-white">
                        Memproses data...
                    </div>
                </div>
            </div>

            <!-- Debug Token -->
            @if ($last_scanned_token)
                <div class="mt-6 rounded bg-gray-100 p-2">
                    <strong>Terakhir di-scan:</strong> {{ $last_scanned_token }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>



    <script>
        function qrScanner() {
            return {
                scanner: null,
                canProcess: true,
                debugMessage: '',
                initializeLibrary() {
                    if (typeof Html5Qrcode === 'undefined') {
                        const script = document.createElement('script');
                        script.src = 'https://unpkg.com/html5-qrcode';
                        script.async = true;
                        script.onload = () => {
                            this.debugMessage = 'Library QR berhasil dimuat';
                            console.log('Library QR berhasil dimuat');
                            this.startScanner();
                        };
                        script.onerror = () => {
                            this.debugMessage = 'Gagal memuat library QR';
                            console.error('Gagal memuat library QR');
                        };
                        document.head.appendChild(script);
                    } else {
                        this.startScanner();
                    }
                },

                startScanner() {
                    if (typeof Html5Qrcode === 'undefined') {
                        this.debugMessage = 'Tunggu library dimuat...';
                        return;
                    }

                    this.debugMessage = 'Memulai scanning...';
                    const readerElement = document.getElementById('reader');
                    if (!readerElement) {
                        this.debugMessage = 'Elemen reader tidak ditemukan';
                        return;
                    }

                    try {
                        this.scanner = new Html5Qrcode("reader", {
                            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
                        });

                        Html5Qrcode.getCameras().then(devices => {
                            if (devices && devices.length) {
                                // Gunakan kamera yang tersedia
                                const cameraId = devices.length > 1 ? devices[1].id : devices[0].id;
                                this.debugMessage = `Menggunakan kamera: ${cameraId}`;

                                // Mulai scanner dengan kamera yang dipilih
                                this.scanner.start(
                                    cameraId, {
                                        fps: 5,
                                        qrbox: 250,
                                        aspectRatio: 1.333
                                    },
                                    (decodedText) => {
                                        // Hanya proses jika flag canProcess aktif
                                        if (this.canProcess && decodedText) {
                                            console.log('QR Decoded:', decodedText);
                                            this.debugMessage = `Berhasil scan: ${decodedText}`;

                                            // Nonaktifkan pemrosesan sementara
                                            this.canProcess = false;

                                            Livewire.dispatch('process-scan', {
                                                token: decodedText
                                            });




                                            // Aktifkan kembali pemrosesan setelah jeda
                                            setTimeout(() => {
                                                this.canProcess = true;
                                                this.startScanner();
                                            }, 3000);
                                        }
                                    },
                                    (errorMessage) => {
                                        // Normal scanning error, tidak perlu ditampilkan
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
                        console.error('Exception in startScanner:', e);
                        this.debugMessage = `Eksepsi: ${e.message}`;
                    }
                }
            };
        }
    </script>

</div>
