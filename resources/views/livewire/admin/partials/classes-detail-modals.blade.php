<!-- Edit Student Modal -->
<div x-data="{ show: @entangle('showEditModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
    role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:p-0">
        <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <div x-show="show" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
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
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Edit Data Siswa</h3>
                        <p class="text-sm text-gray-500">Perbarui informasi siswa
                            {{ $activeStudent->user->name ?? '' }}</p>
                    </div>
                    <div class="ml-auto">
                        <button @click="show = false" type="button" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Tutup</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="bg-gray-50 px-4 py-5 sm:p-6">
                <form wire:submit.prevent="saveStudent">
                    <div class="space-y-4">
                        <!-- NISN -->
                        <div>
                            <label for="nisn" class="block text-sm font-medium text-gray-700">NISN</label>
                            <input type="text" wire:model="editForm.nisn" id="nisn"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('editForm.nisn')
                                <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Nomor HP Orang Tua -->
                        <div>
                            <label for="parent_number" class="block text-sm font-medium text-gray-700">Nomor HP Orang
                                Tua</label>
                            <input type="text" wire:model="editForm.parent_number" id="parent_number"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('editForm.parent_number')
                                <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="bg-white px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button type="button" wire:click="saveStudent"
                    class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                    Simpan Perubahan
                </button>
                <button @click="show = false" type="button"
                    class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Permission History Modal -->
<div x-data="{ show: @entangle('showPermissionsModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0" overflow-auto>
        <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
        <div x-show="show" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl sm:p-6 sm:align-middle">
            <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                <button @click="show = false" type="button"
                    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <span class="sr-only">Tutup</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div>
                <div class="flex items-center">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                        <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Riwayat Perizinan -
                            {{ $activeStudent->user->name ?? '' }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            NISN: {{ $activeStudent->nisn ?? '-' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    @if ($permissionHistory && count($permissionHistory) > 0)
                        <div class="overflow-scroll shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                            Tanggal</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Jenis</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Status</th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Diajukan</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Aksi</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($permissionHistory as $permission)
                                        <tr>
                                            <td
                                                class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                {{ \Carbon\Carbon::parse($permission->permission_date)->format('d M Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                <span
                                                    class="{{ $permission->type === 'izin' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }} inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                                    {{ ucfirst($permission->type) }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                                <span
                                                    class="{{ $permission->status === 'pending'
                                                        ? 'bg-yellow-100 text-yellow-800'
                                                        : ($permission->status === 'approved'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800') }} inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                                    {{ $permission->status === 'pending'
                                                        ? 'Menunggu'
                                                        : ($permission->status === 'approved'
                                                            ? 'Disetujui'
                                                            : 'Ditolak') }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                                {{ $permission->created_at->format('d M Y H:i') }}
                                            </td>
                                            <td
                                                class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                <button wire:click="viewPermissionDetail({{ $permission->id }})"
                                                    class="text-blue-600 hover:text-blue-900">
                                                    Detail
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 md:flex md:justify-between">
                                    <p class="text-sm text-blue-700">Siswa belum pernah mengajukan izin.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button @click="show = false" type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Permission Detail Modal -->
<div x-data="{ show: @entangle('showPermissionDetailModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
        <div x-show="show" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block w-full transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:max-w-lg sm:align-middle">

            <!-- Modal Header -->
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                            Detail Perizinan
                        </h3>
                        <p class="text-sm text-gray-500">
                            @if ($activePermission)
                                {{ ucfirst($activePermission->type) }} -
                                {{ \Carbon\Carbon::parse($activePermission->permission_date)->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if ($activePermission)
                <!-- Content -->
                <div class="bg-gray-50 px-4 py-4 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Nama Siswa</h4>
                            <p class="mt-1 text-sm font-medium text-gray-900">
                                {{ $activePermission->user->name ?? '-' }}</p>
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Status</h4>
                            <div class="mt-1">
                                @if ($activePermission->status === 'approved')
                                    <span
                                        class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
                                        Disetujui
                                    </span>
                                @elseif($activePermission->status === 'rejected')
                                    <span
                                        class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">
                                        Ditolak
                                    </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800">
                                        Menunggu
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <h4 class="text-sm font-medium text-gray-500">Keterangan</h4>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ $activePermission->description ?: 'Tidak ada keterangan' }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <h4 class="text-sm font-medium text-gray-500">Lampiran</h4>
                            <div class="mt-1">
                                @if ($activePermission->attachment_path)
                                    <a href="{{ Storage::url($activePermission->attachment_path) }}" target="_blank"
                                        class="inline-flex items-center text-sm text-blue-600 hover:text-blue-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                        Lihat Lampiran
                                    </a>
                                @else
                                    <p class="text-sm text-gray-500">Tidak ada lampiran</p>
                                @endif
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <h4 class="text-sm font-medium text-gray-500">Catatan Admin</h4>
                            <div class="mt-1">
                                @if ($activePermission->status === 'pending')
                                    <textarea wire:model="adminNotes" rows="3"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Tambahkan catatan untuk siswa"></textarea>
                                @else
                                    <p class="text-sm text-gray-900">
                                        {{ $activePermission->admin_notes ?: 'Tidak ada catatan' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-white px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    @if ($activePermission->status === 'pending')
                        <button wire:click="approvePermission({{ $activePermission->id }})" type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                            Setujui
                        </button>
                        <button wire:click="rejectPermission({{ $activePermission->id }})" type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm">
                            Tolak
                        </button>
                        <button @click="show = false" type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    @else
                        <button @click="show = false" type="button"
                            class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto sm:text-sm">
                            Tutup
                        </button>
                    @endif
                </div>
            @else
                <div class="bg-white px-4 py-5 text-center sm:p-6">
                    <p class="text-sm text-gray-500">Data perizinan tidak ditemukan</p>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6">
                    <button @click="show = false" type="button"
                        class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                        Tutup
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
