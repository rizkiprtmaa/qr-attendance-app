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

    // Tambahkan properties untuk dropdown
    public $majors;
    public $classesList;

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

        return $query->orderBy('check_in_time')->get()->groupBy('user_id');
    }

    #[On('scan-attendance')]
    public function render(): mixed
    {
        return view('livewire.admin.qr-attendance-overview', [
            'attendances' => $this->attendances,
        ]);
    }
}; ?>

<div>
    <div class="container mx-auto p-6">
        <h1 class="mb-4 font-inter text-xl font-medium">Rekap Presensi</h1>

        <div class="mb-6 flex items-center space-x-4">
            {{-- Pilih Tanggal --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Pilih Tanggal</label>
                <input type="date" wire:model.live="selectedDate"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>

            {{-- Filter Jurusan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                <select wire:model.live="major" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="all">Semua Jurusan</option>
                    @foreach ($majors as $majorItem)
                        <option value="{{ $majorItem->id }}">{{ $majorItem->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Kelas --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Kelas</label>
                <select wire:model.live="classes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="all">Semua Kelas</option>
                    @foreach ($classesList as $class)
                        @if ($major == 'all' || $class->major_id == $major)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Statistik Ringkasan --}}
        <div class="mb-6 grid grid-cols-3 gap-4">
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-2 text-lg font-semibold">Total Hadir</h3>
                <p class="text-2xl font-bold text-green-600">
                    {{ $attendances->count() }}
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-2 text-lg font-semibold">Terlambat</h3>
                <p class="text-2xl font-bold text-yellow-600">
                    {{ $attendances->filter(fn($group) => $group->first()->status === 'terlambat')->count() }}
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-2 text-lg font-semibold">Tidak Hadir</h3>
                <p class="text-2xl font-bold text-red-600">
                    0 {{-- Anda perlu logika untuk menghitung ini --}}
                </p>
            </div>
        </div>

        {{-- Tabel Presensi --}}
        <div class="overflow-hidden rounded-lg bg-white shadow-md">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Jurusan</th>
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-left">Kedatangan</th>
                        <th class="px-4 py-3 text-left">Kepulangan</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $userId => $userAttendances)
                        @php
                            $user = $userAttendances->first()->user;
                            $datang = $userAttendances->firstWhere('type', 'datang');
                            $pulang = $userAttendances->firstWhere('type', 'pulang');
                        @endphp
                        <tr class="border-b">
                            <td class="px-4 py-3">{{ $user->name }}</td>
                            @if (!$user->student)
                                <td class="px-4 py-3">-</td>
                            @else
                                <td class="px-4 py-3">{{ $user->student->classes->major->name }}</td>
                            @endif
                            @if (!$user->student)
                                <td class="px-4 py-3">Guru</td>
                            @else
                                <td class="px-4 py-3">{{ $user->student->classes->name }}</td>
                            @endif
                            <td class="px-4 py-3">
                                @if ($datang)
                                    {{ $datang->check_in_time }}
                                    <span
                                        class="text-{{ $datang->status == 'terlambat' ? 'yellow' : 'green' }}-600 text-sm">
                                        ({{ $datang->status }})
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($pulang)
                                    {{ $pulang->check_in_time }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($datang && $pulang)
                                    <span class="text-green-600">Lengkap</span>
                                @elseif($datang)
                                    <span class="text-yellow-600">Belum Pulang</span>
                                @else
                                    <span class="text-red-600">Tidak Hadir</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">
                                Tidak ada data presensi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
