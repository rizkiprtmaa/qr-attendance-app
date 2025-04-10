<?php

use Livewire\Volt\Component;
use App\Models\Attendance;
use App\Models\Major;
use App\Models\Classes;
use Livewire\Attributes\On;
use Carbon\Carbon;

new class extends Component {
    public $selectedDate;
    public $major = 'all';
    public $classes = 'all';
    public $search = '';

    // Tambahkan properties untuk dropdown
    public $majors;
    public $classesList;

    #[On('scan-attendance')]
    public function mount()
    {
        // Default ke hari ini
        $this->selectedDate = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        // Ambil daftar jurusan dan kelas untuk dropdown
        $this->majors = Major::all();
        $this->classesList = Classes::all();
    }

    public function updatedMajor()
    {
        // Reset kelas jika jurusan berubah
        $this->classes = 'all';
    }

    // Fungsi untuk pergi ke hari sebelumnya
    public function previousDay()
    {
        $currentDate = Carbon::parse($this->selectedDate);
        $this->selectedDate = $currentDate->subDay()->format('Y-m-d');
    }

    // Fungsi untuk pergi ke hari berikutnya
    public function nextDay()
    {
        $currentDate = Carbon::parse($this->selectedDate);
        $this->selectedDate = $currentDate->addDay()->format('Y-m-d');
    }

    public function getAttendancesProperty()
    {
        $query = Attendance::with(['user.roles', 'user.student.classes.major']);

        // Filter tanggal
        $query->whereDate('attendance_date', $this->selectedDate);

        // Filter berdasarkan jurusan
        if ($this->major !== 'all') {
            $query->whereHas('user.student.classes', function ($q) {
                $q->where('major_id', $this->major);
            });
        }

        // Filter berdasarkan kelas
        if ($this->classes !== 'all') {
            $query->whereHas('user.student', function ($q) {
                $q->where('classes_id', $this->classes);
            });
        }

        // Filter berdasarkan pencarian
        if (!empty($this->search)) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        // Urutkan berdasarkan beberapa kriteria:
        // 1. Status tidak_hadir akan berada di bawah (ORDER BY FIELD)
        // 2. Jika check_in_time NULL, berikan nilai default yang besar
        // 3. Akhirnya gunakan nama user sebagai kriteria terakhir
        return $query->join('users', 'attendances.user_id', '=', 'users.id')->orderByRaw("FIELD(attendances.status, 'tidak_hadir') ASC")->orderByRaw("IFNULL(attendances.check_in_time, '23:59:59')")->orderBy('users.name')->select('attendances.*')->get()->groupBy('user_id');
    }

    public function render(): mixed
    {
        return view('livewire.admin.qr-attendance-overview', [
            'attendances' => $this->attendances,
        ]);
    }
}; ?>

