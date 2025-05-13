<!-- resources/views/admin/teacher-list.blade.php -->
<x-app-layout>
    <x-slot name="header">

        <div class="flex flex-col gap-2">
            <h2 class="font-inter text-3xl font-semibold leading-tight text-slate-900">
                {{ __('Daftar Guru Pengajar') }}
            </h2>
            <p class="font-inter text-xs text-gray-600 md:text-sm">Pilih guru untuk mengelola mata pelajaran dan
                pertemuan.</p>
        </div>
    </x-slot>

    <div>
        @livewire('admin.teacher-list')
    </div>
</x-app-layout>
