<?php

use App\Models\User;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;


Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('users', 'admin.user.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('users');

Route::view('teachers', 'admin.user.teacher')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('teachers');


Route::view('create-user', 'admin.user.create')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('create.user');

Volt::route('users/{user}/edit', 'admin.edit-user')->middleware(['auth', 'verified', 'role:admin'])->name('user.edit');

Route::view('classes', 'admin.classes.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('classes');

Route::view('settings', 'admin.settings.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('settings');

Route::view('classes-detail/{id}', 'admin.classes.show')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('classes.detail');


Volt::route('classes/{major}/edit', 'admin.edit-major')->middleware(['auth', 'verified', 'role:admin'])->name('edit.major');


Route::get('/attendance/scan', function () {
    return view('admin.attendances.qr-scanner');
})->middleware(['auth', 'verified', 'role:admin'])->name('attendance.scan');

Route::get('/users/{user}/download-qr', [
    'as' => 'user.download.qr',
    'uses' => function ($userId) {
        $user = \App\Models\User::findOrFail($userId);

        if (!$user->qr_code_path) {
            return redirect()->back()->with('error', 'QR Code tidak tersedia');
        }

        return response()->download(storage_path('app/public/' . $user->qr_code_path));
    }
])->middleware('auth');

Route::view('attendances', 'admin.attendances.index')->middleware(['auth', 'verified', 'role:admin'])->name('qr.attendances');


require __DIR__ . '/auth.php';
