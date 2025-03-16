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

    public function render(): mixed
    {
        return view('livewire.admin.qr-attendance-overview', [
            'attendances' => $this->attendances,
        ]);
    }
}; ?>

<div>




    <div class="container mx-auto p-6">
        <h1 class="mb-[40px] font-inter text-xl font-medium">Rekap Presensi</h1>

        <div class="flex flex-row items-center justify-between">
            <div class="mb-6 flex items-center space-x-4">
                {{-- Pilih Tanggal --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pilih Tanggal</label>
                    <input type="date" wire:model.live="selectedDate"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-blue-500">
                </div>

                {{-- Filter Jurusan --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                    <flux:select wire:model.live="major" placeholder="Pilih jurusan...">
                        <flux:select.option value="all">Semua Jurusan</flux:select.option>
                        @foreach ($majors as $majorItem)
                            <flux:select.option value="{{ $majorItem->id }}">{{ $majorItem->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>


                {{-- Filter Kelas --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kelas</label>

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
            <div class="flex items-center">
                <flux:input kbd="⌘K" icon="magnifying-glass" placeholder="Search..." />
            </div>
        </div>

        {{-- Statistik Ringkasan --}}
        <div class="mb-6 grid grid-cols-3 gap-4">
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-2 font-inter text-lg">Total Hadir</h3>
                <p class="font-inter text-3xl font-medium text-green-600">
                    {{ $attendances->count() }}
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-2 font-inter text-lg">Terlambat</h3>
                <p class="font-inter text-3xl font-medium text-yellow-500">
                    {{ $attendances->filter(function ($group) {
                            // Periksa apakah ada entry 'datang' dengan status terlambat
                            return $group->where('type', 'datang')->where('status', 'terlambat')->count() > 0;
                        })->count() }}
                </p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow">
                <h3 class="mb-2 font-inter text-lg">Tidak Hadir</h3>
                <p class="font-inter text-3xl font-medium text-red-600">
                    0 {{-- Anda perlu logika untuk menghitung ini --}}
                </p>
            </div>
        </div>




        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">

            <table class="w-full text-left font-inter text-sm rtl:text-right dark:text-gray-400">
                <thead class="bg-blue-500 text-xs uppercase text-white dark:bg-gray-700 dark:text-gray-400">
                    <tr>

                        <th scope="col" class="px-6 py-3">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Jurusan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Kelas
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Kedatangan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Kepulangan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white text-slate-900">
                    @forelse($attendances as $userId => $userAttendances)
                        @php
                            $user = $userAttendances->first()->user;
                            $datang = $userAttendances->firstWhere('type', 'datang');
                            $pulang = $userAttendances->firstWhere('type', 'pulang');
                        @endphp
                        <tr class="border-b">
                            <td class="px-6 py-4">{{ $user->name }}</td>
                            @if (!$user->student)
                                <td class="px-6 py-4">-</td>
                            @else
                                <td class="px-6 py-4">{{ $user->student->classes->major->name }}</td>
                            @endif
                            @if (!$user->student)
                                <td class="px-6 py-4">Guru</td>
                            @else
                                <td class="px-6 py-4">{{ $user->student->classes->name }}</td>
                            @endif
                            <td class="px-6 py-4">
                                @if ($datang)
                                    {{ $datang->check_in_time }}

                                    <span
                                        class="bg-{{ $datang->status == 'terlambat' ? 'yellow' : 'green' }}-100 text-{{ $datang->status == 'terlambat' ? 'yellow' : 'green' }}-800 me-2 rounded-full px-2.5 py-0.5 text-xs font-medium dark:bg-yellow-900 dark:text-yellow-300">{{ $datang->status }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($pulang)
                                    {{ $pulang->check_in_time }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($datang && $pulang)
                                    <div class="badge badge-soft badge-success badge-sm">Lengkap</div>
                                @elseif($datang)
                                    <div class="badge badge-soft badge-warning badge-sm">Belum Pulang</div>
                                @else
                                    <div class="badge badge-soft badge-error badge-sm">Tidak Hadir</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-gray-500">
                                Tidak ada data presensi
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>

        </div>

    </div>
</div>
