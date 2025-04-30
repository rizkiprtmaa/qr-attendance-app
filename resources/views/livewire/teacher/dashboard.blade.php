<?php

use Livewire\Volt\Component;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\Teacher;
use App\Models\SubjectClassAttendance;
use Illuminate\Support\Str;

new class extends Component {
    public $attendances;
    public $subjectClass;
    public $subjectClassSessions;
    public $subjectClassWithoutSubstitute;
    public $teacherId;
    public $teacher;
    public $studentAttendances;
    public $checkIn;
    public $checkOut;
    public $currentDate;

    // Statistik kehadiran
    public $attendanceStats = [
        'total_attendance_rate' => 0,
        'izin_sakit_rate' => 0,
        'tanpa_keterangan_rate' => 0,
        'students_needing_attention' => 0,
    ];

    protected function calculateAttendanceStatistics()
    {
        if ($this->studentAttendances->isEmpty()) {
            return;
        }

        // Total jumlah attendance
        $totalAttendances = $this->studentAttendances->count();

        // Hitung jumlah setiap status
        $attendanceBreakdown = $this->studentAttendances->groupBy('status')->map->count()->toArray();

        // Perhitungan persentase
        $hadir = $attendanceBreakdown['hadir'] ?? 0;
        $tidakHadir = $attendanceBreakdown['tidak_hadir'] ?? 0;
        $sakit = $attendanceBreakdown['sakit'] ?? 0;
        $izin = $attendanceBreakdown['izin'] ?? 0;

        // Hitung total kehadiran
        $this->attendanceStats['total_attendance_rate'] = $totalAttendances > 0 ? round(($hadir / $totalAttendances) * 100, 2) : 0;

        // Hitung persentase izin/sakit
        $this->attendanceStats['izin_sakit_rate'] = $totalAttendances > 0 ? round((($sakit + $izin) / $totalAttendances) * 100, 2) : 0;

        // Hitung persentase tanpa keterangan
        $this->attendanceStats['tanpa_keterangan_rate'] = $totalAttendances > 0 ? round(($tidakHadir / $totalAttendances) * 100, 2) : 0;

        // Identifikasi siswa yang membutuhkan perhatian (kehadiran < 70%)
        $studentAttendanceRates = $this->calculateStudentAttendanceRates();
        $this->attendanceStats['students_needing_attention'] = count(
            array_filter($studentAttendanceRates, function ($rate) {
                return $rate < 70;
            }),
        );

        // Perhitungan statistik per kelas
        $this->calculateClassAttendanceStatistics();
    }

    public function downloadInfo()
    {
        $this->dispatch('show-toast', type: 'info', message: 'Fitur unduh informasi sedang dikembangkan');
    }

    protected function calculateStudentAttendanceRates()
    {
        // Hitung persentase kehadiran per siswa
        $studentAttendanceRates = [];

        // Kelompokkan attendance berdasarkan student_id
        $studentAttendances = $this->studentAttendances->groupBy('student_id');

        foreach ($studentAttendances as $studentId => $attendances) {
            $totalSessions = $attendances->count();
            $presentSessions = $attendances->where('status', 'hadir')->count();

            $attendanceRate = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100, 2) : 0;

            $studentAttendanceRates[$studentId] = $attendanceRate;
        }

        return $studentAttendanceRates;
    }

    protected function calculateClassAttendanceStatistics()
    {
        // Inisialisasi array untuk menyimpan statistik per kelas
        $classStats = [];

        foreach ($this->subjectClass as $class) {
            // Ambil semua sesi untuk kelas ini
            $sessions = $this->subjectClassSessions->where('subject_class_id', $class->id);

            if ($sessions->isEmpty()) {
                continue;
            }

            // Ambil semua attendance untuk sesi-sesi di kelas ini - gunakan collection filter
            $sessionIds = $sessions->pluck('id')->toArray();
            $classAttendances = $this->studentAttendances->whereIn('subject_class_session_id', $sessionIds);

            if ($classAttendances->isEmpty()) {
                continue;
            }

            // Hitung total attendance untuk kelas ini
            $totalClassAttendances = $classAttendances->count();

            // Hitung breakdown status untuk kelas ini
            $statusCounts = $classAttendances->groupBy('status')->map->count();

            // Hitung persentase untuk setiap status
            $hadirCount = $statusCounts['hadir'] ?? 0;
            $sakitCount = $statusCounts['sakit'] ?? 0;
            $izinCount = $statusCounts['izin'] ?? 0;
            $tidakHadirCount = $statusCounts['tidak_hadir'] ?? 0;

            // Hitung tren (bandingkan dengan bulan lalu jika data tersedia)
            $currentMonthAttendances = $classAttendances->filter(function ($attendance) {
                $session = $this->subjectClassSessions->firstWhere('id', $attendance->subject_class_session_id);
                if (!$session) {
                    return false;
                }

                $sessionDate = \Carbon\Carbon::parse($session->class_date);
                $currentMonth = \Carbon\Carbon::now()->startOfMonth();

                return $sessionDate->month == $currentMonth->month && $sessionDate->year == $currentMonth->year;
            });

            $lastMonthAttendances = $classAttendances->filter(function ($attendance) {
                $session = $this->subjectClassSessions->firstWhere('id', $attendance->subject_class_session_id);
                if (!$session) {
                    return false;
                }

                $sessionDate = \Carbon\Carbon::parse($session->class_date);
                $lastMonth = \Carbon\Carbon::now()->subMonth()->startOfMonth();

                return $sessionDate->month == $lastMonth->month && $sessionDate->year == $lastMonth->year;
            });

            $currentMonthRate = $currentMonthAttendances->isNotEmpty() ? ($currentMonthAttendances->where('status', 'hadir')->count() / $currentMonthAttendances->count()) * 100 : 0;

            $lastMonthRate = $lastMonthAttendances->isNotEmpty() ? ($lastMonthAttendances->where('status', 'hadir')->count() / $lastMonthAttendances->count()) * 100 : 0;

            $trend = $lastMonthRate > 0 ? round($currentMonthRate - $lastMonthRate, 1) : 0;

            // Simpan statistik kelas
            $classStats[] = [
                'id' => $class->id,
                'name' => $class->class_name,
                'class_name' => $class->classes->name,
                'attendance_rate' => $totalClassAttendances > 0 ? round(($hadirCount / $totalClassAttendances) * 100, 1) : 0,
                'izin_sakit_rate' => $totalClassAttendances > 0 ? round((($sakitCount + $izinCount) / $totalClassAttendances) * 100, 1) : 0,
                'tanpa_keterangan_rate' => $totalClassAttendances > 0 ? round(($tidakHadirCount / $totalClassAttendances) * 100, 1) : 0,
                'trend' => $trend,
            ];
        }

        // Urutkan statistik
        usort($classStats, function ($a, $b) {
            return $b['attendance_rate'] <=> $a['attendance_rate'];
        });

        $this->classAttendanceStats = $classStats;
    }

    public function mount()
    {
        $this->teacherId = auth()->user()->id;

        // 1. Load teacher (mengurangi 1 query)
        $this->teacher = Teacher::find($this->teacherId);

        // 2. Load attendances + user (mengurangi 1 query)
        $this->attendances = Attendance::with('user')->where('user_id', $this->teacherId)->get();

        // 3. Load subject classes dan SEMUA relasinya sekaligus (mengurangi >10 query)
        $this->subjectClass = SubjectClass::with(['classes.major', 'classes.student'])
            ->where('user_id', $this->teacherId)
            ->get();

        $this->subjectClassWithoutSubstitute = SubjectClassSession::with(['subjectClass.classes.major', 'subjectClassAttendances'])
            ->whereNull('created_by_substitute')
            ->get();

        // 4. Load semua subject class sessions dan SEMUA relasi untuk perhitungan statistik (mengurangi >5 query)
        $subjectClassIds = $this->subjectClass->pluck('id')->toArray();

        // PENTING: Eager load semua relasi yang dibutuhkan di view
        $this->subjectClassSessions = SubjectClassSession::with([
            'subjectClass.classes.major', // Eager load nested relations
            'subjectClassAttendances',
        ])
            ->whereIn('subject_class_id', $subjectClassIds)
            ->get();

        // 5. Proses data-data ini setelah dimuat
        $this->studentAttendances = collect();

        foreach ($this->subjectClassSessions as $session) {
            $this->studentAttendances = $this->studentAttendances->concat($session->subjectClassAttendances);
        }

        $this->studentAttendanceStatus = $this->studentAttendances->pluck('status');
        $this->checkInTime = $this->studentAttendances->pluck('check_in_time');

        // 6. Pre-compute semua statistik
        $this->calculateAttendanceStatistics();

        // 7. Pra-hitung nilai yang diperlukan di render
        $this->calculateTotalHours($this->teacherId);
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
    }

    private function calculateTotalHours($teacherId)
    {
        // 1. Hitung JP dari kelas reguler (yang sudah Anda implementasikan)
        $regularJP = SubjectClassSession::whereHas('subjectClass', function ($query) use ($teacherId) {
            $query->where('user_id', $teacherId);
        })
            ->whereNull('created_by_substitute')
            ->sum('jam_pelajaran');

        // 2. Hitung JP dari kelas yang digantikan guru ini (sebagai guru pengganti)
        $substitutionJP = SubjectClassSession::whereHas('substitutionRequest', function ($query) use ($teacherId) {
            $query->where('substitute_teacher_id', $teacherId)->whereIn('status', ['approved', 'completed']);
        })->sum('jam_pelajaran');

        // Total JP

        $this->totalHours = $regularJP + $substitutionJP;
    }

    public function render(): mixed
    {
        return view('livewire.teacher.dashboard', [
            'attendances' => $this->attendances,
            'totalAttendances' => $this->attendances->count(),
            'totalSubjectClasses' => $this->subjectClass->count(),
            'totalSubjectClassSessions' => $this->subjectClassSessions->count(),
            'teacher' => $this->teacher,
            'totalHours' => $this->totalHours,
            'subjectClassSessions' => $this->subjectClassSessions,
            'studentAttendanceStatus' => $this->studentAttendanceStatus,
            'checkInTime' => $this->checkInTime,
            'attendanceStats' => $this->attendanceStats,
            'classAttendanceStats' => $this->classAttendanceStats ?? [],
        ]);
    }
}; ?>

