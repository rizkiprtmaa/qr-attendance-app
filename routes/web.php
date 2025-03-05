<?php

use Illuminate\Support\Facades\Route;


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

Route::view('create-user', 'admin.user.create')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('create.user');

Route::view('classes', 'admin.classes.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('classes');

require __DIR__ . '/auth.php';
