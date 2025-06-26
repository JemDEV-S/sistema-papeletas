<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PermissionBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_type_id',
        'year',
        'month',
        'available_hours',
        'used_hours',
        'remaining_hours',
    ];

    protected $casts = [
        'available_hours' => 'decimal:2',
        'used_hours' => 'decimal:2',
        'remaining_hours' => 'decimal:2',
    ];

    /**
     * Relación con usuario
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
     * Calcula las horas restantes
     */
    public function calculateRemainingHours(): float
    {
        return max(0, $this->available_hours - $this->used_hours);
    }

    /**
     * Actualiza las horas restantes automáticamente
     */
    public function updateRemainingHours(): void
    {
        $this->remaining_hours = $this->calculateRemainingHours();
        $this->save();
    }

    /**
     * Verifica si hay suficientes horas disponibles
     */
    public function hasSufficientHours(float $requestedHours): bool
    {
        return $this->remaining_hours >= $requestedHours;
    }

    /**
     * Consume horas del saldo
     */
    public function consumeHours(float $hours): bool
    {
        if (!$this->hasSufficientHours($hours)) {
            return false;
        }
        
        $this->used_hours += $hours;
        $this->updateRemainingHours();
        
        return true;
    }

    /**
     * Devuelve horas al saldo (en caso de cancelación)
     */
    public function returnHours(float $hours): void
    {
        $this->used_hours = max(0, $this->used_hours - $hours);
        $this->updateRemainingHours();
    }

    /**
     * Obtiene o crea el saldo para un usuario, tipo y período
     */
    public static function getOrCreateBalance(
        int $userId, 
        int $permissionTypeId, 
        int $year = null, 
        int $month = null
    ): self {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;
        
        $balance = self::where([
            'user_id' => $userId,
            'permission_type_id' => $permissionTypeId,
            'year' => $year,
            'month' => $month,
        ])->first();
        
        if (!$balance) {
            $permissionType = PermissionType::find($permissionTypeId);
            $availableHours = self::calculateInitialAvailableHours($permissionType, $year, $month);
            
            $balance = self::create([
                'user_id' => $userId,
                'permission_type_id' => $permissionTypeId,
                'year' => $year,
                'month' => $month,
                'available_hours' => $availableHours,
                'used_hours' => 0,
                'remaining_hours' => $availableHours,
            ]);
        }
        
        return $balance;
    }

    /**
     * Calcula las horas iniciales disponibles según el tipo de permiso
     */
    public static function calculateInitialAvailableHours(PermissionType $permissionType, int $year, int $month): float
    {
        // Horas por defecto según configuración del tipo de permiso
        $maxHours = $permissionType->max_hours_per_month ?? 0;
        
        // Ajustes específicos según el tipo de permiso
        switch ($permissionType->code) {
            case PermissionType::ASUNTOS_PARTICULARES:
                // 6 horas máximo por mes según normativa
                return 6;
                
            case PermissionType::DOCENCIA_UNIVERSITARIA:
                // 6 horas semanales = 24 horas mensuales aprox.
                return 24;
                
            case PermissionType::LACTANCIA:
                // 1 hora diaria x días laborables del mes
                $workingDays = self::calculateWorkingDaysInMonth($year, $month);
                return $workingDays;
                
            case PermissionType::GRAVIDEZ:
                // 1 vez al mes, hasta 8 horas
                return 8;
                
            case PermissionType::VACACIONAL_PENDIENTE:
                // Depende del saldo vacacional del usuario (por implementar)
                return 0; // Se calculará en base al saldo real
                
            default:
                return $maxHours;
        }
    }

    /**
     * Calcula los días laborables en un mes
     */
    public static function calculateWorkingDaysInMonth(int $year, int $month): int
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;
        
        while ($startDate <= $endDate) {
            if ($startDate->isWeekday()) {
                $workingDays++;
            }
            $startDate->addDay();
        }
        
        return $workingDays;
    }

    /**
     * Resetea los saldos mensuales (para ejecutar mensualmente)
     */
    public static function resetMonthlyBalances(int $year = null, int $month = null): void
    {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;
        
        // Tipos de permisos que se resetean mensualmente
        $monthlyResetTypes = [
            PermissionType::ASUNTOS_PARTICULARES,
            PermissionType::GRAVIDEZ,
            PermissionType::LACTANCIA,
        ];
        
        $permissionTypes = PermissionType::whereIn('code', $monthlyResetTypes)->get();
        $users = User::active()->get();
        
        foreach ($users as $user) {
            foreach ($permissionTypes as $type) {
                $balance = self::getOrCreateBalance($user->id, $type->id, $year, $month);
                
                // Resetear valores para el nuevo período
                $availableHours = self::calculateInitialAvailableHours($type, $year, $month);
                $balance->update([
                    'available_hours' => $availableHours,
                    'used_hours' => 0,
                    'remaining_hours' => $availableHours,
                ]);
            }
        }
    }

    /**
     * Obtiene el porcentaje de uso del saldo
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->available_hours <= 0) {
            return 0;
        }
        
        return ($this->used_hours / $this->available_hours) * 100;
    }

    /**
     * Verifica si el saldo está agotado
     */
    public function isExhausted(): bool
    {
        return $this->remaining_hours <= 0;
    }

    /**
     * Verifica si el saldo está por agotarse (menos del 20%)
     */
    public function isRunningLow(): bool
    {
        return $this->usage_percentage >= 80;
    }

    /**
     * Obtiene el período como string
     */
    public function getPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    /**
     * Scopes
     */
    public function scopeCurrentMonth($query)
    {
        $now = Carbon::now();
        return $query->where('year', $now->year)
                    ->where('month', $now->month);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPermissionType($query, int $permissionTypeId)
    {
        return $query->where('permission_type_id', $permissionTypeId);
    }

    public function scopeByPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeExhausted($query)
    {
        return $query->where('remaining_hours', '<=', 0);
    }

    public function scopeRunningLow($query)
    {
        return $query->whereRaw('(used_hours / available_hours) >= 0.8');
    }
}