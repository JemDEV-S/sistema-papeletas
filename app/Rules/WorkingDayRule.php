<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WorkingDayRule implements ValidationRule
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
            
            // Verificar que sea un día laborable (lunes a viernes)
            if ($date->isWeekend()) {
                $fail('Los permisos solo pueden solicitarse para días laborables (lunes a viernes).');
                return;
            }
            
            // Verificar que no sea un feriado
            if ($this->isHoliday($date)) {
                $fail('No se pueden solicitar permisos para días feriados.');
                return;
            }
            
            // Verificar que no sea muy lejano en el futuro (máximo 6 meses)
            $maxFutureDate = Carbon::now()->addMonths(6);
            if ($date->gt($maxFutureDate)) {
                $fail('No se pueden solicitar permisos con más de 6 meses de anticipación.');
                return;
            }
            
        } catch (\Exception $e) {
            $fail('Formato de fecha inválido.');
        }
    }

    /**
     * Check if the given date is a holiday.
     */
    private function isHoliday(Carbon $date): bool
    {
        // Lista de feriados fijos en Perú
        $fixedHolidays = [
            '01-01', // Año Nuevo
            '05-01', // Día del Trabajador
            '07-28', // Día de la Independencia
            '07-29', // Día de la Independencia
            '08-30', // Santa Rosa de Lima
            '10-08', // Combate de Angamos
            '11-01', // Todos los Santos
            '12-08', // Inmaculada Concepción
            '12-25', // Navidad
        ];
        
        $dateString = $date->format('m-d');
        
        if (in_array($dateString, $fixedHolidays)) {
            return true;
        }
        
        // Verificar feriados móviles (Semana Santa)
        if ($this->isEasterWeek($date)) {
            return true;
        }
        
        // Verificar feriados específicos por año
        return $this->isSpecificYearHoliday($date);
    }

    /**
     * Check if the date falls in Easter week.
     */
    private function isEasterWeek(Carbon $date): bool
    {
        $year = $date->year;
        $easter = $this->getEasterDate($year);
        
        // Jueves y Viernes Santo
        $holyThursday = $easter->copy()->subDays(3);
        $goodFriday = $easter->copy()->subDays(2);
        
        return $date->isSameDay($holyThursday) || $date->isSameDay($goodFriday);
    }

    /**
     * Calculate Easter date for a given year.
     */
    private function getEasterDate(int $year): Carbon
    {
        // Algoritmo para calcular la fecha de Pascua
        $a = $year % 19;
        $b = intval($year / 100);
        $c = $year % 100;
        $d = intval($b / 4);
        $e = $b % 4;
        $f = intval(($b + 8) / 25);
        $g = intval(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intval($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intval(($a + 11 * $h + 22 * $l) / 451);
        $month = intval(($h + $l - 7 * $m + 114) / 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;
        
        return Carbon::create($year, $month, $day);
    }

    /**
     * Check for specific year holidays (like government-declared non-working days).
     */
    private function isSpecificYearHoliday(Carbon $date): bool
    {
        // Aquí se pueden agregar feriados específicos declarados por el gobierno
        // Por ejemplo, días no laborables declarados por decreto
        
        $specificHolidays = [
            // Formato: 'YYYY-MM-DD'
            // Se pueden cargar desde base de datos o configuración
        ];
        
        return in_array($date->format('Y-m-d'), $specificHolidays);
    }
}