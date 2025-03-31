<?php

use Livewire\Volt\Component;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\Teacher;
use App\Models\SubjectClassAttendance;

new class extends Component {
    public $attendances;
    public $subjectClass;
    public $subjectClassSessions;
    public $teacherId;
    public $studentAttendances;

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
            ->where('teacher_id', $this->teacherId)
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
        $this->totalHours = $this->calculateTotalHours();
    }

    private function calculateTotalHours()
    {
        $totalHours = 0;
        foreach ($this->subjectClassSessions as $session) {
            $start = \Carbon\Carbon::parse($session->start_time);
            $end = \Carbon\Carbon::parse($session->end_time);
            $totalHours += $start->diffInHours($end);
        }
        return number_format($totalHours, 2, '.', '');
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
    openQrModal(qrCodePath, userId) {
        this.currentQrCode = qrCodePath;
        this.currentUserId = userId;
        this.showQrModal = true;
    },
    activeTab: 'overview'
}">
    <!-- Header Section -->
    <div class="flex flex-row items-center justify-between space-y-0">

        @php
            $hour = \Carbon\Carbon::now('Asia/Jakarta')->hour;
            if ($hour >= 5 && $hour < 12) {
                $greeting = 'Selamat Pagi';
            } elseif ($hour >= 12 && $hour < 18) {
                $greeting = 'Selamat Siang';
            } else {
                $greeting = 'Selamat Malam';
            }
        @endphp

        <p class="mt-1 font-inter text-sm font-medium text-gray-600 md:text-base">✨ {{ $greeting }},
            {{ auth()->user()->name }}</p>
        <p class="hidden font-inter text-xs font-medium text-gray-600 md:block md:text-sm">
            {{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}
        </p>


    </div>

    <!-- Navigation Tabs -->
    <div class="mt-6 border-b border-gray-200">
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
    </div>

    <!-- Overview Tab Content -->
    <div x-show="activeTab === 'overview'" class="mt-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-4">
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Total Kehadiran</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalAttendances }}</p>
                    <div class="rounded-full bg-blue-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 text-blue-600">
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
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 text-green-600">
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

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 text-purple-600">
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
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 text-amber-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jadwal Hari Ini -->
        <div class="mt-8 grid grid-cols-1 gap-0 md:grid-cols-3 md:gap-6">
            <div class="col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-md font-inter font-semibold text-gray-800">Jadwal Hari Ini</h2>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                        {{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l') }}
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
                                    <li class="flex items-center justify-between p-4 hover:bg-gray-50">
                                        <div class="flex items-center space-x-4">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-medium text-gray-900">{{ $session->subject_title }}
                                                </h3>
                                                <p class="text-sm text-gray-500">
                                                    {{ $session->subjectClass->class_name }} -
                                                    {{ $session->subjectClass->classes->name }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs font-medium text-gray-900 md:text-sm">
                                                {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}</p>
                                            <a href="{{ route('session.attendance', $session) }}" wire:navigate
                                                class="mt-1 inline-block whitespace-nowrap font-inter text-xs text-blue-600 hover:underline">Kelola
                                                Presensi</a>
                                        </div>
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
                @if ($hasClassesToday)
                    <div class="mt-2 flex flex-row justify-center">
                        <a href="{{ route('classes.attendances') }}" wire:navigate
                            class="mt-4 rounded-full bg-gray-400 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-gray-300">
                            Tambah Pertemuan +
                        </a>
                    </div>
                @endif
            </div>

            <!-- Quick Links and Notes -->
            <div class="mt-5 space-y-6 sm:mt-0">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="text-md mb-4 font-inter font-semibold text-gray-800">Aksi Cepat</h2>
                    <div class="space-y-3">

                        <button
                            @click="openQrModal('{{ auth()->user()->qr_code_path }}', '{{ auth()->user()->id }}')"
                            class="flex w-full items-center space-x-3 rounded-md bg-blue-50 p-3 font-inter text-blue-700 transition hover:bg-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-inter text-sm">Tampilkan QR Code</span>
                        </button>
                        <a href="#"
                            class="flex items-center space-x-3 rounded-md bg-purple-50 p-3 font-inter text-purple-700 transition hover:bg-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-inter text-sm">Buat Sesi Baru</span>
                        </a>
                        <a href="#"
                            class="flex items-center space-x-3 rounded-md bg-green-50 p-3 text-green-700 transition hover:bg-green-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span class="font-inter text-sm">Unduh Laporan</span>
                        </a>
                    </div>
                </div>

                <!-- Reminder Section -->
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Pengingat</h2>

                    @if (count($subjectClassSessions) > 0)
                        <ul class="space-y-3">
                            <li class="flex items-start space-x-3 rounded-md bg-yellow-50 p-3 text-yellow-800">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="mt-0.5 h-5 w-5 flex-shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <span class="text-sm">Perbarui presensi untuk pertemuan terakhir</span>
                            </li>
                            <li class="flex items-start space-x-3 rounded-md bg-blue-50 p-3 text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="mt-0.5 h-5 w-5 flex-shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                <span class="text-sm">Jadwalkan pertemuan untuk minggu depan</span>
                            </li>
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">Tidak ada pengingat saat ini.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Classes -->
        <div class="mt-8">
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
        </div>
    </div>

    <!-- Classes Tab Content -->
    <div x-show="activeTab === 'classes'" class="mt-6" x-cloak>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Daftar Mata Pelajaran</h2>
            <a href="{{ route('classes.attendances') }}"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Buat Kelas Baru
            </a>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
            <!-- Tampilan untuk layar desktop -->
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Mata Pelajaran
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Kelas
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Jurusan
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Jumlah Pertemuan
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Total Jam
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($subjectClass as $class)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $class->class_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $class->class_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $class->classes->name }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $class->classes->major->name }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        {{ $subjectClassSessions->where('subject_class_id', $class->id)->count() }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        @php
                                            $totalClassHours = 0;
                                            foreach (
                                                $subjectClassSessions->where('subject_class_id', $class->id)
                                                as $session
                                            ) {
                                                $start = \Carbon\Carbon::parse($session->start_time);
                                                $end = \Carbon\Carbon::parse($session->end_time);
                                                $totalClassHours += $start->diffInHours($end);
                                            }
                                        @endphp
                                        {{ number_format($totalClassHours, 2) }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <a href="{{ route('subject.detail', $class) }}"
                                        class="text-blue-600 hover:text-blue-900">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-10 w-10 text-gray-300">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                                        <p class="mt-1 text-sm text-gray-500">Anda belum memiliki kelas mata pelajaran.
                                        </p>
                                        <a href="{{ route('classes.attendances') }}"
                                            class="mt-3 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                            Buat Kelas
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Tampilan untuk layar mobile -->
            <div class="block md:hidden">
                @forelse ($subjectClass as $class)
                    <div class="border-b border-gray-200 p-4" href="{{ route('subject.detail', $class) }}">
                        <div class="flex flex-row items-center justify-between">
                            <div class="flex flex-row items-center">
                                <div
                                    class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 md:text-base">
                                        {{ $class->class_name }}</h3>
                                    <div class="mt-1 flex flex-row items-center gap-1 whitespace-normal">
                                        <span
                                            class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                            {{ Str::wordCount($class->classes->major->name) > 2 ? $class->classes->major->code : $class->classes->major->name }}
                                        </span>
                                        <span
                                            class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                                            {{ $class->classes->name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="whitespace-nowrap text-xs text-gray-500">Jumlah Pertemuan</p>
                                <p class="text-end text-sm text-gray-900">
                                    {{ $subjectClassSessions->where('subject_class_id', $class->id)->count() }}
                                </p>
                            </div>
                        </div>



                        <div class="mt-4 flex justify-end">
                            <a href="{{ route('subject.detail', $class) }}"
                                class="text-xs font-medium text-blue-600 hover:text-blue-900 md:text-sm">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="mx-auto h-10 w-10 text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                        <p class="mt-1 text-sm text-gray-500">Anda belum memiliki kelas mata pelajaran.</p>
                        <a href="{{ route('classes.attendances') }}"
                            class="mt-3 inline-block rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Buat Kelas
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Analytics Tab Content -->
    <div x-show="activeTab === 'analytics'" class="mt-6" x-cloak>
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Statistik Kehadiran</h2>

            <!-- Overview Statistics Cards -->
            <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex flex-col items-start md:flex-row">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-green-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <p class="text-xs font-medium text-gray-500 sm:text-sm">Tingkat Kehadiran</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ number_format($attendanceStats['total_attendance_rate'], 1) }}%</p>
                            <p class="mt-1 text-xs text-green-600">Keseluruhan</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex flex-col items-start md:flex-row">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-yellow-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <p class="text-xs font-medium text-gray-500 md:text-sm">Izin & Sakit</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ number_format($attendanceStats['izin_sakit_rate'], 1) }}%</p>
                            <p class="mt-1 text-xs text-gray-500">Dari Total Pertemuan</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex flex-col items-start md:flex-row">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <p class="text-xs font-medium text-gray-500 md:text-sm">Tanpa Keterangan</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ number_format($attendanceStats['tanpa_keterangan_rate'], 1) }}%</p>
                            <p class="mt-1 text-xs text-red-600">Dari Total Pertemuan</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex flex-col items-start md:flex-row">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-purple-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                            </svg>
                        </div>
                        <div class="mt-2 md:mt-0">
                            <p class="text-xs font-medium text-gray-500 md:text-sm">Siswa yang Perlu Perhatian</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ $attendanceStats['students_needing_attention'] }}</p>
                            <p class="mt-1 text-xs text-gray-500">Kehadiran < 70%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Chart placeholder -->
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="flex h-64 flex-col items-center justify-center">
                    <canvas id="attendance-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Class Comparison -->
        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Perbandingan Kehadiran Antar Kelas</h2>

            <div class="overflow-hidden rounded-lg border border-gray-200">
                @if (count($classAttendanceStats) > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Kelas</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Tingkat Kehadiran</th>
                                <th scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 md:table-cell">
                                    Izin/Sakit</th>
                                <th scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 md:table-cell">
                                    Tanpa Keterangan</th>
                                <th scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 md:table-cell">
                                    Tren</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($classAttendanceStats as $class)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $class['class_name'] }} - {{ $class['name'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-full max-w-xs">
                                                <div class="h-2.5 w-full rounded-full bg-gray-200">
                                                    <div class="h-2.5 rounded-full bg-green-600"
                                                        style="width: {{ $class['attendance_rate'] }}%"></div>
                                                </div>
                                            </div>
                                            <span
                                                class="ml-3 text-sm text-gray-900">{{ $class['attendance_rate'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="hidden whitespace-nowrap px-6 py-4 text-sm text-gray-500 sm:table-cell">
                                        {{ $class['izin_sakit_rate'] }}%
                                    </td>
                                    <td class="hidden whitespace-nowrap px-6 py-4 text-sm text-gray-500 sm:table-cell">
                                        {{ $class['tanpa_keterangan_rate'] }}%
                                    </td>
                                    <td class="hidden whitespace-nowrap px-6 py-4 text-sm sm:table-cell">
                                        @if ($class['trend'] > 0)
                                            <span class="inline-flex items-center text-green-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="mr-1 h-4 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                                                </svg>
                                                +{{ $class['trend'] }}%
                                            </span>
                                        @elseif($class['trend'] < 0)
                                            <span class="inline-flex items-center text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="mr-1 h-4 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l5.94-2.28m-5.94 2.28l-2.28 5.941" />
                                                </svg>
                                                {{ $class['trend'] }}%
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-gray-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="mr-1 h-4 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M18.75 7.5h-7.5A2.25 2.25 0 009 9.75v7.5A2.25 2.25 0 0011.25 19.5h7.5A2.25 2.25 0 0021 17.25v-7.5A2.25 2.25 0 0018.75 7.5z" />
                                                </svg>
                                                0%
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="flex flex-col items-center justify-center p-8 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada data perbandingan kelas</h3>
                        <p class="mt-1 text-sm text-gray-500">Data akan muncul setelah Anda memiliki pertemuan kelas
                            dengan presensi.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- QR Modal -->
    <div x-show="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
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
