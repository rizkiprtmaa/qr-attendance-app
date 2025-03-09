<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-inter text-2xl font-medium leading-tight text-slate-900">
                    {{ __('Pengguna') }}
                </h2>
                <nav class="mt-3 flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                        <li class="inline-flex items-center">
                            <a href="{{ route('users') }}" wire:navigate
                                class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="true" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="me-1 size-4 fill-gray-600">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                                Users
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="mx-1 h-3 w-3 text-gray-400 rtl:rotate-180" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <a wire:navigate
                                    class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Guru</a>
                            </div>
                        </li>
                    </ol>
                </nav>

            </div>

        </div>
    </x-slot>



    <div
        class="mx-auto mt-4 max-w-7xl border-b border-gray-200 text-center text-sm font-medium text-gray-500 sm:px-6 lg:px-8 dark:border-gray-700 dark:text-gray-400">
        <ul class="-mb-px flex flex-wrap">
            <li class="me-2">
                <a href="{{ route('users') }}" wire:navigate
                    class="inline-block rounded-t-lg p-4 hover:border-b-2 hover:border-gray-300 hover:text-gray-600">Overview</a>
            </li>
            <li class="me-2">
                <a href="#"
                    class="active inline-block rounded-t-lg border-b-2 border-blue-500 border-transparent p-4 text-blue-600"
                    aria-current="page">Guru</a>
            </li>
            <li class="me-2">
                <a href="#"
                    class="inline-block rounded-t-lg border-b-2 border-transparent p-4 hover:border-gray-300 hover:text-gray-600">Siswa</a>
            </li>

        </ul>
    </div>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div>
                <p class="mb-4 font-inter text-xl font-medium">Kelola Guru</p>
                <livewire:admin.teachers-table />
            </div>

        </div>
    </div>
</x-app-layout>
