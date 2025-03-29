<?php

use Livewire\Volt\Component;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use App\Models\Classes;
use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $subjectClass;
    public $subjectClassId;
    public $subjectName;
    public $subjectCode;
    public $className;
    public $major;
    public $classesId;

    // Form fields
    #[Rule('required', message: 'Judul pertemuan harus diisi')]
    public $subjectTitle;

    #[Rule('required', message: 'Tanggal pertemuan harus diisi')]
    public $classDate;

    #[Rule('required', message: 'Jam mulai harus diisi')]
    public $startTime;

    #[Rule('required', message: 'Jam selesai harus diisi')]
    public $endTime;

    // Form edit
    public $editSessionId;
    public $editSubjectTitle;
    public $editClassDate;
    public $editStartTime;
    public $editEndTime;

    // Store sessions
    public $sessions = [];

    // Search and filter
    public $search = '';
    public $dateFilter = '';
    public $statusFilter = '';

    // Sorting
    public $sortField = 'class_date';
    public $sortDirection = 'desc';

    public function mount(SubjectClass $subjectClass)
    {
        $this->subjectClass = $subjectClass;
        $this->fill($subjectClass->toArray());
        $this->subjectClassId = $subjectClass->id;
        $this->subjectName = $subjectClass->class_name;
        $this->subjectCode = $subjectClass->class_code;
        $this->className = $subjectClass->classes->name;
        $this->major = $subjectClass->classes->major->name;
        $this->classesId = $subjectClass->classes->id;

        // Pastikan properti default untuk search dan filter sudah diinisialisasi
        $this->search = '';
        $this->dateFilter = '';

        // Load existing sessions
        $this->loadSessions();
    }

    public function createSession()
    {
        $this->validate();

        try {
            // Format class date with the start time to create a proper datetime
            $classDateTime = \Carbon\Carbon::parse($this->classDate . ' ' . $this->startTime);
            $sessionDate = $classDateTime->toDateString();

            // Create the session
            $session = SubjectClassSession::create([
                'subject_class_id' => $this->subjectClassId,
                'subject_title' => $this->subjectTitle,
                'class_date' => $classDateTime,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
            ]);

            // Get all students in this class
            $students = Student::whereHas('classes', function ($query) {
                $query->where('id', $this->classesId);
            })->get();

            // Create attendance records for each student in this session
            foreach ($students as $student) {
                // Check if student has approved permission for this date
                $permissionExists = \App\Models\PermissionSubmission::where('user_id', $student->user_id)->whereDate('permission_date', $sessionDate)->where('status', 'approved')->first();

                if ($permissionExists) {
                    // Jika siswa memiliki izin yang disetujui, atur status sesuai tipe izin (izin/sakit)
                    SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => $permissionExists->type, // 'izin' or 'sakit'
                        'check_in_time' => $classDateTime,
                    ]);
                } else {
                    // Jika tidak ada izin, set default status 'tidak_hadir'
                    SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => 'tidak_hadir', // Default status
                        'check_in_time' => null,
                    ]);
                }
            }

            // Reset form fields
            $this->reset(['subjectTitle', 'classDate', 'startTime', 'endTime']);

            // Reload sessions
            $this->loadSessions();

            // Show success message
            $this->dispatch('show-toast', type: 'success', message: 'Sesi pertemuan berhasil dibuat');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Gagal membuat sesi pertemuan: ' . $e->getMessage());
        }
    }

    // Load edit session form data
    public function editSession($sessionId)
    {
        $session = SubjectClassSession::findOrFail($sessionId);

        $this->editSessionId = $sessionId;
        $this->editSubjectTitle = $session->subject_title;
        $this->editClassDate = \Carbon\Carbon::parse($session->class_date)->format('Y-m-d');
        $this->editStartTime = $session->start_time;
        $this->editEndTime = $session->end_time;
    }

    // Update session
    public function updateSession()
    {
        $this->validate(
            [
                'editSubjectTitle' => 'required',
                'editClassDate' => 'required',
                'editStartTime' => 'required',
                'editEndTime' => 'required',
            ],
            [
                'editSubjectTitle.required' => 'Judul pertemuan harus diisi',
                'editClassDate.required' => 'Tanggal pertemuan harus diisi',
                'editStartTime.required' => 'Jam mulai harus diisi',
                'editEndTime.required' => 'Jam selesai harus diisi',
            ],
        );

        try {
            $session = SubjectClassSession::findOrFail($this->editSessionId);

            // Format class date with the start time to create a proper datetime
            $classDateTime = \Carbon\Carbon::parse($this->editClassDate . ' ' . $this->editStartTime);

            $session->update([
                'subject_title' => $this->editSubjectTitle,
                'class_date' => $classDateTime,
                'start_time' => $this->editStartTime,
                'end_time' => $this->editEndTime,
            ]);

            // Reset form fields
            $this->reset(['editSessionId', 'editSubjectTitle', 'editClassDate', 'editStartTime', 'editEndTime']);

            // Reload sessions
            $this->loadSessions();

            // Show success message
            session()->flash('success', 'Sesi pertemuan berhasil diperbarui');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memperbarui sesi pertemuan: ' . $e->getMessage());
        }
    }

    // Delete session confirmation
    public function confirmDeleteSession($sessionId)
    {
        $this->editSessionId = $sessionId;
    }

    // Delete session and all related attendances
    public function deleteSession()
    {
        try {
            $session = SubjectClassSession::findOrFail($this->editSessionId);

            // Delete all related attendances first
            SubjectClassAttendance::where('subject_class_session_id', $this->editSessionId)->delete();

            // Delete the session
            $session->delete();

            // Reset form fields
            $this->reset('editSessionId');

            // Reload sessions
            $this->loadSessions();

            // Show success message
            session()->flash('success', 'Sesi pertemuan berhasil dihapus');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menghapus sesi pertemuan: ' . $e->getMessage());
        }
    }

    public function loadSessions()
    {
        $query = SubjectClassSession::where('subject_class_id', $this->subjectClassId);

        // Apply search filter
        if (!empty($this->search)) {
            $query->where('subject_title', 'like', '%' . $this->search . '%');
        }

        // Apply date filter
        if (!empty($this->dateFilter)) {
            $query->whereDate('class_date', $this->dateFilter);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $this->sessions = $query->get()->toArray();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->loadSessions();
    }

    public function updatedSearch()
    {
        $this->loadSessions();
    }

    public function updatedDateFilter()
    {
        $this->loadSessions();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->dateFilter = '';
        $this->loadSessions();
    }

    // Existing methods...

    public function render(): mixed
    {
        // Get students count for the current class
        $studentsCount = Student::whereHas('classes', function ($query) {
            $query->where('id', $this->classesId);
        })->count();

        // Calculate total hours based on sessions
        $totalHours = 0;
        $sessionsQuery = SubjectClassSession::where('subject_class_id', $this->subjectClassId);

        foreach ($sessionsQuery->get() as $session) {
            $start = \Carbon\Carbon::parse($session->start_time);
            $end = \Carbon\Carbon::parse($session->end_time);
            $totalHours += $start->diffInHours($end);
        }

        $totalHours = number_format($totalHours, 2, '.', '');

        return view('livewire.teacher.detail-subject-class', [
            'totalClasses' => $sessionsQuery->count(),
            'totalStudents' => $studentsCount,
            'totalHours' => $totalHours,
            'sessions' => $this->sessions,
        ]);
    }
}; ?>

