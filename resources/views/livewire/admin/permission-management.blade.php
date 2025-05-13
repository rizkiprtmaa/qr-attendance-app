<?php

use Livewire\Volt\Component;
use App\Models\PermissionSubmission;
use App\Models\Attendance;
use Carbon\Carbon;

new class extends Component {
    public $permissions = [];
    public $permissionId;
    public $adminNotes;

    // Tambahkan properti untuk filter tanggal
    public $dateFilter = '';
    public $groupedPermissions = [];

    public $viewMode = 'pending'; // pending, approved, rejected

    public function mount()
    {
        $this->dateFilter = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        // Query dasar
        $query = PermissionSubmission::with('user');

        // Filter berdasarkan status
        if ($this->viewMode === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->viewMode === 'approved') {
            $query->where('status', 'approved');
        } else {
            $query->where('status', 'rejected');
        }

        // Filter berdasarkan tanggal jika ada
        if ($this->dateFilter) {
            $query->whereDate('permission_date', $this->dateFilter);
        }

        // Urutkan
        if ($this->viewMode === 'pending') {
            $query->orderBy('permission_date', 'asc');
        } elseif ($this->viewMode === 'approved') {
            $query->orderBy('approved_at', 'desc');
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        // Ambil data
        $this->permissions = $query->get();

        // Kelompokkan berdasarkan tanggal untuk tampilan alternatif
        $this->groupPermissionsByDate();
    }

    // Fungsi untuk mengelompokkan izin berdasarkan tanggal
    private function groupPermissionsByDate()
    {
        $this->groupedPermissions = [];

        foreach ($this->permissions as $permission) {
            $date = Carbon::parse($permission->permission_date)->format('Y-m-d');

            if (!isset($this->groupedPermissions[$date])) {
                $this->groupedPermissions[$date] = [
                    'date' => Carbon::parse($permission->permission_date),
                    'permissions' => [],
                ];
            }

            $this->groupedPermissions[$date]['permissions'][] = $permission;
        }

        // Urutkan berdasarkan tanggal
        ksort($this->groupedPermissions);
    }

    // Fungsi untuk mengubah filter tanggal
    public function updatedDateFilter()
    {
        $this->loadPermissions();
    }

    // Tambahkan method untuk reset filter tanggal
    public function resetDateFilter()
    {
        $this->dateFilter = '';
        $this->loadPermissions();
    }

    public function changeViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->loadPermissions();
    }

    public function approvePermission($permissionId)
    {
        try {
            $permission = PermissionSubmission::findOrFail($permissionId);

            // Update permission status
            $permission->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'admin_notes' => $this->adminNotes,
            ]);

            // Hanya proses attendance QR untuk izin yang disetujui
            $this->processQRAttendance($permission);

            $this->loadPermissions();
            $this->reset('adminNotes');

            $this->dispatch('show-toast', type: 'success', message: 'Pengajuan izin berhasil disetujui');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Gagal menyetujui izin: ' . $e->getMessage());
        }
    }

    public function rejectPermission($permissionId)
    {
        try {
            $permission = PermissionSubmission::findOrFail($permissionId);

            $permission->update([
                'status' => 'rejected',
                'admin_notes' => $this->adminNotes,
            ]);

            $this->loadPermissions();
            $this->reset('adminNotes');

            $this->dispatch('show-toast', type: 'success', message: 'Pengajuan izin berhasil ditolak');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Gagal menolak izin: ' . $e->getMessage());
        }
    }

    /**
     * Process QR attendance records for the approved permission
     * Class attendance will be handled later when a session is created
     */
    private function processQRAttendance($permission)
    {
        $user = $permission->user;
        $permissionDate = Carbon::parse($permission->permission_date);
        $permissionType = $permission->type;

        // Buat record untuk kedatangan
        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => $permissionDate->toDateString(),
            'type' => 'datang', // 'datang' untuk presensi pagi
            'status' => $permissionType, // 'izin' atau 'sakit'
            'check_in_time' => $permissionDate->copy()->setTime(7, 0, 0),
        ]);

        // Buat record untuk kepulangan
        Attendance::create([
            'user_id' => $user->id,
            'attendance_date' => $permissionDate->toDateString(),
            'type' => 'pulang', // 'pulang' untuk presensi sore
            'status' => $permissionType, // 'izin' atau 'sakit'
            'check_in_time' => $permissionDate->copy()->setTime(14, 0, 0),
        ]);

        // Catatan: Presensi kelas akan ditangani saat guru membuat sesi baru
    }

    public function render(): mixed
    {
        return view('livewire.admin.permission-management', [
            'permissions' => $this->permissions,
            'pendingCount' => PermissionSubmission::where('status', 'pending')->count(),
            'approvedCount' => PermissionSubmission::where('status', 'approved')->count(),
            'rejectedCount' => PermissionSubmission::where('status', 'rejected')->count(),
        ]);
    }
}; ?>

