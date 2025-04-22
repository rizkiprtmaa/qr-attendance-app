<?php

use Livewire\Volt\Component;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use App\Models\SubstitutionRequest;
use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;

new #[Layout('layouts.app')] class extends Component {
    public $subjectClass;
    public $substitution;
    public $sessions = [];

    // Form fields untuk membuat pertemuan baru
    #[Rule('required|string|max:255', message: 'Judul pertemuan harus diisi')]
    public $subjectTitle;

    #[Rule('required|date', message: 'Tanggal pertemuan harus diisi')]
    public $classDate;

    #[Rule('required', message: 'Jam mulai harus diisi')]
    public $startTime;

    #[Rule('required|after:startTime', message: 'Jam selesai harus setelah jam mulai')]
    public $endTime;

    // Modals
    public $showCreateSessionModal = false;

    public function mount(SubjectClass $subjectClass)
    {
        $this->subjectClass = $subjectClass;

        // Verifikasi bahwa user adalah pengganti yang disetujui
        $this->substitution = SubstitutionRequest::where('substitute_teacher_id', auth()->id())
            ->where('subject_class_id', $subjectClass->id)
            ->where('status', 'approved')
            ->first();

        if (!$this->substitution) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini');
        }

        // Periksa apakah substitusi masih aktif (tanggal sekarang ada dalam rentang substitusi)
        $now = now()->startOfDay();
        $startDate = \Carbon\Carbon::parse($this->substitution->start_date)->startOfDay();
        $endDate = $this->substitution->end_date ? \Carbon\Carbon::parse($this->substitution->end_date)->endOfDay() : $startDate->copy()->endOfDay();

        if ($now->lt($startDate) || $now->gt($endDate)) {
            abort(403, 'Periode substitusi tidak aktif');
        }

        $this->loadSessions();
    }

    public function loadSessions()
    {
        // Ambil hanya sesi yang dibuat oleh guru pengganti ini
        $this->sessions = SubjectClassSession::where('subject_class_id', $this->subjectClass->id)->where('substitution_request_id', $this->substitution->id)->orderBy('class_date', 'desc')->get();
    }

    public function showCreateForm()
    {
        // Set tanggal default ke hari ini
        $this->classDate = now()->format('Y-m-d');
        $this->showCreateSessionModal = true;
        $this->dispatch('show-create-session-modal');
    }

    public function createSession()
    {
        $this->validate();

        try {
            // Format tanggal dan jam
            $classDateTime = \Carbon\Carbon::parse($this->classDate . ' ' . $this->startTime);

            // Buat sesi baru
            $session = SubjectClassSession::create([
                'subject_class_id' => $this->subjectClass->id,
                'subject_title' => $this->subjectTitle,
                'class_date' => $classDateTime,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'created_by_substitute' => auth()->id(),
                'substitution_request_id' => $this->substitution->id,
                'notes' => 'Pertemuan oleh guru pengganti',
            ]);

            // Get all students in this class
            $students = Student::whereHas('classes', function ($query) {
                $query->where('id', $this->subjectClass->classes_id);
            })->get();

            // Create attendance records for each student in this session
            foreach ($students as $student) {
                // Check if student has approved permission for this date
                $sessionDate = $classDateTime->toDateString();
                $permissionExists = \App\Models\PermissionSubmission::where('user_id', $student->user_id)->whereDate('permission_date', $sessionDate)->where('status', 'approved')->first();

                if ($permissionExists) {
                    // Jika siswa memiliki izin yang disetujui, atur status sesuai tipe izin
                    SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => $permissionExists->type, // 'izin' or 'sakit'
                        'check_in_time' => null,
                    ]);
                } else {
                    // Jika tidak ada izin, set default status 'tidak_hadir'
                    SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => 'tidak_hadir',
                        'check_in_time' => null,
                    ]);
                }
            }

            // Reset form dan tutup modal
            $this->reset(['subjectTitle', 'classDate', 'startTime', 'endTime']);
            $this->showCreateSessionModal = false;

            // Reload sesi
            $this->loadSessions();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Pertemuan berhasil dibuat',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal membuat pertemuan: ' . $e->getMessage(),
            ]);
        }
    }

    public function render(): mixed
    {
        return view('livewire.teacher.substitution-class-management', [
            'sessions' => $this->sessions,
            'teacher' => $this->subjectClass->user,
            'class' => $this->subjectClass->classes,
        ]);
    }
}; ?>

