<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        // 'canRegister' => Features::enabled(Features::registration()),
        'canRegister' => false,
    ]);
})->name('home');

// Temporarily disable fortify registration routes while keeping the feature enabled
Route::get('register', function () {
    return Inertia::render('Welcome', [
        'canRegister' => false,
    ]);
})->name('register');
Route::post('register', function () {
    return Inertia::render('Welcome', [
        'canRegister' => false,
    ]);
})->name('register.store');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
