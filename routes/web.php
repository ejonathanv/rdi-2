<?php

use App\Http\Controllers\AdminIncidentController;
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
use App\Http\Controllers\IncidentCategoryController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PanicAlertController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('landingpage', LandingPageController::class)->name('landingpage');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::put('current-area', [CurrentAreaController::class, 'update'])->name('current-area.update');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])
        ->name('push-subscriptions.destroy');

    Route::get('guardia', GuardHomeController::class)->name('guard.home');
    Route::post('guardia/panico', [PanicAlertController::class, 'store'])
        ->middleware('throttle:panic')
        ->name('panic.store');
    Route::get('guardia/incidencias/crear', [IncidentController::class, 'create'])
        ->name('incidents.create');
    Route::post('guardia/incidencias', [IncidentController::class, 'store'])
        ->name('incidents.store');
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
    Route::get('scan/{token}/incidencia', [IncidentController::class, 'createFromScan'])
        ->name('checkpoints.scan.incident');
    Route::post('scan/{token}', [CheckpointScanController::class, 'store'])
        ->name('checkpoints.scan.store');
    Route::post('scan/{token}/sin-novedad', [CheckpointScanController::class, 'allClear'])
        ->name('checkpoints.scan.all-clear');
    Route::get('scan/{token}/completo', [CheckpointScanController::class, 'complete'])
        ->name('checkpoints.scan.complete');

    Route::middleware('view.operations')->group(function () {
        Route::get('rondines', [AdminRondinController::class, 'index'])->name('rondines.index');
        Route::get('rondines/{round}', [AdminRondinController::class, 'showRound'])->name('rondines.rounds.show');
        Route::get('rondines/{round}/patrullas/{patrol}', [AdminRondinController::class, 'showPatrol'])
            ->name('rondines.patrols.show');
        Route::get('rondines/{round}/patrullas/{patrol}/pdf', [AdminRondinController::class, 'downloadPdf'])
            ->name('rondines.patrols.pdf');
        Route::patch('rondines/{round}/patrullas/{patrol}/visitas/{visit}/urgente-atendido', [AdminRondinController::class, 'resolveUrgentVisit'])
            ->name('rondines.visits.resolve-urgent');

        Route::get('incidencias', [AdminIncidentController::class, 'index'])->name('incidencias.index');
        Route::get('incidencias/{incident}', [AdminIncidentController::class, 'show'])->name('incidencias.show');
        Route::patch('incidencias/{incident}/estado', [AdminIncidentController::class, 'updateStatus'])
            ->name('incidencias.status');

        Route::get('reportes/volumen-de-incidencias', [ReportController::class, 'volumen'])
            ->name('reportes.volumen');
        Route::get('reportes/tiempos-de-atencion', [ReportController::class, 'tiempos'])
            ->name('reportes.tiempos');
        Route::get('reportes/puntos-criticos', [ReportController::class, 'puntosCriticos'])
            ->name('reportes.puntos-criticos');
    });

    Route::middleware('manage.areas')->group(function () {
        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('rounds', RoundController::class)->except(['show']);
        Route::resource('incident-categories', IncidentCategoryController::class)
            ->except(['show']);

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
