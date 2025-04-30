<?php

use Livewire\Volt\Component;
use App\Models\Teacher;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'DESC';
    public $perPage = 5;

    public $showMonthYearModal = false;
    public $reportType = null; // 'teacher' atau 'staff'
    public $reportMonth;
    public $reportYear;

    public function updatingSearch()
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

    public function render(): mixed
    {
        // Basis query untuk teacher dengan relasi user
        $query = Teacher::with('user');

        // Pencarian
        if (!empty($this->search)) {
            $query
                ->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->orWhere('nuptk', 'like', '%' . $this->search . '%')
                ->orWhere('phone_number', 'like', '%' . $this->search . '%');
        }

        // Get teachers with ordering
        $teachers = $query->orderBy($this->sortBy, $this->sortDirection)->paginate($this->perPage);

        // Calculate additional stats for each teacher manually
        foreach ($teachers as $teacher) {
            // Get user_id of the teacher
            $userId = $teacher->user->id;

            // Count total subjects by this user_id
            $subjectCount = SubjectClass::where('user_id', $userId)->count();
            $teacher->subject_count = $subjectCount;

            // Get subject class IDs for this user_id
            $subjectClassIds = SubjectClass::where('user_id', $userId)->pluck('id')->toArray();

            // Count total sessions for these subject classes
            $sessionCount = 0;
            if (!empty($subjectClassIds)) {
                $sessionCount = SubjectClassSession::whereIn('subject_class_id', $subjectClassIds)->count();
            }
            $teacher->session_count = $sessionCount;
        }

        return view('livewire.admin.teachers-table', [
            'teachers' => $teachers,
        ]);
    }

    public function mount()
    {
        // Inisialisasi dengan bulan dan tahun saat ini
        $this->reportMonth = date('m');
        $this->reportYear = date('Y');
    }

    public function openReportModal($type)
    {
        $this->reportType = $type;
        $this->showMonthYearModal = true;
    }

    public function downloadReport()
    {
        // Redirect ke route sesuai dengan tipe laporan
        if ($this->reportType === 'teacher') {
            return redirect()->route('teacher.attendance.report', [
                'month' => $this->reportMonth,
                'year' => $this->reportYear,
            ]);
        } elseif ($this->reportType === 'staff') {
            return redirect()->route('staff.attendance.report', [
                'month' => $this->reportMonth,
                'year' => $this->reportYear,
            ]);
        }
    }

    public function delete($id)
    {
        $teacher = Teacher::findOrFail($id);

        // Hapus teacher
        $teacher->delete();

        $this->dispatch('teacher-deleted');
    }
}; ?>

