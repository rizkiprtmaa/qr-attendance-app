<?php

use Livewire\Volt\Component;
use App\Models\SubjectClass;
use App\Models\Classes;
use App\Models\Major;

new class extends Component {
    public $classId;
    public $teacherId;
    public $className;
    public $classCode;
    public $major = '';

    public function mount()
    {
        $this->teacherId = auth()->user()->id;
        // Tambahkan inisialisasi opsional

        $firstMajor = Major::first();
        if ($firstMajor) {
            $this->major = $firstMajor->id;
        }
    }

    public function render(): mixed
    {
        // Ambil kelas yang difilter berdasarkan major jika dipilih
        $classes = $this->major ? Classes::where('major_id', $this->major)->get() : collect([]);

        $subjectClasses = SubjectClass::where('teacher_id', $this->teacherId)
            ->with('classes.student') // Eager load students relationship
            ->get();

        // Set classId ke kelas pertama secara otomatis jika belum dipilih
        if ($classes->isNotEmpty() && !$this->classId) {
            $this->classId = $classes->first()->id;
        }
        return view('livewire.teacher.create-attendances-class', [
            'classes' => $classes,
            'majors' => Major::all(),
            'subjectClasses' => $subjectClasses,
            'totalClasses' => $subjectClasses->count(),
            'totalStudents' => $subjectClasses->sum('student_count'),
        ]);
    }

    // Method untuk memfilter kelas berdasarkan jurusan
    public function updatedMajor($majorId)
    {
        // Reset classId saat major berubah dan set ke kelas pertama
        $classes = Classes::where('major_id', $majorId)->get();
        $this->classId = $classes->isNotEmpty() ? $classes->first()->id : null;
    }

    public function createClasses()
    {
        $this->validate([
            'className' => 'required',
            'classCode' => 'required',
            'major' => 'required|exists:majors,id', // Tambahkan validasi major
            'classId' => 'required|exists:classes,id', // Pastikan classId valid
            'teacherId' => 'required|exists:users,id', // Pastikan teacherId valid
        ]);

        try {
            $subjectClass = SubjectClass::create([
                'class_name' => $this->className,
                'class_code' => $this->classCode,
                'teacher_id' => $this->teacherId,
                'classes_id' => $this->classId,
            ]);

            // Tambahkan logging atau flash message untuk debugging
            session()->flash('success', 'Kelas berhasil dibuat');

            $this->reset();
        } catch (\Exception $e) {
            // Tampilkan error untuk debugging
            session()->flash('error', 'Gagal membuat kelas: ' . $e->getMessage());
        }
    }
}; ?>

