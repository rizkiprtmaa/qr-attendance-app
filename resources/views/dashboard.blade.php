<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-row justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Dashboard') }}
            </h2>

        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">


            @role('admin')
                <livewire:admin.dashboard />
            @endrole
            @role('teacher')
                <livewire:teacher.dashboard />
            @endrole




        </div>
    </div>
</x-app-layout>
