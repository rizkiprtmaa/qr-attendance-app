<?php

use Livewire\Volt\Component;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use App\Models\Student;
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
    public $attendances = [];

    // Filter properties
    public $search = '';
    public $statusFilter = '';

    public function mount(SubjectClassSession $session)
    {
        $this->session = $session;
        $this->sessionId = $session->id;
        $this->fill($session->toArray());
        $this->subjectTitle = $session->subject_title;
        $this->classDate = $session->class_date;
        $this->startTime = $session->start_time;
        $this->endTime = $session->end_time;
        $this->subjectClassId = $session->subject_class_id;

        // Get class details
        $subjectClass = $session->subjectClass;
        $this->className = $subjectClass->class_name;
        $this->classCode = $subjectClass->class_code;

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
                $q->where('name', 'like', '%' . $this->search . '%')->orWhere('student_id', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter if provided
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Tidak mengubah ke array
        $this->attendances = $query->get();
    }

    // Method to update attendance status manually by teacher
    public function updateStatus($attendanceId, $status)
    {
        try {
            $attendance = SubjectClassAttendance::findOrFail($attendanceId);
            $attendance->update([
                'status' => $status,
                'check_in_time' => $status === 'hadir' ? now() : $attendance->check_in_time,
            ]);

            // Reload attendances after update
            $this->loadAttendances();

            session()->flash('success', 'Status presensi berhasil diperbarui');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memperbarui status presensi: ' . $e->getMessage());
        }
    }

    // Method to handle bulk update
    public function bulkUpdateStatus($status)
    {
        try {
            SubjectClassAttendance::where('subject_class_session_id', $this->sessionId)->update([
                'status' => $status,
                'check_in_time' => $status === 'hadir' ? now() : null,
            ]);

            // Reload attendances after bulk update
            $this->loadAttendances();

            session()->flash('success', 'Status presensi semua siswa berhasil diperbarui');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memperbarui status presensi: ' . $e->getMessage());
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

        return view('livewire.teacher.subject-class-attendance', [
            'attendances' => $this->attendances,
            'stats' => $stats,
        ]);
    }
}; ?>


<div>
    {{-- <div class="mt-10 w-full md:mt-0">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-inter text-xl font-medium leading-tight text-gray-800">
                            {{ $subjectTitle }}
                        </h2>
                        <p class="font-inter text-sm text-gray-600">Kelola absensi siswa pada pertemuan ini</p>
                    </div>
                    <a href="{{ route('subject.detail', $subjectClassId) }}" wire:navigate
                        class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-2 h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Success Message Toast -->
    @if (session()->has('success'))
        <div id="toast-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
            class="fixed bottom-5 right-5 z-10 mb-4 flex w-full max-w-xs items-center rounded-lg bg-white p-4 text-gray-500 shadow"
            role="alert">
            <div
                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-500">
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                </svg>
                <span class="sr-only">Success icon</span>
            </div>
            <div class="ml-3 text-sm font-normal">{{ session('success') }}</div>
            <button type="button" @click="show = false"
                class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900">
                <span class="sr-only">Close</span>
                <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Error Message Toast -->
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-5 right-5 z-10 rounded-md border border-red-400 bg-red-100 px-4 py-3 text-red-700"
            role="alert">
            {{ session('error') }}
            <button type="button" @click="show = false"
                class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg p-1.5 text-red-500 hover:bg-red-200">
                <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    <div class="mx-auto mt-10 max-w-7xl px-2 py-3 md:mt-0 md:px-4 lg:px-8">
        <!-- Session Info Card -->
        <div class="mb-6 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-inter text-lg font-medium leading-6 text-gray-900">{{ $subjectTitle }}</h3>
                        <p class="mt-1 max-w-2xl font-inter text-sm text-gray-500">{{ $className }}</p>
                        <p class="mt-1 max-w-2xl font-inter text-sm text-gray-500">{{ $studentClassName }} -
                            {{ $majorName }}</p>

                    </div>
                    <div class="mt-3 flex flex-col sm:mt-0">
                        <p class="flex items-center text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                class="mr-1 h-4 w-4">
                                <path fill-rule="evenodd"
                                    d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z"
                                    clip-rule="evenodd" />
                            </svg>

                            {{ \Carbon\Carbon::parse($classDate)->locale('id')->translatedFormat('l, d F Y') }}
                        </p>
                        <p class="mt-1 flex items-center text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                class="mr-1 h-4 w-4">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z"
                                    clip-rule="evenodd" />
                            </svg>

                            {{ \Carbon\Carbon::parse($startTime)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($endTime)->format('H:i') }}
                        </p>
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
                    <dd class="mt-2 text-3xl font-semibold text-yellow-600">{{ $stats['sakit'] + $stats['izin'] }}</dd>
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

                <!-- Bulk Actions -->
                <div x-data="{ bulkActionOpen: false }" class="relative flex items-center gap-3">
                    <a href="{{ route('attendance.pdf', $sessionId) }}" target="_blank"
                        class="inline-flex items-center rounded-full bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-md hover:bg-green-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="mr-2 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Unduh PDF
                    </a>
                    <div class="relative">
                        <button @click="bulkActionOpen = !bulkActionOpen" type="button"
                            class="inline-flex justify-center rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            Tindakan Massal
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="-mr-1 ml-2 h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-cloak x-show="bulkActionOpen" @click.away="bulkActionOpen = false"
                            class="absolute bottom-full right-0 z-50 mt-2 w-48 origin-bottom-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95">
                            <div class="py-1">
                                <a href="#" wire:click.prevent="bulkUpdateStatus('hadir')"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai Semua
                                    Hadir</a>
                                <a href="#" wire:click.prevent="bulkUpdateStatus('tidak_hadir')"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai Semua Tidak
                                    Hadir</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="rounded-md bg-white shadow">
            @if (count($attendances) > 0)
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach ($attendances as $attendance)
                        <li>
                            <div class="flex items-center px-4 py-4 sm:px-6">
                                <div class="flex min-w-0 flex-1 items-center">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="h-6 w-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex flex-col">
                                        <div class="truncate whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $attendance['student']['user']['name'] }}
                                        </div>
                                        <div class="text-xs text-gray-500 md:text-sm">
                                            {{ $attendance['student']['nisn'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4 hidden flex-shrink-0 md:flex">
                                    @if ($attendance['check_in_time'])
                                        <span
                                            class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($attendance['check_in_time'])->timezone('Asia/Jakarta')->format('H:i') }}</span>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    @switch($attendance['status'])
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
                                <div x-data="{ open: false }" class="relative ml-4">
                                    <button @click="open = !open" type="button"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.away="open = false" x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute bottom-full right-0 z-50 mb-2 w-48 origin-bottom-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                        style="position: absolute; bottom: 100%; right: 0; margin-bottom: 0.5rem;">
                                        <div class="py-1">
                                            <a href="#"
                                                wire:click.prevent="updateStatus({{ $attendance['id'] }}, 'hadir'); open = false"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai
                                                Hadir</a>
                                            <a href="#"
                                                wire:click.prevent="updateStatus({{ $attendance['id'] }}, 'tidak_hadir'); open = false"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai
                                                Tidak Hadir</a>
                                            <a href="#"
                                                wire:click.prevent="updateStatus({{ $attendance['id'] }}, 'sakit'); open = false"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai
                                                Sakit</a>
                                            <a href="#"
                                                wire:click.prevent="updateStatus({{ $attendance['id'] }}, 'izin'); open = false"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai
                                                Izin</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="py-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mx-auto h-12 w-12 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data siswa</h3>
                    <p class="mt-1 text-sm text-gray-500">Tidak ada data siswa yang ditemukan untuk kriteria pencarian
                        ini.</p>
                </div>
            @endif


        </div>
    </div>
</div>
