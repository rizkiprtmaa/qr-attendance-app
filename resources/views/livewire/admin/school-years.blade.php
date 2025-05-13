<?php

use Livewire\Volt\Component;
use App\Models\SchoolYear;
use App\Models\Classes;

new class extends Component {
    public $schoolYears;
    public $start_month;
    public $end_month;

    // Untuk modal edit
    public $editSchoolYearId;
    public $editName;
    public $editStartMonth;
    public $editEndMonth;

    // Untuk modal konfirmasi hapus
    public $deleteSchoolYearId;
    public $schoolYearName;

    public function rules()
    {
        return [
            'schoolYears' => 'required|string|max:255',
            'start_month' => 'required|date',
            'end_month' => 'required|date|after_or_equal:start_month',
            'editName' => 'required|string|max:255',
            'editStartMonth' => 'required|date',
            'editEndMonth' => 'required|date|after_or_equal:editStartMonth',
        ];
    }

    public function messages()
    {
        return [
            'end_month.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai',
            'editEndMonth.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai',
        ];
    }

    public function createSchoolYear()
    {
        $this->validate([
            'schoolYears' => 'required',
            'start_month' => 'required|date',
            'end_month' => 'required|date|after_or_equal:start_month',
        ]);

        SchoolYear::create([
            'name' => $this->schoolYears,
            'start_date' => $this->start_month,
            'end_date' => $this->end_month,
        ]);

        $this->reset(['schoolYears', 'start_month', 'end_month']);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Tahun ajaran berhasil ditambahkan']);
    }

    public function confirmDelete($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);
        $this->deleteSchoolYearId = $id;
        $this->schoolYearName = $schoolYear->name;
    }

    public function delete()
    {
        $schoolYear = SchoolYear::findOrFail($this->deleteSchoolYearId);

        // Cek apakah ada kelas terkait
        $classCount = Classes::where('school_year_id', $this->deleteSchoolYearId)->count();

        if ($classCount > 0) {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Tahun ajaran tidak dapat dihapus karena sudah memiliki kelas']);
            $this->reset(['deleteSchoolYearId', 'schoolYearName']);
            return;
        }

        $schoolYear->delete();
        $this->reset(['deleteSchoolYearId', 'schoolYearName']);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Tahun ajaran berhasil dihapus']);
    }

    public function showEditModal($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);

        // Cek apakah ada kelas terkait
        $classCount = Classes::where('school_year_id', $id)->count();

        if ($classCount > 0) {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Tahun ajaran tidak dapat diedit karena sudah memiliki kelas']);
            return;
        }

        $this->editSchoolYearId = $id;
        $this->editName = $schoolYear->name;
        $this->editStartMonth = $schoolYear->start_date;
        $this->editEndMonth = $schoolYear->end_date;
    }

    public function updateSchoolYear()
    {
        $this->validate([
            'editName' => 'required',
            'editStartMonth' => 'required|date',
            'editEndMonth' => 'required|date|after_or_equal:editStartMonth',
        ]);

        $schoolYear = SchoolYear::findOrFail($this->editSchoolYearId);

        // Cek apakah ada kelas terkait
        $classCount = Classes::where('school_year_id', $this->editSchoolYearId)->count();

        if ($classCount > 0) {
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Tahun ajaran tidak dapat diedit karena sudah memiliki kelas']);
            $this->reset(['editSchoolYearId', 'editName', 'editStartMonth', 'editEndMonth']);
            return;
        }

        $schoolYear->update([
            'name' => $this->editName,
            'start_date' => $this->editStartMonth,
            'end_date' => $this->editEndMonth,
        ]);

        $this->reset(['editSchoolYearId', 'editName', 'editStartMonth', 'editEndMonth']);
        $this->dispatch('show-toast', ['type' => 'success', 'message' => 'Tahun ajaran berhasil diperbarui']);
    }

    public function getClassCount($schoolYearId)
    {
        return Classes::where('school_year_id', $schoolYearId)->count();
    }

    public function render(): mixed
    {
        $years = SchoolYear::all();

        // Hitung jumlah kelas untuk setiap tahun ajaran
        foreach ($years as $year) {
            $year->class_count = $this->getClassCount($year->id);
        }

        return view('livewire.admin.school-years', [
            'years' => $years,
        ]);
    }
}; ?>

