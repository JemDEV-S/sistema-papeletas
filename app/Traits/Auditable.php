<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $model->auditAction(AuditLog::ACTION_CREATED, null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $model->auditAction(
                AuditLog::ACTION_UPDATED,
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function (Model $model) {
            $model->auditAction(AuditLog::ACTION_DELETED, $model->getOriginal(), null);
        });
    }

    /**
     * Registra una acción específica en el log de auditoría
     */
    public function auditAction(string $action, array $oldValues = null, array $newValues = null): AuditLog
    {
        return AuditLog::logAction($action, $this, $oldValues, $newValues);
    }

    /**
     * Registra una acción personalizada
     */
    public function auditCustomAction(string $action, array $data = []): AuditLog
    {
        return AuditLog::logAction($action, $this, null, $data);
    }

    /**
     * Obtiene los logs de auditoría para este modelo
     */
    public function auditLogs()
    {
        return AuditLog::where('model_type', static::class)
                      ->where('model_id', $this->id)
                      ->orderBy('performed_at', 'desc');
    }

    /**
     * Obtiene el último log de auditoría
     */
    public function getLastAuditLog(): ?AuditLog
    {
        return $this->auditLogs()->first();
    }

    /**
     * Obtiene logs de auditoría por acción
     */
    public function getAuditLogsByAction(string $action)
    {
        return $this->auditLogs()->where('action', $action);
    }

    /**
     * Verifica si el modelo ha sido auditado
     */
    public function hasBeenAudited(): bool
    {
        return $this->auditLogs()->exists();
    }

    /**
     * Obtiene estadísticas de auditoría
     */
    public function getAuditStatistics(): array
    {
        $logs = $this->auditLogs()->get();

        return [
            'total_actions' => $logs->count(),
            'actions_by_type' => $logs->groupBy('action')->map->count(),
            'first_action' => $logs->min('performed_at'),
            'last_action' => $logs->max('performed_at'),
            'unique_users' => $logs->whereNotNull('user_id')->pluck('user_id')->unique()->count(),
        ];
    }

    /**
     * Campos que deben ser excluidos de la auditoría
     */
    protected function getAuditExcluded(): array
    {
        return $this->auditExcluded ?? [
            'updated_at',
            'created_at',
            'remember_token',
            'password',
            'two_factor_secret',
        ];
    }

    /**
     * Filtra los atributos para excluir campos sensibles
     */
    protected function filterAuditAttributes(array $attributes): array
    {
        $excluded = $this->getAuditExcluded();
        
        return array_diff_key($attributes, array_flip($excluded));
    }

    /**
     * Sobrescribe getAttributes para filtrar en auditoría
     */
    public function getAuditableAttributes(): array
    {
        return $this->filterAuditAttributes($this->getAttributes());
    }

    /**
     * Sobrescribe getOriginal para filtrar en auditoría
     */
    public function getAuditableOriginal(): array
    {
        return $this->filterAuditAttributes($this->getOriginal());
    }

    /**
     * Sobrescribe getChanges para filtrar en auditoría
     */
    public function getAuditableChanges(): array
    {
        return $this->filterAuditAttributes($this->getChanges());
    }

    /**
     * Campos que requieren encriptación en los logs
     */
    protected function getAuditEncrypted(): array
    {
        return $this->auditEncrypted ?? [];
    }

    /**
     * Encripta campos sensibles antes de guardar en audit log
     */
    protected function encryptAuditData(array $data): array
    {
        $encrypted = $this->getAuditEncrypted();
        
        foreach ($encrypted as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***ENCRYPTED***';
            }
        }
        
        return $data;
    }
}