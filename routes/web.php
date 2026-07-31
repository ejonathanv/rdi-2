<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CurrentAreaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::put('current-area', [CurrentAreaController::class, 'update'])->name('current-area.update');

    Route::middleware('manage.areas')->group(function () {
        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
    });
});

require __DIR__.'/settings.php';