<div x-data="{ schoolYearsModal: false, editModal: false, deleteModal: false }" class="mt-12 md:mt-0">

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

    <div class="flex flex-row justify-between">

        <button @click="schoolYearsModal = true"
            class="flex flex-row items-center gap-2 rounded-md bg-blue-500 px-4 py-2 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <span>Tambah Tahun Ajar</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </button>
    </div>

    <!-- Desktop Table View (md and up) -->
    <div class="mt-5 hidden md:block">
        <div class="overflow-hidden rounded-lg border border-gray-200 shadow-md">
            <table class="w-full text-left text-sm text-gray-500">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 font-medium tracking-wider" wire:click="setSortBy('name')">
                            Tahun Ajaran
                        </th>
                        <th scope="col" class="px-6 py-3 font-medium tracking-wider" wire:click="setSortBy('email')">
                            Bulan Mulai
                        </th>
                        <th scope="col" class="px-6 py-3 font-medium tracking-wider"
                            wire:click="setSortBy('status')">
                            Bulan Selesai
                        </th>
                        <th scope="col" class="px-6 py-3 font-medium tracking-wider"
                            wire:click="setSortBy('roles.name')">
                            Jumlah Kelas
                        </th>
                        <th scope="col" class="px-6 py-3 text-right font-medium tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($years as $year)
                        <tr wire:key="year-{{ $year->id }}" class="transition hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                {{ $year->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ \Carbon\Carbon::parse($year->start_date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ \Carbon\Carbon::parse($year->end_date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                    {{ $year->class_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end space-x-3">
                                    <button wire:click="showEditModal({{ $year->id }})"
                                        @click="editModal = {{ $year->class_count > 0 ? 'false' : 'true' }}"
                                        class="{{ $year->class_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }} font-medium text-blue-600 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                        {{ $year->class_count > 0 ? 'disabled' : '' }}>
                                        <span class="sr-only">Edit</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path
                                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $year->id }})"
                                        @click="deleteModal = {{ $year->class_count > 0 ? 'false' : 'true' }}"
                                        class="{{ $year->class_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }} font-medium text-red-600 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                        {{ $year->class_count > 0 ? 'disabled' : '' }}>
                                        <span class="sr-only">Delete</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-lg font-medium">Belum ada tahun ajaran</p>
                                    <p class="text-sm">Klik tombol 'Tambah Tahun Ajar' untuk menambahkan tahun ajaran
                                        baru</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card View (smaller than md) -->
    <div class="mt-5 space-y-4 md:hidden">
        @forelse ($years as $year)
            <div wire:key="mobile-year-{{ $year->id }}"
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">{{ $year->name }}</h3>
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                            {{ $year->class_count }} Kelas
                        </span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Bulan Mulai</p>
                            <p class="font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($year->start_date)->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Bulan Selesai</p>
                            <p class="font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($year->end_date)->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <button wire:click="showEditModal({{ $year->id }})"
                        @click="editModal = {{ $year->class_count > 0 ? 'false' : 'true' }}"
                        class="{{ $year->class_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }} inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-blue-600 shadow-sm hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        {{ $year->class_count > 0 ? 'disabled' : '' }}>
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1.5 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                        Edit
                    </button>
                    <button wire:click="confirmDelete({{ $year->id }})"
                        @click="deleteModal = {{ $year->class_count > 0 ? 'false' : 'true' }}"
                        class="{{ $year->class_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }} inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-medium text-red-600 shadow-sm hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        {{ $year->class_count > 0 ? 'disabled' : '' }}>
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1.5 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-lg bg-white p-6 text-center shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-4 h-12 w-12 text-gray-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mb-1 text-lg font-medium text-gray-900">Belum ada tahun ajaran</h3>
                <p class="mb-4 text-sm text-gray-500">Klik tombol dibawah untuk menambahkan tahun ajaran baru</p>
                <button @click="schoolYearsModal = true"
                    class="inline-flex items-center rounded-md bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Tambah Tahun Ajar
                </button>
            </div>
        @endforelse
    </div>

    {{-- Modal tambah tahun ajaran --}}
    <div x-cloak x-show="schoolYearsModal" x-transition.opacity.duration.200ms
        x-trap.inert.noscroll="schoolYearsModal" x-on:keydown.esc.window="schoolYearsModal = false"
        x-on:click.self="schoolYearsModal = false"
        class="fixed inset-0 z-30 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="defaultModalTitle">
        <!-- Modal Dialog -->
        <div x-show="schoolYearsModal" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
            <!-- Dialog Header -->
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 id="defaultModalTitle" class="text-lg font-medium leading-6 text-gray-900">
                    Tambah Tahun Ajaran</h3>
                <p class="mt-1 text-sm text-gray-500">Atur tahun ajaran sesuai kebijakan sekolah.</p>
            </div>
            <!-- Dialog Body -->
            <div class="p-6">
                <form wire:submit='createSchoolYear'>
                    <div class="space-y-4">
                        <div>
                            <label for="schoolYears" class="block text-sm font-medium text-gray-700">Tahun
                                Ajaran</label>
                            <input type="text" id="schoolYears" wire:model="schoolYears"
                                placeholder="cth: 2024/2025"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            @error('schoolYears')
                                <p class="mt-1 text-sm text-red-600"><span class="font-medium">Error!</span>
                                    {{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="start_month" class="block text-sm font-medium text-gray-700">Bulan
                                    Mulai</label>
                                <input type="date" id="start_month" wire:model="start_month"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                @error('start_month')
                                    <p class="mt-1 text-sm text-red-600"><span class="font-medium">Error!</span>
                                        {{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="end_month" class="block text-sm font-medium text-gray-700">Bulan
                                    Selesai</label>
                                <input type="date" id="end_month" wire:model="end_month"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                @error('end_month')
                                    <p class="mt-1 text-sm text-red-600"><span class="font-medium">Error!</span>
                                        {{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Dialog Footer -->
                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
                        <button x-on:click="schoolYearsModal = false" type="button"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Batal
                        </button>
                        <button type="submit" x-on:click="schoolYearsModal = false"
                            class="rounded-md bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Tambah Tahun Ajaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal edit tahun ajaran --}}
    <div x-cloak x-show="editModal" x-transition.opacity.duration.200ms x-trap.inert.noscroll="editModal"
        x-on:keydown.esc.window="editModal = false" x-on:click.self="editModal = false"
        class="fixed inset-0 z-30 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="editModalTitle">
        <!-- Modal Dialog -->
        <div x-show="editModal" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
            <!-- Dialog Header -->
            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                <h3 id="editModalTitle" class="text-lg font-medium leading-6 text-gray-900">
                    Edit Tahun Ajaran</h3>
                <p class="mt-1 text-sm text-gray-500">Perbarui informasi tahun ajaran.</p>
            </div>
            <!-- Dialog Body -->
            <div class="p-6">
                <form wire:submit='updateSchoolYear'>
                    <div class="space-y-4">
                        <div>
                            <label for="editName" class="block text-sm font-medium text-gray-700">Tahun
                                Ajaran</label>
                            <input type="text" id="editName" wire:model="editName" placeholder="cth: 2024/2025"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                            @error('editName')
                                <p class="mt-1 text-sm text-red-600"><span class="font-medium">Error!</span>
                                    {{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="editStartMonth" class="block text-sm font-medium text-gray-700">Bulan
                                    Mulai</label>
                                <input type="date" id="editStartMonth" wire:model="editStartMonth"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                @error('editStartMonth')
                                    <p class="mt-1 text-sm text-red-600"><span class="font-medium">Error!</span>
                                        {{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="editEndMonth" class="block text-sm font-medium text-gray-700">Bulan
                                    Selesai</label>
                                <input type="date" id="editEndMonth" wire:model="editEndMonth"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                @error('editEndMonth')
                                    <p class="mt-1 text-sm text-red-600"><span class="font-medium">Error!</span>
                                        {{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Dialog Footer -->
                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
                        <button @click="editModal = false" type="button"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Batal
                        </button>
                        <button type="submit" @click="editModal = false"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi hapus --}}
    <div x-cloak x-show="deleteModal" x-transition.opacity.duration.200ms x-trap.inert.noscroll="deleteModal"
        x-on:keydown.esc.window="deleteModal = false" x-on:click.self="deleteModal = false"
        class="fixed inset-0 z-30 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="deleteModalTitle">
        <!-- Modal Dialog -->
        <div x-show="deleteModal" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
            <!-- Dialog Header -->
            <div class="border-b border-gray-200 bg-red-50 px-6 py-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 id="deleteModalTitle" class="text-lg font-medium leading-6 text-gray-900">
                            Hapus Tahun Ajaran</h3>
                        <p class="mt-1 text-sm text-gray-500">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>
            </div>
            <!-- Dialog Body -->
            <div class="p-6">
                <p class="text-sm text-gray-500">
                    Apakah Anda yakin ingin menghapus tahun ajaran <span
                        class="font-medium text-gray-900">{{ $schoolYearName }}</span>?
                    Semua data yang terkait dengan tahun ajaran ini akan dihapus secara permanen.
                </p>

                <!-- Dialog Footer -->
                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4">
                    <button @click="deleteModal = false" type="button"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Batal
                    </button>
                    <button wire:click="delete" @click="deleteModal = false" type="button"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
