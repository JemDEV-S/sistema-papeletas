<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FutureDateRule implements ValidationRule
{
    private int $minimumHoursAdvance;
    private bool $allowSameDay;

    /**
     * Create a new rule instance.
     */
    public function __construct(int $minimumHoursAdvance = 2, bool $allowSameDay = true)
    {
        $this->minimumHoursAdvance = $minimumHoursAdvance;
        $this->allowSameDay = $allowSameDay;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        try {
            $requestedDate = Carbon::parse($value);
            $now = Carbon::now();
            
            // Verificar que la fecha sea futura
            if ($requestedDate->lte($now)) {
                $fail('La fecha y hora deben ser futuras.');
                return;
            }
            
            // Verificar el tiempo mínimo de anticipación
            $hoursUntilRequest = $now->diffInHours($requestedDate, false);
            
            if ($hoursUntilRequest < $this->minimumHoursAdvance) {
                $fail("Los permisos deben solicitarse con al menos {$this->minimumHoursAdvance} horas de anticipación.");
                return;
            }
            
            // Verificar si permite mismo día
            if (!$this->allowSameDay && $requestedDate->isSameDay($now)) {
                $fail('No se pueden solicitar permisos para el mismo día.');
                return;
            }
            
            // Verificar casos especiales de urgencia
            $this->validateUrgencyRules($requestedDate, $now, $fail);
            
        } catch (\Exception $e) {
            $fail('Formato de fecha y hora inválido.');
        }
    }

    /**
     * Validate special urgency rules.
     */
    private function validateUrgencyRules(Carbon $requestedDate, Carbon $now, Closure $fail): void
    {
        $hoursUntilRequest = $now->diffInHours($requestedDate, false);
        
        // Si es muy próximo (menos de 4 horas), debe justificar urgencia
        if ($hoursUntilRequest < 4) {
            $request = request();
            
            // Verificar si se marcó como urgente
            if (!$request->filled('is_urgent') || !$request->input('is_urgent')) {
                $fail('Las solicitudes con menos de 4 horas de anticipación deben marcarse como urgentes y justificarse apropiadamente.');
                return;
            }
            
            // Verificar que tenga una justificación adecuada
            $reason = $request->input('reason', '');
            if (strlen(trim($reason)) < 50) {
                $fail('Las solicitudes urgentes requieren una justificación detallada de al menos 50 caracteres.');
                return;
            }
            
            // Verificar palabras clave de urgencia
            $urgencyKeywords = [
                'emergencia', 'urgente', 'médico', 'salud', 'hospital', 
                'accidente', 'familiar', 'citación', 'judicial'
            ];
            
            $hasUrgencyKeyword = false;
            $lowerReason = strtolower($reason);
            
            foreach ($urgencyKeywords as $keyword) {
                if (strpos($lowerReason, $keyword) !== false) {
                    $hasUrgencyKeyword = true;
                    break;
                }
            }
            
            if (!$hasUrgencyKeyword) {
                $fail('Las solicitudes urgentes deben incluir palabras que justifiquen la urgencia (emergencia, médico, citación, etc.).');
                return;
            }
        }
        
        // Restricciones para horarios muy tempranos o tardíos
        if ($this->isOutsideNormalHours($requestedDate)) {
            $fail('Los permisos fuera del horario normal (antes de 8:00 AM o después de 5:00 PM) requieren justificación especial.');
        }
    }

    /**
     * Check if the requested time is outside normal working hours.
     */
    private function isOutsideNormalHours(Carbon $date): bool
    {
        $hour = $date->hour;
        return $hour < 8 || $hour >= 17;
    }

    /**
     * Static factory methods for common scenarios.
     */
    public static function standard(): self
    {
        return new self(2, true);
    }

    public static function urgent(): self
    {
        return new self(1, true);
    }

    public static function nextDay(): self
    {
        return new self(12, false);
    }

    public static function medical(): self
    {
        return new self(1, true);
    }
}