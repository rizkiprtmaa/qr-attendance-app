<?php

use Livewire\Volt\Component;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\PermissionSubmission;
use App\Models\Attendance;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    // Base properties
    public $classId;
    public $class;
    public $search = '';
    public $perPage = 10;
    public $sortBy = 'name';
    public $sortDirection = 'ASC';

    // Student Management
    public $activeStudent = null;
    public $editForm = [
        'nisn' => '',
        'parent_number' => '',
    ];
    public $showEditModal = false;

    // Permission Management
    public $permissionStatus = '';
    public $activePermission = null;
    public $permissionHistory = [];
    public $adminNotes;
    public $showPermissionDetailModal = false;
    public $showPermissionsModal = false;

    // Attendance Management
    public $studentAttendance = [];
    public $currentMonth;
    public $currentYear;
    public $attendanceSearch = '';

    public function updatedAttendanceSearch()
    {
        if ($this->activeTab === 'attendance') {
            $this->loadCurrentMonthAttendance();
        }
    }
    public $monthNames = [
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

    // Tabs
    public $activeTab = 'students'; // students, permissions, attendance

    public function mount()
    {
        $this->classId = request()->route('id');
        $this->class = Classes::with(['teacher.user', 'major', 'school_year'])->findOrFail($this->classId);

        // Set bulan dan tahun saat ini untuk laporan kehadiran
        $this->currentMonth = intval(date('m'));
        $this->currentYear = date('Y');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setSortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
        }

        $this->sortBy = $column;
    }

    // Tab navigation
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;

        if ($tab === 'permissions') {
            $this->loadPermissions();
        } elseif ($tab === 'attendance') {
            $this->loadCurrentMonthAttendance();
        }
    }

    // Student Management
    public function getStudentsProperty()
    {
        $studentsQuery = Student::where('classes_id', $this->classId)
            ->when($this->search, function ($query) {
                return $query
                    ->whereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('nisn', 'like', '%' . $this->search . '%')
                    ->orWhere('parent_number', 'like', '%' . $this->search . '%');
            })
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('students.*', 'users.name as user_name')
            ->orderBy($this->sortBy, $this->sortDirection);

        return $studentsQuery->with('user', 'classes')->paginate($this->perPage);
    }

    public function showEditStudent($studentId)
    {
        $this->activeStudent = Student::with('user')->find($studentId);
        if ($this->activeStudent) {
            $this->editForm = [
                'nisn' => $this->activeStudent->nisn,
                'parent_number' => $this->activeStudent->parent_number,
            ];
            $this->showEditModal = true;
        }
    }

    public function saveStudent()
    {
        $this->validate([
            'editForm.nisn' => 'required|string|max:20',
            'editForm.parent_number' => 'nullable|string|max:15',
        ]);

        if ($this->activeStudent) {
            $this->activeStudent->update([
                'nisn' => $this->editForm['nisn'],
                'parent_number' => $this->editForm['parent_number'],
            ]);

            $this->dispatch('show-toast', type: 'success', message: 'Data siswa berhasil diperbarui');
            $this->showEditModal = false;
        }
    }

    // Permission Management
    public function loadPermissions()
    {
        $studentIds = Student::where('classes_id', $this->classId)->pluck('user_id');

        return PermissionSubmission::whereIn('user_id', $studentIds)
            ->when($this->permissionStatus, function ($query) {
                return $query->where('status', $this->permissionStatus);
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function viewPermissions($studentId)
    {
        $this->activeStudent = Student::with('user')->find($studentId);
        if ($this->activeStudent) {
            $this->permissionHistory = PermissionSubmission::where('user_id', $this->activeStudent->user_id)->orderBy('created_at', 'desc')->get();
        }
        $this->showPermissionsModal = true;
    }

    public function viewPermissionDetail($permissionId)
    {
        $this->activePermission = PermissionSubmission::with('user')->find($permissionId);
        $this->adminNotes = $this->activePermission ? $this->activePermission->admin_notes : '';
        $this->showPermissionDetailModal = true;
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

            // Buat record attendance
            $this->processAttendance($permission);

            $this->dispatch('show-toast', type: 'success', message: 'Pengajuan izin berhasil disetujui');
            $this->showPermissionDetailModal = false;
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
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'admin_notes' => $this->adminNotes,
            ]);

            $this->dispatch('show-toast', type: 'success', message: 'Pengajuan izin berhasil ditolak');
            $this->showPermissionDetailModal = false;
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Gagal menolak izin: ' . $e->getMessage());
        }
    }

    // Process attendance when permission is approved
    private function processAttendance($permission)
    {
        $user = $permission->user;
        $permissionDate = Carbon::parse($permission->permission_date);
        $permissionType = $permission->type;

        // Create attendance records for morning check-in
        Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'attendance_date' => $permissionDate->toDateString(),
                'type' => 'datang',
            ],
            [
                'status' => $permissionType, // 'izin' atau 'sakit'
                'check_in_time' => $permissionDate->copy()->setTime(7, 0, 0),
                'is_manual' => true,
                'created_by' => auth()->id(),
            ],
        );

        // Create attendance records for afternoon check-out
        Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'attendance_date' => $permissionDate->toDateString(),
                'type' => 'pulang',
            ],
            [
                'status' => $permissionType, // 'izin' atau 'sakit'
                'check_in_time' => $permissionDate->copy()->setTime(14, 0, 0),
                'is_manual' => true,
                'created_by' => auth()->id(),
            ],
        );
    }

    // Attendance Management
    public function loadCurrentMonthAttendance()
    {
        $studentIds = Student::where('classes_id', $this->classId)
            ->when($this->attendanceSearch, function ($query) {
                return $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->attendanceSearch . '%');
                });
            })
            ->pluck('user_id')
            ->toArray();

        $firstDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->startOfMonth();
        $lastDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->endOfMonth();

        // Get all attendance data for the month
        $attendanceData = Attendance::whereIn('user_id', $studentIds)
            ->whereBetween('attendance_date', [$firstDayOfMonth->format('Y-m-d'), $lastDayOfMonth->format('Y-m-d')])
            ->get();

        // Siapkan data per siswa
        $this->studentAttendance = [];

        foreach ($studentIds as $studentId) {
            $student = User::find($studentId);
            if (!$student) {
                continue;
            }

            $studentData = [
                'student_id' => $studentId,
                'student_name' => $student->name,
                'attendance' => [], // Ini yang penting - sebuah array untuk menyimpan kehadiran per tanggal
                'summary' => [
                    'present' => 0,
                    'late' => 0,
                    'permission' => 0,
                    'sick' => 0,
                    'absent' => 0,
                ],
            ];

            // Filter kehadiran siswa ini
            $studentAttendances = $attendanceData->where('user_id', $studentId);

            // Hitung hari kerja (Senin-Jumat) dalam bulan ini
            $workingDays = [];
            $currentDay = $firstDayOfMonth->copy();
            while ($currentDay->lte($lastDayOfMonth)) {
                if ($currentDay->dayOfWeek !== 0) {
                    // Skip weekend
                    $workingDays[] = $currentDay->format('Y-m-d');
                }
                $currentDay->addDay();
            }

            // Update status kehadiran berdasarkan data yang ada
            foreach ($studentAttendances as $attendance) {
                $date = $attendance->attendance_date;

                // Prioritaskan status tertentu
                if ($attendance->type === 'datang') {
                    $studentData['attendance'][$date] = $attendance->status;
                }
                // Jika sudah ada status datang, jangan timpa dengan status pulang
                elseif ($attendance->type === 'pulang' && !isset($studentData['attendance'][$date])) {
                    $studentData['attendance'][$date] = $attendance->status;
                }
            }

            // Hitung ringkasan
            foreach ($studentData['attendance'] as $status) {
                if ($status === 'hadir') {
                    $studentData['summary']['present']++;
                } elseif ($status === 'terlambat') {
                    $studentData['summary']['late']++;
                } elseif ($status === 'izin') {
                    $studentData['summary']['permission']++;
                } elseif ($status === 'sakit') {
                    $studentData['summary']['sick']++;
                } elseif ($status === 'tidak_hadir') {
                    $studentData['summary']['absent']++;
                }
            }

            // Hitung persentase kehadiran
            $totalDays = count($workingDays);
            if ($totalDays > 0) {
                $presentDays = $studentData['summary']['present'] + $studentData['summary']['late'];
                $studentData['attendance_percentage'] = round(($presentDays / $totalDays) * 100);
            } else {
                $studentData['attendance_percentage'] = 0;
            }

            $this->studentAttendance[] = $studentData;
        }

        // Sort berdasarkan persentase kehadiran untuk mendapatkan ranking
        usort($this->studentAttendance, function ($a, $b) {
            return $b['attendance_percentage'] <=> $a['attendance_percentage'];
        });

        // Assign ranking
        $rank = 1;
        $lastPercentage = null;
        $lastRank = 1;

        foreach ($this->studentAttendance as &$student) {
            if ($lastPercentage !== null && $student['attendance_percentage'] < $lastPercentage) {
                $rank = $lastRank + 1;
            }

            $student['rank'] = $rank;
            $lastPercentage = $student['attendance_percentage'];
            $lastRank = $rank;
            $rank++;
        }
    }

    public function changeMonth($direction)
    {
        if ($direction === 'prev') {
            if ($this->currentMonth == 1) {
                $this->currentMonth = 12;
                $this->currentYear--;
            } else {
                $this->currentMonth--;
            }
        } else {
            if ($this->currentMonth == 12) {
                $this->currentMonth = 1;
                $this->currentYear++;
            } else {
                $this->currentMonth++;
            }
        }

        $this->loadCurrentMonthAttendance();
    }

    public function getStatusColor($status)
    {
        switch ($status) {
            case 'hadir':
                return 'bg-green-100 text-green-800';
            case 'terlambat':
                return 'bg-yellow-100 text-yellow-800';
            case 'izin':
                return 'bg-blue-100 text-blue-800';
            case 'sakit':
                return 'bg-purple-100 text-purple-800';
            default:
                return 'bg-red-100 text-red-800';
        }
    }

    public function getStatusIcon($status)
    {
        switch ($status) {
            case 'hadir':
                return '✓';
            case 'terlambat':
                return '⏱️';
            case 'izin':
                return '📝';
            case 'sakit':
                return '🏥';
            default:
                return '✗';
        }
    }

    public function downloadAttendanceReport()
    {
        if (!$this->class) {
            $this->dispatch('show-toast', type: 'error', message: 'Kelas tidak ditemukan');
            return;
        }

        // Load attendance data jika belum dimuat
        if (empty($this->studentAttendance)) {
            $this->loadCurrentMonthAttendance();
        }

        // Generate workdays array for the month (untuk header tanggal)
        $firstDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->startOfMonth();
        $lastDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->endOfMonth();

        $days = []; // Untuk menyimpan tanggal
        $isWeekend = []; // Untuk menandai weekend
        $currentDay = $firstDayOfMonth->copy();

        while ($currentDay->lte($lastDayOfMonth)) {
            $dayNumber = $currentDay->format('d');
            $days[] = $dayNumber;

            // Tandai hari Sabtu (6) dan Minggu (0)
            $isWeekend[$dayNumber] = $currentDay->dayOfWeek === 0;

            $currentDay->addDay();
        }

        // Siapkan data untuk ditampilkan di PDF
        $attendanceData = [];
        foreach ($this->studentAttendance ?? [] as $student) {
            $studentData = [
                'name' => $student['student_name'] ?? 'Tidak ada nama',
                'days' => [],
                'summary' => [
                    'sick' => $student['summary']['sick'] ?? 0,
                    'permission' => $student['summary']['permission'] ?? 0,
                    'absent' => $student['summary']['absent'] ?? 0,
                    'late' => $student['summary']['late'] ?? 0,
                    'present' => $student['summary']['present'] ?? 0,
                ],
            ];

            // Siapkan data kosong untuk semua tanggal
            foreach ($days as $day) {
                $studentData['days'][$day] = '';
            }

            // Isi data kehadiran (jika ada)
            if (isset($student['attendance']) && is_array($student['attendance'])) {
                foreach ($student['attendance'] as $date => $status) {
                    $day = Carbon::parse($date)->format('d'); // Ambil tanggal saja dari date

                    // Konversi status ke simbol
                    $symbol = '';
                    if ($status === 'hadir') {
                        $symbol = 'H'; // HTML entity for checkmark
                    } elseif ($status === 'terlambat') {
                        $symbol = 'T';
                    } elseif ($status === 'izin') {
                        $symbol = 'I';
                    } elseif ($status === 'sakit') {
                        $symbol = 'S';
                    } elseif ($status === 'tidak_hadir') {
                        $symbol = 'A';
                    }

                    $studentData['days'][$day] = $symbol;
                }
            }

            $attendanceData[] = $studentData;
        }

        // Load logo images
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));

        // Siapkan data untuk laporan
        $data = [
            'class' => $this->class,
            'major' => $this->class->major->name ?? '',
            'month' => $this->monthNames[intval($this->currentMonth)] ?? '',
            'year' => $this->currentYear,
            'students' => $attendanceData,
            'teacher' => $this->class->teacher->user->name ?? 'Belum ditentukan',
            'days' => $days, // Pastikan variabel days diteruskan ke view
            'isWeekend' => $isWeekend,
            'logoProvData' => 'data:image/png;base64,' . $logoProvBase64,
            'logoSekolahData' => 'data:image/png;base64,' . $logoSekolahBase64,
        ];

        // Buat PDF
        $pdf = PDF::loadView('pdfs.monthly-report', $data);

        // Atur kertas dan margin
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption(['dpi' => 150, 'defaultFont' => 'sans-serif']);

        // Nama file
        $filename = 'Laporan_Kehadiran_' . $this->class->name . '_' . $data['month'] . '_' . $data['year'] . '.pdf';

        try {
            // Unduh file
            return response()->streamDownload(fn() => print $pdf->output(), $filename, ['Content-Type' => 'application/pdf']);
        } catch (\Exception $e) {
            // Log error jika terjadi masalah
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            $this->dispatch('show-toast', type: 'error', message: 'Gagal membuat laporan: ' . $e->getMessage());
            return null;
        }
    }

    public function downloadStudentAttendanceReport($studentId)
    {
        $student = Student::with('user')->find($studentId);

        if (!$student) {
            $this->dispatch('show-toast', type: 'error', message: 'Siswa tidak ditemukan');
            return;
        }

        // Dapatkan data kehadiran siswa untuk bulan ini
        $firstDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->startOfMonth();
        $lastDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->endOfMonth();

        $attendanceData = Attendance::where('user_id', $student->user_id)
            ->whereBetween('attendance_date', [$firstDayOfMonth->format('Y-m-d'), $lastDayOfMonth->format('Y-m-d')])
            ->orderBy('attendance_date')
            ->get();

        // Prepare data untuk PDF
        $data = [
            'student' => $student,
            'class' => $this->class,
            'month' => $this->monthNames[intval($this->currentMonth)],
            'year' => $this->currentYear,
            'attendance_data' => $attendanceData,
        ];

        // Buat PDF
        $pdf = PDF::loadView('pdfs.student-attendance-report', $data);

        // Nama file
        $filename = 'Laporan_Kehadiran_' . $student->user->name . '_' . $data['month'] . '_' . $data['year'] . '.pdf';

        try {
            // Unduh file
            return response()->streamDownload(fn() => print $pdf->output(), $filename, ['Content-Type' => 'application/pdf']);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', type: 'error', message: 'Gagal membuat laporan: ' . $e->getMessage());
            return null;
        }
    }

    public function render(): mixed
    {
        return view('livewire.admin.classes-detail', [
            'class' => $this->class,
            'students' => $this->students,
            'pendingPermissions' => $this->activeTab === 'permissions' ? $this->loadPermissions() : [],
        ]);
    }
}; ?>


