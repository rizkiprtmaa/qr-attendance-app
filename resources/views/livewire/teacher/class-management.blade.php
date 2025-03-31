<?php

use Livewire\Volt\Component;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\PermissionSubmission;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public $teacherId;
    public $userId;
    public $class;
    public $students = [];
    public $activeStudent = null;
    // Untuk satu izin
    public $activePermission = null;

    // Untuk koleksi izin
    public $permissionHistory = [];
    public $adminNotes;
    public $studentAttendance = [];
    public $currentMonth;
    public $currentYear;
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
    public $activeTab = 'students'; // students, permissions, attendance, reports

    // Modals
    public $showPermissionsModal = false;
    public $showEditModal = false;
    public $showAddModal = false;
    public $showAttendanceDetailModal = false;
    public $showPermissionDetailModal = false;

    // Filters
    public $search = '';
    public $permissionStatus = '';
    public $permissionDateRange = [];
    public $attendanceFilters = [
        'date' => '',
        'status' => '',
    ];

    // Forms
    public $editForm = [
        'nisn' => '',
        'parent_number' => '',
    ];

    public $addForm = [
        'name' => '',
        'email' => '',
        'nisn' => '',
        'parent_number' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    // Validation rules
    protected $rules = [
        'editForm.nisn' => 'required|string|max:20',
        'editForm.parent_number' => 'nullable|string|max:15',
        'editForm.major' => 'nullable|string|max:100',

        'addForm.name' => 'required|string|max:255',
        'addForm.email' => 'required|email|unique:users,email',
        'addForm.nisn' => 'required|string|max:20|unique:students,nisn',
        'addForm.parent_number' => 'nullable|string|max:15',
        'addForm.password' => 'required|min:8|confirmed',
        'addForm.password_confirmation' => 'required|min:8',

        'adminNotes' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        $this->userId = auth()->user()->id;

        // Cari teacher_id berdasarkan user yang login
        $teacher = Teacher::where('user_id', $this->userId)->first();
        $this->teacherId = $teacher ? $teacher->id : null;

        // Ambil kelas wali
        if ($this->teacherId) {
            $this->class = Classes::where('teacher_id', $this->teacherId)
                ->with(['major', 'school_year'])
                ->first();

            if ($this->class) {
                $this->loadStudents();
            }
        }

        // Set bulan dan tahun saat ini untuk laporan kehadiran
        $this->currentMonth = intval(date('m'));
        $this->currentYear = date('Y');
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
                        $symbol = '√';
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

        // Dalam fungsi downloadAttendanceReport(), tambahkan:
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));

        $data['logoProvData'] = 'data:image/png;base64,' . $logoProvBase64;
        $data['logoSekolahData'] = 'data:image/png;base64,' . $logoSekolahBase64;

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
            'logoProvData' => $data['logoProvData'],
            'logoSekolahData' => $data['logoSekolahData'],
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

    public function loadStudents()
    {
        if (!$this->class) {
            return;
        }

        $query = Student::where('classes_id', $this->class->id)->with([
            'user' => function ($q) {
                $q->select('id', 'name', 'email', 'qr_code_path');
            },
        ]);

        // Filter pencarian
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
                })
                    ->orWhere('nisn', 'like', '%' . $this->search . '%')
                    ->orWhere('parent_number', 'like', '%' . $this->search . '%');
            });
        }

        $this->students = $query->get();
    }

    public function updatedSearch()
    {
        $this->loadStudents();
    }

    // Tab navigation
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;

        if ($tab === 'permissions') {
            $this->loadPendingPermissions();
        } elseif ($tab === 'attendance') {
            $this->loadCurrentMonthAttendance();
        }
    }

    // Student CRUD
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
            $this->loadStudents();
        }
    }

    public function showAddStudent()
    {
        $this->reset('addForm');
        $this->showAddModal = true;
    }

    public function addStudent()
    {
        $this->validate([
            'addForm.name' => 'required|string|max:255',
            'addForm.email' => 'required|email|unique:users,email',
            'addForm.nisn' => 'required|string|max:20|unique:students,nisn',
            'addForm.parent_number' => 'nullable|string|max:15',
            'addForm.password' => 'required|min:8|confirmed',
            'addForm.password_confirmation' => 'required|min:8',
        ]);

        try {
            DB::beginTransaction();

            // Buat user baru
            $user = User::create([
                'name' => $this->addForm['name'],
                'email' => $this->addForm['email'],
                'password' => bcrypt($this->addForm['password']),
            ]);

            // Assign role student
            $user->assignRole('student');

            // Buat record siswa
            Student::create([
                'user_id' => $user->id,
                'classes_id' => $this->class->id,
                'nisn' => $this->addForm['nisn'],
                'parent_number' => $this->addForm['parent_number'],
                'major' => $this->class->major->name ?? null,
            ]);

            DB::commit();

            $this->dispatch('show-toast', type: 'success', message: 'Siswa baru berhasil ditambahkan');
            $this->showAddModal = false;
            $this->loadStudents();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-toast', type: 'error', message: 'Gagal menambahkan siswa: ' . $e->getMessage());
        }
    }

    // Permission Management
    public function loadPendingPermissions()
    {
        if (!$this->class) {
            return [];
        }

        // Get student IDs in the class
        $studentIds = Student::where('classes_id', $this->class->id)->pluck('user_id');

        // Get pending permissions
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
            $this->showPermissionsModal = true;
        }
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

            // Refresh data
            if ($this->showPermissionsModal) {
                $this->activePermission = PermissionSubmission::where('user_id', $this->activeStudent->user_id)->orderBy('created_at', 'desc')->get();
            }
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

            // Refresh data
            if ($this->showPermissionsModal) {
                $this->activePermission = PermissionSubmission::where('user_id', $this->activeStudent->user_id)->orderBy('created_at', 'desc')->get();
            }
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

    public function loadCurrentMonthAttendance()
    {
        if (!$this->class) {
            return;
        }

        $studentIds = Student::where('classes_id', $this->class->id)->pluck('user_id')->toArray();

        $firstDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->startOfMonth();
        $lastDayOfMonth = Carbon::createFromDate($this->currentYear, intval($this->currentMonth), 1)->endOfMonth();

        // Get all attendance data for the month
        $attendanceData = Attendance::whereIn('user_id', $studentIds)
            ->whereBetween('attendance_date', [$firstDayOfMonth->format('Y-m-d'), $lastDayOfMonth->format('Y-m-d')])
            ->get();

        // Log untuk debugging
        \Log::info('Jumlah record kehadiran: ' . $attendanceData->count());

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

            // Inisialisasi semua hari kerja sebagai tidak hadir
            foreach ($workingDays as $day) {
                $studentData['attendance'][$day] = 'tidak_hadir';
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

            $this->studentAttendance[] = $studentData;
        }

        // Debug
        \Log::info('Data kehadiran siswa:', [
            'count' => count($this->studentAttendance),
            'sample' => isset($this->studentAttendance[0])
                ? [
                    'name' => $this->studentAttendance[0]['student_name'],
                    'attendance_count' => count($this->studentAttendance[0]['attendance'] ?? []),
                    'summary' => $this->studentAttendance[0]['summary'],
                ]
                : 'No data',
        ]);
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

    public function render(): mixed
    {
        return view('livewire.teacher.class-management', [
            'class' => $this->class,
            'students' => $this->students,
            'pendingPermissions' => $this->activeTab === 'permissions' ? $this->loadPendingPermissions() : [],
        ]);
    }
}; ?>


