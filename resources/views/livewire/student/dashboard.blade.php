<?php
// student-dashboard.php (Livewire Volt Component)

use App\Models\Attendance;
use App\Models\Student;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    public $attendanceToday = [];
    public $checkIn;
    public $checkOut;
    public $subjects = [];
    public $currentDate;

    public function mount()
    {
        $this->loadAttendanceData();
    }

    public function loadAttendanceData()
    {
        $today = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $this->currentDate = Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY');

        // Get check-in data
        $this->checkIn = Attendance::where('user_id', auth()->id())
            ->where('attendance_date', $today)
            ->where('type', 'datang')
            ->first();

        // Get check-out data
        $this->checkOut = Attendance::where('user_id', auth()->id())
            ->where('attendance_date', $today)
            ->where('type', 'pulang')
            ->first();

        // Get all today's attendance records for this user
        $this->attendanceToday = Attendance::where('user_id', auth()->id())
            ->where('attendance_date', $today)
            ->orderBy('check_in_time', 'desc')
            ->get();

        // Get student's class and today's subjects
        $student = Student::where('user_id', auth()->id())->first();

        if ($student) {
            // Get subject class attendance for today (from subject_class_attendance table)
            $this->subjects = SubjectClassAttendance::whereHas('session.subjectClass', function ($query) use ($student) {
                $query->where('classes_id', $student->classes_id);
            })
                ->where('student_id', $student->id)
                ->whereDate('check_in_time', $today)
                ->orderBy('check_in_time', 'desc')
                ->with('session.subjectClass')
                ->get()
                ->map(function ($attendance) {
                    // Map to a more convenient format
                    $colorClasses = ['bg-blue-200', 'bg-red-200', 'bg-green-200', 'bg-yellow-200', 'bg-purple-200', 'bg-pink-200', 'bg-indigo-200', 'bg-orange-200'];

                    // Use a deterministic way to assign colors based on subject ID if available
                    $subjectId = $attendance->session->subject_class->subject_id ?? 0;
                    $colorIndex = $subjectId % count($colorClasses);

                    return [
                        'id' => $attendance->id,
                        'name' => $attendance->session->subjectClass->class_name ?? 'Tidak diketahui',
                        'start_time' => Carbon::parse($attendance->check_in_time)->timezone('asia/jakarta')->format('H:i'),
                        'color' => $colorClasses[$colorIndex],
                        'status' => $attendance->status ?? 'hadir',
                    ];
                })
                ->toArray();
        }
    }

    public function downloadInfo()
    {
        $this->dispatch('show-toast', type: 'info', message: 'Fitur unduh informasi sedang dikembangkan');
    }

    public function render(): mixed
    {
        return view('livewire.student.dashboard');
    }
};
?>

