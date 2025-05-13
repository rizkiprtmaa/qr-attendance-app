<?php
// resources/views/livewire/admin/session-attendance.php

use Livewire\Volt\Component;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use App\Models\Attendance;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $session;
    public $sessionId;
    public $subjectTitle;
    public $classDate;
    public $startTime;
    public $endTime;
    public $subjectClassId;
    public $className;
    public $classCode;
    public $studentClassName;
    public $majorName;
    public $teacherId;
    public $attendances = [];

    // Filter properties
    public $search = '';
    public $statusFilter = '';

    public function mount($sessionId)
    {
        $this->sessionId = $sessionId;
        $this->session = SubjectClassSession::findOrFail($sessionId);
        $this->fill($this->session->toArray());
        $this->subjectTitle = $this->session->subject_title;
        $this->classDate = $this->session->class_date;
        $this->startTime = $this->session->start_time;
        $this->endTime = $this->session->end_time;

        $this->subjectClassId = $this->session->subject_class_id;

        // Get class details
        $subjectClass = $this->session->subjectClass;
        $this->className = $subjectClass->class_name;
        $this->classCode = $subjectClass->class_code;
        $this->teacherId = $subjectClass->user_id;

        // Get classes detail
        $this->studentClassName = $subjectClass->classes->name;
        $this->majorName = $subjectClass->classes->major->name;

        // Load attendances
        $this->loadAttendances();
    }

    public function loadAttendances()
    {
        $query = SubjectClassAttendance::where('subject_class_session_id', $this->sessionId)->with(['student.user']); // Eager loading untuk student dan user

        // Apply search filter if provided
        if (!empty($this->search)) {
            $query->whereHas('student.user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('nisn', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter if provided
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $this->attendances = $query->get();
    }

    // Method to update attendance status manually by admin
    public function updateStatus($attendanceId, $status)
    {
        try {
            $attendance = SubjectClassAttendance::findOrFail($attendanceId);
            $student = $attendance->student;
            $sessionDate = $this->session->class_date->format('Y-m-d');

            // Cek apakah siswa sudah absen gerbang/hadir hari ini
            $hasAttendedSchool = Attendance::isStudentPresentToday($student->user_id, $sessionDate);

            // Jika tidak hadir di sekolah tapi mencoba menandai hadir di kelas
            if (!$hasAttendedSchool && $status === 'hadir') {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Siswa belum melakukan absensi gerbang, tidak dapat ditandai hadir di kelas',
                ]);
                return;
            }

            $attendance->status = $status;

            // Update check-in time jika status hadir
            if ($status === 'hadir' && $attendance->check_in_time === null) {
                $attendance->check_in_time = now();
            }

            // Clear check-in time jika status bukan hadir
            if ($status !== 'hadir') {
                $attendance->check_in_time = null;
            }

            $attendance->save();

            // Reload attendances after update
            $this->loadAttendances();

            $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Status presensi berhasil diperbarui']);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Gagal memperbarui status presensi: ' . $e->getMessage()]);
        }
    }

    // Method to handle bulk update
    public function bulkUpdateStatus($status)
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

            if ($status === 'hadir') {
                // Filter hanya siswa yang sudah absen gerbang
                $allAttendances = $this->session->attendances()->with('student.user')->get();
                $validAttendances = $allAttendances->filter(function ($attendance) {
                    $sessionDate = $this->session->class_date->format('Y-m-d');
                    $hasAttendedSchool = Attendance::isStudentPresentToday($attendance->student->user_id, $sessionDate);

                    return $hasAttendedSchool;
                });

                $validAttendanceIds = $validAttendances->pluck('id')->toArray();

                if (count($validAttendanceIds) !== count($attendanceIds)) {
                    $skippedCount = count($attendanceIds) - count($validAttendanceIds);
                    $this->dispatch('show-toast', [
                        'type' => 'warning',
                        'message' => "$skippedCount siswa dilewati karena belum absen gerbang",
                    ]);
                }

                // Jika tidak ada siswa yang valid sama sekali
                if (empty($validAttendanceIds) && $status === 'hadir') {
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'Tidak ada siswa yang bisa ditandai hadir karena belum ada yang absen gerbang',
                    ]);
                    return;
                }

                // Hanya update yang valid
                $attendanceIds = $validAttendanceIds;
            }

            // Proses update untuk semua ID yang valid
            if (!empty($attendanceIds)) {
                SubjectClassAttendance::whereIn('id', $attendanceIds)->update([
                    'status' => $status,
                    'check_in_time' => $status === 'hadir' ? now() : null,
                ]);

                // Reload data
                $this->loadAttendances();

                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => 'Status kehadiran siswa berhasil diperbarui (' . count($attendanceIds) . ' siswa)',
                ]);
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Tidak ada siswa yang diperbarui',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui status kehadiran: ' . $e->getMessage(),
            ]);
        }
    }

    // Method triggered when search or filters change
    public function updated($property)
    {
        if (in_array($property, ['search', 'statusFilter'])) {
            $this->loadAttendances();
        }
    }

    public function render(): mixed
    {
        // Get statistics for different status types
        $stats = [
            'total' => SubjectClassAttendance::where('subject_class_session_id', $this->sessionId)->count(),
            'hadir' => SubjectClassAttendance::where('subject_class_session_id', $this->sessionId)->where('status', 'hadir')->count(),
            'tidak_hadir' => SubjectClassAttendance::where('subject_class_session_id', $this->sessionId)->where('status', 'tidak_hadir')->count(),
            'sakit' => SubjectClassAttendance::where('subject_class_session_id', $this->sessionId)->where('status', 'sakit')->count(),
            'izin' => SubjectClassAttendance::where('subject_class_session_id', $this->sessionId)->where('status', 'izin')->count(),
        ];

        return view('livewire.admin.session-attendance', [
            'attendances' => $this->attendances,
            'stats' => $stats,
        ]);
    }
}; ?>

