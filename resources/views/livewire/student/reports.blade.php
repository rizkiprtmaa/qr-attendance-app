<?php
// resources/views/livewire/student/reports.blade.php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\SubjectClass;
use App\Models\SubjectClassAttendance;
use App\Models\SubjectClassSession;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    // Filter dan pengaturan
    public $reportType = 'qr'; // 'qr' atau 'class'
    public $currentMonth;
    public $currentYear;
    public $schoolYear = '';
    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';

    // Data statistik
    public $totalAttendance = 0;
    public $ranking = 0;
    public $onTime = 0;
    public $late = 0;

    // Data mata pelajaran
    public $subjects = [];

    public function mount()
    {
        // Set bulan dan tahun saat ini
        $this->currentMonth = intval(date('m'));
        $this->currentYear = intval(date('Y'));

        // Tentukan tahun ajaran
        $this->determineSchoolYear();

        // Load data laporan
        $this->loadReportData();
    }

    public function determineSchoolYear()
    {
        // Di Indonesia tahun ajaran biasanya Juli-Juni
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        if ($currentMonth >= 7) {
            // Jika bulan >= Juli, tahun ajaran dimulai di tahun ini
            $this->schoolYear = $currentYear . '/' . ($currentYear + 1);
        } else {
            // Jika bulan < Juli, tahun ajaran dimulai di tahun sebelumnya
            $this->schoolYear = $currentYear - 1 . '/' . $currentYear;
        }
    }

    public function updatedReportType()
    {
        $this->resetPage();
        $this->loadReportData();
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->loadSubjects();
    }

    public function changeMonth($direction)
    {
        if ($direction === 'prev') {
            if ($this->currentMonth == 1) {
                $this->currentMonth = 12;
                $this->currentYear--;
            } else {
                $this->currentMonth--;
            }
        } else {
            if ($this->currentMonth == 12) {
                $this->currentMonth = 1;
                $this->currentYear++;
            } else {
                $this->currentMonth++;
            }
        }

        $this->loadReportData();
    }

    public function loadReportData()
    {
        // Load statistik berdasarkan tipe laporan
        if ($this->reportType === 'qr') {
            $this->loadQrAttendanceStats();
        } else {
            $this->loadClassAttendanceStats();
        }

        // Load daftar mata pelajaran
        $this->loadSubjects();
    }

    public function loadQrAttendanceStats()
    {
        $student = Student::where('user_id', auth()->id())->first();
        if (!$student) {
            return;
        }

        // Dapatkan tanggal awal dan akhir bulan
        $startDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->endOfMonth()->format('Y-m-d');

        // Hitung total kehadiran (hadir + terlambat)
        $this->totalAttendance = Attendance::where('user_id', auth()->id())
            ->where('type', 'datang')
            ->whereIn('status', ['hadir', 'terlambat'])
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        // Hitung tepat waktu
        $this->onTime = Attendance::where('user_id', auth()->id())
            ->where('type', 'datang')
            ->where('status', 'hadir')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        // Hitung terlambat
        $this->late = Attendance::where('user_id', auth()->id())
            ->where('type', 'datang')
            ->where('status', 'terlambat')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        // Hitung ranking kehadiran di kelas
        if ($student->classes_id) {
            // Dapatkan semua siswa di kelas yang sama
            $classmates = Student::where('classes_id', $student->classes_id)->get();

            // Array untuk menyimpan statistik kehadiran
            $attendanceStats = [];

            // Hitung statistik untuk setiap siswa
            foreach ($classmates as $classmate) {
                $attendanceCount = Attendance::where('user_id', $classmate->user_id)
                    ->where('type', 'datang')
                    ->whereIn('status', ['hadir', 'terlambat'])
                    ->whereBetween('attendance_date', [$startDate, $endDate])
                    ->count();

                $lateCount = Attendance::where('user_id', $classmate->user_id)
                    ->where('type', 'datang')
                    ->where('status', 'terlambat')
                    ->whereBetween('attendance_date', [$startDate, $endDate])
                    ->count();

                // Simpan statistik (jumlah hadir dan persentase keterlambatan)
                $attendanceStats[$classmate->id] = [
                    'student_id' => $classmate->id,
                    'attendance_count' => $attendanceCount,
                    'late_percentage' => $attendanceCount > 0 ? ($lateCount / $attendanceCount) * 100 : 0,
                ];
            }

            // Urutkan berdasarkan jumlah kehadiran (turun) dan persentase keterlambatan (naik)
            usort($attendanceStats, function ($a, $b) {
                if ($a['attendance_count'] == $b['attendance_count']) {
                    return $a['late_percentage'] <=> $b['late_percentage'];
                }
                return $b['attendance_count'] <=> $a['attendance_count'];
            });

            // Tentukan ranking
            $rank = 1;
            $prevCount = null;
            $prevLatePercentage = null;
            $prevRank = 1;

            foreach ($attendanceStats as $index => $stat) {
                if ($prevCount !== null && ($stat['attendance_count'] != $prevCount || $stat['late_percentage'] != $prevLatePercentage)) {
                    $rank = $index + 1;
                }

                if ($stat['student_id'] == $student->id) {
                    $this->ranking = $rank;
                    break;
                }

                $prevCount = $stat['attendance_count'];
                $prevLatePercentage = $stat['late_percentage'];
                $prevRank = $rank;
            }
        }
    }

    public function loadClassAttendanceStats()
    {
        $student = Student::where('user_id', auth()->id())->first();
        if (!$student) {
            return;
        }

        // Dapatkan tanggal awal dan akhir bulan
        $startDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->endOfMonth()->format('Y-m-d');

        // Hitung total kehadiran kelas
        $this->totalAttendance = SubjectClassAttendance::where('student_id', $student->id)
            ->whereIn('status', ['hadir', 'terlambat'])
            ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Hitung tepat waktu
        $this->onTime = SubjectClassAttendance::where('student_id', $student->id)
            ->where('status', 'hadir')
            ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Hitung terlambat
        $this->late = SubjectClassAttendance::where('student_id', $student->id)
            ->where('status', 'terlambat')
            ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Hitung ranking kehadiran di kelas untuk mata pelajaran
        if ($student->classes_id) {
            // Dapatkan semua siswa di kelas yang sama
            $classmates = Student::where('classes_id', $student->classes_id)->get();

            // Array untuk menyimpan statistik kehadiran
            $attendanceStats = [];

            // Hitung statistik untuk setiap siswa
            foreach ($classmates as $classmate) {
                $attendanceCount = SubjectClassAttendance::where('student_id', $classmate->id)
                    ->whereIn('status', ['hadir', 'terlambat'])
                    ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->count();

                $lateCount = SubjectClassAttendance::where('student_id', $classmate->id)
                    ->where('status', 'terlambat')
                    ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->count();

                // Simpan statistik
                $attendanceStats[$classmate->id] = [
                    'student_id' => $classmate->id,
                    'attendance_count' => $attendanceCount,
                    'late_percentage' => $attendanceCount > 0 ? ($lateCount / $attendanceCount) * 100 : 0,
                ];
            }

            // Urutkan
            usort($attendanceStats, function ($a, $b) {
                if ($a['attendance_count'] == $b['attendance_count']) {
                    return $a['late_percentage'] <=> $b['late_percentage'];
                }
                return $b['attendance_count'] <=> $a['attendance_count'];
            });

            // Tentukan ranking
            $rank = 1;
            $prevCount = null;
            $prevLatePercentage = null;
            $prevRank = 1;

            foreach ($attendanceStats as $index => $stat) {
                if ($prevCount !== null && ($stat['attendance_count'] != $prevCount || $stat['late_percentage'] != $prevLatePercentage)) {
                    $rank = $index + 1;
                }

                if ($stat['student_id'] == $student->id) {
                    $this->ranking = $rank;
                    break;
                }

                $prevCount = $stat['attendance_count'];
                $prevLatePercentage = $stat['late_percentage'];
                $prevRank = $rank;
            }
        }
    }

    public function loadSubjects()
    {
        $student = Student::where('user_id', auth()->id())->first();
        if (!$student) {
            return;
        }

        // Dapatkan semua SubjectClass untuk kelas siswa
        $query = SubjectClass::whereHas('subjectClassSessions', function ($q) use ($student) {
            $q->whereHas('subjectClassAttendances', function ($q2) use ($student) {
                $q2->where('student_id', $student->id);
            });
        })->with([
            'subjectClassSessions.subjectClassAttendances' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            },
        ]);

        // Terapkan pencarian jika ada
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('class_name', 'like', '%' . $this->search . '%');
            });
        }

        // Urutkan
        $query->orderBy('class_name', $this->sortDirection);

        $subjectClasses = $query->get();

        // Siapkan data untuk ditampilkan
        $this->subjects = $subjectClasses
            ->map(function ($subjectClass) {
                $colorClasses = ['bg-red-200', 'bg-blue-200', 'bg-green-200', 'bg-yellow-200', 'bg-purple-200', 'bg-pink-200', 'bg-indigo-200', 'bg-orange-200'];

                // Gunakan ID atau nilai lain yang unik untuk menentukan warna
                $colorIndex = $subjectClass->id % count($colorClasses);

                // Hitung total kehadiran untuk mata pelajaran ini
                $attendanceCount = 0;
                foreach ($subjectClass->subjectClassSessions as $session) {
                    $attendanceCount += $session->subjectClassAttendances->count();
                }

                return [
                    'id' => $subjectClass->id,
                    'name' => $subjectClass->class_name ?? 'Tidak diketahui',
                    'teacher' => $subjectClass->user->name ?? 'Tidak diketahui',
                    'code' => $subjectClass->class_code ?? 'Tidak diketahui',
                    'color' => $colorClasses[$colorIndex],
                    'attendance_count' => $attendanceCount,
                ];
            })
            ->toArray();
    }

    public function downloadReport($subjectId = null)
    {
        $reportType = $subjectId ? 'subject' : 'general';
        $message = $reportType == 'subject' ? 'Unduh laporan mata pelajaran akan segera tersedia' : 'Unduh laporan kehadiran akan segera tersedia';

        $this->dispatch('show-toast', type: 'info', message: $message);
    }

    public function sortSubjects()
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->loadSubjects();
    }

    public function render(): mixed
    {
        return view('livewire.student.reports');
    }
};
?>

