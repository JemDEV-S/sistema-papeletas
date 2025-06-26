<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PermissionRequest;

class PermissionRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_own_requests') || 
               $user->hasPermission('view_subordinate_requests') || 
               $user->hasPermission('view_all_requests');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PermissionRequest $permissionRequest): bool
    {
        // El propietario puede ver su propia solicitud
        if ($user->id === $permissionRequest->user_id) {
            return $user->hasPermission('view_own_requests');
        }

        // Los jefes inmediatos pueden ver solicitudes de sus subordinados
        if ($user->hasPermission('view_subordinate_requests')) {
            return $user->subordinates()->where('id', $permissionRequest->user_id)->exists();
        }

        // RRHH y administradores pueden ver todas las solicitudes
        if ($user->hasPermission('view_all_requests')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo empleados activos pueden crear solicitudes
        return $user->is_active && $user->hasPermission('create_permission_request');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PermissionRequest $permissionRequest): bool
    {
        // Solo el propietario puede editar su solicitud
        if ($user->id !== $permissionRequest->user_id) {
            return false;
        }

        // Solo se pueden editar solicitudes en borrador
        if ($permissionRequest->status !== PermissionRequest::STATUS_DRAFT) {
            return false;
        }

        return $user->hasPermission('create_permission_request');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PermissionRequest $permissionRequest): bool
    {
        // Solo el propietario puede eliminar su solicitud
        if ($user->id !== $permissionRequest->user_id) {
            // Los administradores pueden eliminar cualquier solicitud
            return $user->hasPermission('manage_all_requests');
        }

        // Solo se pueden eliminar solicitudes en borrador o cancelar las enviadas
        return in_array($permissionRequest->status, [
            PermissionRequest::STATUS_DRAFT,
            PermissionRequest::STATUS_PENDING_SUPERVISOR,
            PermissionRequest::STATUS_PENDING_HR,
            PermissionRequest::STATUS_APPROVED, // Para cancelar antes de ejecución
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PermissionRequest $permissionRequest): bool
    {
        return $user->hasPermission('manage_all_requests');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PermissionRequest $permissionRequest): bool
    {
        return $user->hasPermission('manage_all_requests');
    }

    /**
     * Determine whether the user can submit the model for approval.
     */
    public function submit(User $user, PermissionRequest $permissionRequest): bool
    {
        // Solo el propietario puede enviar su solicitud
        if ($user->id !== $permissionRequest->user_id) {
            return false;
        }

        // Solo se pueden enviar solicitudes en borrador
        if ($permissionRequest->status !== PermissionRequest::STATUS_DRAFT) {
            return false;
        }

        // Verificar que el usuario tenga supervisor asignado
        if (!$user->immediate_supervisor_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, PermissionRequest $permissionRequest): bool
    {
        // Solo el propietario puede cancelar su solicitud
        if ($user->id !== $permissionRequest->user_id) {
            return false;
        }

        // Se pueden cancelar solicitudes que no estén completadas o rechazadas
        return !in_array($permissionRequest->status, [
            PermissionRequest::STATUS_COMPLETED,
            PermissionRequest::STATUS_REJECTED,
            PermissionRequest::STATUS_CANCELLED,
        ]);
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, PermissionRequest $permissionRequest): bool
    {
        // Jefe inmediato puede aprobar solicitudes de subordinados
        if ($user->hasPermission('approve_level_1')) {
            return $permissionRequest->status === PermissionRequest::STATUS_PENDING_SUPERVISOR &&
                   $user->subordinates()->where('id', $permissionRequest->user_id)->exists();
        }

        // RRHH puede dar aprobación final
        if ($user->hasPermission('approve_level_2')) {
            return $permissionRequest->status === PermissionRequest::STATUS_PENDING_HR;
        }

        return false;
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, PermissionRequest $permissionRequest): bool
    {
        // Mismo criterio que para aprobar
        return $this->approve($user, $permissionRequest);
    }

    /**
     * Determine whether the user can upload documents for the model.
     */
    public function uploadDocument(User $user, PermissionRequest $permissionRequest): bool
    {
        // Solo el propietario puede subir documentos
        if ($user->id !== $permissionRequest->user_id) {
            return false;
        }

        // Se pueden subir documentos hasta que esté completamente aprobada
        return !in_array($permissionRequest->status, [
            PermissionRequest::STATUS_COMPLETED,
            PermissionRequest::STATUS_REJECTED,
            PermissionRequest::STATUS_CANCELLED,
        ]);
    }

    /**
     * Determine whether the user can download documents from the model.
     */
    public function downloadDocument(User $user, PermissionRequest $permissionRequest): bool
    {
        // El propietario siempre puede descargar sus documentos
        if ($user->id === $permissionRequest->user_id) {
            return true;
        }

        // Los aprobadores pueden ver documentos para tomar decisiones
        if ($this->approve($user, $permissionRequest)) {
            return true;
        }

        // RRHH y administradores pueden ver todos los documentos
        return $user->hasPermission('view_all_requests');
    }

    /**
     * Determine whether the user can view approval history.
     */
    public function viewApprovalHistory(User $user, PermissionRequest $permissionRequest): bool
    {
        // Mismos permisos que para ver la solicitud
        return $this->view($user, $permissionRequest);
    }

    /**
     * Determine whether the user can execute biometric actions.
     */
    public function biometricAction(User $user, PermissionRequest $permissionRequest): bool
    {
        // Solo el propietario puede usar el sistema biométrico para su permiso
        if ($user->id !== $permissionRequest->user_id) {
            return false;
        }

        // Solo para permisos aprobados
        return $permissionRequest->status === PermissionRequest::STATUS_APPROVED;
    }

    /**
     * Determine whether the user can view reports related to permissions.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('view_reports') || 
               $user->hasPermission('view_department_reports');
    }

    /**
     * Determine whether the user can export permission data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('view_reports') || 
               $user->hasPermission('view_department_reports') ||
               $user->hasPermission('view_subordinate_requests');
    }

    /**
     * Determine whether the user can manage permission balances.
     */
    public function manageBalances(User $user): bool
    {
        return $user->hasPermission('manage_permission_balances');
    }

    /**
     * Determine whether the user can override system restrictions.
     */
    public function override(User $user): bool
    {
        return $user->hasRole('administrador') || 
               $user->hasPermission('override_restrictions');
    }
}