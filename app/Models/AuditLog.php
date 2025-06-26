<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'performed_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'performed_at' => 'datetime',
    ];

    /**
     * Acciones auditables
     */
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_SUBMITTED = 'submitted';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_SIGNED = 'signed';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_BIOMETRIC_RECORD = 'biometric_record';
    public const ACTION_EXPORT = 'export';
    public const ACTION_IMPORT = 'import';

    /**
     * Relación con usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene el modelo relacionado
     */
    public function getRelatedModel()
    {
        if (!$this->model_type || !$this->model_id) {
            return null;
        }
        
        if (!class_exists($this->model_type)) {
            return null;
        }
        
        return $this->model_type::find($this->model_id);
    }

    /**
     * Obtiene el nombre de la acción en español
     */
    public function getActionNameAttribute(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => 'Creado',
            self::ACTION_UPDATED => 'Actualizado',
            self::ACTION_DELETED => 'Eliminado',
            self::ACTION_APPROVED => 'Aprobado',
            self::ACTION_REJECTED => 'Rechazado',
            self::ACTION_SUBMITTED => 'Enviado',
            self::ACTION_CANCELLED => 'Cancelado',
            self::ACTION_SIGNED => 'Firmado',
            self::ACTION_LOGIN => 'Inicio de sesión',
            self::ACTION_LOGOUT => 'Cierre de sesión',
            self::ACTION_BIOMETRIC_RECORD => 'Registro biométrico',
            self::ACTION_EXPORT => 'Exportado',
            self::ACTION_IMPORT => 'Importado',
            default => ucfirst($this->action)
        };
    }

    /**
     * Obtiene el nombre del modelo en español
     */
    public function getModelNameAttribute(): string
    {
        if (!$this->model_type) {
            return 'Desconocido';
        }
        
        $modelName = class_basename($this->model_type);
        
        return match($modelName) {
            'User' => 'Usuario',
            'PermissionRequest' => 'Solicitud de Permiso',
            'Approval' => 'Aprobación',
            'Document' => 'Documento',
            'BiometricRecord' => 'Registro Biométrico',
            'DigitalSignature' => 'Firma Digital',
            'PermissionType' => 'Tipo de Permiso',
            'Department' => 'Departamento',
            'Role' => 'Rol',
            'PermissionBalance' => 'Saldo de Permisos',
            'SystemConfiguration' => 'Configuración del Sistema',
            default => $modelName
        };
    }

    /**
     * Obtiene las diferencias entre valores antiguos y nuevos
     */
    public function getChangesAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }
        
        $changes = [];
        
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Obtiene información del navegador
     */
    public function getBrowserInfoAttribute(): array
    {
        if (!$this->user_agent) {
            return [];
        }
        
        // Parseo básico del user agent
        $userAgent = $this->user_agent;
        $browser = 'Desconocido';
        $platform = 'Desconocido';
        
        // Detectar navegador
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        }
        
        // Detectar plataforma
        if (str_contains($userAgent, 'Windows')) {
            $platform = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $platform = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $platform = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $platform = 'Android';
        } elseif (str_contains($userAgent, 'iOS')) {
            $platform = 'iOS';
        }
        
        return [
            'browser' => $browser,
            'platform' => $platform,
            'full' => $userAgent,
        ];
    }

    /**
     * Registra una acción en el log de auditoría
     */
    public static function logAction(
        string $action,
        Model $model = null,
        array $oldValues = null,
        array $newValues = null,
        User $user = null
    ): self {
        $user = $user ?: auth()->user();
        
        return self::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'performed_at' => now(),
        ]);
    }

    /**
     * Registra un login
     */
    public static function logLogin(User $user): self
    {
        return self::logAction(self::ACTION_LOGIN, $user, null, [
            'user_id' => $user->id,
            'email' => $user->email,
            'login_time' => now()->toISOString(),
        ], $user);
    }

    /**
     * Registra un logout
     */
    public static function logLogout(User $user): self
    {
        return self::logAction(self::ACTION_LOGOUT, $user, null, [
            'user_id' => $user->id,
            'logout_time' => now()->toISOString(),
        ], $user);
    }

    /**
     * Obtiene estadísticas de actividad por usuario
     */
    public static function getUserActivityStats(int $userId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $logs = self::where('user_id', $userId)
                   ->where('performed_at', '>=', $startDate)
                   ->get();
        
        return [
            'total_actions' => $logs->count(),
            'actions_by_type' => $logs->groupBy('action')->map->count(),
            'daily_activity' => $logs->groupBy(function ($log) {
                return $log->performed_at->format('Y-m-d');
            })->map->count(),
            'last_activity' => $logs->max('performed_at'),
        ];
    }

    /**
     * Limpia logs antiguos
     */
    public static function cleanupOldLogs(int $daysToKeep = 2555): int // 7 años por defecto
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return self::where('performed_at', '<', $cutoffDate)->delete();
    }

    /**
     * Scopes
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModel($query, string $modelType, int $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('performed_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    public function scopeByIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeLogins($query)
    {
        return $query->where('action', self::ACTION_LOGIN);
    }

    public function scopePermissionActions($query)
    {
        return $query->whereIn('action', [
            self::ACTION_CREATED,
            self::ACTION_APPROVED,
            self::ACTION_REJECTED,
            self::ACTION_SUBMITTED,
            self::ACTION_CANCELLED,
        ])->where('model_type', PermissionRequest::class);
    }
}