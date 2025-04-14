<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $photo;

    /**
     * Update the profile photo for the currently authenticated user.
     */
    public function updateProfilePhoto(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:1024'], // 1MB Max
        ]);

        $user = Auth::user();

        // Hapus foto profil lama jika ada
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Simpan foto baru
        $path = $this->photo->store('profile-photos', 'public');
        $user->profile_photo_path = $path;
        $user->save();

        $this->reset('photo');
        $this->dispatch('profile-photo-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="font-inter text-lg font-medium text-gray-900">
            {{ __('Foto Profil') }}
        </h2>

        <p class="mt-1 font-inter text-sm text-gray-600">
            {{ __('Update foto profilmu.') }}
        </p>
    </header>

    <form wire:submit="updateProfilePhoto" class="mt-6 space-y-6">
        <div>
            <div class="flex items-center gap-6">
                <div>
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 rounded-full object-cover" alt="Preview">
                    @else
                        <img src="{{ auth()->user()->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}"
                            class="h-20 w-20 rounded-full object-cover" alt="{{ auth()->user()->name }}">
                    @endif
                </div>
                <div class="flex-1">
                    <x-input-label for="photo" :value="__('Photo')" />
                    <input wire:model="photo" id="photo" type="file" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-900 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    <x-input-error class="mt-2" :messages="$errors->get('photo')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">


            <button
                class="inline-flex items-center rounded-md border border-transparent bg-blue-500 px-4 py-2 font-inter text-sm text-white shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                type="submit">
                <span wire:loading.remove wire:target="updateProfilePhoto">{{ __('Simpan') }}</span>
                <span wire:loading wire:target="updateProfilePhoto">{{ __('Menyimpan...') }}</span>
            </button>

            <x-action-message class="me-3" on="profile-photo-updated">
                {{ __('Foto profilmu berhasil diubah.') }}
            </x-action-message>
        </div>
    </form>
</section>
