<?php

use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RgpdController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlockchainController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\FuelRecordController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\MileageReadingController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleAssignmentController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'project' => config('autochain.name'),
        'author' => config('autochain.author'),
    ]));

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot']);
    Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);
    Route::get('/rgpd/privacy', [RgpdController::class, 'privacyPolicy']);
    Route::post('/auth/wallet/challenge', [AuthController::class, 'walletChallenge']);
    Route::post('/auth/wallet/login', [AuthController::class, 'walletLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/rgpd/export', [RgpdController::class, 'exportMyData']);
        Route::delete('/rgpd/account', [RgpdController::class, 'deleteMyAccount']);
        Route::get('/auth/wallet/nonce', [AuthController::class, 'walletNonce']);
        Route::post('/auth/wallet/link', [AuthController::class, 'linkWallet']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::middleware('role:super_admin,fleet_manager,driver,mechanic,auditor')->group(function () {
            Route::get('/vehicles', [VehicleController::class, 'index']);
            Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
            Route::get('/vehicles/{vehicle}/timeline', [TimelineController::class, 'show']);
            Route::get('/vehicles/{vehicle}/documents', [DocumentController::class, 'index']);
            Route::get('/documents/{document}', [DocumentController::class, 'show']);
            Route::get('/documents/{document}/download', [DocumentController::class, 'download']);

            Route::get('/assignments', [VehicleAssignmentController::class, 'index']);
            Route::get('/maintenances', [MaintenanceController::class, 'index']);
            Route::get('/maintenances/{maintenance}', [MaintenanceController::class, 'show']);
            Route::get('/mileage-readings', [MileageReadingController::class, 'index']);
            Route::get('/fuel-records', [FuelRecordController::class, 'index']);
            Route::get('/vehicles/{vehicle}/fuel-stats', [FuelRecordController::class, 'vehicleStats']);
            Route::get('/alerts', [AlertController::class, 'index']);

            Route::get('/blockchain/records', [BlockchainController::class, 'records']);
            Route::get('/blockchain/config', [BlockchainController::class, 'config']);
            Route::get('/blockchain/sales/pending', [BlockchainController::class, 'pendingSales']);
            Route::get('/blockchain/sales/{pendingSignature}', [BlockchainController::class, 'showPendingSale']);
        });

        Route::middleware('role:super_admin,fleet_manager')->group(function () {
            Route::post('/vehicles', [VehicleController::class, 'store']);
            Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
            Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);

            Route::post('/vehicles/{vehicle}/assignments', [VehicleAssignmentController::class, 'store']);
            Route::post('/assignments/{assignment}/complete', [VehicleAssignmentController::class, 'complete']);

            Route::post('/vehicles/{vehicle}/documents', [DocumentController::class, 'store']);
            Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

            Route::post('/fuel-records', [FuelRecordController::class, 'store']);
            Route::patch('/alerts/{alert}/read', [AlertController::class, 'markAsRead']);
            Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);

            Route::post('/blockchain/sales/prepare', [BlockchainController::class, 'prepareSale']);
            Route::post('/blockchain/sales/initiate', [BlockchainController::class, 'initiateSale']);
            Route::post('/blockchain/records/{record}/confirm', [BlockchainController::class, 'confirm']);
        });

        Route::middleware('role:driver')->group(function () {
            Route::post('/mileage-readings', [MileageReadingController::class, 'store']);
            Route::get('/driver/dashboard', [DriverController::class, 'dashboard']);
            Route::post('/driver/assignments/{assignment}/pickup', [DriverController::class, 'declarePickup']);
            Route::post('/driver/assignments/{assignment}/complete', [DriverController::class, 'completeMission']);
        });

        Route::middleware('role:mechanic')->group(function () {
            Route::post('/maintenances', [MaintenanceController::class, 'store']);
        });

        Route::middleware('role:super_admin,fleet_manager')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
        });

        Route::middleware('role:super_admin')->group(function () {
            Route::apiResource('users', UserController::class)->except(['index']);
            Route::get('/admin/stats', [AdminController::class, 'archiveStats']);
            Route::put('/admin/blockchain', [AdminController::class, 'updateBlockchainConfig']);
            Route::post('/admin/alerts/run', [AdminController::class, 'runAlerts']);
        });

        Route::middleware('role:super_admin,fleet_manager,auditor')->group(function () {
            Route::get('/exports/vehicles.csv', [ExportController::class, 'vehiclesCsv']);
            Route::get('/exports/alerts.csv', [ExportController::class, 'alertsCsv']);
            Route::get('/exports/maintenances.csv', [ExportController::class, 'maintenancesCsv']);
            Route::get('/exports/fleet-report', [ExportController::class, 'fleetReportPdf']);
        });

        Route::middleware('role:super_admin,auditor,fleet_manager')->group(function () {
            Route::post('/blockchain/sales/{pendingSignature}/sign', [BlockchainController::class, 'signSale']);
        });
    });
});
