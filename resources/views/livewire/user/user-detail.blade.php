<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $user;
    public $userId;
    public $name;
    public $email;
    public $nisn;
    public $nuptk;
    public $phone;
    public $class;
    public $major;

    public $total_attendances;
    public $attendance_percentage;
    public $avg_check_in;
    public $avg_check_out;

    public $filterMonth;
    public $filterYear;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;

        if ($user->teacher) {
            $this->nuptk = $user->teacher->nuptk;
            $this->phone = $user->teacher->phone_number;
        } else {
            $this->nisn = $user->student->nisn;
            $this->phone = $user->student->parent_number;
            $this->class = $user->student->classes->name;
            $this->major = $user->student->classes->major->name;
        }

        // Set default filter to current month and year
        $this->filterMonth = now()->month;
        $this->filterYear = now()->year;

        $this->calculateStatistics();
    }

    public function calculateStatistics()
    {
        // Base query for all user attendances
        $query = Attendance::where('user_id', $this->userId);

        // Apply date filters if they exist
        if ($this->filterMonth && $this->filterYear) {
            $query->whereMonth('attendance_date', $this->filterMonth)->whereYear('attendance_date', $this->filterYear);
        }

        $allAttendances = $query->get();

        // Calculate total attendances where user was present
        $this->total_attendances = $allAttendances
            ->where('type', 'datang')
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();

        // Calculate total working days in the selected month
        $workingDays = $this->getWorkingDaysCount($this->filterYear, $this->filterMonth);

        // Calculate attendance percentage
        $this->attendance_percentage = $workingDays > 0 ? round(($this->total_attendances / $workingDays) * 100, 1) : 0;

        // Calculate average check-in time
        $checkInAttendances = $allAttendances->where('type', 'datang')->whereNotNull('check_in_time');
        $avg_check_in_seconds = $checkInAttendances->isNotEmpty()
            ? $checkInAttendances->avg(function ($attendance) {
                return Carbon::parse($attendance->check_in_time)->timezone('Asia/Jakarta')->secondsSinceMidnight();
            })
            : null;

        // Calculate average check-out time (masih menggunakan check_in_time untuk data "pulang")
        $checkOutAttendances = $allAttendances->where('type', 'pulang')->whereNotNull('check_in_time');
        $avg_check_out_seconds = $checkOutAttendances->isNotEmpty()
            ? $checkOutAttendances->avg(function ($attendance) {
                return Carbon::parse($attendance->check_in_time)->timezone('Asia/Jakarta')->secondsSinceMidnight();
            })
            : null;

        // Format untuk tampilan jam:menit
        $this->avg_check_in = $avg_check_in_seconds ? sprintf('%02d:%02d', floor($avg_check_in_seconds / 3600), floor(($avg_check_in_seconds % 3600) / 60)) : '-';

        $this->avg_check_out = $avg_check_out_seconds ? sprintf('%02d:%02d', floor($avg_check_out_seconds / 3600), floor(($avg_check_out_seconds % 3600) / 60)) : '-';
    }

    public function getWorkingDaysCount($year, $month)
    {
        $date = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $date->daysInMonth;

        $workingDays = 0;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::createFromDate($year, $month, $day);
            // Skip weekends (assuming weekends are Saturday and Sunday)
            if ($currentDate->dayOfWeek !== Carbon::SATURDAY && $currentDate->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    public function updatedFilterMonth()
    {
        $this->resetPage();
        $this->calculateStatistics();
    }

    public function updatedFilterYear()
    {
        $this->resetPage();
        $this->calculateStatistics();
    }

    public function downloadQrCode($userId)
    {
        $user = User::findOrFail($userId);

        if (!$user->qr_code_path) {
            return redirect()->back()->with('error', 'QR Code tidak tersedia');
        }

        return response()->download(storage_path('app/public/' . $user->qr_code_path));
    }

    public function downloadAttendanceReport()
    {
        // This would be implemented in a controller that handles the PDF generation
        return redirect()->route('attendance.report.download', [
            'user' => $this->userId,
            'month' => $this->filterMonth,
            'year' => $this->filterYear,
        ]);
    }

    public function render(): mixed
    {
        $query = Attendance::where('user_id', $this->userId);

        // Apply date filters
        if ($this->filterMonth && $this->filterYear) {
            $query->whereMonth('attendance_date', $this->filterMonth)->whereYear('attendance_date', $this->filterYear);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->orderBy('check_in_time', 'desc')->paginate(6);

        return view('livewire.user.user-detail', [
            'attendances' => $attendances,
            'months' => $this->getMonthsOptions(),
            'years' => $this->getYearsOptions(),
        ]);
    }

    private function getMonthsOptions()
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    private function getYearsOptions()
    {
        $currentYear = date('Y');
        $years = [];
        for ($i = $currentYear - 2; $i <= $currentYear; $i++) {
            $years[$i] = $i;
        }
        return $years;
    }
}; ?>

<div class="mx-auto mt-12 max-w-6xl md:mt-0">
    <!-- Main Header Section -->
    <div class="flex flex-col items-start justify-between space-y-4 pb-5 md:flex-row md:items-center md:space-y-0">
        <div class="hidden md:block">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
                {{ $user->student ? 'Detail Siswa' : 'Detail Guru' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Informasi lengkap dan riwayat presensi
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('users') }}"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <svg class="-ml-0.5 mr-1.5 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Kembali
            </a>
            <a href="{{ route('user.edit', $user->id) }}"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <svg class="-ml-0.5 mr-1.5 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                Edit
            </a>
            <button type="button" wire:click="downloadQrCode({{ $user->id }})"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <svg class="-ml-0.5 mr-1.5 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                </svg>
                QR Code
            </button>

        </div>
    </div>

    <!-- Profile Card -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center">
                <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-full bg-indigo-100">
                    <div class="flex h-full w-full items-center justify-center text-xl font-bold text-indigo-600">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">{{ $user->name }}</h3>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        {{ $user->teacher ? 'NUPTK' : 'NISN' }}
                    </dt>
                    <dd class="mt-1 flex items-center text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        <span id="identifier">{{ $user->teacher ? $user->teacher->nuptk : $user->student->nisn }}</span>
                        <button
                            onclick="navigator.clipboard.writeText(document.getElementById('identifier').textContent); this.querySelector('.tooltip').classList.remove('hidden'); setTimeout(() => this.querySelector('.tooltip').classList.add('hidden'), 2000)"
                            class="ml-2 cursor-pointer rounded-md bg-gray-100 p-1 hover:bg-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-500">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0 .621.504 1.125 1.125 1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                            </svg>
                            <span
                                class="tooltip absolute -mt-8 ml-1 hidden rounded-md bg-gray-800 px-2 py-1 text-xs text-white">Disalin!</span>
                        </button>
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        {{ $user->teacher ? 'Nomor Telepon' : 'Nomor Orang Tua' }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                        {{ $phone }}
                    </dd>
                </div>
                @if ($user->student)
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Kelas</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $class }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Jurusan</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $major }}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Stats & Filter Section -->
    <div class="mt-8">
        <div class="mb-6 flex flex-col justify-between space-y-4 md:flex-row md:items-center md:space-y-0">
            <h2 class="text-xl font-semibold text-gray-900">Statistik Kehadiran</h2>

            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center space-x-2">
                    <label for="month" class="text-sm font-medium text-gray-700">Bulan:</label>
                    <select id="month" wire:model.live="filterMonth"
                        class="rounded-md border-gray-300 py-1.5 pl-3 pr-10 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                        @foreach ($months as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <label for="year" class="text-sm font-medium text-gray-700">Tahun:</label>
                    <select id="year" wire:model.live="filterYear"
                        class="rounded-md border-gray-300 py-1.5 pl-3 pr-10 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                        @foreach ($years as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Stat Card - Total Kehadiran -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-500">Total Kehadiran</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $total_attendances }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat Card - Persentase Kehadiran -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-500">Persentase Kehadiran</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $attendance_percentage }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat Card - Rerata Waktu Datang -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-500">Rerata Waktu Datang</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">
                                {{ $avg_check_in ? $avg_check_in : '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat Card - Rerata Waktu Pulang -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-500">Rerata Waktu Pulang</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $avg_check_out }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance History Section -->
    <div class="mt-8">
        <h2 class="mb-6 text-xl font-semibold text-gray-900">Riwayat Presensi</h2>

        @php
            $groupedAttendances = $attendances->groupBy('attendance_date');
        @endphp

        @if ($groupedAttendances->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($groupedAttendances as $date => $dailyAttendances)
                    <div class="overflow-hidden rounded-lg bg-white shadow"
                        wire:key="attendance-{{ $date }}">
                        <div class="border-b border-gray-200 bg-gray-50 px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-medium text-gray-900">
                                    {{ Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                </h3>

                                @php
                                    $status = $dailyAttendances->first()->status;

                                    $statusClasses = [
                                        'hadir' => 'bg-green-100 text-green-800',
                                        'terlambat' => 'bg-yellow-100 text-yellow-800',
                                        'izin' => 'bg-blue-100 text-blue-800',
                                        'sakit' => 'bg-purple-100 text-purple-800',
                                        'tidak_hadir' => 'bg-red-100 text-red-800',
                                        'pulang_cepat' => 'bg-gray-100 text-gray-800',
                                    ];

                                    $statusLabels = [
                                        'hadir' => 'Tepat Waktu',
                                        'terlambat' => 'Terlambat',
                                        'izin' => 'Izin',
                                        'sakit' => 'Sakit',
                                        'tidak_hadir' => 'Tidak Hadir',
                                        'pulang_cepat' => 'Pulang Cepat',
                                    ];

                                    $statusClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
                                    $statusLabel = $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status));
                                @endphp

                                <span
                                    class="{{ $statusClass }} inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6">
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-6">
                                <div class="col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Datang</dt>
                                    <dd class="mt-1 flex items-center text-sm text-gray-900">
                                        @php
                                            $datang = $dailyAttendances->firstWhere('type', 'datang');
                                        @endphp

                                        <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>

                                        @if ($datang && $datang->check_in_time)
                                            {{ Carbon::parse($datang->check_in_time)->timezone('Asia/Jakarta')->format('H:i') }}
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Pulang</dt>
                                    <dd class="mt-1 flex items-center text-sm text-gray-900">
                                        @php
                                            $pulang = $dailyAttendances->firstWhere('type', 'pulang');
                                        @endphp

                                        <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>

                                        @if ($pulang)
                                            {{ Carbon::parse($pulang->check_in_time)->format('H:i') }}
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </dd>
                                </div>

                                <div class="col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Lokasi</dt>
                                    <dd class="mt-1 flex items-center text-sm text-gray-900">
                                        <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                        </svg>

                                        @if ($datang && $datang->location)
                                            {{ $datang->location }}
                                        @else
                                            <span class="text-gray-500">Tidak tersedia</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $attendances->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada data</h3>
                <p class="mt-1 text-sm text-gray-500">Tidak ada data presensi untuk periode yang dipilih.</p>
            </div>
        @endif
    </div>
</div>