<div class="mx-auto mt-10 max-w-7xl px-4 pb-20 pt-4 sm:px-6 lg:px-8">


    <!-- Tipe Laporan Switcher -->
    <div class="mb-6 flex rounded-xl bg-white p-1.5 shadow-sm">
        <button wire:click="$set('reportType', 'qr')"
            class="{{ $reportType === 'qr' ? 'bg-blue-500 text-white' : 'text-gray-600 hover:bg-gray-100' }} w-1/2 rounded-lg py-2 text-center text-sm font-medium">
            Kehadiran QR
        </button>
        <button wire:click="$set('reportType', 'class')"
            class="{{ $reportType === 'class' ? 'bg-blue-500 text-white' : 'text-gray-600 hover:bg-gray-100' }} w-1/2 rounded-lg py-2 text-center text-sm font-medium">
            Kehadiran Kelas
        </button>
    </div>

    <!-- Bulan Switcher -->
    <div class="mb-6 flex items-center justify-between">
        <button wire:click="changeMonth('prev')" class="rounded-full bg-white p-2 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <span class="text-sm font-medium">
            {{ Carbon::createFromDate($currentYear, $currentMonth, 1)->translatedFormat('F Y') }}
        </span>

        <button wire:click="changeMonth('next')" class="rounded-full bg-white p-2 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <!-- Statistik Cards -->
    <div class="mb-8 grid grid-cols-2 gap-4">
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Jumlah Kehadiran</p>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $totalAttendance }}</p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-green-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                    </svg>

                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Ranking Kehadiran</p>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $ranking }}</p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-green-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                    </svg>

                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Tepat Waktu</p>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $onTime }}</p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-green-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Terlambat</p>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $late }}</p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-yellow-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>

                </div>
            </div>
        </div>
    </div>

    <!-- Download Laporan Kehadiran -->
    <div class="mb-8">
        <h2 class="mb-5 text-lg font-semibold">Unduh Laporan Kehadiran</h2>

        <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-6 w-6 text-gray-600">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium">Laporan Kehadiran</p>
                    <p class="text-sm text-gray-500">TA {{ $schoolYear }}</p>
                </div>
            </div>

            <button wire:click="downloadReport" class="rounded-lg bg-blue-900 px-4 py-2 font-medium text-white">
                Unduh
            </button>
        </div>
    </div>

    <!-- Download Laporan Kehadiran Mata Pelajaran -->
    <div>
        <h2 class="mb-5 text-lg font-semibold">Unduh Laporan Kehadiran Mata Pelajaran</h2>

        <div class="mb-4 flex items-center">
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari disini..."
                    class="w-full rounded-lg border-gray-200 bg-white py-3 pl-10 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <button wire:click="sortSubjects" class="ml-4 rounded-lg bg-blue-500 px-4 py-3 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                    <path
                        d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                </svg>
            </button>
        </div>

        <div class="space-y-4">
            @forelse($subjects as $subject)
                <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="{{ $subject['color'] }} flex h-12 w-12 items-center justify-center rounded-lg">
                            <p class="font-medium">{{ $subject['code'] }}</p>
                        </div>
                        <div>
                            <p class="truncate text-ellipsis font-medium">{{ $subject['name'] }}</p>
                            <p class="text-sm text-gray-500">{{ $subject['teacher'] }}</p>
                        </div>
                    </div>

                    <button wire:click="downloadReport({{ $subject['id'] }})"
                        class="rounded-lg bg-blue-900 px-4 py-2 font-medium text-white">
                        Unduh
                    </button>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center rounded-xl bg-white py-10 text-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mb-2 h-12 w-12 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <p class="text-gray-600">Tidak ada mata pelajaran yang ditemukan</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
