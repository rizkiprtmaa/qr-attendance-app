<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-row items-center justify-between">
            <div class="flex flex-col gap-2">
                <h2 class="font-inter text-xl font-medium leading-tight text-gray-800">
                    {{ __('Presensi Kelas') }}
                </h2>
                <p class="font-inter text-sm text-gray-600">Buat dan atur presensi kelas dari sekarang</p>
            </div>
            <div>
                <p>{{ \Carbon\Carbon::now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d M Y') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div
        class="mx-auto mt-4 max-w-7xl border-b border-gray-200 px-5 text-center text-sm font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
        <ul class="-mb-px flex flex-wrap">
            <li class="me-2">
                <a href="{{ route('users') }}"
                    class="active inline-block rounded-t-lg border-b-2 border-blue-600 border-transparent p-4 text-blue-600">Overview</a>
            </li>
            <li class="me-2">
                <a href="{{ route('teachers') }}" wire:navigate
                    class="inline-block rounded-t-lg p-4 hover:border-b-2 hover:border-gray-300 hover:text-gray-600"
                    aria-current="page">Histori</a>
            </li>

        </ul>
    </div>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <livewire:teacher.create-attendances-class />

        </div>
    </div>
</x-app-layout>
