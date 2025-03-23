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

        // Load attendances
        $this->loadAttendances();
    }

    public function loadAttendances()
    {
        $query = SubjectClassAttendance::where('subject_class_session_id', $this->sessionId)->with(['student.user']); // Eager loading untuk student dan user

        // Apply search filter if provided
        if (!empty($this->search)) {
            $query->whereHas('student', function ($q) {
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
    <div class="mt-10 w-full md:mt-0">
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
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
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
    </div>

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

    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
        <!-- Session Info Card -->
        <div class="mb-6 overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">{{ $className }}</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $classCode }}</p>
                    </div>
                    <div class="mt-3 flex flex-col sm:mt-0">
                        <p class="flex items-center text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            {{ \Carbon\Carbon::parse($classDate)->locale('id')->translatedFormat('l, d F Y') }}
                        </p>
                        <p class="flex items-center text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor"
                                class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
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
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Total Siswa</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total'] }}</dd>
                </div>
            </div>
            <div class="overflow-hidden rounded-lg bg-green-50 shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-green-800">Hadir</dt>
                    <dd class="mt-1 text-3xl font-semibold text-green-600">{{ $stats['hadir'] }}</dd>
                </div>
            </div>
            <div class="overflow-hidden rounded-lg bg-yellow-50 shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-yellow-800">Sakit/Izin</dt>
                    <dd class="mt-1 text-3xl font-semibold text-yellow-600">{{ $stats['sakit'] + $stats['izin'] }}</dd>
                </div>
            </div>
            <div class="overflow-hidden rounded-lg bg-red-50 shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-red-800">Tidak Hadir</dt>
                    <dd class="mt-1 text-3xl font-semibold text-red-600">{{ $stats['tidak_hadir'] }}</dd>
                </div>
            </div>
        </div>

        <!-- Manual Attendance Instructions -->
        <div class="mb-6 rounded-lg bg-blue-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Panduan Absensi Manual</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Silakan tandai status kehadiran siswa menggunakan menu yang tersedia di samping nama
                            masing-masing siswa. Anda juga dapat menggunakan menu "Tindakan Massal" untuk menandai semua
                            siswa sekaligus.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Controls -->
        <div class="mb-6">
            <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                <!-- Search and Filter -->
                <div class="flex flex-col space-y-4 sm:flex-row sm:space-x-4 sm:space-y-0">
                    <div class="relative rounded-md shadow-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama siswa..."
                            class="block w-full rounded-md border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <select wire:model.live="statusFilter"
                        class="block rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        <option value="">Semua Status</option>
                        <option value="hadir">Hadir</option>
                        <option value="tidak_hadir">Tidak Hadir</option>
                        <option value="sakit">Sakit</option>
                        <option value="izin">Izin</option>
                    </select>
                </div>

                <!-- Bulk Actions -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Tindakan Massal
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="-mr-1 ml-2 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false"
                        class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                        <div class="py-1">
                            <a href="#" wire:click.prevent="bulkUpdateStatus('hadir')"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai Semua Hadir</a>
                            <a href="#" wire:click.prevent="bulkUpdateStatus('tidak_hadir')"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai Semua Tidak
                                Hadir</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
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
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $attendance['student']['user']['name'] }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $attendance['student']['nisn'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4 flex flex-shrink-0">
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
                                <div x-data="{ open: false }" class="ml-4">
                                    <button @click="open = !open" type="button"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.away="open = false"
                                        :class="{
                                            'right-0 left-auto': window.innerWidth > 640,
                                            'right-0 left-auto': window
                                                .innerWidth <= 640
                                        }"
                                        style="max-width: 90vw;"
                                        class="absolute z-10 mt-2 w-48 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <div class="py-1">
                                            <a href="#"
                                                wire:click.prevent="updateStatus({{ $attendance['id'] }}, 'hadir')"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai
                                                Hadir</a>
                                            <a href="#"
                                                wire:click.prevent="updateStatus({{ $attendance['id'] }}, 'tidak_hadir')"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai
                                                Tidak Hadir</a>
                                            <a href="#"
                                                wire:click.prevent="updateStatus({{ $attendance['id'] }}, 'sakit')"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Tandai
                                                Sakit</a>
                                            <a href="#"
                                                wire:click.prevent="updateStatus({{ $attendance['id'] }}, 'izin')"
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