<div class="mt-12 md:mt-0">
    <!-- Tab Navigation -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-1 md:space-x-8" aria-label="Tabs">
            <button wire:click="changeViewMode('pending')"
                class="{{ $viewMode === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                Menunggu Persetujuan
                @if ($pendingCount > 0)
                    <span
                        class="ml-2 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">{{ $pendingCount }}</span>
                @endif
            </button>
            <button wire:click="changeViewMode('approved')"
                class="{{ $viewMode === 'approved' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                Disetujui
                @if ($approvedCount > 0)
                    <span
                        class="ml-2 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">{{ $approvedCount }}</span>
                @endif
            </button>
            <button wire:click="changeViewMode('rejected')"
                class="{{ $viewMode === 'rejected' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                Ditolak
                @if ($rejectedCount > 0)
                    <span
                        class="ml-2 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">{{ $rejectedCount }}</span>
                @endif
            </button>
        </nav>
    </div>

    <!-- Filter tanggal -->
    <div class="mb-4 flex flex-wrap items-center justify-between">
        <div class="mb-2 flex flex-wrap items-center gap-2 md:mb-0">
            <input type="date" id="dateFilter" wire:model.live="dateFilter"
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">

            @if ($dateFilter)
                <button wire:click="resetDateFilter" class="text-sm text-gray-500 hover:text-gray-700">
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset
                    </span>
                </button>
            @endif
        </div>

        <div>
            <span class="text-sm text-gray-500">
                Total: {{ count($permissions) }} izin
            </span>
        </div>
    </div>



    <!-- Permission List -->
    <div class="rounded-lg border border-gray-200 bg-white shadow">
        @if (count($permissions) > 0)
            <!-- Tampilan tanggal yang dipilih -->
            <div class="p-4">
                <h3 class="mb-4 flex items-center text-lg font-medium text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-blue-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ $dateFilter ? Carbon::parse($dateFilter)->locale('id')->translatedFormat('l, d F Y') : 'Semua Pengajuan Izin' }}
                    <span class="ml-2 text-sm font-normal text-gray-500">({{ count($permissions) }} pengajuan
                        izin)</span>
                </h3>

                <!-- Tampilan desktop (md ke atas) -->
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Nama</th>
                                @if (!$dateFilter)
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Tanggal</th>
                                @endif
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Tipe</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Deskripsi</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Lampiran</th>
                                @if ($viewMode !== 'pending')
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Catatan Admin</th>
                                @endif
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($permissions as $permission)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-600">
                                                    <img src="{{ $permission->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($permission->user->name) }}"
                                                        alt="{{ $permission->user->name }}"
                                                        class="h-full w-full rounded-full object-cover">
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $permission->user->name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ ucfirst($permission->user->role) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    @if (!$dateFilter)
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {{ Carbon::parse($permission->permission_date)->locale('id')->translatedFormat('d F Y') }}
                                        </td>
                                    @endif
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        <span
                                            class="{{ $permission->type === 'sakit' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }} inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                            {{ ucfirst($permission->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $permission->description ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        @if ($permission->attachment_path)
                                            <a href="{{ Storage::url($permission->attachment_path) }}" target="_blank"
                                                class="text-blue-600 hover:text-blue-900">
                                                Lihat
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @if ($viewMode !== 'pending')
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $permission->admin_notes ?: '-' }}
                                        </td>
                                    @endif
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        @if ($viewMode === 'pending')
                                            <div x-data="{ open: false }" class="relative">
                                                <button @click="open = !open" type="button"
                                                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                                                    Tindakan
                                                </button>
                                                <div x-show="open" @click.away="open = false"
                                                    class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5">
                                                    <div class="p-3">
                                                        <div class="mb-3">
                                                            <label for="adminNotes{{ $permission->id }}"
                                                                class="block text-sm font-medium text-gray-700">Catatan</label>
                                                            <textarea wire:model.defer="adminNotes" id="adminNotes{{ $permission->id }}" rows="2"
                                                                class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                                                        </div>
                                                        <div class="flex justify-between space-x-2">
                                                            <button
                                                                wire:click="approvePermission({{ $permission->id }})"
                                                                @click="open = false"
                                                                class="flex w-full items-center justify-center rounded-md bg-green-100 px-3 py-2 text-sm font-medium text-green-800 hover:bg-green-200">
                                                                Setujui
                                                            </button>
                                                            <button
                                                                wire:click="rejectPermission({{ $permission->id }})"
                                                                @click="open = false"
                                                                class="flex w-full items-center justify-center rounded-md bg-red-100 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-200">
                                                                Tolak
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Tampilan mobile (card list) -->
                <div class="flex flex-col overflow-auto md:hidden">
                    <div class="divide-y divide-gray-200">
                        @foreach ($permissions as $permission)
                            <div class="p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-600">
                                            <img src="{{ $permission->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($permission->user->name) }}"
                                                alt="{{ $permission->user->name }}"
                                                class="h-full w-full rounded-full object-cover">
                                        </div>
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900">{{ $permission->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ ucfirst($permission->user->role) }}
                                            </div>
                                        </div>
                                    </div>
                                    <span
                                        class="{{ $permission->type === 'sakit' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }} inline-flex rounded-full px-2 py-1 text-xs font-semibold">
                                        {{ ucfirst($permission->type) }}
                                    </span>
                                </div>

                                <div class="space-y-2 text-sm">
                                    @if (!$dateFilter)
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Tanggal:</span>
                                            <span
                                                class="font-medium">{{ Carbon::parse($permission->permission_date)->locale('id')->translatedFormat('d F Y') }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Deskripsi:</span>
                                        <span
                                            class="max-w-[60%] text-right">{{ $permission->description ?: '-' }}</span>
                                    </div>

                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Lampiran:</span>
                                        <span>
                                            @if ($permission->attachment_path)
                                                <a href="{{ Storage::url($permission->attachment_path) }}"
                                                    target="_blank" class="text-blue-600 hover:text-blue-900">
                                                    Lihat
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>

                                    @if ($viewMode !== 'pending')
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Catatan Admin:</span>
                                            <span
                                                class="max-w-[60%] text-right">{{ $permission->admin_notes ?: '-' }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if ($viewMode === 'pending')
                                    <div class="mt-4" x-data="{ openMobile: false }">
                                        <button @click="openMobile = !openMobile" type="button"
                                            class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                                            Tindakan
                                        </button>
                                        <div x-show="openMobile" @click.away="openMobile = false"
                                            class="mt-2 rounded-md bg-gray-50 p-3 shadow ring-1 ring-black ring-opacity-5">
                                            <div class="mb-3">
                                                <label for="mobileAdminNotes{{ $permission->id }}"
                                                    class="block text-sm font-medium text-gray-700">Catatan</label>
                                                <textarea wire:model.defer="adminNotes" id="mobileAdminNotes{{ $permission->id }}" rows="2"
                                                    class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                                            </div>
                                            <div class="flex justify-between space-x-2">
                                                <button wire:click="approvePermission({{ $permission->id }})"
                                                    @click="openMobile = false"
                                                    class="flex w-full items-center justify-center rounded-md bg-green-100 px-3 py-2 text-sm font-medium text-green-800 hover:bg-green-200">
                                                    Setujui
                                                </button>
                                                <button wire:click="rejectPermission({{ $permission->id }})"
                                                    @click="openMobile = false"
                                                    class="flex w-full items-center justify-center rounded-md bg-red-100 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-200">
                                                    Tolak
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <!-- Tampilan kosong -->
            <div class="py-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mx-auto h-12 w-12 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                    @if ($dateFilter)
                        Tidak ada pengajuan izin pada tanggal
                        {{ Carbon::parse($dateFilter)->locale('id')->translatedFormat('d F Y') }}
                    @else
                        @if ($viewMode === 'pending')
                            Tidak ada pengajuan yang menunggu persetujuan
                        @elseif($viewMode === 'approved')
                            Tidak ada pengajuan yang disetujui
                        @else
                            Tidak ada pengajuan yang ditolak
                        @endif
                    @endif
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($dateFilter)
                        Coba pilih tanggal yang berbeda atau reset filter
                    @else
                        @if ($viewMode === 'pending')
                            Semua pengajuan izin telah diproses
                        @else
                            Belum ada pengajuan izin yang diproses
                        @endif
                    @endif
                </p>
                @if ($dateFilter)
                    <div class="mt-4">
                        <button wire:click="resetDateFilter"
                            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Reset Filter
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>


    <!-- Toast Notification -->
    <div x-data="{ showToast: false, toastMessage: '', toastType: 'success' }"
        x-on:show-toast.window="
            showToast = true;
            toastMessage = $event.detail.message;
            toastType = $event.detail.type;
            setTimeout(() => showToast = false, 3000);
         ">
        <div x-cloak x-show="showToast" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-90"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-90" class="fixed bottom-5 right-5 z-50">
            <div :class="toastType === 'success' ? 'bg-green-50 text-green-800 ring-green-600/20' :
                'bg-red-50 text-red-800 ring-red-600/20'"
                class="flex items-center rounded-md px-4 py-3 shadow-md ring-1">
                <div :class="toastType === 'success' ? 'bg-green-400/20' : 'bg-red-400/20'"
                    class="mr-3 flex h-8 w-8 items-center justify-center rounded-full">
                    <svg x-show="toastType === 'success'" class="h-5 w-5 text-green-600"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <svg x-show="toastType === 'error'" class="h-5 w-5 text-red-600"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <p x-text="toastMessage" class="text-sm font-medium"></p>
            </div>
        </div>
    </div>
</div>
