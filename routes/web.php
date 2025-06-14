<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Volt::route('zonas', 'zones')->name('zones.index');
    Volt::route('stores','stores')->name('stores.index');

    Volt::route('roles', 'rolesmanagment')->name('roles.index');
    Volt::route('usuarios', 'users')->name('users.index');
});

require __DIR__.'/auth.php';
