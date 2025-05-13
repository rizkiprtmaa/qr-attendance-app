<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-row items-center justify-between">
            <div>
                <h2 class="font-inter text-3xl font-semibold leading-tight text-slate-900">
                    {{ __('Pengguna') }}
                </h2>
                <div class="mt-2 flex items-center text-sm text-gray-500">

                    <span>Kelola data user, guru, dan siswa.</span>
                </div>
            </div>
            <a href="{{ route('create.user') }}" wire:navigate
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mr-2 h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                </svg>
                Tambah Pengguna
            </a>
        </div>
    </x-slot>

    <!-- Tab Navigation -->
    <div class="mx-auto max-w-7xl">
        <livewire:admin.user-tabs :active-tab="request()->routeIs('teachers')
            ? 'teacher'
            : (request()->routeIs('students')
                ? 'student'
                : 'overview')" />
    </div>

    <div class="py-6">
        <div class="mx-auto max-w-7xl">
            @if (request()->routeIs('users') && !request()->routeIs('teachers') && !request()->routeIs('students'))
                <div class="mb-8">
                    <div class="mb-8 flex flex-row items-center justify-between">
                        <p class="mb-4 font-inter text-xl font-medium text-gray-900">Overview</p>
                        <a href="{{ route('create.user') }}" wire:navigate
                            class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="mr-2 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                            </svg>
                            Tambah Pengguna
                        </a>
                    </div>
                    <livewire:admin.users-overview />
                </div>
                <div>
                    <p class="mb-4 font-inter text-xl font-medium text-gray-900">Semua Pengguna</p>
                    <livewire:admin.users-table />
                </div>
            @elseif(request()->routeIs('teachers'))
                <div>
                    <p class="mb-4 font-inter text-xl font-medium text-gray-900">Daftar Guru</p>
                    <livewire:admin.teachers-table />
                </div>
            @elseif(request()->routeIs('students'))
                <div>
                    <p class="mb-4 font-inter text-xl font-medium text-gray-900">Daftar Siswa</p>
                    <livewire:admin.students-table />
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
