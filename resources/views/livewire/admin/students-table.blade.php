<?php

use Livewire\Volt\Component;
use App\Models\Student;
use App\Models\User;
use App\Models\Attendance;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'DESC';
    public $perPage = 20;
    public $classFilter = null;
    public $majorFilter = null;

    // Tambahkan properti untuk menyimpan kelas yang tersedia berdasarkan jurusan
    public $availableClasses = [];

    // Bulan dan tahun untuk statistik kehadiran
    public $currentMonth;
    public $currentYear;

    // Daftar nama bulan
    public $monthNames = [
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

    // Daftar kelas dan jurusan untuk filter
    public $classes = [];
    public $majors = [];

    public function mount()
    {
        // Set bulan dan tahun saat ini
        $this->currentMonth = intval(date('m'));
        $this->currentYear = date('Y');

        // Load daftar jurusan untuk filter
        $this->majors = \App\Models\Major::orderBy('name')->get();

        // Inisialisasi daftar kelas yang tersedia
        $this->resetAvailableClasses();
    }

    public function resetAvailableClasses()
    {
        if ($this->majorFilter) {
            // Filter kelas berdasarkan major_id
            $this->availableClasses = \App\Models\Classes::where('major_id', $this->majorFilter)->orderBy('name')->get();
        } else {
            // Jika tidak ada filter jurusan, tampilkan semua kelas
            $this->availableClasses = \App\Models\Classes::orderBy('name')->get();
        }

        // Reset filter kelas jika major berubah
        $this->classFilter = null;
    }

    public function updatedMajorFilter()
    {
        $this->resetPage();
        $this->resetAvailableClasses();
    }

    public function loadFilterOptions()
    {
        $this->classes = \App\Models\Classes::orderBy('name')->get();
        $this->majors = \App\Models\Major::orderBy('name')->get();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingClassFilter()
    {
        $this->resetPage();
    }

    public function updatingMajorFilter()
    {
        $this->resetPage();
    }

    public function setSortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
        }

        $this->sortBy = $column;
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
    }

    public function getAttendanceStatistics($studentId)
    {
        // Tentukan periode bulan yang dipilih
        $firstDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->startOfMonth();
        $lastDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->endOfMonth();

        // Ambil data kehadiran dalam rentang bulan ini
        $attendanceData = Attendance::where('user_id', $studentId)
            ->whereBetween('attendance_date', [$firstDayOfMonth->format('Y-m-d'), $lastDayOfMonth->format('Y-m-d')])
            ->get();

        // Hitung hari kerja (Senin-Jumat) dalam bulan ini
        $workingDays = [];
        $currentDay = $firstDayOfMonth->copy();
        while ($currentDay->lte($lastDayOfMonth)) {
            if ($currentDay->dayOfWeek !== 0) {
                // Skip weekend (Minggu saja)
                $workingDays[] = $currentDay->format('Y-m-d');
            }
            $currentDay->addDay();
        }

        // Inisialisasi statistik
        $statistics = [
            'present' => 0,
            'late' => 0,
            'permission' => 0,
            'sick' => 0,
            'absent' => 0, // Awalnya semua dianggap tidak hadir
        ];

        // Buat array untuk menyimpan kehadiran per tanggal
        $attendanceByDate = [];

        // Update status kehadiran berdasarkan data yang ada
        foreach ($attendanceData as $attendance) {
            $date = $attendance->attendance_date;

            // Prioritaskan status datang (masuk) daripada pulang
            if ($attendance->type === 'datang') {
                $attendanceByDate[$date] = $attendance->status;
            }
            // Jika sudah ada status datang, jangan timpa dengan status pulang
            elseif ($attendance->type === 'pulang' && !isset($attendanceByDate[$date])) {
                $attendanceByDate[$date] = $attendance->status;
            }
        }

        // Hitung ringkasan berdasarkan status per tanggal
        $statistics['present'] = 0;
        $statistics['late'] = 0;
        $statistics['permission'] = 0;
        $statistics['sick'] = 0;
        $statistics['absent'] = 0;

        foreach ($attendanceByDate as $status) {
            if ($status === 'hadir') {
                $statistics['present']++;
            } elseif ($status === 'terlambat') {
                $statistics['late']++;
            } elseif ($status === 'izin') {
                $statistics['permission']++;
            } elseif ($status === 'sakit') {
                $statistics['sick']++;
            } elseif ($status === 'tidak_hadir') {
                $statistics['absent']++;
            }
        }

        return $statistics;
    }

    public function render(): mixed
    {
        $studentsQuery = Student::with(['user', 'classes', 'major'])
            ->when($this->search, function ($query) {
                return $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->classFilter, function ($query) {
                return $query->where('classes_id', $this->classFilter);
            })
            ->when($this->majorFilter, function ($query) {
                return $query->where('major', $this->majorFilter);
            });

        // Urutkan data
        if ($this->sortBy === 'name') {
            $studentsQuery->whereHas('user', function ($query) {
                $query->orderBy('name', $this->sortDirection);
            });
        } elseif ($this->sortBy === 'classes') {
            $studentsQuery->whereHas('classes', function ($query) {
                $query->orderBy('name', $this->sortDirection);
            });
        } elseif ($this->sortBy === 'major') {
            $studentsQuery->whereHas('major', function ($query) {
                $query->orderBy('name', $this->sortDirection);
            });
        } else {
            $studentsQuery->orderBy($this->sortBy, $this->sortDirection);
        }

        $students = $studentsQuery->paginate($this->perPage);

        // Hitung statistik kehadiran untuk setiap siswa
        foreach ($students as $student) {
            $student->attendance_stats = $this->getAttendanceStatistics($student->user_id);
        }

        return view('livewire.admin.students-table', [
            'students' => $students,
        ]);
    }
}; ?>