<div class="mt-12 md:mt-0">
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
        <div class="flex flex-col justify-between sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $subjectClass->class_name }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    <span class="font-medium">Kelas:</span> {{ $class->name }} - {{ $class->major->name }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    <span class="font-medium">Guru Utama:</span> {{ $teacher->name }}
                </p>
                <p
                    class="mt-1 inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Pengganti: {{ \Carbon\Carbon::parse($substitution->start_date)->format('d M Y') }}
                    @if ($substitution->end_date)
                        - {{ \Carbon\Carbon::parse($substitution->end_date)->format('d M Y') }}
                    @endif
                </p>
            </div>

            <div class="mt-4 sm:mt-0">
                <button wire:click="showCreateForm"
                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Buat Pertemuan
                </button>
            </div>
        </div>
    </div>

    <!-- Sessions List -->
    <div class="rounded-lg bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-medium text-gray-900">Pertemuan yang Telah Dibuat</h3>
        </div>

        @if (count($sessions) > 0)
            <ul class="divide-y divide-gray-200">
                @foreach ($sessions as $session)
                    <li class="px-6 py-4">
                        <div class="flex flex-col items-start sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h4 class="text-base font-medium text-gray-900">{{ $session->subject_title }}</h4>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($session->class_date)->format('d M Y') }} |
                                    {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                                </p>
                            </div>

                            <div class="mt-3 sm:mt-0">
                                <a href="{{ route('substitute.attendance', $session->id) }}" wire:navigate
                                    class="rounded-md bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-100">
                                    Kelola Presensi
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="px-6 py-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada pertemuan</h3>
                <p class="mt-1 text-sm text-gray-500">Silakan buat pertemuan pertama sebagai guru pengganti.</p>
                <div class="mt-6">
                    <button wire:click="showCreateForm"
                        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Buat Pertemuan
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Create Session Modal -->
    <div x-data="{
        show: false,
        init() {
            this.$watch('show', value => {
                if (!value) {
                    setTimeout(() => {
                        @this.showCreateSessionModal = false;
                    }, 300);
                }
            });
        }
    }" x-init="init()" x-modelable="show" x-model="show" x-show="show" x-cloak
        x-on:show-create-session-modal.window="show = true" class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                @click="show = false"></div>

            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:w-full sm:max-w-lg"
                @click.outside="show = false">
                <!-- Modal Header -->
                <div class="bg-white px-4 py-5 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-full bg-blue-50 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Buat Pertemuan Baru</h3>
                            <p class="text-sm text-gray-500">Sebagai guru pengganti untuk
                                {{ $subjectClass->class_name }}</p>
                        </div>
                        <div class="ml-auto">
                            <button @click="show = false" type="button" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Tutup</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="bg-gray-50 px-4 py-5 sm:p-6">
                    <form wire:submit.prevent="createSession">
                        <div class="space-y-4">
                            <!-- Judul Pertemuan -->
                            <div>
                                <label for="subjectTitle" class="block text-sm font-medium text-gray-700">Judul
                                    Pertemuan</label>
                                <input type="text" wire:model="subjectTitle" id="subjectTitle"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="Contoh: Pertemuan 1 - Pengantar Materi">
                                @error('subjectTitle')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Tanggal Pertemuan -->
                            <div>
                                <label for="classDate" class="block text-sm font-medium text-gray-700">Tanggal
                                    Pertemuan</label>
                                <input type="date" wire:model="classDate" id="classDate"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('classDate')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Jam Mulai -->
                                <div>
                                    <label for="startTime" class="block text-sm font-medium text-gray-700">Jam
                                        Mulai</label>
                                    <input type="time" wire:model="startTime" id="startTime"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @error('startTime')
                                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Jam Selesai -->
                                <div>
                                    <label for="endTime" class="block text-sm font-medium text-gray-700">Jam
                                        Selesai</label>
                                    <input type="time" wire:model="endTime" id="endTime"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @error('endTime')
                                        <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="bg-white px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" wire:click="createSession"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                        Buat Pertemuan
                    </button>
                    <button @click="show = false" type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>


</div>
