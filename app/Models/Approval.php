<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'permission_request_id',
        'approver_id',
        'approval_level',
        'status',
        'comments',
        'approved_at',
        'digital_signature_hash',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Estados de aprobación
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Niveles de aprobación
     */
    public const LEVEL_SUPERVISOR = 1;
    public const LEVEL_HR = 2;

    /**
     * Relación con solicitud de permiso
     */
    public function permissionRequest(): BelongsTo
    {
        return $this->belongsTo(PermissionRequest::class);
    }

    /**
     * Relación con aprobador
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Verifica si está aprobada
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Verifica si está rechazada
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Verifica si está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Obtiene el nombre del nivel de aprobación
     */
    public function getLevelNameAttribute(): string
    {
        return match($this->approval_level) {
            self::LEVEL_SUPERVISOR => 'Jefe Inmediato',
            self::LEVEL_HR => 'Recursos Humanos',
            default => 'Desconocido'
        };
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }
}