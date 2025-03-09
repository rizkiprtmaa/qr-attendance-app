<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;

new #[Layout('layouts.app')] class extends Component {
    // Declare these properties explicitly
    public User $user;
    public $student;
    public $teacher;
    public $id;
    public $name;
    public $email;
    public $password;
    public $phoneNumber;
    public $NUPTK;
    public $NISN;
    public $parentNumber;
    public $major;
    public $role;
    public $class;

    public function mount(User $user)
    {
        // Explicitly set the $user property
        $this->user = $user;

        // Load related models based on the role
        $this->fill($user->toArray());
        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()->name;

        // Retrieve related models conditionally
        if ($this->role == 'teacher') {
            $this->teacher = $user->teacher;
            $this->phoneNumber = $this->teacher->phone_number;
            $this->NUPTK = $this->teacher->nuptk;
        }

        if ($this->role == 'student') {
            $this->student = $user->student;
            $this->NISN = $this->student->nisn;
            $this->parentNumber = $this->student->parent_number;
            $this->major = $this->student->major;
            $this->class = $this->student->classes_id;
        }
    }

    public function editUser()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        if ($this->role == 'teacher') {
            $this->validate([
                'phoneNumber' => 'required',
                'NUPTK' => 'required',
            ]);

            $this->user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);

            $this->teacher->update([
                'phone_number' => $this->phoneNumber,
                'nuptk' => $this->NUPTK,
            ]);
        }

        if ($this->role == 'student') {
            $this->validate([
                'NISN' => 'required',
                'parentNumber' => 'required',
                'major' => 'required',
                'class' => 'required',
            ]);

            $this->user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);

            $this->student->update([
                'nisn' => $this->NISN,
                'parent_number' => $this->parentNumber,
                'major' => $this->major,
                'classes_id' => $this->class,
            ]);
        }

        // Fix typo in dispatch
        $this->dispatch('user-updated');
        return $this->redirect(route('users'));
    }

    public function with()
    {
        return [
            'roles' => Spatie\Permission\Models\Role::all(),
            'classes' => App\Models\Classes::all(),
            'majors' => App\Models\Major::all(),
        ];
    }
}; ?>

<div class="flex flex-col gap-4">
    <x-slot name="header">
        <h2 class="text-2xl font-semibold leading-tight text-gray-800">
            {{ __('Edit Pengguna') }}
        </h2>
        <nav class="mt-2.5 flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                <li class="inline-flex items-center">
                    <a href="{{ route('users') }}" wire:navigate
                        class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="true" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="me-1 size-4 fill-gray-600">
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
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 9 4-4-4-4" />
                        </svg>
                        <a href="{{ route('create.user') }}" wire:navigate
                            class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Edit
                            users</a>
                    </div>
                </li>

            </ol>
        </nav>
    </x-slot>
    <div class="min-h-screen/80 mx-auto flex w-full flex-col items-center justify-center" x-data="{
        role: '{{ $role }}',
        isTeacher: '{{ $role }}' === 'teacher',
        isStudent: '{{ $role }}' === 'student'
    }">
        <form wire:submit.prevent="editUser" class="w-full max-w-xl">
            <div class="mb-4">
                <label for="name" class="mb-1 font-header">Nama Lengkap</label>
                <input type="text" wire:model="name" placeholder="Isian berupa nama lengkap beserta gelar"
                    class="block w-full rounded-lg border-gray-300" />
                @error('name')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                        {{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="email" class="mb-1 font-header">Email</label>
                <input type="email" wire:model="email" placeholder="email@domain.com"
                    class="block w-full rounded-lg border-gray-300" />
                @error('email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                        {{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="role" class="mb-1 font-header">
                    Peran</label>
                <input type="text" class="block w-full rounded-lg border-gray-300" value="{{ $role }}"
                    readonly disabled>
            </div>
            <div x-show="isTeacher" x-transition.duration.500ms>
                <div class="mb-4">
                    <label for="phoneNumber" class="mb-1 font-header">Nomor Handphone</label>
                    <input type="number" wire:model="phoneNumber" placeholder="Nomor handphone aktif"
                        class="block w-full rounded-lg border-gray-300" />
                    @error('phoneNumber')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                            {{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="NUPTK" class="mb-1 font-header">NUPTK</label>
                    <input type="number" wire:model="NUPTK" placeholder="Nomor Unik Pendidik dan Tenaga Kependidikan"
                        class="block w-full rounded-lg border-gray-300" />
                    @error('NUPTK')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                            {{ $message }}</p>
                    @enderror
                </div>


            </div>
            <div x-show="isStudent" class="mt-4" x-transition>
                <div class="mb-4">
                    <label for="parentNumber" class="ms-3 font-header">Nomor Orangtua/Wali</label>
                    <input type="number" wire:model="parentNumber" placeholder="Nomor handphone aktif orangtua/wali"
                        class="block w-full rounded-lg border-gray-300" />
                    @error('parentNumber')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                            {{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="NISN" class="ms-3 font-header">NISN</label>
                    <input type="number" wire:model="NISN" placeholder="Nomor Induk Siswa Nasional"
                        class="block w-full rounded-lg border-gray-300" />
                    @error('NISN')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                            {{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="major" class="ms-3 font-header">Pilih
                        Jurusan</label>
                    <select name="major" wire:model="major"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500">
                        <option selected>Pilih Jurusan</option>
                        @foreach ($majors as $major)
                            <option value="{{ $major->id }}">{{ $major->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label for="class" class="ms-3 font-header">Pilih
                        Kelas</label>
                    <select name="class" wire:model="class"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500">
                        <option selected>Pilih Kelas</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="flex w-full justify-center">

                <x-primary-button type="submit" color="blue">Edit Pengguna</x-primary-button>
            </div>
        </form>
    </div>
</div>
