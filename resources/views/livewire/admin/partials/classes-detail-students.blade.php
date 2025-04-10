<div class="overflow-hidden rounded-lg bg-white shadow-sm">
    <div class="border-b border-gray-200 p-6">
        <div class="flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
            <div class="relative w-full max-w-xs">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari siswa..."
                    class="w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 md:text-sm" />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400 md:h-5 md:w-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <select wire:model.live="perPage" class="rounded-lg border-gray-300 text-sm">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <p class="text-sm text-gray-600">Per halaman</p>
            </div>
        </div>
    </div>

    <!-- Student list -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" wire:click="setSortBy('name')"
                        class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        <div class="flex items-center">
                            Nama
                            @if ($sortBy === 'name')
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-3 w-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    @if ($sortDirection === 'ASC')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col" wire:click="setSortBy('nisn')"
                        class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        <div class="flex items-center">
                            NISN
                            @if ($sortBy === 'nisn')
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-3 w-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    @if ($sortDirection === 'ASC')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Email
                    </th>
                    <th scope="col" wire:click="setSortBy('parent_number')"
                        class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        <div class="flex items-center">
                            No. Orang Tua
                            @if ($sortBy === 'parent_number')
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-3 w-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    @if ($sortDirection === 'ASC')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        % Kehadiran
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($students as $student)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full">
                                    <img src="{{ $student->user->profile_photo_path ? Storage::url($student->user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($student->user->name) }}"
                                        alt="{{ $student->user->name }}" class="h-full w-full object-cover">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $student->user->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $student->nisn ?: '-' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $student->user->email }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $student->parent_number ?: '-' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div
                                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">
                                @php
                                    // Jika tidak ada data, tampilkan 0%
                                    $percentage = 0;
                                    if (!empty($studentAttendance)) {
                                        $studentAttendanceRecord = collect($studentAttendance)->firstWhere(
                                            'student_id',
                                            $student->user_id,
                                        );
                                        if ($studentAttendanceRecord) {
                                            $percentage = $studentAttendanceRecord['attendance_percentage'] ?? 0;
                                        }
                                    }
                                @endphp
                                {{ $percentage }}%
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <button wire:click="showEditStudent({{ $student->id }})"
                                    class="rounded-md bg-blue-50 px-2 py-1 text-blue-600 hover:bg-blue-100 hover:text-blue-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="viewPermissions({{ $student->id }})"
                                    class="rounded-md bg-green-50 px-2 py-1 text-green-600 hover:bg-green-100 hover:text-green-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </button>
                                <button wire:click="downloadStudentAttendanceReport({{ $student->id }})"
                                    class="rounded-md bg-indigo-50 px-2 py-1 text-indigo-600 hover:bg-indigo-100 hover:text-indigo-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if (count($students) === 0)
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="mt-2">Tidak ada siswa yang ditemukan</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="border-t border-gray-200 px-6 py-4">
        {{ $students->links() }}
    </div>
</div>
