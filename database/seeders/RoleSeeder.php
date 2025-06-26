<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ADMINISTRADOR,
                'description' => 'Administrador del sistema con acceso completo',
                'permissions' => [
                    'manage_users',
                    'manage_departments',
                    'manage_permission_types',
                    'view_all_requests',
                    'manage_system_config',
                    'view_reports',
                    'manage_audit_logs',
                ]
            ],
            [
                'name' => Role::JEFE_RRHH,
                'description' => 'Jefe de Recursos Humanos - Aprobación nivel 2',
                'permissions' => [
                    'approve_level_2',
                    'view_all_requests',
                    'view_reports',
                    'manage_permission_balances',
                    'view_audit_logs',
                ]
            ],
            [
                'name' => Role::JEFE_INMEDIATO,
                'description' => 'Jefe Inmediato - Aprobación nivel 1',
                'permissions' => [
                    'approve_level_1',
                    'view_subordinate_requests',
                    'view_department_reports',
                ]
            ],
            [
                'name' => Role::EMPLEADO,
                'description' => 'Empleado - Puede solicitar permisos',
                'permissions' => [
                    'create_permission_request',
                    'view_own_requests',
                    'cancel_own_requests',
                ]
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}