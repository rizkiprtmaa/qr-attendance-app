<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <h2 class="font-inter text-3xl font-semibold leading-tight text-gray-800">
                {{ __('Kelas dan Jurusan') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">Kelola data jurusan dan kelas untuk sekolah.</p>

        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl">
            <livewire:admin.classes-organizer />

        </div>
    </div>
</x-app-layout>
