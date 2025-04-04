<?php

use Livewire\Volt\Component;
use App\Models\PermissionSubmission;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $permissionTypes = ['izin', 'sakit'];

    public $permissionType;
    public $permissionDate;
    public $description;
    public $attachment;

    public $permissions = [];

    public function mount()
    {
        $this->loadPermissions();
    }

    protected function rules()
    {
        return [
            'permissionType' => 'required|in:izin,sakit',
            'permissionDate' => 'required|date|after_or_equal:today',
            'description' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ];
    }

    protected function messages()
    {
        return [
            'permissionType.required' => 'Jenis pengajuan harus dipilih',
            'permissionType.in' => 'Jenis pengajuan tidak valid',
            'permissionDate.required' => 'Tanggal izin harus diisi',
            'permissionDate.date' => 'Format tanggal tidak valid',
            'permissionDate.after_or_equal' => 'Tanggal izin minimal hari ini',
            'description.max' => 'Deskripsi maksimal 500 karakter',
            'attachment.file' => 'File tidak valid',
            'attachment.max' => 'Ukuran file maksimal 10MB',
            'attachment.mimes' => 'Format file harus pdf, jpg, jpeg, atau png',
        ];
    }

    public function loadPermissions()
    {
        $this->permissions = PermissionSubmission::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createPermission()
    {
        $this->validate();

        try {
            $attachmentPath = null;

            if ($this->attachment) {
                $attachmentPath = $this->attachment->store('permissions', 'public');
            }

            PermissionSubmission::create([
                'user_id' => auth()->id(),
                'type' => $this->permissionType,
                'permission_date' => $this->permissionDate,
                'description' => $this->description,
                'attachment_path' => $attachmentPath,
                'status' => 'pending',
            ]);

            $this->reset(['permissionType', 'permissionDate', 'description', 'attachment']);
            $this->loadPermissions();

            $this->dispatch('show-toast', type: 'success', message: 'Pengajuan izin berhasil dibuat');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Gagal membuat pengajuan izin: ' . $e->getMessage());
        }
    }

    public function cancelPermission($permissionId)
    {
        try {
            $permission = PermissionSubmission::where('id', $permissionId)
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->firstOrFail();

            $permission->delete();

            $this->loadPermissions();

            $this->dispatch('show-toast', type: 'success', message: 'Pengajuan izin berhasil dibatalkan');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Gagal membatalkan pengajuan izin');
        }
    }

    public function render(): mixed
    {
        return view('livewire.teacher.permission-management', [
            'permissionTypes' => $this->permissionTypes,
            'permissions' => $this->permissions,
        ]);
    }
}; ?>

