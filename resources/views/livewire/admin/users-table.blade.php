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
    showDeleteModal: false,
    currentUserName: null,
    OpenDeleteModal(userId, userName) {
        this.currentUserId = userId;
        this.currentUserName = userName;
        this.showDeleteModal = true;
    },
    currentQrCode: null,
    currentUserId: null,
    showQrModal: false,
    openQrModal(qrCodePath, userId) {
        this.currentQrCode = qrCodePath;
        this.currentUserId = userId;
        this.showQrModal = true;
    },
    init() {
        // Tambahkan listener untuk event user-deleted
        Livewire.on('delete-user', () => {
            this.showDeleteModal = false;
        });
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


    <div
        class="relative max-w-[28rem] overflow-x-auto whitespace-nowrap shadow-md sm:rounded-lg md:max-w-full md:overflow-x-auto">
        <table class="w-full overflow-scroll text-left text-sm text-gray-500 dark:text-gray-400 rtl:text-right">
            <thead class="bg-blue-500 text-xs uppercase text-white">
                <tr>
                    <th scope="col" class="flex flex-row items-center gap-2 px-6 py-3"
                        wire:click="setSortBy('name')">
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
                    <th scope="col" class="flex flex-row items-center justify-center px-6 py-3">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}"
                        class="border-b border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600">
                        <th scope="row"
                            class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white"> <a
                                href="{{ route('user.detail', $user) }}" wire:navigate>
                                {{ $user->name }}</a>
                        </th>
                        <td class="px-6 py-4">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4">
                            Active
                        </td>
                        @if ($user->roles->contains('name', 'student'))
                            <td class="px-6 py-4">
                                Siswa
                            </td>
                        @else
                            <td class="px-6 py-4">
                                Guru
                            </td>
                        @endif
                        <td class="flex flex-row items-center justify-center gap-4 px-6 py-4">


                            <flux:tooltip content="Hapus">
                                <flux:button icon="trash" icon-variant="outline" variant="danger"
                                    @click="OpenDeleteModal({{ $user->id }}, '{{ $user->name }}')" />
                            </flux:tooltip>
                            <flux:tooltip content="Edit">
                                <flux:button icon="pencil-square" icon-variant="outline"
                                    href="{{ route('user.edit', $user->id) }}" wire:navigate />
                            </flux:tooltip>
                            <flux:tooltip variant="primary" content="Lihat QR">
                                <flux:button icon="qr-code" icon-variant="outline"
                                    @click="openQrModal('{{ $user->qr_code_path }}', '{{ $user->id }}')" />
                            </flux:tooltip>
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
    <div x-show="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        x-cloak>
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



    <!-- Modal -->
    <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500 bg-opacity-75"
        x-cloak>
        <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="flex items-center justify-center px-4 text-center sm:block sm:p-0">
                <!-- Modal panel -->
                <div class="inline-block w-full transform items-center overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                    role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <!-- Modal content -->
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <!-- Heroicon name: outline/exclamation -->
                                <svg width="64px" height="64px" class="h-6 w-6 text-red-600" stroke="currentColor"
                                    fill="none" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"
                                    stroke="#ef4444" stroke-width="0.45600000000000007">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier">
                                        <path
                                            d="M12 7.25C12.4142 7.25 12.75 7.58579 12.75 8V13C12.75 13.4142 12.4142 13.75 12 13.75C11.5858 13.75 11.25 13.4142 11.25 13V8C11.25 7.58579 11.5858 7.25 12 7.25Z"
                                            fill="#ef4444"></path>
                                        <path
                                            d="M12 17C12.5523 17 13 16.5523 13 16C13 15.4477 12.5523 15 12 15C11.4477 15 11 15.4477 11 16C11 16.5523 11.4477 17 12 17Z"
                                            fill="#ef4444"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M8.2944 4.47643C9.36631 3.11493 10.5018 2.25 12 2.25C13.4981 2.25 14.6336 3.11493 15.7056 4.47643C16.7598 5.81544 17.8769 7.79622 19.3063 10.3305L19.7418 11.1027C20.9234 13.1976 21.8566 14.8523 22.3468 16.1804C22.8478 17.5376 22.9668 18.7699 22.209 19.8569C21.4736 20.9118 20.2466 21.3434 18.6991 21.5471C17.1576 21.75 15.0845 21.75 12.4248 21.75H11.5752C8.91552 21.75 6.84239 21.75 5.30082 21.5471C3.75331 21.3434 2.52637 20.9118 1.79099 19.8569C1.03318 18.7699 1.15218 17.5376 1.65314 16.1804C2.14334 14.8523 3.07658 13.1977 4.25818 11.1027L4.69361 10.3307C6.123 7.79629 7.24019 5.81547 8.2944 4.47643ZM9.47297 5.40432C8.49896 6.64148 7.43704 8.51988 5.96495 11.1299L5.60129 11.7747C4.37507 13.9488 3.50368 15.4986 3.06034 16.6998C2.6227 17.8855 2.68338 18.5141 3.02148 18.9991C3.38202 19.5163 4.05873 19.8706 5.49659 20.0599C6.92858 20.2484 8.9026 20.25 11.6363 20.25H12.3636C15.0974 20.25 17.0714 20.2484 18.5034 20.0599C19.9412 19.8706 20.6179 19.5163 20.9785 18.9991C21.3166 18.5141 21.3773 17.8855 20.9396 16.6998C20.4963 15.4986 19.6249 13.9488 18.3987 11.7747L18.035 11.1299C16.5629 8.51987 15.501 6.64148 14.527 5.40431C13.562 4.17865 12.8126 3.75 12 3.75C11.1874 3.75 10.4379 4.17865 9.47297 5.40432Z"
                                            fill="#ef4444"></path>
                                    </g>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline"> Delete
                                    Item
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500"> Are you sure you want to
                                        delete <span class="font-bold" x-text="currentUserName"></span>? This action
                                        cannot be
                                        undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button @click="$wire.delete(currentUserId)" type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-500 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete </button>
                        <button @click="showDeleteModal = false" type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel </button>
                    </div>
                </div>
            </div>
        </div>
    </div>




</div>
