<?php

use Livewire\Volt\Component;
use App\Models\AutomaticSchedule;
use App\Models\AutomaticScheduleDetail;
use App\Models\SubjectClass;
use App\Models\Classes;
use App\Models\SubjectClassAttendance;
use App\Models\SubjectClassSession;
use App\Models\Student;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    // Request parameter
    public $day;

    // Model data
    public $schedule;
    public $scheduleDetails = [];

    // Form data for adding new schedule
    public $subjectClassId;
    public $teacherId;
    public $startTime;
    public $endTime;
    public $jamPelajaran;
    public $sessionTitleTemplate = 'Pertemuan %subject% - %date%';

    // Edit form data
    public $editingDetailId = null;
    public $editSubjectClassId;
    public $editStartTime;
    public $editEndTime;
    public $editJamPelajaran;
    public $editSessionTitleTemplate;

    // Filter and search
    public $search = '';
    public $teacherFilter = '';

    // Array of day names for display
    public $dayNames = [
        'Senin' => 'Monday',
        'Selasa' => 'Tuesday',
        'Rabu' => 'Wednesday',
        'Kamis' => 'Thursday',
        'Jumat' => 'Friday',
        'Sabtu' => 'Saturday',
    ];

    public function mount($day)
    {
        $this->day = $day;

        // Find or create the schedule for this day
        $this->schedule = AutomaticSchedule::firstOrCreate(['day_of_week' => $day], ['is_active' => true]);

        $this->loadScheduleDetails();
    }

    public function loadScheduleDetails()
    {
        $query = AutomaticScheduleDetail::where('automatic_schedule_id', $this->schedule->id)->with(['subjectClass', 'subjectClass.user']);

        // Apply search filter
        if (!empty($this->search)) {
            $query->whereHas('subjectClass', function ($q) {
                $q->where('class_name', 'like', '%' . $this->search . '%')->orWhere('class_code', 'like', '%' . $this->search . '%');
            });
        }

        // Apply teacher filter
        if (!empty($this->teacherFilter)) {
            $query->whereHas('subjectClass', function ($q) {
                $q->where('user_id', $this->teacherFilter);
            });
        }

        $this->scheduleDetails = $query->join('subject_classes', 'automatic_schedule_details.subject_class_id', '=', 'subject_classes.id')->join('classes', 'subject_classes.classes_id', '=', 'classes.id')->join('majors', 'classes.major_id', '=', 'majors.id')->orderBy('majors.name', 'asc')->orderBy('classes.name', 'asc')->orderBy('automatic_schedule_details.start_time', 'asc')->select('automatic_schedule_details.*')->get();
    }

    public function getClassLevelBadgeColor($className)
    {
        // Extract angka kelas (10, 11, 12) dari nama kelas
        $classLevel = null;

        // Cek apakah nama kelas dimulai dengan angka kelas
        if (preg_match('/^(10|11|12)/i', $className, $matches)) {
            $classLevel = $matches[1];
        }

        // Tentukan warna berdasarkan tingkat kelas
        return match ($classLevel) {
            '10' => 'bg-emerald-200 text-emerald-800',
            '11' => 'bg-amber-200 text-amber-800',
            '12' => 'bg-purple-200 text-purple-800',
            default => 'bg-gray-200 text-gray-800',
        };
    }

    // Validation rules for the form
    public function rules()
    {
        return [
            'subjectClassId' => 'required|exists:subject_classes,id',
            'startTime' => 'required',
            'endTime' => 'required|after:startTime',
            'jamPelajaran' => 'required|numeric|min:1',
            'sessionTitleTemplate' => 'required|string',
        ];
    }

    // Add new schedule detail
    public function addScheduleDetail()
    {
        $this->validate();

        try {
            AutomaticScheduleDetail::create([
                'automatic_schedule_id' => $this->schedule->id,
                'subject_class_id' => $this->subjectClassId,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'jam_pelajaran' => $this->jamPelajaran,
                'session_title_template' => $this->sessionTitleTemplate,
                'is_active' => true,
            ]);

            $this->reset(['subjectClassId', 'startTime', 'endTime', 'jamPelajaran']);
            $this->sessionTitleTemplate = 'Pertemuan %subject% - %date%';

            $this->loadScheduleDetails();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Jadwal berhasil ditambahkan!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal menambahkan jadwal: ' . $e->getMessage(),
            ]);
        }
    }

    // Edit existing schedule detail
    public function editScheduleDetail($detailId)
    {
        $detail = AutomaticScheduleDetail::findOrFail($detailId);

        $this->editingDetailId = $detailId;
        $this->editSubjectClassId = $detail->subject_class_id;
        $this->editStartTime = $detail->start_time;
        $this->editEndTime = $detail->end_time;
        $this->editJamPelajaran = $detail->jam_pelajaran;
        $this->editSessionTitleTemplate = $detail->session_title_template;
    }

    // Update schedule detail
    public function updateScheduleDetail()
    {
        $this->validate([
            'editSubjectClassId' => 'required|exists:subject_classes,id',
            'editStartTime' => 'required',
            'editEndTime' => 'required|after:editStartTime',
            'editJamPelajaran' => 'required|numeric|min:1',
            'editSessionTitleTemplate' => 'required|string',
        ]);

        try {
            $detail = AutomaticScheduleDetail::findOrFail($this->editingDetailId);

            $detail->update([
                'subject_class_id' => $this->editSubjectClassId,
                'start_time' => $this->editStartTime,
                'end_time' => $this->editEndTime,
                'jam_pelajaran' => $this->editJamPelajaran,
                'session_title_template' => $this->editSessionTitleTemplate,
            ]);

            $this->reset(['editingDetailId', 'editSubjectClassId', 'editStartTime', 'editEndTime', 'editJamPelajaran', 'editSessionTitleTemplate']);

            $this->loadScheduleDetails();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Jadwal berhasil diperbarui!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal memperbarui jadwal: ' . $e->getMessage(),
            ]);
        }
    }

    // Toggle active status of a schedule detail
    public function toggleDetailStatus($detailId)
    {
        $detail = AutomaticScheduleDetail::findOrFail($detailId);

        $detail->update([
            'is_active' => !$detail->is_active,
        ]);

        $this->loadScheduleDetails();

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Status jadwal berhasil diubah!',
        ]);
    }

    // Delete schedule detail
    public function deleteScheduleDetail($detailId)
    {
        try {
            AutomaticScheduleDetail::where('id', $detailId)->delete();

            $this->loadScheduleDetails();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Jadwal berhasil dihapus!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal menghapus jadwal: ' . $e->getMessage(),
            ]);
        }
    }

    // Confirmation before delete
    public function confirmDelete($detailId)
    {
        $this->dispatch('confirm-delete', $detailId);
    }

    #[On('delete-confirmed')]
    public function onDeleteConfirmed($detailId)
    {
        $this->deleteScheduleDetail($detailId);
    }

    // Cancel editing
    public function cancelEdit()
    {
        $this->reset(['editingDetailId', 'editSubjectClassId', 'editStartTime', 'editEndTime', 'editJamPelajaran', 'editSessionTitleTemplate']);
    }

    // Update filter and reload
    public function updatedSearch()
    {
        $this->loadScheduleDetails();
    }

    public function updatedTeacherFilter()
    {
        $this->loadScheduleDetails();
    }

    // Generate sessions for this schedule (test function)
    public function generateSessionsForToday()
    {
        $generatedCount = $this->generateScheduledSessionsForDay($this->day);

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Berhasil menghasilkan ' . $generatedCount . ' pertemuan untuk hari ini!',
        ]);
    }

    // Method to actually generate sessions for a particular day
    public function generateScheduledSessionsForDay($dayName)
    {
        $dayOfWeek = array_search($dayName, array_keys($this->dayNames));

        if ($dayOfWeek === false) {
            return 0;
        }

        // Get schedule for this day
        $schedule = AutomaticSchedule::where('day_of_week', $dayName)->where('is_active', true)->first();

        if (!$schedule) {
            return 0;
        }

        // Get active schedule details
        $scheduleDetails = AutomaticScheduleDetail::where('automatic_schedule_id', $schedule->id)->where('is_active', true)->get();

        if ($scheduleDetails->isEmpty()) {
            return 0;
        }

        // Get the next date for this day of week
        $today = now();
        $targetDate = $today->copy()->next($this->dayNames[$dayName]);
        $generatedCount = 0;

        // Generate sessions for each schedule detail
        foreach ($scheduleDetails as $detail) {
            // Only proceed if the subject class still exists
            $subjectClass = SubjectClass::find($detail->subject_class_id);

            if (!$subjectClass) {
                continue;
            }

            // Format the session title using the template
            $sessionTitle = str_replace(['%subject%', '%date%'], [$subjectClass->class_name, $targetDate->format('d-m-Y')], $detail->session_title_template);

            // Check if a session already exists for this date and time
            $existingSession = SubjectClassSession::where('subject_class_id', $detail->subject_class_id)->whereDate('class_date', $targetDate->format('Y-m-d'))->where('start_time', $detail->start_time)->first();

            if ($existingSession) {
                continue; // Skip if session already exists
            }

            // Create the session
            $session = SubjectClassSession::create([
                'subject_class_id' => $detail->subject_class_id,
                'subject_title' => $sessionTitle,
                'class_date' => $targetDate->format('Y-m-d') . ' ' . $detail->start_time,
                'start_time' => $detail->start_time,
                'end_time' => $detail->end_time,
                'jam_pelajaran' => $detail->jam_pelajaran,
            ]);

            // Get all students in this class
            $students = Student::whereHas('classes', function ($query) use ($subjectClass) {
                $query->where('id', $subjectClass->classes_id);
            })->get();

            // Create attendance records for each student in this session
            foreach ($students as $student) {
                // Check if student has approved permission for this date
                $permissionExists = \App\Models\PermissionSubmission::where('user_id', $student->user_id)->whereDate('permission_date', $targetDate->format('Y-m-d'))->where('status', 'approved')->first();

                if ($permissionExists) {
                    // Jika siswa memiliki izin yang disetujui, atur status sesuai tipe izin (izin/sakit)
                    SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => $permissionExists->type, // 'izin' or 'sakit'
                        'check_in_time' => $targetDate->format('Y-m-d') . ' ' . $detail->start_time,
                    ]);
                } else {
                    // Jika tidak ada izin, set default status 'tidak_hadir'
                    SubjectClassAttendance::create([
                        'subject_class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'status' => 'tidak_hadir', // Default status
                        'check_in_time' => null,
                    ]);
                }
            }

            $generatedCount++;
        }

        return $generatedCount;
    }

    public function getTeachersProperty()
    {
        return User::role('teacher')
            ->whereHas('teacher', function ($query) {
                $query->where('is_karyawan', 0);
            })
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'kepala_sekolah');
            })
            ->when($this->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.admin.automatic-schedule-detail', [
            'dayName' => $this->day,
            'teachers' => $this->getTeachersProperty(),
            'subjectClasses' => SubjectClass::with('user', 'classes', 'classes.major')->get(),
        ]);
    }
}; ?>