<div x-data="{ modalCreateOpen: false }">


    <!-- Daftar Pengajuan Izin -->
    <div class="mt-6 flex flex-row items-center justify-between rounded-lg bg-white shadow">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Riwayat Pengajuan</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Daftar pengajuan izin yang telah Anda buat</p>
        </div>
        <div class="px-4 py-5 sm:px-6">
            <button x-on:click="modalCreateOpen = true"
                class="rounded-md bg-blue-600 px-4 py-2 font-inter text-sm text-white shadow-md hover:bg-blue-700">
                Buat Pengajuan
            </button>
        </div>




    </div>

    <div>

        @if (count($permissions) > 0)
            <!-- Tampilan Tabel untuk Desktop -->
            <div class="mt-5 hidden overflow-x-auto rounded-lg bg-white shadow md:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
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
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Lampiran</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($permissions as $permission)
                            <tr>
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
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if ($permission->status === 'approved')
                                        <span
                                            class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                            Disetujui
                                        </span>
                                    @elseif($permission->status === 'rejected')
                                        <span
                                            class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                            Ditolak
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800">
                                            Menunggu
                                        </span>
                                    @endif
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
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    @if ($permission->status === 'pending')
                                        <button wire:click="cancelPermission({{ $permission->id }})"
                                            wire:confirm="Yakin ingin membatalkan pengajuan ini?"
                                            class="text-red-600 hover:text-red-900">
                                            Batalkan
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Tampilan Card untuk Mobile -->
            <div class="md:hidden">
                <div class="mt-5 divide-y divide-gray-200 rounded-lg bg-white shadow">
                    @foreach ($permissions as $permission)
                        <div class="p-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex flex-row items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        class="size-4">
                                        <path fill-rule="evenodd"
                                            d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2 pt-0.5 text-xs text-slate-900">
                                        {{ \Carbon\Carbon::parse($permission->permission_date)->locale('id')->translatedFormat('d F Y') }}
                                    </span>
                                </div>

                                <div>
                                    @if ($permission->status === 'approved')
                                        <span
                                            class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                            Disetujui
                                        </span>
                                    @elseif($permission->status === 'rejected')
                                        <span
                                            class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                            Ditolak
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800">
                                            Menunggu
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 flex flex-row items-center">
                                <span
                                    class="{{ $permission->type === 'sakit' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }} inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                    {{ ucfirst($permission->type) }}
                                </span>
                            </div>



                            <div class="mt-2 flex items-center justify-between">
                                <div>
                                    @if ($permission->attachment_path)
                                        <a href="{{ Storage::url($permission->attachment_path) }}" target="_blank"
                                            class="text-xs text-blue-600 hover:text-blue-900">
                                            Lihat Lampiran
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-500">Tanpa lampiran</span>
                                    @endif
                                </div>
                                <div class="flex flex-row gap-3">
                                    @if ($permission->status === 'pending')
                                        <button
                                            class="text-xs font-medium text-blue-600 hover:text-blue-900">Detail</button>
                                        <button wire:click="cancelPermission({{ $permission->id }})"
                                            wire:confirm="Yakin ingin membatalkan pengajuan ini?"
                                            class="text-xs font-medium text-red-600 hover:text-red-900">
                                            Batalkan
                                        </button>
                                    @else
                                        <button
                                            class="text-xs font-medium text-blue-600 hover:text-blue-900">Detail</button>
                                    @endif
                                </div>
                            </div>
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
                <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada pengajuan</h3>
                <p class="mt-1 text-sm text-gray-500">Buat pengajuan izin/sakit untuk presensi QR dan kelas.</p>
            </div>
        @endif
    </div>

    <!-- Modal Ajukan Izin -->
    <div x-cloak x-show="modalCreateOpen" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="modalCreateOpen = false" x-on:click.self="modalCreateOpen = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="sessionModalTitle">
        <!-- Modal Dialog -->
        <div x-show="modalCreateOpen" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">
            <!-- Dialog Header -->
            <div class="bg-blue-50 px-6 py-4">
                <h3 id="sessionModalTitle" class="text-lg font-medium text-gray-900">
                    Ajukan Izin
                </h3>
                <p class="mt-1 text-sm text-gray-500">Buat pengajuan izin kehadiran untuk dilakukan approval.</p>
            </div>
            <!-- Dialog Body -->
            <div class="px-6 py-4">
                <form wire:submit="createPermission">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="mb-4">
                            <label for="permissionType" class="block text-sm font-medium text-gray-700">Jenis
                                Pengajuan</label>
                            <select wire:model="permissionType"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Pilih Jenis Pengajuan</option>
                                @foreach ($permissionTypes as $type)
                                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            @error('permissionType')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label for="permissionDate" class="block text-sm font-medium text-gray-700">Tanggal
                                Izin</label>
                            <div class="relative mt-1 w-full rounded-md shadow-sm">
                                <input wire:model="permissionDate" type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                                @error('permissionDate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Alasan Pengajuan
                            <span class="text-xs text-gray-400">(jika ada)</span></label>
                        <textarea wire:model="description" placeholder="deskripsikan alasan pengajuan izin/sakit"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="attachment" class="block text-sm font-medium text-gray-700">
                            Surat Izin/Sakit <span class="text-xs text-gray-400">(jika ada)</span>
                        </label>
                        <input type="file" wire:model="attachment"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100" />
                        <div wire:loading wire:target="attachment" class="mt-1 text-sm text-gray-500">Uploading...
                        </div>
                        @error('attachment')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Dialog Footer -->
                    <div class="mt-6 flex items-center justify-end border-t border-gray-200 pt-4">
                        <button type="button" @click="modalCreateOpen = false"
                            class="mr-3 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" @click="modalCreateOpen = false"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            Ajukan Izin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Message Toast -->
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
</div>