<div class="mt-12 md:mt-0" <!-- Toast Notification Component -->
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

    <!-- Header Kelas -->
    <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
        <div class="flex flex-col items-start justify-between sm:flex-row sm:items-center">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $class->name }}</h1>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                        {{ $class->major->code }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">
                    {{ $class->major->name ?? 'Tidak ada jurusan' }} -
                    Tahun Ajaran {{ $class->school_year->name ?? 'Tidak ada tahun ajaran' }}
                </p>

                <div class="mt-2 flex flex-wrap gap-3">
                    <p class="flex flex-row items-center gap-2 font-inter text-xs text-gray-600 md:text-sm">
                        <svg height="512" class="size-4 md:size-5" viewBox="0 0 25 25" width="512"
                            xmlns="http://www.w3.org/2000/svg">
                            <g id="Teacher" fill="#2b3344">
                                <path
                                    d="m6.5 15.125a2.563 2.563 0 1 1 2.563-2.562 2.565 2.565 0 0 1 -2.563 2.562zm0-4.125a1.563 1.563 0 1 0 1.563 1.563 1.564 1.564 0 0 0 -1.563-1.563z">
                                </path>
                                <path
                                    d="m9.5 21a.5.5 0 0 1 -.5-.5v-1a2.5 2.5 0 0 0 -5 0v1a.5.5 0 0 1 -1 0v-1a3.5 3.5 0 0 1 7 0v1a.5.5 0 0 1 -.5.5z">
                                </path>
                                <path
                                    d="m9.5 20.36a.5.5 0 0 1 -.383-.821l7-8.36a.5.5 0 0 1 .766.642l-7 8.36a.5.5 0 0 1 -.383.179z">
                                </path>
                                <path d="m5.5 9a.5.5 0 0 1 -.5-.5v-4a.5.5 0 0 1 1 0v4a.5.5 0 0 1 -.5.5z"></path>
                                <path d="m21.5 5h-17a.5.5 0 0 1 0-1h17a.5.5 0 0 1 0 1z"></path>
                                <path d="m20.5 16a.5.5 0 0 1 -.5-.5v-11a.5.5 0 0 1 1 0v11a.5.5 0 0 1 -.5.5z"></path>
                                <path d="m21.5 16h-6a.5.5 0 0 1 0-1h6a.5.5 0 0 1 0 1z"></path>
                                <path d="m17.5 8h-6a.5.5 0 0 1 0-1h6a.5.5 0 0 1 0 1z"></path>
                                <path d="m17.5 10h-8a.5.5 0 0 1 0-1h8a.5.5 0 0 1 0 1z"></path>
                            </g>
                        </svg>
                        {{ $class->teacher->user->name }}
                    </p>

                    <p class="flex flex-row items-center gap-2 font-inter text-xs text-gray-600 md:text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4 md:size-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                        {{ $students->count() }} Siswa
                    </p>
                </div>
            </div>


        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-2 md:space-x-8" aria-label="Tabs">
                <button wire:click="setActiveTab('students')"
                    class="{{ $activeTab === 'students' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 px-1 py-4 text-xs font-medium md:text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 inline-block h-3 w-3 md:h-5 md:w-5"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                    </svg>
                    Daftar Siswa
                </button>
                <button wire:click="setActiveTab('permissions')"
                    class="{{ $activeTab === 'permissions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 px-1 py-4 text-xs font-medium md:text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 inline-block h-3 w-3 md:h-5 md:w-5"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                            clip-rule="evenodd" />
                    </svg>
                    Pengajuan Izin
                </button>
                <button wire:click="setActiveTab('attendance')"
                    class="{{ $activeTab === 'attendance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 px-1 py-4 text-xs font-medium md:text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 inline-block h-3 w-3 md:h-5 md:w-5"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                            clip-rule="evenodd" />
                    </svg>
                    Rekap Kehadiran
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Contents -->
    <div>
        @if ($activeTab === 'students')
            @include('livewire.admin.partials.classes-detail-students')
        @elseif ($activeTab === 'permissions')
            @include('livewire.admin.partials.classes-detail-permissions')
        @elseif ($activeTab === 'attendance')
            @include('livewire.admin.partials.classes-detail-attendance')
        @endif
    </div>

    <!-- Modals -->
    @include('livewire.admin.partials.classes-detail-modals')
</div>