<!-- resources/views/livewire/admin/session-attendance.blade.php -->
<div class="mx-auto mt-10 max-w-7xl py-6 md:mt-0">
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

    <div class="mx-auto">
        <!-- Session Info Card -->
        <div class="mb-6 overflow-hidden rounded-lg bg-white shadow-md">
            <div class="px-5 py-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between">
                    <!-- Left Side (Title and Metadata) -->
                    <div class="space-y-3">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $subjectTitle }}</h3>

                        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:gap-x-6 sm:gap-y-2">
                            <!-- Teacher Info -->
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>

                                <span class="ml-1">{{ $session->subjectClass->user->name }}</span>
                            </div>

                            <!-- Class Info -->
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0 text-green-500"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                                </svg>

                                <span>{{ $className }} ({{ $classCode }})</span>
                            </div>

                            <!-- Date Info -->
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0 text-amber-500"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd" />
                                </svg>

                                <span>{{ \Carbon\Carbon::parse($classDate)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</span>
                            </div>

                            <!-- Time Info -->
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0 text-purple-500"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd" />
                                </svg>

                                <span>{{ \Carbon\Carbon::parse($startTime)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($endTime)->format('H:i') }}</span>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>

        <!-- Attendance Stats -->
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <!-- Total Siswa -->
            <div class="overflow-hidden rounded-lg bg-white shadow transition duration-200 hover:shadow-md">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="flex items-center text-sm font-medium text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-blue-500" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                        </svg>
                        Total Siswa
                    </dt>
                    <dd class="mt-2 text-3xl font-semibold text-gray-900">{{ $stats['total'] }}</dd>
                </div>
            </div>

            <!-- Hadir -->
            <div class="overflow-hidden rounded-lg bg-green-50 shadow transition duration-200 hover:shadow-md">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="flex items-center text-sm font-medium text-green-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-green-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        Hadir
                    </dt>
                    <dd class="mt-2 text-3xl font-semibold text-green-600">{{ $stats['hadir'] }}</dd>
                    <p class="mt-1 flex justify-start text-sm text-green-600 md:justify-end">
                        {{ number_format(($stats['hadir'] / ($stats['total'] > 0 ? $stats['total'] : 1)) * 100, 0) }}%
                    </p>
                </div>
            </div>

            <!-- Sakit/Izin -->
            <div class="overflow-hidden rounded-lg bg-yellow-50 shadow transition duration-200 hover:shadow-md">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="flex items-center text-sm font-medium text-yellow-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-yellow-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        Sakit/Izin
                    </dt>
                    <dd class="mt-2 text-3xl font-semibold text-yellow-600">{{ $stats['sakit'] + $stats['izin'] }}
                    </dd>
                    <p class="mt-1 flex justify-start text-sm text-yellow-600 md:justify-end">
                        {{ number_format((($stats['sakit'] + $stats['izin']) / ($stats['total'] > 0 ? $stats['total'] : 1)) * 100, 0) }}%
                    </p>
                </div>
            </div>

            <!-- Tidak Hadir -->
            <div class="overflow-hidden rounded-lg bg-red-50 shadow transition duration-200 hover:shadow-md">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="flex items-center text-sm font-medium text-red-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-red-600" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        Tidak Hadir
                    </dt>
                    <dd class="mt-2 text-3xl font-semibold text-red-600">{{ $stats['tidak_hadir'] }}</dd>
                    <p class="mt-1 flex justify-start text-sm text-red-600 md:justify-end">
                        {{ number_format(($stats['tidak_hadir'] / ($stats['total'] > 0 ? $stats['total'] : 1)) * 100, 0) }}%
                    </p>
                </div>
            </div>
        </div>

        <!-- Attendance Controls -->
        <div class="mb-6">
            <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                <!-- Search and Filter -->
                <div class="flex flex-col space-y-4 sm:flex-row sm:space-x-4 sm:space-y-0">
                    <div class="relative rounded-full shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama siswa..."
                            class="block w-full rounded-full border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <select wire:model.live="statusFilter"
                        class="block rounded-full border-gray-300 py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="hadir">Hadir</option>
                        <option value="tidak_hadir">Tidak Hadir</option>
                        <option value="sakit">Sakit</option>
                        <option value="izin">Izin</option>
                    </select>
                </div>


            </div>
        </div>

        <!-- Attendance List -->
        <div class="rounded-md bg-white shadow">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($attendances as $attendance)
                    <li>
                        <div class="flex items-center px-4 py-4 sm:px-6">
                            <div class="flex min-w-0 flex-1 items-center">
                                <div class="flex-shrink-0">
                                    <img src="{{ $attendance->student->user->profile_photo_path ? Storage::url($attendance->student->user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($attendance->student->user->name) }}"
                                        alt="{{ $attendance->student->user->name }}"
                                        class="mr-3 h-10 w-10 rounded-full object-cover">
                                </div>
                                <div class="ml-4 flex flex-col">
                                    <div class="truncate whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $attendance->student->user->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 md:text-sm">
                                        {{ $attendance->student->nisn }}
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4 hidden flex-shrink-0 md:flex">
                                @if ($attendance->check_in_time)
                                    <span class="text-sm text-gray-500">
                                        {{ Carbon\Carbon::parse($attendance->check_in_time)->translatedFormat('H:i') }}
                                    </span>
                                @endif
                            </div>
                            <div class="ml-4">
                                @switch($attendance->status)
                                    @case('hadir')
                                        <span
                                            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Hadir</span>
                                    @break

                                    @case('tidak_hadir')
                                        <span
                                            class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Tidak
                                            Hadir</span>
                                    @break

                                    @case('sakit')
                                        <span
                                            class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Sakit</span>
                                    @break

                                    @case('izin')
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Izin</span>
                                    @break
                                @endswitch
                            </div>

                        </div>
                    </li>
                    @empty
                        <li class="px-4 py-6 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data siswa</h3>
                            <p class="mt-1 text-sm text-gray-500">Tidak ada data siswa yang ditemukan untuk kriteria
                                pencarian
                                ini.</p>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
