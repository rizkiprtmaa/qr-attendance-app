<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\SubjectClass;
use App\Models\Classes;
use App\Models\SubjectClassSession;
use App\Models\Major;

new class extends Component {
    use WithPagination;

    public $subjectClassId;
    public $classId;
    public $teacherId;
    public $className;
    public $classCode;
    public $major = '';
    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    // Untuk edit kelas
    public $editingSubjectClass = null;

    public $editSubjectClassId;
    public $editClassName;
    public $editClassCode;
    public $editMajor;
    public $editClassId;

    public $totalJP = 0;
    public $totalSubstitution = 0;

    public function mount()
    {
        $this->teacherId = auth()->user()->id;
        $firstMajor = Major::first();
        if ($firstMajor) {
            $this->major = $firstMajor->id;
        }

        $this->calculateTotalJP($this->teacherId);
    }

    public function calculateTotalJP($teacherId)
    {
        // 1. Hitung JP dari kelas reguler (yang sudah Anda implementasikan)
        $regularJP = SubjectClassSession::whereHas('subjectClass', function ($query) use ($teacherId) {
            $query->where('user_id', $teacherId);
        })
            ->whereNull('created_by_substitute')
            ->sum('jam_pelajaran');

        // 2. Hitung JP dari kelas yang digantikan guru ini (sebagai guru pengganti)
        $substitutionJP = SubjectClassSession::whereHas('substitutionRequest', function ($query) use ($teacherId) {
            $query->where('substitute_teacher_id', $teacherId)->whereIn('status', ['approved', 'completed']);
        })->sum('jam_pelajaran');

        // Total JP
        $this->totalJP = $regularJP + $substitutionJP;

        $this->totalSubstitution = SubjectClassSession::whereHas('substitutionRequest', function ($query) use ($teacherId) {
            $query->where('substitute_teacher_id', $teacherId)->whereIn('status', ['approved', 'completed']);
        })->count();
    }

    // Helper function untuk mendapatkan warna badge berdasarkan tingkat kelas
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
        // Query dasar untuk subject classes
        $query = SubjectClass::where('subject_classes.user_id', $this->teacherId)->with('classes.major', 'classes.student');

        // Tambahkan pencarian jika ada
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('class_name', 'like', '%' . $this->search . '%')
                    ->orWhere('class_code', 'like', '%' . $this->search . '%')
                    ->orWhereHas('classes', function ($classQuery) {
                        $classQuery->where('name', 'like', '%' . $this->search . '%')->orWhereHas('major', function ($majorQuery) {
                            $majorQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                    });
            });
        }

        // Terapkan sortir
        if ($this->sortBy === 'class_name' || $this->sortBy === 'class_code' || $this->sortBy === 'created_at') {
            $query->orderBy($this->sortBy, $this->sortDirection);
        } elseif ($this->sortBy === 'class') {
            // Sortir berdasarkan nama kelas
            $query->join('classes', 'subject_classes.classes_id', '=', 'classes.id')->select('subject_classes.*')->orderBy('classes.name', $this->sortDirection);
        } elseif ($this->sortBy === 'major') {
            // Sortir berdasarkan jurusan
            $query->join('classes', 'subject_classes.classes_id', '=', 'classes.id')->join('majors', 'classes.major_id', '=', 'majors.id')->select('subject_classes.*')->orderBy('majors.name', $this->sortDirection);
        }

        // Ambil kelas yang difilter berdasarkan major jika dipilih
        $classes = $this->major ? Classes::where('major_id', $this->major)->get() : collect([]);

        // Set classId ke kelas pertama secara otomatis jika belum dipilih
        if ($classes->isNotEmpty() && !$this->classId) {
            $this->classId = $classes->first()->id;
        }

        // Pagination
        $subjectClasses = $query->paginate(10);

        return view('livewire.teacher.create-attendances-class', [
            'classes' => $classes,
            'majors' => Major::all(),
            'subjectClasses' => $subjectClasses,
            'totalClasses' => $subjectClasses->total(),
            'totalSessions' => SubjectClassSession::whereIn('subject_class_id', $subjectClasses->pluck('id'))->count(),
            'totalHours' => SubjectClassSession::whereIn('subject_class_id', $subjectClasses->pluck('id'))->whereNull('created_by_substitute')->selectRaw('SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) as total_hours')->value('total_hours') ?? 0,
        ]);
    }

    // Method untuk memfilter kelas berdasarkan jurusan
    public function updatedMajor($majorId)
    {
        // Reset classId saat major berubah dan set ke kelas pertama
        $classes = Classes::where('major_id', $majorId)->get();

        // Jika sedang dalam mode edit, simpan classId saat ini jika ada di jurusan yang sama
        if ($this->editingSubjectClass) {
            $currentClassInNewMajor = $classes->where('id', $this->classId)->first();

            // Jika kelas saat ini tidak ada di jurusan baru, pilih yang pertama
            if (!$currentClassInNewMajor) {
                $this->classId = $classes->isNotEmpty() ? $classes->first()->id : null;
            }
            // Jika ada, tetap gunakan kelas yang sama (tidak perlu mengubah classId)
        } else {
            // Mode tambah baru: selalu pilih kelas pertama
            $this->classId = $classes->isNotEmpty() ? $classes->first()->id : null;
        }
    }

    // Method untuk sorting
    public function sortColumnBy($field)
    {
        // Jika kolom yang sama diklik, ubah arah sorting
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Jika berganti kolom, default ke ascending
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Method untuk memulai edit kelas
    public function editSubjectClass($subjectClassId)
    {
        $this->editingSubjectClass = SubjectClass::findOrFail($subjectClassId);
        $this->className = $this->editingSubjectClass->class_name;
        $this->classCode = $this->editingSubjectClass->class_code;
        // Simpan data jurusan dan kelas saat ini
        $currentMajorId = $this->editingSubjectClass->classes->major_id;
        $currentClassId = $this->editingSubjectClass->classes_id;

        // Set major terlebih dahulu
        $this->major = $currentMajorId;

        // Dapatkan kelas-kelas untuk jurusan ini
        $availableClasses = Classes::where('major_id', $currentMajorId)->get();

        // Kemudian tetapkan classId setelah kelas-kelas tersedia
        $this->classId = $currentClassId;

        // Dispatch event untuk Alpine modal
        $this->dispatch('open-edit-modal');
    }

    // Method untuk update kelas
    public function updateSubjectClass()
    {
        $this->validate([
            'className' => 'required',
            'classCode' => 'required',
            'major' => 'required|exists:majors,id',
            'classId' => 'required|exists:classes,id',
        ]);

        try {
            $this->editingSubjectClass->update([
                'class_name' => $this->className,
                'class_code' => $this->classCode,
                'classes_id' => $this->classId,
            ]);

            // Ganti session flash dengan event dispatch
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Kelas berhasil diperbarui',
            ]);

            $this->editingSubjectClass = null;
            $this->reset(['className', 'classCode', 'classId', 'major']);

            // Tutup modal
            $this->dispatch('close-edit-modal');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui kelas: ' . $e->getMessage(),
            ]);
        }
    }

    // Method untuk membatalkan edit
    public function cancelEdit()
    {
        // Reset semua state terkait edit
        $this->editingSubjectClass = null;

        // Reset semua field form
        $this->reset(['className', 'classCode']);

        // Kembalikan major dan classId ke nilai default (opsional)
        $firstMajor = Major::first();
        if ($firstMajor) {
            $this->major = $firstMajor->id;
            $classes = Classes::where('major_id', $this->major)->get();
            $this->classId = $classes->isNotEmpty() ? $classes->first()->id : null;
        } else {
            $this->reset(['major', 'classId']);
        }

        // Tutup modal
        $this->dispatch('close-edit-modal');
    }

    // Method untuk menghapus kelas
    public function deleteSubjectClass()
    {
        try {
            $subjectClass = SubjectClass::findOrFail($this->subjectClassId);

            // Cek apakah kelas memiliki sesi atau data terkait
            $hasRelatedData = $subjectClass->subjectClassSessions()->exists();

            if ($hasRelatedData) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Tidak dapat menghapus kelas yang memiliki riwayat pertemuan.',
                ]);
                // Dispatch event untuk Alpine.js
                $this->dispatch('delete-failed');
                return;
            }

            $subjectClass->delete();
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Kelas berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal menghapus kelas: ' . $e->getMessage(),
            ]);
            $this->dispatch('delete-failed');
        }
    }

    // Delete session confirmation
    public function confirmDeleteSubject($subjectClassId)
    {
        $this->subjectClassId = $subjectClassId;
    }

    public function createClasses()
    {
        $this->validate([
            'className' => 'required',
            'classCode' => 'required',
            'major' => 'required|exists:majors,id',
            'classId' => 'required|exists:classes,id',
        ]);

        try {
            SubjectClass::create([
                'class_name' => $this->className,
                'class_code' => $this->classCode,
                'user_id' => $this->teacherId,
                'classes_id' => $this->classId,
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Kelas berhasil dibuat',
            ]);

            $this->reset(['className', 'classCode', 'classId', 'major']);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal membuat kelas: ' . $e->getMessage(),
            ]);
        }
    }
}; ?>

