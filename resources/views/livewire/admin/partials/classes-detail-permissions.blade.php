<div class="overflow-hidden rounded-lg bg-white shadow-sm">
    <div class="border-b border-gray-200 p-6">
        <div class="flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
            <h2 class="text-sm font-medium text-gray-900 md:text-lg">Pengajuan Izin Siswa</h2>
            <div class="flex space-x-3">
                <select wire:model.live="permissionStatus"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Permissions List -->
    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Nama Siswa
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Tanggal
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Jenis
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Status
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Waktu Pengajuan
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($pendingPermissions as $permission)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full">
                                    <img src="{{ $permission->user->profile_photo_path ? Storage::url($permission->user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($permission->user->name) }}"
                                        alt="{{ $permission->user->name }}" class="h-full w-full object-cover">
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $permission->user->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($permission->permission_date)->format('d M Y') }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span
                                class="{{ $permission->type === 'izin' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }} inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                {{ ucfirst($permission->type) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
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
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900">
                                {{ $permission->created_at->format('d M Y H:i') }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <button wire:click="viewPermissionDetail({{ $permission->id }})"
                                class="rounded-md bg-blue-50 px-2 py-1 text-blue-600 hover:bg-blue-100 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @endforeach

                @if (count($pendingPermissions) === 0)
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2">Tidak ada pengajuan izin yang ditemukan</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Mobile View --}}
    <div class="flex flex-col md:hidden">
        <div class="mt-5 divide-y divide-gray-200 rounded-lg bg-white shadow">
            @forelse ($pendingPermissions as $permission)
                <div class="p-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-row items-center">
                            <p class="font-inter text-sm font-medium text-slate-900">
                                {{ $permission->user->name }} <span
                                    class="{{ $permission->type === 'sakit' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }} ms-1 inline-flex rounded-lg px-2 text-xs font-semibold leading-5">
                                    {{ ucfirst($permission->type) }}
                                </span></p>
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
                    <div class="mt-4 flex items-center justify-between">
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
                        <div class="flex flex-row gap-3">
                            <button class="pe-2 text-xs font-medium text-blue-600 hover:text-blue-900"
                                wire:click="viewPermissionDetail({{ $permission->id }})">Detail</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6">
                    <div class="flex flex-col items-center justify-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">Tidak ada pengajuan izin yang ditemukan</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