<div x-data="{
    createSessionModal: false,
    editSessionModal: false,
    deleteSessionModal: false,
    sessionMenuOpen: null,
    toggleMenu(id) {
        this.sessionMenuOpen = this.sessionMenuOpen === id ? null : id;
    },
    init() {
        this.$watch('deleteSessionModal', value => {
            if (!value) document.body.classList.remove('overflow-hidden');
            else document.body.classList.add('overflow-hidden');
        });

        this.$watch('editSessionModal', value => {
            if (!value) document.body.classList.remove('overflow-hidden');
            else document.body.classList.add('overflow-hidden');
        });

        this.$watch('createSessionModal', value => {
            if (!value) document.body.classList.remove('overflow-hidden');
            else document.body.classList.add('overflow-hidden');
        });

        // Listen to custom events
        window.addEventListener('open-edit-modal', () => {
            this.editSessionModal = true;
        });

        window.addEventListener('open-delete-modal', () => {
            this.deleteSessionModal = true;
        });
    }
}">
    <div class="mt-10 w-full md:mt-0">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3">
                <h2 class="font-inter text-xl font-medium leading-tight text-gray-800">
                    {{ __('Detail Kelas') }}
                </h2>
                <p class="font-inter text-sm text-gray-600">Buat sesi pertemuan untuk mengelola presensi kelas</p>
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
                class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300"
                aria-label="Close">
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
                class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg p-1.5 text-red-500 hover:bg-red-200"
                aria-label="Close">
                <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
        <!-- Header Card -->
        <div class="flex w-full flex-col justify-between rounded-lg bg-white p-6 shadow-md md:flex-row md:items-center">
            <div class="flex flex-row justify-between gap-2 md:flex-col">
                <div class="flex flex-col">
                    <p class="flex flex-col font-inter text-xl font-medium">{{ $subjectName }}</p>
                    <p class="text-sm text-gray-500">{{ $subjectCode }}</p>
                </div>
                <span
                    class="inline-flex items-center rounded-full bg-blue-100 px-3 py-0 text-sm font-medium text-blue-800 md:py-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                    </svg>
                    <span class="ml-1">
                        {{ $className }} - {{ $major }}
                    </span>
                </span>
            </div>
            <div class="mt-4 hidden grid-cols-1 gap-3 sm:grid-cols-3 md:mt-0 md:grid">
                <div class="flex w-full flex-col gap-2 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center">
                        <div class="rounded-md bg-blue-100 p-2 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="font-inter text-sm text-gray-500">Jumlah Pertemuan</p>
                            <p class="font-inter text-xl font-medium text-gray-800">{{ $totalClasses }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex w-full flex-col gap-2 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center">
                        <div class="rounded-md bg-amber-100 p-2 text-amber-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="font-inter text-sm text-gray-500">Jumlah Jam</p>
                            <p class="font-inter text-xl font-medium text-gray-800">{{ $totalHours }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex w-full flex-col gap-2 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center">
                        <div class="rounded-md bg-green-100 p-2 text-green-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="font-inter text-sm text-gray-500">Jumlah Siswa</p>
                            <p class="font-inter text-xl font-medium text-gray-800">{{ $totalStudents }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="mt-5 flex justify-end md:justify-start">
            <button @click="createSessionModal = true"
                class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-md transition hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mr-2 h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Buat Pertemuan
            </button>
        </div>

        <!-- Sessions List as Table -->
        <div class="mt-6">
            <div class="mb-4 flex flex-col space-y-3 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                <h3 class="font-inter text-lg font-medium text-gray-800">Daftar Pertemuan</h3>
                <div class="text-sm text-gray-500">Total: {{ count($sessions) }} pertemuan</div>
            </div>

            <!-- Search and Filters -->
            <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="flex rounded-md shadow-sm">
                    <div class="relative flex flex-grow items-stretch">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            placeholder="Cari judul pertemuan..."
                            class="block w-full rounded-full border-gray-300 pl-10 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="hidden md:block">
                    <label for="dateFilter" class="sr-only block text-sm font-medium text-gray-700">Filter
                        Tanggal</label>
                    <div class="relative">

                        <input wire:model.live.debounce.500ms="dateFilter" type="date"
                            class="block rounded-full border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex flex-row justify-between md:justify-end">
                    <div class="flex md:hidden">
                        <label for="dateFilter" class="sr-only block text-sm font-medium text-gray-700">Filter
                            Tanggal</label>
                        <div class="relative">

                            <input wire:model.live.debounce.500ms="dateFilter" type="date"
                                class="block rounded-full border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <button wire:click="clearFilters"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="mr-2 h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset Filter
                    </button>
                </div>
            </div>

            @if (count($sessions) > 0)
                <!-- Table for desktop view -->
                <div class="hidden rounded-lg border border-gray-200 shadow-sm md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <div class="flex cursor-pointer items-center"
                                        wire:click="sortBy('subject_title')">
                                        Judul Pertemuan
                                        @if ($sortField === 'subject_title')
                                            @if ($sortDirection === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        @endif
                                    </div>
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <div class="flex cursor-pointer items-center" wire:click="sortBy('class_date')">
                                        Tanggal
                                        @if ($sortField === 'class_date')
                                            @if ($sortDirection === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        @endif
                                    </div>
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <div class="flex cursor-pointer items-center" wire:click="sortBy('start_time')">
                                        Waktu
                                        @if ($sortField === 'start_time')
                                            @if ($sortDirection === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        @endif
                                    </div>
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Durasi
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <div class="flex cursor-pointer items-center" wire:click="sortBy('created_at')">
                                        Dibuat
                                        @if ($sortField === 'created_at')
                                            @if ($sortDirection === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        @endif
                                    </div>
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($sessions as $session)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="h-5 w-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $session['subject_title'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($session['class_date'])->format('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($session['start_time'])->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($session['end_time'])->format('H:i') }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            @php
                                                if (!empty($session['start_time']) && !empty($session['end_time'])) {
                                                    $start = \Carbon\Carbon::parse($session['start_time']);
                                                    $end = \Carbon\Carbon::parse($session['end_time']);
                                                    $durationHours = $start->diffInHours($end);
                                                    $durationMinutes = $start->diffInMinutes($end) % 60;
                                                    echo sprintf('%02d:%02d', $durationHours, $durationMinutes);
                                                } else {
                                                    echo 'N/A';
                                                }
                                            @endphp
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($session['created_at'])->diffForHumans(['locale' => 'id']) }}
                                    </td>
                                    <!-- Perbaikan dropdown menu pada tampilan desktop -->
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('session.attendance', $session['id']) }}" wire:navigate
                                                class="rounded-md bg-blue-100 px-2.5 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-200">
                                                Kelola
                                            </a>
                                            <div class="relative" x-data="{ open: false }">
                                                <button @click="open = !open" type="button"
                                                    class="rounded-md bg-gray-100 px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200">
                                                    Opsi
                                                </button>

                                                <!-- Perbaikan z-index dan positioning -->
                                                <div x-show="open" @click.away="open = false"
                                                    class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                                    x-cloak style="min-width: 150px;">
                                                    <button wire:click="editSession({{ $session['id'] }})"
                                                        @click="open = false; $dispatch('open-edit-modal')"
                                                        class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor" class="mr-2 h-4 w-4 text-blue-500">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                        </svg>
                                                        Edit
                                                    </button>
                                                    <button wire:click="confirmDeleteSession({{ $session['id'] }})"
                                                        @click="open = false; $dispatch('open-delete-modal')"
                                                        class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor" class="mr-2 h-4 w-4 text-red-500">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                        </svg>
                                                        Hapus
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile view (responsive card-like table) -->
                <div class="space-y-4 md:hidden">
                    @foreach ($sessions as $session)
                        <div class="rounded-lg bg-white shadow">
                            <div class="border-b border-gray-200 bg-blue-50 px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="truncate text-base font-medium leading-6 text-gray-900">
                                        {{ $session['subject_title'] }}
                                    </h3>
                                    <div class="relative ml-2" x-data>

                                        <!-- Perbaikan dropdown -->
                                        <div class="relative ml-2" x-data="{ open: false }">
                                            <button @click="open = !open" type="button"
                                                class="rounded-full p-1 text-gray-500 hover:bg-blue-100 hover:text-gray-700 focus:outline-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false"
                                                class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                                x-cloak>
                                                <button wire:click="editSession({{ $session['id'] }})"
                                                    @click="open = false; $dispatch('open-edit-modal')"
                                                    class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="mr-2 h-4 w-4 text-blue-500">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button wire:click="confirmDeleteSession({{ $session['id'] }})"
                                                    @click="open = false; $dispatch('open-delete-modal')"
                                                    class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="mr-2 h-4 w-4 text-red-500">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-xs font-medium uppercase text-gray-500">Tanggal</div>
                                        <div class="mt-1 flex items-center text-sm text-gray-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="mr-1.5 h-4 w-4 text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                            </svg>
                                            {{ \Carbon\Carbon::parse($session['class_date'])->format('d M Y') }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-medium uppercase text-gray-500">Waktu</div>
                                        <div class="mt-1 flex items-center text-sm text-gray-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="mr-1.5 h-4 w-4 text-gray-400">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ \Carbon\Carbon::parse($session['start_time'])->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($session['end_time'])->format('H:i') }}
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="border-t border-gray-200 bg-gray-50 px-4 py-4 text-right">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($session['created_at'])->diffForHumans(['locale' => 'id']) }}
                                    </span>
                                    <a href="{{ route('session.attendance', $session['id']) }}" wire:navigate
                                        class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                                        Kelola Presensi
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mx-auto h-12 w-12 text-gray-300">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada pertemuan</h3>
                    <p class="mt-1 text-sm text-gray-500">Silakan buat pertemuan baru untuk mengatur presensi siswa.
                    </p>
                    <div class="mt-6">
                        <button @click="createSessionModal = true"
                            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="mr-2 h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Buat Pertemuan
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Buat Pertemuan -->
    <div x-cloak x-show="createSessionModal" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="createSessionModal = false" x-on:click.self="createSessionModal = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="sessionModalTitle">
        <!-- Modal Dialog -->
        <div x-show="createSessionModal" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">
            <!-- Dialog Header -->
            <div class="bg-blue-50 px-6 py-4">
                <h3 id="sessionModalTitle" class="text-lg font-medium text-gray-900">
                    Buat Pertemuan Mata Pelajaran
                </h3>
                <p class="mt-1 text-sm text-gray-500">Buat sesi pertemuan untuk mengelola presensi siswa.</p>
            </div>
            <!-- Dialog Body -->
            <div class="px-6 py-4">
                <form wire:submit="createSession">
                    <div class="mb-4">
                        <label for="subjectTitle" class="block text-sm font-medium text-gray-700">Judul
                            Pertemuan</label>
                        <input wire:model="subjectTitle" type="text"
                            placeholder="misalnya: Pertemuan 1. Kalkulus Dasar"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        @error('subjectTitle')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="mb-4">
                            <label for="classDate" class="block text-sm font-medium text-gray-700">Tanggal
                                Pertemuan</label>
                            <div class="relative mt-1 w-full rounded-md shadow-sm">
                                <input wire:model="classDate" type="date"
                                    class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">

                                </div>
                            </div>
                            @error('classDate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="startTime" class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input wire:model="startTime" type="time"
                                    class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">

                                </div>
                            </div>
                            @error('startTime')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="endTime" class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input wire:model="endTime" type="time"
                                    class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">

                                </div>
                            </div>
                            @error('endTime')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Dialog Footer -->
                    <div class="mt-6 flex items-center justify-end border-t border-gray-200 pt-4">
                        <button type="button" @click="createSessionModal = false"
                            class="mr-3 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" @click="createSessionModal = false"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            Buat Pertemuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pertemuan -->
    <div x-cloak x-show="editSessionModal" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="editSessionModal = false" x-on:click.self="editSessionModal = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="editSessionModalTitle">
        <!-- Modal Dialog -->
        <div x-show="editSessionModal" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">
            <!-- Dialog Header -->
            <div class="bg-blue-50 px-6 py-4">
                <h3 id="editSessionModalTitle" class="text-lg font-medium text-gray-900">
                    Edit Pertemuan
                </h3>
                <p class="mt-1 text-sm text-gray-500">Ubah detail pertemuan yang sudah ada.</p>
            </div>
            <!-- Dialog Body -->
            <div class="px-6 py-4">
                <form wire:submit="updateSession">
                    <div class="mb-4">
                        <label for="editSubjectTitle" class="block text-sm font-medium text-gray-700">Judul
                            Pertemuan</label>
                        <input wire:model="editSubjectTitle" type="text"
                            placeholder="misalnya: Pertemuan 1. Kalkulus Dasar"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                        @error('editSubjectTitle')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="mb-4">
                            <label for="editClassDate" class="block text-sm font-medium text-gray-700">Tanggal
                                Pertemuan</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input wire:model="editClassDate" type="date"
                                    style="appearance: none; -webkit-appearance: none; -moz-appearance: none;"
                                    class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">

                                </div>
                            </div>
                            @error('editClassDate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="editStartTime" class="block text-sm font-medium text-gray-700">Jam
                                Mulai</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input wire:model="editStartTime" type="time"
                                    class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">

                                </div>
                            </div>
                            @error('editStartTime')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="editEndTime" class="block text-sm font-medium text-gray-700">Jam
                                Selesai</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input wire:model="editEndTime" type="time"
                                    class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">

                                </div>
                            </div>
                            @error('editEndTime')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Dialog Footer -->
                    <div class="mt-6 flex items-center justify-end border-t border-gray-200 pt-4">
                        <button type="button" @click="editSessionModal = false"
                            class="mr-3 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" @click="editSessionModal = false"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div x-cloak x-show="deleteSessionModal" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="deleteSessionModal = false" x-on:click.self="deleteSessionModal = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="deleteSessionModalTitle">
        <!-- Modal Dialog -->
        <div x-show="deleteSessionModal" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
            <div class="px-6 py-6">
                <div class="flex items-center justify-center">
                    <div
                        class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="deleteSessionModalTitle">
                        Hapus Pertemuan
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Apakah Anda yakin ingin menghapus pertemuan ini? Semua data presensi siswa untuk pertemuan
                            ini juga akan dihapus. Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-center gap-3 sm:mt-4">
                    <button type="button" @click="deleteSessionModal = false"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                    <button type="button" wire:click="deleteSession" @click="deleteSessionModal = false"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto sm:text-sm">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
