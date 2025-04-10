<?php

use Livewire\Volt\Component;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use App\Models\SubstitutionRequest;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $session;
    public $attendances = [];
    public $filter = '';

    // Untuk statistik
    public $presentCount = 0;
    public $absentCount = 0;
    public $permissionCount = 0;
    public $totalStudents = 0;

    public function mount(SubjectClassSession $session)
    {
        $this->session = $session;

        // Verifikasi bahwa pengguna adalah guru pengganti yang membuat sesi ini
        $substitution = SubstitutionRequest::where('substitute_teacher_id', auth()->id())
            ->where('subject_class_id', $session->subject_class_id)
            ->where('status', 'approved')
            ->first();

        if (!$substitution || $session->substitution_request_id != $substitution->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola presensi kelas ini');
        }

        $this->loadAttendances();
        $this->updateStatistics();
    }

    public function loadAttendances()
    {
        $query = $this->session->attendances()->with('student.user');

        // Apply filter if set
        if ($this->filter && $this->filter !== 'all') {
            if ($this->filter === 'izin') {
                // Jika filter izin, tampilkan izin dan sakit
                $query->whereIn('status', ['izin', 'sakit']);
            } else {
                $query->where('status', $this->filter);
            }
        }

        $this->attendances = $query->get();
    }

    public function updateStatistics()
    {
        $all = $this->session->attendances;
        $this->presentCount = $all->where('status', 'hadir')->count();
        $this->absentCount = $all->where('status', 'tidak_hadir')->count();
        $this->permissionCount = $all->whereIn('status', ['izin', 'sakit'])->count();
        $this->totalStudents = $all->count();
    }

    public function updateAttendanceStatus($attendanceId, $status)
    {
        try {
            $attendance = SubjectClassAttendance::findOrFail($attendanceId);

            // Update status
            $attendance->status = $status;

            // Update check-in time if status is 'hadir' and it wasn't before
            if ($status === 'hadir' && $attendance->check_in_time === null) {
                $attendance->check_in_time = now();
            }

            // Clear check-in time if status is not 'hadir'
            if ($status !== 'hadir') {
                $attendance->check_in_time = null;
            }

            $attendance->save();

            // Reload data
            $this->loadAttendances();
            $this->updateStatistics();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Status kehadiran berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui status kehadiran: ' . $e->getMessage(),
            ]);
        }
    }

    public function bulkUpdate($status)
    {
        try {
            // Get IDs of currently filtered/visible attendances
            $attendanceIds = $this->attendances->pluck('id')->toArray();

            if (empty($attendanceIds)) {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Tidak ada siswa yang dipilih untuk diperbarui',
                ]);
                return;
            }

            // Update all selected attendances
            SubjectClassAttendance::whereIn('id', $attendanceIds)->update([
                'status' => $status,
                'check_in_time' => $status === 'hadir' ? now() : null,
            ]);

            // Reload data
            $this->loadAttendances();
            $this->updateStatistics();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Status kehadiran siswa berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui status kehadiran: ' . $e->getMessage(),
            ]);
        }
    }

    public function applyFilter($filter)
    {
        $this->filter = $filter;
        $this->loadAttendances();
    }

    public function render(): mixed
    {
        return view('livewire.teacher.session-attendance-management', [
            'attendances' => $this->attendances,
            'session' => $this->session->load('subjectClass.classes.major'),
        ]);
    }
}; ?>

