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
use Illuminate\Support\Facades\DB;

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
    public $absent = 0;
    public $sick = 0;
    public $permission = 0;
    public $totalAbsent = 0;

    public $classRanking = 0;
    public $schoolRanking = 0;
    public $showClassRanking = true; // True untuk menampilkan ranking kelas, false untuk ranking keseluruhan
    public $totalClassStudents = 0;
    public $totalSchoolStudents = 0;

    // Data untuk chart
    public $attendanceChartData;
    public $classAttendanceChartData;

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

        $this->prepareChartData();
    }

    public function prepareChartData()
    {
        $this->attendanceChartData = $this->getMonthlyAttendanceData();
        $this->classAttendanceChartData = $this->getClassesAttendanceData();
        $this->dispatch('attendance-chart-updated', chartData: $this->attendanceChartData);
        $this->dispatch('class-attendance-chart-updated', chartData: $this->classAttendanceChartData);
    }

    private function getClassesAttendanceData()
    {
        $currentMonth = $this->currentMonth;
        $currentYear = $this->currentYear;
        $daysInMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->daysInMonth;

        // Get attendance data
        $startDate = Carbon::createFromDate($currentYear, $currentMonth, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($currentYear, $currentMonth, 1)->endOfMonth()->format('Y-m-d');

        $statusCounts = [
            'hadir' => 0,
            'sakit' => 0,
            'izin' => 0,
            'tidak_hadir' => 0,
        ];

        $student_id = Student::where('user_id', auth()->id())->first()->id;

        $attendances = SubjectClassAttendance::where('student_id', $student_id)
            ->whereBetween('check_in_time', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        foreach ($attendances as $attendance) {
            if (isset($statusCounts[$attendance->status])) {
                $statusCounts[$attendance->status] = $attendance->count;
            }
        }

        return [
            'labels' => ['Hadir', 'Izin', 'Sakit', 'Absen'],
            'datasets' => [
                [
                    'data' => [$statusCounts['hadir'], $statusCounts['izin'], $statusCounts['sakit'], $statusCounts['tidak_hadir']],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)', // blue-500
                        'rgba(99, 102, 241, 0.8)', // indigo-500
                        'rgba(236, 72, 153, 0.8)', // pink-500
                        'rgba(239, 68, 68, 0.8)', // red-500
                    ],
                    'borderWidth' => 0,
                ],
            ],
        ];
    }

    private function getMonthlyAttendanceData()
    {
        $currentMonth = $this->currentMonth;
        $currentYear = $this->currentYear;
        $daysInMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->daysInMonth;

        $days = [];
        $presentData = [];
        $sickData = [];
        $permissionData = [];
        $absentData = [];

        // Setup labels (days of the month)
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $days[] = (string) $day;
            $presentData[] = 0;
            $sickData[] = 0;
            $permissionData[] = 0;
            $absentData[] = 0;
        }

        // Get attendance data
        $startDate = Carbon::createFromDate($currentYear, $currentMonth, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($currentYear, $currentMonth, 1)->endOfMonth()->format('Y-m-d');

        $attendances = Attendance::where('user_id', auth()->id())
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('type', 'datang')
            ->select('attendance_date', 'status')
            ->get();

        // Fill data array
        foreach ($attendances as $attendance) {
            $day = Carbon::parse($attendance->attendance_date)->day;
            $index = $day - 1;

            if (in_array($attendance->status, ['hadir', 'terlambat'])) {
                $presentData[$index] = 1;
            } elseif ($attendance->status === 'sakit') {
                $sickData[$index] = 1;
            } elseif ($attendance->status === 'izin') {
                $permissionData[$index] = 1;
            } else {
                $absentData[$index] = 1;
            }
        }

        return [
            'labels' => $days,
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $presentData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)', // blue
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                    'fill' => true,
                ],
                [
                    'label' => 'Tidak Hadir',
                    'data' => $absentData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)', // red
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 1,
                    'fill' => true,
                ],
                [
                    'label' => 'Sakit',
                    'data' => $sickData,
                    'backgroundColor' => 'rgba(255, 206, 86, 0.2)', // yellow
                    'borderColor' => 'rgba(255, 206, 86, 1)',
                    'borderWidth' => 1,
                    'fill' => true,
                ],
                [
                    'label' => 'Izin',
                    'data' => $permissionData,
                    'backgroundColor' => 'rgba(128, 128, 128, 0.2)', // gray
                    'borderColor' => 'rgba(128, 128, 128, 1)',
                    'borderWidth' => 1,
                    'fill' => true,
                ],
            ],
        ];
    }

    public function toggleRankingType()
    {
        $this->showClassRanking = !$this->showClassRanking;
        $this->ranking = $this->showClassRanking ? $this->classRanking : $this->schoolRanking;
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

        $this->prepareChartData();
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
        $this->prepareChartData();
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

        // Hitung jumlah siswa di kelas
        if ($student->classes_id) {
            $this->totalClassStudents = Student::where('classes_id', $student->classes_id)->count();
        }

        // Hitung jumlah siswa keseluruhan
        $this->totalSchoolStudents = Student::count();

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

        // Hitung absen
        $this->absent = Attendance::where('user_id', auth()->id())
            ->where('type', 'datang')
            ->where('status', 'tidak_hadir')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        // Hitung sakit
        $this->sick = Attendance::where('user_id', auth()->id())
            ->where('type', 'datang')
            ->where('status', 'sakit')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        // Hitung izin
        $this->permission = Attendance::where('user_id', auth()->id())
            ->where('type', 'datang')
            ->where('status', 'izin')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        // Hitung total absen
        $this->totalAbsent = Attendance::where('user_id', auth()->id())
            ->where('type', 'datang')
            ->whereIn('status', ['tidak_hadir', 'sakit', 'izin'])
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->count();

        // Hitung ranking
        $this->loadRankingData($student, $startDate, $endDate);
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

        // Hitung absen
        $this->absent = SubjectClassAttendance::where('student_id', $student->id)
            ->where('status', 'tidak_hadir')
            ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Hitung sakit
        $this->sick = SubjectClassAttendance::where('student_id', $student->id)
            ->where('status', 'sakit')
            ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Hitung izin
        $this->permission = SubjectClassAttendance::where('student_id', $student->id)
            ->where('status', 'izin')
            ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Hitung total absen
        $this->totalAbsent = SubjectClassAttendance::where('student_id', $student->id)
            ->whereIn('status', ['tidak_hadir', 'sakit', 'izin'])
            ->whereBetween('check_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->count();

        // Load ranking data
        $this->loadClassRankingData($student, $startDate, $endDate);
    }

    private function loadRankingData($student, $startDate, $endDate)
    {
        // ------- Ranking di Kelas -------
        if ($student->classes_id) {
            // Dapatkan statistik kehadiran semua siswa di kelas dengan satu query
            $classmatesAttendance = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.id')
                ->leftJoin('attendances', function ($join) use ($startDate, $endDate) {
                    $join
                        ->on('users.id', '=', 'attendances.user_id')
                        ->where('attendances.type', '=', 'datang')
                        ->whereIn('attendances.status', ['hadir', 'terlambat'])
                        ->whereBetween('attendances.attendance_date', [$startDate, $endDate]);
                })
                ->where('students.classes_id', $student->classes_id)
                ->groupBy('students.id')
                ->select('students.id as student_id', DB::raw('COUNT(attendances.id) as attendance_count'), DB::raw('SUM(CASE WHEN attendances.status = "terlambat" THEN 1 ELSE 0 END) as late_count'))
                ->get();

            // Transformasi hasil query untuk perhitungan ranking
            $classAttendanceStats = [];
            foreach ($classmatesAttendance as $attendance) {
                $classAttendanceStats[$attendance->student_id] = [
                    'student_id' => $attendance->student_id,
                    'attendance_count' => $attendance->attendance_count ?? 0,
                    'late_percentage' => $attendance->attendance_count > 0 ? ($attendance->late_count / $attendance->attendance_count) * 100 : 0,
                ];
            }

            // Urutkan berdasarkan jumlah kehadiran (turun) dan persentase keterlambatan (naik)
            usort($classAttendanceStats, function ($a, $b) {
                if ($a['attendance_count'] == $b['attendance_count']) {
                    return $a['late_percentage'] <=> $b['late_percentage'];
                }
                return $b['attendance_count'] <=> $a['attendance_count'];
            });

            // Tentukan ranking kelas
            $classRank = 1;
            $prevCount = null;
            $prevLatePercentage = null;

            foreach ($classAttendanceStats as $index => $stat) {
                if ($prevCount !== null && ($stat['attendance_count'] != $prevCount || $stat['late_percentage'] != $prevLatePercentage)) {
                    $classRank = $index + 1;
                }

                if ($stat['student_id'] == $student->id) {
                    $this->classRanking = $classRank;
                    break;
                }

                $prevCount = $stat['attendance_count'];
                $prevLatePercentage = $stat['late_percentage'];
            }
        }

        // ------- Ranking Keseluruhan -------
        // Dapatkan statistik kehadiran semua siswa di sekolah dengan satu query
        $allStudentsAttendance = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('attendances', function ($join) use ($startDate, $endDate) {
                $join
                    ->on('users.id', '=', 'attendances.user_id')
                    ->where('attendances.type', '=', 'datang')
                    ->whereIn('attendances.status', ['hadir', 'terlambat'])
                    ->whereBetween('attendances.attendance_date', [$startDate, $endDate]);
            })
            ->groupBy('students.id')
            ->select('students.id as student_id', DB::raw('COUNT(attendances.id) as attendance_count'), DB::raw('SUM(CASE WHEN attendances.status = "terlambat" THEN 1 ELSE 0 END) as late_count'))
            ->get();

        // Transformasi hasil query untuk perhitungan ranking
        $schoolAttendanceStats = [];
        foreach ($allStudentsAttendance as $attendance) {
            $schoolAttendanceStats[$attendance->student_id] = [
                'student_id' => $attendance->student_id,
                'attendance_count' => $attendance->attendance_count ?? 0,
                'late_percentage' => $attendance->attendance_count > 0 ? ($attendance->late_count / $attendance->attendance_count) * 100 : 0,
            ];
        }

        // Urutkan berdasarkan jumlah kehadiran (turun) dan persentase keterlambatan (naik)
        usort($schoolAttendanceStats, function ($a, $b) {
            if ($a['attendance_count'] == $b['attendance_count']) {
                return $a['late_percentage'] <=> $b['late_percentage'];
            }
            return $b['attendance_count'] <=> $a['attendance_count'];
        });

        // Tentukan ranking keseluruhan sekolah
        $schoolRank = 1;
        $prevCount = null;
        $prevLatePercentage = null;

        foreach ($schoolAttendanceStats as $index => $stat) {
            if ($prevCount !== null && ($stat['attendance_count'] != $prevCount || $stat['late_percentage'] != $prevLatePercentage)) {
                $schoolRank = $index + 1;
            }

            if ($stat['student_id'] == $student->id) {
                $this->schoolRanking = $schoolRank;
                break;
            }

            $prevCount = $stat['attendance_count'];
            $prevLatePercentage = $stat['late_percentage'];
        }

        // Tentukan ranking yang akan ditampilkan (sesuai dengan tab yang aktif)
        $this->ranking = $this->showClassRanking ? $this->classRanking : $this->schoolRanking;
    }

    private function loadClassRankingData($student, $startDate, $endDate)
    {
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

<div class="mx-auto mt-10 max-w-7xl pb-20 pt-4 md:mt-0">
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
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Ranking Kehadiran</p>
            </div>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $ranking }} <span class="text-sm">/
                        {{ $showClassRanking ? $totalClassStudents : $totalSchoolStudents }}</span></p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-green-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                    </svg>
                </div>
            </div>
            <button wire:click="toggleRankingType"
                class="flex items-center justify-end text-[0.5rem] text-blue-600 hover:text-blue-800">
                {{ $showClassRanking ? 'Lihat Ranking Sekolah' : 'Lihat Ranking Kelas' }}
            </button>
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

    @if ($reportType === 'qr')
        <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-1">
            <!-- Kehadiran 7 Hari Terakhir -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-md text-center font-inter font-medium text-gray-900">Statistik Bulan
                        {{ Carbon::createFromDate($currentYear, $currentMonth, 1)->locale('id')->translatedFormat('F Y') }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="h-80" x-data="attendanceChart()" x-init="initChart(@js($attendanceChartData))"
                        @attendance-chart-updated.window="updateChart($event.detail.chartData)">
                        <canvas class="p-4" id="AttendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($reportType === 'class')
        <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-1">
            <!-- Kehadiran 7 Hari Terakhir -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-md text-center font-inter font-medium text-gray-900">Statistik Bulan
                        {{ Carbon::createFromDate($currentYear, $currentMonth, 1)->locale('id')->translatedFormat('F Y') }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="h-80" x-data="classAttendanceChart()" x-init="initChart(@js($classAttendanceChartData))"
                        @class-attendance-chart-updated.window="updateChart($event.detail.chartData)">
                        <canvas class="p-4" id="ClassAttendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif


    <!-- Statistik Absent Cards -->
    <div class="mt-8 grid grid-cols-2 gap-4">
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Jumlah Tidak Hadir</p>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $totalAbsent }}</p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>

                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Tanpa Keterangan</p>
            </div>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $absent }}</p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-orange-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>


                </div>
            </div>

        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Sakit</p>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $sick }}</p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-yellow-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                    </svg>

                </div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Izin</p>
            <div class="mt-1 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $permission }}</p>
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>

                </div>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div wire:loading wire:target="changeMonth"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
            <div class="flex items-center rounded-lg bg-white px-6 py-4 shadow-xl">
                <svg class="mr-3 h-6 w-6 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span class="text-lg font-medium">Memuat...</span>
            </div>
        </div>
    </div>


    <!-- Chart Initialization Scripts -->
    <script>
        function attendanceChart() {
            return {
                chart: null,

                initChart(chartData) {
                    // Tunggu hingga DOM selesai di-render
                    this.$nextTick(() => {
                        this.createChart(chartData);
                    });

                    // Tambahkan listener untuk event browser resize
                    window.addEventListener('resize', () => {
                        if (this.chart) {
                            this.chart.resize();
                        }
                    });
                },

                createChart(chartData) {
                    const ctx = document.getElementById('AttendanceChart').getContext('2d');

                    // Hapus chart lama jika ada
                    if (this.chart) {
                        this.chart.destroy();
                    }

                    // Buat chart baru jika data valid
                    if (chartData && chartData.labels && chartData.datasets) {
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: chartData,
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 1,
                                        ticks: {
                                            stepSize: 1
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top'
                                    }
                                }
                            }
                        });
                    }
                },

                updateChart(newData) {
                    if (!newData || !newData.labels || !newData.datasets) return;

                    // Jika chart sudah ada, update datanya
                    if (this.chart) {
                        this.chart.data.labels = newData.labels;
                        this.chart.data.datasets = newData.datasets;
                        this.chart.update();
                    } else {
                        // Jika chart belum ada, buat baru
                        this.createChart(newData);
                    }
                }
            };
        }

        function classAttendanceChart() {
            return {
                chart: null,

                initChart(chartData) {
                    // Tunggu hingga DOM selesai di-render
                    this.$nextTick(() => {
                        this.createChart(chartData);
                    });

                    // Tambahkan listener untuk event browser resize
                    window.addEventListener('resize', () => {
                        if (this.chart) {
                            this.chart.resize();
                        }
                    });
                },

                createChart(chartData) {
                    const ctx = document.getElementById('ClassAttendanceChart').getContext('2d');

                    // Hapus chart lama jika ada
                    if (this.chart) {
                        this.chart.destroy();
                    }

                    // Buat chart baru jika data valid
                    if (chartData && chartData.labels && chartData.datasets) {
                        this.chart = new Chart(ctx, {
                            type: 'doughnut',
                            data: chartData,
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,

                                plugins: {
                                    legend: {
                                        position: 'top'
                                    }
                                }
                            }
                        });
                    }
                },

                updateChart(newData) {
                    if (!newData || !newData.labels || !newData.datasets) return;

                    // Jika chart sudah ada, update datanya
                    if (this.chart) {
                        this.chart.data.labels = newData.labels;
                        this.chart.data.datasets = newData.datasets;
                        this.chart.update();
                    } else {
                        // Jika chart belum ada, buat baru
                        this.createChart(newData);
                    }
                }
            };
        }
    </script>



</div>