<div>
    <div class="container mx-auto overflow-hidden">
        <!-- Header & Filters -->
        <div class="mb-6">

            <div class="mb-6 rounded-lg bg-white p-4 shadow">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-row items-center gap-4 md:flex-row md:items-start">
                        {{-- Pilih Tanggal --}}
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="date" wire:model.live="selectedDate"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        {{-- Filter Jurusan --}}
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Jurusan</label>
                            <flux:select wire:model.live="major" placeholder="Pilih jurusan...">
                                <flux:select.option value="all">Semua Jurusan</flux:select.option>
                                @foreach ($majors as $majorItem)
                                    <flux:select.option value="{{ $majorItem->id }}">{{ $majorItem->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Filter Kelas --}}
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kelas</label>
                            <flux:select wire:model.live="classes" placeholder="Pilih kelas...">
                                <flux:select.option value="all">Semua Kelas</flux:select.option>
                                @foreach ($classesList as $class)
                                    @if ($major == 'all' || $class->major_id == $major)
                                        <flux:select.option value="{{ $class->id }}">{{ $class->name }}
                                        </flux:select.option>
                                    @endif
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Pencarian</label>
                        <flux:input wire:model.live.debounce.300ms="search" kbd="⌘K" icon="magnifying-glass"
                            placeholder="Cari nama..." />
                    </div>
                </div>
            </div>
        </div>

        {{-- Statistik Ringkasan --}}
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Card: Ringkasan Kehadiran -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-blue-100 bg-blue-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-blue-800">Ringkasan Kehadiran</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-4">
                        <!-- Total Hadir -->
                        <div class="text-center">
                            @php
                                $tepat = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('type', 'datang')->where('status', 'hadir')->count() > 0;
                                    })
                                    ->count();
                                $terlambat = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('type', 'datang')->where('status', 'terlambat')->count() >
                                            0;
                                    })
                                    ->count();
                            @endphp
                            <p class="mb-1 text-xs text-gray-500">Total Hadir</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $tepat + $terlambat }}</p>
                        </div>

                        <!-- Tepat Waktu -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Tepat Waktu</p>
                            @php
                                $tepat = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('type', 'datang')->where('status', 'hadir')->count() > 0;
                                    })
                                    ->count();
                            @endphp
                            <p class="text-2xl font-bold text-green-600">{{ $tepat }}</p>
                        </div>

                        <!-- Terlambat -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Terlambat</p>
                            @php
                                $terlambat = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('type', 'datang')->where('status', 'terlambat')->count() >
                                            0;
                                    })
                                    ->count();
                            @endphp
                            <p class="text-2xl font-bold text-amber-500">{{ $terlambat }}</p>
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
                            @php
                                // Hitung siswa yang tidak hadir tanpa keterangan
                                $tidakHadir = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('status', 'tidak_hadir')->count() > 0;
                                    })
                                    ->count();
                            @endphp
                            <p class="text-2xl font-bold text-red-600">{{ $tidakHadir }}</p>
                        </div>

                        <!-- Izin -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Izin</p>
                            @php
                                $izin = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('status', 'izin')->count() > 0;
                                    })
                                    ->count();
                            @endphp
                            <p class="text-2xl font-bold text-sky-600">{{ $izin }}</p>
                        </div>

                        <!-- Sakit -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Sakit</p>
                            @php
                                $sakit = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('status', 'sakit')->count() > 0;
                                    })
                                    ->count();
                            @endphp
                            <p class="text-2xl font-bold text-pink-600">{{ $sakit }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Status Presensi -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-green-100 bg-green-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-green-800">Status Presensi</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-4">
                        <!-- Lengkap -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Lengkap</p>
                            @php
                                $lengkap = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('type', 'datang')->count() > 0 &&
                                            $group->where('type', 'pulang')->count() > 0;
                                    })
                                    ->count();
                            @endphp
                            <p class="text-2xl font-bold text-green-600">{{ $lengkap }}</p>
                        </div>

                        <!-- Tidak Lengkap -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Tidak Lengkap</p>
                            @php
                                $tidakLengkap = $attendances
                                    ->filter(function ($group) {
                                        $datangHadir =
                                            $group
                                                ->where('type', 'datang')
                                                ->whereIn('status', ['hadir', 'terlambat'])
                                                ->count() > 0;
                                        $pulangHadir = $group->where('type', 'pulang')->count() > 0;
                                        return $datangHadir && !$pulangHadir;
                                    })
                                    ->count();
                            @endphp
                            <p class="text-2xl font-bold text-red-500">{{ $tidakLengkap }}</p>
                        </div>

                        <!-- Total Siswa -->
                        <div class="text-center">
                            <p class="mb-1 text-xs text-gray-500">Tidak Hadir</p>
                            @php
                                // Jumlah siswa bergantung pada filter yang aktif
                                $totalAbsent = $attendances
                                    ->filter(function ($group) {
                                        return $group->where('status', 'tidak_hadir')->count() > 0 ||
                                            $group->where('status', 'izin')->count() > 0 ||
                                            $group->where('status', 'sakit')->count() > 0;
                                    })
                                    ->count();
                                // Untuk lebih akurat, Anda bisa mengganti dengan query yang mengambil total siswa berdasarkan filter aktif
                            @endphp
                            <p class="text-2xl font-bold text-blue-800">{{ $totalAbsent }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Presensi -->
        <div class="hidden overflow-hidden rounded-lg bg-white shadow-md md:block">
            <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Daftar Presensi</h3>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Total: {{ $attendances->count() }} data</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left font-inter text-sm text-gray-500">
                    <thead class="bg-blue-500 text-xs uppercase text-white">
                        <tr>
                            <th scope="col" class="px-6 py-3">Nama</th>
                            <th scope="col" class="px-6 py-3">Jurusan</th>
                            <th scope="col" class="px-6 py-3">Kelas</th>
                            <th scope="col" class="px-6 py-3">Kedatangan</th>
                            <th scope="col" class="px-6 py-3">Kepulangan</th>
                            <th scope="col" class="px-6 py-3">Status</th>

                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($attendances as $userId => $userAttendances)
                            @php
                                $user = $userAttendances->first()->user;
                                $datang = $userAttendances->firstWhere('type', 'datang');
                                $pulang = $userAttendances->firstWhere('type', 'pulang');
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $user->name }}</td>
                                <td class="px-6 py-4">
                                    @if (!$user->student)
                                        <span class="text-gray-400">-</span>
                                    @else
                                        {{ $user->student->classes->major->name }}
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if (!$user->student)
                                        <span
                                            class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">
                                            Guru
                                        </span>
                                    @else
                                        {{ $user->student->classes->name }}
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($datang)
                                        <div class="flex items-center">
                                            <span class="mr-2">{{ $datang->check_in_time }}</span>
                                            @if ($datang->status == 'terlambat')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                                    Terlambat
                                                </span>
                                            @elseif($datang->status == 'tidak_hadir')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20">
                                                    Tidak Hadir
                                                </span>
                                            @elseif($datang->status == 'izin')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-800 ring-1 ring-inset ring-blue-600/20">
                                                    Izin
                                                </span>
                                            @elseif($datang->status == 'sakit')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-pink-50 px-2 py-1 text-xs font-medium text-pink-800 ring-1 ring-inset ring-pink-600/20">
                                                    Sakit
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                    {{ ucfirst($datang->status) }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($pulang)
                                        <div class="flex items-center">
                                            <span class="mr-2">{{ $pulang->check_in_time }}</span>
                                            @if ($pulang->status == 'pulang_cepat')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                                    Pulang Cepat
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($datang && $pulang)
                                        <span
                                            class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            Lengkap
                                        </span>
                                    @elseif($datang && $datang->status != 'tidak_hadir' && $datang->status != 'izin' && $datang->status != 'sakit')
                                        <span
                                            class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                            Belum Pulang
                                        </span>
                                    @elseif($datang && $datang->status == 'tidak_hadir')
                                        <span
                                            class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                            Tidak Hadir
                                        </span>
                                    @elseif($datang && $datang->status == 'izin')
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                            Izin
                                        </span>
                                    @elseif($datang && $datang->status == 'sakit')
                                        <span
                                            class="inline-flex items-center rounded-full bg-pink-50 px-2.5 py-1 text-xs font-medium text-pink-700 ring-1 ring-inset ring-pink-600/20">
                                            Sakit
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                            Tidak Hadir
                                        </span>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p class="mt-2 text-sm font-medium">Tidak ada data presensi</p>
                                        <p class="mt-1 text-xs text-gray-400">Silakan ubah filter atau tanggal untuk
                                            melihat data lainnya</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>


        {{-- Tampilan Mobile Card List --}}
        <div class="block space-y-4 md:hidden">
            @forelse($attendances as $userId => $userAttendances)
                @php
                    $user = $userAttendances->first()->user;
                    $datang = $userAttendances->firstWhere('type', 'datang');
                    $pulang = $userAttendances->firstWhere('type', 'pulang');
                @endphp
                <div class="rounded-lg bg-white p-4 shadow">
                    <div class="mb-2 flex justify-between">
                        <div>
                            <h3 class="font-medium text-gray-900">{{ $user->name }}</h3>
                            <div class="mt-1 flex items-center gap-2 text-sm">
                                @if (!$user->student)
                                    <span
                                        class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">
                                        Guru
                                    </span>
                                @else
                                    <span class="text-gray-600">{{ $user->student->classes->major->name }}</span>
                                    <span class="text-gray-400">•</span>
                                    <span class="text-gray-600">{{ $user->student->classes->name }}</span>
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Kedatangan</span>
                            <div class="flex items-center gap-2">
                                @if ($datang)
                                    <span>{{ $datang->check_in_time }}</span>
                                    @if ($datang->status == 'terlambat')
                                        <span
                                            class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                            Terlambat
                                        </span>
                                    @elseif($datang->status == 'tidak_hadir')
                                        <span
                                            class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-600/20">
                                            Tidak Hadir
                                        </span>
                                    @elseif($datang->status == 'izin')
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-800 ring-1 ring-inset ring-blue-600/20">
                                            Izin
                                        </span>
                                    @elseif($datang->status == 'sakit')
                                        <span
                                            class="inline-flex items-center rounded-full bg-pink-50 px-2 py-1 text-xs font-medium text-pink-800 ring-1 ring-inset ring-pink-600/20">
                                            Sakit
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            {{ ucfirst($datang->status) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Kepulangan</span>
                            <div class="flex items-center gap-2">
                                @if ($pulang)
                                    <span>{{ $pulang->check_in_time }}</span>
                                    @if ($pulang->status == 'pulang_cepat')
                                        <span
                                            class="inline-flex items-center rounded-full bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                            Pulang Cepat
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Status</span>
                            @if ($datang && $pulang)
                                <span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                    Lengkap
                                </span>
                            @elseif($datang && $datang->status != 'tidak_hadir' && $datang->status != 'izin' && $datang->status != 'sakit')
                                <span
                                    class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                    Belum Pulang
                                </span>
                            @elseif($datang && $datang->status == 'tidak_hadir')
                                <span
                                    class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                    Tidak Hadir
                                </span>
                            @elseif($datang && $datang->status == 'izin')
                                <span
                                    class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                    Izin
                                </span>
                            @elseif($datang && $datang->status == 'sakit')
                                <span
                                    class="inline-flex items-center rounded-full bg-pink-50 px-2.5 py-1 text-xs font-medium text-pink-700 ring-1 ring-inset ring-pink-600/20">
                                    Sakit
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                    Tidak Hadir
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-lg bg-white p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="mt-2 text-sm font-medium text-gray-900">Tidak ada data presensi</p>
                    <p class="mt-1 text-xs text-gray-500">Silakan ubah filter atau tanggal untuk melihat data lainnya
                    </p>
                </div>
            @endforelse
        </div>

        <!-- Action Buttons (Export dan Scan) -->
        <div class="mt-6 flex flex-col justify-between gap-4 sm:flex-row">
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="previousDay"
                    class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Hari Kemarin
                </button>
                <button type="button" wire:click="nextDay"
                    class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    Hari Berikutnya
                </button>
                
            </div>
            <div class="flex gap-2">

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
        </div>


    </div>


</div>
