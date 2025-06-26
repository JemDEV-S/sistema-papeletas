<?php

namespace App\Services;

use App\Models\User;
use App\Models\PermissionRequest;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Envía notificación de aprobación pendiente
     */
    public function sendApprovalNotification(User $user, PermissionRequest $request): void
    {
        try {
            // Crear notificación en el sistema
            $notification = Notification::createPermissionRequestNotification(
                $user,
                $request,
                Notification::TYPE_SYSTEM
            );

            // Enviar email si está habilitado
            if ($this->isEmailEnabled()) {
                $this->sendApprovalEmail($user, $request);
                
                // Marcar como enviada
                $notification->markAsSent();
            }

            // Enviar SMS si está habilitado
            if ($this->isSmsEnabled()) {
                $this->sendApprovalSms($user, $request);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de aprobación', [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envía notificación de aprobación exitosa
     */
    public function sendApprovalNotification2(User $user, PermissionRequest $request, string $approverName): void
    {
        try {
            // Crear notificación en el sistema
            $notification = Notification::createApprovalNotification(
                $user,
                $request,
                $approverName,
                Notification::TYPE_SYSTEM
            );

            // Enviar email si está habilitado
            if ($this->isEmailEnabled()) {
                $this->sendApprovalSuccessEmail($user, $request, $approverName);
                $notification->markAsSent();
            }

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de aprobación exitosa', [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envía notificación de rechazo
     */
    public function sendRejectionNotification(User $user, PermissionRequest $request, string $approverName, string $reason): void
    {
        try {
            // Crear notificación en el sistema
            $notification = Notification::createRejectionNotification(
                $user,
                $request,
                $approverName,
                $reason,
                Notification::TYPE_SYSTEM
            );

            // Enviar email si está habilitado
            if ($this->isEmailEnabled()) {
                $this->sendRejectionEmail($user, $request, $approverName, $reason);
                $notification->markAsSent();
            }

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de rechazo', [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envía notificación de progreso
     */
    public function sendProgressNotification(User $user, PermissionRequest $request, string $message): void
    {
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => Notification::TYPE_SYSTEM,
                'title' => 'Actualización de su solicitud',
                'message' => $message,
                'data' => [
                    'category' => Notification::CATEGORY_APPROVAL,
                    'permission_request_id' => $request->id,
                    'action_url' => route('permissions.show', $request->id),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de progreso', [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envía notificación de cancelación
     */
    public function sendCancellationNotification(PermissionRequest $request): void
    {
        try {
            // Notificar a aprobadores pendientes
            $pendingApprovals = $request->approvals()
                ->where('status', 'pending')
                ->with('approver')
                ->get();

            foreach ($pendingApprovals as $approval) {
                Notification::create([
                    'user_id' => $approval->approver->id,
                    'type' => Notification::TYPE_SYSTEM,
                    'title' => 'Solicitud cancelada',
                    'message' => "La solicitud #{$request->request_number} de {$request->user->full_name} ha sido cancelada.",
                    'data' => [
                        'category' => Notification::CATEGORY_SYSTEM,
                        'permission_request_id' => $request->id,
                    ],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de cancelación', [
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envía recordatorio urgente
     */
    public function sendUrgentReminderNotification(PermissionRequest $request): void
    {
        try {
            $pendingApprovals = $request->approvals()
                ->where('status', 'pending')
                ->with('approver')
                ->get();

            foreach ($pendingApprovals as $approval) {
                $notification = Notification::createReminderNotification(
                    $approval->approver,
                    'RECORDATORIO URGENTE: Solicitud pendiente de aprobación',
                    "La solicitud #{$request->request_number} de {$request->user->full_name} lleva más de 72 horas sin respuesta. Por favor, revise y procese esta solicitud a la brevedad.",
                    [
                        'permission_request_id' => $request->id,
                        'action_url' => route('approvals.show', $request->id),
                        'urgency' => 'high',
                    ],
                    Notification::TYPE_SYSTEM
                );

                // Enviar email urgente
                if ($this->isEmailEnabled()) {
                    $this->sendUrgentReminderEmail($approval->approver, $request);
                    $notification->markAsSent();
                }
            }

        } catch (\Exception $e) {
            Log::error('Error enviando recordatorio urgente', [
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envía notificación de saldo bajo
     */
    public function sendLowBalanceNotification(User $user, string $permissionTypeName, float $remainingHours): void
    {
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => Notification::TYPE_SYSTEM,
                'title' => 'Saldo de permisos bajo',
                'message' => "Su saldo de permisos para '{$permissionTypeName}' está bajo: {$remainingHours} horas restantes.",
                'data' => [
                    'category' => Notification::CATEGORY_REMINDER,
                    'permission_type' => $permissionTypeName,
                    'remaining_hours' => $remainingHours,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de saldo bajo', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envía notificación de permiso por vencer
     */
    public function sendPermissionExpiringNotification(User $user, PermissionRequest $request): void
    {
        try {
            $hoursUntilStart = now()->diffInHours($request->start_datetime);
            
            Notification::create([
                'user_id' => $user->id,
                'type' => Notification::TYPE_SYSTEM,
                'title' => 'Permiso próximo a iniciar',
                'message' => "Su permiso '{$request->permissionType->name}' iniciará en {$hoursUntilStart} horas ({$request->start_datetime->format('d/m/Y H:i')}).",
                'data' => [
                    'category' => Notification::CATEGORY_REMINDER,
                    'permission_request_id' => $request->id,
                    'start_datetime' => $request->start_datetime->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando notificación de permiso por vencer', [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Procesa notificaciones automáticas diarias
     */
    public function processDailyNotifications(): array
    {
        $results = [
            'permission_reminders' => 0,
            'low_balance_alerts' => 0,
            'errors' => [],
        ];

        try {
            // Recordatorios de permisos próximos a iniciar (24 horas antes)
            $upcomingPermissions = PermissionRequest::with(['user', 'permissionType'])
                ->where('status', PermissionRequest::STATUS_APPROVED)
                ->whereBetween('start_datetime', [
                    now()->addHours(23),
                    now()->addHours(25)
                ])
                ->get();

            foreach ($upcomingPermissions as $permission) {
                $this->sendPermissionExpiringNotification($permission->user, $permission);
                $results['permission_reminders']++;
            }

            // Alertas de saldo bajo (menos del 20% disponible)
            $lowBalances = \DB::table('permission_balances')
                ->join('users', 'permission_balances.user_id', '=', 'users.id')
                ->join('permission_types', 'permission_balances.permission_type_id', '=', 'permission_types.id')
                ->select('users.*', 'permission_types.name as permission_type_name', 'permission_balances.remaining_hours')
                ->whereRaw('(permission_balances.used_hours / permission_balances.available_hours) >= 0.8')
                ->where('permission_balances.remaining_hours', '>', 0)
                ->get();

            foreach ($lowBalances as $balance) {
                $user = User::find($balance->id);
                if ($user) {
                    $this->sendLowBalanceNotification($user, $balance->permission_type_name, $balance->remaining_hours);
                    $results['low_balance_alerts']++;
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Error procesando notificaciones diarias', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Limpia notificaciones antiguas
     */
    public function cleanupOldNotifications(int $daysToKeep = 90): int
    {
        return Notification::cleanupOldNotifications($daysToKeep);
    }

    /**
     * Marca todas las notificaciones de un usuario como leídas
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Obtiene notificaciones no leídas de un usuario
     */
    public function getUnreadNotifications(User $user, int $limit = 10): array
    {
        return Notification::forUser($user->id)
            ->unread()
            ->recent(30)
            ->limit($limit)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    // Métodos privados para verificar configuraciones

    private function isEmailEnabled(): bool
    {
        return config('notifications.email_enabled', true);
    }

    private function isSmsEnabled(): bool
    {
        return config('notifications.sms_enabled', false);
    }

    // Métodos privados para envío de emails (por implementar)

    private function sendApprovalEmail(User $user, PermissionRequest $request): void
    {
        // Implementar envío de email usando Laravel Mail
        // Mail::to($user->email)->send(new ApprovalPendingMail($request));
    }

    private function sendApprovalSuccessEmail(User $user, PermissionRequest $request, string $approverName): void
    {
        // Implementar envío de email de aprobación exitosa
    }

    private function sendRejectionEmail(User $user, PermissionRequest $request, string $approverName, string $reason): void
    {
        // Implementar envío de email de rechazo
    }

    private function sendUrgentReminderEmail(User $user, PermissionRequest $request): void
    {
        // Implementar envío de email urgente
    }

    // Métodos privados para envío de SMS (por implementar)

    private function sendApprovalSms(User $user, PermissionRequest $request): void
    {
        // Implementar envío de SMS usando un gateway
    }
}