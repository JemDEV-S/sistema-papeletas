<?php

namespace App\Services;

use App\Models\User;
use App\Models\PermissionRequest;
use App\Models\Approval;
use App\Models\PermissionBalance;
use App\Models\AuditLog;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Aprueba una solicitud de permiso
     */
    public function approveRequest(PermissionRequest $request, User $approver, string $comments = ''): array
    {
        return DB::transaction(function () use ($request, $approver, $comments) {
            // Obtener el nivel de aprobación actual
            $approvalLevel = $this->getApprovalLevelForUser($approver, $request);
            
            if (!$approvalLevel) {
                throw new \Exception('No tiene permisos para aprobar esta solicitud.');
            }

            // Actualizar la aprobación correspondiente
            $approval = $request->approvals()
                ->where('approval_level', $approvalLevel)
                ->where('approver_id', $approver->id)
                ->first();

            if (!$approval) {
                throw new \Exception('No se encontró la aprobación correspondiente.');
            }

            $approval->update([
                'status' => Approval::STATUS_APPROVED,
                'comments' => $comments,
                'approved_at' => now(),
                'digital_signature_hash' => $this->generateSignatureHash($request, $approver),
            ]);

            // Determinar el siguiente paso
            $isFinalApproval = $this->processFinalApproval($request, $approvalLevel);

            // Enviar notificaciones
            $this->sendApprovalNotifications($request, $approver, $isFinalApproval);

            // Registrar en auditoría
            AuditLog::logAction(AuditLog::ACTION_APPROVED, $request, null, [
                'approver_id' => $approver->id,
                'approval_level' => $approvalLevel,
                'comments' => $comments,
            ]);

            return [
                'success' => true,
                'final_approval' => $isFinalApproval,
                'next_level' => !$isFinalApproval ? $approvalLevel + 1 : null,
            ];
        });
    }

    /**
     * Rechaza una solicitud de permiso
     */
    public function rejectRequest(PermissionRequest $request, User $approver, string $comments): array
    {
        return DB::transaction(function () use ($request, $approver, $comments) {
            // Obtener el nivel de aprobación actual
            $approvalLevel = $this->getApprovalLevelForUser($approver, $request);
            
            if (!$approvalLevel) {
                throw new \Exception('No tiene permisos para rechazar esta solicitud.');
            }

            if (empty($comments)) {
                throw new \Exception('Los comentarios son obligatorios al rechazar una solicitud.');
            }

            // Actualizar la aprobación correspondiente
            $approval = $request->approvals()
                ->where('approval_level', $approvalLevel)
                ->where('approver_id', $approver->id)
                ->first();

            if (!$approval) {
                throw new \Exception('No se encontró la aprobación correspondiente.');
            }

            $approval->update([
                'status' => Approval::STATUS_REJECTED,
                'comments' => $comments,
                'approved_at' => now(),
                'digital_signature_hash' => $this->generateSignatureHash($request, $approver),
            ]);

            // Actualizar el estado de la solicitud
            $request->update(['status' => PermissionRequest::STATUS_REJECTED]);

            // Cancelar aprobaciones pendientes de niveles superiores
            $request->approvals()
                ->where('approval_level', '>', $approvalLevel)
                ->where('status', Approval::STATUS_PENDING)
                ->update([
                    'status' => Approval::STATUS_REJECTED,
                    'comments' => 'Rechazado en nivel anterior',
                ]);

            // Enviar notificaciones
            $this->notificationService->sendRejectionNotification(
                $request->user,
                $request,
                $approver->full_name,
                $comments
            );

            // Registrar en auditoría
            AuditLog::logAction(AuditLog::ACTION_REJECTED, $request, null, [
                'approver_id' => $approver->id,
                'approval_level' => $approvalLevel,
                'comments' => $comments,
            ]);

            return [
                'success' => true,
                'message' => 'Solicitud rechazada exitosamente.',
            ];
        });
    }

    /**
     * Aprueba múltiples solicitudes a la vez
     */
    public function bulkApprove(array $requestIds, User $approver, string $comments = ''): array
    {
        $results = [
            'approved' => 0,
            'errors' => [],
            'final_approvals' => 0,
        ];

        foreach ($requestIds as $requestId) {
            try {
                $request = PermissionRequest::findOrFail($requestId);
                $result = $this->approveRequest($request, $approver, $comments);
                
                $results['approved']++;
                if ($result['final_approval']) {
                    $results['final_approvals']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Solicitud #{$requestId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Procesa la aprobación final y determina el siguiente paso
     */
    private function processFinalApproval(PermissionRequest $request, int $currentLevel): bool
    {
        if ($currentLevel === Approval::LEVEL_SUPERVISOR) {
            // Pasar al siguiente nivel (RRHH)
            $request->update([
                'status' => PermissionRequest::STATUS_PENDING_HR,
                'current_approval_level' => Approval::LEVEL_HR,
            ]);

            // Activar la aprobación de RRHH
            $hrApproval = $request->approvals()
                ->where('approval_level', Approval::LEVEL_HR)
                ->first();

            if ($hrApproval) {
                $hrApproval->update(['status' => Approval::STATUS_PENDING]);
            }

            return false; // No es la aprobación final
        }

        if ($currentLevel === Approval::LEVEL_HR) {
            // Es la aprobación final
            $request->update([
                'status' => PermissionRequest::STATUS_APPROVED,
                'current_approval_level' => Approval::LEVEL_HR,
            ]);

            // Consumir horas del saldo
            $this->consumePermissionBalance($request);

            return true; // Es la aprobación final
        }

        return false;
    }

    /**
     * Consume horas del saldo de permisos
     */
    private function consumePermissionBalance(PermissionRequest $request): void
    {
        $startDate = Carbon::parse($request->start_datetime);
        
        $balance = PermissionBalance::getOrCreateBalance(
            $request->user_id,
            $request->permission_type_id,
            $startDate->year,
            $startDate->month
        );

        if (!$balance->consumeHours($request->requested_hours)) {
            // Si no se puede consumir, registrar una alerta pero no fallar
            \Log::warning("No se pudo consumir saldo para solicitud aprobada", [
                'request_id' => $request->id,
                'requested_hours' => $request->requested_hours,
                'available_hours' => $balance->remaining_hours,
            ]);
        }
    }

    /**
     * Envía notificaciones después de una aprobación
     */
    private function sendApprovalNotifications(PermissionRequest $request, User $approver, bool $isFinalApproval): void
    {
        if ($isFinalApproval) {
            // Notificar al solicitante que su permiso fue aprobado
            $this->notificationService->sendApprovalNotification(
                $request->user,
                $request,
                $approver->full_name
            );
        } else {
            // Notificar al siguiente aprobador
            $nextApproval = $request->approvals()
                ->where('status', Approval::STATUS_PENDING)
                ->where('approval_level', '>', $this->getApprovalLevelForUser($approver, $request))
                ->orderBy('approval_level')
                ->first();

            if ($nextApproval) {
                $this->notificationService->sendApprovalNotification(
                    $nextApproval->approver,
                    $request
                );
            }

            // Notificar al solicitante sobre el progreso
            $this->notificationService->sendProgressNotification(
                $request->user,
                $request,
                "Su solicitud ha sido aprobada por {$approver->full_name} y enviada al siguiente nivel."
            );
        }
    }

    /**
     * Obtiene el nivel de aprobación para un usuario específico
     */
    private function getApprovalLevelForUser(User $user, PermissionRequest $request): ?int
    {
        // Verificar si es jefe inmediato del solicitante
        if ($user->id === $request->user->immediate_supervisor_id && 
            $request->status === PermissionRequest::STATUS_PENDING_SUPERVISOR) {
            return Approval::LEVEL_SUPERVISOR;
        }

        // Verificar si es jefe de RRHH
        if ($user->hasRole('jefe_rrhh') && 
            $request->status === PermissionRequest::STATUS_PENDING_HR) {
            return Approval::LEVEL_HR;
        }

        return null;
    }

    /**
     * Genera un hash de firma digital simulado
     */
    private function generateSignatureHash(PermissionRequest $request, User $approver): string
    {
        $data = [
            'request_id' => $request->id,
            'approver_id' => $approver->id,
            'timestamp' => now()->toISOString(),
            'request_data' => $request->toArray(),
        ];

        return hash('sha256', json_encode($data) . config('app.key'));
    }

    /**
     * Verifica si una solicitud puede ser aprobada por un usuario
     */
    public function canApprove(User $user, PermissionRequest $request): bool
    {
        return !is_null($this->getApprovalLevelForUser($user, $request));
    }

    /**
     * Obtiene estadísticas de aprobaciones para un usuario
     */
    public function getApprovalStats(User $user, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        return [
            'total_approved' => Approval::where('approver_id', $user->id)
                ->where('status', Approval::STATUS_APPROVED)
                ->where('approved_at', '>=', $startDate)
                ->count(),
            'total_rejected' => Approval::where('approver_id', $user->id)
                ->where('status', Approval::STATUS_REJECTED)
                ->where('approved_at', '>=', $startDate)
                ->count(),
            'pending_count' => Approval::where('approver_id', $user->id)
                ->where('status', Approval::STATUS_PENDING)
                ->count(),
            'average_response_time' => $this->calculateAverageResponseTime($user, $days),
        ];
    }

    /**
     * Calcula el tiempo promedio de respuesta para aprobaciones
     */
    private function calculateAverageResponseTime(User $user, int $days): float
    {
        $approvals = Approval::where('approver_id', $user->id)
            ->whereNotNull('approved_at')
            ->where('approved_at', '>=', Carbon::now()->subDays($days))
            ->with('permissionRequest')
            ->get();

        if ($approvals->isEmpty()) {
            return 0;
        }

        $totalHours = 0;
        $count = 0;

        foreach ($approvals as $approval) {
            $submittedAt = $approval->permissionRequest->submitted_at;
            $approvedAt = $approval->approved_at;

            if ($submittedAt && $approvedAt) {
                $totalHours += $submittedAt->diffInHours($approvedAt);
                $count++;
            }
        }

        return $count > 0 ? round($totalHours / $count, 2) : 0;
    }

    /**
     * Obtiene solicitudes próximas a vencer sin aprobación
     */
    public function getExpiringRequests(int $hoursThreshold = 72): array
    {
        $threshold = now()->subHours($hoursThreshold);

        return PermissionRequest::with(['user', 'permissionType'])
            ->whereIn('status', [
                PermissionRequest::STATUS_PENDING_SUPERVISOR,
                PermissionRequest::STATUS_PENDING_HR
            ])
            ->where('submitted_at', '<=', $threshold)
            ->orderBy('submitted_at')
            ->get()
            ->toArray();
    }

    /**
     * Proceso automático de escalamiento por timeout
     */
    public function processTimeoutEscalation(): array
    {
        $timeoutHours = config('permissions.approval_timeout_hours', 72);
        $expiringRequests = $this->getExpiringRequests($timeoutHours);
        $escalated = [];
        $errors = [];

        foreach ($expiringRequests as $requestData) {
            try {
                $request = PermissionRequest::find($requestData['id']);
                
                // Enviar recordatorio urgente
                $this->notificationService->sendUrgentReminderNotification($request);
                
                $escalated[] = $request->request_number;
            } catch (\Exception $e) {
                $errors[] = "Error escalando solicitud {$requestData['request_number']}: " . $e->getMessage();
            }
        }

        return [
            'escalated_count' => count($escalated),
            'escalated_requests' => $escalated,
            'errors' => $errors,
        ];
    }
}