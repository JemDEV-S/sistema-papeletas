<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PermissionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'user_id',
        'permission_type_id',
        'start_datetime',
        'end_datetime',
        'requested_hours',
        'reason',
        'status',
        'metadata',
        'submitted_at',
        'current_approval_level',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'requested_hours' => 'decimal:2',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
    ];

    /**
     * Estados posibles de la solicitud
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_SUPERVISOR = 'pending_supervisor';
    public const STATUS_PENDING_HR = 'pending_hr';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_IN_EXECUTION = 'in_execution';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Relación con usuario solicitante
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con tipo de permiso
     */
    public function permissionType(): BelongsTo
    {
        return $this->belongsTo(PermissionType::class);
    }

    /**
     * Relación con aprobaciones
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    /**
     * Relación con documentos adjuntos
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Relación con registros biométricos
     */
    public function biometricRecords(): HasMany
    {
        return $this->hasMany(BiometricRecord::class);
    }

    /**
     * Relación con firmas digitales
     */
    public function digitalSignatures(): HasMany
    {
        return $this->hasMany(DigitalSignature::class);
    }

    /**
     * Genera el número de solicitud único
     */
    public static function generateRequestNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $lastRequest = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $consecutive = $lastRequest ? (int)substr($lastRequest->request_number, -4) + 1 : 1;
        
        return sprintf('PER-%s%s-%04d', $year, $month, $consecutive);
    }

    /**
     * Verifica si la solicitud está pendiente de aprobación
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_SUPERVISOR,
            self::STATUS_PENDING_HR
        ]);
    }

    /**
     * Verifica si la solicitud está aprobada
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Verifica si la solicitud está rechazada
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Verifica si la solicitud está en ejecución
     */
    public function isInExecution(): bool
    {
        return $this->status === self::STATUS_IN_EXECUTION;
    }

    /**
     * Obtiene el siguiente nivel de aprobación requerido
     */
    public function getNextApprovalLevel(): ?int
    {
        if ($this->current_approval_level === 0) {
            return 1; // Jefe Inmediato
        } elseif ($this->current_approval_level === 1) {
            return 2; // RRHH
        }
        
        return null; // Ya está completamente aprobada
    }

    /**
     * Obtiene el aprobador para el siguiente nivel
     */
    public function getNextApprover(): ?User
    {
        $nextLevel = $this->getNextApprovalLevel();
        
        if ($nextLevel === 1) {
            return $this->user->immediateSupervisor;
        } elseif ($nextLevel === 2) {
            // Buscar usuario con rol de RRHH
            return User::whereHas('role', function ($query) {
                $query->where('name', Role::JEFE_RRHH);
            })->first();
        }
        
        return null;
    }

    /**
     * Calcula la duración en horas entre start_datetime y end_datetime
     */
    public function calculateHours(): float
    {
        if (!$this->start_datetime || !$this->end_datetime) {
            return 0;
        }
        
        $start = Carbon::parse($this->start_datetime);
        $end = Carbon::parse($this->end_datetime);
        
        return $start->diffInHours($end, true);
    }

    /**
     * Verifica si la solicitud está activa (en el período de ejecución)
     */
    public function isActive(): bool
    {
        if (!$this->isApproved() && !$this->isInExecution()) {
            return false;
        }
        
        $now = Carbon::now();
        return $now->between($this->start_datetime, $this->end_datetime);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING_SUPERVISOR,
            self::STATUS_PENDING_HR
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeInExecution($query)
    {
        return $query->where('status', self::STATUS_IN_EXECUTION);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_datetime', [$startDate, $endDate]);
    }

    public function scopeByPermissionType($query, $permissionTypeId)
    {
        return $query->where('permission_type_id', $permissionTypeId);
    }
}