<div>

    <!-- Toast Notification Component -->
    <div x-data="{
        toastMessage: '',
        toastType: '',
        showToast: false
    }"
        x-on:show-toast.window="
            const data = $event.detail[0] || $event.detail;
            toastMessage = data.message;
            toastType = data.type;
            showToast = true;
            setTimeout(() => showToast = false, 3000)
         ">

        <div x-cloak x-show="showToast" x-transition.opacity
            :class="toastType === 'success' ? 'bg-white text-gray-500' : 'bg-red-100 text-red-700'"
            class="fixed bottom-5 right-5 z-10 mb-4 flex w-full max-w-xs items-center rounded-lg p-4 shadow"
            role="alert">

            <template x-if="toastType === 'success'">
                <div
                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-500">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                    </svg>
                </div>
            </template>

            <template x-if="toastType === 'error'">
                <div
                    class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-500">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                    </svg>
                </div>
            </template>

            <div class="ml-3 text-sm font-normal" x-text="toastMessage"></div>

            <button type="button" @click="showToast = false"
                class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300">
                <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    </div>
    <!-- Header Info -->
    <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $session->subject_title }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    <span class="font-medium">Mata Pelajaran:</span> {{ $session->subjectClass->class_name }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    <span class="font-medium">Kelas:</span> {{ $session->subjectClass->classes->name }} -
                    {{ $session->subjectClass->classes->major->name }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    <span class="font-medium">Tanggal:</span> {{ $session->class_date->format('d M Y') }} |
                    {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} -
                    {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                </p>
            </div>

            <div class="mt-4 sm:mt-0">
                <a href="{{ route('substitute.class', $session->subject_class_id) }}" wire:navigate
                    class="text-blue-600 hover:text-blue-900">
                    &larr; Kembali ke Kelas
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="mb-6 grid grid-cols-2 gap-5 md:grid-cols-4 lg:grid-cols-4">
        <!-- Present Stats Card -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-full bg-green-100 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Hadir</dt>
                            <dd>
                                <div class="text-lg font-medium text-gray-900">
                                    {{ $presentCount }} / {{ $totalStudents }}
                                    <span
                                        class="text-sm text-gray-500">({{ $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100) : 0 }}%)</span>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="#" wire:click.prevent="applyFilter('hadir')"
                        class="font-medium text-green-700 hover:text-green-900">Lihat siswa</a>
                </div>
            </div>
        </div>

        <!-- Absent Stats Card -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-full bg-red-100 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Tidak Hadir</dt>
                            <dd>
                                <div class="text-lg font-medium text-gray-900">
                                    {{ $absentCount }} / {{ $totalStudents }}
                                    <span
                                        class="text-sm text-gray-500">({{ $totalStudents > 0 ? round(($absentCount / $totalStudents) * 100) : 0 }}%)</span>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="#" wire:click.prevent="applyFilter('tidak_hadir')"
                        class="font-medium text-red-700 hover:text-red-900">Lihat siswa</a>
                </div>
            </div>
        </div>

        <!-- Permission Stats Card -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-full bg-blue-100 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Izin/Sakit</dt>
                            <dd>
                                <div class="text-lg font-medium text-gray-900">
                                    {{ $permissionCount }} / {{ $totalStudents }}
                                    <span
                                        class="text-sm text-gray-500">({{ $totalStudents > 0 ? round(($permissionCount / $totalStudents) * 100) : 0 }}%)</span>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="#" wire:click.prevent="applyFilter('izin')"
                        class="font-medium text-blue-700 hover:text-blue-900">Lihat siswa</a>
                </div>
            </div>
        </div>

        <!-- All Students Card -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-full bg-gray-100 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">Total Siswa</dt>
                            <dd>
                                <div class="text-lg font-medium text-gray-900">{{ $totalStudents }}</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="#" wire:click.prevent="applyFilter('all')"
                        class="font-medium text-gray-700 hover:text-gray-900">Lihat semua</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Action Buttons -->
    <div class="mb-4 sm:flex sm:items-center sm:justify-between">
        <div class="flex space-x-2">
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    Tindakan Massal
                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false"
                    class="absolute left-0 z-10 mt-2 w-56 origin-top-left rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                    x-cloak>
                    <div class="py-1">
                        <button wire:click="bulkUpdate('hadir')"
                            class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
                            <span
                                class="mr-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                <svg class="-ml-0.5 mr-1 h-2 w-2 text-green-400" fill="currentColor"
                                    viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Hadir
                            </span>
                            Tandai Semua Hadir
                        </button>
                        <button wire:click="bulkUpdate('tidak_hadir')"
                            class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
                            <span
                                class="mr-2 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                <svg class="-ml-0.5 mr-1 h-2 w-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Tidak Hadir
                            </span>
                            Tandai Semua Tidak Hadir
                        </button>
                    </div>
                </div>
            </div>

            <button wire:click="applyFilter('all')"
                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset Filter
            </button>
        </div>
    </div>

    <!-- Student Attendance List -->
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Siswa</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">NISN</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Waktu
                            Hadir</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                <div class="font-medium text-gray-900">{{ $attendance->student->user->name }}</div>
                                <div class="text-gray-500">{{ $attendance->student->user->email }}</div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $attendance->student->nisn ?? 'N/A' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                @if ($attendance->status === 'hadir')
                                    <span
                                        class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold leading-5 text-green-800">Hadir</span>
                                @elseif($attendance->status === 'tidak_hadir')
                                    <span
                                        class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold leading-5 text-red-800">Tidak
                                        Hadir</span>
                                @elseif($attendance->status === 'izin')
                                    <span
                                        class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold leading-5 text-yellow-800">Izin</span>
                                @elseif($attendance->status === 'sakit')
                                    <span
                                        class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold leading-5 text-blue-800">Sakit</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : '-' }}
                            </td>
                            <td
                                class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <div class="flex justify-end space-x-2">
                                    <button wire:click="updateAttendanceStatus({{ $attendance->id }}, 'hadir')"
                                        class="{{ $attendance->status === 'hadir' ? 'bg-green-100 text-green-600' : 'text-gray-400 hover:text-green-600' }} rounded-full p-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <button wire:click="updateAttendanceStatus({{ $attendance->id }}, 'tidak_hadir')"
                                        class="{{ $attendance->status === 'tidak_hadir' ? 'bg-red-100 text-red-600' : 'text-gray-400 hover:text-red-600' }} rounded-full p-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <button wire:click="updateAttendanceStatus({{ $attendance->id }}, 'izin')"
                                        class="{{ $attendance->status === 'izin' ? 'bg-yellow-100 text-yellow-600' : 'text-gray-400 hover:text-yellow-600' }} rounded-full p-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </button>
                                    <button wire:click="updateAttendanceStatus({{ $attendance->id }}, 'sakit')"
                                        class="{{ $attendance->status === 'sakit' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-blue-600' }} rounded-full p-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data siswa</h3>
                                <p class="mt-1 text-sm text-gray-500">Tidak ada siswa yang ditemukan.</p>
                                <div class="mt-6">
                                    <button wire:click="applyFilter('all')"
                                        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Reset Filter
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


</div>