<div x-data="{
    showDetailModal: false,
    currentStudentId: null,
    currentStudentName: null
}">
    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col items-center gap-4 sm:flex-row">
            <!-- Search -->
            <div class="relative flex w-full max-w-xs">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari siswa..."
                    class="block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor"
                    class="absolute left-3 top-1/2 size-4 -translate-y-1/2 transform text-gray-500">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <!-- Major Filter -->
            <div class="w-full max-w-xs">
                <select wire:model.live="majorFilter"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">Semua Jurusan</option>
                    @foreach ($majors as $major)
                        <option value="{{ $major->id }}">{{ $major->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Class Filter -->
            <div class="w-full max-w-xs">
                <select wire:model.live="classFilter"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    @if (count($availableClasses) === 0 && !$majorFilter) disabled @endif>
                    <option value="">{{ $majorFilter ? 'Semua Kelas' : 'Pilih Jurusan Dulu' }}</option>
                    @foreach ($availableClasses as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Month Selector -->
        <div class="flex flex-row items-center justify-center gap-2">
            <button wire:click="changeMonth('prev')"
                class="rounded-md border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <span class="min-w-[160px] text-center text-sm font-medium">
                {{ $monthNames[intval($currentMonth)] }} {{ $currentYear }}
            </span>
            <button wire:click="changeMonth('next')"
                class="rounded-md border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Students Table -->
    <div class="hidden overflow-hidden rounded-lg border border-gray-200 shadow md:block">
        <table class="w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-blue-500 text-xs uppercase text-white">
                <tr>
                    <th scope="col" wire:click="setSortBy('name')" class="cursor-pointer px-6 py-3 font-medium">
                        <div class="flex items-center gap-2">
                            Nama Siswa
                            @if ($sortBy === 'name')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="{{ $sortDirection === 'ASC' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" />
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" wire:click="setSortBy('class')" class="cursor-pointer px-6 py-3 font-medium">
                        <div class="flex items-center gap-2">
                            Kelas
                            @if ($sortBy === 'class')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="{{ $sortDirection === 'ASC' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" />
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" wire:click="setSortBy('major')" class="cursor-pointer px-6 py-3 font-medium">
                        <div class="flex items-center gap-2">
                            Jurusan
                            @if ($sortBy === 'major')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="{{ $sortDirection === 'ASC' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" />
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Hadir
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Terlambat
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Izin
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Sakit
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Tidak Hadir
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Detail
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($students as $student)
                    <tr wire:key="student-{{ $student->id }}" class="transition-colors hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                            <a href="{{ route('user.detail', $student->user) }}" class="hover:text-blue-600"
                                wire:navigate>
                                {{ $student->user->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $student->classes->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $student->classes->major->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                {{ $student->attendance_stats['present'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                                {{ $student->attendance_stats['late'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                                {{ $student->attendance_stats['permission'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800">
                                {{ $student->attendance_stats['sick'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
                                {{ $student->attendance_stats['absent'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('user.detail', $student->user) }}" wire:navigate
                                class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-200">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="mb-2 size-10 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                                <p>Tidak ada siswa yang ditemukan</p>
                                <p class="mt-1 text-sm">Coba ubah kriteria pencarian</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View - Only show on small screens -->
    <div class="mt-4 grid grid-cols-1 gap-4 md:hidden">
        @forelse ($students as $student)
            <div wire:key="student-card-{{ $student->id }}"
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <a href="{{ route('user.detail', $student->user) }}" class="hover:text-blue-600"
                                wire:navigate>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $student->user->name }}</h3>
                            </a>
                            <div class="mt-1 flex flex-wrap gap-2">
                                <span class="text-sm text-gray-600">{{ $student->classes->name ?? '-' }}</span>
                                <span class="text-sm text-gray-600">•</span>
                                <span class="text-sm text-gray-600">{{ $student->classes->major->name ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-5 gap-2">
                        <div class="flex flex-col items-center space-y-1">
                            <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">
                                {{ $student->attendance_stats['present'] }}
                            </span>
                            <span class="text-xs text-gray-500">Hadir</span>
                        </div>
                        <div class="flex flex-col items-center space-y-1">
                            <span class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800">
                                {{ $student->attendance_stats['late'] }}
                            </span>
                            <span class="text-xs text-gray-500">Terlambat</span>
                        </div>
                        <div class="flex flex-col items-center space-y-1">
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">
                                {{ $student->attendance_stats['permission'] }}
                            </span>
                            <span class="text-xs text-gray-500">Izin</span>
                        </div>
                        <div class="flex flex-col items-center space-y-1">
                            <span class="rounded-full bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-800">
                                {{ $student->attendance_stats['sick'] }}
                            </span>
                            <span class="text-xs text-gray-500">Sakit</span>
                        </div>
                        <div class="flex flex-col items-center space-y-1">
                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800">
                                {{ $student->attendance_stats['absent'] }}
                            </span>
                            <span class="text-xs text-gray-500">Tidak Hadir</span>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <a href="{{ route('user.detail', $student->user->id) }}"
                            class="rounded-md bg-gray-100 px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200">
                            Detail Kehadiran
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mx-auto mb-2 size-10 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                </svg>
                <p>Tidak ada siswa yang ditemukan</p>
                <p class="mt-1 text-sm">Coba ubah kriteria pencarian</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination Controls -->
    <div class="mt-5 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <select wire:model.live="perPage" class="rounded-lg border-gray-300 text-sm shadow-sm">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
            </select>
            <span class="text-sm text-gray-600">Per halaman</span>
        </div>

        <div>
            {{ $students->links() }}
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
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
                <h2 class="text-xl font-medium text-gray-900" x-text="`Detail Kehadiran: ${currentStudentName}`"></h2>
                <button @click="showDetailModal = false" class="rounded-md p-1 hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-5 text-gray-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-500">{{ $monthNames[intval($currentMonth)] }}
                    {{ $currentYear }}</h3>
            </div>

            <div class="rounded-lg border border-gray-200">
                <!-- Detail konten akan di-load secara dinamis -->
                <p class="p-4 text-center text-gray-500">
                    Detail kehadiran belum tersedia. Fitur ini masih dalam pengembangan.
                </p>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button @click="showDetailModal = false"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Tutup
                </button>
                <button
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mr-1.5 size-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Unduh Detail
                </button>
            </div>
        </div>
    </div>
</div>
