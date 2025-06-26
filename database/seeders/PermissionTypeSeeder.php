<?php

namespace Database\Seeders;

use App\Models\PermissionType;
use Illuminate\Database\Seeder;

class PermissionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionTypes = [
            [
                'name' => 'Por enfermedad',
                'code' => PermissionType::ENFERMEDAD,
                'description' => 'Permiso por enfermedad que requiere certificado médico',
                'max_hours_per_day' => 4,
                'max_hours_per_month' => null,
                'max_times_per_month' => null,
                'requires_document' => true,
                'validation_rules' => [
                    'required_documents' => ['certificado_medico'],
                    'justification_required' => true,
                ],
            ],
            [
                'name' => 'Por estado de gravidez',
                'code' => PermissionType::GRAVIDEZ,
                'description' => 'Permiso por estado de gravidez para control mensual',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => 8,
                'max_times_per_month' => 1,
                'requires_document' => true,
                'validation_rules' => [
                    'required_documents' => ['certificado_control_medico'],
                    'frequency_limit' => 'monthly',
                ],
            ],
            [
                'name' => 'Por capacitación laboral',
                'code' => PermissionType::CAPACITACION,
                'description' => 'Permiso para capacitación relacionada con las funciones del servidor',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => null,
                'max_times_per_month' => null,
                'requires_document' => false,
                'validation_rules' => [
                    'requires_approval' => true,
                    'work_related' => true,
                ],
            ],
            [
                'name' => 'Por citación expresa',
                'code' => PermissionType::CITACION_EXPRESA,
                'description' => 'Permiso por citación judicial, militar o policial',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => null,
                'max_times_per_month' => null,
                'requires_document' => true,
                'validation_rules' => [
                    'required_documents' => ['copia_citacion'],
                    'justification_automatic' => true,
                ],
            ],
            [
                'name' => 'Por función edil',
                'code' => PermissionType::FUNCION_EDIL,
                'description' => 'Permiso por función edil con acreditación correspondiente',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => null,
                'max_times_per_month' => null,
                'requires_document' => true,
                'validation_rules' => [
                    'required_documents' => ['acreditacion_edil'],
                ],
            ],
            [
                'name' => 'A cuenta del período vacacional',
                'code' => PermissionType::VACACIONAL_PENDIENTE,
                'description' => 'Permiso a cuenta del período vacacional pendiente de uso físico',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => null,
                'max_times_per_month' => 3,
                'requires_document' => false,
                'validation_rules' => [
                    'frequency_limit' => 'monthly',
                    'max_times_per_month' => 3,
                    'exceptional_causes' => true,
                ],
            ],
            [
                'name' => 'Por representación cultural/deportiva',
                'code' => PermissionType::REPRESENTACION_CULTURAL,
                'description' => 'Permiso por representación cultural y deportiva',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => null,
                'max_times_per_month' => null,
                'requires_document' => false,
                'validation_rules' => [
                    'supervisor_approval' => true,
                    'hr_approval' => true,
                ],
            ],
            [
                'name' => 'Por docencia universitaria',
                'code' => PermissionType::DOCENCIA_UNIVERSITARIA,
                'description' => 'Permiso para ejercer docencia universitaria (máx. 6h semanales)',
                'max_hours_per_day' => 6,
                'max_hours_per_month' => 24, // 6 horas x 4 semanas
                'max_times_per_month' => null,
                'requires_document' => true,
                'validation_rules' => [
                    'required_documents' => ['resolucion_nombramiento', 'horario_visado'],
                    'max_hours_weekly' => 6,
                    'compensation_required' => true,
                ],
            ],
            [
                'name' => 'Por estudios universitarios',
                'code' => PermissionType::ESTUDIOS_UNIVERSITARIOS,
                'description' => 'Permiso para seguir estudios universitarios',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => null,
                'max_times_per_month' => null,
                'requires_document' => true,
                'validation_rules' => [
                    'required_documents' => ['resolucion_nombramiento', 'horario_recuperacion'],
                    'academic_week_only' => true,
                ],
            ],
            [
                'name' => 'Por representatividad sindical',
                'code' => PermissionType::REPRESENTACION_SINDICAL,
                'description' => 'Permiso por representatividad sindical (máx. 30 días/año)',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => null,
                'max_times_per_month' => null,
                'requires_document' => false,
                'validation_rules' => [
                    'max_days_per_year' => 30,
                    'sindical_functions_only' => true,
                ],
            ],
            [
                'name' => 'Por lactancia',
                'code' => PermissionType::LACTANCIA,
                'description' => 'Permiso por lactancia (1 hora diaria hasta 1 año del menor)',
                'max_hours_per_day' => 1,
                'max_hours_per_month' => 22, // Aproximadamente 22 días laborables
                'max_times_per_month' => null,
                'requires_document' => true,
                'validation_rules' => [
                    'required_documents' => ['partida_nacimiento', 'declaracion_jurada_supervivencia'],
                    'until_one_year' => true,
                    'daily_limit' => 1,
                ],
            ],
            [
                'name' => 'Por comisión de servicios',
                'code' => PermissionType::COMISION_SERVICIOS,
                'description' => 'Permiso por comisión de servicios oficial',
                'max_hours_per_day' => 8,
                'max_hours_per_month' => null,
                'max_times_per_month' => null,
                'requires_document' => false,
                'validation_rules' => [
                    'official_commission' => true,
                    'mobility_payment' => true,
                    'signature_required' => true,
                ],
            ],
            [
                'name' => 'Por asuntos particulares',
                'code' => PermissionType::ASUNTOS_PARTICULARES,
                'description' => 'Permiso sin goce por asuntos particulares (máx. 2h/día, 6h/mes)',
                'max_hours_per_day' => 2,
                'max_hours_per_month' => 6,
                'max_times_per_month' => null,
                'requires_document' => false,
                'validation_rules' => [
                    'without_pay' => true,
                    'proportional_discount' => true,
                    'subject_to_service_needs' => true,
                ],
            ],
        ];

        foreach ($permissionTypes as $typeData) {
            PermissionType::updateOrCreate(
                ['code' => $typeData['code']],
                $typeData
            );
        }
    }
}