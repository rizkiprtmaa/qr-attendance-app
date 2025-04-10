<?php

use Livewire\Volt\Component;
use App\Models\PermissionSubmission;
use App\Models\Attendance;
use Carbon\Carbon;

new class extends Component {
    public $permissions = [];
    public $permissionId;
    public $adminNotes;

    public $viewMode = 'pending'; // pending, approved, rejected

    public function mount()
    {
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        if ($this->viewMode === 'pending') {
            $this->permissions = PermissionSubmission::with('user')->where('status', 'pending')->orderBy('permission_date', 'asc')->get();
        } elseif ($this->viewMode === 'approved') {
            $this->permissions = PermissionSubmission::with('user')->where('status', 'approved')->orderBy('approved_at', 'desc')->get();
        } else {
            $this->permissions = PermissionSubmission::with('user')->where('status', 'rejected')->orderBy('updated_at', 'desc')->get();
        }
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

<div>


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

    <!-- Permission List -->
    <div class="rounded-lg border border-gray-200 bg-white shadow">
        @if (count($permissions) > 0)
            <!-- Tampilan desktop (tabel) - Hanya ditampilkan pada layar md ke atas -->
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Nama</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Tanggal</th>
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
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-6 w-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $permission->user->name }}
                                            </div>
                                            <div class="text-sm text-gray-500">{{ ucfirst($permission->user->role) }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($permission->permission_date)->locale('id')->translatedFormat('d F Y') }}
                                </td>
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
                                                        <button wire:click="approvePermission({{ $permission->id }})"
                                                            @click="open = false"
                                                            class="flex w-full items-center justify-center rounded-md bg-green-100 px-3 py-2 text-sm font-medium text-green-800 hover:bg-green-200">
                                                            Setujui
                                                        </button>
                                                        <button wire:click="rejectPermission({{ $permission->id }})"
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

            <!-- Tampilan mobile (card list) - Hanya ditampilkan pada layar kecil (sm ke bawah) -->
            <div class="block md:hidden">
                <div class="divide-y divide-gray-200">
                    @foreach ($permissions as $permission)
                        <div class="p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-medium text-gray-900">{{ $permission->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ ucfirst($permission->user->role) }}</div>
                                    </div>
                                </div>
                                <span
                                    class="{{ $permission->type === 'sakit' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }} inline-flex rounded-full px-2 py-1 text-xs font-semibold">
                                    {{ ucfirst($permission->type) }}
                                </span>
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Tanggal:</span>
                                    <span
                                        class="font-medium">{{ \Carbon\Carbon::parse($permission->permission_date)->locale('id')->translatedFormat('d F Y') }}</span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-500">Deskripsi:</span>
                                    <span class="max-w-[60%] text-right">{{ $permission->description ?: '-' }}</span>
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
        @else
            <div class="py-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mx-auto h-12 w-12 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                    @if ($viewMode === 'pending')
                        Tidak ada pengajuan yang menunggu persetujuan
                    @elseif($viewMode === 'approved')
                        Tidak ada pengajuan yang disetujui
                    @else
                        Tidak ada pengajuan yang ditolak
                    @endif
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($viewMode === 'pending')
                        Semua pengajuan izin telah diproses
                    @else
                        Belum ada pengajuan izin yang diproses
                    @endif
                </p>
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
