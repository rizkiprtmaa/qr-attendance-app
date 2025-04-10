<div class="overflow-hidden rounded-lg bg-white shadow-sm">
    <div class="border-b border-gray-200 p-6">
        <div class="flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
            <h2 class="text-lg font-medium text-gray-900">Rekap Kehadiran</h2>
            <div class="flex flex-row items-center justify-center space-x-3">
                <button wire:click="downloadAttendanceReport"
                    class="ml-4 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Laporan
                </button>
                <button wire:click="changeMonth('prev')"
                    class="rounded-md border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <span class="min-w-[160px] text-center text-sm font-medium">
                    {{ $monthNames[intval($currentMonth)] }} {{ $currentYear }}
                </span>
                <button wire:click="changeMonth('next')"
                    class="rounded-md border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Search Box for Attendance -->
    <div class="border-b border-gray-200 px-6 py-4">
        <div class="relative w-full max-w-xs">
            <input type="text" wire:model.live.debounce.300ms="attendanceSearch" placeholder="Cari siswa..."
                class="w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 md:text-sm" />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400 md:h-5 md:w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="w-full overflow-x-auto pb-2" style="-webkit-overflow-scrolling: touch;">
        <table class="w-full border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="sticky left-0 z-10 bg-gray-50 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        Nama Siswa
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                        Hadir
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                        Terlambat
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                        Izin
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                        Sakit
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                        Tidak Hadir
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                        % Kehadiran
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                        Ranking
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                        Detail
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($studentAttendance as $record)
                    <tr>
                        <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $record['student_name'] }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                {{ $record['summary']['present'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                                {{ $record['summary']['late'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                                {{ $record['summary']['permission'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800">
                                {{ $record['summary']['sick'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
                                {{ $record['summary']['absent'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <span class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-800">
                                {{ $record['attendance_percentage'] ?? 0 }}%
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">
                                #{{ $record['rank'] ?? '-' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-center">
                            <button
                                class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-200">
                                Detail
                            </button>
                        </td>
                    </tr>
                @endforeach

                @if (count($studentAttendance) === 0)
                    <tr>
                        <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="mt-2">Tidak ada data kehadiran untuk bulan ini</p>
                                <button wire:click="loadCurrentMonthAttendance"
                                    class="mt-3 inline-flex items-center rounded-md bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Muat Data
                                </button>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
