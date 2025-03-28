<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Major;
use App\Models\Classes;

new class extends Component {
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

    public function with()
    {
        return [
            'roles' => Spatie\Permission\Models\Role::all(),
            'majors' => Major::all(),
            'classes' => Classes::all(),
        ];
    }

    public function submit()
    {
        // Validasi Umum Berdasarkan Role
        $this->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($this->role == 'teacher') {
            // Validasi khusus untuk Guru
            $this->validate([
                'phoneNumber' => 'required',
                'NUPTK' => 'required',
            ]);

            // Membuat user
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            // Menetapkan role guru
            $user->assignRole($this->role);

            // Membuat data guru
            Teacher::create([
                'user_id' => $user->id,
                'phone_number' => $this->phoneNumber,
                'nuptk' => $this->NUPTK,
            ]);

            return $this->redirect(route('users'));
        }

        if ($this->role == 'student') {
            // Validasi khusus untuk Siswa
            $this->validate([
                'NISN' => 'required',
                'parentNumber' => 'required',
                'major' => 'required',
                'class' => 'required',
            ]);

            // Membuat user
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            // Menetapkan role siswa
            $user->assignRole($this->role);

            // Membuat data siswa
            Student::create([
                'user_id' => $user->id,
                'nisn' => $this->NISN,
                'parent_number' => $this->parentNumber,
                'major' => $this->major,
                'classes_id' => $this->class,
            ]);
            return $this->redirect(route('users'));
        }
    }
};
?>

<div>
    <div class="mx-auto w-1/2" x-data="{ role: '', isTeacher: false, isStudent: false }">
        <form wire:submit.prevent="submit">
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
                <label for="password" class="mb-1 font-header">Password</label>
                <input type="password" wire:model="password" placeholder="*********"
                    class="block w-full rounded-lg border-gray-300" />
                @error('password')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                        {{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="role" class="mb-1 font-header">Pilih
                    Peran</label>
                <select name="role" x-model="role" wire:model="role"
                    @change="isTeacher = role === 'teacher'; isStudent = role === 'student';" id="role"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500">
                    <option selected>Pilih role user</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
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

                <x-primary-button type="submit" color="blue">Buat Pengguna</x-primary-button>
            </div>
        </form>
    </div>
</div>
