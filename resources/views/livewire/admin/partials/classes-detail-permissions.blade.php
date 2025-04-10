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
    <div class="overflow-x-auto">
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
</div>
