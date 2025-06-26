<?php

namespace App\Rules;

use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WorkingHoursRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return;
        }

        try {
            $date = Carbon::parse($value);
            $time = $date->format('H:i');
            
            // Obtener horarios de trabajo desde configuración
            $workStart = SystemConfiguration::get('working_hours_start', '08:00');
            $workEnd = SystemConfiguration::get('working_hours_end', '17:00');
            $lunchStart = SystemConfiguration::get('lunch_break_start', '13:00');
            $lunchEnd = SystemConfiguration::get('lunch_break_end', '14:00');
            
            // Verificar que esté dentro del horario laboral
            if ($time < $workStart || $time > $workEnd) {
                $fail("La hora debe estar dentro del horario laboral ({$workStart} - {$workEnd}).");
                return;
            }
            
            // Verificar que no esté en horario de almuerzo
            if ($time >= $lunchStart && $time <= $lunchEnd) {
                $fail("No se pueden solicitar permisos durante el horario de almuerzo ({$lunchStart} - {$lunchEnd}).");
                return;
            }
            
            // Verificar que sea en intervalos de 15 minutos
            $minutes = (int) $date->format('i');
            if ($minutes % 15 !== 0) {
                $fail('Los horarios deben ser en intervalos de 15 minutos (00, 15, 30, 45).');
                return;
            }
            
        } catch (\Exception $e) {
            $fail('Formato de fecha y hora inválido.');
        }
    }
}