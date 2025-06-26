<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PermissionType;
use App\Models\PermissionBalance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PermissionBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role_id', '!=', 1)->get(); // Excluir administrador
        $permissionTypes = PermissionType::active()->get();
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        foreach ($users as $user) {
            foreach ($permissionTypes as $permissionType) {
                // Crear saldos para el mes actual
                $this->createBalanceForUser($user, $permissionType, $currentYear, $currentMonth);
                
                // Crear saldos para los próximos 2 meses
                $nextMonth = Carbon::now()->addMonth();
                $this->createBalanceForUser($user, $permissionType, $nextMonth->year, $nextMonth->month);
                
                $monthAfter = Carbon::now()->addMonths(2);
                $this->createBalanceForUser($user, $permissionType, $monthAfter->year, $monthAfter->month);
            }
        }
    }

    /**
     * Crea el saldo de permisos para un usuario específico
     */
    private function createBalanceForUser(User $user, PermissionType $permissionType, int $year, int $month): void
    {
        // Verificar si ya existe el saldo
        $existingBalance = PermissionBalance::where([
            'user_id' => $user->id,
            'permission_type_id' => $permissionType->id,
            'year' => $year,
            'month' => $month,
        ])->first();

        if ($existingBalance) {
            return; // Ya existe, no crear duplicado
        }

        // Calcular horas disponibles según el tipo de permiso
        $availableHours = $this->calculateAvailableHours($permissionType, $year, $month, $user);
        
        // Simular algo de uso para datos de prueba (solo para algunos usuarios)
        $usedHours = 0;
        if ($user->id > 2 && $year === Carbon::now()->year && $month === Carbon::now()->month) {
            // Usar hasta el 30% del saldo disponible para algunos usuarios
            $usedHours = $availableHours * (rand(0, 30) / 100);
        }

        PermissionBalance::create([
            'user_id' => $user->id,
            'permission_type_id' => $permissionType->id,
            'year' => $year,
            'month' => $month,
            'available_hours' => $availableHours,
            'used_hours' => $usedHours,
            'remaining_hours' => $availableHours - $usedHours,
        ]);
    }

    /**
     * Calcula las horas disponibles según el tipo de permiso y características del usuario
     */
    private function calculateAvailableHours(PermissionType $permissionType, int $year, int $month, User $user): float
    {
        switch ($permissionType->code) {
            case PermissionType::ASUNTOS_PARTICULARES:
                return 6; // 6 horas máximo por mes

            case PermissionType::GRAVIDEZ:
                // Solo para empleadas (simular que algunas están embarazadas)
                return in_array($user->id, [7, 8]) ? 8 : 0;

            case PermissionType::LACTANCIA:
                // Solo para empleadas con hijos menores de 1 año (simular)
                $workingDays = $this->calculateWorkingDaysInMonth($year, $month);
                return $user->id === 7 ? $workingDays : 0; // Solo Carmen Flores

            case PermissionType::DOCENCIA_UNIVERSITARIA:
                // Solo para algunos empleados que enseñan
                return in_array($user->id, [6, 8]) ? 24 : 0; // Pedro y José

            case PermissionType::ESTUDIOS_UNIVERSITARIOS:
                // Solo para empleados que estudian
                return in_array($user->id, [6, 7]) ? 40 : 0; // Pedro y Carmen

            case PermissionType::REPRESENTACION_SINDICAL:
                // Solo para representantes sindicales
                return $user->id === 8 ? 30 : 0; // Solo José

            case PermissionType::ENFERMEDAD:
                return 32; // 4 horas x 8 días máximo estimado

            case PermissionType::CAPACITACION:
                return 16; // 2 días de capacitación al mes

            case PermissionType::CITACION_EXPRESA:
                return 8; // 1 día máximo estimado

            case PermissionType::FUNCION_EDIL:
                return 0; // Solo para funcionarios electos

            case PermissionType::VACACIONAL_PENDIENTE:
                // Depende del saldo vacacional (simular entre 0-24 horas)
                return rand(0, 3) * 8; // 0-3 días

            case PermissionType::REPRESENTACION_CULTURAL:
                return 16; // 2 días al mes

            case PermissionType::COMISION_SERVICIOS:
                return 40; // 5 días al mes

            default:
                return $permissionType->max_hours_per_month ?? 8;
        }
    }

    /**
     * Calcula los días laborables en un mes
     */
    private function calculateWorkingDaysInMonth(int $year, int $month): int
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
}