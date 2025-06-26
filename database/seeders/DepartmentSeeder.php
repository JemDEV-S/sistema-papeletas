<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Alcaldía',
                'code' => 'ALC',
                'description' => 'Despacho de Alcaldía',
                'parent_department_id' => null,
                'manager_id' => null,
            ],
            [
                'name' => 'Gerencia Municipal',
                'code' => 'GM',
                'description' => 'Gerencia Municipal',
                'parent_department_id' => 1, // Alcaldía
                'manager_id' => null,
            ],
            [
                'name' => 'Recursos Humanos',
                'code' => 'RRHH',
                'description' => 'Unidad de Recursos Humanos',
                'parent_department_id' => 2, // Gerencia Municipal
                'manager_id' => null,
            ],
            [
                'name' => 'Administración y Finanzas',
                'code' => 'ADMIN',
                'description' => 'Gerencia de Administración y Finanzas',
                'parent_department_id' => 2, // Gerencia Municipal
                'manager_id' => null,
            ],
            [
                'name' => 'Obras Públicas',
                'code' => 'OBRAS',
                'description' => 'Gerencia de Obras Públicas',
                'parent_department_id' => 2, // Gerencia Municipal
                'manager_id' => null,
            ],
            [
                'name' => 'Desarrollo Social',
                'code' => 'DESSOC',
                'description' => 'Gerencia de Desarrollo Social',
                'parent_department_id' => 2, // Gerencia Municipal
                'manager_id' => null,
            ],
            [
                'name' => 'Servicios Públicos',
                'code' => 'SERV',
                'description' => 'Gerencia de Servicios Públicos',
                'parent_department_id' => 2, // Gerencia Municipal
                'manager_id' => null,
            ],
            [
                'name' => 'Planeamiento y Presupuesto',
                'code' => 'PLAN',
                'description' => 'Oficina de Planeamiento y Presupuesto',
                'parent_department_id' => 2, // Gerencia Municipal
                'manager_id' => null,
            ],
            [
                'name' => 'Sistemas e Informática',
                'code' => 'SIST',
                'description' => 'Oficina de Sistemas e Informática',
                'parent_department_id' => 4, // Administración y Finanzas
                'manager_id' => null,
            ],
            [
                'name' => 'Tesorería',
                'code' => 'TES',
                'description' => 'Oficina de Tesorería',
                'parent_department_id' => 4, // Administración y Finanzas
                'manager_id' => null,
            ],
            [
                'name' => 'Contabilidad',
                'code' => 'CONT',
                'description' => 'Oficina de Contabilidad',
                'parent_department_id' => 4, // Administración y Finanzas
                'manager_id' => null,
            ],
            [
                'name' => 'Logística',
                'code' => 'LOG',
                'description' => 'Oficina de Logística',
                'parent_department_id' => 4, // Administración y Finanzas
                'manager_id' => null,
            ],
        ];

        foreach ($departments as $departmentData) {
            Department::updateOrCreate(
                ['code' => $departmentData['code']],
                $departmentData
            );
        }
    }
}