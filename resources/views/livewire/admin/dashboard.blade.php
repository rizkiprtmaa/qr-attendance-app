<?php

use Livewire\Volt\Component;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public $totalStudents;
    public $totalTeachers;
    public $totalClasses;
    public $totalSubjectClasses;

    public $todayAttendance;
    public $todayAttendanceStats;
    public $ongoingSessions;

    public $attendanceChartData;
    public $monthlyAttendanceData;
    public $statusDistributionData;

    public $currentDate;

    // Cache durations
    private $shortCacheDuration = 300; // 5 minutes for frequently changing data
    private $longCacheDuration = 3600; // 1 hour for slowly changing data

    public function mount()
    {
        $this->currentDate = Carbon::now()->format('Y-m-d');
        $this->loadCounts();
        $this->loadTodayAttendance();
        $this->loadOngoingSessions();
        $this->prepareAllChartData();
    }

    private function loadCounts()
    {
        // Cache slow-changing counts to reduce queries
        $counts = Cache::remember('admin_dashboard_counts', $this->longCacheDuration, function () {
            return [
                'students' => Student::count(),
                'teachers' => Teacher::count(),
                'classes' => Classes::count(),
                'subjects' => SubjectClass::count(),
            ];
        });

        $this->totalStudents = $counts['students'];
        $this->totalTeachers = $counts['teachers'];
        $this->totalClasses = $counts['classes'];
        $this->totalSubjectClasses = $counts['subjects'];
    }

    private function loadTodayAttendance()
    {
        $today = Carbon::today()->timezone('asia/jakarta')->format('Y-m-d');
        $cacheKey = 'today_attendance_' . $today;

        // Cache today's attendance for a shorter duration since it changes more frequently
        $attendanceData = Cache::remember($cacheKey, $this->shortCacheDuration, function () use ($today) {
            // Single query to get all attendance stats for today
            $attendances = Attendance::where('attendance_date', $today)->select('status', 'type', DB::raw('count(*) as count'))->groupBy('status', 'type')->get();

            $stats = [
                'total' => 0,
                'hadir' => 0,
                'terlambat' => 0,
                'izin' => 0,
                'sakit' => 0,
                'tidak_hadir' => 0,
                'pulang_cepat' => 0,
                'datang' => 0,
                'pulang' => 0,
            ];

            foreach ($attendances as $attendance) {
                $stats['total'] += $attendance->count;

                if ($attendance->status) {
                    $stats[$attendance->status] = ($stats[$attendance->status] ?? 0) + $attendance->count;
                }

                if ($attendance->type) {
                    $stats[$attendance->type] = ($stats[$attendance->type] ?? 0) + $attendance->count;
                }
            }

            return $stats;
        });

        $this->todayAttendance = $attendanceData['total'];
        $this->todayAttendanceStats = $attendanceData;
    }

    private function loadOngoingSessions()
    {
        $now = Carbon::now()->timezone('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        $cacheKey = 'ongoing_sessions_' . $today . '_' . substr($currentTime, 0, 5); // Cache per 5 minutes

        $this->ongoingSessions = Cache::remember($cacheKey, 300, function () use ($today, $currentTime) {
            return SubjectClassSession::with(['subjectClass.classes', 'subjectClass.teacher.user'])
                ->whereDate('class_date', $today)
                ->where('start_time', '<=', $currentTime)
                ->where('end_time', '>=', $currentTime)
                ->take(5)
                ->get()
                ->map(function ($session) {
                    return [
                        'subject' => $session->subject_title,
                        'class' => $session->subjectClass->classes->name ?? 'Tidak ada kelas',
                        'major' => $session->subjectClass->classes->major->name ?? 'Tidak ada jurusan',
                        'teacher' => $session->subjectClass->user->name ?? 'Tidak ada guru',
                        'start_time' => Carbon::parse($session->start_time)->format('H:i'),
                        'end_time' => Carbon::parse($session->end_time)->format('H:i'),
                    ];
                });
        });
    }

    private function prepareAllChartData()
    {
        $cacheKey = 'admin_dashboard_charts_' . Carbon::now()->format('Y-m-d');

        $chartData = Cache::remember($cacheKey, $this->shortCacheDuration, function () {
            // Get all the data we need in as few queries as possible
            $lastWeekData = $this->getLastWeekAttendanceData();
            $monthlyData = $this->getMonthlyAttendanceData();
            $statusDistribution = $this->getStatusDistributionData();

            return [
                'weeklyChart' => $this->formatWeeklyChartData($lastWeekData),
                'monthlyChart' => $this->formatMonthlyChartData($monthlyData),
                'statusChart' => $this->formatStatusDistributionData($statusDistribution),
            ];
        });

        $this->attendanceChartData = $chartData['weeklyChart'];
        $this->monthlyAttendanceData = $chartData['monthlyChart'];
        $this->statusDistributionData = $chartData['statusChart'];
    }

    private function getLastWeekAttendanceData()
    {
        $startDate = Carbon::today()->timezone('asia/jakarta')->subDays(6)->format('Y-m-d');
        $endDate = Carbon::today()->timezone('asia/jakarta')->format('Y-m-d');

        // Single query to get all attendance data for last week
        return Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->select('attendance_date', 'status', DB::raw('count(*) as count'))
            ->groupBy('attendance_date', 'status')
            ->orderBy('attendance_date')
            ->get()
            ->groupBy('attendance_date');
    }

    private function formatWeeklyChartData($lastWeekData)
    {
        $dates = [];
        $attendanceData = [
            'hadir' => [],
            'terlambat' => [],
            'izin' => [],
            'sakit' => [],
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $formattedDate = $date->format('Y-m-d');
            $dayName = $date->format('D');
            $dates[] = $dayName;

            $dayData = $lastWeekData->get($formattedDate, collect([]));

            $attendanceData['hadir'][] = $dayData->firstWhere('status', 'hadir')->count ?? 0;
            $attendanceData['terlambat'][] = $dayData->firstWhere('status', 'terlambat')->count ?? 0;
            $attendanceData['izin'][] = $dayData->firstWhere('status', 'izin')->count ?? 0;
            $attendanceData['sakit'][] = $dayData->firstWhere('status', 'sakit')->count ?? 0;
        }

        return [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $attendanceData['hadir'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)', // blue-500
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $attendanceData['terlambat'],
                    'backgroundColor' => 'rgba(245, 158, 11, 0.8)', // amber-500
                ],
                [
                    'label' => 'Izin',
                    'data' => $attendanceData['izin'],
                    'backgroundColor' => 'rgba(99, 102, 241, 0.8)', // indigo-500
                ],
                [
                    'label' => 'Sakit',
                    'data' => $attendanceData['sakit'],
                    'backgroundColor' => 'rgba(236, 72, 153, 0.8)', // pink-500
                ],
            ],
        ];
    }

    private function getMonthlyAttendanceData()
    {
        $currentMonth = Carbon::now()->format('m');
        $currentYear = Carbon::now()->format('Y');

        $startOfMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->endOfMonth()->format('Y-m-d');

        // Get all attendance data for the month in a single query
        return Attendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->where('type', 'datang')
            ->select('attendance_date', 'status', DB::raw('count(*) as count'))
            ->groupBy('attendance_date', 'status')
            ->orderBy('attendance_date')
            ->get()
            ->groupBy('attendance_date');
    }

    private function formatMonthlyChartData($monthlyData)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $daysInMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->daysInMonth;

        $days = [];
        $presentData = [];
        $absentData = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($currentYear, $currentMonth, $day);
            $formattedDate = $date->format('Y-m-d');
            $days[] = $date->format('d');

            $dayData = $monthlyData->get($formattedDate, collect([]));

            $presentCount = ($dayData->firstWhere('status', 'hadir')->count ?? 0) + ($dayData->firstWhere('status', 'terlambat')->count ?? 0);
            $absentCount = $dayData->firstWhere('status', 'tidak_hadir')->count ?? 0;

            $presentData[] = $presentCount;
            $absentData[] = $absentCount;
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
            ],
        ];
    }

    private function getStatusDistributionData()
    {
        $currentMonth = Carbon::now()->format('m');
        $currentYear = Carbon::now()->format('Y');

        // Get status distribution in a single query
        return Attendance::whereYear('attendance_date', $currentYear)->whereMonth('attendance_date', $currentMonth)->select('status', DB::raw('count(*) as count'))->groupBy('status')->get()->pluck('count', 'status')->toArray();
    }

    private function formatStatusDistributionData($statusCounts)
    {
        return [
            'labels' => ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Tidak Hadir', 'Pulang Cepat'],
            'datasets' => [
                [
                    'data' => [$statusCounts['hadir'] ?? 0, $statusCounts['terlambat'] ?? 0, $statusCounts['izin'] ?? 0, $statusCounts['sakit'] ?? 0, $statusCounts['tidak_hadir'] ?? 0, $statusCounts['pulang_cepat'] ?? 0],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)', // blue-500
                        'rgba(245, 158, 11, 0.8)', // amber-500
                        'rgba(99, 102, 241, 0.8)', // indigo-500
                        'rgba(236, 72, 153, 0.8)', // pink-500
                        'rgba(239, 68, 68, 0.8)', // red-500
                        'rgba(16, 185, 129, 0.8)', // emerald-500
                    ],
                    'borderWidth' => 0,
                ],
            ],
        ];
    }
}; ?>

