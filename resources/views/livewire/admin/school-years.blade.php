<?php

use Livewire\Volt\Component;
use App\Models\SchoolYear;

new class extends Component {
    public $schoolYears;
    public $start_month;
    public $end_month;

    public function createSchoolYear()
    {
        $this->validate([
            'schoolYears' => 'required',
            'start_month' => 'required',
            'end_month' => 'required',
        ]);

        SchoolYear::create([
            'name' => $this->schoolYears,
            'start_date' => $this->start_month,
            'end_date' => $this->end_month,
        ]);

        $this->reset();
    }

    public function render(): mixed
    {
        return view('livewire.admin.school-years', [
            'years' => SchoolYear::all(),
        ]);
    }
}; ?>

<div x-data="{ schoolYearsModal: false }">
    <div class="flex flex-row justify-between">
        <p class="font-inter text-xl font-medium">Atur Periode Tahun Ajaran</p>
        <button @click="schoolYearsModal = true"
            class="flex flex-row items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-white">Tambah Tahun Ajar
            <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </span></button>
    </div>

    <div class="mt-5">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-left text-sm text-gray-500 rtl:text-right dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3" wire:click="setSortBy('name')">
                            Tahun Ajaran
                        </th>
                        <th scope="col" class="px-6 py-3" wire:click="setSortBy('email')">
                            Bulan Mulai
                        </th>
                        <th scope="col" class="px-6 py-3" wire:click="setSortBy('status')">
                            Bulan Selesai
                        </th>
                        <th scope="col" class="px-6 py-3" wire:click="setSortBy('roles.name')">
                            Jumlah Kelas
                        </th>
                        <th scope="col" class="px-6 py-3">
                            <span class="sr-only">Edit</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($years as $year)
                        <tr wire:key="year-{{ $year->id }}"
                            class="border-b border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-600">
                            <th scope="row"
                                class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $year->name }}
                            </th>
                            <td class="px-6 py-4">
                                {{ $year->start_date }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $year->end_date }}
                            </td>
                            <td class="px-6 py-4">
                                0
                            </td>
                            <td class="gap-4 px-6 py-4 text-right">
                                <a href="#" class="me-4 font-medium text-blue-600 hover:underline">Edit</a>
                                <button class="font-medium text-red-600 hover:underline"
                                    x-on:click="$wire.delete('{{ $year->id }}')">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modals tambah --}}
    <div x-cloak x-show="schoolYearsModal" x-transition.opacity.duration.200ms x-trap.inert.noscroll="schoolYearsModal"
        x-on:keydown.esc.window="schoolYearsModal = false" x-on:click.self="schoolYearsModal = false"
        class="fixed inset-0 z-30 flex w-full items-center justify-center bg-black/20 p-4 pb-8 lg:p-8" role="dialog"
        aria-modal="true" aria-labelledby="defaultModalTitle">
        <!-- Modal Dialog -->
        <div x-show="schoolYearsModal"
            x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity"
            x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
            class="rounded-radius border-gray text-on-surface flex w-full max-w-2xl flex-col gap-4 overflow-hidden rounded-xl border bg-white px-8 py-6 backdrop-blur-lg backdrop-filter">
            <!-- Dialog Header -->
            <div
                class="border-outline bg-surface-alt/60 flex flex-col items-center justify-center gap-2 px-4 pb-4 pt-10">
                <h3 id="defaultModalTitle"
                    class="text-on-surface-strong text-center font-inter text-xl font-medium tracking-wide">
                    Tambah Tahun Ajaran</h3>
                <p class="font-inter text-sm text-gray-600">Atur tahun ajaran sesuai kebijakan sekolah.</p>
            </div>
            <!-- Dialog Body -->
            <div class="px-8">
                <form wire:submit='createSchoolYear'>
                    <div class="mb-4">
                        <label for="name" class="font-inter text-sm font-semibold text-slate-500">Tahun
                            Ajaran</label>
                        <input type="text" wire:model="schoolYears" placeholder="cth: 2024/2025"
                            class="flex w-full rounded-lg border-gray-300 text-sm" />
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span class="font-medium">Oops!</span>
                                {{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-4 flex w-full flex-row gap-4">
                        <div class="w-full">
                            <label for="code" class="font-inter text-sm font-semibold text-slate-500">Bulan
                                Mulai</label>
                            <input type="date" wire:model="start_month" placeholder="DKV / TSM"
                                class="w-full rounded-lg border-gray-300 text-sm" />
                            @error('code')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span
                                        class="font-medium">Oops!</span>
                                    {{ $message }}</p>
                            @enderror
                        </div>
                        <div class="w-full">
                            <label for="code" class="font-inter text-sm font-semibold text-slate-500">Bulan
                                Selesai</label>
                            <input type="date" wire:model="end_month" placeholder="DKV / TSM"
                                class="w-full rounded-lg border-gray-300 text-sm" />
                            @error('code')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-500"><span
                                        class="font-medium">Oops!</span>
                                    {{ $message }}</p>
                            @enderror
                        </div>
                    </div>

            </div>
            <!-- Dialog Footer -->
            <div
                class="border-outline bg-surface-alt/60 dark:border-outline-dark dark:bg-surface-dark/20 flex flex-col-reverse justify-between gap-2 border-t p-4 sm:flex-row sm:items-center md:justify-end">
                <button x-on:click="schoolYearsModal = false" type="button"
                    class="text-on-surface focus-visible:outline-primary dark:text-on-surface-dark dark:focus-visible:outline-primary-dark whitespace-nowrap rounded-md px-4 py-2 text-center text-sm font-medium tracking-wide transition hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 active:opacity-100 active:outline-offset-0">Batal</button>
                <button x-on:click="schoolYearsModal = false" type="submit"
                    class="border-primary text-on-primary focus-visible:outline-primary whitespace-nowrap rounded-md border bg-slate-900 px-4 py-2 text-center font-inter text-sm font-medium tracking-wide text-white transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 active:opacity-100 active:outline-offset-0">Tambah
                    Tahun Ajaran</button>
                </form>
            </div>
        </div>
    </div>
</div>
