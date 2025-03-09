<?php

use Livewire\Volt\Component;
use App\Models\Student;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\Attributes\On;

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

    public function downloadQrCode($userId)
    {
        $user = User::findOrFail($userId);

        if (!$user->qr_code_path) {
            return redirect()->back()->with('error', 'QR Code tidak tersedia');
        }

        return response()->download(storage_path('app/public/' . $user->qr_code_path));
    }

    public function showQrCode($userId)
    {
        $user = User::findOrFail($userId);

        if (!$user->qr_code_path) {
            return redirect()->back()->with('error', 'QR Code tidak tersedia');
        }

        return $user->qr_code_path;
    }

    #[On('user-updated')]
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

<div x-data="{
    showQrModal: false,
    currentQrCode: null,
    currentUserId: null,
    openQrModal(qrCodePath, userId) {
        this.currentQrCode = qrCodePath;
        this.currentUserId = userId;
        this.showQrModal = true;
    }
}">

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
            <thead class="bg-blue-500 text-xs uppercase text-white">
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
                        Aksi
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
                        <td class="flex items-center gap-4 px-6 py-4">

                            <button title="Hapus" class="font-medium text-red-600 hover:underline"
                                x-on:click="$wire.delete('{{ $user->id }}')"><svg xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                            <a href="{{ route('user.edit', $user->id) }}"
                                class="font-medium text-blue-600 hover:underline"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-5 text-green-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </a>
                            <button class="font-medium text-blue-600 hover:underline"
                                @click="openQrModal('{{ $user->qr_code_path }}', '{{ $user->id }}')"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
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
        {{ $users->links() }}
    </div>

    <!-- QR Modal -->
    <div x-show="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="w-full max-w-md rounded-lg bg-white p-6">
            <div class="mb-4 flex items-center justify-center">
                <h2 class="font-inter text-xl font-medium">QR Code Presensi</h2>

            </div>

            <div class="mb-4 flex justify-center">
                <template x-if="currentQrCode">
                    <img :src="'/storage/' + currentQrCode" alt="QR Code" class="h-auto max-w-full">
                </template>
                <template x-if="!currentQrCode">
                    <p>QR Code tidak tersedia</p>
                </template>
            </div>

            <div class="mt-3 flex justify-center space-x-4">
                <x-primary-button color="green"><a x-show="currentUserId"
                        :href="`/users/${currentUserId}/download-qr`">
                        Download QR
                    </a></x-primary-button>
                <x-primary-button @click="showQrModal = false" color="gray">Tutup</x-primary-button>
            </div>
        </div>
    </div>



</div>
