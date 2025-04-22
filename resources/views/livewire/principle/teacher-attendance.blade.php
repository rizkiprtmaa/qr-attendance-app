<?php

use Livewire\Volt\Component;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Teacher;
use Livewire\Attributes\On;
use Carbon\Carbon;

new class extends Component {
    public $selectedMonth;
    public $selectedYear;
    public $search = '';

    // Untuk filter hanya guru (tanpa karyawan)
    public $showOnlyTeachers = true;

    public function mount()
    {
        // Default ke bulan dan tahun saat ini
        $today = now()->timezone('Asia/Jakarta');
        $this->selectedMonth = $today->format('m');
        $this->selectedYear = $today->format('Y');
    }

    // Mendapatkan list tahun untuk filter (3 tahun ke belakang sampai tahun sekarang)
    public function getYearsProperty()
    {
        $currentYear = now()->year;
        return range($currentYear - 2, $currentYear);
    }

    // Mendapatkan list bulan untuk filter
    public function getMonthsProperty()
    {
        return [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];
    }

    // Mendapatkan data kehadiran guru
    public function getTeacherAttendancesProperty()
    {
        // Menentukan rentang tanggal bulan yang dipilih
        $startDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        // Dapatkan semua user dengan role guru
        $teacherUserIds = User::role('teacher')->pluck('id');

        // Jika showOnlyTeachers aktif, hilangkan karyawan dari list
        if ($this->showOnlyTeachers) {
            $karyawanIds = Teacher::where('is_karyawan', true)->pluck('user_id');
            $teacherUserIds = $teacherUserIds->diff($karyawanIds);
        }

        // Filter berdasarkan pencarian jika ada
        if (!empty($this->search)) {
            $searchTeacherIds = User::whereIn('id', $teacherUserIds)
                ->where('name', 'like', '%' . $this->search . '%')
                ->pluck('id');
            $teacherUserIds = $searchTeacherIds;
        }

        // Ambil data kehadiran untuk rentang bulan terpilih
        $attendances = Attendance::whereIn('user_id', $teacherUserIds)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->with(['user'])
            ->orderBy('attendance_date')
            ->get()
            ->groupBy('user_id');

        // Membuat struktur data untuk summary kehadiran per guru
        $teacherSummary = [];

        foreach ($attendances as $userId => $userAttendances) {
            $teacher = $userAttendances->first()->user;

            // Inisialisasi data ringkasan
            $summary = [
                'user_id' => $userId,
                'name' => $teacher->name,
                'total_days' => $this->getWorkingDaysInMonth(),
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'sick' => 0,
                'permission' => 0,
                'incomplete' => 0,
                'daily_records' => [],
            ];

            // Group data per tanggal
            $groupedByDate = $userAttendances->groupBy('attendance_date');

            // Proses data per tanggal
            foreach ($groupedByDate as $date => $dateAttendances) {
                $datang = $dateAttendances->firstWhere('type', 'datang');
                $pulang = $dateAttendances->firstWhere('type', 'pulang');

                $dailyStatus = [
                    'date' => Carbon::parse($date)->format('Y-m-d'),
                    'check_in' => $datang ? $datang->check_in_time : null,
                    'check_out' => $pulang ? $pulang->check_in_time : null,
                    'status' => 'tidak_hadir',
                ];

                if ($datang) {
                    if ($datang->status == 'hadir') {
                        $summary['present']++;
                        $dailyStatus['status'] = 'hadir';
                    } elseif ($datang->status == 'terlambat') {
                        $summary['late']++;
                        $dailyStatus['status'] = 'terlambat';
                    } elseif ($datang->status == 'izin') {
                        $summary['permission']++;
                        $dailyStatus['status'] = 'izin';
                    } elseif ($datang->status == 'sakit') {
                        $summary['sick']++;
                        $dailyStatus['status'] = 'sakit';
                    } elseif ($datang->status == 'tidak_hadir') {
                        $summary['absent']++;
                        $dailyStatus['status'] = 'tidak_hadir';
                    }

                    // Cek kelengkapan presensi
                    if (($datang->status == 'hadir' || $datang->status == 'terlambat') && !$pulang) {
                        $summary['incomplete']++;
                        $dailyStatus['status'] .= '_incomplete';
                    }
                }

                $summary['daily_records'][$date] = $dailyStatus;
            }

            $teacherSummary[$userId] = $summary;
        }

        return $teacherSummary;
    }

    // Mendapatkan jumlah hari kerja dalam sebulan (Senin-Jumat)
    private function getWorkingDaysInMonth()
    {
        $startDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        $workingDays = 0;
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Mengecualikan hari Sabtu dan Minggu
            if ($date->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    // Mendapatkan array tanggal-tanggal di bulan terpilih
    public function getDaysInMonthProperty()
    {
        $startDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        $days = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Mengecualikan hari Sabtu dan Minggu
            if ($date->dayOfWeek !== Carbon::SUNDAY) {
                $days[] = [
                    'date' => $date->format('Y-m-d'),
                    'display' => $date->format('d'),
                    'day' => $date->format('D'),
                ];
            }
        }

        return $days;
    }

    public function render(): mixed
    {
        return view('livewire.principle.teacher-attendance', [
            'teacherAttendances' => $this->teacherAttendances,
            'months' => $this->months,
            'years' => $this->years,
            'daysInMonth' => $this->daysInMonth,
        ]);
    }
}; ?>

<div class="mt-8">
    <div class="container mx-auto overflow-hidden">
        <!-- Header & Filters -->
        <div class="mb-6">
            <div class="rounded-lg bg-white p-4 shadow">
                <div
                    class="flex flex-col space-y-4 md:flex-row md:items-end md:justify-between md:space-x-4 md:space-y-0">
                    <div class="grid grid-cols-2 gap-4 md:flex md:items-end md:space-x-4">
                        {{-- Filter Bulan --}}
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Bulan</label>
                            <flux:select wire:model.live="selectedMonth" class="w-full">
                                @foreach ($months as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Filter Tahun --}}
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tahun</label>
                            <flux:select wire:model.live="selectedYear" class="w-full">
                                @foreach ($years as $year)
                                    <flux:select.option value="{{ $year }}">{{ $year }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>

                    <div class="flex flex-col space-y-4 md:flex-row md:items-end md:space-x-4 md:space-y-0">
                        {{-- Tampilkan hanya guru --}}
                        <div class="flex items-center">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model.live="showOnlyTeachers"
                                    class="form-checkbox h-5 w-5 text-blue-600">
                                <span class="ml-2 text-sm text-gray-700">Hanya Guru</span>
                            </label>
                        </div>

                        {{-- Pencarian --}}
                        <div>
                            <flux:input wire:model.live.debounce.300ms="search" kbd="⌘K" icon="magnifying-glass"
                                placeholder="Cari nama guru..." class="w-full md:w-60" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Kehadiran -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-6">
            <!-- Card: Total Guru -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-blue-100 bg-blue-50 px-4 py-2">
                    <h3 class="text-xs font-semibold text-blue-800 md:text-sm">Total Guru</h3>
                </div>
                <div class="p-3 md:p-4">
                    <p class="text-xl font-bold text-blue-600 md:text-3xl">{{ count($teacherAttendances) }}</p>
                </div>
            </div>

            <!-- Card: Hadir Penuh -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-green-100 bg-green-50 px-4 py-2">
                    <h3 class="text-xs font-semibold text-green-800 md:text-sm">Kehadiran Penuh</h3>
                </div>
                <div class="p-3 md:p-4">
                    @php
                        $fullAttendance = count(
                            array_filter($teacherAttendances, function ($teacher) {
                                return $teacher['present'] + $teacher['late'] >= $teacher['total_days'];
                            }),
                        );
                    @endphp
                    <p class="text-xl font-bold text-green-600 md:text-3xl">{{ $fullAttendance }}</p>
                </div>
            </div>

            <!-- Card: Terlambat -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-yellow-100 bg-yellow-50 px-4 py-2">
                    <h3 class="text-xs font-semibold text-yellow-800 md:text-sm">Keterlambatan</h3>
                </div>
                <div class="p-3 md:p-4">
                    @php
                        $lateTeachers = count(
                            array_filter($teacherAttendances, function ($teacher) {
                                return $teacher['late'] > 0;
                            }),
                        );
                    @endphp
                    <p class="text-xl font-bold text-yellow-600 md:text-3xl">{{ $lateTeachers }}</p>
                </div>
            </div>

            <!-- Card: Tidak Hadir -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-red-100 bg-red-50 px-4 py-2">
                    <h3 class="text-xs font-semibold text-red-800 md:text-sm">Ketidakhadiran</h3>
                </div>
                <div class="p-3 md:p-4">
                    @php
                        $absentTeachers = count(
                            array_filter($teacherAttendances, function ($teacher) {
                                return $teacher['absent'] > 0;
                            }),
                        );
                    @endphp
                    <p class="text-xl font-bold text-red-600 md:text-3xl">{{ $absentTeachers }}</p>
                </div>
            </div>
        </div>

        <!-- Tabel Kehadiran Guru (Desktop Only) -->
        <div class="hidden overflow-hidden rounded-lg bg-white shadow md:block">
            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Rekap Kehadiran Guru</h3>

            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left font-inter text-sm text-gray-500">
                    <thead class="bg-blue-500 text-xs uppercase text-white">
                        <tr>
                            <th scope="col" class="sticky left-0 bg-blue-500 px-6 py-3">Nama Guru</th>
                            <th scope="col" class="px-6 py-3 text-center">Hadir</th>
                            <th scope="col" class="px-6 py-3 text-center">Terlambat</th>
                            <th scope="col" class="px-6 py-3 text-center">Izin</th>
                            <th scope="col" class="px-6 py-3 text-center">Sakit</th>
                            <th scope="col" class="px-6 py-3 text-center">Tidak Hadir</th>
                            <th scope="col" class="px-6 py-3 text-center">Tidak Lengkap</th>
                            <th scope="col" class="px-6 py-3 text-center">Persentase</th>
                            <th scope="col" class="px-6 py-3 text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($teacherAttendances as $teacherData)
                            <tr class="hover:bg-gray-50">
                                <td class="sticky left-0 bg-white px-6 py-4 font-medium text-gray-900 hover:bg-gray-50">
                                    {{ $teacherData['name'] }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-green-600">{{ $teacherData['present'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-yellow-600">{{ $teacherData['late'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-blue-600">{{ $teacherData['permission'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-pink-600">{{ $teacherData['sick'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-red-600">{{ $teacherData['absent'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-orange-600">{{ $teacherData['incomplete'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $workingDays = $teacherData['total_days'];
                                        $presentDays = $teacherData['present'] + $teacherData['late'];
                                        $percentage = $workingDays > 0 ? round(($presentDays / $workingDays) * 100) : 0;
                                    @endphp
                                    <div class="flex items-center justify-center">
                                        <span
                                            class="{{ $percentage >= 90 ? 'text-green-600' : ($percentage >= 70 ? 'text-yellow-600' : 'text-red-600') }} mr-2 font-medium">
                                            {{ $percentage }}%
                                        </span>
                                        <div class="h-2 w-16 overflow-hidden rounded-full bg-gray-200">
                                            <div class="{{ $percentage >= 90 ? 'bg-green-500' : ($percentage >= 70 ? 'bg-yellow-500' : 'bg-red-500') }} h-full rounded-full"
                                                style="width: {{ $percentage }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('user.detail', $teacherData['user_id']) }}" wire:navigate
                                        class="rounded-md bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p class="mt-2 text-sm font-medium">Tidak ada data kehadiran guru</p>
                                        <p class="mt-1 text-xs text-gray-400">Silakan ubah filter atau bulan untuk
                                            melihat data lainnya</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card View for Mobile -->
        <div class="block md:hidden">
            <h3 class="mb-3 text-lg font-medium text-gray-900">Rekap Kehadiran Guru</h3>

            @forelse($teacherAttendances as $teacherData)
                @php
                    $workingDays = $teacherData['total_days'];
                    $presentDays = $teacherData['present'] + $teacherData['late'];
                    $percentage = $workingDays > 0 ? round(($presentDays / $workingDays) * 100) : 0;
                    $bgColorClass =
                        $percentage >= 90 ? 'bg-green-500' : ($percentage >= 70 ? 'bg-yellow-500' : 'bg-red-500');
                    $textColorClass =
                        $percentage >= 90 ? 'text-green-700' : ($percentage >= 70 ? 'text-yellow-700' : 'text-red-700');
                @endphp

                <div class="mb-4 overflow-hidden rounded-lg bg-white shadow">
                    <!-- Card Header -->
                    <div class="relative border-b border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-start justify-between">
                            <h4 class="font-medium text-gray-900">{{ $teacherData['name'] }}</h4>
                            <div class="ml-2 flex-shrink-0">
                                <a href="{{ route('user.detail', $teacherData['user_id']) }}" wire:navigate
                                    class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                    Detail
                                </a>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-2">
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                        <div class="{{ $bgColorClass }} h-full rounded-full"
                                            style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                                <span class="{{ $textColorClass }} ml-2 text-xs font-medium">
                                    {{ $percentage }}%
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                Hadir {{ $presentDays }} dari {{ $workingDays }} hari kerja
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4">
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-md bg-green-50 p-2">
                                <span class="block text-xs text-gray-500">Hadir</span>
                                <span class="text-lg font-bold text-green-600">{{ $teacherData['present'] }}</span>
                            </div>
                            <div class="rounded-md bg-yellow-50 p-2">
                                <span class="block text-xs text-gray-500">Terlambat</span>
                                <span class="text-lg font-bold text-yellow-600">{{ $teacherData['late'] }}</span>
                            </div>
                            <div class="rounded-md bg-blue-50 p-2">
                                <span class="block text-xs text-gray-500">Izin</span>
                                <span class="text-lg font-bold text-blue-600">{{ $teacherData['permission'] }}</span>
                            </div>
                            <div class="rounded-md bg-pink-50 p-2">
                                <span class="block text-xs text-gray-500">Sakit</span>
                                <span class="text-lg font-bold text-pink-600">{{ $teacherData['sick'] }}</span>
                            </div>
                            <div class="rounded-md bg-red-50 p-2">
                                <span class="block text-xs text-gray-500">Tidak Hadir</span>
                                <span class="text-lg font-bold text-red-600">{{ $teacherData['absent'] }}</span>
                            </div>
                            <div class="rounded-md bg-orange-50 p-2">
                                <span class="block text-xs text-gray-500">Tidak Lengkap</span>
                                <span
                                    class="text-lg font-bold text-orange-600">{{ $teacherData['incomplete'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-lg bg-white p-8 text-center shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="mt-2 text-sm font-medium text-gray-900">Tidak ada data kehadiran guru</p>
                    <p class="mt-1 text-xs text-gray-500">Silakan ubah filter atau bulan untuk melihat data lainnya</p>
                </div>
            @endforelse
        </div>


    </div>
</div>
