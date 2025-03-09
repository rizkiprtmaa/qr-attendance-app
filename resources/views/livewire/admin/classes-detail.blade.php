<?php

use Livewire\Volt\Component;
use App\Models\Classes;
use App\Models\Student;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $perPage = 10;
    public $classId;
    public $search = '';
    public $sortBy = 'name';
    public $sortDirection = 'ASC';

    public function mount()
    {
        $this->classId = request()->route('id');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setSortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
        }

        $this->sortBy = $column;
    }

    public function render(): mixed
    {
        $studentsQuery = Student::where('classes_id', $this->classId)
            ->when($this->search, function ($query) {
                return $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')->orWhere('nisn', 'like', '%' . $this->search . '%');
                });
            })
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select('students.*', 'users.name as user_name')
            ->orderBy($this->sortBy, $this->sortDirection);

        return view('livewire.admin.classes-detail', [
            'class' => Classes::with('teacher', 'major', 'school_year')->findOrFail($this->classId),
            'students' => $studentsQuery->with('user', 'classes')->paginate($this->perPage),
        ]);
    }
}; ?>

<div class="flex flex-col gap-4">
    <div class="flex flex-row items-center justify-between">

        <p class="flex items-center gap-3 font-inter text-2xl font-medium">{{ $class->name }} -
            {{ $class->major->name }}
            <span
                class="rounded-full bg-blue-500 px-4 py-2 text-xs font-thin text-white">{{ $class->major->code }}</span>
        </p>
        <x-primary-button type="button" color="blue">Unduh Laporan</x-primary-button>
    </div>
    <div class="mt-1 flex flex-row gap-2">
        <p class="flex flex-row items-center gap-2 font-inter text-sm text-gray-600"><svg height="512" class="size-7"
                viewBox="0 0 25 25" width="512" xmlns="http://www.w3.org/2000/svg" id="fi_3666632">
                <g id="Teacher" fill="#2b3344">
                    <path
                        d="m6.5 15.125a2.563 2.563 0 1 1 2.563-2.562 2.565 2.565 0 0 1 -2.563 2.562zm0-4.125a1.563 1.563 0 1 0 1.563 1.563 1.564 1.564 0 0 0 -1.563-1.563z">
                    </path>
                    <path
                        d="m9.5 21a.5.5 0 0 1 -.5-.5v-1a2.5 2.5 0 0 0 -5 0v1a.5.5 0 0 1 -1 0v-1a3.5 3.5 0 0 1 7 0v1a.5.5 0 0 1 -.5.5z">
                    </path>
                    <path d="m9.5 20.36a.5.5 0 0 1 -.383-.821l7-8.36a.5.5 0 0 1 .766.642l-7 8.36a.5.5 0 0 1 -.383.179z">
                    </path>
                    <path d="m5.5 9a.5.5 0 0 1 -.5-.5v-4a.5.5 0 0 1 1 0v4a.5.5 0 0 1 -.5.5z"></path>
                    <path d="m21.5 5h-17a.5.5 0 0 1 0-1h17a.5.5 0 0 1 0 1z"></path>
                    <path d="m20.5 16a.5.5 0 0 1 -.5-.5v-11a.5.5 0 0 1 1 0v11a.5.5 0 0 1 -.5.5z"></path>
                    <path d="m21.5 16h-6a.5.5 0 0 1 0-1h6a.5.5 0 0 1 0 1z"></path>
                    <path d="m17.5 8h-6a.5.5 0 0 1 0-1h6a.5.5 0 0 1 0 1z"></path>
                    <path d="m17.5 10h-8a.5.5 0 0 1 0-1h8a.5.5 0 0 1 0 1z"></path>
                </g>
            </svg>{{ $class->teacher->user->name }}</p>
        <p class="ms-2 flex flex-row items-center gap-2 font-inter text-sm text-gray-600"><svg
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
            </svg>{{ $class->school_year->name }}</p>
        </p>
        <p class="ms-2 flex flex-row items-center gap-2 font-inter text-sm text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
            </svg>
            {{ $class->student->count() }} Siswa
        </p>
    </div>


    <div class="mt-4">
        <p class="font-inter text-lg font-medium">Data Siswa</p>
    </div>

    <div class="flex items-center justify-between">
        <div class="relative mb-2 flex w-full max-w-xs">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari siswa..."
                class="block w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-slate-900 focus:ring focus:ring-slate-300 focus:ring-opacity-50" />
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 transform text-gray-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        </div>
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-left text-sm text-gray-500 rtl:text-right dark:text-gray-400">
            <thead class="bg-blue-500 text-xs uppercase text-white dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('name')">
                        Nama
                    </th>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('nisn')">
                        NISN
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Kelas
                    </th>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('parent_number')">
                        Nomor Handphone
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Persentasi Kehadiran
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Ranking Kehadiran
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>


            <tbody>
                @foreach ($students as $student)
                    <tr wire:key="student-{{ $student->id }}"
                        class="border-b border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600">
                        <th scope="row"
                            class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $student->user->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $student->nisn }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $student->classes->name }}
                        </td>
                        <td class="px-6 py-4">
                            {{ $student->parent_number }}
                        </td>
                        <td class="px-6 py-4">
                            100%
                        </td>
                        <td class="px-6 py-4">
                            12
                        </td>
                        <td class="flex items-center gap-4 px-6 py-4 text-right">
                            <a href="#" class="me-1 font-medium text-green-600 hover:underline"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </a>
                            <button class="font-medium text-slate-900"
                                x-on:click="$wire.delete('{{ $student->id }}')"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                </svg>

                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>


    <div class="mt-5">
        <div class="flex items-center gap-4">
            <select wire:model.live="perPage" class="rounded-lg border-gray-300 text-sm">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
            </select>
            <p class="text-sm text-gray-600">Per Page</p>
        </div>
        {{ $students->links() }}
    </div>

</div>
