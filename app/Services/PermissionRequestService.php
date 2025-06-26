<?php

namespace App\Services;

use App\Models\User;
use App\Models\PermissionRequest;
use App\Models\PermissionType;
use App\Models\PermissionBalance;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermissionRequestService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Crea una nueva solicitud de permiso
     */
    public function createRequest(User $user, array $data): PermissionRequest
    {
        return DB::transaction(function () use ($user, $data) {
            // Validar datos adicionales
            $this->validateRequestData($user, $data);

            // Crear la solicitud
            $request = PermissionRequest::create([
                'request_number' => PermissionRequest::generateRequestNumber(),
                'user_id' => $user->id,
                'permission_type_id' => $data['permission_type_id'],
                'start_datetime' => $data['start_datetime'],
                'end_datetime' => $data['end_datetime'],
                'requested_hours' => $this->calculateHours($data['start_datetime'], $data['end_datetime']),
                'reason' => $data['reason'],
                'status' => PermissionRequest::STATUS_DRAFT,
                'metadata' => $this->buildMetadata($data),
                'current_approval_level' => 0,
            ]);

            // Crear aprobaciones pendientes
            $this->createPendingApprovals($request);

            // Registrar en auditoría
            AuditLog::logAction(AuditLog::ACTION_CREATED, $request);

            return $request;
        });
    }

    /**
     * Actualiza una solicitud de permiso
     */
    public function updateRequest(PermissionRequest $request, array $data): PermissionRequest
    {
        if ($request->status !== PermissionRequest::STATUS_DRAFT) {
            throw new \Exception('Solo se pueden editar solicitudes en estado borrador.');
        }

        return DB::transaction(function () use ($request, $data) {
            $oldData = $request->toArray();

            // Validar datos adicionales
            $this->validateRequestData($request->user, $data);

            // Actualizar la solicitud
            $request->update([
                'permission_type_id' => $data['permission_type_id'],
                'start_datetime' => $data['start_datetime'],
                'end_datetime' => $data['end_datetime'],
                'requested_hours' => $this->calculateHours($data['start_datetime'], $data['end_datetime']),
                'reason' => $data['reason'],
                'metadata' => $this->buildMetadata($data),
            ]);

            // Actualizar aprobaciones si cambió el tipo de permiso
            if ($oldData['permission_type_id'] != $data['permission_type_id']) {
                $this->updatePendingApprovals($request);
            }

            // Registrar en auditoría
            AuditLog::logAction(AuditLog::ACTION_UPDATED, $request, $oldData, $request->getChanges());

            return $request->fresh();
        });
    }

    /**
     * Envía una solicitud para aprobación
     */
    public function submitRequest(PermissionRequest $request): PermissionRequest
    {
        if ($request->status !== PermissionRequest::STATUS_DRAFT) {
            throw new \Exception('La solicitud ya ha sido enviada.');
        }

        return DB::transaction(function () use ($request) {
            // Validar que la solicitud esté completa
            $this->validateCompleteRequest($request);

            // Verificar saldo disponible
            $this->validatePermissionBalance($request);

            // Actualizar estado
            $request->update([
                'status' => PermissionRequest::STATUS_PENDING_SUPERVISOR,
                'submitted_at' => now(),
                'current_approval_level' => 1,
            ]);

            // Activar la primera aprobación
            $firstApproval = $request->approvals()
                ->where('approval_level', Approval::LEVEL_SUPERVISOR)
                ->first();

            if ($firstApproval) {
                $firstApproval->update(['status' => Approval::STATUS_PENDING]);
                
                // Enviar notificación al supervisor
                $this->notificationService->sendApprovalNotification(
                    $firstApproval->approver,
                    $request
                );
            }

            // Registrar en auditoría
            AuditLog::logAction(AuditLog::ACTION_SUBMITTED, $request);

            return $request->fresh();
        });
    }

    /**
     * Cancela una solicitud de permiso
     */
    public function cancelRequest(PermissionRequest $request): PermissionRequest
    {
        $allowedStatuses = [
            PermissionRequest::STATUS_DRAFT,
            PermissionRequest::STATUS_PENDING_SUPERVISOR,
            PermissionRequest::STATUS_PENDING_HR,
            PermissionRequest::STATUS_APPROVED,
        ];

        if (!in_array($request->status, $allowedStatuses)) {
            throw new \Exception('No se puede cancelar una solicitud en estado: ' . $request->status);
        }

        return DB::transaction(function () use ($request) {
            $oldStatus = $request->status;

            // Si estaba aprobada, devolver las horas al saldo
            if ($request->status === PermissionRequest::STATUS_APPROVED) {
                $this->returnHoursToBalance($request);
            }

            // Actualizar estado
            $request->update(['status' => PermissionRequest::STATUS_CANCELLED]);

            // Cancelar aprobaciones pendientes
            $request->approvals()
                ->where('status', Approval::STATUS_PENDING)
                ->update(['status' => Approval::STATUS_REJECTED, 'comments' => 'Solicitud cancelada por el usuario']);

            // Enviar notificaciones si había aprobadores pendientes
            if (in_array($oldStatus, [PermissionRequest::STATUS_PENDING_SUPERVISOR, PermissionRequest::STATUS_PENDING_HR])) {
                $this->notificationService->sendCancellationNotification($request);
            }

            // Registrar en auditoría
            AuditLog::logAction(AuditLog::ACTION_CANCELLED, $request);

            return $request->fresh();
        });
    }

    /**
     * Valida los datos de la solicitud
     */
    private function validateRequestData(User $user, array $data): void
    {
        $permissionType = PermissionType::findOrFail($data['permission_type_id']);
        
        // Validar fechas
        $startDate = Carbon::parse($data['start_datetime']);
        $endDate = Carbon::parse($data['end_datetime']);
        
        if ($startDate->isPast()) {
            throw new \Exception('La fecha de inicio no puede ser en el pasado.');
        }

        if ($endDate->lte($startDate)) {
            throw new \Exception('La fecha de fin debe ser posterior a la fecha de inicio.');
        }

        // Validar horario laboral
        $this->validateWorkingHours($startDate, $endDate);

        // Validar horas según el tipo de permiso
        $hours = $this->calculateHours($data['start_datetime'], $data['end_datetime']);
        $this->validatePermissionHours($permissionType, $hours, $startDate);

        // Validar frecuencia según el tipo de permiso
        $this->validatePermissionFrequency($user, $permissionType, $startDate);
    }

    /**
     * Valida que la solicitud esté completa antes del envío
     */
    private function validateCompleteRequest(PermissionRequest $request): void
    {
        $permissionType = $request->permissionType;

        // Verificar documentos requeridos
        if ($permissionType->requires_document) {
            $requiredDocs = $permissionType->getRequiredDocuments();
            $uploadedDocs = $request->documents->pluck('document_type')->toArray();
            
            foreach ($requiredDocs as $requiredDoc) {
                if (!in_array($requiredDoc, $uploadedDocs)) {
                    throw new \Exception("Falta subir el documento requerido: {$requiredDoc}");
                }
            }
        }

        // Validar que tenga supervisor asignado
        if (!$request->user->immediate_supervisor_id) {
            throw new \Exception('El usuario no tiene un supervisor asignado. Contacte a RRHH.');
        }
    }

    /**
     * Valida el saldo de permisos disponible
     */
    private function validatePermissionBalance(PermissionRequest $request): void
    {
        $startDate = Carbon::parse($request->start_datetime);
        
        $balance = PermissionBalance::getOrCreateBalance(
            $request->user_id,
            $request->permission_type_id,
            $startDate->year,
            $startDate->month
        );

        if (!$balance->hasSufficientHours($request->requested_hours)) {
            throw new \Exception(
                "Saldo insuficiente. Disponible: {$balance->remaining_hours} horas, " .
                "Solicitado: {$request->requested_hours} horas."
            );
        }
    }

    /**
     * Valida horario laboral
     */
    private function validateWorkingHours(Carbon $startDate, Carbon $endDate): void
    {
        $workStart = config('app.working_hours.start', '08:00');
        $workEnd = config('app.working_hours.end', '17:00');

        // Verificar que las fechas estén en días laborables
        if ($startDate->isWeekend() || $endDate->isWeekend()) {
            throw new \Exception('Los permisos solo pueden solicitarse para días laborables.');
        }

        // Verificar horario laboral
        $startTime = $startDate->format('H:i');
        $endTime = $endDate->format('H:i');

        if ($startTime < $workStart || $endTime > $workEnd) {
            throw new \Exception("Los permisos deben estar dentro del horario laboral ({$workStart} - {$workEnd}).");
        }
    }

    /**
     * Valida las horas según el tipo de permiso
     */
    private function validatePermissionHours(PermissionType $permissionType, float $hours, Carbon $date): void
    {
        $errors = $permissionType->validateHours($hours, $date->month, $date->year);
        
        if (!empty($errors)) {
            throw new \Exception(implode(' ', $errors));
        }
    }

    /**
     * Valida la frecuencia de solicitudes según el tipo
     */
    private function validatePermissionFrequency(User $user, PermissionType $permissionType, Carbon $date): void
    {
        $rules = $permissionType->validation_rules ?? [];

        // Validar límite de veces por mes
        if (isset($rules['max_times_per_month'])) {
            $requestsThisMonth = $user->permissionRequests()
                ->where('permission_type_id', $permissionType->id)
                ->whereYear('start_datetime', $date->year)
                ->whereMonth('start_datetime', $date->month)
                ->whereNotIn('status', [PermissionRequest::STATUS_CANCELLED, PermissionRequest::STATUS_REJECTED])
                ->count();

            if ($requestsThisMonth >= $rules['max_times_per_month']) {
                throw new \Exception("Ya ha alcanzado el límite de {$rules['max_times_per_month']} solicitudes por mes para este tipo de permiso.");
            }
        }

        // Validar frecuencia específica para algunos tipos
        switch ($permissionType->code) {
            case PermissionType::GRAVIDEZ:
                $this->validatePregnancyPermissionFrequency($user, $date);
                break;
                
            case PermissionType::LACTANCIA:
                $this->validateLactationPermissionFrequency($user, $date);
                break;
        }
    }

    /**
     * Valida frecuencia para permisos por gravidez
     */
    private function validatePregnancyPermissionFrequency(User $user, Carbon $date): void
    {
        $existingThisMonth = $user->permissionRequests()
            ->whereHas('permissionType', function ($q) {
                $q->where('code', PermissionType::GRAVIDEZ);
            })
            ->whereYear('start_datetime', $date->year)
            ->whereMonth('start_datetime', $date->month)
            ->whereNotIn('status', [PermissionRequest::STATUS_CANCELLED, PermissionRequest::STATUS_REJECTED])
            ->exists();

        if ($existingThisMonth) {
            throw new \Exception('Solo se permite un permiso por gravidez al mes.');
        }
    }

    /**
     * Valida frecuencia para permisos por lactancia
     */
    private function validateLactationPermissionFrequency(User $user, Carbon $date): void
    {
        $existingToday = $user->permissionRequests()
            ->whereHas('permissionType', function ($q) {
                $q->where('code', PermissionType::LACTANCIA);
            })
            ->whereDate('start_datetime', $date->toDateString())
            ->whereNotIn('status', [PermissionRequest::STATUS_CANCELLED, PermissionRequest::STATUS_REJECTED])
            ->exists();

        if ($existingToday) {
            throw new \Exception('Solo se permite un permiso por lactancia al día.');
        }
    }

    /**
     * Calcula las horas entre dos fechas
     */
    private function calculateHours(string $startDateTime, string $endDateTime): float
    {
        $start = Carbon::parse($startDateTime);
        $end = Carbon::parse($endDateTime);
        
        return $start->diffInHours($end, true);
    }

    /**
     * Construye metadata adicional
     */
    private function buildMetadata(array $data): array
    {
        return [
            'priority' => $data['priority'] ?? 2,
            'is_urgent' => $data['is_urgent'] ?? false,
            'additional_info' => $data['additional_info'] ?? null,
            'created_from_ip' => request()->ip(),
            'created_user_agent' => request()->userAgent(),
        ];
    }

    /**
     * Crea las aprobaciones pendientes iniciales
     */
    private function createPendingApprovals(PermissionRequest $request): void
    {
        $user = $request->user;

        // Nivel 1: Jefe Inmediato
        if ($user->immediate_supervisor_id) {
            Approval::create([
                'permission_request_id' => $request->id,
                'approver_id' => $user->immediate_supervisor_id,
                'approval_level' => Approval::LEVEL_SUPERVISOR,
                'status' => Approval::STATUS_PENDING,
            ]);
        }

        // Nivel 2: RRHH
        $hrUser = User::whereHas('role', function ($query) {
            $query->where('name', 'jefe_rrhh');
        })->first();

        if ($hrUser) {
            Approval::create([
                'permission_request_id' => $request->id,
                'approver_id' => $hrUser->id,
                'approval_level' => Approval::LEVEL_HR,
                'status' => Approval::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Actualiza las aprobaciones pendientes
     */
    private function updatePendingApprovals(PermissionRequest $request): void
    {
        // Eliminar aprobaciones existentes que aún no han sido procesadas
        $request->approvals()
            ->where('status', Approval::STATUS_PENDING)
            ->delete();

        // Recrear aprobaciones
        $this->createPendingApprovals($request);
    }

    /**
     * Devuelve horas al saldo cuando se cancela una solicitud aprobada
     */
    private function returnHoursToBalance(PermissionRequest $request): void
    {
        $startDate = Carbon::parse($request->start_datetime);
        
        $balance = PermissionBalance::getOrCreateBalance(
            $request->user_id,
            $request->permission_type_id,
            $startDate->year,
            $startDate->month
        );

        $balance->returnHours($request->requested_hours);
    }
}