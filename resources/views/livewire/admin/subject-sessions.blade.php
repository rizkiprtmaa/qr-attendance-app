<?php
// resources/views/livewire/admin/subject-sessions.php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\Student;
use Carbon\Carbon;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $subjectClass;
    public $subjectClassId;
    public $search = '';
    public $dateFilter = '';

    // Form properties
    // public $showCreateModal = false;
    public $subjectTitle = '';
    public $classDate = '';
    public $startTime = '';
    public $endTime = '';
    public $jamPelajaran = '';

    // Edit properties
    public $editSessionId = null;
    public $isEditing = false;

    // Delete confirmation
    public $deleteSessionId = null;

    public function mount($subjectClassId)
    {
        $this->subjectClassId = $subjectClassId;
        $this->subjectClass = SubjectClass::with(['classes.major', 'user'])->findOrFail($subjectClassId);
        $this->classDate = date('Y-m-d');
    }

    // Edit Session
    public function editSession($sessionId)
    {
        $this->isEditing = true;
        $this->editSessionId = $sessionId;

        $session = SubjectClassSession::findOrFail($sessionId);

        $this->subjectTitle = $session->subject_title;
        $this->classDate = Carbon::parse($session->class_date)->format('Y-m-d');
        $this->startTime = $session->start_time;
        $this->endTime = $session->end_time;
        $this->jamPelajaran = $session->jam_pelajaran;
    }

    public function updateSession()
    {
        $this->validate([
            'subjectTitle' => 'required|string|max:255',
            'classDate' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'jamPelajaran' => 'required|numeric|min:1',
        ]);

        try {
            $session = SubjectClassSession::findOrFail($this->editSessionId);

            // Format class date with the start time to create a proper datetime
            $classDateTime = Carbon::parse($this->classDate . ' ' . $this->startTime)->setTimezone('Asia/Jakarta');

            $session->update([
                'subject_title' => $this->subjectTitle,
                'class_date' => $classDateTime,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'jam_pelajaran' => $this->jamPelajaran,
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Sesi pertemuan berhasil diperbarui',
            ]);
            $this->resetForm();
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui sesi pertemuan: ' . $e->getMessage(),
            ]);
        }
    }

    // Delete Session
    public function confirmDelete($sessionId)
    {
        $this->deleteSessionId = $sessionId;
    }

    public function deleteSession()
    {
        try {
            $session = SubjectClassSession::findOrFail($this->deleteSessionId);

            // Delete attendance records first (manage the foreign key constraint)
            \App\Models\SubjectClassAttendance::where('subject_class_session_id', $this->deleteSessionId)->delete();

            // Delete the session
            $session->delete();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Sesi pertemuan berhasil dihapus',
            ]);
            $this->deleteSessionId = null;
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal menghapus sesi pertemuan: ' . $e->getMessage(),
            ]);
        }
    }

    public function cancelDelete()
    {
        $this->deleteSessionId = null;
    }

    public function resetForm()
    {
        $this->subjectTitle = '';
        $this->classDate = date('Y-m-d');
        $this->startTime = '';
        $this->endTime = '';
        $this->jamPelajaran = '';
        $this->editSessionId = null;
        $this->isEditing = false;
        $this->resetErrorBag();
    }

    public function createSession()
    {
        $this->validate([
            'subjectTitle' => 'required|string|max:255',
            'classDate' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'jamPelajaran' => 'required|numeric|min:1',
        ]);

        try {
            // Format class date with the start time to create a proper datetime
            $classDateTime = Carbon::parse($this->classDate . ' ' . $this->startTime)->setTimezone('Asia/Jakarta');
            $sessionDate = $classDateTime->toDateString();

            // Create the session
            $session = SubjectClassSession::create([
                'subject_class_id' => $this->subjectClassId,
                'subject_title' => $this->subjectTitle,
                'class_date' => $classDateTime,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'jam_pelajaran' => $this->jamPelajaran,
            ]);

            // Get all students in this class
            $students = Student::whereHas('classes', function ($query) {
                $query->where('id', $this->subjectClass->classes_id);
            })->get();

            // Create attendance records for each student in this session
            foreach ($students as $student) {
                // Check if student has approved permission for this date
                $permissionExists = \App\Models\PermissionSubmission::where('user_id', $student->user_id)->whereDate('permission_date', $sessionDate)->where('status', 'approved')->first();

                if ($permissionExists) {
                    // Jika siswa memiliki izin yang disetujui, atur status sesuai tipe izin (izin/sakit)
                    \App\Models\SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => $permissionExists->type, // 'izin' or 'sakit'
                        'check_in_time' => $classDateTime,
                    ]);
                } else {
                    // Jika tidak ada izin, set default status 'tidak_hadir'
                    \App\Models\SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => 'tidak_hadir', // Default status
                        'check_in_time' => null,
                    ]);
                }
            }

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Sesi pertemuan berhasil dibuat',
            ]);
            $this->resetForm();
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal membuat sesi pertemuan: ' . $e->getMessage(),
            ]);
        }
    }

    public function getSessionsProperty()
    {
        return SubjectClassSession::where('subject_class_id', $this->subjectClassId)
            ->when($this->search, function ($query, $search) {
                return $query->where('subject_title', 'like', "%{$search}%");
            })
            ->when($this->dateFilter, function ($query, $dateFilter) {
                return $query->whereDate('class_date', $dateFilter);
            })
            ->orderBy('class_date', 'desc')
            ->paginate(10);
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->dateFilter = '';
    }

    public function render(): mixed
    {
        // Get statistics
        $studentCount = Student::whereHas('classes', function ($query) {
            $query->where('id', $this->subjectClass->classes_id);
        })->count();

        $sessionsCount = SubjectClassSession::where('subject_class_id', $this->subjectClassId)->count();

        $totalSubstitute = SubjectClassSession::where('subject_class_id', $this->subjectClassId)->whereNotNull('created_by_substitute')->count();
        $totalJP = SubjectClassSession::where('subject_class_id', $this->subjectClassId)->whereNull('created_by_substitute')->sum('jam_pelajaran');

        return view('livewire.admin.subject-sessions', [
            'sessions' => $this->sessions,
            'studentCount' => $studentCount,
            'sessionsCount' => $sessionsCount,
            'totalJP' => $totalJP,
            'totalSubstitute' => $totalSubstitute,
        ]);
    }
}; ?>

