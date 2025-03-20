<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3">
            <h2 class="font-inter text-xl font-medium leading-tight text-gray-800">
                {{ __('Detail Kelas') }}
            </h2>
            <p class="font-inter text-sm text-gray-600">Buat sesi pertemuan untuk mengelola presensi kelas</p>

        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <livewire:teacher.detail-subject-class />

        </div>
    </div>
</x-app-layout>
