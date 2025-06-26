<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BiometricController;
use App\Http\Controllers\SystemConfigurationController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckRolePermission;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación (Laravel Breeze/Fortify)
require __DIR__.'/auth.php';

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/api-data', [DashboardController::class, 'apiData'])->name('dashboard.api-data');

    // Perfil de usuario
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
        Route::post('/upload-avatar', [ProfileController::class, 'uploadAvatar'])->name('upload-avatar');
    });

    // Solicitudes de permisos
    Route::prefix('permissions')->name('permissions.')->group(function () {
        // Rutas básicas CRUD
        Route::get('/', [PermissionRequestController::class, 'index'])->name('index');
        Route::get('/create', [PermissionRequestController::class, 'create'])
            ->middleware(CheckRolePermission::class . ':create_permission_request')
            ->name('create');
        Route::post('/', [PermissionRequestController::class, 'store'])
            ->middleware(CheckRolePermission::class . ':create_permission_request')
            ->name('store');
        Route::get('/{permission}', [PermissionRequestController::class, 'show'])->name('show');
        Route::get('/{permission}/edit', [PermissionRequestController::class, 'edit'])->name('edit');
        Route::put('/{permission}', [PermissionRequestController::class, 'update'])->name('update');
        Route::delete('/{permission}', [PermissionRequestController::class, 'destroy'])->name('destroy');

        // Acciones especiales
        Route::post('/{permission}/submit', [PermissionRequestController::class, 'submit'])->name('submit');
        Route::post('/{permission}/cancel', [PermissionRequestController::class, 'cancel'])->name('cancel');

        // Gestión de documentos
        Route::post('/{permission}/documents', [PermissionRequestController::class, 'uploadDocument'])->name('upload-document');
        Route::get('/documents/{document}/download', [PermissionRequestController::class, 'downloadDocument'])->name('download-document');
        Route::delete('/documents/{document}', [PermissionRequestController::class, 'deleteDocument'])->name('delete-document');

        // APIs para formularios dinámicos
        Route::get('/api/permission-types/{permissionType}', [PermissionRequestController::class, 'getPermissionTypeDetails'])->name('api.permission-type');
        Route::post('/api/calculate-hours', [PermissionRequestController::class, 'calculateHours'])->name('api.calculate-hours');
        Route::get('/api/user-balance', [PermissionRequestController::class, 'getUserBalance'])->name('api.user-balance');
    });

    // Aprobaciones
    Route::prefix('approvals')->name('approvals.')->middleware(CheckRolePermission::class . ':approve_level_1,approve_level_2')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/{permission}', [ApprovalController::class, 'show'])->name('show');
        Route::post('/{permission}/process', [ApprovalController::class, 'process'])->name('process');
        Route::post('/bulk-approve', [ApprovalController::class, 'bulkApprove'])->name('bulk-approve');
        Route::get('/{permission}/history', [ApprovalController::class, 'history'])->name('history');
        Route::get('/api/pending-count', [ApprovalController::class, 'getPendingCount'])->name('api.pending-count');
        Route::get('/export', [ApprovalController::class, 'export'])->name('export');
    });

    // Notificaciones
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::get('/api/unread-count', [NotificationController::class, 'getUnreadCount'])->name('api.unread-count');
    });

    // Sistema biométrico
    Route::prefix('biometric')->name('biometric.')->group(function () {
        Route::get('/', [BiometricController::class, 'index'])->name('index');
        Route::post('/record', [BiometricController::class, 'recordAttendance'])->name('record');
        Route::post('/permission-start/{permission}', [BiometricController::class, 'startPermission'])->name('start-permission');
        Route::post('/permission-end/{permission}', [BiometricController::class, 'endPermission'])->name('end-permission');
        Route::get('/history', [BiometricController::class, 'history'])->name('history');
        Route::get('/api/active-permissions', [BiometricController::class, 'getActivePermissions'])->name('api.active-permissions');
    });

    // Reportes
    Route::prefix('reports')->name('reports.')->middleware(CheckRolePermission::class . ':view_reports,view_department_reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/permissions', [ReportController::class, 'permissionsReport'])->name('permissions');
        Route::get('/usage', [ReportController::class, 'usageReport'])->name('usage');
        Route::get('/balances', [ReportController::class, 'balancesReport'])->name('balances');
        Route::get('/department', [ReportController::class, 'departmentReport'])->name('department');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
        Route::post('/custom', [ReportController::class, 'customReport'])->name('custom');
    });

    // Gestión de usuarios (solo para administradores y RRHH)
    Route::prefix('users')->name('users.')->middleware(CheckRolePermission::class . ':manage_users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/activate', [UserController::class, 'activate'])->name('activate');
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::get('/api/subordinates/{user}', [UserController::class, 'getSubordinates'])->name('api.subordinates');
    });

    // Configuración del sistema (solo administradores)
    Route::prefix('system')->name('system.')->middleware(CheckRolePermission::class . ':manage_system_config')->group(function () {
        Route::get('/settings', [SystemConfigurationController::class, 'index'])->name('settings');
        Route::put('/settings', [SystemConfigurationController::class, 'update'])->name('settings.update');
        Route::get('/permission-types', [SystemConfigurationController::class, 'permissionTypes'])->name('permission-types');
        Route::post('/permission-types', [SystemConfigurationController::class, 'storePermissionType'])->name('permission-types.store');
        Route::put('/permission-types/{permissionType}', [SystemConfigurationController::class, 'updatePermissionType'])->name('permission-types.update');
        Route::delete('/permission-types/{permissionType}', [SystemConfigurationController::class, 'destroyPermissionType'])->name('permission-types.destroy');
        Route::get('/departments', [SystemConfigurationController::class, 'departments'])->name('departments');
        Route::get('/roles', [SystemConfigurationController::class, 'roles'])->name('roles');
        Route::get('/audit-logs', [SystemConfigurationController::class, 'auditLogs'])->name('audit-logs');
        Route::post('/backup', [SystemConfigurationController::class, 'createBackup'])->name('backup');
        Route::get('/maintenance', [SystemConfigurationController::class, 'maintenance'])->name('maintenance');
    });
});

