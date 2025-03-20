<?php

use Livewire\Volt\Component;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use App\Models\Classes;
use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;

new #[Layout('layouts.app')] class extends Component {
    public $subjectClass;
    public $subjectClassId;
    public $subjectName;
    public $subjectCode;
    public $className;
    public $major;
    public $classesId;

    // form
    #[Rule('required', message: 'Judul pertemuan harus diisi')]
    public $subjectTitle;

    #[Rule('required', message: 'Tanggal pertemuan harus diisi')]
    public $classDate;

    #[Rule('required', message: 'Jam mulai harus diisi')]
    public $startTime;

    #[Rule('required', message: 'Jam selesai harus diisi')]
    public $endTime;

    // store sessions
    public $sessions = [];

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

        // Load existing sessions
        $this->loadSessions();
    }

    public function loadSessions()
    {
        $this->sessions = SubjectClassSession::where('subject_class_id', $this->subjectClassId)->orderBy('class_date', 'desc')->orderBy('start_time', 'desc')->get()->toArray();
    }

    public function createSession()
    {
        $this->validate();

        try {
            // Format class date with the start time to create a proper datetime
            $classDateTime = \Carbon\Carbon::parse($this->classDate . ' ' . $this->startTime);

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
                SubjectClassAttendance::create([
                    'subject_class_session_id' => $session->id,
                    'student_id' => $student->id,
                    'status' => 'tidak_hadir', // Default status
                    'check_in_time' => null,
                ]);
            }

            // Reset form fields
            $this->reset(['subjectTitle', 'classDate', 'startTime', 'endTime']);

            // Reload sessions
            $this->loadSessions();

            // Show success message
            session()->flash('success', 'Sesi pertemuan berhasil dibuat');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal membuat sesi pertemuan: ' . $e->getMessage());
        }
    }

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
            $totalHours += $start->diffInHours($end); // Pastikan urutan parameter benar
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

