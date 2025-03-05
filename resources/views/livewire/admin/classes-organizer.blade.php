<?php

use Livewire\Volt\Component;
use App\Models\Major;
use App\Models\Classes;

new class extends Component {
    public $name;
    public $code;
    public $classesName;
    public $major;

    public function createMajor()
    {
        $this->validate([
            'name' => 'required|string',
            'code' => 'required|string',
        ]);

        Major::create([
            'name' => $this->name,
            'code' => $this->code,
        ]);

        $this->reset();
    }

    public function delete($id)
    {
        $major = Major::findOrFail($id);
        $major->delete();
    }

    public function createClasses()
    {
        $this->validate([
            'classesName' => 'required|string',
            'major' => 'required',
        ]);

        Classes::create([
            'name' => $this->classesName,
            'major_id' => $this->major,
        ]);

        $this->reset();
    }

    public function deleteClass($id)
    {
        $class = Classes::findOrFail($id);
        $class->delete();
    }

    public function render(): mixed
    {
        return view('livewire.admin.classes-organizer', [
            'majors' => Major::all(),
            'classes' => Classes::with('major')->orderBy('created_at', 'desc')->get(),
        ]);
    }
}; ?>

<div x-data="{ showMajorModal: false, showClassesModal: false }">
    <div class="flex items-center justify-between">
        <p class="font-inter text-xl font-medium">Jurusan</p>
        <button @click="showMajorModal = true"
            class="flex flex-row items-center gap-2 rounded-lg border-t-2 border-t-white bg-gray-800 px-6 py-2 text-white hover:bg-gray-700 hover:shadow-2xl">
            Tambah Jurusan <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </span></button>
    </div>


    {{-- Kelola Jurusan --}}
    <div class="mt-4 grid grid-cols-2 gap-4">
        @foreach ($majors as $major)
            <div class="flex w-full flex-row items-center gap-4 rounded-md border border-slate-400/40 bg-white px-5 py-5"
                wire:key="major-{{ $major->id }}">
                <div class="flex h-12 w-12 items-center justify-center rounded-md bg-gray-700 text-white">
                    {{ substr($major->name, 0, 1) }}
                </div>
                <div class="flex w-full flex-row items-center justify-between">
                    <div>
                        <p class="font-inter font-medium">{{ $major->name }}</p>
                        <p class="mt-2 font-inter text-sm text-slate-500">{{ $major->code }}</p>
                    </div>
                    <div class="">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center rounded-md p-2 hover:bg-gray-200"><svg
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link href="#" class="flex flex-row items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                                    </svg>


                                    <p class="font-inter">Kelola Kelas</p>
                                </x-dropdown-link>
                                <x-dropdown-link href="#" class="flex flex-row items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>

                                    Edit
                                </x-dropdown-link>
                                <x-dropdown-link
                                    x-on:click="if(confirm('Yakin mau hapus jurusan ini?')) $wire.delete({{ $major->id }})"
                                    class="flex flex-row items-center gap-2 hover:cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-5 text-red-600">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>

                                    <p class="text-red-600">Hapus</p>
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>

                </div>

            </div>
        @endforeach
    </div>

    {{-- Modals Jurusan --}}
    <div x-cloak x-show="showMajorModal" x-transition.opacity.duration.200ms x-trap.inert.noscroll="showMajorModal"
        x-on:keydown.esc.window="showMajorModal = false" x-on:click.self="showMajorModal = false"
        class="fixed inset-0 z-30 flex w-full items-center justify-center bg-black/20 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="defaultModalTitle">
        <!-- Modal Dialog -->
        <div x-show="showMajorModal"
            x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity"
            x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
            class="rounded-radius border-gray text-on-surface flex w-full max-w-2xl flex-col gap-4 overflow-hidden rounded-xl border bg-white px-8 py-6 backdrop-blur-lg backdrop-filter">
            <!-- Dialog Header -->
            <div
                class="border-outline bg-surface-alt/60 flex flex-col items-center justify-center gap-2 px-4 pb-4 pt-10">
                <h3 id="defaultModalTitle"
                    class="text-on-surface-strong text-center font-inter text-xl font-medium tracking-wide">
                    Tambah
                    Jurusan</h3>
                <p class="font-inter text-sm text-gray-600">Buat jurusan sebelum integrasi dengan kelas.</p>
            </div>
            <!-- Dialog Body -->
            <div class="px-8">
                <form wire:submit='createMajor'>
                    <div class="mb-4">
                        <label for="name" class="font-inter text-sm font-semibold text-slate-500">Nama
                            Jurusan</label>
                        <input type="text" wire:model="name" placeholder="Nama lengkap jurusan"
                            class="flex w-full rounded-lg border-gray-300 text-sm" />
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                                {{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="code" class="font-inter text-sm font-semibold text-slate-500">Kode
                            Jurusan</label>
                        <input type="text" wire:model="code" placeholder="DKV / TSM"
                            class="w-full rounded-lg border-gray-300 text-sm" />
                        @error('code')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                                {{ $message }}</p>
                        @enderror
                    </div>

            </div>
            <!-- Dialog Footer -->
            <div
                class="border-outline bg-surface-alt/60 dark:border-outline-dark dark:bg-surface-dark/20 flex flex-col-reverse justify-between gap-2 border-t p-4 sm:flex-row sm:items-center md:justify-end">
                <button x-on:click="showMajorModal = false" type="button"
                    class="text-on-surface focus-visible:outline-primary dark:text-on-surface-dark dark:focus-visible:outline-primary-dark whitespace-nowrap rounded-md px-4 py-2 text-center text-sm font-medium tracking-wide transition hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 active:opacity-100 active:outline-offset-0">Batal</button>
                <button x-on:click="showMajorModal = false" type="submit"
                    class="border-primary text-on-primary focus-visible:outline-primary whitespace-nowrap rounded-md border bg-slate-900 px-4 py-2 text-center font-inter text-sm font-medium tracking-wide text-white transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 active:opacity-100 active:outline-offset-0">Buat
                    Jurusan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-8 flex items-center justify-between">
        <p class="font-inter text-xl font-medium">Kelas</p>
        <button @click="showClassesModal = true"
            class="flex flex-row items-center gap-2 rounded-lg border-t-2 border-t-white bg-gray-800 px-6 py-2 text-white hover:bg-gray-700 hover:shadow-2xl">
            Tambah Kelas <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </span></button>
    </div>

    {{-- Kelola Kelas --}}
    <div class="mt-4 grid grid-cols-4 gap-4">
        @foreach ($classes as $class)
            <div class="rounded-2xl bg-gray-700 shadow-md" wire:key="class-{{ $class->id }}">
                <div
                    class="flex w-full flex-row items-center gap-4 rounded-xl border border-slate-400/40 bg-white px-5 py-5">

                    <div class="flex w-full flex-row items-center justify-between">
                        <div>
                            <p class="font-inter font-medium">{{ $class->name }}</p>
                            <p class="mt-2 font-inter text-sm text-slate-500">
                                {{ Str::words($class->major->name, 2, '...') }}</p>
                        </div>
                        <div>
                            <p class="font-inter font-medium">{{ $class->major->code }}</p>
                        </div>
                    </div>

                </div>
                <div class="flex flex-row items-center justify-between rounded-b-xl bg-gray-700 px-4 py-3 text-white">
                    <div class="flex flex-row items-center gap-2">
                        <button><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                        </button>
                        <button
                            x-on:click="if(confirm('Apakah anda yakin ingin menghapus kelas ini?')) $wire.deleteClass({{ $class->id }})"><svg
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-5 text-red-500">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                    <div>
                        <p class="font-inter text-xs text-white"><span class="rounded-full bg-green-500 px-2 py-1">18
                                Siswa</span></p>
                    </div>

                </div>
            </div>
        @endforeach
    </div>

    {{-- Modals Kelas --}}
    <div x-cloak x-show="showClassesModal" x-transition.opacity.duration.200ms
        x-trap.inert.noscroll="showClassesModal" x-on:keydown.esc.window="showClassesModal = false"
        x-on:click.self="showClassesModal = false"
        class="fixed inset-0 z-30 flex w-full items-center justify-center bg-black/20 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="defaultModalTitle">
        <!-- Modal Dialog -->
        <div x-show="showClassesModal"
            x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity"
            x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
            class="rounded-radius border-gray text-on-surface flex w-full max-w-2xl flex-col gap-4 overflow-hidden rounded-xl border bg-white px-8 py-6 backdrop-blur-lg backdrop-filter">
            <!-- Dialog Header -->
            <div
                class="border-outline bg-surface-alt/60 flex flex-col items-center justify-center gap-2 px-4 pb-4 pt-10">
                <h3 id="defaultModalTitle"
                    class="text-on-surface-strong text-center font-inter text-xl font-medium tracking-wide">
                    Tambah
                    Kelas</h3>
                <p class="font-inter text-sm text-gray-600">Buat kelas, integrasikan dengan jurusan, dan daftarkan
                    siswa.
                </p>
            </div>
            <!-- Dialog Body -->
            <div class="px-8">
                <form wire:submit='createClasses'>
                    <div class="mb-4">
                        <label for="name" class="font-inter text-sm font-semibold text-slate-500">Nama
                            Kelas</label>
                        <input type="text" wire:model="classesName"
                            placeholder="Isi nama kelas dengan tingkatan dan rombel, cth: 10 A"
                            class="flex w-full rounded-lg border-gray-300 text-sm" />
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                                {{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="major" class="font-inter text-sm font-semibold text-slate-500">Jurusan</label>
                        <select wire:model="major" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="null" disabled selected>--- Pilih Jurusan ---</option>
                            @foreach ($majors as $major)
                                <option value="{{ $major->id }}">{{ $major->name }}</option>
                            @endforeach
                        </select>
                        @error('major')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                                {{ $message }}</p>
                        @enderror
                    </div>

            </div>
            <!-- Dialog Footer -->
            <div
                class="border-outline bg-surface-alt/60 dark:border-outline-dark dark:bg-surface-dark/20 flex flex-col-reverse justify-between gap-2 border-t p-4 sm:flex-row sm:items-center md:justify-end">
                <button x-on:click="showClassesModal = false" type="button"
                    class="text-on-surface focus-visible:outline-primary dark:text-on-surface-dark dark:focus-visible:outline-primary-dark whitespace-nowrap rounded-md px-4 py-2 text-center text-sm font-medium tracking-wide transition hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 active:opacity-100 active:outline-offset-0">Batal</button>
                <button x-on:click="showClassesModal = false" type="submit"
                    class="border-primary text-on-primary focus-visible:outline-primary whitespace-nowrap rounded-md border bg-slate-900 px-4 py-2 text-center font-inter text-sm font-medium tracking-wide text-white transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 active:opacity-100 active:outline-offset-0">Buat
                    Jurusan</button>
                </form>
            </div>
        </div>
    </div>

</div>
