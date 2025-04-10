<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\SubjectClassAttendance;
use App\Models\SubjectClassSession;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    // Filter dan pengaturan
    public $searchTerm = '';
    public $monthFilter;
    public $yearFilter;
    public $currentTab = 'all'; // 'all', 'qr', 'subject'
    public $studentId;

    // Pagination
    public $perPage = 10;
    public $page = 1;
    public $totalPages = 1;

    // Data
    public $attendances = [];
    public $totalAttendances = 0;

    public function mount()
    {
        // Set default filter to current month/year
        $now = Carbon::now();
        $this->monthFilter = $now->month;
        $this->yearFilter = $now->year;

        // Get student ID from auth user
        $user = Auth::user();
        $this->studentId = $user->student->id ?? null;

        // Load attendances
        $this->loadAttendances();
    }

    public function changeTab($tab)
    {
        $this->currentTab = $tab;
        $this->page = 1;
        $this->loadAttendances();
    }

    public function updatedMonthFilter()
    {
        $this->page = 1;
        $this->loadAttendances();
    }

    public function updatedYearFilter()
    {
        $this->page = 1;
        $this->loadAttendances();
    }

    public function updatedSearchTerm()
    {
        $this->page = 1;
        $this->loadAttendances();
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadAttendances();
        }
    }

    public function nextPage()
    {
        if ($this->page < $this->totalPages) {
            $this->page++;
            $this->loadAttendances();
        }
    }

    public function getQrAttendances()
    {
        return Attendance::where('user_id', Auth::id())
            ->when($this->monthFilter, function ($query) {
                return $query->whereMonth('attendance_date', $this->monthFilter);
            })
            ->when($this->yearFilter, function ($query) {
                return $query->whereYear('attendance_date', $this->yearFilter);
            })
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->attendance_date)->format('d M Y'),
                    'type' => 'qr',
                    'title' => ucfirst($item->type), // 'datang' atau 'pulang'
                    'status' => $item->status,
                    'check_time' => Carbon::parse($item->check_in_time)->format('H:i'),
                    'created_at' => Carbon::parse($item->created_at),
                    'raw_date' => $item->attendance_date,
                    'icon_class' => $item->status === 'tidak_hadir' ? 'bg-red-100 text-red-500' : ($item->type === 'datang' ? 'bg-green-100 text-green-500' : 'bg-pink-100 text-pink-500'),
                    'icon' => $item->status === 'tidak_hadir' ? 'danger' : ($item->type === 'datang' ? 'door-open' : 'door-closed'),
                ];
            });
    }

    public function getSubjectAttendances()
    {
        if (!$this->studentId) {
            return collect();
        }

        return SubjectClassAttendance::with(['session.subjectClass', 'session'])
            ->where('student_id', $this->studentId)
            ->whereHas('session', function ($query) {
                $query
                    ->when($this->monthFilter, function ($q) {
                        return $q->whereMonth('class_date', $this->monthFilter);
                    })
                    ->when($this->yearFilter, function ($q) {
                        return $q->whereYear('class_date', $this->yearFilter);
                    })
                    ->when($this->searchTerm, function ($q) {
                        return $q->where('subject_title', 'like', '%' . $this->searchTerm . '%');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->session->class_date)->format('d M Y'),
                    'type' => 'subject',
                    'title' => $item->session->subject_title,
                    'status' => $item->status,
                    'check_time' => $item->check_in_time ? Carbon::parse($item->check_in_time)->format('H:i') : '-',
                    'created_at' => Carbon::parse($item->created_at),
                    'raw_date' => $item->session->class_date,
                    'icon_class' => 'bg-blue-100 text-blue-500',
                    'icon' => 'book-open',
                    'session_id' => $item->subject_class_session_id,
                    'code' => $item->session->subjectClass->class_code,
                ];
            });
    }

    public function getAllAttendances()
    {
        // Get both types of attendances
        $qrAttendances = $this->getQrAttendances();
        $subjectAttendances = $this->getSubjectAttendances();

        // Combine and sort by date (newest first)
        return $qrAttendances->concat($subjectAttendances)->sortByDesc('raw_date')->sortByDesc('created_at')->values();
    }

    public function loadAttendances()
    {
        // Get the filtered attendances based on the current tab
        $allAttendances = collect();

        switch ($this->currentTab) {
            case 'qr':
                $allAttendances = $this->getQrAttendances();
                break;
            case 'subject':
                $allAttendances = $this->getSubjectAttendances();
                break;
            default:
                // 'all'
                $allAttendances = $this->getAllAttendances();
                break;
        }

        // Apply search filter if needed
        if ($this->searchTerm && $this->currentTab !== 'subject') {
            $allAttendances = $allAttendances
                ->filter(function ($item) {
                    return stripos($item['title'], $this->searchTerm) !== false;
                })
                ->values();
        }

        // Save total count
        $this->totalAttendances = $allAttendances->count();

        // Calculate pagination
        $this->totalPages = ceil($this->totalAttendances / $this->perPage);

        // Get items for current page
        $offset = ($this->page - 1) * $this->perPage;
        $this->attendances = $allAttendances->slice($offset, $this->perPage)->values()->toArray();
    }

    public function render(): mixed
    {
        $availableMonths = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        // Get years for filter (current year and previous year)
        $currentYear = Carbon::now()->year;
        $availableYears = [$currentYear - 1, $currentYear, $currentYear + 1];

        return view('livewire.student.attendance-history', [
            'availableMonths' => $availableMonths,
            'availableYears' => $availableYears,
            'hasMorePages' => $this->page < $this->totalPages,
        ]);
    }
};
?>