<div>

    <div class="mt-2">
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
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-4">
        <!-- Card: Total Siswa -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="flex items-center p-5">
                <div class="flex-shrink-0 rounded-full bg-blue-100 p-3">
                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-sm font-medium text-gray-600">Total Siswa</h2>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalStudents) }}</p>
                </div>
            </div>
        </div>

        <!-- Card: Total Guru -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="flex items-center p-5">
                <div class="flex-shrink-0 rounded-full bg-indigo-100 p-3">
                    <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-sm font-medium text-gray-600">Total Guru</h2>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalTeachers) }}</p>
                </div>
            </div>
        </div>

        <!-- Card: Total Kelas -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="flex items-center p-5">
                <div class="flex-shrink-0 rounded-full bg-green-100 p-3">
                    <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-sm font-medium text-gray-600">Total Kelas</h2>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalClasses) }}</p>
                </div>
            </div>
        </div>

        <!-- Card: Total Mata Pelajaran -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="flex items-center p-5">
                <div class="flex-shrink-0 rounded-full bg-purple-100 p-3">
                    <svg class="h-6 w-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-sm font-medium text-gray-600">Total Mata Pelajaran</h2>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalSubjectClasses) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Kehadiran Hari Ini -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900">Statistik Kehadiran Hari Ini
            ({{ Carbon::now()->format('d F Y') }})</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
            <!-- Kehadiran Hari Ini -->
            <div class="overflow-hidden rounded-lg bg-blue-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-blue-700">Total Kehadiran</h3>
                    <p class="mt-1 text-2xl font-semibold text-blue-900">{{ number_format($todayAttendance) }}</p>
                </div>
            </div>

            <!-- Datang -->
            <div class="overflow-hidden rounded-lg bg-green-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-green-700">Datang</h3>
                    <p class="mt-1 text-2xl font-semibold text-green-900">
                        {{ number_format($todayAttendanceStats['datang']) }}</p>
                </div>
            </div>

            <!-- Pulang -->
            <div class="overflow-hidden rounded-lg bg-purple-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-purple-700">Pulang</h3>
                    <p class="mt-1 text-2xl font-semibold text-purple-900">
                        {{ number_format($todayAttendanceStats['pulang']) }}</p>
                </div>
            </div>

            <!-- Hadir -->
            <div class="overflow-hidden rounded-lg bg-indigo-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-indigo-700">Hadir</h3>
                    <p class="mt-1 text-2xl font-semibold text-indigo-900">
                        {{ number_format($todayAttendanceStats['hadir']) }}</p>
                </div>
            </div>

            <!-- Terlambat -->
            <div class="overflow-hidden rounded-lg bg-amber-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-amber-700">Terlambat</h3>
                    <p class="mt-1 text-2xl font-semibold text-amber-900">
                        {{ number_format($todayAttendanceStats['terlambat']) }}</p>
                </div>
            </div>

            <!-- Tidak Hadir -->
            <div class="overflow-hidden rounded-lg bg-red-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-red-700">Tidak Hadir</h3>
                    <p class="mt-1 text-2xl font-semibold text-red-900">
                        {{ number_format($todayAttendanceStats['tidak_hadir']) }}</p>
                </div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <!-- Izin -->
            <div class="overflow-hidden rounded-lg bg-sky-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-sky-700">Izin</h3>
                    <p class="mt-1 text-2xl font-semibold text-sky-900">
                        {{ number_format($todayAttendanceStats['izin']) }}</p>
                </div>
            </div>

            <!-- Sakit -->
            <div class="overflow-hidden rounded-lg bg-pink-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-pink-700">Sakit</h3>
                    <p class="mt-1 text-2xl font-semibold text-pink-900">
                        {{ number_format($todayAttendanceStats['sakit']) }}</p>
                </div>
            </div>

            <!-- Pulang Cepat -->
            <div class="overflow-hidden rounded-lg bg-emerald-50 shadow">
                <div class="p-4">
                    <h3 class="text-sm font-medium text-emerald-700">Pulang Cepat</h3>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900">
                        {{ number_format($todayAttendanceStats['pulang_cepat']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Kelas Berlangsung -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900">Sesi Kelas Sedang Berlangsung</h2>
        <div class="mt-4 overflow-hidden rounded-lg bg-white shadow">
            @if (count($ongoingSessions) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Mata Pelajaran</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Kelas</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Guru</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($ongoingSessions as $session)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $session['subject'] }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $session['class'] }} -
                                            {{ $session['major'] }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $session['teacher'] }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ $session['start_time'] }} - {{ $session['end_time'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-base text-gray-500">Tidak ada kelas yang sedang berlangsung saat ini</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Chart Section -->
    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Kehadiran 7 Hari Terakhir -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Kehadiran 7 Hari Terakhir</h3>
            </div>
            <div class="p-6">
                <div class="h-80" x-data="weeklyAttendanceChart()">
                    <canvas id="weeklyAttendanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribusi Status Kehadiran Bulan Ini -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Distribusi Status Kehadiran
                    ({{ Carbon::now()->format('F Y') }})</h3>
            </div>
            <div class="p-6">
                <div class="h-80" x-data="statusDistributionChart()">
                    <canvas id="statusDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Kehadiran Bulan Ini Chart -->
    <div class="mt-8">
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Kehadiran Bulan Ini
                    ({{ Carbon::now()->format('F Y') }})</h3>
            </div>
            <div class="p-6">
                <div class="h-80" x-data="monthlyAttendanceChart()">
                    <canvas id="monthlyAttendanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Initialization Scripts -->
    <script>
        function weeklyAttendanceChart() {
            const data = @json($attendanceChartData);

            setTimeout(() => {
                const ctx = document.getElementById('weeklyAttendanceChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: data.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                stacked: true,
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        }
                    }
                });
            }, 100);
        }

        function statusDistributionChart() {
            const data = @json($statusDistributionData);

            setTimeout(() => {
                const ctx = document.getElementById('statusDistributionChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: data.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }, 100);
        }

        function monthlyAttendanceChart() {
            const data = @json($monthlyAttendanceData);

            setTimeout(() => {
                const ctx = document.getElementById('monthlyAttendanceChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: data.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        }
                    }
                });
            }, 100);
        }
    </script>
</div>
