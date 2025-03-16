<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-inter text-xl font-medium leading-tight text-gray-800">
                {{ __('Detail Pengguna') }}
            </h2>

        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <livewire:user.user-detail />

        </div>
    </div>
</x-app-layout>
