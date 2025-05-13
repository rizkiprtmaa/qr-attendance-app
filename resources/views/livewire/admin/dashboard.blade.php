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

<div class="mt-12 md:mt-0">

    <div class="mt-2 hidden md:block">
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
                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
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
        <div class="flex flex-row items-center justify-between">
            <h2 class="flex flex-row items-center gap-2 font-inter text-sm font-medium text-gray-900 md:text-lg"><svg
                    id="fi_3176395" class="h-6 w-6" enable-background="new 0 0 497 497" height="512"
                    viewBox="0 0 497 497" width="512" xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <path
                            d="m472 80v77h-447v-77c0-11.51 6.48-21.51 16-26.53 4.18-2.22 8.94-3.47 14-3.47h387c11.51 0 21.51 6.48 26.53 16 2.22 4.18 3.47 8.94 3.47 14z"
                            fill="#fd646f"></path>
                        <path d="m145 50v56c0 5.52-4.48 10-10 10h-46c-5.52 0-10-4.48-10-10v-56z" fill="#fc4755"></path>
                        <path d="m402 50v56c0 5.52-4.48 10-10 10h-46c-5.52 0-10-4.48-10-10v-56z" fill="#fc4755"></path>
                        <path d="m41 53.47v103.53h-16v-77c0-11.51 6.48-21.51 16-26.53z" fill="#fc4755"></path>
                        <g fill="#e6e6e6">
                            <path
                                d="m107 216.82h51.45c2.761 0 5 2.239 5 5v51.45c0 2.761-2.239 5-5 5h-51.45c-2.761 0-5-2.239-5-5v-51.45c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m107 293.27h51.45c2.761 0 5 2.239 5 5v51.46c0 2.761-2.239 5-5 5h-51.45c-2.761 0-5-2.239-5-5v-51.46c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m107 369.73h51.45c2.761 0 5 2.239 5 5v51.45c0 2.761-2.239 5-5 5h-51.45c-2.761 0-5-2.239-5-5v-51.45c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m183.45 216.82h52.55c2.761 0 5 2.239 5 5v51.45c0 2.761-2.239 5-5 5h-52.55c-2.761 0-5-2.239-5-5v-51.45c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m183.45 293.27h52.55c2.761 0 5 2.239 5 5v51.46c0 2.761-2.239 5-5 5h-52.55c-2.761 0-5-2.239-5-5v-51.46c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m183.45 369.73h52.55c2.761 0 5 2.239 5 5v51.45c0 2.761-2.239 5-5 5h-52.55c-2.761 0-5-2.239-5-5v-51.45c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m261 216.82h52.55c2.761 0 5 2.239 5 5v51.45c0 2.761-2.239 5-5 5h-52.55c-2.761 0-5-2.239-5-5v-51.45c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m261 293.27h52.55c2.761 0 5 2.239 5 5v51.46c0 2.761-2.239 5-5 5h-52.55c-2.761 0-5-2.239-5-5v-51.46c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m261 369.73h52.55c2.761 0 5 2.239 5 5v51.45c0 2.761-2.239 5-5 5h-52.55c-2.761 0-5-2.239-5-5v-51.45c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m395 221.82v51.45c0 2.761-2.239 5-5 5h-51.45c-2.761 0-5-2.239-5-5v-51.45c0-2.761 2.239-5 5-5h51.45c2.761 0 5 2.239 5 5z">
                            </path>
                            <path
                                d="m338.55 293.27h51.45c2.761 0 5 2.239 5 5v51.46c0 2.761-2.239 5-5 5h-51.45c-2.761 0-5-2.239-5-5v-51.46c0-2.761 2.239-5 5-5z">
                            </path>
                            <path
                                d="m338.55 369.73h51.45c2.761 0 5 2.239 5 5v51.45c0 2.761-2.239 5-5 5h-51.45c-2.761 0-5-2.239-5-5v-51.45c0-2.761 2.239-5 5-5z">
                            </path>
                        </g>
                        <path d="m25 150v317c0 16.57 13.43 30 30 30h387c16.57 0 30-13.43 30-30v-317z" fill="#f5f5f5">
                        </path>
                        <path
                            d="m145 10v80c0 5.52-4.48 10-10 10h-30c-5.52 0-10-4.48-10-10v-80c0-5.52 4.48-10 10-10h30c5.52 0 10 4.48 10 10z"
                            fill="#50758d"></path>
                        <path d="m102 216.82h61.45v61.45h-61.45z" fill="#ffac5c"></path>
                        <path d="m333.55 216.82h61.45v61.45h-61.45z" fill="#ffac5c"></path>
                        <path d="m178.45 216.82h62.55v61.45h-62.55z" fill="#ffac5c"></path>
                        <path d="m102 293.27h61.45v61.46h-61.45z" fill="#ffac5c"></path>
                        <path d="m333.55 293.27h61.45v61.46h-61.45z" fill="#e6e6e6"></path>
                        <path d="m178.45 293.27h62.55v61.46h-62.55z" fill="#ffac5c"></path>
                        <path d="m102 369.73h61.45v61.45h-61.45z" fill="#e6e6e6"></path>
                        <path d="m333.55 369.73h61.45v61.45h-61.45z" fill="#e6e6e6"></path>
                        <path d="m178.45 369.73h62.55v61.45h-62.55z" fill="#e6e6e6"></path>
                        <path d="m256 216.82h62.55v61.45h-62.55z" fill="#ffac5c"></path>
                        <path d="m256 293.27h62.55v61.46h-62.55z" fill="#ffac5c"></path>
                        <path d="m256 369.73h62.55v61.45h-62.55z" fill="#e6e6e6"></path>
                        <path
                            d="m402 10v80c0 5.52-4.48 10-10 10h-30c-5.52 0-10-4.48-10-10v-80c0-5.52 4.48-10 10-10h30c5.52 0 10 4.48 10 10z"
                            fill="#50758d"></path>
                        <path
                            d="m25 150v317c0 16.57 13.43 30 30 30h11.08c-13.99-2.54-24.58-14.78-24.58-29.5v-296.28c1.13-3.05 4.06-5.22 7.5-5.22h423v-15.5-.5z"
                            fill="#e6e6e6"></path>
                        <path d="m121 100h-16c-5.52 0-10-4.48-10-10v-80c0-5.52 4.48-10 10-10h6v90c0 5.52 4.48 10 10 10z"
                            fill="#2b597f"></path>
                        <path d="m378 100h-16c-5.52 0-10-4.48-10-10v-80c0-5.52 4.48-10 10-10h6v90c0 5.52 4.48 10 10 10z"
                            fill="#2b597f"></path>
                    </g>
                </svg>Statistik Kehadiran Hari Ini
            </h2>
            <a href="{{ route('attendance.scan') }}" wire:navigate
                class="flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none">
                <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                Scan QR
            </a>
        </div>
        <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Card: Ringkasan Kehadiran -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-blue-100 bg-blue-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-blue-800">Ringkasan Kehadiran</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-4">
                        <!-- Tepat Waktu -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Tepat Waktu</p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ number_format($todayAttendanceStats['hadir']) }}</p>
                            <div class="mt-1 flex items-center justify-center">
                                @if (isset($todayAttendanceStats['hadir_yesterday']) &&
                                        $todayAttendanceStats['hadir'] > $todayAttendanceStats['hadir_yesterday']
                                )
                                    <span
                                        class="text-xs text-green-600">+{{ $todayAttendanceStats['hadir'] - $todayAttendanceStats['hadir_yesterday'] }}</span>
                                @elseif(isset($todayAttendanceStats['hadir_yesterday']) &&
                                        $todayAttendanceStats['hadir'] < $todayAttendanceStats['hadir_yesterday']
                                )
                                    <span
                                        class="text-xs text-red-600">-{{ $todayAttendanceStats['hadir_yesterday'] - $todayAttendanceStats['hadir'] }}</span>
                                @else
                                    <span class="text-xs text-gray-500">—</span>
                                @endif
                            </div>
                        </div>

                        <!-- Terlambat -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Terlambat</p>
                            <p class="text-2xl font-bold text-amber-500">
                                {{ number_format($todayAttendanceStats['terlambat']) }}</p>
                            <div class="mt-1 flex items-center justify-center">
                                @if (isset($todayAttendanceStats['terlambat_yesterday']) &&
                                        $todayAttendanceStats['terlambat'] > $todayAttendanceStats['terlambat_yesterday']
                                )
                                    <span
                                        class="text-xs text-red-600">+{{ $todayAttendanceStats['terlambat'] - $todayAttendanceStats['terlambat_yesterday'] }}</span>
                                @elseif(isset($todayAttendanceStats['terlambat_yesterday']) &&
                                        $todayAttendanceStats['terlambat'] < $todayAttendanceStats['terlambat_yesterday']
                                )
                                    <span
                                        class="text-xs text-green-600">-{{ $todayAttendanceStats['terlambat_yesterday'] - $todayAttendanceStats['terlambat'] }}</span>
                                @else
                                    <span class="text-xs text-gray-500">—</span>
                                @endif
                            </div>
                        </div>

                        <!-- Total Kehadiran -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Total</p>
                            <p class="text-2xl font-bold text-indigo-600">
                                {{ number_format($todayAttendanceStats['datang']) }}</p>
                            <div class="mt-1 flex items-center justify-center">
                                @if (isset($todayAttendanceStats['datang_yesterday']) &&
                                        $todayAttendanceStats['datang'] > $todayAttendanceStats['datang_yesterday']
                                )
                                    <span
                                        class="text-xs text-green-600">+{{ $todayAttendanceStats['datang'] - $todayAttendanceStats['datang_yesterday'] }}</span>
                                @elseif(isset($todayAttendanceStats['datang_yesterday']) &&
                                        $todayAttendanceStats['datang'] < $todayAttendanceStats['datang_yesterday']
                                )
                                    <span
                                        class="text-xs text-red-600">-{{ $todayAttendanceStats['datang_yesterday'] - $todayAttendanceStats['datang'] }}</span>
                                @else
                                    <span class="text-xs text-gray-500">—</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Ringkasan Kepulangan -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-purple-100 bg-purple-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-purple-800">Ringkasan Kepulangan</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-4">
                        <!-- Pulang -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Pulang</p>
                            <p class="text-2xl font-bold text-purple-600">
                                {{ number_format($todayAttendanceStats['pulang']) }}</p>
                            <div class="mt-1 flex items-center justify-center">
                                <span class="text-xs text-gray-500">—</span>
                            </div>
                        </div>

                        <!-- Pulang Cepat -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Pulang Cepat</p>
                            <p class="text-2xl font-bold text-emerald-600">
                                {{ number_format($todayAttendanceStats['pulang_cepat']) }}</p>
                            <div class="mt-1 flex items-center justify-center">
                                <span class="text-xs text-gray-500">—</span>
                            </div>
                        </div>

                        <!-- Persentase -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">% Pulang</p>
                            @php
                                $pulangPercentage =
                                    $todayAttendanceStats['datang'] > 0
                                        ? round(
                                            ($todayAttendanceStats['pulang'] / $todayAttendanceStats['datang']) * 100,
                                        )
                                        : 0;
                            @endphp
                            <p class="text-2xl font-bold text-purple-800">{{ $pulangPercentage }}%</p>
                            <div class="mt-1 flex items-center justify-center">
                                <span class="text-xs text-gray-500">dari kehadiran</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Ringkasan Ketidakhadiran -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-red-100 bg-red-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-red-800">Ringkasan Ketidakhadiran</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-4">
                        <!-- Tanpa Keterangan -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Tanpa Ket.</p>
                            <p class="text-2xl font-bold text-red-600">
                                {{ number_format($todayAttendanceStats['tidak_hadir']) }}</p>
                            <div class="mt-1 flex items-center justify-center">
                                <span class="text-xs text-gray-500">—</span>
                            </div>
                        </div>

                        <!-- Izin -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Izin</p>
                            <p class="text-2xl font-bold text-sky-600">
                                {{ number_format($todayAttendanceStats['izin']) }}</p>
                            <div class="mt-1 flex items-center justify-center">
                                <span class="text-xs text-gray-500">—</span>
                            </div>
                        </div>

                        <!-- Sakit -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Sakit</p>
                            <p class="text-2xl font-bold text-pink-600">
                                {{ number_format($todayAttendanceStats['sakit']) }}</p>
                            <div class="mt-1 flex items-center justify-center">
                                <span class="text-xs text-gray-500">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kelas Berlangsung -->
    <div class="mt-8">
        <h2 class="flex flex-row items-center gap-2 font-inter text-sm font-medium text-gray-900 md:text-lg"><svg
                clip-rule="evenodd" class="h-6 w-6" fill-rule="evenodd" stroke-linejoin="round"
                stroke-miterlimit="2" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" id="fi_17179297">
                <path
                    d="m512 49.92c0-9.89-8.03-17.92-17.92-17.92h-476.16c-9.89 0-17.92 8.03-17.92 17.92v412.16c0 9.89 8.03 17.92 17.92 17.92h476.16c9.89 0 17.92-8.03 17.92-17.92z"
                    fill="#6a906c"></path>
                <path
                    d="m512 49.92v412.16c0 9.89-8.03 17.92-17.92 17.92h-476.16c-9.89 0-17.92-8.03-17.92-17.92v-412.16c0-9.89 8.03-17.92 17.92-17.92h476.16c9.89 0 17.92 8.03 17.92 17.92zm-4.167 0c0-7.591-6.162-13.753-13.753-13.753h-476.16c-7.591 0-13.753 6.162-13.753 13.753v412.16c0 7.591 6.162 13.753 13.753 13.753h476.16c7.591 0 13.753-6.162 13.753-13.753z"
                    fill="#366138"></path>
                <path d="m0 49.92c0-9.89 8.03-17.92 17.92-17.92h476.16c9.89 0 17.92 8.03 17.92 17.92v14.08h-512z"
                    fill="#c6dcab"></path>
                <path
                    d="m0 49.92c0-9.89 8.03-17.92 17.92-17.92h476.16c9.89 0 17.92 8.03 17.92 17.92v14.08h-512zm4.167 0v9.913h503.666v-9.913c0-7.591-6.162-13.753-13.753-13.753h-476.16c-7.591 0-13.753 6.162-13.753 13.753z"
                    fill="#366138"></path>
                <path
                    d="m224 101.12c0-2.826-2.294-5.12-5.12-5.12h-181.76c-2.826 0-5.12 2.294-5.12 5.12v117.76c0 2.826 2.294 5.12 5.12 5.12h181.76c2.826 0 5.12-2.294 5.12-5.12z"
                    fill="#c6dcab"></path>
                <path
                    d="m224 101.12v117.76c0 2.826-2.294 5.12-5.12 5.12h-181.76c-2.826 0-5.12-2.294-5.12-5.12v-117.76c0-2.826 2.294-5.12 5.12-5.12h181.76c2.826 0 5.12 2.294 5.12 5.12zm-4.167 0c0-.526-.427-.953-.953-.953h-181.76c-.526 0-.953.427-.953.953v117.76c0 .526.427.953.953.953h181.76c.526 0 .953-.427.953-.953z"
                    fill="#366138"></path>
                <path
                    d="m216 204.64c0-.353-.287-.64-.64-.64h-38.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h38.72c.353 0 .64-.287.64-.64z"
                    fill="#6a906c"></path>
                <path
                    d="m220.167 204.64v6.72l-.38 1.873-1.028 1.526-1.526 1.028-1.873.38h-38.72l-1.873-.38-1.526-1.028-1.028-1.526-.38-1.873v-6.72l.38-1.873 1.028-1.526 1.526-1.028 1.873-.38h38.72l1.873.38 1.526 1.028 1.028 1.526zm-4.167 0c0-.353-.287-.64-.64-.64h-38.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h38.72c.353 0 .64-.287.64-.64z"
                    fill="#366138"></path>
                <path
                    d="m480 263.68c0-4.239-3.441-7.68-7.68-7.68h-432.64c-4.239 0-7.68 3.441-7.68 7.68v176.64c0 4.239 3.441 7.68 7.68 7.68h432.64c4.239 0 7.68-3.441 7.68-7.68z"
                    fill="#c6dcab"></path>
                <path
                    d="m480 263.68v176.64c0 4.239-3.441 7.68-7.68 7.68h-432.64c-4.239 0-7.68-3.441-7.68-7.68v-176.64c0-4.239 3.441-7.68 7.68-7.68h432.64c4.239 0 7.68 3.441 7.68 7.68zm-4.167 0c0-1.939-1.574-3.513-3.513-3.513h-432.64c-1.939 0-3.513 1.574-3.513 3.513v176.64c0 1.939 1.574 3.513 3.513 3.513h432.64c1.939 0 3.513-1.574 3.513-3.513z"
                    fill="#366138"></path>
                <path
                    d="m139.935 364.853c29.627 4.289 52.065 24.244 52.065 48.183 0 27.063-128 27.063-128 0 0-23.939 22.438-43.894 52.065-48.183-16.255-5.082-28.065-20.266-28.065-38.186 0-22.077 17.923-40 40-40s40 17.923 40 40c0 17.92-11.81 33.104-28.065 38.186z"
                    fill="#6a906c"></path>
                <path
                    d="m139.935 364.853c29.627 4.289 52.065 24.244 52.065 48.183 0 27.063-128 27.063-128 0 0-23.939 22.438-43.894 52.065-48.183-16.255-5.082-28.065-20.266-28.065-38.186 0-22.077 17.923-40 40-40s40 17.923 40 40c0 17.92-11.81 33.104-28.065 38.186zm-1.243-3.977c14.562-4.553 25.141-18.155 25.141-34.209 0-19.777-16.056-35.834-35.833-35.834s-35.833 16.057-35.833 35.834c0 16.054 10.579 29.656 25.141 34.209 1.857.58 3.065 2.369 2.91 4.308-.155 1.94-1.631 3.514-3.556 3.792-27.365 3.962-48.495 21.949-48.495 44.06 0 1.608.901 2.984 2.27 4.27 1.985 1.866 4.882 3.448 8.411 4.847 11.913 4.723 30.537 7.014 49.152 7.014s37.239-2.291 49.152-7.014c3.529-1.399 6.426-2.981 8.411-4.847 1.369-1.286 2.27-2.662 2.27-4.27 0-22.111-21.13-40.098-48.495-44.06-1.925-.278-3.401-1.852-3.556-3.792-.155-1.939 1.053-3.728 2.91-4.308z"
                    fill="#366138"></path>
                <path
                    d="m448 285.92c0-3.267-2.653-5.92-5.92-5.92h-212.16c-3.267 0-5.92 2.653-5.92 5.92v136.16c0 3.267 2.653 5.92 5.92 5.92h212.16c3.267 0 5.92-2.653 5.92-5.92z"
                    fill="#6a906c"></path>
                <path
                    d="m448 285.92v136.16c0 3.267-2.653 5.92-5.92 5.92h-212.16c-3.267 0-5.92-2.653-5.92-5.92v-136.16c0-3.267 2.653-5.92 5.92-5.92h212.16c3.267 0 5.92 2.653 5.92 5.92zm-4.167 0c0-.968-.785-1.753-1.753-1.753h-212.16c-.968 0-1.753.785-1.753 1.753v136.16c0 .968.785 1.753 1.753 1.753h212.16c.968 0 1.753-.785 1.753-1.753z"
                    fill="#366138"></path>
                <path
                    d="m440 290.64c0-1.457-1.183-2.64-2.64-2.64h-202.72c-1.457 0-2.64 1.183-2.64 2.64v126.72c0 1.457 1.183 2.64 2.64 2.64h202.72c1.457 0 2.64-1.183 2.64-2.64z"
                    fill="#c6dcab"></path>
                <path
                    d="m440 290.64v126.72c0 1.457-1.183 2.64-2.64 2.64h-202.72c-1.457 0-2.64-1.183-2.64-2.64v-126.72c0-1.457 1.183-2.64 2.64-2.64h202.72c1.457 0 2.64 1.183 2.64 2.64zm-203.833 1.527v123.666h199.666v-123.666z"
                    fill="#366138"></path>
                <path
                    d="m104.521 161.266c20.429 4.224 35.479 18.531 35.479 35.511 0 20.297-96 20.297-96 0 0-16.98 15.05-31.287 35.479-35.511-10.313-4.748-17.479-15.177-17.479-27.266 0-16.557 13.443-30 30-30s30 13.443 30 30c0 12.089-7.166 22.518-17.479 27.266z"
                    fill="#6a906c"></path>
                <path
                    d="m104.521 161.266c20.429 4.224 35.479 18.531 35.479 35.511 0 20.297-96 20.297-96 0 0-16.98 15.05-31.287 35.479-35.511-10.313-4.748-17.479-15.177-17.479-27.266 0-16.557 13.443-30 30-30s30 13.443 30 30c0 12.089-7.166 22.518-17.479 27.266zm-1.742-3.785c8.881-4.089 15.054-13.069 15.054-23.481 0-14.258-11.575-25.833-25.833-25.833s-25.833 11.575-25.833 25.833c0 10.412 6.173 19.392 15.054 23.481 1.635.753 2.602 2.47 2.397 4.258-.204 1.788-1.533 3.243-3.296 3.607-18.299 3.784-32.155 16.221-32.155 31.431 0 .932.581 1.698 1.374 2.444 1.409 1.324 3.474 2.432 5.979 3.425 8.841 3.505 22.665 5.187 36.48 5.187s27.639-1.682 36.48-5.187c2.505-.993 4.57-2.101 5.979-3.425.793-.746 1.374-1.512 1.374-2.444 0-15.21-13.856-27.647-32.155-31.431-1.763-.364-3.092-1.819-3.296-3.607-.205-1.788.762-3.505 2.397-4.258z"
                    fill="#366138"></path>
                <path
                    d="m480 101.12c0-2.826-2.294-5.12-5.12-5.12h-181.76c-2.826 0-5.12 2.294-5.12 5.12v117.76c0 2.826 2.294 5.12 5.12 5.12h181.76c2.826 0 5.12-2.294 5.12-5.12z"
                    fill="#c6dcab"></path>
                <path
                    d="m480 101.12v117.76c0 2.826-2.294 5.12-5.12 5.12h-181.76c-2.826 0-5.12-2.294-5.12-5.12v-117.76c0-2.826 2.294-5.12 5.12-5.12h181.76c2.826 0 5.12 2.294 5.12 5.12zm-4.167 0c0-.526-.427-.953-.953-.953h-181.76c-.526 0-.953.427-.953.953v117.76c0 .526.427.953.953.953h181.76c.526 0 .953-.427.953-.953z"
                    fill="#366138"></path>
                <path
                    d="m472 204.64c0-.353-.287-.64-.64-.64h-38.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h38.72c.353 0 .64-.287.64-.64z"
                    fill="#6a906c"></path>
                <path
                    d="m476.167 204.64v6.72l-.38 1.873-1.028 1.526-1.526 1.028-1.873.38h-38.72l-1.873-.38-1.526-1.028-1.028-1.526-.38-1.873v-6.72l.38-1.873 1.028-1.526 1.526-1.028 1.873-.38h38.72l1.873.38 1.526 1.028 1.028 1.526zm-4.167 0c0-.353-.287-.64-.64-.64h-38.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h38.72c.353 0 .64-.287.64-.64z"
                    fill="#366138"></path>
                <path
                    d="m472 264.64c0-.353-.287-.64-.64-.64h-38.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h38.72c.353 0 .64-.287.64-.64z"
                    fill="#6a906c"></path>
                <path
                    d="m476.167 264.64v6.72l-.38 1.873-1.028 1.526-1.526 1.028-1.873.38h-38.72l-1.873-.38-1.526-1.028-1.028-1.526-.38-1.873v-6.72l.38-1.873 1.028-1.526 1.526-1.028 1.873-.38h38.72l1.873.38 1.526 1.028 1.028 1.526zm-4.167 0c0-.353-.287-.64-.64-.64h-38.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h38.72c.353 0 .64-.287.64-.64z"
                    fill="#366138"></path>
                <path
                    d="m360.521 161.266c20.429 4.224 35.479 18.531 35.479 35.511 0 20.297-96 20.297-96 0 0-16.98 15.05-31.287 35.479-35.511-10.313-4.748-17.479-15.177-17.479-27.266 0-16.557 13.443-30 30-30s30 13.443 30 30c0 12.089-7.166 22.518-17.479 27.266z"
                    fill="#6a906c"></path>
                <path
                    d="m360.521 161.266c20.429 4.224 35.479 18.531 35.479 35.511 0 20.297-96 20.297-96 0 0-16.98 15.05-31.287 35.479-35.511-10.313-4.748-17.479-15.177-17.479-27.266 0-16.557 13.443-30 30-30s30 13.443 30 30c0 12.089-7.166 22.518-17.479 27.266zm-1.742-3.785c8.881-4.089 15.054-13.069 15.054-23.481 0-14.258-11.575-25.833-25.833-25.833s-25.833 11.575-25.833 25.833c0 10.412 6.173 19.392 15.054 23.481 1.635.753 2.602 2.47 2.397 4.258-.204 1.788-1.533 3.243-3.296 3.607-18.299 3.784-32.155 16.221-32.155 31.431 0 .932.581 1.698 1.374 2.444 1.409 1.324 3.474 2.432 5.979 3.425 8.841 3.505 22.665 5.187 36.48 5.187s27.639-1.682 36.48-5.187c2.505-.993 4.57-2.101 5.979-3.425.793-.746 1.374-1.512 1.374-2.444 0-15.21-13.856-27.647-32.155-31.431-1.763-.364-3.092-1.819-3.296-3.607-.205-1.788.762-3.505 2.397-4.258z"
                    fill="#366138"></path>
                <circle cx="404" cy="48" fill="#6a906c" r="12"></circle>
                <path
                    d="m404 36c6.623 0 12 5.377 12 12s-5.377 12-12 12-12-5.377-12-12 5.377-12 12-12zm0 4.167c-4.323 0-7.833 3.51-7.833 7.833s3.51 7.833 7.833 7.833 7.833-3.51 7.833-7.833-3.51-7.833-7.833-7.833z"
                    fill="#366138"></path>
                <circle cx="436" cy="48" fill="#6a906c" r="12"></circle>
                <path
                    d="m436 36c6.623 0 12 5.377 12 12s-5.377 12-12 12-12-5.377-12-12 5.377-12 12-12zm0 4.167c-4.323 0-7.833 3.51-7.833 7.833s3.51 7.833 7.833 7.833 7.833-3.51 7.833-7.833-3.51-7.833-7.833-7.833z"
                    fill="#366138"></path>
                <circle cx="468" cy="48" fill="#6a906c" r="12"></circle>
                <path
                    d="m468 36c6.623 0 12 5.377 12 12s-5.377 12-12 12-12-5.377-12-12 5.377-12 12-12zm0 4.167c-4.323 0-7.833 3.51-7.833 7.833s3.51 7.833 7.833 7.833 7.833-3.51 7.833-7.833-3.51-7.833-7.833-7.833z"
                    fill="#366138"></path>
                <path
                    d="m336 329.64c0-.353-.287-.64-.64-.64h-78.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h78.72c.353 0 .64-.287.64-.64z"
                    fill="#6a906c"></path>
                <path
                    d="m340.167 329.64v6.72l-.38 1.873-1.028 1.526-1.526 1.028-1.873.38h-78.72l-1.873-.38-1.526-1.028-1.028-1.526-.38-1.873v-6.72l.38-1.873 1.028-1.526 1.526-1.028 1.873-.38h78.72l1.873.38 1.526 1.028 1.028 1.526zm-4.167 0c0-.353-.287-.64-.64-.64h-78.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h78.72c.353 0 .64-.287.64-.64z"
                    fill="#366138"></path>
                <path
                    d="m336 349.64c0-.353-.287-.64-.64-.64h-78.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h78.72c.353 0 .64-.287.64-.64z"
                    fill="#6a906c"></path>
                <path
                    d="m340.167 349.64v6.72l-.38 1.873-1.028 1.526-1.526 1.028-1.873.38h-78.72l-1.873-.38-1.526-1.028-1.028-1.526-.38-1.873v-6.72l.38-1.873 1.028-1.526 1.526-1.028 1.873-.38h78.72l1.873.38 1.526 1.028 1.028 1.526zm-4.167 0c0-.353-.287-.64-.64-.64h-78.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h78.72c.353 0 .64-.287.64-.64z"
                    fill="#366138"></path>
                <path
                    d="m336 371.64c0-.353-.287-.64-.64-.64h-78.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h78.72c.353 0 .64-.287.64-.64z"
                    fill="#6a906c"></path>
                <path
                    d="m340.167 371.64v6.72l-.38 1.873-1.028 1.526-1.526 1.028-1.873.38h-78.72l-1.873-.38-1.526-1.028-1.028-1.526-.38-1.873v-6.72l.38-1.873 1.028-1.526 1.526-1.028 1.873-.38h78.72l1.873.38 1.526 1.028 1.028 1.526zm-4.167 0c0-.353-.287-.64-.64-.64h-78.72c-.353 0-.64.287-.64.64v6.72c0 .353.287.64.64.64h78.72c.353 0 .64-.287.64-.64z"
                    fill="#366138"></path>
                <circle cx="384" cy="352" fill="#6a906c" r="32"></circle>
                <path
                    d="m384 320c17.661 0 32 14.339 32 32s-14.339 32-32 32-32-14.339-32-32 14.339-32 32-32zm0 4.167c-15.362 0-27.833 12.471-27.833 27.833s12.471 27.833 27.833 27.833 27.833-12.471 27.833-27.833-12.471-27.833-27.833-27.833z"
                    fill="#366138"></path>
            </svg>Sesi Kelas Sedang Berlangsung</h2>
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
    <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2">
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