<div x-data="{
    showAddModal: false,
    showEditModal: false,
    confirmingDelete: false,
    detailToDelete: null
}" @confirm-delete.window="confirmingDelete = true; detailToDelete = $event.detail"
    class="mt-12 md:mt-0">
    <div class="mx-auto mt-8 max-w-7xl">
        <!-- Header -->
        <div class="mb-6 flex flex-col items-center justify-start md:flex-row md:justify-between">
            <div>
                <div class="flex items-center">
                    <a href="{{ route('admin.automatic-schedules') }}"
                        class="hidden text-gray-500 hover:text-blue-600 md:block">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                        </svg>
                    </a>
                    <h1 class="ml-4 font-inter text-2xl font-semibold text-gray-900">Jadwal
                        {{ $dayName }}</h1>
                </div>
                <p class="mt-1 hidden font-inter text-sm text-gray-500 md:block">Kelola jadwal otomatis untuk hari
                    {{ $dayName }}
                </p>
            </div>

            <div class="mt-4 flex space-x-2 md:mt-0">
                <button wire:click="generateSessionsForToday" type="button"
                    class="inline-flex items-center rounded-md border border-blue-600 bg-white px-4 py-2 text-sm font-medium text-blue-600 shadow-sm hover:bg-blue-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mr-2 h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" />
                    </svg>
                    Generate Manual
                </button>

                <button @click="showAddModal = true" type="button"
                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mr-2 h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Jadwal
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-4 flex flex-col space-y-3 md:flex-row md:items-center md:justify-between md:space-y-0">
            <div class="flex w-full md:w-72">
                <div class="relative flex w-full items-stretch">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="h-5 w-5 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari mata pelajaran..."
                        class="block w-full rounded-md border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 md:w-72">
                </div>
            </div>

            <div class="w-full md:w-64">
                <select wire:model.live="teacherFilter"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6">
                    <option value="">Semua Guru</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Schedule List -->
        <div class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white font-inter shadow md:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Mata Pelajaran</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Guru</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Kelas</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Waktu</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                JP</th>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($scheduleDetails as $detail)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">

                                        <div>
                                            <div class="truncate text-sm font-medium text-gray-900">
                                                {{ $detail->subjectClass->class_name }}</div>

                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="truncate text-sm text-gray-900">{{ $detail->subjectClass->user->name }}
                                    </div>
                                </td>
                                <td class="flex flex-col gap-2 px-6 py-4">
                                    <div
                                        class="{{ $this->getClassLevelBadgeColor($detail->subjectClass->classes->name) }} rounded-md py-1 text-center text-xs">
                                        {{ $detail->subjectClass->classes->name }}</div>
                                    <div
                                        class="{{ $detail->subjectClass->classes->major->badge_color }} rounded-md py-1 text-center text-xs">
                                        {{ $detail->subjectClass->classes->major->code }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($detail->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($detail->end_time)->format('H:i') }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $detail->jam_pelajaran }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4">
                                    <span
                                        class="{{ $detail->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                        {{ $detail->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <button wire:click="toggleDetailStatus({{ $detail->id }})"
                                            class="{{ $detail->is_active ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }} rounded-md px-2.5 py-1 text-xs">
                                            {{ $detail->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                        <button wire:click="editScheduleDetail({{ $detail->id }})"
                                            @click="showEditModal = true"
                                            class="rounded-md bg-blue-100 px-2.5 py-1 text-xs text-blue-700">
                                            Edit
                                        </button>
                                        <button wire:click="confirmDelete({{ $detail->id }})"
                                            class="rounded-md bg-red-100 px-2.5 py-1 text-xs text-red-700">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Belum ada jadwal untuk hari {{ $dayName }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View: Card List -->
        <div class="mb-4 block md:hidden">
            <div class="space-y-4">
                @forelse($scheduleDetails as $detail)
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex flex-row justify-between">
                                <div class="mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ $detail->subjectClass->class_name }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ $detail->subjectClass->user->name }}</p>
                                </div>

                                <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
                                    <span
                                        class="{{ $this->getClassLevelBadgeColor($detail->subjectClass->classes->name) }} rounded-md px-2 py-1 text-xs">
                                        {{ $detail->subjectClass->classes->name }}
                                    </span>
                                    <span
                                        class="{{ $detail->subjectClass->classes->major->badge_color }} rounded-md px-2 py-1 text-xs">
                                        {{ $detail->subjectClass->classes->major->code }}
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4 grid grid-cols-3 gap-4 text-center text-sm">
                                <div>
                                    <span class="text-xs text-gray-500">Waktu:</span>
                                    <p class="text-xs text-gray-800">
                                        {{ \Carbon\Carbon::parse($detail->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($detail->end_time)->format('H:i') }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-gray-500">Total JP:</span>
                                    <p class="text-xs text-gray-800">{{ $detail->jam_pelajaran }}</p>
                                </div>
                                <div>
                                    <span
                                        class="{{ $detail->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} inline-flex rounded-full px-2 py-1 text-xs font-semibold">
                                        {{ $detail->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </div>



                            <div class="flex space-x-2">
                                <button wire:click="toggleDetailStatus({{ $detail->id }})"
                                    class="{{ $detail->is_active ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }} flex-1 rounded-md px-3 py-2 text-sm font-medium">
                                    {{ $detail->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                                <button wire:click="editScheduleDetail({{ $detail->id }})"
                                    @click="showEditModal = true"
                                    class="flex-1 rounded-md bg-blue-100 px-3 py-2 text-sm font-medium text-blue-700">
                                    Edit
                                </button>
                                <button wire:click="confirmDelete({{ $detail->id }})"
                                    class="flex-1 rounded-md bg-red-100 px-3 py-2 text-sm font-medium text-red-700">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-gray-200 bg-white p-4 text-center text-sm text-gray-500">
                        Belum ada jadwal untuk hari {{ $dayName }}
                    </div>
                @endforelse
            </div>
        </div>


    </div>

    <!-- Add Schedule Modal -->
    <div x-cloak x-show="showAddModal" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="showAddModal = false" x-on:click.self="showAddModal = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true">

        <div x-show="showAddModal" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">

            <div class="bg-blue-50 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Tambah Jadwal Otomatis</h3>
                <p class="mt-1 text-sm text-gray-500">Tambahkan jadwal baru untuk hari {{ $dayName }}</p>
            </div>

            <div class="px-6 py-4">
                <form wire:submit="addScheduleDetail">
                    <div class="mb-4" x-data="{
                        open: false,
                        search: '',
                        selected: null,
                    
                        get filteredOptions() {
                            return this.search === '' ?
                                @js($subjectClasses) :
                                @js($subjectClasses).filter(option =>
                                    option.class_name.toLowerCase().includes(this.search.toLowerCase()) ||
                                    option.classes.name.toLowerCase().includes(this.search.toLowerCase()) ||
                                    option.user.name.toLowerCase().includes(this.search.toLowerCase()) || option.classes.major.code.toLowerCase().includes(this.search.toLowerCase())
                                )
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700">Mata Pelajaran</label>
                        <div class="relative mt-1">
                            <button type="button" @click="open = !open"
                                class="relative w-full cursor-default rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-left shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:text-sm">
                                <span class="block truncate">
                                    <template x-if="selected">
                                        <span
                                            x-text="selected.class_name + ' - ' + selected.classes.name + ' - ' + selected.classes.major.code + ' (' + selected.user.name + ')'"></span>
                                    </template>
                                    <template x-if="!selected">
                                        <span class="text-gray-500">-- Pilih Mata Pelajaran --</span>
                                    </template>
                                </span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="none"
                                        stroke="currentColor">
                                        <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </button>

                            <div x-show="open" @click.away="open = false"
                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                <div class="sticky top-0 z-10 bg-white px-2 py-2">
                                    <input type="search" x-model="search"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Cari mata pelajaran...">
                                </div>

                                <template x-for="option in filteredOptions" :key="option.id">
                                    <div @click="selected = option; open = false; $wire.subjectClassId = option.id"
                                        :class="{
                                            'bg-blue-600 text-white': selected?.id === option
                                                .id,
                                            'text-gray-900': selected?.id !== option.id
                                        }"
                                        class="relative cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-blue-600 hover:text-white">
                                        <span
                                            x-text="option.class_name + ' - ' + option.classes.name + ' - ' + option.classes.major.code + ' (' + option.user.name + ')'"></span>
                                        <template x-if="selected?.id === option.id">
                                            <span class="absolute inset-y-0 right-0 flex items-center pr-4">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </template>
                                    </div>
                                </template>

                                <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                    Tidak ada mata pelajaran yang ditemukan
                                </div>
                            </div>
                        </div>
                        @error('subjectClassId')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                            <input wire:model="startTime" type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('startTime')
                                <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                            <input wire:model="endTime" type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('endTime')
                                <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Jumlah JP</label>
                        <input wire:model="jamPelajaran" type="number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            min="1">
                        @error('jamPelajaran')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Template Judul Pertemuan</label>
                        <input wire:model="sessionTitleTemplate" type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Gunakan %subject% untuk nama mapel dan %date% untuk
                            tanggal pertemuan</p>
                        @error('sessionTitleTemplate')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="button" @click="showAddModal = false"
                            class="mr-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" @click="showAddModal = false"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div x-cloak x-show="showEditModal" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="showEditModal = false" x-on:click.self="showEditModal = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true">

        <div x-show="showEditModal" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">

            <div class="bg-blue-50 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Jadwal Otomatis</h3>
                <p class="mt-1 text-sm text-gray-500">Edit jadwal untuk hari {{ $dayName }}</p>
            </div>

            <div class="px-6 py-4">
                <form wire:submit="updateScheduleDetail">
                    <div class="mb-4" x-data="{
                        open: false,
                        search: '',
                        selected: null,
                    
                        get filteredOptions() {
                            return this.search === '' ?
                                @js($subjectClasses) :
                                @js($subjectClasses).filter(option =>
                                    option.class_name.toLowerCase().includes(this.search.toLowerCase()) ||
                                    option.classes.name.toLowerCase().includes(this.search.toLowerCase()) ||
                                    option.user.name.toLowerCase().includes(this.search.toLowerCase())
                                )
                        },
                    
                        init() {
                            // Get the current editSubjectClassId from wire:model
                            const currentId = @entangle('editSubjectClassId');
                            // Find the matching subject class
                            this.selected = @js($subjectClasses).find(option => option.id === currentId);
                        }
                    }" x-init="init()">
                        <label class="block text-sm font-medium text-gray-700">Mata Pelajaran</label>
                        <div class="relative mt-1">
                            <button type="button" @click="open = !open"
                                class="relative w-full cursor-default rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-left shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:text-sm">
                                <span class="block truncate">
                                    <template x-if="selected">
                                        <span
                                            x-text="selected.class_name + ' - ' + selected.classes.name + ' (' + selected.user.name + ')'"></span>
                                    </template>
                                    <template x-if="!selected">
                                        <span class="text-gray-500">-- Pilih Mata Pelajaran --</span>
                                    </template>
                                </span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="none"
                                        stroke="currentColor">
                                        <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </button>

                            <div x-show="open" @click.away="open = false"
                                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                <div class="sticky top-0 z-10 bg-white px-2 py-2">
                                    <input type="search" x-model="search"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Cari mata pelajaran...">
                                </div>

                                <template x-for="option in filteredOptions" :key="option.id">
                                    <div @click="selected = option; open = false; $wire.editSubjectClassId = option.id"
                                        :class="{
                                            'bg-blue-600 text-white': selected?.id === option
                                                .id,
                                            'text-gray-900': selected?.id !== option.id
                                        }"
                                        class="relative cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-blue-600 hover:text-white">
                                        <span
                                            x-text="option.class_name + ' - ' + option.classes.name + ' (' + option.user.name + ')'"></span>
                                        <template x-if="selected?.id === option.id">
                                            <span class="absolute inset-y-0 right-0 flex items-center pr-4">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </template>
                                    </div>
                                </template>

                                <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                    Tidak ada mata pelajaran yang ditemukan
                                </div>
                            </div>
                        </div>
                        @error('editSubjectClassId')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                            <input wire:model="editStartTime" type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('editStartTime')
                                <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                            <input wire:model="editEndTime" type="time"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('editEndTime')
                                <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Jumlah JP</label>
                        <input wire:model="editJamPelajaran" type="number"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            min="1">
                        @error('editJamPelajaran')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Template Judul Pertemuan</label>
                        <input wire:model="editSessionTitleTemplate" type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Gunakan %subject% untuk nama mapel dan %date% untuk
                            tanggal pertemuan</p>
                        @error('editSessionTitleTemplate')
                            <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="button" wire:click="cancelEdit" @click="showEditModal = false"
                            class="mr-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" @click="showEditModal = false"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-cloak x-show="confirmingDelete" x-transition.opacity.duration.200ms
        x-on:keydown.esc.window="confirmingDelete = false" x-on:click.self="confirmingDelete = false"
        class="fixed inset-0 z-50 flex w-full items-center justify-center bg-black/50 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true">

        <div x-show="confirmingDelete" x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">

            <div class="px-6 py-6">
                <div class="flex justify-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium text-gray-900">Hapus Jadwal</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Apakah Anda yakin ingin menghapus jadwal ini? Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex justify-center space-x-3">
                    <button @click="confirmingDelete = false" type="button"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Batal
                    </button>
                    <button @click="$wire.emit('delete-confirmed', detailToDelete); confirmingDelete = false;"
                        type="button"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
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
            <div
                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-500">
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                </svg>
            </div>
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
