<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Major;
use App\Models\Classes;

new #[Layout('layouts.app')] class extends Component {
    public $name;
    public $code;
    public $major;
    public $id;
    public $isUpdateClass = false;

    public function mount(Major $major)
    {
        $this->fill($major);
        $this->id = $major->id;
        $this->name = $major->name;
        $this->code = $major->code;
    }

    public function saveMajor()
    {
        $this->validate([
            'name' => 'required',
            'code' => 'required',
        ]);

        $this->major->update([
            'name' => $this->name,
            'code' => $this->code,
        ]);

        $this->dispatch('majorUpdated');
    }
}; ?>

<div class="mt-12 md:mt-0">

    <div class="flex min-h-[80vh] flex-col items-center justify-center">
        <div class="flex w-1/5 flex-row justify-end">
            <a wire:navigate href="{{ route('classes') }}" class="mb-4 flex flex-row justify-end gap-1 text-slate-600">
                <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-5 text-slate-600">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
                    </svg>
                </span>Kembali</a>
        </div>
        <div class="w-full max-w-md rounded-lg bg-white px-6 py-8 shadow-lg">
            <div class="gap-2 text-center">
                <p class="font-inter text-xl font-medium">Edit Jurusan</p>
                <p class="font-inter text-sm text-gray-500">Edit atau update jurusan yang sudah tersedia.</p>
            </div>
            <div class="mt-4">
                <form wire:submit='saveMajor'>
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
                    <div class="mb-4">
                        <label class="inline-flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="item-center flex size-5 text-green-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>

                            <span class="ms-2 text-sm font-medium text-green-400 dark:text-gray-300">Update akan
                                diterapkan pada
                                kelas tersedia</span>
                        </label>
                    </div>
                    <div class="mt-2 flex justify-center">
                        <button type="submit"
                            class="w-full rounded-lg bg-slate-900 px-4 py-2 font-inter text-sm text-white">Update
                            Jurusan</button>
                    </div>
                    <x-action-message on="majorUpdated" class="mt-4 text-green-600" />
                </form>
            </div>
        </div>
    </div>
</div>