// Rutas API adicionales (sin middleware de verificación)
Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
    // APIs para aplicaciones móviles o integraciones externas
    Route::get('/user', function () {
        return auth()->user();
    });
    
    Route::apiResource('permissions', PermissionRequestController::class);
    Route::apiResource('approvals', ApprovalController::class);
    
    // APIs específicas para integración biométrica
    Route::post('/biometric/verify', [BiometricController::class, 'verifyFingerprint']);
    Route::post('/biometric/register', [BiometricController::class, 'registerFingerprint']);
    
    // APIs para notificaciones push
    Route::post('/notifications/register-device', [NotificationController::class, 'registerDevice']);
    Route::post('/notifications/send-push', [NotificationController::class, 'sendPushNotification']);
});

// Rutas para webhooks y integraciones externas
Route::prefix('webhooks')->group(function () {
    // Webhook para firma digital Perú
    Route::post('/digital-signature', function () {
        // Implementar webhook para validación de firmas digitales
    })->name('webhooks.digital-signature');
    
    // Webhook para sistema biométrico
    Route::post('/biometric-device', function () {
        // Implementar webhook para dispositivos biométricos
    })->name('webhooks.biometric');
});

// Rutas de emergencia y mantenimiento
Route::prefix('emergency')->middleware(['auth', CheckRolePermission::class . ':manage_system_config'])->group(function () {
    Route::post('/disable-system', [SystemConfigurationController::class, 'disableSystem'])->name('emergency.disable');
    Route::post('/enable-system', [SystemConfigurationController::class, 'enableSystem'])->name('emergency.enable');
    Route::post('/force-approve/{permission}', [ApprovalController::class, 'forceApprove'])->name('emergency.force-approve');
});

// Ruta de fallback para errores 404
Route::fallback(function () {
    return view('errors.404');
});