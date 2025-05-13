<?php

use Livewire\Volt\Component;
use App\Models\TeacherSchedule;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    public $schedules = [];

    public $dayOfWeek;
    public $startTime;
    public $endTime;
    public $notes;
    public $scheduleId;

    protected $rules = [
        'dayOfWeek' => 'required',
        'startTime' => 'required',
        'endTime' => 'required|after:startTime',
    ];

    public function mount()
    {
        $this->loadSchedules();
    }

    public function loadSchedules()
    {
        $userId = Auth::id();

        // Periksa apakah jadwal sudah diisi untuk setiap hari
        foreach ($this->days as $day) {
            $schedule = TeacherSchedule::where('user_id', $userId)->where('day_of_week', $day)->first();

            // Jika belum ada jadwal, buat default (07:30 - 13:50)
            if (!$schedule) {
                TeacherSchedule::create([
                    'user_id' => $userId,
                    'day_of_week' => $day,
                    'start_time' => '07:30',
                    'end_time' => '13:50',
                    'notes' => 'Jadwal default',
                ]);
            }
        }

        $this->schedules = TeacherSchedule::where('user_id', $userId)->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")->get();
    }

    public function editSchedule($id)
    {
        $schedule = TeacherSchedule::find($id);

        if ($schedule && $schedule->user_id == Auth::id()) {
            $this->scheduleId = $schedule->id;
            $this->dayOfWeek = $schedule->day_of_week;
            $this->startTime = $schedule->start_time;
            $this->endTime = $schedule->end_time;
            $this->notes = $schedule->notes;
        }
    }

    public function saveSchedule()
    {
        $this->validate();

        try {
            $schedule = TeacherSchedule::find($this->scheduleId);

            if ($schedule && $schedule->user_id == Auth::id()) {
                $schedule->update([
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                    'notes' => $this->notes,
                ]);

                $this->reset(['dayOfWeek', 'startTime', 'endTime', 'notes', 'scheduleId']);
                $this->loadSchedules();

                session()->flash('message', 'Jadwal berhasil disimpan!');
            } else {
                session()->flash('error', 'Anda tidak memiliki izin untuk mengedit jadwal ini');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal menyimpan jadwal: ' . $e->getMessage());
        }
    }

    public function render(): mixed
    {
        return view('livewire.teacher.teacher-schedule-manager');
    }
}; ?>

<!-- resources/views/livewire/teacher/teacher-schedule-manager.blade.php -->
<div>
    <div class="mx-auto max-w-7xl">


        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-4 mt-2 rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 mt-2 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-6">
            @if ($scheduleId)
                <div class="mb-6 rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Edit Jadwal {{ $dayOfWeek }}</h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="mb-4">
                            <label for="startTime" class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                            <input wire:model="startTime" type="time" id="startTime"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('startTime')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="endTime" class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                            <input wire:model="endTime" type="time" id="endTime"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            @error('endTime')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea wire:model="notes" id="notes" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" wire:click="$set('scheduleId', null)"
                            class="mr-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Batal
                        </button>
                        <button type="button" wire:click="saveSchedule"
                            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Simpan Jadwal
                        </button>
                    </div>
                </div>
            @endif

            <!-- Daftar Jadwal -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Daftar Jadwal KBM</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Hari</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Jam</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Catatan</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($schedules as $schedule)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $schedule->day_of_week }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $schedule->notes ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <button wire:click="editSchedule({{ $schedule->id }})"
                                            class="text-blue-600 hover:text-blue-900">Edit</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
