<?php

use App\Http\Controllers\AdminRondinController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CheckpointController;
use App\Http\Controllers\CheckpointQuestionController;
use App\Http\Controllers\CheckpointQuestionnaireController;
use App\Http\Controllers\CheckpointScanController;
use App\Http\Controllers\CurrentAreaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuardHomeController;
use App\Http\Controllers\GuardPatrolController;
use App\Http\Controllers\GuardRoundController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::put('current-area', [CurrentAreaController::class, 'update'])->name('current-area.update');

    Route::get('guardia', GuardHomeController::class)->name('guard.home');
    Route::get('guardia/recorridos', [GuardRoundController::class, 'index'])->name('guard.rounds.index');
    Route::match(['get', 'post'], 'guardia/recorridos/{round}/iniciar', [GuardPatrolController::class, 'start'])
        ->name('guard.rounds.start');
    // Compatibilidad: el listado antiguo hacía GET a /guardia/recorridos/{round}
    Route::get('guardia/recorridos/{round}', [GuardPatrolController::class, 'start'])
        ->name('guard.rounds.show');
    Route::get('guardia/patrullas/{patrol}', [GuardPatrolController::class, 'show'])
        ->name('guard.patrols.show');
    Route::post('guardia/patrullas/{patrol}/puntos/{checkpoint}/verificar', [GuardPatrolController::class, 'verifyCheckpoint'])
        ->name('guard.patrols.verify-checkpoint');

    Route::get('scan/{token}', [CheckpointScanController::class, 'show'])
        ->name('checkpoints.scan.show');
    Route::post('scan/{token}', [CheckpointScanController::class, 'store'])
        ->name('checkpoints.scan.store');
    Route::post('scan/{token}/sin-novedad', [CheckpointScanController::class, 'allClear'])
        ->name('checkpoints.scan.all-clear');
    Route::get('scan/{token}/completo', [CheckpointScanController::class, 'complete'])
        ->name('checkpoints.scan.complete');

    Route::middleware('manage.areas')->group(function () {
        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('rounds', RoundController::class)->except(['show']);

        Route::get('rondines', [AdminRondinController::class, 'index'])->name('rondines.index');
        Route::get('rondines/{round}', [AdminRondinController::class, 'showRound'])->name('rondines.rounds.show');
        Route::get('rondines/{round}/patrullas/{patrol}', [AdminRondinController::class, 'showPatrol'])
            ->name('rondines.patrols.show');
        Route::get('rondines/{round}/patrullas/{patrol}/pdf', [AdminRondinController::class, 'downloadPdf'])
            ->name('rondines.patrols.pdf');

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
