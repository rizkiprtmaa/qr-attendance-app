<?php

use Livewire\Volt\Component;
use App\Models\Major;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolYear;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $activeTab = 'major';
    public $name;
    public $code;
    public $classesName;
    public $major;
    public $schoolYear;
    public $teacher;
    public $search = '';
    public $majorFilter = null;
    public $perPage = 10;

    // Tambahkan badge_color ke property component
    public $badge_color = 'bg-gray-100 text-gray-800';

    // For editing
    public $editingMajorId = null;
    public $editingClassId = null;

    public function mount()
    {
        // Cek apakah ada tab yang aktif dari parameter URL
        $this->activeTab = request()->query('tab', 'major');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function createMajor()
    {
        $this->validate([
            'name' => 'required|string|min:3|max:100',
            'code' => 'required|string|min:2|max:10|unique:majors,code',
            'badge_color' => 'required|string',
        ]);

        Major::create([
            'name' => $this->name,
            'code' => $this->code,
            'badge_color' => $this->badge_color,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Jurusan berhasil ditambahkan',
        ]);

        $this->reset(['name', 'code', 'badge_color']);
    }

    public function startEditMajor($id)
    {
        $this->editingMajorId = $id;
        $major = Major::findOrFail($id);
        $this->name = $major->name;
        $this->code = $major->code;
        $this->badge_color = $major->badge_color;
    }

    public function updateMajor()
    {
        $this->validate([
            'name' => 'required|string|min:3|max:100',
            'code' => 'required|string|min:2|max:10|unique:majors,code,' . $this->editingMajorId,
            'badge_color' => 'required|string',
        ]);

        $major = Major::findOrFail($this->editingMajorId);
        $major->update([
            'name' => $this->name,
            'code' => $this->code,
            'badge_color' => $this->badge_color,
        ]);

        // Update related classes if needed
        $classes = Classes::where('major_id', $this->editingMajorId)->get();
        foreach ($classes as $class) {
            // Jika perlu update kelas juga
            // $class->update([
            //     'name' => $class->name, // Tetap menggunakan nama kelas yang ada
            //     'major_id' => $this->editingMajorId,
            // ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Jurusan berhasil diperbarui',
        ]);

        $this->reset(['name', 'code', 'editingMajorId']);
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'code', 'editingMajorId', 'editingClassId']);
    }

    public function delete($id)
    {
        try {
            $major = Major::findOrFail($id);

            // Cek apakah ada kelas yang terkait
            $relatedClasses = Classes::where('major_id', $id)->count();
            if ($relatedClasses > 0) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Tidak dapat menghapus jurusan yang masih memiliki kelas',
                ]);
                return;
            }

            $major->delete();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Jurusan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal menghapus jurusan: ' . $e->getMessage(),
            ]);
        }
    }

    public function createClasses()
    {
        $this->validate([
            'classesName' => 'required|string|min:2|max:50',
            'major' => 'required|exists:majors,id',
            'schoolYear' => 'required|exists:school_years,id',
            'teacher' => 'required|exists:teachers,id',
        ]);

        Classes::create([
            'name' => $this->classesName,
            'major_id' => $this->major,
            'school_year_id' => $this->schoolYear,
            'teacher_id' => $this->teacher,
        ]);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil ditambahkan',
        ]);

        $this->reset(['classesName', 'major', 'schoolYear', 'teacher']);
    }

    public function startEditClass($id)
    {
        $this->editingClassId = $id;
        $class = Classes::findOrFail($id);
        $this->classesName = $class->name;
        $this->major = $class->major_id;
        $this->schoolYear = $class->school_year_id;
        $this->teacher = $class->teacher_id;
    }

    public function updateClass()
    {
        $this->validate([
            'classesName' => 'required|string|min:2|max:50',
            'major' => 'required|exists:majors,id',
            'schoolYear' => 'required|exists:school_years,id',
            'teacher' => 'required|exists:teachers,id',
        ]);

        $class = Classes::findOrFail($this->editingClassId);
        $class->update([
            'name' => $this->classesName,
            'major_id' => $this->major,
            'school_year_id' => $this->schoolYear,
            'teacher_id' => $this->teacher,
        ]);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Kelas berhasil diperbarui',
        ]);

        $this->reset(['classesName', 'major', 'schoolYear', 'teacher', 'editingClassId']);
    }

    public function reset(...$properties)
    {
        // ...
        $this->badge_color = 'bg-gray-100 text-gray-800';
        // ...
    }

    public function deleteClass($id)
    {
        try {
            $class = Classes::findOrFail($id);

            // Cek apakah ada siswa yang terkait
            $relatedStudents = Student::where('classes_id', $id)->count();
            if ($relatedStudents > 0) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Tidak dapat menghapus kelas yang masih memiliki siswa',
                ]);
                return;
            }

            $class->delete();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Kelas berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal menghapus kelas: ' . $e->getMessage(),
            ]);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingMajorFilter()
    {
        $this->resetPage();
    }

    #[On('majorUpdated')]
    public function render(): mixed
    {
        // Query jurusan
        $majorsQuery = Major::query();

        // Filter pencarian jurusan
        if ($this->search && $this->activeTab == 'major') {
            $majorsQuery->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        $majors = $majorsQuery->orderBy('name')->get();

        // Query kelas
        $classesQuery = Classes::with(['major', 'teacher.user', 'school_year']);

        // Filter pencarian kelas
        if ($this->search && $this->activeTab == 'class') {
            $classesQuery
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhereHas('major', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('teacher.user', function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%');
                });
        }

        // Filter berdasarkan jurusan
        if ($this->majorFilter) {
            $classesQuery->where('major_id', $this->majorFilter);
        }

        $classes = $classesQuery->orderBy('name')->paginate($this->perPage);

        // Ambil data lain yang dibutuhkan
        $allMajors = Major::orderBy('name')->get();
        $school_years = SchoolYear::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('created_at', 'desc')->get();

        // Hitung jumlah siswa per kelas
        $classStudents = [];
        foreach ($classes as $class) {
            $classStudents[$class->id] = Student::where('classes_id', $class->id)->count();
        }

        return view('livewire.admin.classes-organizer', [
            'majors' => $majors,
            'classes' => $classes,
            'allMajors' => $allMajors,
            'school_years' => $school_years,
            'teachers' => $teachers,
            'classStudents' => $classStudents,
        ]);
    }
}; ?>

