<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\SubstitutionRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $dateFilter = '';

    // Modal fields
    public $ApprovalModal = false;
    public $selectedRequest = null;
    public $adminNotes = '';
    public $currentAction = ''; // 'approve' or 'reject'

    // Untuk statistik dashboard
    public $pendingCount = 0;
    public $approvedCount = 0;
    public $rejectedCount = 0;
    public $completedCount = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'dateFilter' => ['except' => ''],
    ];

    protected $listeners = ['refreshRequests' => '$refresh', 'approval-clicked' => 'showApprovalModal'];

    public function mount()
    {
        $this->updateCounts();
    }

    public function updateCounts()
    {
        $this->pendingCount = SubstitutionRequest::where('status', 'pending')->count();
        $this->approvedCount = SubstitutionRequest::where('status', 'approved')->count();
        $this->rejectedCount = SubstitutionRequest::where('status', 'rejected')->count();
        $this->completedCount = SubstitutionRequest::where('status', 'completed')->count();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    #[On('approval-clicked')]
    public function showApprovalModal($requestId, $action)
    {
        $this->selectedRequest = SubstitutionRequest::with(['user', 'substituteTeacher', 'subjectClass.classes.major'])->findOrFail($requestId);
        $this->currentAction = $action;
        $this->adminNotes = '';
        $this->ApprovalModal = true;
        $this->dispatch('show-approval-modal');
    }

    public function processRequest()
    {
        if (!$this->selectedRequest) {
            return;
        }

        $this->validate([
            'adminNotes' => $this->currentAction === 'reject' ? 'required|string|max:500' : 'nullable|string|max:500',
        ]);

        try {
            $request = SubstitutionRequest::findOrFail($this->selectedRequest->id);

            $request->status = $this->currentAction === 'approve' ? 'approved' : 'rejected';
            $request->admin_notes = $this->adminNotes;
            $request->approved_by = Auth::id();
            $request->approved_at = now();
            $request->save();

            // Opsional: Tambahkan notifikasi email
            // $request->substituteTeacher->notify(new SubstitutionRequestStatusUpdated($request));

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Permintaan berhasil ' . ($this->currentAction === 'approve' ? 'disetujui' : 'ditolak'),
            ]);

            $this->ApprovalModal = false;
            $this->reset(['selectedRequest', 'adminNotes', 'currentAction']);
            $this->updateCounts();
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal memproses permintaan: ' . $e->getMessage(),
            ]);
        }
    }

    public function getRequests()
    {
        return SubstitutionRequest::with(['user', 'substituteTeacher', 'subjectClass.classes.major'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })
                        ->orWhereHas('substituteTeacher', function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('subjectClass', function ($q) {
                            $q->where('class_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->dateFilter, function ($query) {
                $date = Carbon::parse($this->dateFilter);
                $query->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->orWhereDate('start_date', $date);
            })
            ->latest()
            ->paginate(10);
    }

    public function filterByStatus($status)
    {
        $this->statusFilter = $status;
    }

    public function render(): mixed
    {
        return view('livewire.admin.substitution-request', [
            'requests' => $this->getRequests(),
        ]);
    }
};

?>

<div>
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


        <!-- Filter dan Pencarian -->
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="flex rounded-md shadow-sm">
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Cari nama guru atau mata pelajaran..."
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>

            <div class="flex rounded-md shadow-sm">
                <select wire:model.live="statusFilter"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                    <option value="completed">Selesai</option>
                </select>
            </div>

            <div class="flex rounded-md shadow-sm">
                <input wire:model.live="dateFilter" type="date" placeholder="Filter tanggal"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>

            <div class="flex justify-end">
                <button wire:click="$refresh"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Bagian Stats Cards -->
        <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Pending Stats Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Menunggu Persetujuan</dt>
                                <dd>
                                    <div class="text-lg font-medium text-gray-900">{{ $pendingCount }}</div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="#" wire:click.prevent="filterByStatus('pending')"
                            class="font-medium text-blue-700 hover:text-blue-900">Lihat semua</a>
                    </div>
                </div>
            </div>

            <!-- Approved Stats Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Disetujui</dt>
                                <dd>
                                    <div class="text-lg font-medium text-gray-900">{{ $approvedCount }}</div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="#" wire:click.prevent="filterByStatus('approved')"
                            class="font-medium text-blue-700 hover:text-blue-900">Lihat semua</a>
                    </div>
                </div>
            </div>

            <!-- Rejected Stats Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Ditolak</dt>
                                <dd>
                                    <div class="text-lg font-medium text-gray-900">{{ $rejectedCount }}</div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="#" wire:click.prevent="filterByStatus('rejected')"
                            class="font-medium text-blue-700 hover:text-blue-900">Lihat semua</a>
                    </div>
                </div>
            </div>

            <!-- Completed Stats Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Selesai</dt>
                                <dd>
                                    <div class="text-lg font-medium text-gray-900">{{ $completedCount }}</div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="#" wire:click.prevent="filterByStatus('completed')"
                            class="font-medium text-blue-700 hover:text-blue-900">Lihat semua</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Daftar Permintaan -->
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Guru
                            Pengganti</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Guru
                            Utama</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Mata
                            Pelajaran</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Kelas
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tanggal
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($requests as $request)
                        <tr class="@if ($request->status === 'pending') bg-yellow-50 @endif">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                <div class="font-medium text-gray-900">{{ $request->substituteTeacher->name }}</div>
                                <div class="text-xs text-gray-500">{{ $request->substituteTeacher->teacher->nuptk }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <div class="font-medium text-gray-900">{{ $request->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $request->substituteTeacher->teacher->nuptk }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $request->subjectClass->class_name }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ $request->subjectClass->classes->name }} -
                                {{ $request->subjectClass->classes->major->name }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($request->start_date)->format('d M Y') }}
                                @if ($request->end_date && $request->start_date != $request->end_date)
                                    - {{ \Carbon\Carbon::parse($request->end_date)->format('d M Y') }}
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                @if ($request->status === 'pending')
                                    <span
                                        class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold leading-5 text-yellow-800">Menunggu</span>
                                @elseif($request->status === 'approved')
                                    <span
                                        class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold leading-5 text-green-800">Disetujui</span>
                                @elseif($request->status === 'rejected')
                                    <span
                                        class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold leading-5 text-red-800">Ditolak</span>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold leading-5 text-blue-800">Selesai</span>
                                @endif
                            </td>
                            <td
                                class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <div class="flex justify-end space-x-2">
                                    <button @click="$wire.showApprovalModal({{ $request->id }}, 'approve')"
                                        class="text-green-600 hover:text-green-900 disabled:cursor-not-allowed disabled:opacity-50"
                                        @if ($request->status !== 'pending') disabled @endif>
                                        <span class="sr-only">Setujui</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button wire:click="showApprovalModal({{ $request->id }}, 'reject')"
                                        class="text-red-600 hover:text-red-900 disabled:cursor-not-allowed disabled:opacity-50"
                                        @if ($request->status !== 'pending') disabled @endif>
                                        <span class="sr-only">Tolak</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button x-data="{}"
                                        x-on:click="$dispatch('open-detail', { request: {{ json_encode($request->load('user', 'substituteTeacher', 'subjectClass.classes.major')) }} })"
                                        class="text-blue-600 hover:text-blue-900">
                                        <span class="sr-only">Detail</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd"
                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-sm text-gray-500">
                                Tidak ada permintaan penggantian
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                {{ $requests->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Persetujuan/Penolakan -->
    <div x-data="{
        show: false,
        init() {
            $wire.on('show-approval-modal', () => {
                this.show = true;
            });
        }
    }" x-init="init()" x-modelable="show" x-model="show" x-show="show" x-cloak
        x-on:show-approval-modal.window="show = true" class="fixed inset-0 z-50 overflow-y-auto"
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
                @if ($selectedRequest)
                    <div class="bg-white px-4 py-5 sm:px-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 rounded-full p-3"
                                :class="{
                                    'bg-green-50': '{{ $currentAction }}'
                                    === 'approve',
                                    'bg-red-50': '{{ $currentAction }}'
                                    === 'reject'
                                }">
                                <template x-if="'{{ $currentAction }}' === 'approve'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </template>
                                <template x-if="'{{ $currentAction }}' === 'reject'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </template>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">
                                    {{ $currentAction === 'approve' ? 'Setujui' : 'Tolak' }} Permintaan Penggantian
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ $currentAction === 'approve' ? 'Berikan catatan tambahan (opsional)' : 'Berikan alasan penolakan' }}
                                </p>
                            </div>
                            <div class="ml-auto">
                                <button @click="show = false" type="button"
                                    class="text-gray-400 hover:text-gray-500">
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

                    <div class="bg-gray-50 px-4 py-5 sm:p-6">
                        <div class="space-y-4">
                            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                                <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                                    <dl class="sm:divide-y sm:divide-gray-200">
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Guru Pengganti</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                                {{ $selectedRequest->substituteTeacher->name }}</dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Guru Utama</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                                {{ $selectedRequest->user->name }}</dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Mata Pelajaran</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                                {{ $selectedRequest->subjectClass->class_name }}</dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Kelas</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                                {{ $selectedRequest->subjectClass->classes->name }} -
                                                {{ $selectedRequest->subjectClass->classes->major->name }}</dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Tanggal</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                                {{ \Carbon\Carbon::parse($selectedRequest->start_date)->format('d M Y') }}
                                                @if ($selectedRequest->end_date && $selectedRequest->start_date != $selectedRequest->end_date)
                                                    -
                                                    {{ \Carbon\Carbon::parse($selectedRequest->end_date)->format('d M Y') }}
                                                @endif
                                            </dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Alasan</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                                {{ $selectedRequest->reason }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <div>
                                <label for="adminNotes" class="block text-sm font-medium text-gray-700">
                                    {{ $currentAction === 'approve' ? 'Catatan Admin (Opsional)' : 'Alasan Penolakan' }}
                                </label>
                                <textarea wire:model="adminNotes" id="adminNotes" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="{{ $currentAction === 'approve' ? 'Tambahkan catatan untuk guru pengganti (opsional)' : 'Jelaskan alasan penolakan permintaan ini' }}"
                                    {{ $currentAction === 'reject' ? 'required' : '' }}></textarea>
                                @error('adminNotes')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-white px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button @click="$wire.processRequest ; show = false" type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                            :class="{
                                'bg-green-600 hover:bg-green-700 focus:ring-green-500': '{{ $currentAction }}'
                                === 'approve',
                                'bg-red-600 hover:bg-red-700 focus:ring-red-500': '{{ $currentAction }}'
                                === 'reject'
                            }">
                            {{ $currentAction === 'approve' ? 'Setujui Permintaan' : 'Tolak Permintaan' }}
                        </button>
                        <button @click="show = false" type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Detail Permintaan -->
    <div x-data="{
        show: false,
        request: null
    }" x-show="show" x-cloak
        @open-detail.window="show = true; request = $event.detail.request" class="fixed inset-0 z-50 overflow-y-auto"
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
                <div class="bg-white px-4 py-5 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-full bg-blue-50 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Detail Permintaan</h3>
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

                <div class="bg-gray-50 px-4 py-5 sm:p-6">
                    <template x-if="request">
                        <div class="space-y-4">
                            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                                <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                                    <dl class="sm:divide-y sm:divide-gray-200">
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                                            <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                                <span x-show="request.status === 'pending'"
                                                    class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold leading-5 text-yellow-800">Menunggu</span>
                                                <span x-show="request.status === 'approved'"
                                                    class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold leading-5 text-green-800">Disetujui</span>
                                                <span x-show="request.status === 'rejected'"
                                                    class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold leading-5 text-red-800">Ditolak</span>
                                                <span x-show="request.status === 'completed'"
                                                    class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold leading-5 text-blue-800">Selesai</span>
                                            </dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Guru Pengganti</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="request.substitute_teacher.name"></dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Guru Utama</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="request.user.name"></dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Mata Pelajaran</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="request.subject_class.class_name"></dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Kelas</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="request.subject_class.classes.name + ' - ' + request.subject_class.classes.major.name">
                                            </dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Tanggal Mulai</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="new Date(request.start_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})">
                                            </dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3"
                                            x-show="request.end_date && request.start_date !== request.end_date">
                                            <dt class="text-sm font-medium text-gray-500">Tanggal Selesai</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="new Date(request.end_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})">
                                            </dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3">
                                            <dt class="text-sm font-medium text-gray-500">Alasan</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="request.reason"></dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3"
                                            x-show="request.admin_notes">
                                            <dt class="text-sm font-medium text-gray-500">Catatan Admin</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="request.admin_notes"></dd>
                                        </div>
                                        <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-3"
                                            x-show="request.approved_at">
                                            <dt class="text-sm font-medium text-gray-500">Tanggal Persetujuan</dt>
                                            <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0"
                                                x-text="new Date(request.approved_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'})">
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="bg-white px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button @click="show = false" type="button"
                        class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>


</div>
