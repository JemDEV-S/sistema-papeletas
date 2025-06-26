<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'dni',
        'first_name',
        'last_name',
        'email',
        'password',
        'department_id',
        'role_id',
        'immediate_supervisor_id',
        'is_active',
        'two_factor_secret',
        'two_factor_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Relación con departamento
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relación con rol
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relación con supervisor inmediato
     */
    public function immediateSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'immediate_supervisor_id');
    }

    /**
     * Relación con subordinados
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'immediate_supervisor_id');
    }

    /**
     * Relación con solicitudes de permisos
     */
    public function permissionRequests(): HasMany
    {
        return $this->hasMany(PermissionRequest::class);
    }

    /**
     * Relación con aprobaciones realizadas
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'approver_id');
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
     * Relación con saldos de permisos
     */
    public function permissionBalances(): HasMany
    {
        return $this->hasMany(PermissionBalance::class);
    }

    /**
     * Relación con notificaciones
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Obtener nombre completo
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    /**
     * Verifica si el usuario tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    /**
     * Verifica si el usuario es jefe de otro usuario
     */
    public function isSupervisorOf(User $user): bool
    {
        return $this->id === $user->immediate_supervisor_id;
    }

    /**
     * Scope para usuarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para usuarios por departamento
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}