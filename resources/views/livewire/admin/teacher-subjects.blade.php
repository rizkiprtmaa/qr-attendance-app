<?php
// resources/views/livewire/admin/teacher-subjects.php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\SubjectClass;
use App\Models\Classes;
use App\Models\Major;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $teacher;
    public $teacherId;
    public $search = '';

    // Filter properties
    public $filterMajor = '';
    public $filterClass = '';
    public $availableClasses = [];

    // Sorting properties
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public $className = '';
    public $classCode = '';
    public $selectedMajor = null;
    public $selectedClass = null;
    public $majors = [];
    public $classes = [];

    // Tambahkan properti berikut di class
    public $editSubjectClassId = null;
    public $editClassName = '';
    public $editClassCode = '';
    public $editSelectedMajor = null;
    public $editSelectedClass = null;
    public $editClasses = [];
    public $deleteSubjectClassId = null;

    public function mount($teacherId)
    {
        $this->teacherId = $teacherId;
        $this->teacher = User::findOrFail($teacherId);
        $this->majors = Major::all();

        // Load available classes for filter dropdown
        $this->refreshAvailableClasses();
    }

    // Fungsi untuk membuka modal edit
    public function editSubjectClass($subjectClassId)
    {
        $this->editSubjectClassId = $subjectClassId;
        $subjectClass = SubjectClass::findOrFail($subjectClassId);

        $this->editClassName = $subjectClass->class_name;
        $this->editClassCode = $subjectClass->class_code;

        $this->editSelectedMajor = $subjectClass->classes->major_id;
        $this->refreshEditClasses();
        $this->editSelectedClass = $subjectClass->classes_id;

        // Modal dibuka oleh Alpine.js, tidak perlu properti tambahan
    }

    // Fungsi untuk refresh kelas pada edit modal
    public function refreshEditClasses()
    {
        if ($this->editSelectedMajor) {
            $this->editClasses = Classes::where('major_id', $this->editSelectedMajor)->get();
        } else {
            $this->editClasses = [];
        }
    }

    // Fungsi untuk menangani perubahan jurusan pada edit modal
    public function updatedEditSelectedMajor($value)
    {
        $this->editSelectedClass = null;
        $this->refreshEditClasses();
    }

    // Fungsi untuk mengupdate mata pelajaran
    public function updateSubjectClass()
    {
        $this->validate([
            'editClassName' => 'required|string|max:255',
            'editClassCode' => 'required|string|max:50',
            'editSelectedClass' => 'required|exists:classes,id',
        ]);

        try {
            $subjectClass = SubjectClass::findOrFail($this->editSubjectClassId);

            $subjectClass->update([
                'class_name' => $this->editClassName,
                'class_code' => $this->editClassCode,
                'classes_id' => $this->editSelectedClass,
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Mata pelajaran berhasil diperbarui',
            ]);

            $this->refreshAvailableClasses(); // Refresh available classes for filter

            // Modal akan ditutup oleh Alpine.js berdasarkan event show-toast
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui mata pelajaran: ' . $e->getMessage(),
            ]);
        }
    }

    // Fungsi untuk menyiapkan id untuk konfirmasi hapus
    public function confirmDeleteSubjectClass($subjectClassId)
    {
        $this->deleteSubjectClassId = $subjectClassId;
        // Modal ditampilkan oleh Alpine.js
    }

    // Fungsi untuk menghapus mata pelajaran
    public function deleteSubjectClass()
    {
        try {
            $subjectClass = SubjectClass::findOrFail($this->deleteSubjectClassId);

            // Cek apakah mata pelajaran memiliki pertemuan
            $hasSessions = $subjectClass->subjectClassSessions()->exists();

            if ($hasSessions) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Tidak dapat menghapus mata pelajaran yang memiliki pertemuan',
                ]);
                return;
            }

            $subjectClass->delete();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Mata pelajaran berhasil dihapus',
            ]);

            $this->refreshAvailableClasses(); // Refresh available classes for filter

            // Modal akan ditutup oleh Alpine.js berdasarkan event show-toast
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal menghapus mata pelajaran: ' . $e->getMessage(),
            ]);
        }
    }

    public function updatedSelectedMajor($value)
    {
        $this->selectedClass = null;
        $this->refreshClasses();
    }

    public function updatedFilterMajor($value)
    {
        $this->filterClass = ''; // Reset class filter when major changes
        $this->refreshAvailableClasses();
        $this->resetPage(); // Reset pagination when filter changes
    }

    public function refreshAvailableClasses()
    {
        if ($this->filterMajor) {
            // If major filter is selected, show only classes from that major
            $this->availableClasses = Classes::where('major_id', $this->filterMajor)->get();
        } else {
            // Otherwise, get classes from subject classes related to this teacher
            $classIds = SubjectClass::where('user_id', $this->teacherId)->pluck('classes_id')->unique();

            $this->availableClasses = Classes::whereIn('id', $classIds)->get();
        }
    }

    public function refreshClasses()
    {
        if ($this->selectedMajor) {
            $this->classes = Classes::where('major_id', $this->selectedMajor)->get();
        } else {
            $this->classes = [];
        }
    }

    public function resetForm()
    {
        $this->className = '';
        $this->classCode = '';
        $this->selectedMajor = null;
        $this->selectedClass = null;
        $this->classes = [];
        $this->resetErrorBag();
    }

    public function createSubjectClass()
    {
        $this->validate([
            'className' => 'required|string|max:255',
            'classCode' => 'required|string|max:50',
            'selectedClass' => 'required|exists:classes,id',
        ]);

        try {
            SubjectClass::create([
                'user_id' => $this->teacherId,
                'classes_id' => $this->selectedClass,
                'class_name' => $this->className,
                'class_code' => $this->classCode,
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Mata pelajaran berhasil dibuat',
            ]);

            $this->resetForm();
            $this->refreshAvailableClasses(); // Refresh available classes for filter
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal membuat mata pelajaran: ' . $e->getMessage(),
            ]);
        }
    }

    public function clearFilters()
    {
        $this->filterMajor = '';
        $this->filterClass = '';
        $this->search = '';
        $this->resetPage();
        $this->refreshAvailableClasses();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function getSubjectClassesProperty()
    {
        $query = SubjectClass::where('user_id', $this->teacherId);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($query) {
                $query->where('class_name', 'like', "%{$this->search}%")->orWhere('class_code', 'like', "%{$this->search}%");
            });
        }

        // Apply major filter
        if ($this->filterMajor) {
            $query->whereHas('classes', function ($q) {
                $q->where('major_id', $this->filterMajor);
            });
        }

        // Apply class filter
        if ($this->filterClass) {
            $query->where('classes_id', $this->filterClass);
        }

        // Apply sorting
        if ($this->sortField === 'class_name' || $this->sortField === 'class_code' || $this->sortField === 'created_at') {
            $query->orderBy($this->sortField, $this->sortDirection);
        } elseif ($this->sortField === 'class') {
            $query->join('classes', 'subject_classes.classes_id', '=', 'classes.id')->select('subject_classes.*')->orderBy('classes.name', $this->sortDirection);
        } elseif ($this->sortField === 'major') {
            $query->join('classes', 'subject_classes.classes_id', '=', 'classes.id')->join('majors', 'classes.major_id', '=', 'majors.id')->select('subject_classes.*')->orderBy('majors.name', $this->sortDirection);
        }

        return $query->with(['classes.major'])->paginate(10);
    }

    public function getClassLevelBadgeColor($className)
    {
        // Extract angka kelas (10, 11, 12) dari nama kelas
        $classLevel = null;

        // Cek apakah nama kelas dimulai dengan angka kelas
        if (preg_match('/^(10|11|12)/i', $className, $matches)) {
            $classLevel = $matches[1];
        }

        // Tentukan warna berdasarkan tingkat kelas
        return match ($classLevel) {
            '10' => 'bg-emerald-200 text-emerald-800',
            '11' => 'bg-amber-200 text-amber-800',
            '12' => 'bg-purple-200 text-purple-800',
            default => 'bg-gray-200 text-gray-800',
        };
    }

    public function render(): mixed
    {
        return view('livewire.admin.teacher-subjects', [
            'subjectClasses' => $this->subjectClasses,
        ]);
    }
}; ?>