<div x-data="{ createSessionModal: false }">
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
            class="fixed bottom-5 right-5 z-10 mb-4 flex w-full max-w-xs items-center rounded-lg bg-white p-4 text-gray-500 shadow dark:bg-gray-800 dark:text-gray-400"
            role="alert">
            <div
                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-500 dark:bg-green-800 dark:text-green-200">
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                </svg>
                <span class="sr-only">Check icon</span>
            </div>
            <div class="ml-3 text-sm font-normal">{{ session('success') }}</div>
            <button type="button" @click="show = false"
                class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300 dark:bg-gray-800 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-white"
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
        <div class="flex w-full flex-col items-center justify-between rounded-md bg-white p-6 shadow-md md:flex-row">
            <div class="flex w-full flex-row justify-between gap-2 md:max-w-[10rem] md:flex-col">
                <div class="flex flex-col">
                    <p class="flex flex-col font-inter text-xl font-medium">{{ $subjectName }}</p>
                    <p>{{ $subjectCode }}</p>
                </div>
                <span
                    class="inline-flex items-center rounded-full bg-blue-200 px-3 py-1 text-sm font-semibold text-blue-600 hover:bg-blue-300">
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
            <div class="mt-4 hidden grid-cols-2 place-items-center gap-3 md:mt-0 md:grid md:grid-cols-3">
                <div class="flex w-full flex-col gap-2 rounded-md border border-gray-400/50 bg-white p-4">
                    <p class="font-inter text-sm text-slate-900">Jumlah Pertemuan</p>
                    <p class="font-inter text-xl font-medium">{{ $totalClasses }}</p>
                </div>
                <div class="flex w-full flex-col gap-2 rounded-md border border-gray-400/50 bg-white p-4">
                    <p class="font-inter text-sm text-slate-900">Jumlah Jam</p>
                    <p class="font-inter text-xl font-medium">{{ $totalHours }}</p>
                </div>
                <div class="flex w-full flex-col gap-2 rounded-md border border-gray-400/50 bg-white p-4">
                    <p class="font-inter text-sm text-slate-900">Jumlah Siswa</p>
                    <p class="font-inter text-xl font-medium">{{ $totalStudents }}</p>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="mt-5 flex justify-end md:justify-start">
            <button @click="createSessionModal = true"
                class="text-inter rounded border border-blue-600/50 bg-blue-500 px-4 py-2 text-sm text-white shadow-md hover:bg-blue-700">
                Buat Pertemuan
            </button>
        </div>

        <!-- Sessions List -->
        <div class="mt-6">
            <h3 class="mb-4 font-inter text-lg font-medium text-gray-800">Daftar Pertemuan</h3>

            @if (count($sessions) > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($sessions as $session)
                        <div class="overflow-hidden rounded-lg bg-white shadow transition hover:shadow-md">
                            <div class="px-4 py-4 sm:px-6">
                                <h3 class="text-md font-inter font-medium leading-6 text-gray-900">
                                    {{ $session['subject_title'] }}
                                </h3>
                            </div>
                            <flux:separator></flux:separator>
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex flex-row items-center justify-between gap-2">
                                    <div class="flex flex-col gap-2 text-sm text-gray-500">
                                        <div class="flex flex-row items-center justify-start gap-2 text-start">
                                            <p class="font-xs font-inter font-semibold">Tanggal</p>
                                        </div>
                                        <div class="flex flex-row items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                            </svg>
                                            <span>{{ \Carbon\Carbon::parse($session['class_date'])->format('d M Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-2 text-sm text-gray-500">
                                        <div class="flex flex-row items-center justify-start gap-2 text-start">
                                            <p class="font-xs font-inter font-semibold">Jam</p>
                                        </div>
                                        <div class="flex flex-row items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="h-5 w-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ \Carbon\Carbon::parse($session['start_time'])->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($session['end_time'])->format('H:i') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <flux:separator></flux:separator>
                            <div class="flex flex-row justify-end p-4">
                                <a href="{{ route('session.attendance', $session['id']) }}" wire:navigate
                                    class="inline-flex items-center rounded-md border border-blue-300 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 shadow-sm hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-2 h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                    </svg>
                                    Kelola Presensi
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M8.485 3.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 3.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Belum ada pertemuan</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Silakan buat pertemuan baru untuk mengatur presensi siswa.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Buat Pertemuan -->
    <div x-cloak x-show="createSessionModal" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="createSessionModal = false" x-on:click.self="createSessionModal = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/20 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="sessionModalTitle">
        <!-- Modal Dialog -->
        <div x-show="createSessionModal"
            x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity"
            x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
            class="rounded-radius border-gray text-on-surface relative z-50 flex w-full max-w-2xl flex-col gap-4 overflow-hidden rounded-xl border bg-white px-8 py-6 backdrop-blur-lg backdrop-filter">
            <!-- Dialog Header -->
            <div
                class="border-outline bg-surface-alt/60 flex flex-col items-center justify-center gap-2 px-4 pb-4 pt-10">
                <h3 id="sessionModalTitle"
                    class="text-on-surface-strong text-center font-inter text-xl font-medium tracking-wide">
                    Buat Pertemuan Mata Pelajaran</h3>
                <p class="font-inter text-sm text-gray-600">Buat sesi pertemuan untuk mengelola presensi siswa.</p>
            </div>
            <!-- Dialog Body -->
            <div class="px-8">
                <form wire:submit="createSession">
                    <div class="mb-4">
                        <label for="subjectTitle" class="font-inter text-sm font-semibold text-slate-500">Judul
                            Pertemuan</label>
                        <input wire:model="subjectTitle" type="text"
                            placeholder="misalnya: Pertemuan 1. Kalkulus Dasar"
                            class="flex w-full rounded-lg border-gray-300 text-sm" />
                        @error('subjectTitle')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                                {{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-0 md:grid-cols-3 md:gap-4">
                        <div class="mb-4">
                            <label for="classDate" class="font-inter text-sm font-semibold text-slate-500">Tanggal
                                Pertemuan</label>
                            <input wire:model="classDate" type="date"
                                class="flex w-full rounded-lg border-gray-300 text-sm" />
                            @error('classDate')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span
                                        class="font-medium">Oops!</span>
                                    {{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="startTime" class="font-inter text-sm font-semibold text-slate-500">Jam
                                Mulai</label>
                            <input wire:model="startTime" type="time"
                                class="flex w-full rounded-lg border-gray-300 text-sm" />
                            @error('startTime')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span
                                        class="font-medium">Oops!</span>
                                    {{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="endTime" class="font-inter text-sm font-semibold text-slate-500">Jam
                                Selesai</label>
                            <input wire:model="endTime" type="time"
                                class="flex w-full rounded-lg border-gray-300 text-sm" />
                            @error('endTime')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span
                                        class="font-medium">Oops!</span>
                                    {{ $message }}</p>
                            @enderror
                        </div>
                    </div>
            </div>
            <!-- Dialog Footer -->
            <div
                class="border-outline bg-surface-alt/60 dark:border-outline-dark dark:bg-surface-dark/20 flex flex-col-reverse justify-between gap-2 border-t p-4 sm:flex-row sm:items-center md:justify-end">
                <button x-on:click="createSessionModal = false" type="button"
                    class="text-on-surface focus-visible:outline-primary dark:text-on-surface-dark dark:focus-visible:outline-primary-dark whitespace-nowrap rounded-md px-4 py-2 text-center text-sm font-medium tracking-wide transition hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 active:opacity-100 active:outline-offset-0">Batal</button>
                <x-primary-button type="submit" class="text-center!" color="blue"
                    x-on:click="createSessionModal = false">Buat Pertemuan</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</div>