<div x-data="{
    showDeleteModal: false,
    currentTeacherName: null,
    currentTeacherId: null,

    openDeleteModal(teacherId, teacherName) {
        this.currentTeacherId = teacherId;
        this.currentTeacherName = teacherName;
        this.showDeleteModal = true;
    },

    init() {
        Livewire.on('teacher-deleted', () => {
            this.showDeleteModal = false;
        });
    }
}">
    <!-- Search Section -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex w-full max-w-xs">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari guru..."
                class="block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 transform text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>

        <div class="flex items-center gap-4">
            <select wire:model.live="perPage" class="rounded-lg border-gray-300 text-sm shadow-sm">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
            </select>
            <span class="text-sm text-gray-600">Per halaman</span>
        </div>
    </div>

    <div class="mb-4 flex justify-end">
        <button wire:click="openReportModal('teacher')"
            class="mr-3 inline-flex items-center rounded-md border border-green-600 bg-white px-4 py-2 text-sm font-medium text-green-600 shadow-sm hover:bg-green-600 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
            Laporan Presensi Guru
        </button>
        <button wire:click="openReportModal('staff')"
            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
            Laporan Presensi Karyawan
        </button>
    </div>

    <!-- Modal untuk memilih bulan dan tahun -->
    <div x-data="{ show: @entangle('showMonthYearModal') }" x-show="show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-cloak>
        <div x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90" @click.away="show = false"
            class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ $reportType === 'teacher' ? 'Laporan Presensi Guru' : 'Laporan Presensi Karyawan' }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">Pilih bulan dan tahun untuk mengunduh laporan.</p>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <label for="reportMonth" class="block text-sm font-medium text-gray-700">Bulan</label>
                    <select wire:model="reportMonth" id="reportMonth"
                        class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>
                <div>
                    <label for="reportYear" class="block text-sm font-medium text-gray-700">Tahun</label>
                    <select wire:model="reportYear" id="reportYear"
                        class="mt-1 block w-full rounded-md border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @for ($y = date('Y') - 2; $y <= date('Y'); $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button @click="show = false"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Batal
                </button>
                <button wire:click="downloadReport" @click="show = false"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                    Unduh Laporan
                </button>
            </div>
        </div>
    </div>



    <!-- Teachers Table -->
    <div class="hidden overflow-hidden rounded-lg border border-gray-200 shadow md:block">
        <table class="w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-blue-500 text-xs uppercase text-white">
                <tr>
                    <th scope="col" wire:click="setSortBy('user.name')" class="cursor-pointer px-6 py-3 font-medium">
                        <div class="flex items-center gap-2">
                            Nama
                            @if ($sortBy === 'user.name')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="{{ $sortDirection === 'ASC' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" />
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" wire:click="setSortBy('nuptk')" class="cursor-pointer px-6 py-3 font-medium">
                        <div class="flex items-center gap-2">
                            NUPTK
                            @if ($sortBy === 'nuptk')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="{{ $sortDirection === 'ASC' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" />
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" wire:click="setSortBy('phone_number')"
                        class="cursor-pointer px-6 py-3 font-medium">
                        <div class="flex items-center gap-2">
                            Nomor HP
                            @if ($sortBy === 'phone_number')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="{{ $sortDirection === 'ASC' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" />
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Jumlah Kelas
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Jumlah Pertemuan
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-center font-medium">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($teachers as $teacher)
                    <tr wire:key="teacher-{{ $teacher->id }}" class="transition-colors hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                            <a href="{{ route('user.detail', $teacher->user) }}" class="hover:text-blue-600"
                                wire:navigate>
                                {{ $teacher->user->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            {{ $teacher->nuptk ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            {{ $teacher->phone_number ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">
                                {{ $teacher->subject_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-800">
                                {{ $teacher->session_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">
                                Aktif
                            </span>
                        </td>
                        <td class="flex items-center justify-center gap-2 px-6 py-4">
                            <a href="{{ route('user.edit', $teacher->user->id) }}" wire:navigate
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </a>

                            <button @click="openDeleteModal({{ $teacher->id }}, '{{ $teacher->user->name }}')"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                title="Hapus">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="mb-2 size-10 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                <p>Tidak ada guru yang ditemukan</p>
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
        @forelse ($teachers as $teacher)
            <div wire:key="teacher-card-{{ $teacher->id }}"
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <a href="{{ route('user.detail', $teacher->user) }}" class="hover:text-blue-600"
                                wire:navigate>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $teacher->user->name }}</h3>
                            </a>
                            <p class="mt-1 text-sm text-gray-600">{{ $teacher->user->email }}</p>
                        </div>
                        <span
                            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">
                            Aktif
                        </span>
                    </div>

                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">NUPTK</span>
                            <span class="text-sm font-medium">{{ $teacher->nuptk ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Nomor HP</span>
                            <span class="text-sm font-medium">{{ $teacher->phone_number ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Jumlah Kelas</span>
                            <span
                                class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800">
                                {{ $teacher->subject_count }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Jumlah Pertemuan</span>
                            <span
                                class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-800">
                                {{ $teacher->session_count }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <a href="{{ route('user.edit', $teacher->user->id) }}" wire:navigate
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </a>

                        <button @click="openDeleteModal({{ $teacher->id }}, '{{ $teacher->user->name }}')"
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white p-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                            title="Hapus">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mx-auto mb-2 size-10 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <p>Tidak ada guru yang ditemukan</p>
                <p class="mt-1 text-sm">Coba ubah kriteria pencarian</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination Controls -->
    <div class="mt-5">
        {{ $teachers->links() }}
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="w-full max-w-md transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:max-w-lg"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-medium text-gray-900">Hapus Guru</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Apakah Anda yakin ingin menghapus guru <span class="font-bold"
                                    x-text="currentTeacherName"></span>?
                                Tindakan ini tidak dapat dibatalkan dan semua data terkait guru ini akan dihapus.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button @click="$wire.delete(currentTeacherId)"
                    class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                    Hapus
                </button>
                <button @click="showDeleteModal = false"
                    class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>
