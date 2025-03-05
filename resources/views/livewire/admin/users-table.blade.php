<?php

use Livewire\Volt\Component;
use App\Models\Student;
use App\Models\User;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';

    public $sortBy = 'created_at';
    public $sortDirection = 'DESC';

    public $perPage = 5;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $user = User::with('roles')
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->whereHas('roles', function ($query) {
                $query
                    ->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('roles.name', 'like', '%' . $this->search . '%');
            })
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->select('users.*')
            ->paginate($this->perPage);

        return view('livewire.admin.users-table', [
            'users' => $user,
        ]);
    }

    public function setSortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
        }

        $this->sortBy = $column;
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        // Hapus data terkait di tabel 'student' jika ada
        $student = Student::where('user_id', $id)->first();
        if ($student) {
            $student->delete();
        }

        // Hapus peran (role) yang terkait dengan user
        $role = Spatie\Permission\Models\Role::whereHas('users', function ($query) use ($id) {
            $query->where('model_id', $id);
        })->first();
        if ($role) {
            $role->users()->detach($id);
        }

        // Hapus user
        $user->delete();

        $this->dispatch('delete-user');
    }
}; ?>

<div>

    <div class="flex items-center justify-between">
        <div class="relative mb-4 flex w-full max-w-xs">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari pengguna..."
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
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('name')">
                        Nama
                    </th>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('email')">
                        Email
                    </th>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('status')">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3" wire:click="setSortBy('roles.name')">
                        Peran
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}"
                        class="border-b border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600">
                        <th scope="row"
                            class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $user->name }}
                        </th>
                        <td class="px-6 py-4">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4">
                            Active
                        </td>
                        <td class="px-6 py-4">
                            {{ $user->roles->first()->name }}
                        </td>
                        <td class="gap-4 px-6 py-4 text-right">
                            <a href="#" class="me-4 font-medium text-blue-600 hover:underline">Edit</a>
                            <button class="font-medium text-red-600 hover:underline"
                                x-on:click="$wire.delete('{{ $user->id }}')">Delete</button>
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
        {{ $users->links() }}
    </div>

</div>
