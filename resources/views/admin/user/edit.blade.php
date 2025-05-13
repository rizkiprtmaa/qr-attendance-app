<x-app-layout>
    <x-slot name="header">
        <h2 class="font-inter text-3xl font-semibold leading-tight text-gray-800">
            {{ __('Edit Pengguna') }}
        </h2>

    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl">
            <livewire:admin.edit-user />
        </div>
    </div>
</x-app-layout>
