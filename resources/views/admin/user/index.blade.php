<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-inter text-xl font-medium leading-tight text-gray-800">
                {{ __('Pengguna') }}
            </h2>
            <button wire:navigate href="{{ route('create.user') }}"
                class="flex flex-row items-center gap-2 rounded-lg border-t-2 border-t-white bg-gray-800 px-6 py-2 text-white hover:bg-gray-700 hover:shadow-2xl">Tambah
                Pengguna <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span></button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div>
                <p class="mb-4 font-inter text-xl font-medium">Overview</p>
                <livewire:admin.users-overview />
            </div>
            <div class="mt-8">
                <p class="mb-4 font-inter text-xl font-medium">Kelola Pengguna</p>
                <livewire:admin.users-table />
            </div>

        </div>
    </div>
</x-app-layout>