<div x-data="{
    showMajorModal: false,
    showClassesModal: false,
    showEditMajorModal: false,
    showEditClassModal: false,
    showDeleteMajorModal: false,
    showDeleteClassModal: false,
    majorToDelete: null,
    classToDelete: null,
    notifyType: '',
    notifyMessage: '',
    showNotification: false,

    showNotify(type, message) {
        this.notifyType = type;
        this.notifyMessage = message;
        this.showNotification = true;

        setTimeout(() => {
            this.showNotification = false;
        }, 3000);
    }
}" @notify.window="showNotify($event.detail.type, $event.detail.message)" class="mt-12 md:mt-0">

    <!-- Notification Toast -->
    <div x-show="showNotification" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        class="fixed bottom-4 right-4 z-50 max-w-sm rounded-md p-4 shadow-lg"
        :class="{
            'bg-green-50 text-green-800 border border-green-200': notifyType === 'success',
            'bg-red-50 text-red-800 border border-red-200': notifyType === 'error',
            'bg-blue-50 text-blue-800 border border-blue-200': notifyType === 'info'
        }">
        <div class="flex items-center">
            <!-- Success Icon -->
            <svg x-show="notifyType === 'success'" xmlns="http://www.w3.org/2000/svg"
                class="mr-2 h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            <!-- Error Icon -->
            <svg x-show="notifyType === 'error'" xmlns="http://www.w3.org/2000/svg" class="mr-2 h-6 w-6 text-red-500"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            <!-- Info Icon -->
            <svg x-show="notifyType === 'info'" xmlns="http://www.w3.org/2000/svg" class="mr-2 h-6 w-6 text-blue-500"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            <span x-text="notifyMessage"></span>
        </div>
    </div>

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

    <!-- Header with Title and Search -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">


        <div class="relative flex w-full max-w-xs">
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="Cari {{ $activeTab === 'major' ? 'jurusan' : 'kelas' }}..."
                class="block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 transform text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button wire:click="setActiveTab('major')"
                class="{{ $activeTab === 'major' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} group inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor"
                    class="{{ $activeTab === 'major' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }} mr-2 size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                Jurusan
            </button>

            <button wire:click="setActiveTab('class')"
                class="{{ $activeTab === 'class' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} group inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor"
                    class="{{ $activeTab === 'class' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }} mr-2 size-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                </svg>
                Kelas
            </button>
        </nav>
    </div>

    <!-- Major Tab Content -->
    <div class="{{ $activeTab === 'major' ? 'block' : 'hidden' }} space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Daftar Jurusan</h2>
            <button @click="showMajorModal = true"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mr-2 h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Jurusan
            </button>
        </div>

        @if ($majors->isEmpty())
            <div
                class="flex min-h-[250px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white p-6">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-8 w-8 text-blue-600">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Belum ada jurusan</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai tambahkan jurusan untuk sekolah Anda.</p>
                <button @click="showMajorModal = true"
                    class="mt-4 inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Tambah Jurusan Pertama
                </button>
            </div>
        @else
            <!-- Desktop View for Majors (Tablet and up) -->
            <div class="hidden md:block">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Kode</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Nama Jurusan</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Jumlah Kelas</th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($majors as $major)
                                <tr wire:key="major-{{ $major->id }}" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div
                                            class="{{ $major->badge_color }} inline-flex items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium">
                                            {{ $major->code }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $major->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ $major->classes->count() }} kelas
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm">
                                        <div class="flex justify-center space-x-2">
                                            <button
                                                @click="$wire.startEditMajor({{ $major->id }}); showEditMajorModal = true"
                                                class="rounded p-1 text-blue-600 hover:bg-blue-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="majorToDelete = {{ $major->id }}; showDeleteMajorModal = true"
                                                class="rounded p-1 text-red-600 hover:bg-red-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile View for Majors (Small screens) -->
            <div class="grid gap-4 md:hidden">
                @foreach ($majors as $major)
                    <div wire:key="major-mobile-{{ $major->id }}"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                        <div class="px-4 py-5 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div
                                        class="{{ $major->badge_color }} inline-flex items-center rounded-md px-2.5 py-1 text-sm font-medium">
                                        {{ $major->code }}
                                    </div>
                                    <h3 class="mt-2 text-lg font-medium text-gray-900">{{ $major->name }}</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="$wire.startEditMajor({{ $major->id }}); showEditMajorModal = true"
                                        class="rounded-full bg-blue-50 p-2 text-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>
                                    <button @click="majorToDelete = {{ $major->id }}; showDeleteMajorModal = true"
                                        class="rounded-full bg-red-50 p-2 text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-2 h-5 w-5 text-gray-500">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                    </svg>
                                    <span class="text-sm text-gray-700">{{ $major->classes->count() }} kelas</span>
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Classes Tab Content -->
    <div class="{{ $activeTab === 'class' ? 'block' : 'hidden' }} space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Daftar Kelas</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola kelas dan siswa dalam tiap kelas</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <select wire:model.live="majorFilter"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Jurusan</option>
                    @foreach ($allMajors as $majorItem)
                        <option value="{{ $majorItem->id }}">{{ $majorItem->name }}</option>
                    @endforeach
                </select>

                <button @click="showClassesModal = true"
                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mr-2 h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Kelas
                </button>
            </div>
        </div>

        @if ($classes->isEmpty())
            <div
                class="flex min-h-[250px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white p-6">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-8 w-8 text-blue-600">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Belum ada kelas</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai tambahkan kelas untuk jurusan yang telah dibuat.</p>
                <button @click="showClassesModal = true"
                    class="mt-4 inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Tambah Kelas Pertama
                </button>
            </div>
        @else
            <!-- Desktop View for Classes (Tablet and up) -->
            <div class="hidden md:block">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Kelas</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Jurusan</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Wali Kelas</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Tahun Ajaran</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Jumlah Siswa</th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($classes as $class)
                                <tr wire:key="class-{{ $class->id }}" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $class->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <div
                                            class="{{ $class->major->badge_color }} inline-flex items-center justify-center rounded-md px-2.5 py-1 text-xs font-medium">
                                            {{ $class->major->code ?? 'N/A' }}
                                        </div>
                                        <span class="ml-2">{{ $class->major->name ?? 'Tidak ada jurusan' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ $class->teacher->user->name ?? 'Belum ditentukan' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ $class->school_year->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ $classStudents[$class->id] ?? 0 }} siswa
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm">
                                        <div class="flex justify-center space-x-2">
                                            <button
                                                @click="$wire.startEditClass({{ $class->id }}); showEditClassModal = true"
                                                class="rounded p-1 text-blue-600 hover:bg-blue-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="classToDelete = {{ $class->id }}; showDeleteClassModal = true"
                                                class="rounded p-1 text-red-600 hover:bg-red-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                            <a href="{{ route('classes.detail', $class->id) }}"
                                                class="rounded p-1 text-purple-600 hover:bg-purple-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Desktop Pagination -->
                <div class="mt-4">
                    {{ $classes->links() }}
                </div>
            </div>

            <!-- Mobile View for Classes (Small screens) -->
            <div class="grid gap-4 md:hidden">
                @foreach ($classes as $class)
                    <div wire:key="class-mobile-{{ $class->id }}"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                        <div class="bg-blue-600 px-4 py-5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-white">{{ $class->name }}</h3>
                                <div
                                    class="{{ $class->major->badge_color }} inline-flex items-center justify-center rounded-md px-2.5 py-1 text-xs font-medium">
                                    {{ $class->major->code ?? 'N/A' }}
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-blue-100">{{ $class->major->name ?? 'Tidak ada jurusan' }}</p>
                        </div>

                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-2 h-5 w-5 text-gray-500">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500">Wali Kelas</p>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $class->teacher->user->name ?? 'Belum ditentukan' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-2 h-5 w-5 text-gray-500">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500">Tahun Ajaran</p>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $class->school_year->name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between border-b border-gray-200 py-3">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-2 h-5 w-5 text-gray-500">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500">Jumlah Siswa</p>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $classStudents[$class->id] ?? 0 }} siswa</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-3">
                                <div class="flex space-x-2">
                                    <button
                                        @click="$wire.startEditClass({{ $class->id }}); showEditClassModal = true"
                                        class="rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600">
                                        Edit
                                    </button>
                                    <button @click="classToDelete = {{ $class->id }}; showDeleteClassModal = true"
                                        class="rounded-md bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600">
                                        Hapus
                                    </button>
                                </div>
                                <a href="{{ route('classes.detail', $class->id) }}"
                                    class="inline-flex items-center rounded-md bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-600">
                                    Kelola Kelas
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="ml-1 h-3.5 w-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Mobile Pagination -->
                <div class="mt-4">
                    {{ $classes->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Add Major Modal -->
    <div x-cloak x-show="showMajorModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50 p-4">
        <div x-cloak x-show="showMajorModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" @click.away="showMajorModal = false"
            class="relative max-h-[90vh] w-full max-w-md overflow-auto rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Tambah Jurusan Baru</h3>
                <button @click="showMajorModal = false" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="createMajor">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Jurusan</label>
                        <input type="text" id="name" wire:model="name"
                            placeholder="contoh: Teknik Sepeda Motor"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @error('name')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Kode Jurusan</label>
                        <input type="text" id="code" wire:model="code" placeholder="contoh: TSM"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @error('code')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="badge_color" class="block text-sm font-medium text-gray-700">Warna Badge</label>
                        <select id="badge_color" wire:model="badge_color"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="bg-gray-100 text-gray-800">Abu-abu (Default)</option>
                            <option value="bg-blue-100 text-blue-800">Biru</option>
                            <option value="bg-green-100 text-green-800">Hijau</option>
                            <option value="bg-red-100 text-red-800">Merah</option>
                            <option value="bg-yellow-100 text-yellow-800">Kuning</option>
                            <option value="bg-purple-100 text-purple-800">Ungu</option>
                            <option value="bg-pink-100 text-pink-800">Pink</option>
                            <option value="bg-indigo-100 text-indigo-800">Indigo</option>
                            <option value="bg-orange-100 text-orange-800">Oranye</option>
                        </select>
                        @error('badge_color')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showMajorModal = false"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" @click="showMajorModal = false"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Major Modal -->
    <div x-cloak x-show="showEditMajorModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50 p-4">
        <div x-cloak x-show="showEditMajorModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" @click.away="showEditMajorModal = false; $wire.cancelEdit()"
            class="relative max-h-[90vh] w-full max-w-md overflow-auto rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Edit Jurusan</h3>
                <button @click="showEditMajorModal = false; $wire.cancelEdit()"
                    class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="updateMajor">
                <div class="space-y-4">
                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-gray-700">Nama Jurusan</label>
                        <input type="text" id="edit-name" wire:model="name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @error('name')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-code" class="block text-sm font-medium text-gray-700">Kode Jurusan</label>
                        <input type="text" id="edit-code" wire:model="code"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @error('code')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="badge_color" class="block text-sm font-medium text-gray-700">Warna Badge</label>
                        <select id="badge_color" wire:model="badge_color"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="bg-gray-100 text-gray-800">Abu-abu (Default)</option>
                            <option value="bg-blue-100 text-blue-800">Biru</option>
                            <option value="bg-green-100 text-green-800">Hijau</option>
                            <option value="bg-red-100 text-red-800">Merah</option>
                            <option value="bg-yellow-100 text-yellow-800">Kuning</option>
                            <option value="bg-purple-100 text-purple-800">Ungu</option>
                            <option value="bg-pink-100 text-pink-800">Pink</option>
                            <option value="bg-indigo-100 text-indigo-800">Indigo</option>
                            <option value="bg-orange-100 text-orange-800">Oranye</option>
                        </select>
                        @error('badge_color')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showEditMajorModal = false; $wire.cancelEdit()"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" @click="showEditMajorModal = false"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Class Modal -->
    <div x-cloak x-show="showClassesModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50 p-4">
        <div x-cloak x-show="showClassesModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" @click.away="showClassesModal = false"
            class="relative max-h-[90vh] w-full max-w-md overflow-auto rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Tambah Kelas Baru</h3>
                <button @click="showClassesModal = false" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="createClasses">
                <div class="space-y-4">
                    <div>
                        <label for="classesName" class="block text-sm font-medium text-gray-700">Nama Kelas</label>
                        <input type="text" id="classesName" wire:model="classesName" placeholder="contoh: 10 A"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @error('classesName')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="major" class="block text-sm font-medium text-gray-700">Jurusan</label>
                        <select id="major" wire:model="major"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Pilih Jurusan</option>
                            @foreach ($allMajors as $majorOption)
                                <option value="{{ $majorOption->id }}">{{ $majorOption->name }}</option>
                            @endforeach
                        </select>
                        @error('major')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="schoolYear" class="block text-sm font-medium text-gray-700">Tahun Ajaran</label>
                        <select id="schoolYear" wire:model="schoolYear"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Pilih Tahun Ajaran</option>
                            @foreach ($school_years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                        @error('schoolYear')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="teacher" class="block text-sm font-medium text-gray-700">Wali Kelas</label>
                        <select id="teacher" wire:model="teacher"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Pilih Wali Kelas</option>
                            @foreach ($teachers as $teacherOption)
                                <option value="{{ $teacherOption->id }}">{{ $teacherOption->user->name }}</option>
                            @endforeach
                        </select>
                        @error('teacher')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showClassesModal = false"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" @click="showClassesModal = false"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div x-cloak x-show="showEditClassModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50 p-4">
        <div x-cloak x-show="showEditClassModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" @click.away="showEditClassModal = false; $wire.cancelEdit()"
            class="relative max-h-[90vh] w-full max-w-md overflow-auto rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Edit Kelas</h3>
                <button @click="showEditClassModal = false; $wire.cancelEdit()"
                    class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="updateClass">
                <div class="space-y-4">
                    <div>
                        <label for="edit-classesName" class="block text-sm font-medium text-gray-700">Nama
                            Kelas</label>
                        <input type="text" id="edit-classesName" wire:model="classesName"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @error('classesName')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-major" class="block text-sm font-medium text-gray-700">Jurusan</label>
                        <select id="edit-major" wire:model="major"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Pilih Jurusan</option>
                            @foreach ($allMajors as $majorOption)
                                <option value="{{ $majorOption->id }}">{{ $majorOption->name }}</option>
                            @endforeach
                        </select>
                        @error('major')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-schoolYear" class="block text-sm font-medium text-gray-700">Tahun
                            Ajaran</label>
                        <select id="edit-schoolYear" wire:model="schoolYear"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Pilih Tahun Ajaran</option>
                            @foreach ($school_years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                        @error('schoolYear')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-teacher" class="block text-sm font-medium text-gray-700">Wali Kelas</label>
                        <select id="edit-teacher" wire:model="teacher"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Pilih Wali Kelas</option>
                            @foreach ($teachers as $teacherOption)
                                <option value="{{ $teacherOption->id }}">{{ $teacherOption->user->name }}</option>
                            @endforeach
                        </select>
                        @error('teacher')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showEditClassModal = false; $wire.cancelEdit()"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" @click="showEditClassModal = false"
                        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Major Confirmation Modal -->
    <div x-cloak x-show="showDeleteMajorModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50 p-4">
        <div x-cloak x-show="showDeleteMajorModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" @click.away="showDeleteMajorModal = false"
            class="relative max-h-[90vh] w-full max-w-md overflow-auto rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-6 flex items-center">
                <div
                    class="mr-4 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Hapus Jurusan</h3>
            </div>

            <div class="mb-6">
                <p class="text-sm text-gray-500">Apakah Anda yakin ingin menghapus jurusan ini? Tindakan ini tidak
                    dapat dibatalkan. Semua data terkait jurusan ini akan dihapus secara permanen.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" @click="showDeleteMajorModal = false"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Batal
                </button>
                <button type="button" @click="$wire.delete(majorToDelete); showDeleteMajorModal = false"
                    class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">
                    Hapus
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Class Confirmation Modal -->
    <div x-cloak x-show="showDeleteClassModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50 p-4">
        <div x-cloak x-show="showDeleteClassModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" @click.away="showDeleteClassModal = false"
            class="relative max-h-[90vh] w-full max-w-md overflow-auto rounded-lg bg-white p-6 shadow-xl">
            <div class="mb-6 flex items-center">
                <div
                    class="mr-4 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Hapus Kelas</h3>
            </div>

            <div class="mb-6">
                <p class="text-sm text-gray-500">Apakah Anda yakin ingin menghapus kelas ini? Tindakan ini tidak dapat
                    dibatalkan. Semua data terkait kelas ini akan dihapus secara permanen.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" @click="showDeleteClassModal = false"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Batal
                </button>
                <button type="button" @click="$wire.deleteClass(classToDelete); showDeleteClassModal = false"
                    class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
