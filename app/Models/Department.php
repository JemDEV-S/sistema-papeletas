<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_department_id',
        'manager_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relación con departamento padre
     */
    public function parentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    /**
     * Relación con departamentos hijos
     */
    public function childDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    /**
     * Relación con el gerente/jefe del departamento
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relación con usuarios del departamento
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Obtener todos los departamentos hijos recursivamente
     */
    public function getAllChildDepartments()
    {
        $children = collect();
        
        foreach ($this->childDepartments as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildDepartments());
        }
        
        return $children;
    }

    /**
     * Verifica si es departamento raíz
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_department_id);
    }

    /**
     * Scope para departamentos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para departamentos raíz
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_department_id');
    }
}