<div>
    <!-- Toast Notification Component -->
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

    @if (!$class)
        <div class="py-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak Ada Kelas</h3>
            <p class="mt-1 text-sm text-gray-500">Anda belum menjadi wali kelas untuk kelas manapun.</p>
            <p class="mt-4 text-sm text-gray-500">Silakan hubungi administrator untuk ditugaskan sebagai wali kelas.</p>
        </div>
    @else
        <!-- Header Kelas -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
            <div class="flex flex-col items-start justify-between sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $class->name }}</h1>
                    <p class="text-sm text-gray-500">
                        {{ $class->major->name ?? 'Tidak ada jurusan' }} -
                        Tahun Ajaran {{ $class->school_year->name ?? 'Tidak ada tahun ajaran' }}
                    </p>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 sm:mt-0">
                    <span
                        class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                        </svg>
                        Wali Kelas
                    </span>
                    <span
                        class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path
                                d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                        </svg>
                        {{ count($students) }} Siswa
                    </span>
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
            <!-- Students Tab -->
            @if ($activeTab === 'students')
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-200 p-6">
                        <div
                            class="flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
                            <div class="relative w-full sm:w-64">
                                <input type="text" wire:model.live.debounce.300ms="search"
                                    placeholder="Cari siswa..."
                                    class="w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500 md:text-sm" />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400 md:h-5 md:w-5"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <button wire:click="showAddStudent"
                                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-xs font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Tambah Siswa
                            </button>
                        </div>
                    </div>

                    <!-- Student list -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Nama
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        NISN
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Email
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        No. Orang Tua
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
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <div
                                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
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
                                            <div class="text-sm text-gray-900">{{ $student->parent_number ?: '-' }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <button wire:click="showEditStudent({{ $student->id }})"
                                                    class="rounded-md bg-blue-50 px-2 py-1 text-blue-600 hover:bg-blue-100 hover:text-blue-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="viewPermissions({{ $student->id }})"
                                                    class="rounded-md bg-green-50 px-2 py-1 text-green-600 hover:bg-green-100 hover:text-green-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </button>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @if (count($students) === 0)
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <p class="mt-2">Tidak ada siswa yang ditemukan</p>
                                                <button wire:click="showAddStudent"
                                                    class="mt-3 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                    Tambah Siswa Baru
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>



                </div>

                <div class="md:hidden">
                    <div class="mt-5 divide-y divide-gray-200 rounded-lg bg-white shadow">
                        @foreach ($students as $student)
                            <div class="p-4">
                                <div class="flex flex-row items-center justify-between">
                                    <div class="flex flex-row items-center gap-3">
                                        <div>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <p class="font-inter text-sm font-medium text-slate-900">
                                                {{ $student->user->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $student->nisn }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-row items-center gap-2">
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
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if (count($students) === 0)
                        <div>
                            <div class="px-6 py-10 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <p class="mt-2">Tidak ada siswa yang ditemukan</p>
                                    <button wire:click="showAddStudent"
                                        class="mt-3 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Tambah Siswa Baru
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            @endif

            <!-- Permissions Tab -->
            @if ($activeTab === 'permissions')
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-200 p-6">
                        <div
                            class="flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
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
                                                <div class="h-8 w-8 flex-shrink-0">
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
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
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
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
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
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

                <div class="mt-5 flex flex-col md:hidden">
                    <div class="mt-5 divide-y divide-gray-200 rounded-lg bg-white shadow">
                        @foreach ($pendingPermissions as $permission)
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
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="currentColor" class="size-4">
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
                        @endforeach


                    </div>
                </div>
            @endif

            <!-- Attendance Tab -->
            @if ($activeTab === 'attendance')
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="border-b border-gray-200 p-6">
                        <div
                            class="flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">

                            <h2 class="text-lg font-medium text-gray-900">Rekap Kehadiran</h2>

                            <div class="flex flex-row items-center justify-center space-x-3">
                                <button wire:click="downloadAttendanceReport"
                                    class="ml-4 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:inline-flex">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Unduh Laporan
                                </button>
                                <button wire:click="changeMonth('prev')"
                                    class="rounded-md border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <span class="min-w-[160px] text-center text-sm font-medium">
                                    {{ $monthNames[intval($currentMonth)] }} {{ $currentYear }}
                                </span>
                                <button wire:click="changeMonth('next')"
                                    class="rounded-md border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>

                            </div>
                        </div>
                    </div>

                    <!-- Attendance Table -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full divide-y divide-gray-200">
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
                                            <span
                                                class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                                {{ $record['summary']['present'] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center">
                                            <span
                                                class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                                                {{ $record['summary']['late'] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center">
                                            <span
                                                class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                                                {{ $record['summary']['permission'] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center">
                                            <span
                                                class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800">
                                                {{ $record['summary']['sick'] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center">
                                            <span
                                                class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
                                                {{ $record['summary']['absent'] }}
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
                                        <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <p class="mt-2">Tidak ada data kehadiran untuk bulan ini</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif


            <!-- Modals -->
            <!-- Edit Student Modal -->
            <div x-data="{ show: @entangle('showEditModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                    <div x-show="show" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                    <div x-show="show" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                        <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                            <button @click="show = false" type="button"
                                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span class="sr-only">Tutup</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex flex-col">
                            <div class="flex flex-row items-center px-6">
                                <div
                                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>

                                </div>
                                <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Edit Data
                                        Siswa</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">Perbarui informasi siswa
                                            {{ $activeStudent->user->name ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 w-full px-6 py-4 sm:ml-6 sm:mt-0 sm:w-auto">

                                <form wire:submit.prevent="saveStudent" class="mt-4 space-y-4">
                                    <div>
                                        <label for="nisn"
                                            class="block text-sm font-medium text-gray-700">NISN</label>
                                        <input type="text" wire:model="editForm.nisn" id="nisn"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('editForm.nisn')
                                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="parent_number"
                                            class="block text-sm font-medium text-gray-700">Nomor
                                            HP Orang Tua</label>
                                        <input type="text" wire:model="editForm.parent_number" id="parent_number"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('editForm.parent_number')
                                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div
                                        class="mt-6 flex items-center justify-end gap-1 border-t border-gray-200 pt-4">
                                        <button type="submit"
                                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                                        <button @click="show = false" type="button"
                                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Add Student Modal -->
            <div x-data="{ show: @entangle('showAddModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-scroll"
                aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex min-h-screen flex-col items-center justify-center px-4 pb-20 pt-4 text-center sm:p-0">
                    <div x-show="show" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                    <div x-show="show" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="w-full transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                        <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                            <button @click="show = false" type="button"
                                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span class="sr-only">Tutup</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex flex-col sm:flex sm:items-start">
                            <div class="flex flex-row items-center">
                                <div
                                    class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div class="ml-4 mt-3 w-full text-left sm:mt-0 md:text-center">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">Tambah
                                        Siswa
                                        Baru</h3>
                                    <div class="mt-2">
                                        <p class="text-xs text-gray-500 md:text-sm">Data siswa akan ditambahkan ke
                                            kelas
                                            {{ $class->name ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 w-full px-6 py-4">
                                <form wire:submit.prevent="addStudent" class="mt-4 space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Nama
                                            Lengkap</label>
                                        <input type="text" wire:model="addForm.name" id="name"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('addForm.name')
                                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="email"
                                            class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" wire:model="addForm.email" id="email"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('addForm.email')
                                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="nisn"
                                            class="block text-sm font-medium text-gray-700">NISN</label>
                                        <input type="text" wire:model="addForm.nisn" id="nisn"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('addForm.nisn')
                                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="parent_number"
                                            class="block text-sm font-medium text-gray-700">Nomor
                                            HP Orang Tua</label>
                                        <input type="text" wire:model="addForm.parent_number" id="parent_number"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('addForm.parent_number')
                                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="password"
                                            class="block text-sm font-medium text-gray-700">Password</label>
                                        <input type="password" wire:model="addForm.password" id="password"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('addForm.password')
                                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="password_confirmation"
                                            class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                                        <input type="password" wire:model="addForm.password_confirmation"
                                            id="password_confirmation"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        @error('addForm.password_confirmation')
                                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div
                                        class="mt-5 flex flex-row items-center justify-end gap-2 border-t border-gray-200 pt-4 text-xs md:gap-1 md:text-sm">
                                        <button @click="show = false" type="button"
                                            class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm md:text-base">Batal</button>
                                        <button type="submit"
                                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm md:text-base">Tambah
                                            Siswa</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Permissions Modal -->
        <div x-data="{ show: @entangle('showPermissionsModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0"
                overflow-auto>
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                <div x-show="show" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl sm:p-6 sm:align-middle">
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button @click="show = false" type="button"
                            class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span class="sr-only">Tutup</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <div class="flex items-center">
                            <div
                                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
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
                                                        <button
                                                            wire:click="viewPermissionDetail({{ $permission->id }})"
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
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="w-full transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle">
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button @click="show = false" type="button"
                            class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span class="sr-only">Tutup</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        @if ($activePermission)
                            <div class="flex items-center justify-start">
                                <div
                                    class="{{ $activePermission->type === 'izin' ? 'bg-blue-100' : 'bg-purple-100' }} flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="{{ $activePermission->type === 'izin' ? 'text-blue-600' : 'text-purple-600' }} h-6 w-6"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4 text-center sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Detail Perizinan</h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ ucfirst($activePermission->type) }} -
                                        {{ \Carbon\Carbon::parse($activePermission->permission_date)->format('d M Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <dl class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-1">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Nama Siswa</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ $activePermission->user->name ?? '-' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="mt-1 text-sm">
                                            <span
                                                class="{{ $activePermission->status === 'pending'
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : ($activePermission->status === 'approved'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800') }} inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                                {{ $activePermission->status === 'pending'
                                                    ? 'Menunggu'
                                                    : ($activePermission->status === 'approved'
                                                        ? 'Disetujui'
                                                        : 'Ditolak') }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Keterangan</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ $activePermission->description ?: 'Tidak ada keterangan' }}
                                        </dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Lampiran</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            @if ($activePermission->attachment_path)
                                                <a href="{{ Storage::url($activePermission->attachment_path) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center text-blue-600 hover:text-blue-900">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                    </svg>
                                                    Lihat Lampiran
                                                </a>
                                            @else
                                                <span class="text-gray-500">Tidak ada lampiran</span>
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Catatan Admin</dt>
                                        <dd class="mt-1">
                                            @if ($activePermission->status === 'pending')
                                                <textarea wire:model="adminNotes" rows="3"
                                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                    placeholder="Tambahkan catatan untuk siswa"></textarea>
                                            @else
                                                <p class="text-sm text-gray-900">
                                                    {{ $activePermission->admin_notes ?: 'Tidak ada catatan' }}
                                                </p>
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                @if ($activePermission->status === 'pending')
                                    <button wire:click="approvePermission({{ $activePermission->id }})"
                                        type="button"
                                        class="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:col-start-2 sm:text-sm">
                                        Setujui
                                    </button>
                                    <button wire:click="rejectPermission({{ $activePermission->id }})" type="button"
                                        class="mt-3 inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:col-start-1 sm:mt-0 sm:text-sm">
                                        Tolak
                                    </button>
                                @else
                                    <button @click="show = false" type="button"
                                        class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:col-span-2 sm:mt-0 sm:text-sm">
                                        Tutup
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Data perizinan tidak ditemukan</p>
                                <button @click="show = false" type="button"
                                    class="mt-5 inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-6 sm:w-auto sm:text-sm">
                                    Tutup
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