<!-- resources/views/livewire/admin/subject-sessions.blade.php -->
<div class="mt-12 py-6 md:mt-0" x-data="{ showCreateModal: false, showEditModal: false, showDeleteModal: false }">
    <div class="mx-auto max-w-7xl">
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

        <!-- Subject Class Header Card -->
        <div class="mb-6 overflow-hidden rounded-lg bg-white shadow-md transition-shadow duration-300 hover:shadow-lg">
            <div class="px-5 py-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-3">
                        <h3 class="font-inter text-2xl font-semibold tracking-tight text-gray-900">
                            {{ $subjectClass->class_name }}
                        </h3>
                        <div class="flex flex-row gap-x-6 gap-y-2 sm:flex-row sm:flex-wrap md:flex-col">
                            <!-- Class Code -->
                            <div class="hidden items-center font-inter text-sm text-gray-600 md:flex">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.727 1.169 1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zm9.3 7.176A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                                </svg>
                                <span class="font-medium">{{ $subjectClass->class_code }}</span>
                            </div>

                            <!-- Class and Major -->
                            <div class="flex items-center font-inter text-sm text-gray-600">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0 text-green-500"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1.581.814L10 13.197l-4.419 2.617A1 1 0 014 15V4zm2-1a1 1 0 00-1 1v10.566l3.419-2.021a1 1 0 011.162 0L13 14.566V4a1 1 0 00-1-1H6z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>{{ $subjectClass->classes->name }} -
                                    {{ $subjectClass->classes->major->name }}</span>
                            </div>

                            <!-- Teacher Name -->
                            <div class="flex items-center truncate font-inter text-sm text-gray-600">
                                <svg class="mr-2 h-5 w-5 flex-shrink-0 text-purple-500"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="truncate">{{ $subjectClass->user->name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Create Meeting Button -->
                    <div class="mt-5 sm:mt-0">
                        <button type="button" @click="showCreateModal = true"
                            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2.5 font-inter text-xs font-medium text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 h-4 w-4 md:h-5 md:w-5"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            Buat Pertemuan
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="mb-6 hidden grid-cols-2 gap-5 md:grid md:grid-cols-4">
            <!-- Total Siswa -->
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Total Siswa</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $studentCount }}</dd>
            </div>

            <!-- Total Pertemuan -->
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Total Pertemuan</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $sessionsCount }}</dd>
            </div>

            <!-- Total JP -->
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Total JP</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalJP }}</dd>
            </div>

            <!-- Total Kelas Digantikan -->
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Kelas Digantikan</dt>
                <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalSubstitute }}</dd>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="mb-6 sm:flex sm:items-center sm:justify-between">
            <div class="mt-3 flex flex-col gap-3 md:flex-row">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="block w-full rounded-md border-gray-300 pl-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 md:w-72"
                        placeholder="Cari pertemuan...">
                </div>
                <div class="flex flex-row items-center gap-3 md:mt-0">
                    <!-- Modal Filter Bulan untuk Laporan -->
                    <div x-data="{ monthFilterModal: false, reportType: null }" x-on:keydown.esc.window="monthFilterModal = false">
                        <!-- Tombol unduh dengan onclick yang memicu modal -->
                        <div class="flex flex-row items-center gap-2">
                            <button @click="monthFilterModal = true; reportType = 'agenda'"
                                class="inline-flex items-center rounded-lg border border-blue-600 px-4 py-2 font-inter text-xs font-medium text-blue-600 ring-blue-300 transition duration-150 ease-in-out hover:bg-blue-700 hover:text-white focus:border-blue-900 focus:outline-none focus:ring active:bg-blue-900 disabled:opacity-25">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Agenda KBM
                            </button>
                            <button @click="monthFilterModal = true; reportType = 'attendance'"
                                class="flex items-center rounded-lg border border-transparent bg-green-600 px-4 py-2 font-inter text-xs font-medium text-white ring-green-300 transition duration-150 ease-in-out hover:bg-green-700 focus:border-green-900 focus:outline-none focus:ring active:bg-green-900 disabled:opacity-25">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                                Laporan Kehadiran
                            </button>
                        </div>

                        <!-- Modal Pemilihan Bulan -->
                        <div x-cloak x-show="monthFilterModal" x-transition.opacity.duration.200ms
                            x-on:click.self="monthFilterModal = false"
                            class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8"
                            role="dialog" aria-modal="true">

                            <!-- Modal Dialog -->
                            <div x-show="monthFilterModal"
                                x-transition:enter="transition ease-out duration-200 delay-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">

                                <!-- Dialog Header -->
                                <div class="bg-blue-50 px-6 py-4">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <span
                                            x-text="reportType === 'agenda' ? 'Unduh Agenda KBM' : 'Unduh Laporan Kehadiran'"></span>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">Pilih periode laporan yang ingin diunduh.</p>
                                </div>

                                <!-- Dialog Body -->
                                <div class="px-6 py-4">
                                    <form x-data="{ month: new Date().getMonth() + 1, year: new Date().getFullYear() }" x-ref="monthFilterForm" method="GET"
                                        :action="reportType === 'agenda' ? '{{ route('agenda.report', $subjectClass->id) }}' :
                                            '{{ route('attendance.report', $subjectClass->id) }}'">

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="mb-4">
                                                <label for="month"
                                                    class="block text-sm font-medium text-gray-700">Bulan</label>
                                                <select x-model="month" name="month" id="month"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
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
                                            <div class="mb-4">
                                                <label for="year"
                                                    class="block text-sm font-medium text-gray-700">Tahun</label>
                                                <select x-model="year" name="year" id="year"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                    @for ($y = date('Y') - 2; $y <= date('Y'); $y++)
                                                        <option value="{{ $y }}">{{ $y }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Dialog Footer -->
                                        <div
                                            class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                                            <a href="#"
                                                @click.prevent="monthFilterModal = false; 
                           window.location.href = reportType === 'agenda' 
                               ? '{{ route('agenda.report', $subjectClass->id) }}'
                               : '{{ route('attendance.report', $subjectClass->id) }}';"
                                                class="text-sm font-medium text-blue-600 hover:text-blue-700">
                                                Unduh semua periode
                                            </a>
                                            <div>
                                                <button type="button" @click="monthFilterModal = false"
                                                    class="mr-3 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                                    Batal
                                                </button>
                                                <button type="submit" @click="monthFilterModal = false"
                                                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                                                    Unduh
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="mt-3 hidden sm:mt-0 sm:items-center md:flex">
                <div>
                    <label for="date-filter" class="sr-only">Filter Tanggal</label>
                    <input type="date" wire:model.live="dateFilter" id="date-filter"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <button type="button" wire:click="clearFilters"
                    class="ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 h-4 w-4" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Reset
                </button>
            </div>
        </div>

        <!-- Sessions List -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($sessions as $session)
                    <li>
                        <div class="flex items-center px-4 py-4 sm:px-6">
                            <div class="flex min-w-0 flex-1 items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 px-4">
                                    <div>
                                        <p class="truncate font-inter text-sm font-medium text-gray-900">
                                            {{ $session->subject_title }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <div class="flex items-center font-inter text-sm text-gray-600">
                                                <svg class="mr-1 h-4 w-4 text-gray-500" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>

                                                <span
                                                    class="ml-1">{{ Carbon::parse($session->class_date)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</span>
                                            </div>

                                            <div class="flex items-center font-inter text-sm text-gray-600">
                                                <svg class="mr-1 h-4 w-4 text-gray-500" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>

                                                <span
                                                    class="ml-1">{{ Carbon::parse($session->start_time)->format('H:i') }}
                                                    -
                                                    {{ Carbon::parse($session->end_time)->format('H:i') }}</span>
                                            </div>

                                            <div class="flex items-center font-inter text-sm text-gray-600">
                                                <svg class="mr-1 h-4 w-4 text-gray-500" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>

                                                <span class="ml-1">{{ $session->jam_pelajaran }} JP</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <!-- View Button -->
                                <a href="{{ route('admin.session.attendance', $session->id) }}" wire:navigate
                                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-2 py-2 font-inter text-sm text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </a>

                                <!-- Edit Button -->
                                <button wire:click="editSession({{ $session->id }})" @click="showEditModal = true"
                                    class="inline-flex items-center rounded-md border border-transparent bg-amber-500 px-2 py-2 font-inter text-sm text-white shadow-sm hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                </button>

                                <!-- Delete Button -->
                                <button wire:click="confirmDelete({{ $session->id }})"
                                    @click="showDeleteModal = true"
                                    class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-2 py-2 font-inter text-sm text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada pertemuan</h3>
                        <p class="mt-1 text-sm text-gray-500">Belum ada pertemuan yang dibuat untuk mata pelajaran ini.
                        </p>
                        <div class="mt-6">
                            <button type="button" @click="showCreateModal = true"
                                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Buat Pertemuan
                            </button>
                        </div>
                    </li>
                @endforelse
            </ul>

            <!-- Pagination -->
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                {{ $sessions->links() }}
            </div>
        </div>

        <!-- Create Session Modal -->
        <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-10 text-center sm:block sm:p-0">
                <div x-show="showCreateModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div x-show="showCreateModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white p-6 text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button type="button" @click="showCreateModal = false"
                            class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span class="sr-only">Tutup</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-col items-start">
                        <div class="flex flex-row items-center gap-3">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Buat
                                    Pertemuan
                                    Baru</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Buat sesi pertemuan baru untuk
                                        {{ $subjectClass->class_name }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="ml-3 mt-3 w-full text-start sm:mt-0 sm:text-left">

                            <form class="mr-5 mt-5" wire:submit.prevent="createSession">
                                <div class="mb-4">
                                    <label for="subjectTitle" class="block text-sm font-medium text-gray-700">Judul
                                        Pertemuan</label>
                                    <input type="text" wire:model="subjectTitle" id="subjectTitle"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Contoh: Pertemuan 1 - Pengenalan">
                                    @error('subjectTitle')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="classDate" class="block text-sm font-medium text-gray-700">Tanggal
                                        Pertemuan</label>
                                    <input type="date" wire:model="classDate" id="classDate"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @error('classDate')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="mb-4">
                                        <label for="startTime" class="block text-sm font-medium text-gray-700">Jam
                                            Mulai</label>
                                        <input type="time" wire:model="startTime" id="startTime"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('startTime')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="mb-4">
                                        <label for="endTime" class="block text-sm font-medium text-gray-700">Jam
                                            Selesai</label>
                                        <input type="time" wire:model="endTime" id="endTime"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('endTime')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="jamPelajaran" class="block text-sm font-medium text-gray-700">Jumlah
                                        JP</label>
                                    <input type="number" wire:model="jamPelajaran" id="jamPelajaran"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        min="1" placeholder="Contoh: 2">
                                    @error('jamPelajaran')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                            x-on:show-toast.window="if ($event.detail[0].type === 'success') showCreateModal = false">
                            <span wire:loading.remove wire:target="createSession">Tambah Mapel</span>
                            <span wire:loading wire:target="createSession" class="inline-flex items-center gap-1">
                                <svg class="inline h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="inline-flex text-xs">Menyimpan...</span>
                            </span>
                        </button>
                        <button type="button" @click="showCreateModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Session Modal -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div x-show="showEditModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div x-show="showEditModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block w-full max-w-lg transform overflow-hidden rounded-lg bg-white p-6 text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button type="button" @click="showEditModal = false"
                            class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span class="sr-only">Tutup</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-col items-start">
                        <div class="flex flex-row items-center gap-3">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Edit
                                    Pertemuan</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Perbarui informasi pertemuan</p>
                                </div>
                            </div>
                        </div>
                        <div class="ml-3 mt-3 w-full text-start sm:mt-0 sm:text-left">

                            <form class="mr-5 mt-5" wire:submit.prevent="updateSession">
                                <div class="mb-4">
                                    <label for="subjectTitle" class="block text-sm font-medium text-gray-700">Judul
                                        Pertemuan</label>
                                    <input type="text" wire:model="subjectTitle" id="subjectTitle"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Contoh: Pertemuan 1 - Pengenalan">
                                    @error('subjectTitle')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="classDate" class="block text-sm font-medium text-gray-700">Tanggal
                                        Pertemuan</label>
                                    <input type="date" wire:model="classDate" id="classDate"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @error('classDate')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="mb-4">
                                        <label for="startTime" class="block text-sm font-medium text-gray-700">Jam
                                            Mulai</label>
                                        <input type="time" wire:model="startTime" id="startTime"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('startTime')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="mb-4">
                                        <label for="endTime" class="block text-sm font-medium text-gray-700">Jam
                                            Selesai</label>
                                        <input type="time" wire:model="endTime" id="endTime"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('endTime')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="jamPelajaran" class="block text-sm font-medium text-gray-700">Jumlah
                                        JP</label>
                                    <input type="number" wire:model="jamPelajaran" id="jamPelajaran"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        min="1" placeholder="Contoh: 2">
                                    @error('jamPelajaran')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                    <button type="submit"
                                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                                        x-on:show-toast.window="if ($event.detail[0].type === 'success') showEditModal = false">
                                        <span wire:loading.remove wire:target="updateSession">Perbarui Pertemuan</span>
                                        <span wire:loading wire:target="updateSession"
                                            class="inline-flex items-center gap-1">
                                            <svg class="inline h-4 w-4 animate-spin text-white"
                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <span class="inline-flex text-xs">Memperbarui...</span>
                                        </span>
                                    </button>
                                    <button type="button" @click="showEditModal = false"
                                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div x-show="showDeleteModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                Hapus Pertemuan
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Apakah Anda yakin ingin menghapus pertemuan ini? Semua data presensi terkait
                                    pertemuan ini akan dihapus. Tindakan ini tidak dapat dibatalkan.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button wire:click="deleteSession" type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                            x-on:show-toast.window="if ($event.detail[0].type === 'success') showDeleteModal = false">
                            <span wire:loading.remove wire:target="deleteSession">Hapus</span>
                            <span wire:loading wire:target="deleteSession" class="inline-flex items-center gap-1">
                                <svg class="inline h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="inline-flex text-xs">Menghapus...</span>
                            </span>
                        </button>
                        <button wire:click="cancelDelete" @click="showDeleteModal = false" type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
