<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener roles
        $adminRole = Role::where('name', Role::ADMINISTRADOR)->first();
        $hrRole = Role::where('name', Role::JEFE_RRHH)->first();
        $supervisorRole = Role::where('name', Role::JEFE_INMEDIATO)->first();
        $employeeRole = Role::where('name', Role::EMPLEADO)->first();

        // Obtener departamentos
        $rrhhDept = Department::where('code', 'RRHH')->first();
        $gmDept = Department::where('code', 'GM')->first();
        $adminDept = Department::where('code', 'ADMIN')->first();
        $obrasDept = Department::where('code', 'OBRAS')->first();

        $users = [
            [
                'dni' => '73129103',
                'first_name' => 'Administrador',
                'last_name' => 'Sistema',
                'email' => 'admin@sanjeronimo.gob.pe',
                'password' => Hash::make('73129103'),
                'department_id' => $rrhhDept->id,
                'role_id' => $adminRole->id,
                'immediate_supervisor_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'dni' => '87654321',
                'first_name' => 'María',
                'last_name' => 'González Pérez',
                'email' => 'mgonzalez@sanjeronimo.gob.pe',
                'password' => Hash::make('rrhh123'),
                'department_id' => $rrhhDept->id,
                'role_id' => $hrRole->id,
                'immediate_supervisor_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'dni' => '11223344',
                'first_name' => 'Carlos',
                'last_name' => 'Rodríguez Sánchez',
                'email' => 'crodriguez@sanjeronimo.gob.pe',
                'password' => Hash::make('jefe123'),
                'department_id' => $gmDept->id,
                'role_id' => $supervisorRole->id,
                'immediate_supervisor_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'dni' => '55667788',
                'first_name' => 'Ana',
                'last_name' => 'Torres Mendoza',
                'email' => 'atorres@sanjeronimo.gob.pe',
                'password' => Hash::make('jefe123'),
                'department_id' => $adminDept->id,
                'role_id' => $supervisorRole->id,
                'immediate_supervisor_id' => 3, // Carlos Rodríguez
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'dni' => '99887766',
                'first_name' => 'Luis',
                'last_name' => 'Vargas Castro',
                'email' => 'lvargas@sanjeronimo.gob.pe',
                'password' => Hash::make('jefe123'),
                'department_id' => $obrasDept->id,
                'role_id' => $supervisorRole->id,
                'immediate_supervisor_id' => 3, // Carlos Rodríguez
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'dni' => '33445566',
                'first_name' => 'Pedro',
                'last_name' => 'Morales Lima',
                'email' => 'pmorales@sanjeronimo.gob.pe',
                'password' => Hash::make('emp123'),
                'department_id' => $adminDept->id,
                'role_id' => $employeeRole->id,
                'immediate_supervisor_id' => 4, // Ana Torres
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'dni' => '77889900',
                'first_name' => 'Carmen',
                'last_name' => 'Flores Díaz',
                'email' => 'cflores@sanjeronimo.gob.pe',
                'password' => Hash::make('emp123'),
                'department_id' => $obrasDept->id,
                'role_id' => $employeeRole->id,
                'immediate_supervisor_id' => 5, // Luis Vargas
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'dni' => '22334455',
                'first_name' => 'José',
                'last_name' => 'Huamán Quispe',
                'email' => 'jhuaman@sanjeronimo.gob.pe',
                'password' => Hash::make('emp123'),
                'department_id' => $rrhhDept->id,
                'role_id' => $employeeRole->id,
                'immediate_supervisor_id' => 2, // María González
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['dni' => $userData['dni']],
                $userData
            );
        }

        // Actualizar los managers de los departamentos
        $rrhhDept->update(['manager_id' => 2]); // María González
        $gmDept->update(['manager_id' => 3]); // Carlos Rodríguez
        $adminDept->update(['manager_id' => 4]); // Ana Torres
        $obrasDept->update(['manager_id' => 5]); // Luis Vargas
    }
}