<div x-data="{
    showCreateClasses: false,
    deleteSubjectModal: false,
    sessionMenuOpen: null,
    openSummary: false,
    toggleMenu(id) {
        this.sessionMenuOpen = this.sessionMenuOpen === id ? null : id;
    }
}" class="mt-12 md:mt-0">
    <div class="item-center relative mb-5 flex w-full text-gray-500 md:mt-0 md:hidden">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari kelas..."
            class="w-full rounded-full border border-gray-300 px-4 py-2 pl-8 text-sm" />
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="absolute left-3 top-3 h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
    </div>
    <div class="flex flex-row justify-between md:justify-start">
        <button @click="showCreateClasses = true" type="button"
            class="flex flex-row items-center gap-1 rounded-full bg-blue-600 px-4 py-2 font-inter text-sm text-white shadow-md hover:bg-blue-700">Buat
            Mata Pelajaran<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                class="size-5">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z"
                    clip-rule="evenodd" />
            </svg>
        </button>
        <button @click="openSummary = !openSummary" class="hidden px-4 py-2 text-xs text-gray-500"
            x-text="openSummary ? 'Tutup Ringkasan' : 'Tampilkan Ringkasan'">

        </button>


    </div>

    <div class="mb-6 mt-6 hidden grid-cols-2 gap-4 md:grid md:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Kelas Aktif</h3>
            <p class="text-2xl font-bold">{{ $totalClasses }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Total Pertemuan</h3>
            <p class="text-2xl font-bold">{{ $totalSessions }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Total JP</h3>
            <p class="text-2xl font-bold">{{ $totalJP }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Kelas Pengganti</h3>
            <p class="text-2xl font-bold">{{ $totalSubstitution }}</p>
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






    <div>
        <div class="mb-6 mt-10 hidden items-center md:flex">
            <p class="font-inter text-lg font-medium">Kelas Aktif</p>

        </div>

        <!-- Header Filters -->
        <div class="mb-4 mt-8 flex items-center justify-start text-sm md:mt-0 md:flex-row md:justify-between">
            <div class="flex space-x-2 md:space-x-5">
                <button x-on:click="$wire.sortColumnBy('created_at')" class="flex items-center"
                    :class="{
                        'font-medium text-white bg-blue-500 py-2 px-3 text-xs md:text-base rounded-full': '{{ $sortBy }}'
                        === 'created_at',
                        'text-gray-500 hover:text-gray-700 text-xs md:text-base border rounded-full py-2 px-3': '{{ $sortBy }}'
                        !== 'created_at'
                    }">
                    Terbaru
                    @if ($sortBy === 'created_at')
                        @if ($sortDirection === 'desc')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25 12 21m0 0-3.75-3.75M12 21V3" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 6.75 12 3m0 0 3.75 3.75M12 3v18" />
                            </svg>
                        @endif
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="ml-1 h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                        </svg>


                    @endif
                </button>

                <button wire:click="sortColumnBy('class_name')" class="flex items-center"
                    :class="{
                        'font-medium text-white bg-blue-500 py-1.5 px-3 rounded-full text-xs md:text-base': '{{ $sortBy }}'
                        === 'class_name',
                        'text-gray-500 hover:text-gray-700 text-xs md:text-base border rounded-full py-2 px-3': '{{ $sortBy }}'
                        !== 'class_name'
                    }">
                    Mapel
                    @if ($sortBy === 'class_name')
                        @if ($sortDirection === 'asc')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25 12 21m0 0-3.75-3.75M12 21V3" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 6.75 12 3m0 0 3.75 3.75M12 3v18" />
                            </svg>
                        @endif
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>

                    @endif
                </button>

                <!-- Tambahkan opsi sortir lainnya seperti kelas dan jurusan -->
                <button wire:click="sortColumnBy('class')" class="flex items-center"
                    :class="{
                        'font-medium text-white bg-blue-500 py-1.5 px-3 rounded-full text-xs md:text-base': '{{ $sortBy }}'
                        === 'class',
                        'text-gray-500 hover:text-gray-700 text-xs md:text-base border rounded-full py-2 px-3': '{{ $sortBy }}'
                        !== 'class'
                    }">
                    Kelas
                    @if ($sortBy === 'class')
                        @if ($sortDirection === 'asc')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25 12 21m0 0-3.75-3.75M12 21V3" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 6.75 12 3m0 0 3.75 3.75M12 3v18" />
                            </svg>
                        @endif
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>

                    @endif
                </button>

                <button wire:click="sortColumnBy('major')" class="flex items-center"
                    :class="{
                        'font-medium text-white bg-blue-500 py-1.5 px-3 rounded-full text-xs md:text-base': '{{ $sortBy }}'
                        === 'major',
                        'text-gray-500 hover:text-gray-700 text-xs md:text-base border rounded-full py-2 px-3': '{{ $sortBy }}'
                        !== 'major'
                    }">
                    Jurusan
                    @if ($sortBy === 'major')
                        @if ($sortDirection === 'asc')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25 12 21m0 0-3.75-3.75M12 21V3" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 6.75 12 3m0 0 3.75 3.75M12 3v18" />
                            </svg>
                        @endif
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="ml-1 h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819" />
                        </svg>

                    @endif
                </button>
            </div>

            <div class="item-center relative mt-4 hidden text-gray-500 md:mt-0 md:block">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari kelas..."
                    class="w-64 rounded-full border border-gray-300 px-4 py-2 pl-8 text-sm" />
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="absolute left-3 top-3 h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
        </div>

        <!-- Table View -->
        <div class="hidden rounded-lg border border-gray-200 bg-white shadow md:block">
            <!-- Table Header -->
            <div
                class="grid grid-cols-4 border-b border-gray-200 bg-gray-50 py-4 text-xs font-medium uppercase tracking-wider text-gray-500 md:grid-cols-12">
                <div class="col-span-2 px-6 md:col-span-3">Mata Pelajaran</div>
                <div class="col-span-2 hidden px-3 text-center md:block">Kelas</div>
                <div class="col-span-1 px-1 text-center md:col-span-2 md:px-3">Jurusan</div>
                <div class="col-span-2 hidden px-1 text-center md:block md:px-3">Jumlah Siswa</div>
                <div class="col-span-2 hidden px-1 text-center md:block md:px-3">Pertemuan</div>
                <div class="col-span-1 px-1 text-center md:px-3"></div>
            </div>

            <!-- Table Body -->
            @if ($subjectClasses->count() > 0)
                @foreach ($subjectClasses as $subjectClass)
                    <div
                        class="grid grid-cols-4 items-center border-b border-gray-200 hover:bg-gray-50 md:grid-cols-12">
                        <!-- Subject Info -->
                        <div class="col-span-2 px-6 py-4 md:col-span-3">
                            <div class="flex items-center">
                                <div
                                    class="mr-3 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><a
                                            href="{{ route('subject.detail', $subjectClass) }}"
                                            wire:navigate>{{ $subjectClass->class_name }}</a></div>
                                    <div class="md:blocktext-xs hidden text-gray-500">{{ $subjectClass->class_code }}
                                    </div>
                                    <div class="flex md:hidden"><span
                                            class="rounded-md bg-blue-200 px-2 py-1 text-[0.625rem] text-blue-600">{{ $subjectClass->classes->name }}</span>
                                        <span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Class Info -->
                        <div class="col-span-2 hidden px-3 py-4 md:block">
                            <div class="flex items-center justify-center">
                                <div
                                    class="{{ $this->getClassLevelBadgeColor($subjectClass->classes->name) }} rounded-md px-2 py-1 text-xs">
                                    {{ $subjectClass->classes->name }}
                                </div>
                            </div>
                        </div>

                        <!-- Major Info -->
                        <div class="col-span-1 px-1 py-4 md:col-span-2 md:px-3">
                            <div class="flex items-center justify-center">
                                <div
                                    class="{{ $subjectClass->classes->major->badge_color }} rounded-md px-2 py-1 text-center text-[0.625rem] md:text-xs">
                                    {{ $subjectClass->classes->major->name }}
                                </div>
                            </div>
                        </div>

                        <!-- Student Count -->
                        <div class="col-span-1 hidden px-1 py-4 md:col-span-2 md:block md:px-3">
                            <div class="flex items-center justify-center">

                                <span
                                    class="text-sm text-gray-600">{{ $subjectClass->classes->student->count() }}</span>
                            </div>
                        </div>

                        <!-- Meetings Count -->
                        <div class="col-span-1 hidden px-0 py-4 md:col-span-2 md:block md:px-3">
                            <div class="flex items-center justify-center">

                                @php
                                    $sessionCount = App\Models\SubjectClassSession::where(
                                        'subject_class_id',
                                        $subjectClass->id,
                                    )->count();
                                @endphp
                                <span class="text-sm text-gray-600">{{ $sessionCount }}</span>
                            </div>
                        </div>

                        <!-- Actions Column dengan Fixed Dropdown Positioning -->
                        <div class="relative col-span-1 text-center">
                            <div class="relative inline-block text-left">
                                <button @click="toggleMenu({{ $subjectClass->id }})" type="button"
                                    class="rounded-full bg-blue-300 px-3 text-white hover:bg-blue-100 hover:text-gray-700 focus:outline-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                    </svg>

                                </button>

                                <!-- Fixed Dropdown with Absolute Positioning -->
                                <div x-cloak x-show="sessionMenuOpen === {{ $subjectClass->id }}"
                                    style="position: absolute; bottom: 100%; right: 0; margin-bottom: 0.5rem;"
                                    @click.away="sessionMenuOpen = null"
                                    class="absolute bottom-full right-0 mt-2 w-48 origin-bottom-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                    style="z-index: 100;" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95">

                                    <div class="py-1">
                                        <a href="{{ route('subject.detail', $subjectClass) }}" wire:navigate
                                            class="flex w-full items-center px-4 py-2 text-sm text-slate-900 hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="mr-2 h-4 w-4 text-blue-500">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                                            </svg>
                                            Kelola
                                        </a>

                                        <button wire:click="editSubjectClass({{ $subjectClass->id }})"
                                            @click="sessionMenuOpen = null"
                                            class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="mr-2 h-4 w-4 text-blue-500">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            Edit Kelas
                                        </button>

                                        <button wire:click="confirmDeleteSubject({{ $subjectClass['id'] }})"
                                            @click="deleteSubjectModal = true; sessionMenuOpen = null"
                                            class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="mr-2 h-4 w-4 text-red-500">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                            Hapus Kelas
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mx-auto h-12 w-12 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada kelas</h3>
                    <p class="mt-1 text-sm text-gray-500">Buat kelas baru untuk mulai mengelola presensi.</p>
                    <div class="mt-6">
                        <button @click="showCreateClasses = true" type="button"
                            class="inline-flex items-center rounded-md bg-blue-500 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="-ml-0.5 mr-1.5 h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Buat Kelas
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>


    <!-- Perbaikan card mobile dengan notifikasi jumlah pertemuan -->
    <div class="md:hidden">
        @if ($subjectClasses->count() > 0)
            <div class="mt-5 divide-y divide-gray-200 rounded-lg bg-white shadow">
                @foreach ($subjectClasses as $subjectClass)
                    <div class="p-4">
                        <div class="flex flex-row items-center justify-between">
                            <div class="flex items-center">
                                <div class="relative mr-3 inline-flex">

                                    <!-- Icon kelas -->
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><a
                                            href="{{ route('subject.detail', $subjectClass) }}"
                                            wire:navigate>{{ $subjectClass->class_name }}</a></div>

                                    <div class="mt-1 flex flex-row gap-1">
                                        <span
                                            class="{{ $this->getClassLevelBadgeColor($subjectClass->classes->name) }} rounded-md px-2 py-1 text-[0.625rem]">{{ $subjectClass->classes->name }}</span>
                                        <span
                                            class="{{ $subjectClass->classes->major->badge_color }} rounded-md px-2 py-1 text-[0.625rem]">{{ $subjectClass->classes->major->code }}</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Actions Column -->
                            <div class="relative flex flex-row items-center text-center">
                                <div class="relative inline-block text-left">
                                    <button @click="toggleMenu({{ $subjectClass->id }})" type="button"
                                        class="rounded-md bg-blue-300 px-3 text-white hover:bg-blue-100 hover:text-gray-700 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                        </svg>
                                    </button>

                                    <!-- Dropdown -->
                                    <div x-cloak x-show="sessionMenuOpen === {{ $subjectClass->id }}"
                                        style="position: absolute; bottom: 100%; right: 0; margin-bottom: 0.5rem;"
                                        @click.away="sessionMenuOpen = null"
                                        class="absolute bottom-full right-0 mt-2 w-48 origin-bottom-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                        style="z-index: 100;" x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95">

                                        <div class="py-1">
                                            <a href="{{ route('subject.detail', $subjectClass) }}" wire:navigate
                                                class="flex w-full items-center px-4 py-2 text-sm text-slate-900 hover:bg-gray-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="mr-2 h-4 w-4 text-blue-500">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                                                </svg>
                                                Kelola
                                            </a>

                                            <button wire:click="editSubjectClass({{ $subjectClass->id }})"
                                                @click="sessionMenuOpen = null"
                                                class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="mr-2 h-4 w-4 text-blue-500">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                                Edit Kelas
                                            </button>

                                            <button wire:click="confirmDeleteSubject({{ $subjectClass['id'] }})"
                                                @click="deleteSubjectModal = true; sessionMenuOpen = null"
                                                class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="mr-2 h-4 w-4 text-red-500">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                Hapus Kelas
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Null state for mobile -->
            <div class="mt-5 rounded-lg bg-white p-6 text-center shadow">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mx-auto h-10 w-10 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada kelas</h3>
                <p class="mt-1 text-sm text-gray-500">Buat kelas baru untuk mulai mengelola presensi.</p>
                <div class="mt-4">
                    <button @click="showCreateClasses = true" type="button"
                        class="inline-flex items-center rounded-md bg-blue-500 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="-ml-0.5 mr-1.5 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Buat Kelas
                    </button>
                </div>
            </div>
        @endif
    </div>


    <!-- Pagination -->
    <div class="mt-4">
        {{ $subjectClasses->links() }}
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
                <p class="text-center font-inter text-sm text-gray-600">Kelola presensi mata pelajaran berdasarkan sesi
                    dan
                    kelas.
                </p>
            </div>
            <!-- Dialog Body -->
            <form wire:submit="createClasses">
                <div class="px-5 md:px-8">
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
                                <option value="{{ $class->id }}">{{ $class->name }} -
                                    {{ $class->major->name }}
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
                    <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 font-inter text-sm font-medium tracking-wide text-white hover:bg-blue-700"
                        type="submit" x-on:click="showCreateClasses = false">Buat Kelas</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Edit Modal Component -->
    <div x-data="{ editModalOpen: false }" x-on:open-edit-modal.window="editModalOpen = true"
        x-on:close-edit-modal.window="editModalOpen = false">

        <div x-cloak x-show="editModalOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 bg-black/50" @click="editModalOpen = false">
        </div>

        <div x-cloak x-show="editModalOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-50 flex items-center justify-center p-4">

            <div @click.stop class="w-full max-w-xl rounded-lg bg-white p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold">Edit Kelas</h3>
                    <button wire:click="cancelEdit" @click="editModalOpen = false"
                        class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="updateSubjectClass" class="mt-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nama Mata Pelajaran</label>
                        <input wire:model="className" type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        @error('className')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Kode Kelas</label>
                        <input wire:model="classCode" type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        @error('classCode')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Jurusan</label>
                        <select wire:model.live="major" wire:key="class-select-{{ $major }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach ($majors as $majorItem)
                                <option value="{{ $majorItem->id }}">{{ $majorItem->name }}</option>
                            @endforeach
                        </select>
                        @error('major')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <select wire:model="classId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }} - {{ $class->major->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('classId')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="cancelEdit" @click="editModalOpen = false"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm">Batal</button>
                        <button type="submit"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Simpan
                            Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal Delete Confirmation -->
    <div x-cloak x-show="deleteSubjectModal" x-transition.opacity.duration.200ms
        x-on:delete-failed.window="deleteSubjectModal = false" x-on:keydown.esc.window="deleteSubjectModal = false"
        x-on:click.self="deleteSubjectModal = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="deleteSubjectModalTitle">
        <!-- Modal Dialog -->
        <div x-show="deleteSubjectModal" x-transition:enter="transition ease-out duration-200 delay-100"
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
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="deleteSubjectModalTitle">
                        Hapus Mata Pelajaran
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Apakah Anda yakin ingin menghapus mapel ini? Semua data presensi siswa untuk mapel
                            ini juga akan dihapus. Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>
                <!-- Fixed button container -->
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center sm:gap-3">
                    <button type="button" @click="deleteSubjectModal = false"
                        class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                    <button type="button" wire:click="deleteSubjectClass" @click="deleteSubjectModal = false"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto sm:text-sm">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
