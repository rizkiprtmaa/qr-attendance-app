<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Major;
use App\Models\Classes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $user;
    public $userId;
    public $student;
    public $teacher;

    // User attributes
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $role;

    // Student attributes
    public $majors = [];
    public $selectedMajor;
    public $classes = [];
    public $selectedClass;
    public $phone_number;
    public $nisn;

    // Teacher attributes
    public $nuptk;
    public $teacher_phone;

    // Tab controls
    public $activeTab = 'account';

    public function mount(User $user)
    {
        $this->fill($user);
        $this->userId = $user->id;
        $this->user = User::with('roles')->findOrFail($this->userId);
        $this->name = $this->user->name;
        $this->email = $this->user->email;

        if ($this->user->hasRole('student')) {
            $this->role = 'student';
            $this->student = Student::where('user_id', $this->userId)
                ->with(['major', 'classes'])
                ->first();

            if ($this->student) {
                $this->phone_number = $this->student->parent_number;
                $this->selectedMajor = $this->student->major;
                $this->selectedClass = $this->student->classes_id;
                $this->nisn = $this->student->nisn;
            }
        } elseif ($this->user->hasRole('teacher')) {
            $this->role = 'teacher';
            $this->teacher = Teacher::where('user_id', $this->userId)->first();

            if ($this->teacher) {
                $this->nuptk = $this->teacher->nuptk;
                $this->teacher_phone = $this->teacher->phone_number;
            }
        }

        $this->majors = Major::all();
        $this->classes = Classes::all();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, $this->getValidationRules());
    }

    public function getValidationRules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->userId)],
        ];

        // Password is optional during edit
        if ($this->password) {
            $rules['password'] = 'required|min:8|confirmed';
        }

        if ($this->role === 'student') {
            $studentRules = [
                'phone_number' => 'required|string|max:15',
                'nisn' => ['required', 'string', 'max:50', Rule::unique('students')->ignore($this->student->id ?? null)],
                'selectedMajor' => 'required|exists:majors,id',
                'selectedClass' => 'required|exists:classes,id',
            ];
            $rules = array_merge($rules, $studentRules);
        } elseif ($this->role === 'teacher') {
            $teacherRules = [
                'nuptk' => ['required', 'string', 'max:50', Rule::unique('teachers', 'nuptk')->ignore($this->teacher->id ?? null)],
                'teacher_phone' => 'required|string|max:15',
            ];
            $rules = array_merge($rules, $teacherRules);
        }

        return $rules;
    }

    public function updateUser()
    {
        $this->validate($this->getValidationRules());

        // DB Transaction to ensure all related records are updated successfully
        \DB::beginTransaction();

        try {
            // Update user
            $data = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            $this->user->update($data);

            // Update related record based on role
            if ($this->role === 'student') {
                if ($this->student) {
                    $this->student->update([
                        'parent_number' => $this->phone_number,
                        'nisn' => $this->nisn,
                        'classes_id' => $this->selectedClass,
                        'major' => $this->selectedMajor,
                    ]);
                } else {
                    // Create student if doesn't exist (rare case of role change)
                    Student::create([
                        'user_id' => $this->userId,
                        'parent_number' => $this->phone_number,
                        'nisn' => $this->nisn,
                        'class_id' => $this->selectedClass,
                        'major' => $this->selectedMajor,
                    ]);

                    // Update role if needed
                    if (!$this->user->hasRole('student')) {
                        $this->user->syncRoles(['student']);
                    }
                }
            } elseif ($this->role === 'teacher') {
                if ($this->teacher) {
                    $this->teacher->update([
                        'nuptk' => $this->nuptk,
                        'phone_number' => $this->teacher_phone,
                    ]);
                } else {
                    // Create teacher if doesn't exist (rare case of role change)
                    Teacher::create([
                        'user_id' => $this->userId,
                        'nuptk' => $this->nuptk,
                        'phone_number' => $this->teacher_phone,
                    ]);

                    // Update role if needed
                    if (!$this->user->hasRole('teacher')) {
                        $this->user->syncRoles(['teacher']);
                    }
                }
            }

            \DB::commit();

            $this->dispatch('user-updated');

            session()->flash('message', 'User berhasil diperbarui!');
        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}; ?>

<div class="mt-12 md:mt-0"
    @if (session()->has('message')) <div class="mb-4 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                </div>
            </div>
        </div> @endif
    @if (session()->has('error')) <div class="mb-4 rounded-md bg-red-50 p-4">
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
        </div> @endif
    <div class="mb-6 flex items-center justify-between">
    <div class="hidden md:block">
        <h2 class="text-xl font-semibold text-gray-800">Edit Pengguna: {{ $name }}</h2>
        <p class="mt-1 text-sm text-gray-500">Perbarui informasi pengguna di bawah ini.</p>
    </div>
    <div class="hidden items-center gap-2 md:flex">
        <span class="text-sm text-gray-500">{{ $role === 'student' ? 'Siswa' : 'Guru' }}</span>

    </div>
</div>

<!-- Tab Navigation -->
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button wire:click="setActiveTab('account')"
            class="{{ $activeTab === 'account' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} group inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor"
                class="{{ $activeTab === 'account' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }} mr-2 size-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
            Akun
        </button>

        <button wire:click="setActiveTab('profile')"
            class="{{ $activeTab === 'profile' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} group inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor"
                class="{{ $activeTab === 'profile' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }} mr-2 size-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
            </svg>
            Detail
        </button>
    </nav>
</div>

<form wire:submit="updateUser">
    <!-- Account Tab -->
    <div x-data="{
        showPassword: false,
        showConfirmPassword: false,
        togglePassword() {
            this.showPassword = !this.showPassword;
        },
        toggleConfirmPassword() {
            this.showConfirmPassword = !this.showConfirmPassword;
        }
    }" class="{{ $activeTab === 'account' ? 'block' : 'hidden' }}">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
            <div class="px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Informasi Akun</h3>
                <p class="mt-1 text-sm text-gray-500">Perbarui informasi dasar akun pengguna.</p>
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
                        <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                        <div class="relative mt-1">
                            <input :type="showPassword ? 'text' : 'password'" id="password" wire:model="password"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <button type="button" @click="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3">
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    class="size-4 text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    class="size-4 text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Kosongkan jika tidak ingin mengubah password</p>
                        @error('password')
                            <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi
                            Password Baru</label>
                        <div class="relative mt-1">
                            <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation"
                                wire:model="password_confirmation"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <button type="button" @click="toggleConfirmPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3">
                                <svg x-show="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    class="size-4 text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="showConfirmPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    class="size-4 text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Tab -->
    <div class="{{ $activeTab === 'profile' ? 'block' : 'hidden' }}">
        <!-- Student-specific fields -->
        @if ($role === 'student')
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Siswa</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Perbarui data siswa.
                    </p>
                </div>

                <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <div class="grid gap-6 md:grid-cols-2">
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
                            <label for="selectedMajor" class="block text-sm font-medium text-gray-700">Jurusan</label>
                            <div class="mt-1">
                                <select id="selectedMajor" wire:model="selectedMajor"
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
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="">Pilih Kelas</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('selectedClass')
                                <span class="mt-1 text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Teacher-specific fields -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
                <div class="px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Guru</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Perbarui data guru.
                    </p>
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
        @endif

        <!-- QR Code Display Section -->
        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
            <div class="px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">QR Code Presensi</h3>
                <p class="mt-1 text-sm text-gray-500">QR Code untuk presensi kehadiran.</p>
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                <div class="flex justify-center">
                    @if ($user->qr_code_path)
                        <div class="rounded-lg bg-white p-4 shadow">
                            <img src="{{ asset('storage/' . $user->qr_code_path) }}" alt="QR Code Presensi"
                                class="h-48 w-48">
                        </div>
                    @else
                        <div
                            class="flex flex-col items-center justify-center rounded-lg bg-gray-100 p-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="mb-2 h-12 w-12">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                            </svg>
                            <p>QR Code tidak tersedia</p>
                        </div>
                    @endif
                </div>

                @if ($user->qr_code_path)
                    <div class="mt-4 flex justify-center">
                        <a href="/users/{{ $userId }}/download-qr"
                            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Download QR Code
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="mt-6 flex items-center justify-end space-x-3">
        <a href="{{ route('users') }}" wire:navigate
            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Batal
        </a>
        <button type="submit"
            class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="mr-1.5 h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Simpan Perubahan
        </button>
    </div>
</form>
</div>
