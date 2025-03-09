<?php

use Livewire\Volt\Component;
use App\Models\Teacher;

new class extends Component {
    public function render(): mixed
    {
        $this->teachers = Teacher::with('user')->orderBy('created_at', 'desc')->paginate(5);
        return view('livewire.admin.teachers-table', [
            'teachers' => $this->teachers,
        ]);
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div class="relative mb-4 flex w-full max-w-xs">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari guru..."
                class="block w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 transform text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-left text-sm text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="bg-blue-500 text-xs uppercase text-white">
                <tr>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('name')">
                        Nama
                    </th>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('email')">
                        NUPTK
                    </th>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('status')">
                        Nomor Handphone
                    </th>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('roles.name')">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($teachers as $teacher)
                    <tr wire:key="teacher-{{ $teacher->id }}"
                        class="border-b border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600">
                        <th scope="row"
                            class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $teacher->user->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $teacher->nuptk }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $teacher->phone_number }}
                        </td>
                        <td class="px-6 py-4">
                            Aktif
                        </td>
                        <td class="gap-4 px-6 py-4">
                            <a href="{{ route('user.edit', $teacher->user->id) }}"
                                class="me-4 font-medium text-blue-600 hover:underline">Edit</a>
                            <button class="font-medium text-red-600 hover:underline"
                                x-on:click="$wire.delete('{{ $teacher->id }}')">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
