<?php

use Livewire\Volt\Component;
use App\Models\SubjectClassSession;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

new class extends Component {
    public $ongoingSessions;
    public $currentTeacherPresence = [];
    public $refreshInterval = 60000; // refresh data setiap 1 menit

    public function mount()
    {
        $this->loadOngoingSessions();
    }

    public function loadOngoingSessions()
    {
        $now = Carbon::now()->timezone('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        $cacheKey = 'principal_ongoing_sessions_' . $today . '_' . substr($currentTime, 0, 5); // Cache per 5 menit

        // Ambil semua sesi yang sedang berlangsung
        $this->ongoingSessions = Cache::remember($cacheKey, 300, function () use ($today, $currentTime) {
            $sessions = SubjectClassSession::with(['subjectClass.classes.major', 'subjectClass.teacher.user'])
                ->whereDate('class_date', $today)
                ->where('start_time', '<=', $currentTime)
                ->where('end_time', '>=', $currentTime)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'subject' => $session->subject_title,
                        'class' => $session->subjectClass->classes->name ?? 'Tidak ada kelas',
                        'major' => $session->subjectClass->classes->major->name ?? 'Tidak ada jurusan',
                        'teacher_id' => $session->subjectClass->user->id ?? null,
                        'teacher_name' => $session->subjectClass->user->name ?? 'Tidak ada guru',
                        'start_time' => Carbon::parse($session->start_time)->format('H:i'),
                        'end_time' => Carbon::parse($session->end_time)->format('H:i'),
                        'room' => $session->room ?? 'Tidak ditentukan',
                        'duration' => Carbon::parse($session->start_time)->diffInMinutes(Carbon::parse($session->end_time)) . ' menit',
                    ];
                });

            return $sessions;
        });

        // Cek kehadiran guru untuk setiap sesi
        $this->checkTeacherPresence();
    }

    public function checkTeacherPresence()
    {
        $today = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d');

        // Reset array kehadiran guru
        $this->currentTeacherPresence = [];

        // Loop melalui semua sesi dan cek kehadiran guru
        foreach ($this->ongoingSessions as $session) {
            if ($session['teacher_id']) {
                // Cek kehadiran guru hari ini
                $teacherAttendance = \App\Models\Attendance::where('user_id', $session['teacher_id'])->whereDate('attendance_date', $today)->where('type', 'datang')->first();

                $this->currentTeacherPresence[$session['teacher_id']] = [
                    'is_present' => $teacherAttendance ? true : false,
                    'status' => $teacherAttendance ? $teacherAttendance->status : 'tidak_hadir',
                    'check_in_time' => $teacherAttendance ? $teacherAttendance->check_in_time : null,
                ];
            }
        }
    }

    // Refresh data secara manual
    public function refresh()
    {
        $this->loadOngoingSessions();
    }

    // Mendapatkan total sesi yang sedang berlangsung
    public function getTotalOngoingSessionsProperty()
    {
        return count($this->ongoingSessions);
    }

    // Mendapatkan total guru yang hadir
    public function getTotalPresentTeachersProperty()
    {
        return collect($this->currentTeacherPresence)
            ->filter(function ($attendance) {
                return $attendance['is_present'] === true;
            })
            ->count();
    }

    public function render(): mixed
    {
        return view('livewire.principle.live-classes');
    }
}; ?>

<div class="mt-12 overflow-hidden rounded-lg bg-white shadow md:mt-0" x-data="{ isReloading: false }" x-init="setInterval(() => {
    isReloading = true;
    $wire.refresh().then(() => { isReloading = false; })
}, $wire.refreshInterval)">
    <div class="border-b border-gray-200 bg-gradient-to-r from-green-50 to-teal-50 px-6 py-4">
        <div class="flex flex-col items-start justify-between gap-3 md:flex-row md:items-center">
            <h3 class="flex items-center space-x-2 text-lg font-medium text-gray-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                        clip-rule="evenodd" />
                </svg>
                <span>Kelas Sedang Berlangsung</span>

                <!-- Indikator refresh -->
                <span x-show="isReloading"
                    class="ml-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-green-500 border-t-transparent"></span>
            </h3>

            <div class="flex space-x-2 text-sm">
                <div class="rounded-full bg-green-100 px-3 py-1 text-green-700">
                    <span class="font-semibold">{{ $this->totalOngoingSessions }}</span> Kelas
                </div>
                <div class="rounded-full bg-blue-100 px-3 py-1 text-blue-700">
                    <span class="font-semibold">{{ $this->totalPresentTeachers }}</span> Guru Hadir
                </div>
                <button wire:click="refresh" class="rounded-full bg-gray-100 p-1 text-gray-600 hover:bg-gray-200"
                    title="Refresh Data">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="hidden md:block">
        @if (count($ongoingSessions) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Mata
                                Pelajaran</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Kelas
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Guru
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Jam
                            </th>

                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($ongoingSessions as $session)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $session['subject'] }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-900">{{ $session['class'] }}</span>
                                        <span class="text-gray-500">- {{ $session['major'] }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $session['teacher_name'] }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <span class="font-medium">{{ $session['start_time'] }} -
                                            {{ $session['end_time'] }}</span>
                                        <span class="block text-xs text-gray-500">{{ $session['duration'] }}</span>
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada kelas yang sedang berlangsung</h3>
                    <p class="mt-1 text-sm text-gray-500">Tidak ada jadwal kelas yang aktif saat ini.</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Mobile View -->
    <div class="block space-y-4 p-4 md:hidden">
        @forelse($ongoingSessions as $session)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="font-medium text-gray-900">{{ $session['subject'] }}</h3>


                </div>

                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <p class="text-xs text-gray-500">Kelas</p>
                        <p class="font-medium">{{ $session['class'] }} - {{ $session['major'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Guru</p>
                        <p class="font-medium">{{ $session['teacher_name'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Jadwal</p>
                        <p class="font-medium">{{ $session['start_time'] }} - {{ $session['end_time'] }}</p>
                    </div>

                </div>

                @if (isset($currentTeacherPresence[$session['teacher_id']]) &&
                        $currentTeacherPresence[$session['teacher_id']]['is_present']
                )
                    <div class="mt-2 text-xs text-gray-500">
                        Guru check-in pada:
                        {{ $currentTeacherPresence[$session['teacher_id']]['check_in_time'] }}
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                <svg class="mx-auto h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada kelas berlangsung</h3>
                <p class="mt-1 text-xs text-gray-500">Tidak ada jadwal kelas yang aktif saat ini.</p>
            </div>
        @endforelse
    </div>

    <!-- Footer dengan timestamp -->
    <div class="border-t border-gray-200 px-6 py-2">
        <div class="flex items-center justify-between text-xs text-gray-500">
            <span>Terakhir diperbaharui: {{ Carbon::now()->timezone('Asia/Jakarta')->format('H:i:s') }}</span>
            <span>Data akan refresh otomatis setiap 1 menit</span>
        </div>
    </div>
    </.div>
