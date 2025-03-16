<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-row items-center justify-between">
            <div>
                <h2 class="font-inter text-2xl font-medium leading-tight text-slate-900">
                    {{ __('Pengguna') }}
                </h2>
                <flux:breadcrumbs class="mt-2">
                    <flux:breadcrumbs.item href="{{ route('users') }}" icon="users" />

                </flux:breadcrumbs>

            </div>
            <div>
                <x-primary-button color="blue" href="{{ route('create.user') }}" wire:navigate
                    class="flex flex-row items-center justify-center text-xs md:text-sm">Tambah
                    Pengguna</x-primary-button>
            </div>
        </div>
    </x-slot>



    <div
        class="mx-auto mt-4 max-w-7xl border-b border-gray-200 text-center text-sm font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
        <ul class="-mb-px flex flex-wrap">
            <li class="me-2">
                <a href="{{ route('users') }}"
                    class="active inline-block rounded-t-lg border-b-2 border-blue-600 border-transparent p-4 text-blue-600">Overview</a>
            </li>
            <li class="me-2">
                <a href="{{ route('teachers') }}" wire:navigate
                    class="inline-block rounded-t-lg p-4 hover:border-b-2 hover:border-gray-300 hover:text-gray-600"
                    aria-current="page">Guru</a>
            </li>
            <li class="me-2">
                <a href="#"
                    class="inline-block rounded-t-lg border-b-2 border-transparent p-4 hover:border-gray-300 hover:text-gray-600 dark:hover:text-gray-300">Siswa</a>
            </li>

        </ul>
    </div>

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
