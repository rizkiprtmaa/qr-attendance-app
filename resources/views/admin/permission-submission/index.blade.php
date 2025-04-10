<x-app-layout>
    <x-slot name="header">
        <h2 class="font-inter text-3xl font-semibold leading-tight text-gray-800">
            {{ __('Manajemen Perizinan') }}
        </h2>
        <p class="font-inter text-xs text-gray-600 md:text-sm">Kelola izin pengguna.</p>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl">

            <livewire:admin.permission-management />

        </div>
    </div>
</x-app-layout>
