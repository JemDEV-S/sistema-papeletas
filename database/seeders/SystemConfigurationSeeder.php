<?php

namespace Database\Seeders;

use App\Models\SystemConfiguration;
use Illuminate\Database\Seeder;

class SystemConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            [
                'key' => 'app_name',
                'value' => 'Sistema de Papeletas Digitales',
                'description' => 'Nombre de la aplicación',
                'data_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'organization_name',
                'value' => 'Municipalidad Distrital de San Jerónimo',
                'description' => 'Nombre de la organización',
                'data_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'session_timeout',
                'value' => '120',
                'description' => 'Tiempo de expiración de sesión en minutos',
                'data_type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'max_file_upload_size',
                'value' => '5120',
                'description' => 'Tamaño máximo de archivo en KB',
                'data_type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'allowed_file_types',
                'value' => '["pdf","jpg","jpeg","png","doc","docx"]',
                'description' => 'Tipos de archivo permitidos para subir',
                'data_type' => 'json',
                'is_public' => false,
            ],
            [
                'key' => 'approval_timeout_hours',
                'value' => '72',
                'description' => 'Horas para timeout de aprobación automática',
                'data_type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'notification_email_enabled',
                'value' => 'true',
                'description' => 'Habilitar notificaciones por email',
                'data_type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'notification_sms_enabled',
                'value' => 'false',
                'description' => 'Habilitar notificaciones por SMS',
                'data_type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'biometric_device_enabled',
                'value' => 'false',
                'description' => 'Habilitar integración con dispositivo biométrico',
                'data_type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'digital_signature_enabled',
                'value' => 'false',
                'description' => 'Habilitar firma digital',
                'data_type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'working_hours_start',
                'value' => '08:00',
                'description' => 'Hora de inicio de jornada laboral',
                'data_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'working_hours_end',
                'value' => '17:00',
                'description' => 'Hora de fin de jornada laboral',
                'data_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'lunch_break_start',
                'value' => '13:00',
                'description' => 'Hora de inicio del almuerzo',
                'data_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'lunch_break_end',
                'value' => '14:00',
                'description' => 'Hora de fin del almuerzo',
                'data_type' => 'string',
                'is_public' => true,
            ],
            [
                'key' => 'backup_retention_days',
                'value' => '90',
                'description' => 'Días de retención de backups',
                'data_type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'audit_log_retention_days',
                'value' => '2555', // 7 años
                'description' => 'Días de retención de logs de auditoría',
                'data_type' => 'integer',
                'is_public' => false,
            ],
            [
                'key' => 'default_permission_balance_reset',
                'value' => 'monthly',
                'description' => 'Frecuencia de reseteo de saldos de permisos',
                'data_type' => 'string',
                'is_public' => false,
            ],
            [
                'key' => 'auto_approve_emergency',
                'value' => 'false',
                'description' => 'Auto-aprobar permisos de emergencia',
                'data_type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'require_supervisor_signature',
                'value' => 'true',
                'description' => 'Requerir firma digital del supervisor',
                'data_type' => 'boolean',
                'is_public' => false,
            ],
            [
                'key' => 'require_hr_signature',
                'value' => 'true',
                'description' => 'Requerir firma digital de RRHH',
                'data_type' => 'boolean',
                'is_public' => false,
            ],
        ];

        foreach ($configurations as $configData) {
            SystemConfiguration::updateOrCreate(
                ['key' => $configData['key']],
                $configData
            );
        }
    }
}