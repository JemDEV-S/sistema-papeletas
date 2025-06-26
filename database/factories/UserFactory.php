<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => fake()->unique()->numerify('########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName() . ' ' . fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password123'),
            'department_id' => Department::factory(),
            'role_id' => Role::factory(),
            'immediate_supervisor_id' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Usuario administrador
     */
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            $adminRole = Role::where('name', Role::ADMINISTRADOR)->first() 
                ?? Role::factory()->create(['name' => Role::ADMINISTRADOR]);
            
            return [
                'role_id' => $adminRole->id,
                'email' => 'admin@test.com',
            ];
        });
    }

    /**
     * Usuario jefe de RRHH
     */
    public function hrManager(): static
    {
        return $this->state(function (array $attributes) {
            $hrRole = Role::where('name', Role::JEFE_RRHH)->first() 
                ?? Role::factory()->create(['name' => Role::JEFE_RRHH]);
            
            return [
                'role_id' => $hrRole->id,
                'email' => 'rrhh@test.com',
            ];
        });
    }

    /**
     * Usuario jefe inmediato
     */
    public function supervisor(): static
    {
        return $this->state(function (array $attributes) {
            $supervisorRole = Role::where('name', Role::JEFE_INMEDIATO)->first() 
                ?? Role::factory()->create(['name' => Role::JEFE_INMEDIATO]);
            
            return [
                'role_id' => $supervisorRole->id,
                'email' => 'supervisor@test.com',
            ];
        });
    }

    /**
     * Usuario empleado
     */
    public function employee(): static
    {
        return $this->state(function (array $attributes) {
            $employeeRole = Role::where('name', Role::EMPLEADO)->first() 
                ?? Role::factory()->create(['name' => Role::EMPLEADO]);
            
            return [
                'role_id' => $employeeRole->id,
                'email' => 'empleado@test.com',
            ];
        });
    }

    /**
     * Con supervisor asignado
     */
    public function withSupervisor(User $supervisor = null): static
    {
        return $this->state(fn (array $attributes) => [
            'immediate_supervisor_id' => $supervisor?->id ?? User::factory()->supervisor(),
        ]);
    }

    /**
     * En departamento específico
     */
    public function inDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
        ]);
    }

    /**
     * Con DNI específico
     */
    public function withDni(string $dni): static
    {
        return $this->state(fn (array $attributes) => [
            'dni' => $dni,
        ]);
    }

    /**
     * Con email específico
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Con autenticación de dos factores habilitada
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_expires_at' => now()->addMinutes(5),
        ]);
    }
}