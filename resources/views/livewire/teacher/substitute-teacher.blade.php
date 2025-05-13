<?php

use Livewire\Volt\Component;
use App\Models\Teacher;
use App\Models\SubjectClass;
use App\Models\SubstitutionRequest;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $searchTeacher = '';
    public $subjectFilter = '';
    public $dateFilter = '';

    // Form fields
    public $selectedUserId;
    public $selectedSubjectClassId;

    #[Rule('required|date')]
    public $startDate;

    #[Rule('required|date|after_or_equal:startDate')]
    public $endDate;

    #[Rule('required')]
    public $reason;

    // Modals
    public $showRequestModal = false;

    // Tambahkan property untuk data yang dibutuhkan di modal
    public $subjectClassDetails = null;

    // Delete Substitute
    public $substitutionId;
    public $subjectClassId;
    public $teacherId;

    public function mount()
    {
        $this->startDate = now()->timezone('Asia/Jakarta')->format('Y-m-d');
    }

    public function deleteSubstitution()
    {
        try {
            $substitutionRequest = SubstitutionRequest::findOrFail($this->substitutionId);

            $substitutionRequest->delete();
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

    public function confirmDeleteSubstitution($substitutionId)
    {
        $this->substitutionId = $substitutionId;
    }

    public function showRequestForm($userId, $subjectClassId)
    {
        // Simpan ID tanpa perlu mengambil data tambahan
        $this->selectedUserId = $userId;
        $this->selectedSubjectClassId = $subjectClassId;

        // Reset nilai form
        $this->startDate = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $this->endDate = null;
        $this->reason = '';

        // Tampilkan modal
        $this->showRequestModal = true;
    }

    public function submitRequest()
    {
        $this->validate([
            'selectedUserId' => 'required',
            'selectedSubjectClassId' => 'required',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'reason' => 'required',
        ]);

        // Get current teacher based on authenticated user
        $currentTeacher = auth()->user()->id;

        if (!$currentTeacher) {
            $this->dispatch('show-toast', type: 'error', message: 'Anda tidak terdaftar sebagai guru');
            return;
        }

        try {
            // Langsung set status 'approved' (tidak perlu approval admin)
            SubstitutionRequest::create([
                'user_id' => $this->selectedUserId,
                'substitute_teacher_id' => $currentTeacher,
                'subject_class_id' => $this->selectedSubjectClassId,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'reason' => $this->reason,
                'status' => 'approved', // Langsung disetujui
            ]);

            $this->reset(['selectedUserId', 'selectedSubjectClassId', 'startDate', 'endDate', 'reason']);
            $this->showRequestModal = false;
            $this->dispatch('show-toast', type: 'success', message: 'Permintaan penggantian berhasil dibuat dan langsung disetujui');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Gagal mengajukan permintaan: ' . $e->getMessage());
        }
    }

    public function getAvailableClasses()
    {
        $currentUserId = auth()->id();

        return SubjectClass::with(['user', 'classes.major'])
            // Jangan tampilkan kelas dari guru yang sedang login
            ->where('user_id', '!=', $currentUserId)
            ->when($this->searchTeacher, function ($query) {
                return $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchTeacher . '%');
                });
            })
            ->when($this->subjectFilter, function ($query) {
                return $query->where('class_name', 'like', '%' . $this->subjectFilter . '%');
            })
            ->get();
    }

    public function getMyRequests()
    {
        return SubstitutionRequest::where('substitute_teacher_id', auth()->id())
            ->with(['user', 'subjectClass.classes.major'])
            ->latest()
            ->paginate(10);
    }

    public function render(): mixed
    {
        return view('livewire.teacher.substitute-teacher', [
            'availableClasses' => $this->getAvailableClasses(),
            'myRequests' => $this->getMyRequests(),
        ]);
    }
};
?>

