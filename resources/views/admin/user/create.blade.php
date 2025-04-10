<x-app-layout>
    <x-slot name="header">
        <h2 class="text-3xl font-semibold leading-tight text-gray-800">
            {{ __('Buat Pengguna') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">Buat akun pengguna baru.</p>

    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl">
            <livewire:admin.create-user />
        </div>
    </div>
</x-app-layout>