<div>
    <div class="mb-4 mt-10 rounded-lg bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="font-inter text-xl font-medium text-gray-800">Riwayat Kehadiran</h2>

            <!-- Filter dan Pencarian -->
            <div class="flex flex-col gap-2 sm:flex-row">


                <!-- Kolom Pencarian -->
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Cari..."
                        class="w-full rounded-md border-gray-300 pr-8 text-sm shadow-sm">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 md:relative">
                    <!-- Dropdown Bulan -->
                    <select wire:model.live="monthFilter" class="rounded-md border-gray-300 text-sm shadow-sm">
                        @foreach ($availableMonths as $month => $monthName)
                            <option value="{{ $month }}">{{ $monthName }}</option>
                        @endforeach
                    </select>

                    <!-- Dropdown Tahun -->
                    <select wire:model.live="yearFilter" class="rounded-md border-gray-300 text-sm shadow-sm">
                        @foreach ($availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

    </div>


    <!-- Switcher -->
    <div class="mb-6 flex rounded-xl bg-white p-1.5 shadow-sm">
        <button wire:click="changeTab('all')"
            class="{{ $currentTab === 'all' ? 'bg-blue-500 text-white' : 'text-gray-600 hover:bg-gray-100' }} w-1/2 rounded-lg py-2 text-center text-sm font-medium">
            Semua
        </button>
        <button wire:click="changeTab('qr')"
            class="{{ $currentTab === 'qr' ? 'bg-blue-500 text-white' : 'text-gray-600 hover:bg-gray-100' }} w-1/2 rounded-lg py-2 text-center text-sm font-medium">
            Presensi QR
        </button>
        <button wire:click="changeTab('subject')"
            class="{{ $currentTab === 'subject' ? 'bg-blue-500 text-white' : 'text-gray-600 hover:bg-gray-100' }} w-1/2 rounded-lg py-2 text-center text-sm font-medium">
            Presensi Kelas
        </button>
    </div>

    <div class="mt-4 space-y-3">
        @if ($totalAttendances === 0)
            <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                <svg class="mx-auto mb-3 h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <p class="text-gray-600">Belum ada data kehadiran untuk bulan dan tahun yang dipilih.</p>
            </div>
        @else
            @foreach ($attendances as $attendance)
                <a href="{{ $attendance['type'] === 'qr'
                    ? route('student.attendance.detail.qr', ['date' => $attendance['raw_date']])
                    : route('student.attendance.detail.subject', [
                        'date' => $attendance['raw_date'],
                        'id' => $attendance['session_id'] ?? 0,
                    ]) }}"
                    class="block transition duration-150 hover:bg-gray-50">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <!-- Icon sesuai jenis presensi -->
                                <div
                                    class="{{ $attendance['icon_class'] }} flex h-12 w-12 items-center justify-center rounded-lg p-2">
                                    @if ($attendance['icon'] === 'door-open')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                        </svg>
                                    @elseif($attendance['icon'] === 'door-closed')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                        </svg>
                                    @elseif($attendance['icon'] === 'danger')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                        </svg>
                                    @else
                                        <p class="text-md font-xs font-inter leading-tight">{{ $attendance['code'] }}
                                        </p>
                                    @endif
                                </div>

                                <div>
                                    <div class="font-inter text-sm font-medium text-gray-800">
                                        @if ($attendance['status'] === 'tidak_hadir')
                                            Absen
                                        @else
                                            {{ $attendance['title'] }}
                                        @endif
                                    </div>

                                    <div class="font-inter text-xs text-gray-500">{{ $attendance['date'] }}</div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end justify-center gap-1 text-right">
                                <div
                                    class="{{ match ($attendance['status']) {
                                        'hadir' => 'text-green-600',
                                        'terlambat' => 'text-yellow-600',
                                        'pulang_cepat' => 'text-orange-600',
                                        'izin' => 'text-blue-600',
                                        'sakit' => 'text-purple-600',
                                        default => 'text-red-600',
                                    } }} font-inter text-xs font-medium">
                                    {{ ucwords(str_replace('_', ' ', $attendance['status'])) }}
                                </div>
                                <div class="font-inter text-xs text-gray-500">{{ $attendance['check_time'] }}</div>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach

            <!-- Pagination -->
            @if ($totalAttendances > $perPage)
                <div class="mt-4 flex items-center justify-between rounded-lg bg-white p-3 shadow-sm">
                    <button wire:click="previousPage"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                        {{ $page <= 1 ? 'disabled' : '' }}>
                        Sebelumnya
                    </button>

                    <span class="text-sm text-gray-700">
                        Halaman {{ $page }} dari {{ $totalPages }}
                    </span>

                    <button wire:click="nextPage"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                        {{ !$hasMorePages ? 'disabled' : '' }}>
                        Selanjutnya
                    </button>
                </div>
            @endif
        @endif
    </div>

    <!-- Loading Indicator -->
    <div wire:loading wire:target="changeTab, monthFilter, yearFilter, searchTerm, previousPage, nextPage"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
            <div class="flex items-center rounded-lg bg-white px-6 py-4 shadow-xl">
                <svg class="mr-3 h-6 w-6 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span class="text-lg font-medium">Memuat...</span>
            </div>
        </div>
    </div>