<div class="mt-12 md:mt-0" x-data="{ deleteSubstitutionModal: false }">
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
        <!-- Tab untuk switching antara Cari Kelas dan Permintaan Saya -->
        <div x-data="{ activeTab: 'available' }" class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button @click="activeTab = 'available'"
                        :class="{ 'border-blue-500 text-blue-600': activeTab === 'available', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'available' }"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                        Kelas Tersedia
                    </button>
                    <button @click="activeTab = 'requests'"
                        :class="{ 'border-blue-500 text-blue-600': activeTab === 'requests', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'requests' }"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                        Permintaan Saya
                    </button>
                </nav>
            </div>

            <!-- Panel Kelas Tersedia -->
            <div x-show="activeTab === 'available'">
                <!-- Filter dan Pencarian -->
                <div class="mb-6 mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="flex rounded-md shadow-sm">
                        <input wire:model.live.debounce.300ms="searchTeacher" type="text" placeholder="Cari guru..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>

                    <div class="flex rounded-md shadow-sm">
                        <input wire:model.live.debounce.300ms="subjectFilter" type="text"
                            placeholder="Cari mata pelajaran..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                </div>

                <!-- Tampilan Tabel untuk MD ke atas -->
                <div class="hidden md:block">
                    <div class="mt-4 shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                        Guru
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Mata Pelajaran</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Kelas</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($availableClasses as $class)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                            <div class="font-medium text-gray-900">{{ $class->user->name }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ $class->class_name }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ $class->classes->name }} - {{ $class->classes->major->name }}
                                        </td>
                                        <td
                                            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <button
                                                wire:click="showRequestForm({{ $class->user->id }}, {{ $class->id }})"
                                                class="text-blue-600 hover:text-blue-900">
                                                Ajukan Penggantian
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-sm text-gray-500">
                                            Tidak ada kelas yang tersedia untuk penggantian
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tampilan Card untuk Mobile/SM -->
                <div class="mt-4 space-y-4 md:hidden">
                    @forelse ($availableClasses as $class)
                        <div class="overflow-hidden rounded-lg bg-white shadow">
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $class->class_name }}</h3>
                                </div>
                                <div class="mt-2">
                                    <div class="mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-gray-400"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                        </svg>
                                        <span class="text-sm text-gray-700">{{ $class->user->name }}</span>
                                    </div>
                                    <div class="mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-gray-400"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                                        </svg>
                                        <span class="text-sm text-gray-700">{{ $class->classes->name }} -
                                            {{ $class->classes->major->name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-right">
                                <button wire:click="showRequestForm({{ $class->user->id }}, {{ $class->id }})"
                                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Ajukan Penggantian
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg bg-white p-6 text-center shadow">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada kelas</h3>
                            <p class="mt-1 text-sm text-gray-500">Tidak ada kelas yang tersedia untuk penggantian</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Panel Permintaan Saya - tampilan status yang telah diubah -->
            <div x-show="activeTab === 'requests'">
                <!-- Tampilan Tabel untuk MD ke atas -->
                <div class="hidden md:block">
                    <div class="mt-4 w-full overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                        Guru
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Mata Pelajaran</th>
                                    <th scope="col"
                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Tanggal</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($myRequests as $request)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                            <div class="font-medium text-gray-900">{{ $request->user->name }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ $request->subjectClass->class_name }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($request->start_date)->format('d M Y') }}
                                            @if ($request->end_date)
                                                - {{ \Carbon\Carbon::parse($request->end_date)->format('d M Y') }}
                                            @endif
                                        </td>
                                        <td
                                            class="flex flex-row items-center justify-end gap-2 whitespace-nowrap px-3 py-4 text-end text-sm font-medium sm:pr-6">
                                            <a href="{{ route('substitute.class', $request->subjectClass->id) }}"
                                                class="flex flex-row text-blue-600 hover:text-blue-900">
                                                Kelola Kelas
                                            </a>
                                            <button wire:click="confirmDeleteSubstitution({{ $request->id }})"
                                                @click="deleteSubstitutionModal = true"
                                                class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="mr-2 h-4 w-4 text-red-500">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                Hapus
                                            </button>

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 text-center text-sm text-gray-500">
                                            Anda belum mengajukan permintaan penggantian
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                            {{ $myRequests->links() }}
                        </div>
                    </div>
                </div>

                <!-- Tampilan Card untuk Mobile/SM -->
                <div class="mt-4 space-y-4 md:hidden">
                    @forelse ($myRequests as $request)
                        <div class="overflow-hidden rounded-lg bg-white shadow">
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $request->subjectClass->class_name }}
                                        </h3>
                                        <p class="text-sm text-gray-500">{{ $request->user->name }}</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-gray-400"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-sm text-gray-700">
                                            {{ \Carbon\Carbon::parse($request->start_date)->format('d M Y') }}
                                            @if ($request->end_date)
                                                - {{ \Carbon\Carbon::parse($request->end_date)->format('d M Y') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="flex flex-row justify-end gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 text-right">
                                <a href="{{ route('substitute.class', $request->subjectClass->id) }}"
                                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Kelola Kelas
                                </a>
                                <button wire:click="confirmDeleteSubstitution({{ $request->id }})"
                                    @click="deleteSubstitutionModal = true"
                                    class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-2 h-4 w-4 text-red-500">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg bg-white p-6 text-center shadow">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada permintaan</h3>
                            <p class="mt-1 text-sm text-gray-500">Anda belum mengajukan permintaan penggantian</p>
                        </div>
                    @endforelse

                    <div class="py-2">
                        {{ $myRequests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajukan Penggantian -->
    <div x-data="{ show: @entangle('showRequestModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <div x-show="show" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:w-full sm:max-w-lg">

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
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Ajukan Permintaan Penggantian</h3>
                            <p class="text-sm text-gray-500">Isi detail untuk mengajukan penggantian kelas</p>
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
                    <form wire:submit.prevent="submitRequest">
                        <div class="space-y-4">
                            <!-- Tanggal Mulai -->
                            <div>
                                <label for="startDate" class="block text-sm font-medium text-gray-700">Tanggal
                                    Mulai</label>
                                <input type="date" wire:model.defer="startDate" id="startDate"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('startDate')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Tanggal Selesai (Opsional) -->
                            <div>
                                <label for="endDate" class="block text-sm font-medium text-gray-700">Tanggal Selesai
                                </label>
                                <input type="date" wire:model.defer="endDate" id="endDate"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('endDate')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Isi tanggal yang sama jika hanya untuk satu hari
                                </p>
                            </div>

                            <!-- Alasan -->
                            <div>
                                <label for="reason" class="block text-sm font-medium text-gray-700">Alasan
                                    Penggantian</label>
                                <textarea wire:model.defer="reason" id="reason" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="Jelaskan alasan Anda ingin menggantikan kelas ini"></textarea>
                                @error('reason')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-white px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                        Ajukan Permintaan
                    </button>
                    <button @click="show = false" type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div x-cloak x-show="deleteSubstitutionModal" x-transition.opacity.duration.200ms
        x-on:delete-failed.window="deleteSubstitutionModal = false"
        x-on:keydown.esc.window="deleteSubstitutionModal = false" x-on:click.self="deleteSubstitutionModal = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="deleteSubstitutionModalTitle">
        <!-- Modal Dialog -->
        <div x-show="deleteSubstitutionModal" x-transition:enter="transition ease-out duration-200 delay-100"
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
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="deleteSubstitutionModalTitle">
                        Hapus Sesi Pergantian
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Apakah Anda yakin ingin menghapus sesi ini? Semua data presensi siswa untuk mapel
                            ini tetap ada, tetapi anda tidak bisa membuat pertemuan meskipun belum mencapai tenggat
                            akhir. Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>
                <!-- Fixed button container -->
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center sm:gap-3">
                    <button type="button" @click="deleteSubstitutionModal = false"
                        class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                    <button type="button" wire:click="deleteSubstitution" @click="deleteSubstitutionModal = false"
                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto sm:text-sm">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