<!-- resources/views/livewire/admin/teacher-subjects.blade.php -->
<div class="mt-12 py-6 md:mt-0" x-data="{
    showCreateModal: false,
    showEditModal: false,
    showDeleteModal: false,

    // Inisialisasi listeners
    init() {
        this.$watch('showEditModal', value => {
            if (!value) document.body.classList.remove('overflow-hidden');
            else document.body.classList.add('overflow-hidden');
        });

        this.$watch('showDeleteModal', value => {
            if (!value) document.body.classList.remove('overflow-hidden');
            else document.body.classList.add('overflow-hidden');
        });

        // Tambahkan event listeners untuk modal
        window.addEventListener('show-edit-modal', () => {
            this.showEditModal = true;
        });

        window.addEventListener('show-delete-modal', () => {
            this.showDeleteModal = true;
        });

        // Tutup modal jika operasi sukses
        window.addEventListener('show-toast', (event) => {
            if (event.detail[0].type === 'success') {
                this.showEditModal = false;
                this.showDeleteModal = false;
            }
        });
        // Tutup modal jika operasi sukses
        window.addEventListener('show-toast', (event) => {
            if (event.detail[0].type === 'error') {
                this.showDeleteModal = false;
            }
        });
    }
}">
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

        <div class="mb-6 md:flex md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Mata Pelajaran {{ $teacher->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola mata pelajaran dan buat pertemuan untuk guru</p>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <button type="button" @click="showCreateModal = true"
                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Buat Mata Pelajaran
                </button>
            </div>
        </div>

        <!-- Filters dan Search -->
        <div class="mb-6 grid grid-cols-2 gap-4 md:flex md:flex-row md:items-end">
            <!-- Search Box -->
            <div class="col-span-2">
                <label for="search" class="sr-only block text-sm font-medium text-gray-700">Cari</label>
                <div class="relative mt-1 rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="block w-full rounded-md border-gray-300 pl-10 text-xs focus:border-blue-500 focus:ring-blue-500 md:text-sm"
                        placeholder="Cari mata pelajaran...">
                </div>
            </div>

            <!-- Filter Jurusan -->
            <div class="md:col-span-1">

                <select wire:model.live="filterMajor" id="filterMajor"
                    class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 md:text-sm">
                    <option value="">Semua Jurusan</option>
                    @foreach ($majors as $major)
                        <option value="{{ $major->id }}">{{ $major->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Kelas -->
            <div class="md:col-span-1">

                <select wire:model.live="filterClass" id="filterClass"
                    class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 md:text-sm"
                    {{ count($availableClasses) === 0 ? 'disabled' : '' }}>
                    <option value="">Semua Kelas</option>
                    @foreach ($availableClasses as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end md:col-span-1">
                <button wire:click="clearFilters" type="button"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Reset Filter
                </button>
            </div>
        </div>

        <!-- Sorting Headers -->
        <div class="mb-4 flex space-x-2 text-sm">
            <button wire:click="sortBy('class_name')"
                class="{{ $sortField === 'class_name' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }} flex items-center rounded-full px-3 py-1 hover:bg-blue-50">
                Mata Pelajaran
                @if ($sortField === 'class_name')
                    @if ($sortDirection === 'asc')
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                @endif
            </button>

            <button wire:click="sortBy('class')"
                class="{{ $sortField === 'class' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }} flex items-center rounded-full px-3 py-1 hover:bg-blue-50">
                Kelas
                @if ($sortField === 'class')
                    @if ($sortDirection === 'asc')
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                @endif
            </button>

            <button wire:click="sortBy('major')"
                class="{{ $sortField === 'major' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }} flex items-center rounded-full px-3 py-1 hover:bg-blue-50">
                Jurusan
                @if ($sortField === 'major')
                    @if ($sortDirection === 'asc')
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                @endif
            </button>

            <button wire:click="sortBy('created_at')"
                class="{{ $sortField === 'created_at' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }} flex items-center rounded-full px-3 py-1 hover:bg-blue-50">
                Terbaru
                @if ($sortField === 'created_at')
                    @if ($sortDirection === 'asc')
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                @endif
            </button>
        </div>

        <!-- Subject Classes List -->
        <div class="bg-white shadow sm:rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($subjectClasses as $subjectClass)
                    <li>
                        <div class="flex items-center px-4 py-4 sm:px-6">
                            <div class="flex min-w-0 flex-1 items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path
                                                d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 px-4">
                                    <div>
                                        <p class="truncate text-sm font-medium text-gray-900">
                                            {{ $subjectClass->class_name }}</p>
                                        <p
                                            class="mt-1 flex flex-row items-center gap-2 truncate text-sm text-gray-500">
                                            <span
                                                class="{{ $this->getClassLevelBadgeColor($subjectClass->classes->name) }} rounded-md px-2 py-1 text-xs">
                                                {{ $subjectClass->classes->name }}</span>
                                            <span
                                                class="{{ $subjectClass->classes->major->badge_color }} inline-flex items-center justify-center rounded-md px-2.5 py-1 text-xs font-medium">{{ $subjectClass->classes->major->code }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.subject.sessions', $subjectClass->id) }}" wire:navigate
                                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:text-sm">
                                    Kelola
                                </a>
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" type="button"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:text-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                    <div x-cloak x-show="open" @click.away="open = false" style="z-index: 100;"
                                        class="absolute bottom-full right-0 mt-2 w-48 origin-bottom-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <button wire:click="editSubjectClass({{ $subjectClass->id }})"
                                            @click="open = false; $dispatch('show-edit-modal')"
                                            class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4 text-gray-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button wire:click="confirmDeleteSubjectClass({{ $subjectClass->id }})"
                                            @click="open = false; $dispatch('show-delete-modal')"
                                            class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4 text-red-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada mata pelajaran</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if ($filterMajor || $filterClass || $search)
                                Tidak ada mata pelajaran yang sesuai dengan filter yang dipilih.
                                <button wire:click="clearFilters" class="text-blue-600 hover:text-blue-800">Reset
                                    filter</button>
                            @else
                                Guru ini belum memiliki mata pelajaran. Klik tombol Buat Mata Pelajaran untuk mulai
                                menambahkan.
                            @endif
                        </p>
                    </li>
                @endforelse
            </ul>

            <!-- Pagination -->
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                {{ $subjectClasses->links() }}
            </div>
        </div>

        <!-- Create Subject Class Modal -->
        <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 text-center sm:block sm:p-0">
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

                    <div class="flex flex-col sm:items-start">
                        <div class="flex flex-col items-center gap-2 text-center md:flex-row md:text-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Buat Mata
                                    Pelajaran Baru</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Buat mata pelajaran baru untuk
                                        {{ $teacher->name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="ml-2 mr-5 mt-3 grid w-full items-center text-center sm:mt-0 sm:text-left">


                            <form class="mr-2 mt-5 text-start" wire:submit.prevent="createSubjectClass">
                                <div class="mb-4">
                                    <label for="className" class="block text-sm font-medium text-gray-700">Nama Mata
                                        Pelajaran</label>
                                    <input type="text" wire:model="className" id="className"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Contoh: Bahasa Inggris">
                                    @error('className')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="classCode" class="block text-sm font-medium text-gray-700">Kode Mata
                                        Pelajaran</label>
                                    <input type="text" wire:model="classCode" id="classCode"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Contoh: B.ING">
                                    @error('classCode')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="selectedMajor"
                                        class="block text-sm font-medium text-gray-700">Jurusan</label>
                                    <select wire:model.live="selectedMajor" id="selectedMajor"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Pilih Jurusan</option>
                                        @foreach ($majors as $major)
                                            <option value="{{ $major->id }}">{{ $major->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="selectedClass"
                                        class="block text-sm font-medium text-gray-700">Kelas</label>
                                    <select wire:model="selectedClass" id="selectedClass"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        {{ !$selectedMajor ? 'disabled' : '' }}>
                                        <option value="">Pilih Kelas</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }} -
                                                {{ $class->major->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedClass')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                            x-on:show-toast.window="if ($event.detail[0].type === 'success') showCreateModal = false">
                            <span wire:loading.remove wire:target="createSubjectClass">Tambah Mapel</span>
                            <span wire:loading wire:target="createSubjectClass"
                                class="inline-flex items-center gap-1">
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

        <!-- Edit Subject Class Modal -->
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

                    <div class="flex flex-col sm:items-start">
                        <div class="flex flex-col items-center gap-2 text-center md:flex-row md:text-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Edit Mata
                                    Pelajaran</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Edit mata pelajaran untuk
                                        {{ $teacher->name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="ml-2 mr-5 mt-3 grid w-full items-center text-center sm:mt-0 sm:text-left">
                            <form class="mr-2 mt-5 text-start" wire:submit.prevent="updateSubjectClass">
                                <div class="mb-4">
                                    <label for="editClassName" class="block text-sm font-medium text-gray-700">Nama
                                        Mata
                                        Pelajaran</label>
                                    <input type="text" wire:model="editClassName" id="editClassName"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Contoh: Bahasa Inggris">
                                    @error('editClassName')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="editClassCode" class="block text-sm font-medium text-gray-700">Kode
                                        Mata
                                        Pelajaran</label>
                                    <input type="text" wire:model="editClassCode" id="editClassCode"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Contoh: B.ING">
                                    @error('editClassCode')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="editSelectedMajor"
                                        class="block text-sm font-medium text-gray-700">Jurusan</label>
                                    <select wire:model.live="editSelectedMajor" id="editSelectedMajor"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Pilih Jurusan</option>
                                        @foreach ($majors as $major)
                                            <option value="{{ $major->id }}">{{ $major->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="editSelectedClass"
                                        class="block text-sm font-medium text-gray-700">Kelas</label>
                                    <select wire:model="editSelectedClass" id="editSelectedClass"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        {{ !$editSelectedMajor ? 'disabled' : '' }}>
                                        <option value="">Pilih Kelas</option>
                                        @foreach ($editClasses as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }} -
                                                {{ $class->major->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('editSelectedClass')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                        </div>
                    </div>

                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                            <span wire:loading.remove wire:target="updateSubjectClass">Simpan Perubahan</span>
                            <span wire:loading wire:target="updateSubjectClass"
                                class="inline-flex items-center gap-1">
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
                        <button type="button" @click="showEditModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                    </form>
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
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Hapus Mata
                                Pelajaran</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Apakah Anda yakin ingin menghapus mata pelajaran ini?
                                    Tindakan ini tidak dapat dibatalkan.</p>
                                <p class="mt-2 text-sm text-yellow-600">Catatan: Mata pelajaran yang memiliki pertemuan
                                    tidak dapat dihapus.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="deleteSubjectClass"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                            Hapus
                        </button>
                        <button type="button" @click="showDeleteModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
