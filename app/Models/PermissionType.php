<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'max_hours_per_day',
        'max_hours_per_month',
        'max_times_per_month',
        'requires_document',
        'validation_rules',
        'is_active',
    ];

    protected $casts = [
        'requires_document' => 'boolean',
        'validation_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con solicitudes de permisos
     */
    public function permissionRequests(): HasMany
    {
        return $this->hasMany(PermissionRequest::class);
    }

    /**
     * Relación con saldos de permisos
     */
    public function permissionBalances(): HasMany
    {
        return $this->hasMany(PermissionBalance::class);
    }

    /**
     * Constantes para tipos de permisos según normativa
     */
    public const ENFERMEDAD = 'ENFERMEDAD';
    public const GRAVIDEZ = 'GRAVIDEZ';
    public const CAPACITACION = 'CAPACITACION';
    public const CITACION_EXPRESA = 'CITACION_EXPRESA';
    public const FUNCION_EDIL = 'FUNCION_EDIL';
    public const VACACIONAL_PENDIENTE = 'VACACIONAL_PENDIENTE';
    public const REPRESENTACION_CULTURAL = 'REPRESENTACION_CULTURAL';
    public const DOCENCIA_UNIVERSITARIA = 'DOCENCIA_UNIVERSITARIA';
    public const ESTUDIOS_UNIVERSITARIOS = 'ESTUDIOS_UNIVERSITARIOS';
    public const REPRESENTACION_SINDICAL = 'REPRESENTACION_SINDICAL';
    public const LACTANCIA = 'LACTANCIA';
    public const COMISION_SERVICIOS = 'COMISION_SERVICIOS';
    public const ASUNTOS_PARTICULARES = 'ASUNTOS_PARTICULARES';

    /**
     * Verifica si el tipo de permiso requiere documento
     */
    public function requiresDocument(): bool
    {
        return $this->requires_document;
    }

    /**
     * Valida las horas solicitadas contra las reglas del tipo
     */
    public function validateHours(float $hours, int $month = null, int $year = null): array
    {
        $errors = [];

        // Validar máximo por día
        if ($this->max_hours_per_day && $hours > $this->max_hours_per_day) {
            $errors[] = "Las horas solicitadas ({$hours}) exceden el máximo permitido por día ({$this->max_hours_per_day})";
        }

        // Validar máximo por mes si se proporciona período
        if ($this->max_hours_per_month && $month && $year) {
            // Aquí se podría agregar lógica para verificar el consumo mensual
            // Por ahora solo validamos el límite básico
            if ($hours > $this->max_hours_per_month) {
                $errors[] = "Las horas solicitadas ({$hours}) exceden el máximo permitido por mes ({$this->max_hours_per_month})";
            }
        }

        return $errors;
    }

    /**
     * Obtiene los documentos requeridos para este tipo de permiso
     */
    public function getRequiredDocuments(): array
    {
        $documents = [];
        
        switch ($this->code) {
            case self::ENFERMEDAD:
                $documents[] = 'certificado_medico';
                break;
            case self::GRAVIDEZ:
                $documents[] = 'certificado_control_medico';
                break;
            case self::CITACION_EXPRESA:
                $documents[] = 'copia_citacion';
                break;
            case self::FUNCION_EDIL:
                $documents[] = 'acreditacion_edil';
                break;
            case self::DOCENCIA_UNIVERSITARIA:
                $documents[] = 'resolucion_nombramiento';
                $documents[] = 'horario_visado';
                break;
            case self::ESTUDIOS_UNIVERSITARIOS:
                $documents[] = 'resolucion_nombramiento';
                $documents[] = 'horario_recuperacion';
                break;
            case self::LACTANCIA:
                $documents[] = 'partida_nacimiento';
                $documents[] = 'declaracion_jurada_supervivencia';
                break;
        }

        return $documents;
    }

    /**
     * Scope para tipos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para tipos que requieren documento
     */
    public function scopeRequiresDocument($query)
    {
        return $query->where('requires_document', true);
    }
}