<div x-data="{ showCreateClasses: false }">
    <div class="flex justify-end md:justify-start">
        <button @click="showCreateClasses = true" type="button"
            class="rounded-md bg-blue-500 px-4 py-2 font-inter text-sm text-white hover:bg-blue-700">Buat
            Kelas</button>
    </div>

    <div class="mb-6 mt-6 grid grid-cols-2 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Kelas Aktif</h3>
            <p class="text-2xl font-bold">{{ $totalClasses }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Total Pertemuan</h3>
            <p class="text-2xl font-bold">2</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Jumlah Jam</h3>
            <p class="text-2xl font-bold">3</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Kelas Pengganti</h3>
            <p class="text-2xl font-bold">0</p>
        </div>
    </div>

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



    @if (session()->has('error'))
        <div class="fixed bottom-5 right-5 z-10 rounded-md border border-red-400 bg-red-100 px-4 py-3 text-red-700"
            role="alert">
            {{ session('error') }}
        </div>
    @endif


    <div>
        <p class="mb-6 font-inter text-lg font-medium">Kelas Aktif</p>
    </div>

    <div class="flex flex-col">
        @foreach ($subjectClasses as $subjectClass)
            <div class="mb-6 rounded-lg bg-blue-100">
                <div class="rounded-lg bg-white px-6 py-3 shadow-md">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col gap-1">
                            <h2 class="font-inter text-lg font-semibold">{{ $subjectClass->class_name }}
                            </h2>
                            <div class="flex flex-row items-center gap-4">
                                <div>
                                    <p class="flex flex-row items-center gap-2 font-inter text-sm text-gray-500"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                        </svg>
                                        {{ $subjectClass->mainTeacher->name }}</p>
                                </div>
                                <div>
                                    <p class="flex flex-row items-center gap-2 font-inter text-sm text-gray-500"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                        </svg>

                                        {{ $subjectClass->classes->student->count() }} Siswa</p>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('subject.detail', $subjectClass) }}" wire:navigate
                            class="rounded-lg bg-blue-500 px-4 py-2 font-inter text-sm text-white hover:bg-blue-600">
                            Kelola Presensi
                        </a>
                    </div>
                </div>
                <div class="-z-10 flex flex-row justify-between gap-4 rounded-b-md bg-blue-100 p-6 shadow-sm">
                    <div class="flex flex-row gap-4">
                        <div class="flex items-center rounded-md bg-gray-400 px-2 py-1 text-white shadow-md">
                            {{ $subjectClass->classes->name }}
                        </div>
                        <div class="flex flex-col">
                            <p class="font-inter text-sm text-slate-500">Jurusan</p>
                            <p class="font-inter font-medium">{{ $subjectClass->classes->major->name }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <p class="font-inter text-sm text-slate-500">Jumlah Pertemuan</p>
                        <p class="text-end font-inter font-medium">0</p>
                    </div>


                </div>
            </div>
        @endforeach
    </div>




    {{-- Modals Buat Kelas --}}
    <div x-cloak x-show="showCreateClasses" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="showCreateClasses = false" x-on:click.self="showCreateClasses = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/20 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="defaultModalTitle">
        <!-- Modal Dialog -->
        <div x-show="showCreateClasses"
            x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity"
            x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
            class="rounded-radius border-gray text-on-surface relative z-50 flex w-full max-w-2xl flex-col gap-4 overflow-hidden rounded-xl border bg-white px-8 py-6 backdrop-blur-lg backdrop-filter">
            <!-- Dialog Header -->
            <div
                class="border-outline bg-surface-alt/60 flex flex-col items-center justify-center gap-2 px-4 pb-4 pt-10">
                <h3 id="defaultModalTitle"
                    class="text-on-surface-strong text-center font-inter text-xl font-medium tracking-wide">
                    Buat Kelas Mata Pelajaran</h3>
                <p class="font-inter text-sm text-gray-600">Kelola presensi mata pelajaran berdasarkan sesi dan kelas.
                </p>
            </div>
            <!-- Dialog Body -->
            <div class="px-8">
                <form wire:submit="createClasses">
                    <div class="mb-4">
                        <label for="name" class="font-inter text-sm font-semibold text-slate-500">Nama
                            Mata Pelajaran</label>
                        <input wire:model="className" type="text" placeholder="misalnya: Bahasa Indonesia"
                            class="flex w-full rounded-lg border-gray-300 text-sm" />
                        @error('className')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                                {{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="name" class="font-inter text-sm font-semibold text-slate-500">Kode
                            Kelas</label>
                        <input wire:model="classCode" type="text" placeholder="misalnya: B.IND, MAT"
                            class="flex w-full rounded-lg border-gray-300 text-sm" />
                        @error('classCode')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                                {{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="major" class="font-inter text-sm font-semibold text-slate-500">Jurusan</label>
                        <select wire:model.live="major" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="" disabled selected>--- Pilih Jurusan ---</option>
                            @foreach ($majors as $majorItem)
                                <option value="{{ $majorItem->id }}">{{ $majorItem->name }}</option>
                            @endforeach
                        </select>
                        @error('major')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">
                                <span class="font-medium">Oops!</span> {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="classId" class="font-inter text-sm font-semibold text-slate-500">Kelas</label>
                        <select wire:model="classId" class="w-full rounded-lg border-gray-300 text-sm"
                            @if (!$major) disabled @endif
                            wire:change="$set('classId', $event.target.value)" aria-placeholder="--- Pilih Kelas ---">

                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->major->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('classId')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500">
                                <span class="font-medium">Oops!</span> {{ $message }}
                            </p>
                        @enderror
                    </div>



            </div>
            <!-- Dialog Footer -->
            <div
                class="border-outline bg-surface-alt/60 dark:border-outline-dark dark:bg-surface-dark/20 flex flex-col-reverse justify-between gap-2 border-t p-4 sm:flex-row sm:items-center md:justify-end">
                <button x-on:click="showCreateClasses = false" type="button"
                    class="text-on-surface focus-visible:outline-primary dark:text-on-surface-dark dark:focus-visible:outline-primary-dark whitespace-nowrap rounded-md px-4 py-2 text-center text-sm font-medium tracking-wide transition hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 active:opacity-100 active:outline-offset-0">Batal</button>
                <x-primary-button type="submit" class="text-center!" color="blue"
                    x-on:click="showCreateClasses = false">Buat Kelas</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</div>
