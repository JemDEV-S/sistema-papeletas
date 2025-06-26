<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Relación con usuarios
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Verifica si el rol tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Constantes para roles del sistema
     */
    public const ADMINISTRADOR = 'administrador';
    public const JEFE_RRHH = 'jefe_rrhh';
    public const JEFE_INMEDIATO = 'jefe_inmediato';
    public const EMPLEADO = 'empleado';

    /**
     * Scope para filtrar roles activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}