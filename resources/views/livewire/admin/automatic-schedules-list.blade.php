<?php

use Livewire\Volt\Component;
use App\Models\AutomaticSchedule;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $days = [
        'Senin' => 'Monday',
        'Selasa' => 'Tuesday',
        'Rabu' => 'Wednesday',
        'Kamis' => 'Thursday',
        'Jumat' => 'Friday',
        'Sabtu' => 'Saturday',
    ];

    public function render(): mixed
    {
        // Fetch schedule data
        $automaticSchedules = AutomaticSchedule::with('scheduleDetails')->get();

        // Create schedule summary
        $scheduleSummary = [];

        foreach ($this->days as $dayInd => $dayEng) {
            $schedule = $automaticSchedules->where('day_of_week', $dayInd)->first();

            $scheduleSummary[] = [
                'day_ind' => $dayInd,
                'day_eng' => $dayEng,
                'schedule' => $schedule,
                'total_classes' => $schedule ? $schedule->scheduleDetails->count() : 0,
                'is_active' => $schedule ? $schedule->is_active : false,
            ];
        }

        return view('livewire.admin.automatic-schedules-list', [
            'scheduleSummary' => $scheduleSummary,
        ]);
    }

    public function toggleScheduleStatus($day)
    {
        $schedule = AutomaticSchedule::where('day_of_week', $day)->first();

        if ($schedule) {
            $schedule->update([
                'is_active' => !$schedule->is_active,
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Status jadwal berhasil diubah!',
            ]);
        }
    }
}; ?>

<div>
    <div class="mx-auto mt-12 max-w-7xl md:mt-3">
        <!-- Header -->
        <div class="mb-8 hidden flex-col gap-2 md:flex">
            <h1 class="font-inter text-3xl font-semibold text-gray-900">Penjadwalan Otomatis</h1>
            <p class="font-inter text-sm text-gray-500">Pilih hari untuk mengaktifkan penjadwalan otomatis.</p>
        </div>

        <!-- Day Cards -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($scheduleSummary as $item)
                <div class="overflow-hidden rounded-lg border bg-white shadow">
                    <div class="border-b px-4 py-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-inter text-lg font-medium text-gray-900">{{ $item['day_ind'] }}</h3>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" class="peer sr-only"
                                    wire:change="toggleScheduleStatus('{{ $item['day_ind'] }}')"
                                    {{ $item['is_active'] ? 'checked' : '' }}>
                                <div
                                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300">
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                                <span class="font-inter text-sm text-gray-600">{{ $item['total_classes'] }} Mata
                                    Pelajaran</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.automatic-schedule.detail', ['day' => $item['day_ind']]) }}"
                            wire:navigate
                            class="mt-2 inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 font-inter text-sm font-medium text-white hover:bg-blue-700">
                            Kelola Jadwal
                        </a>
                    </div>
                </div>
            @endforeach
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
