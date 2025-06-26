<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PermissionType;
use App\Models\PermissionRequest;
use App\Models\Approval;
use App\Models\PermissionBalance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SamplePermissionRequestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = User::whereHas('role', function ($query) {
            $query->where('name', 'empleado');
        })->get();

        $permissionTypes = PermissionType::active()->get();

        foreach ($employees as $employee) {
            // Crear entre 2-5 solicitudes por empleado
            $requestCount = rand(2, 5);
            
            for ($i = 0; $i < $requestCount; $i++) {
                $this->createSampleRequest($employee, $permissionTypes->random());
            }
        }
    }

    /**
     * Crea una solicitud de permiso de ejemplo
     */
    private function createSampleRequest(User $employee, PermissionType $permissionType): void
    {
        // Fechas aleatorias en los próximos 30 días
        $startDate = Carbon::now()->addDays(rand(1, 30));
        $startDate = $this->adjustToWorkingDay($startDate);
        
        // Calcular horas según el tipo de permiso
        $hours = $this->calculateRequestHours($permissionType);
        $endDate = $startDate->copy()->addHours($hours);

        // Determinar estado aleatorio
        $statuses = [
            PermissionRequest::STATUS_PENDING_SUPERVISOR,
            PermissionRequest::STATUS_PENDING_HR,
            PermissionRequest::STATUS_APPROVED,
            PermissionRequest::STATUS_REJECTED,
            PermissionRequest::STATUS_COMPLETED,
        ];
        
        $status = $statuses[array_rand($statuses)];

        // Crear la solicitud
        $request = PermissionRequest::create([
            'request_number' => PermissionRequest::generateRequestNumber(),
            'user_id' => $employee->id,
            'permission_type_id' => $permissionType->id,
            'start_datetime' => $startDate,
            'end_datetime' => $endDate,
            'requested_hours' => $hours,
            'reason' => $this->generateReason($permissionType),
            'status' => $status,
            'submitted_at' => $status !== PermissionRequest::STATUS_DRAFT ? Carbon::now()->subDays(rand(1, 7)) : null,
            'current_approval_level' => $this->getCurrentApprovalLevel($status),
            'metadata' => [
                'priority' => rand(1, 3),
                'is_urgent' => rand(0, 1) === 1,
            ],
        ]);

        // Crear aprobaciones según el estado
        $this->createApprovalsForRequest($request, $status);

        // Actualizar saldo si está aprobada
        if ($status === PermissionRequest::STATUS_APPROVED || $status === PermissionRequest::STATUS_COMPLETED) {
            $this->updatePermissionBalance($employee, $permissionType, $hours, $startDate);
        }
    }

    /**
     * Ajusta la fecha a un día laborable
     */
    private function adjustToWorkingDay(Carbon $date): Carbon
    {
        while ($date->isWeekend()) {
            $date->addDay();
        }
        
        // Ajustar hora al horario laboral
        $date->setTime(rand(8, 16), rand(0, 3) * 15); // Entre 8:00 y 16:45
        
        return $date;
    }

    /**
     * Calcula las horas a solicitar según el tipo
     */
    private function calculateRequestHours(PermissionType $permissionType): float
    {
        switch ($permissionType->code) {
            case PermissionType::ASUNTOS_PARTICULARES:
                return rand(1, 2); // 1-2 horas

            case PermissionType::ENFERMEDAD:
                return rand(2, 4); // 2-4 horas

            case PermissionType::GRAVIDEZ:
                return rand(2, 4); // 2-4 horas para control

            case PermissionType::LACTANCIA:
                return 1; // 1 hora diaria

            case PermissionType::DOCENCIA_UNIVERSITARIA:
                return rand(2, 4); // 2-4 horas

            case PermissionType::ESTUDIOS_UNIVERSITARIOS:
                return rand(3, 6); // 3-6 horas

            case PermissionType::CAPACITACION:
                return rand(4, 8); // 4-8 horas

            case PermissionType::CITACION_EXPRESA:
                return rand(2, 6); // 2-6 horas

            case PermissionType::COMISION_SERVICIOS:
                return rand(4, 8); // 4-8 horas

            default:
                return rand(2, 4); // Por defecto 2-4 horas
        }
    }

    /**
     * Genera una razón apropiada según el tipo de permiso
     */
    private function generateReason(PermissionType $permissionType): string
    {
        $reasons = [
            PermissionType::ASUNTOS_PARTICULARES => [
                'Trámites bancarios personales',
                'Cita médica familiar',
                'Gestiones documentarias',
                'Asuntos legales personales',
            ],
            PermissionType::ENFERMEDAD => [
                'Malestar estomacal',
                'Dolor de cabeza severo',
                'Cita médica de emergencia',
                'Síntomas gripales',
            ],
            PermissionType::GRAVIDEZ => [
                'Control prenatal mensual',
                'Exámenes médicos de rutina',
                'Consulta obstétrica',
            ],
            PermissionType::CAPACITACION => [
                'Curso de Excel Avanzado',
                'Taller de Gestión Pública',
                'Seminario de Atención al Ciudadano',
                'Capacitación en Sistema de Contabilidad',
            ],
            PermissionType::DOCENCIA_UNIVERSITARIA => [
                'Clases en Universidad Nacional',
                'Dictado de curso de especialización',
                'Sesiones de tutoría universitaria',
            ],
            PermissionType::ESTUDIOS_UNIVERSITARIOS => [
                'Exámenes parciales',
                'Clases presenciales obligatorias',
                'Sustentación de trabajo de investigación',
            ],
            PermissionType::CITACION_EXPRESA => [
                'Citación judicial como testigo',
                'Comparecencia en comisaría',
                'Diligencia en el Poder Judicial',
            ],
            PermissionType::COMISION_SERVICIOS => [
                'Reunión con entidad regional',
                'Capacitación interinstitucional',
                'Coordinación con gobierno provincial',
            ],
        ];

        $typeReasons = $reasons[$permissionType->code] ?? ['Solicitud de permiso'];
        return $typeReasons[array_rand($typeReasons)];
    }

    /**
     * Obtiene el nivel de aprobación actual según el estado
     */
    private function getCurrentApprovalLevel(string $status): int
    {
        return match($status) {
            PermissionRequest::STATUS_DRAFT => 0,
            PermissionRequest::STATUS_PENDING_SUPERVISOR => 1,
            PermissionRequest::STATUS_PENDING_HR => 2,
            PermissionRequest::STATUS_APPROVED,
            PermissionRequest::STATUS_REJECTED,
            PermissionRequest::STATUS_COMPLETED => 2,
            default => 0
        };
    }

    /**
     * Crea las aprobaciones necesarias según el estado
     */
    private function createApprovalsForRequest(PermissionRequest $request, string $status): void
    {
        if ($status === PermissionRequest::STATUS_DRAFT) {
            return;
        }

        $supervisor = $request->user->immediateSupervisor;
        if (!$supervisor) {
            return;
        }

        // Crear aprobación de nivel 1 (supervisor)
        $level1Status = match($status) {
            PermissionRequest::STATUS_PENDING_SUPERVISOR => Approval::STATUS_PENDING,
            PermissionRequest::STATUS_REJECTED => Approval::STATUS_REJECTED,
            default => Approval::STATUS_APPROVED
        };

        $level1Approval = Approval::create([
            'permission_request_id' => $request->id,
            'approver_id' => $supervisor->id,
            'approval_level' => Approval::LEVEL_SUPERVISOR,
            'status' => $level1Status,
            'comments' => $level1Status === Approval::STATUS_REJECTED ? 
                'No es posible aprobar por necesidades del servicio' : 
                'Aprobado por el jefe inmediato',
            'approved_at' => $level1Status !== Approval::STATUS_PENDING ? 
                Carbon::now()->subDays(rand(1, 3)) : null,
        ]);

        // Si pasó el nivel 1, crear aprobación de nivel 2 (RRHH)
        if (in_array($status, [
            PermissionRequest::STATUS_PENDING_HR,
            PermissionRequest::STATUS_APPROVED,
            PermissionRequest::STATUS_COMPLETED
        ])) {
            $hrUser = User::whereHas('role', function ($query) {
                $query->where('name', 'jefe_rrhh');
            })->first();

            if ($hrUser) {
                $level2Status = $status === PermissionRequest::STATUS_PENDING_HR ? 
                    Approval::STATUS_PENDING : Approval::STATUS_APPROVED;

                Approval::create([
                    'permission_request_id' => $request->id,
                    'approver_id' => $hrUser->id,
                    'approval_level' => Approval::LEVEL_HR,
                    'status' => $level2Status,
                    'comments' => $level2Status === Approval::STATUS_APPROVED ? 
                        'Aprobado por Recursos Humanos' : null,
                    'approved_at' => $level2Status === Approval::STATUS_APPROVED ? 
                        Carbon::now()->subDays(rand(0, 2)) : null,
                ]);
            }
        }
    }

    /**
     * Actualiza el saldo de permisos del usuario
     */
    private function updatePermissionBalance(User $employee, PermissionType $permissionType, float $hours, Carbon $date): void
    {
        $balance = PermissionBalance::getOrCreateBalance(
            $employee->id,
            $permissionType->id,
            $date->year,
            $date->month
        );

        $balance->consumeHours($hours);
    }
}