<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-inter text-3xl font-semibold leading-tight text-gray-800">
                {{ __('Settings') }}
            </h2>

        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl">
            <livewire:admin.school-years />

        </div>
    </div>
</x-app-layout>
