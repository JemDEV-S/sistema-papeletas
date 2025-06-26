<?php

namespace App\Http\Controllers;

use App\Models\BiometricRecord;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BiometricController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display the biometric control interface.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Obtener último registro del día
        $lastRecord = BiometricRecord::getLastRecordForUserToday($user->id);
        
        // Obtener permisos activos
        $activePermissions = $user->permissionRequests()
            ->with('permissionType')
            ->where('status', PermissionRequest::STATUS_APPROVED)
            ->where('start_datetime', '<=', now())
            ->where('end_datetime', '>=', now())
            ->get();

        // Obtener próximos permisos (próximas 24 horas)
        $upcomingPermissions = $user->permissionRequests()
            ->with('permissionType')
            ->where('status', PermissionRequest::STATUS_APPROVED)
            ->where('start_datetime', '>', now())
            ->where('start_datetime', '<=', now()->addHours(24))
            ->orderBy('start_datetime')
            ->get();

        // Estadísticas del día
        $todayStats = $this->getTodayStats($user);

        return view('biometric.index', compact(
            'lastRecord', 
            'activePermissions', 
            'upcomingPermissions',
            'todayStats'
        ));
    }

    /**
     * Record attendance (entry/exit).
     */
    public function recordAttendance(Request $request): JsonResponse
    {
        $request->validate([
            'biometric_data' => 'required|string',
            'device_id' => 'required|string|max:50',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = auth()->user();
        
        return DB::transaction(function () use ($request, $user) {
            try {
                // Verificar último registro para determinar tipo
                $lastRecord = BiometricRecord::getLastRecordForUserToday($user->id);
                $recordType = $this->determineRecordType($lastRecord);

                // Validar que puede hacer este tipo de registro
                $validation = $this->validateAttendanceRecord($user, $recordType);
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => $validation['message']
                    ], 400);
                }

                // Crear registro biométrico
                $record = BiometricRecord::create([
                    'user_id' => $user->id,
                    'record_type' => $recordType,
                    'timestamp' => now(),
                    'biometric_data_hash' => BiometricRecord::generateBiometricHash($request->biometric_data),
                    'device_id' => $request->device_id,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'is_valid' => true,
                ]);

                // Validar secuencia del día
                $sequenceErrors = $record->validateDailySequence();
                if (!empty($sequenceErrors)) {
                    $record->update(['is_valid' => false]);
                }

                // Registrar en auditoría
                AuditLog::logAction(AuditLog::ACTION_BIOMETRIC_RECORD, $record, null, [
                    'record_type' => $recordType,
                    'device_id' => $request->device_id,
                    'has_location' => $record->hasLocation(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $this->getRecordMessage($recordType),
                    'record' => [
                        'id' => $record->id,
                        'type' => $recordType,
                        'type_name' => $record->record_type_name,
                        'timestamp' => $record->timestamp->format('d/m/Y H:i:s'),
                        'is_valid' => $record->is_valid,
                        'sequence_errors' => $sequenceErrors,
                    ]
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar asistencia: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Start permission execution.
     */
    public function startPermission(Request $request, PermissionRequest $permission): JsonResponse
    {
        $request->validate([
            'biometric_data' => 'required|string',
            'device_id' => 'required|string|max:50',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = auth()->user();

        // Verificar que el permiso pertenece al usuario
        if ($permission->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para usar este permiso.'
            ], 403);
        }

        // Verificar que el permiso está aprobado y activo
        if (!$permission->isApproved() || !$permission->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'El permiso no está activo o no ha sido aprobado.'
            ], 400);
        }

        return DB::transaction(function () use ($request, $permission, $user) {
            try {
                // Crear registro de inicio de permiso
                $record = BiometricRecord::create([
                    'user_id' => $user->id,
                    'permission_request_id' => $permission->id,
                    'record_type' => BiometricRecord::TYPE_PERMISSION_START,
                    'timestamp' => now(),
                    'biometric_data_hash' => BiometricRecord::generateBiometricHash($request->biometric_data),
                    'device_id' => $request->device_id,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'is_valid' => true,
                ]);

                // Actualizar estado del permiso
                $permission->update(['status' => PermissionRequest::STATUS_IN_EXECUTION]);

                // Registrar en auditoría
                AuditLog::logAction(AuditLog::ACTION_BIOMETRIC_RECORD, $record, null, [
                    'action' => 'permission_start',
                    'permission_request_id' => $permission->id,
                    'permission_type' => $permission->permissionType->name,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Inicio de permiso registrado exitosamente.',
                    'permission' => [
                        'id' => $permission->id,
                        'number' => $permission->request_number,
                        'type' => $permission->permissionType->name,
                        'started_at' => $record->timestamp->format('d/m/Y H:i:s'),
                    ]
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al iniciar permiso: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * End permission execution.
     */
    public function endPermission(Request $request, PermissionRequest $permission): JsonResponse
    {
        $request->validate([
            'biometric_data' => 'required|string',
            'device_id' => 'required|string|max:50',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = auth()->user();

        // Verificar que el permiso pertenece al usuario
        if ($permission->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para usar este permiso.'
            ], 403);
        }

        // Verificar que el permiso está en ejecución
        if ($permission->status !== PermissionRequest::STATUS_IN_EXECUTION) {
            return response()->json([
                'success' => false,
                'message' => 'El permiso no está en ejecución.'
            ], 400);
        }

        return DB::transaction(function () use ($request, $permission, $user) {
            try {
                // Crear registro de fin de permiso
                $record = BiometricRecord::create([
                    'user_id' => $user->id,
                    'permission_request_id' => $permission->id,
                    'record_type' => BiometricRecord::TYPE_PERMISSION_END,
                    'timestamp' => now(),
                    'biometric_data_hash' => BiometricRecord::generateBiometricHash($request->biometric_data),
                    'device_id' => $request->device_id,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'is_valid' => true,
                ]);

                // Calcular tiempo utilizado
                $startRecord = BiometricRecord::where('permission_request_id', $permission->id)
                    ->where('record_type', BiometricRecord::TYPE_PERMISSION_START)
                    ->latest()
                    ->first();

                $actualHours = 0;
                if ($startRecord) {
                    $actualHours = $startRecord->timestamp->diffInHours($record->timestamp, true);
                }

                // Actualizar estado del permiso
                $permission->update([
                    'status' => PermissionRequest::STATUS_COMPLETED,
                    'metadata' => array_merge($permission->metadata ?? [], [
                        'actual_hours_used' => $actualHours,
                        'completed_at' => $record->timestamp->toISOString(),
                    ])
                ]);

                // Verificar si excedió el tiempo
                $isOvertime = $actualHours > $permission->requested_hours;
                if ($isOvertime) {
                    // Notificar exceso de tiempo
                    $this->notificationService->sendLowBalanceNotification(
                        $user,
                        'Exceso de tiempo en permiso',
                        $actualHours - $permission->requested_hours
                    );
                }

                // Registrar en auditoría
                AuditLog::logAction(AuditLog::ACTION_BIOMETRIC_RECORD, $record, null, [
                    'action' => 'permission_end',
                    'permission_request_id' => $permission->id,
                    'actual_hours' => $actualHours,
                    'requested_hours' => $permission->requested_hours,
                    'is_overtime' => $isOvertime,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Fin de permiso registrado exitosamente.',
                    'permission' => [
                        'id' => $permission->id,
                        'number' => $permission->request_number,
                        'type' => $permission->permissionType->name,
                        'ended_at' => $record->timestamp->format('d/m/Y H:i:s'),
                        'actual_hours' => $actualHours,
                        'requested_hours' => $permission->requested_hours,
                        'is_overtime' => $isOvertime,
                    ]
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al finalizar permiso: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Display biometric history.
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        
        $query = BiometricRecord::byUser($user->id)->with('permissionRequest.permissionType');

        // Filtros
        if ($request->filled('date_from')) {
            $query->whereDate('timestamp', '>=', $request->date_from);
        } else {
            // Por defecto, últimos 30 días
            $query->whereDate('timestamp', '>=', now()->subDays(30));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('timestamp', '<=', $request->date_to);
        }

        if ($request->filled('record_type')) {
            $query->byType($request->record_type);
        }

        if ($request->filled('device_id')) {
            $query->byDevice($request->device_id);
        }

        $records = $query->orderBy('timestamp', 'desc')->paginate(20);

        // Estadísticas
        $stats = $this->getHistoryStats($user, $request);

        return view('biometric.history', compact('records', 'stats'));
    }

    /**
     * Get active permissions for AJAX.
     */
    public function getActivePermissions(): JsonResponse
    {
        $user = auth()->user();
        
        $permissions = $user->permissionRequests()
            ->with('permissionType')
            ->where('status', PermissionRequest::STATUS_APPROVED)
            ->where('start_datetime', '<=', now())
            ->where('end_datetime', '>=', now())
            ->get()
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'number' => $permission->request_number,
                    'type' => $permission->permissionType->name,
                    'start_datetime' => $permission->start_datetime->format('d/m/Y H:i'),
                    'end_datetime' => $permission->end_datetime->format('d/m/Y H:i'),
                    'hours' => $permission->requested_hours,
                    'can_start' => $permission->status !== PermissionRequest::STATUS_IN_EXECUTION,
                ];
            });

        return response()->json($permissions);
    }

    /**
     * Verify fingerprint (for biometric device integration).
     */
    public function verifyFingerprint(Request $request): JsonResponse
    {
        $request->validate([
            'biometric_data' => 'required|string',
            'device_id' => 'required|string',
        ]);

        // Simular verificación de huella digital
        // En un sistema real, esto se integraría con el SDK del dispositivo biométrico
        
        $isValid = $this->simulateFingerprintVerification($request->biometric_data);
        
        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Huella digital no reconocida.'
            ], 401);
        }

        // Obtener información del usuario (simulado)
        $user = auth()->user();
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'dni' => $user->dni,
                'department' => $user->department->name ?? null,
            ],
            'last_record' => BiometricRecord::getLastRecordForUserToday($user->id)?->record_type_name,
        ]);
    }

    /**
     * Register fingerprint template.
     */
    public function registerFingerprint(Request $request): JsonResponse
    {
        $this->authorize('manage_users');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'biometric_template' => 'required|string',
            'finger_index' => 'required|integer|between:1,10',
        ]);

        // En un sistema real, aquí se guardaría el template biométrico
        // en una base de datos segura o en el dispositivo biométrico
        
        return response()->json([
            'success' => true,
            'message' => 'Huella digital registrada exitosamente.',
            'template_id' => uniqid('tpl_')
        ]);
    }

    /**
     * Get attendance summary.
     */
    public function getAttendanceSummary(Request $request): JsonResponse
    {
        $user = auth()->user();
        $date = $request->get('date', now()->toDateString());
        
        $records = BiometricRecord::byUser($user->id)
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp')
            ->get();

        $summary = [
            'date' => $date,
            'records' => $records->map(function ($record) {
                return [
                    'type' => $record->record_type,
                    'type_name' => $record->record_type_name,
                    'timestamp' => $record->timestamp->format('H:i:s'),
                    'is_valid' => $record->is_valid,
                    'has_permission' => !is_null($record->permission_request_id),
                    'permission_type' => $record->permissionRequest?->permissionType->name,
                ];
            }),
            'total_hours' => $this->calculateDailyHours($records),
            'has_issues' => $records->where('is_valid', false)->count() > 0,
        ];

        return response()->json($summary);
    }

    // Métodos privados de apoyo

    private function determineRecordType(?BiometricRecord $lastRecord): string
    {
        if (!$lastRecord) {
            return BiometricRecord::TYPE_ENTRY;
        }

        return match($lastRecord->record_type) {
            BiometricRecord::TYPE_ENTRY => BiometricRecord::TYPE_EXIT,
            BiometricRecord::TYPE_EXIT => BiometricRecord::TYPE_ENTRY,
            BiometricRecord::TYPE_PERMISSION_START => BiometricRecord::TYPE_PERMISSION_END,
            BiometricRecord::TYPE_PERMISSION_END => BiometricRecord::TYPE_ENTRY,
            default => BiometricRecord::TYPE_ENTRY
        };
    }

    private function validateAttendanceRecord(User $user, string $recordType): array
    {
        $workStart = config('app.working_hours.start', '08:00');
        $workEnd = config('app.working_hours.end', '17:00');
        $currentTime = now()->format('H:i');

        // Validar horario laboral para entrada/salida normal
        if (in_array($recordType, [BiometricRecord::TYPE_ENTRY, BiometricRecord::TYPE_EXIT])) {
            if ($recordType === BiometricRecord::TYPE_ENTRY && $currentTime > $workEnd) {
                return [
                    'valid' => false,
                    'message' => 'No se puede registrar entrada fuera del horario laboral.'
                ];
            }
        }

        return ['valid' => true];
    }

    private function getRecordMessage(string $recordType): string
    {
        return match($recordType) {
            BiometricRecord::TYPE_ENTRY => 'Entrada registrada exitosamente.',
            BiometricRecord::TYPE_EXIT => 'Salida registrada exitosamente.',
            BiometricRecord::TYPE_PERMISSION_START => 'Inicio de permiso registrado.',
            BiometricRecord::TYPE_PERMISSION_END => 'Fin de permiso registrado.',
            default => 'Registro biométrico completado.'
        };
    }

    private function getTodayStats(User $user): array
    {
        $records = BiometricRecord::byUser($user->id)->today()->get();
        
        return [
            'total_records' => $records->count(),
            'entry_time' => $records->where('record_type', BiometricRecord::TYPE_ENTRY)->first()?->timestamp,
            'exit_time' => $records->where('record_type', BiometricRecord::TYPE_EXIT)->last()?->timestamp,
            'permission_records' => $records->whereIn('record_type', [
                BiometricRecord::TYPE_PERMISSION_START,
                BiometricRecord::TYPE_PERMISSION_END
            ])->count(),
            'total_hours' => $this->calculateDailyHours($records),
            'issues' => $records->where('is_valid', false)->count(),
        ];
    }

    private function getHistoryStats(User $user, Request $request): array
    {
        $query = BiometricRecord::byUser($user->id);
        
        if ($request->filled('date_from')) {
            $query->whereDate('timestamp', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('timestamp', '<=', $request->date_to);
        }

        $records = $query->get();
        
        return [
            'total_records' => $records->count(),
            'by_type' => $records->groupBy('record_type')->map->count(),
            'valid_records' => $records->where('is_valid', true)->count(),
            'invalid_records' => $records->where('is_valid', false)->count(),
            'with_permission' => $records->whereNotNull('permission_request_id')->count(),
        ];
    }

    private function calculateDailyHours($records): float
    {
        $entryTime = $records->where('record_type', BiometricRecord::TYPE_ENTRY)->first()?->timestamp;
        $exitTime = $records->where('record_type', BiometricRecord::TYPE_EXIT)->last()?->timestamp;
        
        if (!$entryTime || !$exitTime) {
            return 0;
        }
        
        return $entryTime->diffInHours($exitTime, true);
    }

    private function simulateFingerprintVerification(string $biometricData): bool
    {
        // Simulación: siempre retorna true para propósitos de desarrollo
        // En un sistema real, esto verificaría contra templates almacenados
        return true;
    }
}