<div class="mx-auto mb-20 mt-16 max-w-xl md:mt-4 md:max-w-7xl" x-data="{
    showQrModal: false,
    showDownloadModal: false,
    openQrModal(qrCodePath, userId) {
        this.currentQrCode = qrCodePath;
        this.currentUserId = userId;
        this.showQrModal = true;
    },
    openDownloadModal(userName) {
        this.showDownloadModal = true;
        this.currentUserName = userName;
    },
}" x-cloak>
    <div class="flex flex-col">
        <!-- Profile Card -->
        <div class="rounded-xl bg-gradient-to-r from-blue-200 to-blue-100 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 overflow-hidden rounded-lg bg-red-500">
                        <img src="{{ auth()->user()->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}"
                            alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
                    </div>
                    <div>
                        <h2 class="font-inter text-lg font-semibold">{{ auth()->user()->name }}</h2>
                        <div class="flex flex-col gap-1 font-inter text-xs md:text-sm">
                            <span
                                class="truncate">{{ auth()->user()->student->classes->name . ' ' . auth()->user()->student->classes->major->code ?? 'Tidak ada kelas' }}</span>
                            <span
                                class="md:tex-sm font-inter text-xs text-gray-600">{{ auth()->user()->student->nisn ?? '000' }}</span>
                        </div>

                    </div>
                </div>

                <button @click="openQrModal('{{ auth()->user()->qr_code_path }}', '{{ auth()->user()->id }}')"
                    class="h-20 w-20 rounded-xl border border-white/20 bg-white/30 p-2 shadow-sm backdrop-blur-sm">
                    {{-- <img src="{{ auth()->user()->qr_code_path ? Storage::url(auth()->user()->qr_code_path) : '/images/qr-placeholder.png' }}"
                        alt="QR Code" class="h-full w-full"> --}}

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-18 w-18 flex items-center justify-center">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
                    </svg>



                </button>
            </div>

            <div class="flex flex-row items-end justify-between">
                <button @click="openDownloadModal('{{ auth()->user()->name }}')"
                    class="mt-3 flex w-auto items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mr-2 h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Unduh Informasi
                </button>

                <span
                    class="flex flex-row items-center gap-1 rounded-md bg-green-300 px-2 py-1 text-xs text-green-600"><svg
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>Siswa</span>

            </div>
        </div>

        <!-- Current Date -->
        <div class="mt-4 text-sm text-gray-700">
            {{ $currentDate }}
        </div>

        <!-- Attendance Times -->
        <div class="mt-3 grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="h-5 w-5 text-green-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                    </div>
                    <span class="ml-2 text-sm font-medium">Datang</span>
                </div>

                <div class="mt-2">
                    <h3 class="text-2xl font-bold">
                        {{ $checkIn && $checkIn->check_in_time ? \Carbon\Carbon::parse($checkIn->check_in_time)->format('H:i') : '--:--' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        {{ $checkIn
                            ? match ($checkIn->status) {
                                'tidak_hadir' => 'Absent',
                                default => ucfirst($checkIn->status),
                            }
                            : 'Belum presensi' }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-pink-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="h-5 w-5 text-pink-500">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                        </svg>
                    </div>
                    <span class="ml-2 text-sm font-medium">Pulang</span>
                </div>

                <div class="mt-2">
                    <h3 class="text-2xl font-bold">
                        {{ $checkOut && $checkOut->check_in_time ? \Carbon\Carbon::parse($checkOut->check_in_time)->format('H:i') : '--:--' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        {{ $checkOut
                            ? match ($checkOut->status) {
                                'hadir' => 'Tepat waktu',
                                'pulang_cepat' => 'Pulang cepat',
                                default => ucfirst($checkIn->status),
                            }
                            : 'Belum presensi' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="mt-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-medium">Riwayat Kehadiran</h2>
                <a class="text-sm font-medium text-blue-600" href="{{ route('attendance-history') }}"
                    wire:navigate>Lihat detail</a>
            </div>

            <div class="mt-3 space-y-3">
                @if ($checkOut)
                    <div class="flex items-center rounded-lg bg-white p-3 shadow-sm">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-pink-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-pink-500">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium">Pulang</p>
                            <p class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($checkOut->attendance_date)->format('d F Y') }}
                            </p>
                        </div>
                        <div class="ml-auto text-right">
                            <p class="font-bold">
                                {{ \Carbon\Carbon::parse($checkOut->check_in_time)->format('H:i') }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ match ($checkOut->status) {
                                    'hadir' => 'Tepat waktu',
                                    'pulang_cepat' => 'Pulang cepat',
                                    default => ucfirst($checkIn->status),
                                } }}
                            </p>
                        </div>
                    </div>
                @endif

                @foreach ($subjects as $subject)
                    <div class="flex items-center rounded-lg bg-white p-3 shadow-sm">
                        <div class="{{ $subject['color'] }} flex h-10 w-10 items-center justify-center rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-700">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium">{{ $subject['name'] }}</p>
                            <p class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::now()->timezone('asia/jakarta')->format('d F Y') }}</p>
                        </div>
                        <div class="ml-auto text-right">
                            <p class="font-bold">{{ $subject['start_time'] }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($subject['status']) }}</p>
                        </div>
                    </div>
                @endforeach

                @if ($checkIn)
                    <div class="flex items-center rounded-lg bg-white p-3 shadow-sm">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-green-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium">Datang</p>
                            <p class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($checkIn->attendance_date)->timezone('asia/jakarta')->format('d F Y') }}
                            </p>
                        </div>
                        <div class="ml-auto text-right">
                            <p class="font-bold">
                                {{ $checkIn && $checkIn->check_in_time ? \Carbon\Carbon::parse($checkIn->check_in_time)->format('H:i') : '--:--' }}
                            </p>
                            <p class="text-xs text-gray-500">{{ ucfirst($checkIn->status) }}</p>
                        </div>
                    </div>
                @endif

                @if (!$checkIn && !$checkOut && count($subjects) == 0)
                    <div
                        class="flex flex-col items-center justify-center rounded-lg bg-white p-6 text-center shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="mb-2 h-10 w-10 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122" />
                        </svg>

                        <p class="font-inter text-sm text-gray-500">Belum ada data kehadiran hari ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- QR Modal -->
    <div x-show="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-5"
        x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 shadow-xl transition-all"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-medium text-gray-900">QR Code Presensi</h2>
                <button @click="showQrModal = false" class="rounded-md p-1 hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-5 text-gray-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-3 flex justify-center rounded-lg bg-gray-50 p-4 md:mb-6">
                <template x-if="currentQrCode">
                    <img :src="'/storage/' + currentQrCode" alt="QR Code" class="h-auto max-w-full">
                </template>
                <template x-if="!currentQrCode">
                    <div class="flex flex-col items-center justify-center py-4 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="mb-2 size-10">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <p>QR Code tidak tersedia</p>
                    </div>
                </template>
            </div>

            <div class="flex justify-center">

                <a x-show="currentUserId" :href="`/users/${currentUserId}/download-qr`"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mr-1.5 size-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download QR
                </a>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="showDownloadModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-5" x-cloak
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="max-h-[90vh] w-full max-w-3xl transform overflow-auto rounded-lg bg-white p-6 shadow-xl transition-all"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-medium text-gray-900" x-text="`Detail Kartu: ${currentUserName}`"></h2>
                <button @click="showDownloadModal = false" class="rounded-md p-1 hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-5 text-gray-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>


            <div class="rounded-lg border border-gray-200">
                <!-- Detail konten akan di-load secara dinamis -->
                <p class="p-4 text-center text-gray-500">
                    Kartu Pengguna belum tersedia pada tahap percobaan.
                </p>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button @click="showDownloadModal = false"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Tutup
                </button>

            </div>
        </div>
    </div>
</div>
