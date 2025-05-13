<?php
// resources/views/livewire/admin/teacher-list.php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Teacher;

new class extends Component {
    use WithPagination;

    public $search = '';

    public function getTeachersProperty()
    {
        return User::role('teacher')
            ->whereHas('teacher', function ($query) {
                $query->where('is_karyawan', 0);
            })
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'kepala_sekolah');
            })
            ->when($this->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function render(): mixed
    {
        return view('livewire.admin.teacher-list', [
            'teachers' => $this->teachers,
        ]);
    }
}; ?>

<!-- resources/views/livewire/admin/teacher-list.blade.php -->
<div class="mt-12 py-6 md:mt-0">
    <div class="mx-auto max-w-7xl">

        <!-- Search Box -->
        <div class="mb-6">
            <div class="relative mt-1 rounded-md shadow-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="block w-full rounded-md border-gray-300 pl-10 focus:border-blue-500 focus:ring-blue-500 sm:text-sm md:w-96"
                    placeholder="Cari nama guru...">
            </div>
        </div>

        <!-- Teachers List -->
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($teachers as $teacher)
                    <li>
                        <div class="flex items-center px-4 py-4 sm:px-6">
                            <div class="flex min-w-0 flex-1 items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        {{ substr($teacher->name, 0, 1) }}
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 px-4">
                                    <div>
                                        <p class="truncate text-sm font-medium text-gray-900">{{ $teacher->name }}</p>
                                        <p class="mt-1 truncate text-sm text-gray-500">{{ $teacher->email }}</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('admin.teacher.subjects', $teacher->id) }}" wire:navigate
                                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:text-sm">
                                    Lihat Mapel
                                    <svg xmlns="http://www.w3.org/2000/svg" class="-mr-1 ml-2 h-4 w-4"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada guru</h3>
                        <p class="mt-1 text-sm text-gray-500">Tidak ada guru yang ditemukan dengan kriteria tersebut.
                        </p>
                    </li>
                @endforelse
            </ul>

            <!-- Pagination -->
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                {{ $teachers->links() }}
            </div>
        </div>
    </div>
</div>