<div x-data="{
    currentQrCode: null,
    currentUserId: null,
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
    activeTab: 'overview'
}" class="mt-16 md:mt-0">


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
            class="fixed bottom-5 right-5 z-10 mb-4 flex w-full max-w-xs items-center rounded-lg p-4 shadow"
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

            <template x-if="toastType === 'info'">
                <div
                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 text-blue-500">
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
    <!-- Header Section -->
    <!-- Profile Card -->
    <div class="flex- flex-col">
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
                                class="md:tex-sm font-inter text-xs text-gray-600">{{ auth()->user()->teacher->nuptk ?? '000' }}</span>
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
                @role('kepala_sekolah')
                    <span
                        class="flex flex-row items-center gap-1 rounded-md bg-orange-300 px-2 py-1 text-xs text-orange-600"><svg
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                        </svg>
                        Kepala Sekolah</span>
                @endrole
                @hasrole('teacher')
                    @unlessrole('kepala_sekolah')
                        <span
                            class="flex flex-row items-center gap-1 rounded-md bg-green-300 px-2 py-1 text-xs text-green-600"><svg
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>Pengajar</span>
                    @endunlessrole
                @endhasrole

            </div>
        </div>

        <!-- Current Date -->
        <div class="mt-4 text-sm text-gray-700">
            {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, j F Y') }}
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
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-pink-500">
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
    </div>



    <!-- Navigation Tabs -->
    {{-- <div class="mt-6 border-b border-gray-200">
        <ul class="-mb-px flex flex-wrap text-center text-sm font-medium">
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'overview'"
                    :class="{ 'text-blue-600 border-b-2 border-blue-600': activeTab === 'overview', 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300': activeTab !== 'overview' }"
                    class="inline-block p-4">
                    Overview
                </a>
            </li>
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'classes'"
                    :class="{ 'text-blue-600 border-b-2 border-blue-600': activeTab === 'classes', 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300': activeTab !== 'classes' }"
                    class="inline-block p-4">
                    Kelas Saya
                </a>
            </li>
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'analytics'"
                    :class="{ 'text-blue-600 border-b-2 border-blue-600': activeTab === 'analytics', 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300': activeTab !== 'analytics' }"
                    class="inline-block p-4">
                    Analitik
                </a>
            </li>
        </ul>
    </div> --}}

    <!-- Overview Tab Content -->
    <div x-show="activeTab === 'overview'" class="mt-6">
        <!-- Stats Cards -->
        <div class="hidden grid-cols-2 gap-4 sm:grid-cols-2 md:grid md:grid-cols-4">
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Total Kehadiran</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalAttendances }}</p>
                    <div class="rounded-full bg-blue-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="size-6 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                </div>
            </div>
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Mata Pelajaran</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalSubjectClasses }}</p>
                    <div class="rounded-full bg-green-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="size-6 text-green-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                </div>
            </div>
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Jumlah Pertemuan</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalSubjectClassSessions }}</p>
                    <div class="rounded-full bg-purple-100 p-2">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="size-6 text-purple-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Total Jam</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalHours }}</p>
                    <div class="rounded-full bg-amber-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="size-6 text-amber-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jadwal Hari Ini -->
        @hasrole('teacher')
            @unlessrole('kepala_sekolah')
                <div class="mt-8 grid grid-cols-1 gap-0 md:grid-cols-1 md:gap-6">
                    <div class="col-span-2">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-md font-inter font-medium text-gray-800">Jadwal Hari Ini</h2>
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                <a href="{{ route('classes.attendances') }}">+ Buat kelas</a>
                            </span>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            @php
                                $hasClassesToday = false;
                                $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
                                foreach ($subjectClassSessions as $session) {
                                    if (\Carbon\Carbon::parse($session->class_date)->toDateString() === $today) {
                                        $hasClassesToday = true;
                                        break;
                                    }
                                }
                            @endphp

                            @if ($hasClassesToday)
                                <ul class="divide-y divide-gray-200">
                                    @foreach ($subjectClassSessions as $session)
                                        @if (\Carbon\Carbon::parse($session->class_date)->toDateString() === $today)
                                            <li class="w-full">
                                                <a href="{{ route('session.attendance', $session) }}"
                                                    class="block w-full hover:bg-gray-50">
                                                    <div class="flex items-center p-4">
                                                        <div
                                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                                class="h-5 w-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                                            </svg>
                                                        </div>
                                                        <div class="ml-4 flex-1">
                                                            <h3 class="md:text-md text-sm font-medium text-gray-900">
                                                                {{ $session->subjectClass->classes->name }} -
                                                                {{ $session->subjectClass->classes->major->code }}
                                                            </h3>
                                                            <div class="flex items-center justify-between">
                                                                <p
                                                                    class="overflow-hidden truncate text-xs text-gray-500 md:text-sm">
                                                                    {{ $session->subjectClass->class_name }} -
                                                                    {{ Str::limit($session->subject_title, 30, '...') }}
                                                                </p>

                                                                <p class="text-xs font-medium text-gray-900 md:text-sm">
                                                                    {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}
                                                                    -
                                                                    {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                <div class="flex flex-col items-center justify-center p-8">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-300">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada jadwal hari ini</h3>
                                    <p class="mt-1 text-center text-sm text-gray-500">Anda tidak memiliki kelas yang
                                        dijadwalkan untuk hari
                                        ini.</p>
                                </div>
                            @endif
                        </div>

                    </div>


                </div>
            @endunlessrole
        @endhasrole

        <!-- Recent Classes -->
        {{-- <div class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="flex items-center space-x-2 text-lg font-semibold text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Pertemuan Terbaru</span>
                </h2>
                <a href="{{ route('classes.attendances') }}"
                    class="text-sm font-medium text-blue-600 hover:underline">
                    Kelola Kehadiran
                </a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                @forelse ($subjectClassSessions->sortByDesc('created_at')->take(6) as $subject)
                    <div
                        class="rounded-lg border border-slate-200 bg-white shadow-sm transition duration-300 hover:shadow-md">
                        <div class="flex flex-col gap-1 p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-inter text-base font-medium text-slate-800">
                                    {{ $subject->subjectClass->class_name }}
                                </p>
                                <span class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($subject->created_at)->format('d M Y') }}
                                </span>
                            </div>
                            <p class="font-inter text-sm text-slate-700">{{ $subject->subject_title }}</p>
                            <div class="mt-1 flex flex-wrap gap-2">
                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                    {{ $subject->subjectClass->classes->major->name }}
                                </span>
                                <span
                                    class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                                    {{ $subject->subjectClass->classes->name }}
                                </span>
                                <span
                                    class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                    {{ \Carbon\Carbon::parse($subject->start_time)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($subject->end_time)->format('H:i') }}
                                </span>
                            </div>
                        </div>
                        <div class="border-t border-slate-200"></div>
                        <div class="flex items-center justify-between p-4">
                            <p class="text-xs font-medium text-slate-500">
                                {{ $subject->created_at->diffForHumans(['locale' => 'id']) }}
                            </p>
                            <a href="{{ route('session.attendance', $subject) }}"
                                class="rounded-md bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                Lihat Kehadiran
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3">
                        <div
                            class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-white p-8">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-300">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                            <h3 class="mt-2 text-base font-medium text-gray-900">Belum ada pertemuan</h3>
                            <p class="mt-1 text-sm text-gray-500">Anda belum membuat pertemuan apapun.</p>
                            <a href="{{ route('classes.attendances') }}"
                                class="mt-4 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Buat Pertemuan
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div> --}}

        {{-- Today Attendance History --}}
        <!-- Attendance History -->
        @role('kepala_sekolah')
            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-medium">Riwayat Kehadiran</h2>

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

                    @if (!$checkIn && !$checkOut)
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
        @endrole


    </div>



    <!-- QR Modal -->
    <div x-show="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-5"
        x-cloak @keydown.escape.window="showQrModal = false">
        <div class="w-full max-w-md rounded-lg bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-inter text-xl font-medium">QR Code Presensi</h2>
                <button @click="showQrModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 flex justify-center">
                <template x-if="currentQrCode">
                    <img :src="'/storage/' + currentQrCode" alt="QR Code"
                        class="h-auto max-w-full rounded-lg border border-gray-200 p-2">
                </template>
                <template x-if="!currentQrCode">
                    <div
                        class="flex h-64 w-64 flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                        <p class="mt-2 text-center text-sm text-gray-500">QR Code tidak tersedia</p>
                    </div>
                </template>
            </div>

            <div class="mt-3 flex justify-center space-x-4">
                <a x-show="currentUserId" :href="`/users/${currentUserId}/download-qr`"
                    class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    Download QR
                </a>
                <button @click="showQrModal = false"
                    class="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300">
                    Tutup
                </button>
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


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Pastikan element canvas ada sebelum mencoba membuat chart
            const canvas = document.getElementById('attendance-chart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Fungsi untuk memformat tanggal tanpa memerlukan moment.js
            function formatMonthYear(dateString) {
                const date = new Date(dateString);
                const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov",
                    "Dec"
                ];
                return `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
            }

            // Fungsi untuk mendapatkan key bulan tanpa moment.js
            function getMonthKey(dateString) {
                const date = new Date(dateString);
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            }

            // Fungsi untuk mengumpulkan data kehadiran per bulan
            function processAttendanceData(sessions, attendances) {
                // Kelompokkan data berdasarkan bulan
                const monthlyData = {};

                sessions.forEach(session => {
                    const monthKey = getMonthKey(session.class_date);

                    if (!monthlyData[monthKey]) {
                        monthlyData[monthKey] = {
                            hadir: 0,
                            tidak_hadir: 0,
                            sakit: 0,
                            izin: 0,
                            total: 0
                        };
                    }

                    // Cari attendances untuk session ini
                    const sessionAttendances = attendances.filter(att =>
                        att.subject_class_session_id === session.id
                    );

                    sessionAttendances.forEach(att => {
                        monthlyData[monthKey].total++;

                        if (att.status === 'hadir') {
                            monthlyData[monthKey].hadir++;
                        } else if (att.status === 'tidak_hadir') {
                            monthlyData[monthKey].tidak_hadir++;
                        } else if (att.status === 'sakit') {
                            monthlyData[monthKey].sakit++;
                        } else if (att.status === 'izin') {
                            monthlyData[monthKey].izin++;
                        }
                    });
                });

                return monthlyData;
            }

            try {
                // Ambil data dari backend
                const sessions = @json($subjectClassSessions);
                const attendances = @json($studentAttendances);

                // Pastikan bahwa keduanya adalah array
                if (!Array.isArray(sessions) || !Array.isArray(attendances)) {
                    console.error('Data sessions atau attendances tidak valid');
                    return;
                }

                // Jika array kosong, tampilkan chart kosong dengan pesan
                if (sessions.length === 0 || attendances.length === 0) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Belum ada data'],
                            datasets: [{
                                label: 'Belum ada data kehadiran',
                                data: [0],
                                backgroundColor: 'rgba(200, 200, 200, 0.6)'
                            }]
                        },
                        options: {
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Belum ada data kehadiran untuk ditampilkan'
                                }
                            }
                        }
                    });
                    return;
                }

                // Proses data kehadiran
                const monthlyAttendance = processAttendanceData(sessions, attendances);

                // Siapkan data untuk chart
                const labels = Object.keys(monthlyAttendance).sort();
                const hadir = labels.map(month => monthlyAttendance[month].hadir);
                const tidakHadir = labels.map(month => monthlyAttendance[month].tidak_hadir);
                const sakit = labels.map(month => monthlyAttendance[month].sakit);
                const izin = labels.map(month => monthlyAttendance[month].izin);

                // Konfigurasi chart
                const config = {
                    type: 'bar',
                    data: {
                        labels: labels.map(month => formatMonthYear(month + '-01')),
                        datasets: [{
                                label: 'Hadir',
                                data: hadir,
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Tidak Hadir',
                                data: tidakHadir,
                                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Sakit',
                                data: sakit,
                                backgroundColor: 'rgba(255, 206, 86, 0.6)',
                                borderColor: 'rgba(255, 206, 86, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Izin',
                                data: izin,
                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Statistik Kehadiran Bulanan'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.parsed.y || 0;
                                        const monthKey = labels[context.dataIndex];
                                        const totalMonth = monthlyAttendance[monthKey]?.total || 1;
                                        const percentage = ((value / totalMonth) * 100).toFixed(1);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            },
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                title: {
                                    display: true,
                                    text: 'Bulan'
                                }
                            },
                            y: {
                                stacked: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah Siswa'
                                },
                                beginAtZero: true
                            }
                        }
                    }
                };

                // Inisialisasi chart
                new Chart(ctx, config);
            } catch (error) {
                console.error('Error saat membuat chart:', error);
                // Tampilkan pesan error pada canvas
                ctx.font = '14px Arial';
                ctx.fillStyle = 'red';
                ctx.fillText('Terjadi kesalahan saat memuat grafik', 10, 50);
            }
        });
    </script>

</div>
