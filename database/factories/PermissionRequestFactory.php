<?php

namespace Database\Factories;

use App\Models\PermissionRequest;
use App\Models\User;
use App\Models\PermissionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PermissionRequest>
 */
class PermissionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+30 days');
        $startDateTime = Carbon::instance($startDate);
        
        // Ajustar a horario laboral
        $startDateTime->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 15, 30, 45]));
        
        $hours = fake()->randomFloat(2, 1, 8);
        $endDateTime = $startDateTime->copy()->addHours($hours);

        return [
            'request_number' => PermissionRequest::generateRequestNumber(),
            'user_id' => User::factory(),
            'permission_type_id' => PermissionType::factory(),
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'requested_hours' => $hours,
            'reason' => fake()->sentence(10),
            'status' => fake()->randomElement([
                PermissionRequest::STATUS_DRAFT,
                PermissionRequest::STATUS_PENDING_SUPERVISOR,
                PermissionRequest::STATUS_PENDING_HR,
                PermissionRequest::STATUS_APPROVED,
                PermissionRequest::STATUS_REJECTED,
            ]),
            'metadata' => [
                'priority' => fake()->numberBetween(1, 3),
                'is_urgent' => fake()->boolean(20), // 20% probability
            ],
            'submitted_at' => fake()->optional(0.8)->dateTimeBetween('-7 days', 'now'),
            'current_approval_level' => fake()->numberBetween(0, 2),
        ];
    }

    /**
     * Estado borrador
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermissionRequest::STATUS_DRAFT,
            'submitted_at' => null,
            'current_approval_level' => 0,
        ]);
    }

    /**
     * Estado pendiente de supervisor
     */
    public function pendingSupervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermissionRequest::STATUS_PENDING_SUPERVISOR,
            'submitted_at' => fake()->dateTimeBetween('-3 days', 'now'),
            'current_approval_level' => 1,
        ]);
    }

    /**
     * Estado pendiente de RRHH
     */
    public function pendingHR(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermissionRequest::STATUS_PENDING_HR,
            'submitted_at' => fake()->dateTimeBetween('-5 days', '-1 day'),
            'current_approval_level' => 2,
        ]);
    }

    /**
     * Estado aprobado
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermissionRequest::STATUS_APPROVED,
            'submitted_at' => fake()->dateTimeBetween('-7 days', '-2 days'),
            'current_approval_level' => 2,
        ]);
    }

    /**
     * Estado rechazado
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermissionRequest::STATUS_REJECTED,
            'submitted_at' => fake()->dateTimeBetween('-7 days', '-1 day'),
            'current_approval_level' => fake()->randomElement([1, 2]),
        ]);
    }

    /**
     * Permiso urgente
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_datetime' => fake()->dateTimeBetween('now', '+3 days'),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'is_urgent' => true,
                'priority' => 1,
            ]),
        ]);
    }

    /**
     * Para tipo específico de permiso
     */
    public function forPermissionType(PermissionType $permissionType): static
    {
        return $this->state(function (array $attributes) use ($permissionType) {
            $hours = $this->getHoursForPermissionType($permissionType);
            $startDateTime = Carbon::parse($attributes['start_datetime']);
            
            return [
                'permission_type_id' => $permissionType->id,
                'requested_hours' => $hours,
                'end_datetime' => $startDateTime->copy()->addHours($hours),
                'reason' => $this->getReasonForPermissionType($permissionType),
            ];
        });
    }

    /**
     * Para usuario específico
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Obtiene horas típicas para un tipo de permiso
     */
    private function getHoursForPermissionType(PermissionType $permissionType): float
    {
        return match($permissionType->code) {
            PermissionType::ASUNTOS_PARTICULARES => fake()->randomFloat(2, 0.5, 2),
            PermissionType::ENFERMEDAD => fake()->randomFloat(2, 2, 4),
            PermissionType::LACTANCIA => 1,
            PermissionType::DOCENCIA_UNIVERSITARIA => fake()->randomFloat(2, 2, 6),
            PermissionType::CAPACITACION => fake()->randomFloat(2, 4, 8),
            default => fake()->randomFloat(2, 1, 8),
        };
    }

    /**
     * Obtiene razón típica para un tipo de permiso
     */
    private function getReasonForPermissionType(PermissionType $permissionType): string
    {
        $reasons = [
            PermissionType::ASUNTOS_PARTICULARES => [
                'Trámites bancarios',
                'Gestiones personales',
                'Cita médica familiar',
            ],
            PermissionType::ENFERMEDAD => [
                'Malestar general',
                'Consulta médica',
                'Síntomas gripales',
            ],
            PermissionType::CAPACITACION => [
                'Curso de capacitación laboral',
                'Taller de especialización',
                'Seminario institucional',
            ],
            PermissionType::CITACION_EXPRESA => [
                'Citación judicial',
                'Comparecencia policial',
                'Diligencia legal',
            ],
        ];

        $typeReasons = $reasons[$permissionType->code] ?? ['Solicitud de permiso'];
        return fake()->randomElement($typeReasons);
    }
}