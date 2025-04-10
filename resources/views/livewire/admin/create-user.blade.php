<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Major;
use App\Models\Classes;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

new class extends Component {
    // User attributes
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $role = 'student';

    // Student attributes (if needed)
    public $majors = [];
    public $selectedMajor = null;
    public $classes = [];
    public $availableClasses = [];
    public $selectedClass = null;
    public $phone_number;
    public $nisn;

    // Teacher attributes (if needed)
    public $nuptk;
    public $teacher_phone;

    public function mount()
    {
        $this->majors = Major::all();
        $this->classes = Classes::all();
        $this->resetAvailableClasses();
    }

    public function resetAvailableClasses()
    {
        if ($this->selectedMajor) {
            // Filter kelas berdasarkan major_id
            $this->availableClasses = Classes::where('major_id', $this->selectedMajor)->get();
        } else {
            $this->availableClasses = []; // Kosongkan jika belum ada major yang dipilih
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, $this->getValidationRules());

        if ($propertyName === 'selectedMajor') {
            $this->selectedClass = null; // Reset kelas yang dipilih ketika major berubah
            $this->resetAvailableClasses();
        }
    }

    public function getValidationRules()
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:student,teacher',
        ];

        if ($this->role === 'student') {
            return array_merge($baseRules, [
                'selectedMajor' => 'required|exists:majors,id',
                'selectedClass' => 'required|exists:classes,id',
                'phone_number' => 'required|string|max:15',
                'nisn' => 'required|string|max:50|unique:students',
            ]);
        } else {
            return array_merge($baseRules, [
                'nuptk' => 'required|string|max:50|unique:teachers',
                'teacher_phone' => 'required|string|max:15',
            ]);
        }
    }

    public function createUser()
    {
        $this->validate($this->getValidationRules());

        // DB Transaction to ensure all related records are created successfully
        \DB::beginTransaction();

        try {
            // Create user - token and QR code will be handled by the observer
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);

            // Assign role
            $role = Role::where('name', $this->role)->first();
            $user->assignRole($role);

            // Create related record based on role
            if ($this->role === 'student') {
                Student::create([
                    'user_id' => $user->id,
                    'parent_number' => $this->phone_number,
                    'classes_id' => $this->selectedClass,
                    'major' => $this->selectedMajor,
                    'nisn' => $this->nisn,
                ]);
            } else {
                Teacher::create([
                    'user_id' => $user->id,
                    'nuptk' => $this->nuptk,
                    'phone_number' => $this->teacher_phone,
                ]);
            }

            \DB::commit();

            $this->dispatch('user-created', $user->id);

            return redirect()->route('users')->with('status', 'User berhasil dibuat!');
        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}; ?>

<div x-data="{
    isStudent: $wire.role === 'student'
}" x-init="$watch('$wire.role', value => isStudent = value === 'student')">
    <form wire:submit="createUser" class="mx-auto max-w-3xl">
        <!-- Alert Messages -->
        @if (session()->has('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- User Information -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
            <div class="px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Informasi Akun</h3>
                <p class="mt-1 text-sm text-gray-500">Masukkan informasi dasar untuk membuat akun pengguna baru.</p>
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <div class="mt-1">
                            <input type="text" id="name" wire:model="name" autocomplete="name"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        @error('name')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-span-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <div class="mt-1">
                            <input type="email" id="email" wire:model="email" autocomplete="email"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        @error('email')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1">
                            <input type="password" id="password" wire:model="password"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        @error('password')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi
                            Password</label>
                        <div class="mt-1">
                            <input type="password" id="password_confirmation" wire:model="password_confirmation"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Jenis Pengguna</label>
                        <div class="mt-2 flex space-x-4">
                            <div class="flex items-center">
                                <input id="role-student" wire:model="role" type="radio" value="student"
                                    class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="role-student" class="ml-2 block text-sm text-gray-700">Siswa</label>
                            </div>
                            <div class="flex items-center">
                                <input id="role-teacher" wire:model="role" type="radio" value="teacher"
                                    class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="role-teacher" class="ml-2 block text-sm text-gray-700">Guru</label>
                            </div>
                        </div>
                        @error('role')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Student-specific fields -->
        <div x-show="isStudent" class="mt-6">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Siswa</h3>
                    <p class="mt-1 text-sm text-gray-500">Masukkan informasi tambahan untuk siswa.</p>
                </div>

                <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-gray-700">Nomor
                                Orangtua</label>
                            <div class="mt-1">
                                <input type="text" id="phone_number" wire:model="phone_number"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            @error('phone_number')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="nisn" class="block text-sm font-medium text-gray-700">NISN</label>
                            <div class="mt-1">
                                <input type="text" id="nisn" wire:model="nisn"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            @error('nisn')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="selectedMajor" class="block text-sm font-medium text-gray-700">Jurusan</label>
                            <div class="mt-1">
                                <select id="selectedMajor" wire:model.live="selectedMajor"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">Pilih Jurusan</option>
                                    @foreach ($majors as $major)
                                        <option value="{{ $major->id }}">{{ $major->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('selectedMajor')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="selectedClass" class="block text-sm font-medium text-gray-700">Kelas</label>
                            <div class="mt-1">
                                <select id="selectedClass" wire:model="selectedClass"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    @if (!$selectedMajor) disabled @endif>
                                    <option value="">Pilih Kelas</option>
                                    @foreach ($availableClasses as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                @if (!$selectedMajor)
                                    <p class="mt-1 text-xs text-gray-500">Pilih jurusan terlebih dahulu</p>
                                @endif
                            </div>
                            @error('selectedClass')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teacher-specific fields -->
        <div x-show="!isStudent" class="mt-6">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Guru</h3>
                    <p class="mt-1 text-sm text-gray-500">Masukkan informasi tambahan untuk guru.</p>
                </div>

                <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="nuptk" class="block text-sm font-medium text-gray-700">NUPTK</label>
                            <div class="mt-1">
                                <input type="text" id="nuptk" wire:model="nuptk"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            @error('nuptk')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="teacher_phone" class="block text-sm font-medium text-gray-700">Nomor
                                Handphone</label>
                            <div class="mt-1">
                                <input type="text" id="teacher_phone" wire:model="teacher_phone"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                            @error('teacher_phone')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('users') }}" wire:navigate
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Batal
            </a>

            <button type="submit"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="mr-1.5 h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Buat Pengguna
            </button>
        </div>
    </form>
</div>
