<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-inter text-2xl font-medium leading-tight text-slate-900">
                    {{ __('Pengguna') }}
                </h2>
                <flux:breadcrumbs class="mt-2">
                    <flux:breadcrumbs.item href="{{ route('users') }}" icon="users" />
                    <flux:breadcrumbs.item href="{{ route('teachers') }}">Guru</flux:breadcrumbs.item>
                </flux:breadcrumbs>

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
                    class="active inline-block rounded-t-lg border-b-2 border-blue-600 border-transparent p-4 text-blue-600"
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
