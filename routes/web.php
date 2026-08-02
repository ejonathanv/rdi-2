<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CheckpointController;
use App\Http\Controllers\CheckpointQuestionController;
use App\Http\Controllers\CheckpointQuestionnaireController;
use App\Http\Controllers\CheckpointScanController;
use App\Http\Controllers\CurrentAreaController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::put('current-area', [CurrentAreaController::class, 'update'])->name('current-area.update');

    Route::get('scan/{token}', [CheckpointScanController::class, 'show'])
        ->name('checkpoints.scan.show');
    Route::post('scan/{token}', [CheckpointScanController::class, 'store'])
        ->name('checkpoints.scan.store');
    Route::get('scan/{token}/completo', [CheckpointScanController::class, 'complete'])
        ->name('checkpoints.scan.complete');

    Route::middleware('manage.areas')->group(function () {
        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('rounds', RoundController::class)->except(['show']);

        Route::post('rounds/{round}/checkpoints', [CheckpointController::class, 'store'])
            ->name('rounds.checkpoints.store');
        Route::put('checkpoints/{checkpoint}', [CheckpointController::class, 'update'])
            ->name('checkpoints.update');
        Route::delete('checkpoints/{checkpoint}', [CheckpointController::class, 'destroy'])
            ->name('checkpoints.destroy');
        Route::put('rounds/{round}/checkpoints/reorder', [CheckpointController::class, 'reorder'])
            ->name('rounds.checkpoints.reorder');

        Route::get('checkpoints/{checkpoint}/questionnaire', [CheckpointQuestionnaireController::class, 'edit'])
            ->name('checkpoints.questionnaire.edit');
        Route::post('checkpoints/{checkpoint}/questions', [CheckpointQuestionController::class, 'store'])
            ->name('checkpoints.questions.store');
        Route::put('checkpoints/{checkpoint}/questions/reorder', [CheckpointQuestionController::class, 'reorder'])
            ->name('checkpoints.questions.reorder');
        Route::put('questions/{question}', [CheckpointQuestionController::class, 'update'])
            ->name('questions.update');
        Route::delete('questions/{question}', [CheckpointQuestionController::class, 'destroy'])
            ->name('questions.destroy');
    });
});

require __DIR__.'/settings.php';
