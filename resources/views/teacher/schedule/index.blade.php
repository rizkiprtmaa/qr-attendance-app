<!-- resources/views/teacher/schedule.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">

            <h2 class="font-inter text-3xl font-semibold leading-tight text-gray-800">
                {{ __('Jadwal KBM') }}
            </h2>
            <p class="font-inter text-sm text-gray-600">Kelola dan sesuaikan jadwal KBM.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl">
            <livewire:teacher.teacher-schedule-manager />
        </div>
    </div>
</x-app-layout>
